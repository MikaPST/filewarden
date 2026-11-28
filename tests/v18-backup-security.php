<?php
/** Adversarial manifest and job recovery checks on disposable local WordPress. */
if(!defined('WP_CLI')||!WP_CLI||getenv('FILEWARDEN_TESTING')!=='1'||!preg_match('~/.tools/v(?:18|19)-dev/~',wp_normalize_path(ABSPATH))||(!in_array(home_url(),['http://127.0.0.1:8092','http://127.0.0.1:8091'],true)&&!(getenv('FILEWARDEN_MANIFEST_ONLY')==='1'&&wp_parse_url(home_url(),PHP_URL_HOST)==='127.0.0.1')))exit(1);
use FileWarden\{BackupManifest,BackupService,BackupJobs,BackupRestore,Repository,PermissionService,StorageService,DocumentService,AuditService};
global $wpdb;wp_set_current_user(1);$repo=new Repository();$storage=new StorageService(FILEWARDEN_STORAGE_DIR,[ABSPATH],true);$service=new DocumentService($repo,new PermissionService([$repo,'folder']),$storage,new AuditService());if(getenv('FILEWARDEN_MANIFEST_ONLY')!=='1')BackupService::empty_library();
$expected=json_decode(file_get_contents(getenv('FILEWARDEN_BACKUP_RESULT')),true);$manifest=$expected['manifest'];
$reject=static function($fn,$message){try{$fn();}catch(Throwable $error){return;}throw new RuntimeException($message);};
$changes=[
 static function(&$m){$m['documents'][1]['uuid']=strtoupper($m['documents'][0]['uuid']);},static function(&$m){$m['documents'][0]['mime']='text/html';},static function(&$m){$m['version']=999;},static function(&$m){$m['schema']='999';},
 static function(&$m){$m['folders'][0]['parent_id']=$m['folders'][0]['id'];},static function(&$m){$m['documents'][0]['folder_id']=999999;},
 static function(&$m){$m['documents'][0]['uuid']='../../wp-config.php';},static function(&$m){$m['documents'][0]['size']=BackupManifest::MAX_FILE+1;},
 static function(&$m){$m['documents'][0]['storage_key']='../../wp-config.php';},static function(&$m){$m['documents'][0]['retained_rules']='[null]';},
 static function(&$m){$m['documents'][0]['policy']='{"type":"roles","roles":["../administrator"]}';},
 static function(&$m){$m['folders'][0]['policy']='{"type":"inherit"}';},
 static function(&$m){$m['settings']['appearance']['logo_id']=123;},static function(&$m){$m['settings']['appearance']['colors']['text']='url(https://invalid.test)';},
 static function(&$m){$m['audit_logs'][0]['event']='DROP TABLE';},static function(&$m){$m['audit_logs'][0]['actor_id']='99999999999999999999';},
 static function(&$m){$m['documents'][0]['name']=['invalid'];},static function(&$m){$m['documents'][]=$m['documents'][0];},
];
foreach($changes as$change){$mutated=$manifest;$change($mutated);$reject(fn()=>BackupManifest::validate($mutated),'Malicious manifest accepted');}
$filter=static fn($sql)=>str_contains($sql,'SHOW TABLE STATUS')&&str_contains($sql,$GLOBALS['wpdb']->options)?"SELECT '".$GLOBALS['wpdb']->options."' AS Name, 'MyISAM' AS Engine UNION ALL SELECT '".$GLOBALS['wpdb']->posts."','InnoDB' UNION ALL SELECT '".$GLOBALS['wpdb']->postmeta."','InnoDB'":$sql;
add_filter('query',$filter);try{$reject(fn()=>BackupRestore::require_transactions(),'Nontransactional WordPress metadata accepted');}finally{remove_filter('query',$filter);}
if(getenv('FILEWARDEN_MANIFEST_ONLY')==='1'){WP_CLI::success('v18 security: eighteen malformed manifests and transactional WordPress metadata refusal passed.');return;}
$archive=$expected['archive'];$phrase='disposable migration test phrase 2026';
$job=BackupService::start($service,'restore',$phrase,$archive);$state=BackupJobs::load($service,$job['id']);$key=bin2hex(random_bytes(32));$state['pending']=$key;BackupJobs::save($service,$state);file_put_contents($storage->path($key),'incomplete private fixture');
$job=BackupService::tick($service,$job['id']);if(is_file($storage->path($key)))throw new RuntimeException('Interrupted pending object was not cleaned');
$state=BackupJobs::load($service,$job['id']);$staged=array_values($state['staged']);BackupJobs::exclusive(fn()=>BackupJobs::cleanup($service,$state));foreach($staged as$key)if(is_file($storage->path($key)))throw new RuntimeException('Cancellation left staged bytes');if(get_option(BackupJobs::BARRIER))throw new RuntimeException('Cancellation kept barrier');
$job=BackupService::start($service,'restore',$phrase,$archive);$job=BackupService::tick($service,$job['id']);$state=BackupJobs::load($service,$job['id']);$state['expires']=time()-1;BackupJobs::save($service,$state);
$reject(fn()=>BackupService::tick($service,$job['id']),'Expired job continued');if(get_option(BackupJobs::BARRIER)||is_file(BackupJobs::path($service,$job['id'])))throw new RuntimeException('Expired job was not cleaned');BackupService::empty_library();
WP_CLI::success('v18 security: eighteen malformed manifests, WordPress MyISAM refusal, interrupted staging retry, cancel and expiry cleanup passed without activating metadata.');
