<?php
/** Corrective sign-in settings, real privileges and server-rendered UI on a disposable site. */
if(!defined('WP_CLI')||!WP_CLI||'1'!==getenv('FILEWARDEN_TESTING')||!in_array(wp_parse_url(home_url(),PHP_URL_HOST),array('127.0.0.1','localhost','::1'),true)){exit(1);}
$fix_assert=static function($condition,$message){if(!$condition)throw new RuntimeException($message);};
$fix_reject=static function($callback,$class)use($fix_assert){try{$callback();}catch(Throwable $error){$fix_assert($error instanceof $class,'Wrong rejection: '.$error->getMessage());return;}throw new RuntimeException('Invalid/unauthorized sign-in setting accepted.');};
$fix_user=get_current_user_id();wp_set_current_user(1);
$fix_original=get_option(FileWarden\PublicationLogin::OPTION,null);
$fix_repo=new FileWarden\Repository();$fix_audit=new FileWarden\AuditService();
$fix_service=new FileWarden\DocumentService($fix_repo,new FileWarden\PermissionService(array($fix_repo,'folder')),new FileWarden\StorageService(FILEWARDEN_STORAGE_DIR,array(ABSPATH),true),$fix_audit);
$fix_fixture=json_decode(file_get_contents(getenv('FILEWARDEN_FIXTURES')),true);
$fix_document=$fix_repo->document($fix_fixture['public']);
try{
 foreach(array('publication','viewer')as$fix_css){
  $fix_version=FILEWARDEN_VERSION.'-'.substr(hash_file('sha256',FILEWARDEN_PATH.'assets/'.$fix_css.'.css'),0,12);
  $fix_assert(str_ends_with(FileWarden\FrontendAppearance::stylesheet_url($fix_css),'?ver='.$fix_version),'Public style URL does not identify same-version corrective contents.');
 }
 $fix_assert('wordpress'===FileWarden\PublicationLogin::current()['mode'],'Fresh correction did not preserve native login default.');
 $fix_return=home_url('/documents/');$fix_seen=null;
 $fix_filter=static function($url,$return)use(&$fix_seen){$fix_seen=$return;return home_url('/native-login-plugin/');};
 add_filter('login_url',$fix_filter,10,2);
 $fix_assert(home_url('/native-login-plugin/')===FileWarden\PublicationLogin::destination($fix_return)&&$fix_seen===$fix_return,'Native WordPress filter/return was bypassed.');
 remove_filter('login_url',$fix_filter,10);
 $fix_url='https://identity.example.test/login?client=filewarden&return=%2Fdocuments#form';
 $fix_value=array('mode'=>'custom','url'=>$fix_url);FileWarden\PublicationLogin::save($fix_value,$fix_audit);
 $fix_assert($fix_value===FileWarden\PublicationLogin::current(),'Sign-in preference was not persisted exactly.');
 $fix_assert($fix_url===FileWarden\PublicationLogin::destination($fix_return),'A return argument rewrote the custom provider URL.');
 foreach(array('javascript:alert(1)','//evil.test/login','https://user:password@example.test/login',"https://example.test/\r\nInjected",'/login')as$fix_bad){
  $fix_reject(fn()=>FileWarden\PublicationLogin::save(array('mode'=>'custom','url'=>$fix_bad),$fix_audit),InvalidArgumentException::class);
  $fix_assert($fix_value===FileWarden\PublicationLogin::current(),'A rejected setting partially replaced the stored destination.');
 }
 $fix_subscriber=get_user_by('login','fw_subscriber');$fix_had_read=isset($fix_subscriber->caps['filewarden_read_all_files']);$fix_subscriber->add_cap('filewarden_read_all_files');
 try{wp_set_current_user($fix_subscriber->ID);$fix_reject(fn()=>FileWarden\PublicationLogin::save(array('mode'=>'wordpress'),$fix_audit),DomainException::class);}finally{wp_set_current_user(1);if(!$fix_had_read)$fix_subscriber->remove_cap('filewarden_read_all_files');}
 $fix_assert($fix_document===$fix_repo->document($fix_fixture['public']),'Changing the sign-in link altered a document or its permissions.');
 update_option(FileWarden\PublicationLogin::OPTION,array('mode'=>'custom','url'=>'data:text/html,unsafe'));
 $fix_assert('wordpress'===FileWarden\PublicationLogin::current()['mode'],'Malformed stored data did not fall back to native WordPress.');
 FileWarden\PublicationLogin::save(array('mode'=>'wordpress'),$fix_audit);
 $fix_before_get=$_GET;$_GET=array();
 ob_start();FileWarden\Publisher::page($fix_service);$fix_html=ob_get_clean();$_GET=$fix_before_get;
 $fix_assert(2===substr_count($fix_html,'data-publication-tab='),'The two publication tabs are missing.');
 $fix_assert(!str_contains($fix_html,'<details class="fw-publication-integration"'),'Integration still starts in a folded disclosure.');
 $fix_assert((count(FileWarden\FrontendAppearance::presets())+1)===substr_count($fix_html,'class="fw-appearance-preset"')&&10===substr_count($fix_html,'data-color-picker='),'Compact templates lost a preset or a color control.');
 $fix_assert(str_contains($fix_html,'fw-icon-upload')&&str_contains($fix_html,'class="fw-appearance-checks"'),'Upload/check layout was not rendered.');
 $_GET=array('collection'=>'unfiled','search'=>'v17-');
 ob_start();(new FileWarden\AdminWorkspace($fix_service,new FileWarden\AdminController($fix_service)))->render();$fix_html=ob_get_clean();$_GET=$fix_before_get;
 $fix_assert((bool)preg_match('/<div class="fw-access-status"><span class="filewarden-policy">.*?<\/span><details class="fw-rule-info">/s',$fix_html),'The retained-rule icon is not beside the policy badge.');
 WP_CLI::success('Corrective V17 integration: public CSS content digests, native filtered login/return, literal custom URL, persistence, role/URL refusal, unchanged ACL, two tabs, all colors and inline rule note passed.');
}finally{
 wp_set_current_user(1);if(null===$fix_original)delete_option(FileWarden\PublicationLogin::OPTION);else update_option(FileWarden\PublicationLogin::OPTION,$fix_original);
 wp_set_current_user($fix_user);
}
