<?php
/** Resume a genuine schema-6 backup across an installed corrective ZIP upgrade. */
if(!defined('WP_CLI')||getenv('FILEWARDEN_TESTING')!=='1'||!str_contains(wp_normalize_path(ABSPATH),'/.tools/v18-dev/v18pending715/'))exit(1);
use FileWarden\{Repository,PermissionService,StorageService,DocumentService,AuditService,BackupService,BackupJobs,AuditHistory,AuditIp};
global $wpdb;
function fwp_assert($ok,$message){if(!$ok)throw new RuntimeException($message);}
wp_set_current_user(1);
$repo=new Repository();$storage=new StorageService(FILEWARDEN_STORAGE_DIR,[ABSPATH],true);$service=new DocumentService($repo,new PermissionService([$repo,'folder']),$storage,new AuditService());
$path=dirname(rtrim(ABSPATH,'/\\')).'/pending-proof.json';$stage=getenv('FILEWARDEN_PENDING_STAGE');
if($stage==='start'){
 fwp_assert(get_option('filewarden_schema')==='6','Original schema expected');
 $job=BackupService::start($service,'backup','disposable pending migration phrase');
 fwp_assert($job['status']==='working'&&get_option(BackupJobs::BARRIER),'Original backup not pending');
 file_put_contents($path,wp_json_encode(['job'=>$job['id'],'documents'=>$repo->documents(0)]));
}elseif($stage==='resume'){
 $proof=json_decode(file_get_contents($path),true);
 fwp_assert(get_option('filewarden_schema')==='6'&&!AuditIp::available(),'Migration did not defer');
 fwp_assert(has_action('wp_ajax_filewarden_backup')&&has_action('wp_ajax_filewarden_diagnostic'),'Recovery runtime not registered');
 $rows=AuditHistory::rows(AuditHistory::filters([]),AuditHistory::upper());fwp_assert(count($rows)>0,'Legacy audit inaccessible');
 do{$job=BackupService::tick($service,$proof['job']);}while($job['status']==='working');
 fwp_assert($job['status']==='ready'&&!get_option(BackupJobs::BARRIER),'Legacy encrypted backup not resumed');
 fwp_assert($repo->documents(0)===$proof['documents'],'Pending upgrade changed document metadata');
 $state=BackupJobs::load($service,$job['id']);fwp_assert($state['manifest']['schema']==='6','Original authenticated manifest changed');
}elseif($stage==='migrated'){
 $proof=json_decode(file_get_contents($path),true);
 fwp_assert(get_option('filewarden_schema')==='7'&&AuditIp::available(),'Migration did not run after release');
 fwp_assert($repo->documents(0)===$proof['documents'],'Final migration changed documents');
 foreach($proof['documents']as$row){$stream=$storage->open($row['storage_key'],(int)$row['size']);try{fwp_assert(hash('sha256',stream_get_contents($stream))===$row['sha256'],'Document bytes changed');}finally{fclose($stream);}}
 $proof=['passed'=>true,'schema_before'=>'6','schema_after'=>'7','resumed_job'=>true,'metadata_and_bytes_preserved'=>true];file_put_contents($path,wp_json_encode($proof));
}else{throw new RuntimeException('Unknown stage');}
WP_CLI::success('Pending legacy backup upgrade: '.$stage.' passed.');
