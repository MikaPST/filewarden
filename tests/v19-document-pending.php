<?php
if(!defined('WP_CLI')||getenv('FILEWARDEN_TESTING')!=='1'||!str_contains(wp_normalize_path(ABSPATH),'/.tools/v19-dev/v19pending/')||wp_parse_url(home_url(),PHP_URL_HOST)!=='127.0.0.1')exit(1);
use FileWarden\{Repository,PermissionService,StorageService,DocumentService,AuditService,BackupService,BackupJobs,BackupManifest,AuditIp};
function fwdp_assert($v,$why){if(!$v)throw new RuntimeException($why);}
wp_set_current_user(1);$repo=new Repository();$service=new DocumentService($repo,new PermissionService([$repo,'folder']),new StorageService(FILEWARDEN_STORAGE_DIR,[ABSPATH],true),new AuditService());$file=dirname(__DIR__).'/.tools/v19-dev/document-pending.json';$stage=getenv('FW_DOCUMENT_STAGE');
if($stage==='start'){
 fwdp_assert(get_option('filewarden_schema')==='8','Expected real old schema8');$job=BackupService::start($service,'backup','pending schema8 backup password');file_put_contents($file,wp_json_encode(['id'=>$job['id'],'rows'=>$repo->documents(0)]));fwdp_assert(get_option(BackupJobs::BARRIER),'Missing barrier');
}elseif($stage==='resume'){
 fwdp_assert(get_option('filewarden_schema')==='8'&&AuditIp::available(),'Migration did not defer schema8');$proof=json_decode(file_get_contents($file),true);$capture=BackupManifest::capture();fwdp_assert($capture[0]['schema']==='8','Diagnostic snapshot broke deferred schema8');
 do{$job=BackupService::tick($service,$proof['id']);}while($job['status']==='working');fwdp_assert($job['status']==='ready'&&!get_option(BackupJobs::BARRIER),'Pending job did not finish');fwdp_assert($repo->documents(0)===$proof['rows'],'Pending upgrade changed records');
}else{
 fwdp_assert(get_option('filewarden_schema')==='9','Deferred migration failed');$proof=json_decode(file_get_contents($file),true);$rows=$repo->documents(0);foreach($rows as&$row){fwdp_assert($row['description']===null,'Description invented');unset($row['description']);}fwdp_assert($rows===$proof['rows'],'Migration changed original data');
}
WP_CLI::success('Document lot pending schema8->9 phase '.$stage.' passed.');
