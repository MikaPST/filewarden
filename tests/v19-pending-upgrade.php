<?php
/** Actual v18 pending-backup upgrade; loopback disposable fixture only. */
if(!defined('WP_CLI')||getenv('FILEWARDEN_TESTING')!=='1'||!str_contains(wp_normalize_path(ABSPATH),'/.tools/v19-dev/v19pending/')||wp_parse_url(home_url(),PHP_URL_HOST)!=='127.0.0.1')exit(1);
use FileWarden\{Repository,PermissionService,StorageService,DocumentService,AuditService,BackupService,BackupJobs,AuditIp};
function fwp19_assert($v,$m){if(!$v)throw new RuntimeException($m);}
wp_set_current_user(1);$repo=new Repository();$service=new DocumentService($repo,new PermissionService([$repo,'folder']),new StorageService(FILEWARDEN_STORAGE_DIR,[ABSPATH],true),new AuditService());$proof=dirname(rtrim(wp_normalize_path(ABSPATH),'/')).'/pending-proof.json';
if(getenv('FILEWARDEN_PENDING_STAGE')==='start'){
 fwp19_assert(FILEWARDEN_VERSION==='0.1.18-dev'&&get_option('filewarden_schema')==='7','V18 source required');$job=BackupService::start($service,'backup','disposable pending backup password 2026');file_put_contents($proof,wp_json_encode(['id'=>$job['id'],'documents'=>$repo->documents(0)]));fwp19_assert(get_option(BackupJobs::BARRIER),'Missing writer barrier');
}elseif(getenv('FILEWARDEN_PENDING_STAGE')==='resume'){
 fwp19_assert(FILEWARDEN_VERSION==='0.1.19-dev'&&get_option('filewarden_schema')==='7'&&AuditIp::available(),'V19 did not preserve pending schema-7 services');$before=json_decode(file_get_contents($proof),true);do{$job=BackupService::tick($service,$before['id']);}while($job['status']==='working');fwp19_assert($job['status']==='ready'&&!get_option(BackupJobs::BARRIER),'Legacy backup did not resume');fwp19_assert($repo->documents(0)===$before['documents'],'Pending upgrade changed library');
}else{
 $before=json_decode(file_get_contents($proof),true);fwp19_assert(get_option('filewarden_schema')==='8'&&AuditIp::available(),'Deferred migration did not complete');$now=$repo->documents(0);foreach($before['documents']as$i=>$row)foreach($row as$key=>$value)fwp19_assert($now[$i][$key]===$value,'Deferred migration changed original field');fwp19_assert($now[0]['folder_added_at']===null,'Deferred migration invented date');
}
WP_CLI::success('V19 pending-upgrade phase '.getenv('FILEWARDEN_PENDING_STAGE').' passed.');
