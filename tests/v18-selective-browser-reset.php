<?php
/** Remove only the known synthetic migration rows from the dedicated disposable target. */
if(!defined('WP_CLI')||!WP_CLI||getenv('FILEWARDEN_TESTING')!=='1'||!str_contains(ABSPATH,'v18uiselect715')||wp_parse_url(home_url(),PHP_URL_HOST)!=='127.0.0.1')exit(1);
use FileWarden\{ManagedStorage,StorageService,BackupJobs};
global $wpdb;$path=getenv('FILEWARDEN_SELECTIVE_RESULT');if(!$path||!str_contains($path,'.tools'))throw new RuntimeException('Fixture proof missing');
$m=json_decode(file_get_contents($path),true)['cases']['complete']['manifest'];$rows=$wpdb->get_results("SELECT uuid,storage_key FROM {$wpdb->prefix}filewarden_documents",ARRAY_A);
$ids=array_column($m['documents'],'uuid');if(count($rows)!==count($ids)||array_diff(array_column($rows,'uuid'),$ids)||get_option(BackupJobs::BARRIER))throw new RuntimeException('Unexpected target content or active job');
$managed=new ManagedStorage();(new ReflectionProperty($managed,'verified'))->setValue($managed,true);$storage=new StorageService('',[ABSPATH],false,$managed);
foreach($rows as$row)$storage->remove($row['storage_key']);foreach(['documents','folders','audit_logs']as$kind)$wpdb->query($wpdb->prepare('DELETE FROM %i',$wpdb->prefix.'filewarden_'.$kind));
update_option('filewarden_language','en_US',false);WP_CLI::success('Known disposable target emptied for actual browser restoration.');
