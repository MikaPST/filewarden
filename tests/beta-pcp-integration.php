<?php
/** Real SQL/catalog regression, restricted to explicitly disposable acceptance sites. */
require __DIR__.'/acceptance-path.php';
use FileWarden\{AuditHistory,AuditEvents,AuditService,Language,Repository,DocumentService,PermissionService,StorageService,Lifecycle,DocumentCollection,BackupManifest,BackupComponents,WordPressTables};
function fwb_assert($ok,$message){if(!$ok)throw new RuntimeException($message);}
function fwb_refuse($fn,$message){try{$fn();}catch(Throwable $e){return $e;}throw new RuntimeException($message);}
wp_set_current_user(1);global $wpdb;
$suffix=bin2hex(random_bytes(4));$actor='pcp_'.$suffix;$table=$wpdb->prefix.'filewarden_audit_logs';$fixture=[];
$auditIds=[];$insert=function($event,$login,$type='user',$id=1)use($wpdb,$table,&$auditIds){
 fwb_assert(false!==$wpdb->insert($table,['event'=>$event,'actor_login'=>$login,'actor_id'=>$id,'actor_type'=>$type,'created_at'=>'2026-11-28 10:20:30','resource_type'=>'','resource_name'=>'','resource_rules'=>'']),'Audit fixture insert failed');$auditIds[]=(int)$wpdb->insert_id;return (int)$wpdb->insert_id;
};
foreach(AuditEvents::CATALOG as$event=>$kind)$fixture[$insert($event,$actor)]=$event;
foreach(['document_uploaded_upper','DOCUMENT_UPLOADED','unknown,document_uploaded','unknown_%']as$event)$fixture[$insert($event,$actor)]=$event;
$upper=AuditHistory::upper();$assertRows=function($input,$expected,$bound=null)use($upper){
 $filters=AuditHistory::filters($input);$rows=AuditHistory::rows($filters,$bound??$upper,1000);$ids=array_map('intval',array_column($rows,'id'));sort($ids);sort($expected);fwb_assert($ids===$expected,'Audit selection mismatch: '.wp_json_encode($input));return$filters;
};
foreach(AuditEvents::actions()as$action=>$label)foreach(AuditEvents::statuses()as$status=>$unused){
 $expected=[];foreach($fixture as$id=>$event)if(AuditEvents::classify($event)===[$action,$status])$expected[]=$id;
 $assertRows(['actor_login'=>$actor,'actions'=>[$action],'statuses'=>[$status]],$expected);
}
$assertRows(['actor_login'=>$actor],array_keys($fixture));
$assertRows(['actor_login'=>$actor,'from'=>'','until'=>''],array_keys($fixture));
$assertRows(['actor_login'=>$actor,'from'=>'2026-11-28T10:20','until'=>'2026-11-28T10:20'],array_keys($fixture));
$assertRows(['actor_login'=>$actor,'actions'=>[],'statuses'=>[]],[]);
$assertRows(['actor_login'=>$actor,'from'=>'2026-11-28T10:21'],[]);
$assertRows(['actor_login'=>$actor,'until'=>'2026-11-28T10:19'],[]);
$quoted="pcp' OR 1=1 -- %_".$suffix;$literal=$insert('unknown_quote',$quoted);
$assertRows(['actor_login'=>$quoted],[$literal],AuditHistory::upper());
$assertRows(['actor_login'=>str_replace('%_','xx',$quoted)],[],AuditHistory::upper());
fwb_refuse(fn()=>AuditHistory::filters(['event'=>"x' OR 1=1"]),'Unvalidated event accepted');
// Legacy blank-login rows map to current users, imported source identities never do.
$login=get_userdata(1)->user_login;$legacyEvent='beta_legacy_'.$suffix;$legacy=$insert($legacyEvent,'');$imported=$insert($legacyEvent,'','source_user');
$legacyFilters=AuditHistory::filters(['actor_login'=>$login,'event'=>$legacyEvent]);
fwb_assert(array_map('intval',array_column(AuditHistory::rows($legacyFilters,AuditHistory::upper()),'id'))===[$legacy],'Imported source actor matched a current account');
// Review and deletion share the precise predicate; a newer event is outside the snapshot.
$filters=AuditHistory::filters(['actor_login'=>$actor,'actions'=>['upload'],'statuses'=>['success']]);
$review=AuditHistory::review($filters,$upper);fwb_assert($review['count']===1,'Purge scope differs from listing');
$late=$insert('document_uploaded',$actor);$purgeTrace=$insert('audit_purged',$actor);
fwb_refuse(fn()=>AuditHistory::purge($filters,$upper,'invalid'),'Forged purge proof accepted');
$oldErrors=$wpdb->suppress_errors(true);$fail=static fn($sql)=>str_starts_with($sql,'INSERT INTO `'.$GLOBALS['wpdb']->prefix.'filewarden_audit_logs`')&&str_contains($sql,'audit_purged')?'INVALID SQL FOR BETA ROLLBACK':$sql;
add_filter('query',$fail);try{fwb_refuse(fn()=>AuditHistory::purge($filters,$upper,$review['proof']),'Audit failure committed a purge');}finally{remove_filter('query',$fail);$wpdb->suppress_errors($oldErrors);}
fwb_assert(AuditHistory::review($filters,$upper)===$review,'Failed purge did not roll back');
AuditHistory::purge($filters,$upper,$review['proof']);fwb_assert(AuditHistory::review($filters,$upper)['count']===0,'Reviewed event was not deleted');
foreach([$late,$purgeTrace,$imported]as$id)fwb_assert((int)$wpdb->get_var($wpdb->prepare('SELECT COUNT(*) FROM %i WHERE id=%d',$table,$id))===1,'Purge crossed its boundary');
$reader=wp_insert_user(['user_login'=>'pcp_reader_'.$suffix,'user_pass'=>'disposable-test-password','role'=>'subscriber']);wp_set_current_user($reader);
fwb_refuse(fn()=>AuditHistory::rows($filters,$upper),'Audit read allowed without capability');fwb_refuse(fn()=>AuditHistory::review($filters,$upper),'Purge review allowed without capability');wp_set_current_user(1);
// These deliberately malformed legacy SQL fixtures are not valid archive events.
// Remove only this run's explicitly recorded synthetic IDs before testing full backups.
foreach($auditIds as$id)$wpdb->delete($table,['id'=>$id],['%d']);
// Catalog selection never changes WordPress's locale or another domain's catalog path.
$oldChoice=Language::choice();$oldSite=get_option('WPLANG');$core=__('Settings');
foreach([['fr_CA','auto','Général'],['de_DE','auto','General'],['en_US','fr_FR','Général'],['fr_FR','en_US','General']]as[$site,$choice,$expected]){
 update_option('WPLANG',$site);update_option('filewarden_language',$choice);unload_textdomain('filewarden',false);Language::register();fwb_assert(__('General','filewarden')===$expected,'Bundled language fallback failed');fwb_assert(__('Settings')===$core,'Core translations changed');fwb_assert(Language::catalog('/unchanged.mo','other-domain')==='/unchanged.mo','Another domain was redirected');
}
update_option('WPLANG',$oldSite);update_option('filewarden_language',$oldChoice);unload_textdomain('filewarden',false);Language::register();
// Selective manifest scopes use real active/trash documents and preserve the original ACL boundary.
$repo=new Repository();$storage=new StorageService(FILEWARDEN_STORAGE_DIR,[ABSPATH],true);$service=new DocumentService($repo,new PermissionService([$repo,'folder']),$storage,new AuditService());
$folder=$service->create_folder('Beta PCP '.$suffix,0,['type'=>'roles','roles'=>['administrator']]);$store=new ReflectionMethod(DocumentService::class,'store');$source=tempnam(sys_get_temp_dir(),'fw-beta-');$ids=[];
foreach(['active','trash']as$kind){file_put_contents($source,$kind.' '.$suffix);$ids[$kind]=$store->invoke($service,['tmp_name'=>$source,'name'=>$kind.'-'.$suffix.'.txt'],$folder,['type'=>'inherit'])['id'];}unlink($source);(new Lifecycle($service))->trash($ids['trash']);
foreach([[true,false],[false,true],[true,true],[false,false]]as[$active,$trash]){
 $flags=BackupComponents::defaults();$flags['documents']=$active;$flags['trash']=$trash;[$manifest,$keys]=BackupManifest::capture($flags);$present=array_column($manifest['documents'],'uuid');
 fwb_assert(in_array($ids['active'],$present,true)===$active&&in_array($ids['trash'],$present,true)===$trash,'Manifest scope includes omitted content');
 fwb_assert(count($keys)===count($manifest['documents']),'Storage key scope differs');
}
$trashRows=array_column(DocumentCollection::rows($service,true),'uuid');fwb_assert(in_array($ids['trash'],$trashRows,true)&&!in_array($ids['active'],$trashRows,true),'Trash collection mixed active files');
wp_set_current_user($reader);fwb_refuse(fn()=>DocumentCollection::rows($service,true),'Unauthorized trash collection allowed');wp_set_current_user(1);
// Fingerprints exclude only the converter checkpoint, and include ordinary options.
$snapshot=new ReflectionMethod(WordPressTables::class,'snapshot');$before=$snapshot->invoke(null,'options');$checkpoint=get_option(WordPressTables::CHECKPOINT,null);update_option(WordPressTables::CHECKPOINT,['beta_fixture'=>$suffix],false);$during=$snapshot->invoke(null,'options');
fwb_assert($before['data']===$during['data'],'Checkpoint included in table content fingerprint');
update_option('filewarden_beta_fixture_'.$suffix,'changed');$after=$snapshot->invoke(null,'options');fwb_assert($after['data']!==$during['data'],'Ordinary options excluded from fingerprint');delete_option('filewarden_beta_fixture_'.$suffix);if($checkpoint===null)delete_option(WordPressTables::CHECKPOINT);else update_option(WordPressTables::CHECKPOINT,$checkpoint,false);
$proof=['version'=>FILEWARDEN_VERSION,'wordpress'=>get_bloginfo('version'),'audit_taxonomy_combinations'=>count(AuditEvents::actions())*count(AuditEvents::statuses()),'literal_quotes_percent_underscore'=>true,'source_actor_separation'=>true,'snapshot_purge_and_rollback'=>true,'capability_denials'=>true,'catalog_choices_and_fallback'=>true,'selective_backup_scopes'=>4,'trash_boundary'=>true,'checkpoint_exclusion'=>true];
file_put_contents(fw_acceptance_path('beta-pcp-result.json'),wp_json_encode($proof,JSON_PRETTY_PRINT));WP_CLI::success('Beta PCP regression: literal SQL filters, full taxonomy/case, bounded atomic purge, capability refusals, isolated FR/EN fallback, four backup scopes, trash and table fingerprints passed.');
