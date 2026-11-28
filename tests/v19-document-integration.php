<?php
/** Real WordPress/database/storage acceptance on explicitly disposable fixtures only. */
require __DIR__.'/acceptance-path.php';
use FileWarden\{Repository,PermissionService,StorageService,DocumentService,AuditService,DocumentDescription,ContentDuplicates,DuplicateContent,LibraryQuery,BackupManifest,BackupService,BackupJobs,BackupRestore,BackupComponents,Installer};
function fwd_assert($v,$why){if(!$v)throw new RuntimeException($why);}
function fwd_refuse($fn,$why){try{$fn();}catch(Throwable $e){return $e;}throw new RuntimeException($why);}
global $wpdb;wp_set_current_user(1);$root=dirname(__DIR__);$evidence=fw_acceptance_path('document-result.json');$stage=getenv('FW_DOCUMENT_STAGE');
if($stage==='before'){
 fwd_assert(get_option('filewarden_schema')==='8','Expected real schema8 upgrade source');
 $data=[];foreach(['folders','documents']as$kind)$data[$kind]=$wpdb->get_results('SELECT * FROM '.$wpdb->prefix.'filewarden_'.$kind.' ORDER BY id',ARRAY_A);
 file_put_contents($root.'/.tools/v19-dev/document-migration-before.json',wp_json_encode($data));WP_CLI::success('Schema8 original rows captured privately.');return;
}
fwd_assert(get_option('filewarden_schema')==='9','Schema9 not installed');
$repo=new Repository();$storage=new StorageService(FILEWARDEN_STORAGE_DIR,[ABSPATH],true);$service=new DocumentService($repo,new PermissionService([$repo,'folder']),$storage,new AuditService());
if($stage==='restore'){
 $oldArchive=$root.'/.tools/v19-dev/v19-full.fwbackup';
 if(is_file($oldArchive)){
  $legacy=BackupService::start($service,'restore','disposable v19 backup password 2026',$oldArchive);
  do{$legacy=BackupService::tick($service,$legacy['id']);}while($legacy['status']==='working');
  fwd_assert($legacy['status']==='review','Old schema8 archive unreadable');BackupJobs::cleanup($service,BackupJobs::load($service,$legacy['id']));
 }
 $proof=json_decode(file_get_contents($evidence),true);$job=BackupService::start($service,'restore','document-tests-password-strong',$proof['archive']);
 do{$job=BackupService::tick($service,$job['id']);}while($job['status']==='working');
 $roles=array_fill_keys($job['roles'],'administrator');$choices=BackupComponents::restoration(BackupJobs::load($service,$job['id'])['manifest']);$choices['creators']='current';
 $done=BackupRestore::apply($service,$job['id'],$roles,[],$choices);fwd_assert($done['status']==='restored','Restore failed');
 foreach($proof['descriptions']as$uuid=>$text){$row=$repo->document($uuid,true);fwd_assert($row&&($row['description']??'')===$text,'Description not restored');$handle=$storage->open($row['storage_key'],(int)$row['size']);fwd_assert(hash('sha256',stream_get_contents($handle))===$row['sha256'],'Restored content changed');fclose($handle);}
 WP_CLI::success('Cross-installation encrypted restoration retains descriptions and original bytes.');return;
}
if($stage!=='ci'){
$before=json_decode(file_get_contents($root.'/.tools/v19-dev/document-migration-before.json'),true);
foreach($before as$kind=>$rows)foreach($rows as$old){$now=$wpdb->get_row($wpdb->prepare('SELECT * FROM %i WHERE id=%d',$wpdb->prefix.'filewarden_'.$kind,$old['id']),ARRAY_A);if($kind==='documents'){fwd_assert($now['description']===null,'Old description invented');unset($now['description']);}fwd_assert($old===$now,'Schema migration changed original data');}
}
Installer::migrate();fwd_assert(get_option('filewarden_schema')==='9','Repeat migration failed');
$suffix=bin2hex(random_bytes(4));$folder=$service->create_folder('Document lot '.$suffix,0,['type'=>'public']);$restricted=$service->create_folder('Private '.$suffix,0,['type'=>'roles','roles'=>['administrator']]);
$path=tempnam(sys_get_temp_dir(),'fwd-');$bytes='Original content '.$suffix."\n";file_put_contents($path,$bytes);$hash=hash('sha256',$bytes);
$insert=function($name,$destination,$text,$digest=null)use($repo,$storage,$path){file_put_contents($path,$text);$uuid=wp_generate_uuid4();$repo->insert('documents',['uuid'=>$uuid,'folder_id'=>$destination,'name'=>$name,'description'=>'','storage_key'=>$storage->put($path,strlen($text)),'mime'=>'text/plain','size'=>strlen($text),'sha256'=>$digest??hash('sha256',$text),'policy'=>['type'=>'inherit']]);return$uuid;};
$hidden=$insert('Hidden '.$suffix.'.txt',$restricted,$bytes);
$uid=wp_insert_user(['user_login'=>'fw_document_'.$suffix,'user_pass'=>'disposable-test-password','role'=>'subscriber','user_email'=>$suffix.'@example.test']);$user=get_user_by('id',$uid);foreach(['filewarden_manage_settings','filewarden_manage_permissions','filewarden_edit_files','filewarden_upload_files']as$cap)$user->add_cap($cap);wp_set_current_user($uid);
file_put_contents($path,$bytes);fwd_assert(ContentDuplicates::find($service,$path,strlen($bytes),$hash)===null,'Unreadable candidate disclosed');
wp_set_current_user(1);$visible=$insert('Visible '.$suffix.'.txt',$folder,$bytes);
$store=new ReflectionMethod(DocumentService::class,'store');$store->setAccessible(true);
wp_set_current_user($uid);file_put_contents($path,$bytes);
$count=(int)$wpdb->get_var('SELECT COUNT(*) FROM '.$wpdb->prefix.'filewarden_documents');
$error=fwd_refuse(fn()=>$store->invoke($service,['tmp_name'=>$path,'name'=>'Independent '.$suffix.'.txt'],$folder,['type'=>'inherit']),'Duplicate imported without review');
fwd_assert($error instanceof DuplicateContent&&$error->review['uuid']===$visible&&!str_contains(wp_json_encode($error->review),'Hidden'),'Authorized match missing or private name leaked');
fwd_assert((int)$wpdb->get_var('SELECT COUNT(*) FROM '.$wpdb->prefix.'filewarden_documents')===$count,'Review persisted a file');
$proof=$error->review['proof'];
fwd_assert(fwd_refuse(fn()=>$store->invoke($service,['tmp_name'=>$path,'name'=>'Independent '.$suffix.'.txt'],$folder,['type'=>'inherit'],['duplicate_proof'=>'wrong']),'Forged proof accepted') instanceof DuplicateContent,'Wrong proof not reviewed');
$copy=$store->invoke($service,['tmp_name'=>$path,'name'=>'Independent '.$suffix.'.txt'],$folder,['type'=>'inherit'],['duplicate_proof'=>$proof,'description'=>'Repère descriptif '.$suffix]);
fwd_assert($copy['id']!==$visible&&$repo->document($copy['id'])['storage_key']!==$repo->document($visible)['storage_key'],'Copy not independent');
$new=$repo->document($copy['id']);$repo->update_document((int)$new['id'],['retained_rules'=>wp_json_encode([['type'=>'authenticated']])]);
$beforeRules=$repo->document($copy['id']);$fail=static fn($sql)=>str_starts_with($sql,'INSERT INTO `'.$GLOBALS['wpdb']->prefix.'filewarden_audit_logs`')&&str_contains($sql,'document_policy_changed')?'INVALID SQL FOR DOCUMENT ATOMICITY':$sql;
$oldErrors=$wpdb->suppress_errors(true);add_filter('query',$fail);fwd_refuse(fn()=>$service->change_policy('document',$copy['id'],['type'=>'public']),'Audit failure activated rights');remove_filter('query',$fail);$wpdb->suppress_errors($oldErrors);
fwd_assert($repo->document($copy['id'])['retained_rules']===$beforeRules['retained_rules']&&$repo->document($copy['id'])['policy']===$beforeRules['policy'],'Policy failure changed retained rules');
$service->change_policy('document',$copy['id'],['type'=>'public']);fwd_assert(empty($repo->document($copy['id'])['retained_rules']),'Explicit policy did not clear pastille source');
$emoji=str_repeat('🌿',400);DocumentDescription::save($service,$copy['id'],$emoji);fwd_assert($repo->document($copy['id'])['description']===$emoji,'400 Unicode characters rejected');
fwd_refuse(fn()=>DocumentDescription::save($service,$copy['id'],$emoji.'x'),'401 characters accepted');fwd_refuse(fn()=>DocumentDescription::validate("bad\xff"),'Invalid UTF-8 accepted');
DocumentDescription::save($service,$copy['id'],'<b>Repère</b> descriptif '.$suffix);fwd_assert($repo->document($copy['id'])['description']==='Repère descriptif '.$suffix,'Plain text not enforced');
$matches=LibraryQuery::rows($service,null,LibraryQuery::filters(['scope'=>'all','search'=>'Repere descriptif '.$suffix]));fwd_assert(count($matches)===1&&$matches[0]['uuid']===$copy['id']&&$matches[0]['description_match'],'Authorized description search failed');
$user->remove_cap('filewarden_edit_files');wp_set_current_user(0);wp_set_current_user($uid);fwd_refuse(fn()=>DocumentDescription::save($service,$copy['id'],'forbidden'),'Read-only user edited description');$user->add_cap('filewarden_edit_files');wp_set_current_user(1);
// Export through the real authenticated archive, not a JSON-only test.
$job=BackupService::start($service,'backup','document-tests-password-strong');do{$job=BackupService::tick($service,$job['id']);}while($job['status']==='working');
$archive=fw_acceptance_path('document-lot.fwbackup');copy(BackupJobs::path($service,$job['id'],'fwbackup'),$archive);
$descriptions=[];foreach($wpdb->get_results('SELECT uuid,description FROM '.$wpdb->prefix.'filewarden_documents',ARRAY_A)as$row)$descriptions[$row['uuid']]=$row['description']??'';
file_put_contents($evidence,wp_json_encode(['schema'=>'9','folder'=>$folder,'document'=>$copy['id'],'visible'=>$visible,'hidden'=>$hidden,'archive'=>$archive,'descriptions'=>$descriptions,'limited_user'=>$user->user_login,'browser'=>['folder'=>$folder,'document'=>$copy['id']]],JSON_PRETTY_PRINT));
unlink($path);WP_CLI::success('Schema8->9 preserves original data; exact authorized duplicate review/independent copy, stale proof, atomic policy rollback, retained-rule replacement, Unicode/plain text, denied edits, search and encrypted export passed.');
