<?php
/** Exact comparison of real decrypted streams, collisions and reviewed ACL changes. */
if(!defined('WP_CLI')||getenv('FILEWARDEN_TESTING')!=='1'||!str_contains(wp_normalize_path(ABSPATH),'/.tools/v19-dev/v19backupsource/')||wp_parse_url(home_url(),PHP_URL_HOST)!=='127.0.0.1')exit(1);
use FileWarden\{Repository,PermissionService,StorageService,ManagedStorage,DocumentService,AuditService,ContentDuplicates,DuplicateContent,DocumentDescription,BackupManifest,LibraryQuery};
function fwds_assert($v,$why){if(!$v)throw new RuntimeException($why);}
function fwds_refuse($fn,$why){try{$fn();}catch(Throwable $e){return$e;}throw new RuntimeException($why);}
global$wpdb;wp_set_current_user(1);$proof=json_decode(file_get_contents(dirname(__DIR__).'/.tools/v19-dev/document-result.json'),true);$repo=new Repository();
// Native encryption/decryption is real. Only the hosting HTTPS probe is bypassed on this disposable loopback fixture.
$managed=new ManagedStorage();(new ReflectionProperty($managed,'verified'))->setValue($managed,true);$storage=new StorageService('',[ABSPATH],false,$managed);$service=new DocumentService($repo,new PermissionService([$repo,'folder']),$storage,new AuditService());
$source=tempnam(sys_get_temp_dir(),'fwds-');$bytes=str_repeat('private original '.bin2hex(random_bytes(8))."\n",6000);$other=$bytes;$other[65536]='Q';file_put_contents($source,$other);$key=$storage->put($source,strlen($other));$uuid=wp_generate_uuid4();
$id=$repo->insert('documents',['uuid'=>$uuid,'folder_id'=>$proof['folder'],'name'=>'encrypted-'.bin2hex(random_bytes(6)).'.txt','description'=>'Private original description','storage_key'=>$key,'mime'=>'text/plain','size'=>strlen($other),'sha256'=>hash('sha256',$bytes),'policy'=>['type'=>'inherit']]);
try{
 file_put_contents($source,$bytes);fwds_assert(ContentDuplicates::find($service,$source,strlen($bytes),hash('sha256',$bytes))===null,'Hash alone accepted different original bytes');
 $wpdb->update($wpdb->prefix.'filewarden_documents',['sha256'=>hash('sha256',$other)],['id'=>$id]);file_put_contents($source,$other);
 fwds_assert(ContentDuplicates::find($service,$source,strlen($other),hash('sha256',$other))['uuid']===$uuid,'Encrypted original not compared');
 $store=new ReflectionMethod(DocumentService::class,'store');$store->setAccessible(true);$file=['tmp_name'=>$source,'name'=>'new-independent-'.bin2hex(random_bytes(4)).'.txt'];
 $review=fwds_refuse(fn()=>$store->invoke($service,$file,$proof['folder'],['type'=>'inherit']),'Encrypted duplicate imported without review');fwds_assert($review instanceof DuplicateContent,'Missing authorized review');
 $service->change_policy('document',$uuid,['type'=>'authenticated']);
 fwds_assert(fwds_refuse(fn()=>$store->invoke($service,$file,$proof['folder'],['type'=>'inherit'],['duplicate_proof'=>$review->review['proof']]),'Stale policy proof accepted') instanceof DuplicateContent,'Policy change not reviewed again');
 $fail=static fn($sql)=>str_starts_with($sql,'INSERT INTO `'.$GLOBALS['wpdb']->prefix.'filewarden_audit_logs`')&&str_contains($sql,'document_description_changed')?'INVALID SQL FOR DESCRIPTION ROLLBACK':$sql;
 $errors=$wpdb->suppress_errors(true);add_filter('query',$fail);fwds_refuse(fn()=>DocumentDescription::save($service,$uuid,'Must roll back'),'Failed audit changed description');remove_filter('query',$fail);$wpdb->suppress_errors($errors);
 fwds_assert($repo->document($uuid)['description']==='Private original description','Description rollback failed');DocumentDescription::save($service,$uuid,'');fwds_assert($repo->document($uuid)['description']==='','Empty description did not clear');
 // Both historical absence and hostile new fields are validated before an archive can activate.
 $legacyStorage=new StorageService(FILEWARDEN_STORAGE_DIR,[ABSPATH],true);
 // Snapshot validation reads metadata only, so mixed test storage does not open bytes here.
 [$manifest]=BackupManifest::capture();$old=$manifest;$old['schema']='8';foreach($old['documents']as&$row)unset($row['description']);unset($row);BackupManifest::validate($old);
 foreach([str_repeat('x',401),'<script>bad</script>',[1,2],"invalid\xff"]as$bad){$hostile=$manifest;$hostile['documents'][0]['description']=$bad;fwds_refuse(fn()=>BackupManifest::validate($hostile),'Hostile archive description accepted');}
 // A hidden candidate is skipped before opening its bytes, even if its binary cannot be opened.
 $service->change_policy('document',$uuid,['type'=>'roles','roles'=>['administrator']]);DocumentDescription::save($service,$uuid,'Private search token '.$uuid);
 $limited=get_user_by('login',$proof['limited_user']);$wpdb->update($wpdb->prefix.'filewarden_documents',['storage_key'=>str_repeat('f',64)],['id'=>$id]);
 try{
  wp_set_current_user($limited->ID);fwds_assert(ContentDuplicates::find($service,$source,strlen($other),hash('sha256',$other))===null,'Hidden encrypted candidate opened or disclosed');
  fwds_assert(LibraryQuery::rows($service,null,LibraryQuery::filters(['scope'=>'all','search'=>'Private search token '.$uuid]))===[],'Private description leaked through search');
 }finally{wp_set_current_user(1);$wpdb->update($wpdb->prefix.'filewarden_documents',['storage_key'=>$key],['id'=>$id]);}
}finally{
 // Only the exact row and binary generated above are removed; no existing fixture/library content is deleted.
 $storage->remove($key);fwds_assert($wpdb->delete($wpdb->prefix.'filewarden_documents',['uuid'=>$uuid,'storage_key'=>$key])===1,'Synthetic row cleanup failed');unlink($source);
}
WP_CLI::success('Native encrypted-stream exact comparison, forged equal hash/different bytes, stale ACL proof, description audit rollback/clear, old schema8 descriptions absent and hostile archive fields passed.');
