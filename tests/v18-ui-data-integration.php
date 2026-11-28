<?php
/** Real transactional tests, exclusively against disposable loopback fixtures. */
if (!defined('WP_CLI') || !WP_CLI || getenv('FILEWARDEN_TESTING')!=='1' || !str_contains(ABSPATH,'.tools') || !str_starts_with(basename(dirname(ABSPATH)),'v18') || !in_array(wp_parse_url(home_url(),PHP_URL_HOST),['127.0.0.1','localhost'],true))exit(1);
use FileWarden\{Repository,PermissionService,StorageService,DocumentService,AuditService,LibraryActions,Lifecycle,WordPressTables,Diagnostics};
function fwu_assert($ok,$why){if(!$ok)throw new RuntimeException($why);}
function fwu_reject($fn,$why){try{$fn();}catch(Throwable $e){return $e->getMessage();}throw new RuntimeException($why);}
global $wpdb;wp_set_current_user(1);
$repo=new Repository();$service=new DocumentService($repo,new PermissionService([$repo,'folder']),new StorageService(FILEWARDEN_STORAGE_DIR,[ABSPATH],true),new AuditService());
$suffix=bin2hex(random_bytes(4));$folder=$service->create_folder('UI pass '.$suffix,0,['type'=>'public']);
$add=function($name,$target)use($service){$u=wp_upload_bits($name,null,'plain isolated fixture');$id=wp_insert_attachment(['post_title'=>$name,'post_mime_type'=>'text/plain','post_status'=>'inherit'],$u['file']);return $service->import_media($id,$target,['type'=>'inherit']);};
$a=$add('UI-a-'.$suffix.'.txt',$folder);$b=$add('UI-b-'.$suffix.'.txt',0);$ids=[$a['id'],$b['id']];$before=array_map(fn($id)=>$repo->document($id),$ids);
$review=LibraryActions::review($service,$ids,'trash',0);fwu_assert(count($review['rows'])===2,'Trash review incomplete');
// Inject a deterministic failure on the second record; the first trash and audit writes must roll back.
$writeCount=0;$fail=function($sql)use($wpdb,&$writeCount){if(str_starts_with($sql,'UPDATE `'.$wpdb->prefix.'filewarden_documents`')&&str_contains($sql,'trashed_at')){++$writeCount;if($writeCount===2)return 'INVALID SQL FOR ATOMIC TEST';}return $sql;};
$prev=$wpdb->suppress_errors(true);$auditBefore=(int)$wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}filewarden_audit_logs");add_filter('query',$fail);
try{fwu_reject(fn()=>LibraryActions::bulk($service,$ids,'trash',0,$review['proof']),'Partial trash accepted');}finally{remove_filter('query',$fail);$wpdb->suppress_errors($prev);}
fwu_assert($writeCount===2,'Failure injection did not reach the second update');
foreach($ids as$id)fwu_assert(empty($repo->document($id,true)['trashed_at']),'Atomic trash left a partial mutation');
fwu_assert($auditBefore===(int)$wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}filewarden_audit_logs"),'Atomic failure left audit success');
$service->change_policy('folder',$folder,['type'=>'authenticated']);fwu_reject(fn()=>LibraryActions::bulk($service,$ids,'trash',0,$review['proof']),'Stale trash proof accepted');
$deleter=wp_insert_user(['user_login'=>'ui-deleter-'.$suffix,'user_pass'=>'disposable-test-password','role'=>'subscriber']);foreach(['filewarden_delete_files','filewarden_manage_settings','filewarden_manage_permissions']as$cap)get_userdata($deleter)->add_cap($cap);
wp_set_current_user($deleter);$review=LibraryActions::review($service,$ids,'trash',0);LibraryActions::bulk($service,$ids,'trash',0,$review['proof']);
foreach($ids as$i=>$id){$row=$repo->document($id,true);fwu_assert(!empty($row['trashed_at']),'Trash needs unrelated edit capability');foreach(['uuid','storage_key','sha256','policy','retained_rules','folder_id']as$key)fwu_assert(($row[$key]??null)===($before[$i][$key]??null),'Trash changed protected field '.$key);}
get_userdata($deleter)->remove_cap('filewarden_delete_files');fwu_reject(fn()=>LibraryActions::review($service,$ids,'trash',0),'Missing delete capability accepted');
fwu_reject(fn()=>WordPressTables::preflight(),'FileWarden settings alone granted shared WordPress conversion');
wp_set_current_user(1);fwu_reject(fn()=>LibraryActions::review($service,array_fill(0,51,$a['id']),'trash',0),'Over-limit or duplicate trash accepted');
// Exercise real DDL failure, resumable checkpoints, fingerprint drift and SQL writer exclusion.
$foreign=wp_insert_user(['user_login'=>'ui-converter-'.$suffix,'user_pass'=>'disposable-test-password','role'=>'administrator']);
$conversionAuditBefore=(int)$wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}filewarden_audit_logs WHERE event='wordpress_tables_converted'");
foreach([$wpdb->options,$wpdb->posts,$wpdb->postmeta]as$table)fwu_assert(false!==$wpdb->query($wpdb->prepare('ALTER TABLE %i ENGINE=MyISAM',$table)),'Cannot set disposable MyISAM fixture');
$r=WordPressTables::preflight();fwu_assert(count(array_filter($r['engines'],fn($e)=>$e==='myisam'))===3,'Preflight changed an engine');
$remote=static fn($sql)=>'SELECT @@hostname'===$sql?"SELECT 'different-disposable-database-host'":$sql;add_filter('query',$remote);
try{fwu_assert(!WordPressTables::preflight()['disk_measured'],'PHP disk was mistaken for a remote SQL disk');fwu_assert('Core table disk unknown.'===fwu_reject(fn()=>WordPressTables::step($r['reviewed_at'],$r['proof'],true,false),'Unmeasured SQL disk skipped explicit host confirmation'),'Wrong remote-capacity refusal');}finally{remove_filter('query',$remote);}
fwu_reject(fn()=>WordPressTables::step($r['reviewed_at'],$r['proof'],false,true),'Missing maintenance/backup acknowledgment accepted');
fwu_reject(fn()=>WordPressTables::step($r['reviewed_at'],'forged',true,true),'Forged conversion review accepted');
$s=WordPressTables::step($r['reviewed_at'],$r['proof'],true,true);fwu_assert(!$s['done']&&$s['completed']===1,'Unbounded conversion step');
wp_set_current_user($foreign);fwu_reject(fn()=>WordPressTables::preflight(),'Another administrator took over a conversion');wp_set_current_user(1);
fwu_reject(fn()=>$service->create_folder('blocked',0,['type'=>'public']),'Pending conversion did not freeze FileWarden');
$ddlInjected=false;$failure=function($sql)use($wpdb,&$ddlInjected){if(str_starts_with($sql,'ALTER TABLE `'.$wpdb->posts.'`')){$ddlInjected=true;return 'INVALID SQL FOR DDL FAILURE';}return $sql;};add_filter('query',$failure);$prev=$wpdb->suppress_errors(true);
try{fwu_reject(fn()=>WordPressTables::step($r['reviewed_at'],$r['proof'],true,true),'Failed DDL reported success');}finally{remove_filter('query',$failure);$wpdb->suppress_errors($prev);}
fwu_assert($ddlInjected,'The intended ALTER failure was not reached');
fwu_assert((bool)get_option(WordPressTables::CHECKPOINT),'DDL failure lost recovery checkpoint');
$post=(int)$wpdb->get_var("SELECT MIN(ID) FROM {$wpdb->posts}");$original=$wpdb->get_var($wpdb->prepare('SELECT post_title FROM %i WHERE ID=%d',$wpdb->posts,$post));$wpdb->update($wpdb->posts,['post_title'=>'changed during maintenance test'],['ID'=>$post]);
fwu_reject(fn()=>WordPressTables::step($r['reviewed_at'],$r['proof'],true,true),'Changed shared data passed conversion fingerprint');$wpdb->update($wpdb->posts,['post_title'=>$original],['ID'=>$post]);
$lock=new ReflectionMethod(WordPressTables::class,'lock_tables');$lock->invoke(null);
$other=new mysqli('127.0.0.1','root','disposable-db-password',DB_NAME,33076);$other->query('SET SESSION lock_wait_timeout=1');
mysqli_report(MYSQLI_REPORT_OFF);$blocked=$other->query('UPDATE `'.$wpdb->posts.'` SET post_title=post_title WHERE ID='.$post);$other->close();$wpdb->query('UNLOCK TABLES');fwu_assert(false===$blocked,'SQL table lock did not exclude an external writer');
for($i=0;$i<3&&!$s['done'];$i++)$s=WordPressTables::step($r['reviewed_at'],$r['proof'],true,true);
fwu_assert($s['done']&&!get_option(WordPressTables::CHECKPOINT),'Conversion did not resume after verified unchanged data');
fwu_assert($conversionAuditBefore+1===(int)$wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}filewarden_audit_logs WHERE event='wordpress_tables_converted'"),'Conversion success not recorded exactly once');
$report=Diagnostics::run($service);$checks=array_column($report['checks'],null,'id');fwu_assert('good'===$checks['wp_tables']['state'],'Post-conversion diagnostic failed');
WP_CLI::success('UI pass data: atomic bulk trash, capabilities, stale proofs, real bounded DDL, failure/resume, data-drift refusal and SQL writer exclusion passed.');
