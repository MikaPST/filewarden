<?php
/** Real migration across two disposable WordPress databases. Never run on a user site. */
if (!defined('WP_CLI') || !WP_CLI || getenv('FILEWARDEN_TESTING')!=='1' || !in_array(wp_parse_url(home_url(),PHP_URL_HOST),['127.0.0.1','localhost'],true)) exit(1);
use FileWarden\{Repository,PermissionService,StorageService,ManagedStorage,DocumentService,AuditService,BackupService,BackupJobs,BackupRestore,BackupManifest,Lifecycle,FrontendAppearance};
function fw18b_assert($ok,$message){if(!$ok)throw new RuntimeException($message);}
function fw18b_reject($fn,$message){try{$fn();}catch(Throwable $error){return $error->getMessage();}throw new RuntimeException($message);}
global $wpdb;
wp_set_current_user(1);FileWarden\Installer::backup_capabilities();
$repo=new Repository();$permissions=new PermissionService([$repo,'folder']);
// Local PHP's HTTP server has no HTTPS/deny rules. Encryption is real; deployment probing is deliberately isolated from this fixture.
$managed=new ManagedStorage();$verified=new ReflectionProperty($managed,'verified');$verified->setValue($managed,true);
$storage=new StorageService('',[ABSPATH],false,$managed);$legacy=new StorageService(FILEWARDEN_STORAGE_DIR,[ABSPATH],true);$service=new DocumentService($repo,$permissions,$storage,new AuditService());
$stage=getenv('FILEWARDEN_BACKUP_STAGE');$output=getenv('FILEWARDEN_BACKUP_RESULT');fw18b_assert($output && str_contains($output,'.tools'),'Missing isolated proof path');
if($stage==='source-existing'){
    $prior=json_decode(file_get_contents($output),true);wp_set_current_user(0);$service->audit->record('settings_changed');wp_set_current_user(1);
    $job=BackupService::start($service,'backup','disposable migration test phrase 2026');
    do{$job=BackupService::tick($service,$job['id']);}while($job['status']==='working');
    $state=BackupJobs::load($service,$job['id']);$archive=dirname($output).'/migration-final.fwbackup';copy(BackupJobs::path($service,$job['id'],'fwbackup'),$archive);
    $prior['manifest']=$state['manifest'];$prior['archive']=$archive;$prior['archive_sha256']=hash_file('sha256',$archive);file_put_contents($output,wp_json_encode($prior));
    WP_CLI::success('Final source snapshot exported from the existing encrypted fixture with bounded metadata reads.');
}elseif($stage==='source'){
    foreach($repo->documents(0)as$row){$key=bin2hex(random_bytes(32));$storage->receive_chunks($key,(int)$row['size'],fn($consume)=>$legacy->visit_chunks($row['storage_key'],(int)$row['size'],$consume));$wpdb->update($wpdb->prefix.'filewarden_documents',['storage_key'=>$key],['id'=>$row['id']]);}
    add_role('fw_source_reader','Migration reader',['read'=>true]);$creator=wp_insert_user(['user_login'=>'fw_source_creator','user_pass'=>'disposable-test-password','role'=>'fw_source_reader']);
    $folder=$service->create_folder('Migration restricted',0,['type'=>'roles','roles'=>['fw_source_reader']]);$child=$service->create_folder('Nested public',$folder,['type'=>'public']);
    $bytes="%PDF-1.4\n1 0 obj<</Type/Catalog>>endobj\n".str_repeat('backup fixture bytes ',10000)."\n%%EOF\n";
    $temporary=tempnam(sys_get_temp_dir(),'fw18-source');file_put_contents($temporary,$bytes);$key=$storage->put($temporary,strlen($bytes));unlink($temporary);
    $uuid=wp_generate_uuid4();$doc=$repo->insert('documents',['uuid'=>$uuid,'folder_id'=>$child,'name'=>'migrated.pdf','storage_key'=>$key,'mime'=>'application/pdf','size'=>strlen($bytes),'sha256'=>hash('sha256',$bytes),'policy'=>['type'=>'inherit'],'retained_rules'=>[['type'=>'authenticated','roles'=>[]]]]);$wpdb->update($wpdb->prefix.'filewarden_documents',['created_by'=>$creator],['id'=>$doc]);
    (new Lifecycle($service))->trash($uuid);$service->audit->record('document_uploaded',$doc);
    $png=base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO+jRZkAAAAASUVORK5CYII=');$upload=wp_upload_bits('migration-logo.png',null,$png);$logo=wp_insert_attachment(['post_title'=>'Migration logo','post_mime_type'=>'image/png','post_status'=>'inherit'],$upload['file']);
    $appearance=FrontendAppearance::defaults();$appearance['name']='Migration fixture';$appearance['logo_id']=$logo;FrontendAppearance::save($appearance,$service->audit);update_option('filewarden_language','fr_FR');update_option('filewarden_notification_seconds',15);update_option('filewarden_allow_all_types','1');
    $sourceKey=get_option(ManagedStorage::OPTION);$phrase='disposable migration test phrase 2026';$job=BackupService::start($service,'backup',$phrase);
    fw18b_reject(fn()=>$service->create_folder('must remain blocked',0,['type'=>'public']),'write barrier did not freeze mutations');
    fw18b_assert($repo->folder($folder)!==null,'read failed during snapshot');
    $foreign=wp_insert_user(['user_login'=>'fw_backup_foreign','user_pass'=>'disposable-test-password','role'=>'administrator']);wp_set_current_user($foreign);fw18b_reject(fn()=>BackupService::tick($service,$job['id']),'foreign owner accessed job');wp_set_current_user(1);
    // Simulate an interrupted append. Resume must truncate bytes beyond the last checkpoint.
    file_put_contents(BackupJobs::path($service,$job['id'],'fwbackup'),'incomplete uncommitted append',FILE_APPEND);
    do{$job=BackupService::tick($service,$job['id']);}while($job['status']==='working');fw18b_assert($job['status']==='ready'&&!get_option(BackupJobs::BARRIER),'source backup not released');
    $state=BackupJobs::load($service,$job['id']);$archive=dirname($output).'/migration.fwbackup';copy(BackupJobs::path($service,$job['id'],'fwbackup'),$archive);
    $cipher=file_get_contents($archive);fw18b_assert(!str_contains($cipher,'Migration fixture')&&!str_contains($cipher,'backup fixture bytes')&&!str_contains($cipher,$sourceKey),'archive leaked plaintext/key');
    fw18b_assert(!str_contains(file_get_contents(BackupJobs::path($service,$job['id'])),$phrase),'checkpoint leaked phrase');
    $expected=['manifest'=>$state['manifest'],'source_key'=>$sourceKey,'source_creator'=>$creator,'archive'=>$archive,'source_home'=>home_url(),'archive_sha256'=>hash_file('sha256',$archive)];file_put_contents($output,wp_json_encode($expected));
    WP_CLI::success('Source: real managed encryption, consistent snapshot barrier, owner denial, interrupted append recovery, encrypted metadata/content and complete archive prepared.');
}elseif($stage==='target'){
    $expected=json_decode(file_get_contents($output),true);$archive=$expected['archive'];
    // Remove only the one known fresh-install fixture, refusing any unexpected target content.
    $fresh=$repo->documents(0);fw18b_assert(count($fresh)===1&&$fresh[0]['name']==='fresh.pdf','Target is not the disposable fresh fixture');$legacy->remove($fresh[0]['storage_key']);$wpdb->delete($wpdb->prefix.'filewarden_documents',['id'=>$fresh[0]['id']]);
    wp_insert_user(['user_login'=>'fw_target_padding','user_pass'=>'disposable-test-password','role'=>'subscriber']);$creator=wp_insert_user(['user_login'=>'fw_destination_creator','user_pass'=>'disposable-test-password','role'=>'editor']);fw18b_assert($creator!==$expected['source_creator'],'Fixture user IDs must differ');
    fw18b_reject(fn()=>BackupService::start($service,'restore','incorrect secret phrase',$archive),'wrong phrase accepted');fw18b_assert(!get_option(BackupJobs::BARRIER),'wrong phrase left barrier');
    $tampered=dirname($output).'/tampered.fwbackup';$bytes=file_get_contents($archive);$bytes[strlen($bytes)-10]=chr(ord($bytes[strlen($bytes)-10])^1);file_put_contents($tampered,$bytes);
    $bad=BackupService::start($service,'restore','disposable migration test phrase 2026',$tampered);fw18b_reject(function()use($service,$bad){do{$bad=BackupService::tick($service,$bad['id']);}while($bad['status']==='working');},'tampered final archive accepted');fw18b_assert(!get_option(BackupJobs::BARRIER),'failed staging left barrier');BackupService::empty_library();
    $job=BackupService::start($service,'restore','disposable migration test phrase 2026',$archive);
    $job=BackupService::tick($service,$job['id']); // A subsequent request reloads an authenticated private checkpoint.
    do{$job=BackupService::tick($service,$job['id']);}while($job['status']==='working');fw18b_assert($job['status']==='review','whole archive authentication missing');
    $interrupted=BackupJobs::load($service,$job['id']);$interrupted['status']='activating';BackupJobs::save($service,$interrupted);$job=BackupService::tick($service,$job['id']);fw18b_assert($job['status']==='review','uncommitted activation did not return to review');
    fw18b_reject(fn()=>BackupRestore::apply($service,$job['id'],[],[]),'missing mappings accepted');BackupService::empty_library();
    $roles=['fw_source_reader'=>'editor'];$users=[];foreach($expected['manifest']['users']as$id=>$login)$users[$id]=(int)$id===$expected['source_creator']?$creator:1;
    $beforeKey=get_option(ManagedStorage::OPTION);fw18b_assert($beforeKey&&$beforeKey!==$expected['source_key'],'destination key not independently generated');
    // Inject a database write failure after some inserts and prove rollback keeps the library empty.
    $fail=static fn($sql)=>str_starts_with($sql,'INSERT INTO `'.$GLOBALS['wpdb']->prefix.'filewarden_documents`')?'INSERT INTO nonexistent_fw18_fixture VALUES (1)':$sql;
    $wpdb->suppress_errors(true);add_filter('query',$fail);try{fw18b_reject(fn()=>BackupRestore::apply($service,$job['id'],$roles,$users),'partial metadata activated');}finally{remove_filter('query',$fail);$wpdb->suppress_errors(false);}BackupService::empty_library();
    $failOptions=static fn($sql)=>str_starts_with($sql,'INSERT INTO `'.$GLOBALS['wpdb']->options.'`')&&str_contains($sql,'filewarden_root_uuid')?'INSERT INTO nonexistent_fw18_fixture VALUES (1)':$sql;
    $wpdb->suppress_errors(true);add_filter('query',$failOptions);try{fw18b_reject(fn()=>BackupRestore::apply($service,$job['id'],$roles,$users),'logo/options rollback failed');}finally{remove_filter('query',$failOptions);$wpdb->suppress_errors(false);}BackupService::empty_library();
    $rolled=BackupJobs::load($service,$job['id']);fw18b_assert(!isset($rolled['logo_path'])||!is_file($rolled['logo_path']),'failed activation left public logo');
    $job=BackupRestore::apply($service,$job['id'],$roles,$users);fw18b_assert($job['status']==='restored'&&!get_option(BackupJobs::BARRIER),'restoration did not finish');fw18b_assert(get_option(ManagedStorage::OPTION)===$beforeKey,'destination master key overwritten');
    foreach($expected['manifest']['documents']as$source){$row=$repo->document($source['uuid'],true);fw18b_assert($row!==null,'UUID not preserved');foreach(['name','mime','size','sha256','created_at','trashed_at','folder_id']as$key)fw18b_assert((string)$row[$key]===(string)$source[$key],'document metadata changed: '.$key);$hash=hash_init('sha256');$storage->visit_chunks($row['storage_key'],(int)$row['size'],fn($bytes)=>hash_update($hash,$bytes));fw18b_assert(hash_final($hash)===$source['sha256'],'restored bytes differ');fw18b_assert(!str_contains(file_get_contents($storage->path($row['storage_key'])),'backup fixture bytes'),'destination stored plaintext');}
    foreach($expected['manifest']['folders']as$source){$row=$repo->folder((int)$source['id']);fw18b_assert($row['uuid']===$source['uuid']&&$row['name']===$source['name'],'folder identity lost');}
    fw18b_assert(FrontendAppearance::current()['name']==='Migration fixture'&&FrontendAppearance::current()['logo_id']>0,'public identity/logo lost');fw18b_assert(hash_file('sha256',get_attached_file(FrontendAppearance::current()['logo_id']))===$expected['manifest']['logo']['sha256'],'public logo bytes changed');
    fw18b_assert(get_option('filewarden_language')==='fr_FR'&&(int)get_option('filewarden_notification_seconds')===15&&!FileWarden\ImportPolicy::unrestricted(),'functional settings or safe defaults differ');
    $row=$repo->document($expected['manifest']['documents'][1]['uuid'],true);$row['trashed_at']=null;fw18b_assert(!$permissions->can_read($row,new WP_User(0)),'migration exposed restricted document');
    $logs=$wpdb->get_results("SELECT actor_id,actor_type,actor_login FROM {$wpdb->prefix}filewarden_audit_logs WHERE actor_type='imported'",ARRAY_A);fw18b_assert(count($logs)>0,'historical source actors not distinguished');
    fw18b_reject(fn()=>BackupService::start($service,'restore','disposable migration test phrase 2026',$archive),'non-empty library restoration accepted');
    $interrupted=BackupJobs::load($service,$job['id']);$interrupted['status']='activating';BackupJobs::save($service,$interrupted);$recovered=BackupService::tick($service,$job['id']);fw18b_assert($recovered['status']==='restored','committed activation status was not recovered');
    $auditRow=$wpdb->get_row("SELECT * FROM {$wpdb->prefix}filewarden_audit_logs WHERE actor_type='imported' AND resource_type='document' AND resource_rules IS NOT NULL LIMIT 1",ARRAY_A);
    fw18b_assert($auditRow&&isset(json_decode($auditRow['resource_rules'],true)['source_uuid']),'historical resources lack UUID binding');
    // Recovery after a committed activation is idempotent and must never remove live files.
    $again=BackupRestore::apply($service,$job['id'],$roles,$users);fw18b_assert($again['status']==='restored','committed activation recovery failed');
    file_put_contents(dirname($output).'/migration-result.json',wp_json_encode(['source'=>$expected['source_home'],'target'=>home_url(),'different_keys'=>true,'different_user_ids'=>true,'documents'=>count($expected['manifest']['documents']),'folders'=>count($expected['manifest']['folders']),'audit'=>count($expected['manifest']['audit_logs']),'archive_sha256'=>$expected['archive_sha256'],'passed'=>true]));
    WP_CLI::success('Target: wrong phrase/tamper cleanup, explicit mappings, transaction rollback, restored bytes/UUIDs/ACL/trash/logo/settings, distinct identities and keys, non-empty refusal and committed recovery passed.');
}else{throw new RuntimeException('Unknown test stage');}
