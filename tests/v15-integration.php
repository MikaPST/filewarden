<?php
/** Guarded by integration.php: real MyISAM tables and failure injection on disposable data only. */
if(!defined('WP_CLI')||!WP_CLI||'1'!==getenv('FILEWARDEN_TESTING'))exit(1);
wp_set_current_user(1);
$tables = array_keys(FileWarden\DatabaseUpgrade::status()['engines']);
fw_assert(3 === count($tables), 'Fresh install did not create all three transactional tables.');
fw_assert('ready' === FileWarden\DatabaseUpgrade::status()['state'], 'Explicit InnoDB creation failed.');
$repair_leaf = $service->create_folder('Engine repair leaf', 0, array('type'=>'authenticated'));
$life15 = new FileWarden\Lifecycle($service);
$repair_doc = $life15->transfer('copy', $ids['public'], $repair_leaf, 'repair-byte-preservation.pdf', $life15->plan($ids['public'], $repair_leaf)['proof']);
$repair_row = $repository->document($repair_doc['id']);
$repair_bytes = hash_file('sha256', $service->storage->path($repair_row['storage_key']));
$records = array();
foreach($tables as $table) {
    $records[$table] = $wpdb->get_results($wpdb->prepare('SELECT * FROM %i ORDER BY id', $table), ARRAY_A);
    fw_assert(false !== $wpdb->query($wpdb->prepare('ALTER TABLE %i ENGINE=MyISAM', $table)), 'MyISAM reproduction failed.');
}
fw_assert('incompatible' === FileWarden\DatabaseUpgrade::status()['state'], 'Actual MyISAM was not diagnosed.');
fw_reject(fn()=>FileWarden\FolderLifecycle::remove($service,$repair_leaf), RuntimeException::class, 'MyISAM deletion bypassed transactional safety.');
fw_reject(fn()=>FileWarden\DatabaseUpgrade::repair(false), InvalidArgumentException::class, 'Repair accepted missing backup confirmation.');
wp_set_current_user($users['subscriber']);
fw_reject(fn()=>FileWarden\DatabaseUpgrade::repair(true), DomainException::class, 'Subscriber could repair tables.');
wp_set_current_user(1);
// Deletion requests may append audit rows before checking the engine; refresh the exact snapshot.
foreach($tables as $table) $records[$table] = $wpdb->get_results($wpdb->prepare('SELECT * FROM %i ORDER BY id',$table),ARRAY_A);
$deny_alter = static function($query)use($wpdb){return str_starts_with($query,'ALTER TABLE `'.$wpdb->prefix.'filewarden_folders` ENGINE=InnoDB')?'FILEWARDEN_SIMULATED_ALTER_DENIAL':$query;};
add_filter('query',$deny_alter);
try { fw_reject(fn()=>FileWarden\DatabaseUpgrade::repair(true), RuntimeException::class, 'Denied ALTER claimed successful repair.'); }
finally {remove_filter('query',$deny_alter);}
fw_assert((bool)get_option(FileWarden\DatabaseUpgrade::CHECKPOINT),'Interrupted conversion lost its checkpoint.');
$pending_checkpoint=get_option(FileWarden\DatabaseUpgrade::CHECKPOINT);
FileWarden\Installer::activate();
fw_assert($pending_checkpoint===get_option(FileWarden\DatabaseUpgrade::CHECKPOINT),'Reactivation erased or rewrote an interrupted repair.');
fw_reject(fn()=>$service->create_folder('Must stay blocked',0,array('type'=>'public')), RuntimeException::class, 'Pending repair allowed document mutations.');
fw_reject(fn()=>$service->audit->record('must_not_be_written'), RuntimeException::class, 'Pending repair allowed audit writes.');
$audit_before_cleanup=$wpdb->get_results("SELECT * FROM {$wpdb->prefix}filewarden_audit_logs ORDER BY id",ARRAY_A);
$later_cron_callback=false;
$cron_probe=static function()use(&$later_cron_callback){$later_cron_callback=true;};
add_action('filewarden_audit_cleanup',$cron_probe,100);
try{do_action('filewarden_audit_cleanup');}finally{remove_action('filewarden_audit_cleanup',$cron_probe,100);}
fw_assert($later_cron_callback,'Pending repair aborted later WordPress cron callbacks.');
fw_assert($audit_before_cleanup===$wpdb->get_results("SELECT * FROM {$wpdb->prefix}filewarden_audit_logs ORDER BY id",ARRAY_A),'Pending repair allowed audit cleanup writes.');
$original_name=$repository->folder($repair_leaf)['name'];
$wpdb->update($wpdb->prefix.'filewarden_folders',array('name'=>'Simulated external drift'),array('id'=>$repair_leaf));
try{fw_reject(fn()=>FileWarden\DatabaseUpgrade::repair(true),RuntimeException::class,'Repair ignored changed data since its checkpoint.');}
finally{$wpdb->update($wpdb->prefix.'filewarden_folders',array('name'=>$original_name),array('id'=>$repair_leaf));}
fw_assert($pending_checkpoint===get_option(FileWarden\DatabaseUpgrade::CHECKPOINT),'Integrity refusal rewrote the original checkpoint.');
FileWarden\DatabaseUpgrade::repair(true);
fw_assert(!get_option(FileWarden\DatabaseUpgrade::CHECKPOINT),'Successful repair retained its write block.');
fw_assert('ready' === FileWarden\DatabaseUpgrade::status()['state'],'Resumed conversion did not verify all three engines.');
foreach($tables as $table) {
    $actual=$wpdb->get_results($wpdb->prepare('SELECT * FROM %i ORDER BY id',$table),ARRAY_A);
    foreach($records[$table]as$original){$found=array_values(array_filter($actual,static fn($row)=>$row['id']===$original['id']));fw_assert(array($original)===$found,'Repair changed an existing record.');}
    if(str_ends_with($table,'audit_logs')){
        $new=array_values(array_filter($actual,static fn($row)=>!in_array($row['id'],array_column($records[$table],'id'),true)));
        fw_assert(array('database_repair_requested','database_repaired')===array_column($new,'event'),'Repair audit outcomes were missing or invented.');
    }else fw_assert($records[$table]===$actual,'Repair changed document/folder records.');
}
fw_assert($repair_bytes === hash_file('sha256',$service->storage->path($repair_row['storage_key'])),'Repair changed encrypted document bytes.');
fw_assert($repair_row === $repository->document($repair_doc['id']),'Repair changed UUID, keys, policy or location.');
FileWarden\DatabaseUpgrade::repair(true); // Verified InnoDB is an idempotent no-op.
FileWarden\FolderLifecycle::remove($service,$repair_leaf);
$after_repair = $repository->document($repair_doc['id']);
fw_assert(null === $repository->folder($repair_leaf) && 0 === (int)$after_repair['folder_id'],'Repaired leaf deletion failed to recover its documents.');
fw_assert($repair_row['uuid'] === $after_repair['uuid'] && $repair_row['storage_key'] === $after_repair['storage_key'] && !empty($after_repair['retained_rules']),'Repaired deletion lost identity or inherited restrictions.');
// Metadata denial is different from a known incompatible engine.
$deny_status = static fn($query)=>str_starts_with($query,'SHOW TABLE STATUS WHERE Name IN')?'FILEWARDEN_SIMULATED_METADATA_DENIAL':$query;
add_filter('query',$deny_status);
try {fw_assert('unavailable' === FileWarden\DatabaseUpgrade::status()['state'],'Metadata denial was mistaken for MyISAM.');fw_reject(fn()=>FileWarden\DatabaseUpgrade::repair(true),RuntimeException::class,'Unverifiable metadata allowed repair.');}
finally {remove_filter('query',$deny_status);}
$missing_table = $wpdb->prefix.'filewarden_folders';
$renamed_table = $wpdb->prefix.'filewarden_folders_test_hidden';
$wpdb->query($wpdb->prepare('RENAME TABLE %i TO %i',$missing_table,$renamed_table));
try {fw_assert('missing' === FileWarden\DatabaseUpgrade::status()['state'],'Missing table was not diagnosed.');fw_reject(fn()=>FileWarden\DatabaseUpgrade::repair(true),RuntimeException::class,'Missing table allowed repair.');}
finally {$wpdb->query($wpdb->prepare('RENAME TABLE %i TO %i',$renamed_table,$missing_table));}
// Fresh tables must use InnoDB even when the server session defaults to MyISAM.
$old_prefix=$wpdb->prefix;
try {
    $wpdb->query('SET SESSION default_storage_engine=MyISAM');
    $wpdb->prefix=$old_prefix.'engine_test_';
    FileWarden\Installer::migrate();
    fw_assert('ready' === FileWarden\DatabaseUpgrade::status()['state'],'Installer inherited a MyISAM server default.');
} finally {
    foreach(array('documents','folders','audit_logs')as$kind)$wpdb->query($wpdb->prepare('DROP TABLE IF EXISTS %i',$old_prefix.'engine_test_filewarden_'.$kind));
    $wpdb->prefix=$old_prefix;
    $wpdb->query('SET SESSION default_storage_engine=InnoDB');
}
fw_assert('deleted' === FileWarden\AuditAppearance::event('document_purged')[0], 'Confirmed deletion is not distinguished from failure.');
fw_assert('warning' === FileWarden\AuditAppearance::event('document_purge_requested')[0], 'Destructive request looks completed.');
fw_assert('neutral' === FileWarden\AuditAppearance::event('unknown_event_failed')[0], 'Unknown event result was invented.');
// An independent connection cannot enter the write gate while a writer owns it.
$gate_key='fw_'.substr(hash('sha256',$wpdb->prefix.':database-write-gate'),0,60);
$held=$repository->lock('gate-concurrency-fixture');
$second=new wpdb(DB_USER,DB_PASSWORD,DB_NAME,DB_HOST);
try {fw_assert('0'===(string)$second->get_var($second->prepare('SELECT GET_LOCK(%s, 0)',$gate_key)),'A concurrent connection bypassed the write gate.');}
finally {$repository->unlock($held);}
fw_assert('1'===(string)$second->get_var($second->prepare('SELECT GET_LOCK(%s, 0)',$gate_key)),'Write gate leaked after release.');
$second->get_var($second->prepare('SELECT RELEASE_LOCK(%s)',$gate_key));$second->close();
// A compact summary must keep a real blocked-storage message and import limits outside its disclosure.
$limited=static fn()=>1;add_filter('upload_size_limit',$limited);
try {
    $blocked_storage=new FileWarden\StorageService('',array(ABSPATH),false);
    $blocked_service=new FileWarden\DocumentService($repository,$permission,$blocked_storage,$service->audit);
    ob_start();FileWarden\SettingsConsole::page($blocked_service,new FileWarden\AdminController($blocked_service));$blocked_markup=ob_get_clean();
    $summary=explode('<details class="fw-health-details">',$blocked_markup)[0];
    fw_assert(str_contains($summary,'fw-health-alert')&&str_contains($summary,'File operations remain blocked'),'Blocked storage was hidden inside health details.');
    fw_assert(str_contains($summary,'fw-health-warning'),'Configuration limits were hidden inside health details.');
}finally{remove_filter('upload_size_limit',$limited);}
WP_CLI::success('0.1.15 acceptance: real MyISAM reproduction, partial ALTER failure/resume, exact rows/index verification, document recovery, missing metadata/table, capability/confirmation refusal and explicit InnoDB under a MyISAM default passed.');
