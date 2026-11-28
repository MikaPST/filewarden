<?php
/** Actual selective encrypted migrations across isolated databases; never run on a real site. */
if (!defined('WP_CLI') || !WP_CLI || getenv('FILEWARDEN_TESTING')!=='1' || !str_contains(ABSPATH,'.tools') || !in_array(wp_parse_url(home_url(),PHP_URL_HOST),['127.0.0.1','localhost'],true))exit(1);
use FileWarden\{Repository,PermissionService,StorageService,ManagedStorage,DocumentService,AuditService,BackupService,BackupJobs,BackupRestore,BackupManifest,BackupComponents,FrontendAppearance,PublicationLogin};
function fws_assert($ok,$why){if(!$ok)throw new RuntimeException($why);}
function fws_reject($fn,$why){try{$fn();}catch(Throwable $e){return;}throw new RuntimeException($why);}
global $wpdb;wp_set_current_user(1);
$proof=getenv('FILEWARDEN_SELECTIVE_RESULT');fws_assert($proof&&str_contains($proof,'.tools')&&str_contains($proof,'ui-selective'),'Isolated output required');
$repo=new Repository();$permissions=new PermissionService([$repo,'folder']);$managed=new ManagedStorage();(new ReflectionProperty($managed,'verified'))->setValue($managed,true);
$storage=new StorageService('',[ABSPATH],false,$managed);$service=new DocumentService($repo,$permissions,$storage,new AuditService());
$phase=getenv('FILEWARDEN_SELECTIVE_STAGE');$phrase='disposable selective backup phrase 2026';
if($phase==='source'){
    fws_assert(str_contains(ABSPATH,'v18backup715'),'Wrong source fixture');
    // The pre-existing source contains actual encrypted active/trash documents, a logo and a custom role.
    [$base]=$capture=BackupManifest::capture();fws_assert(count($base['documents'])>1&&$base['logo'],'Insufficient source fixture');
    for($mask=0;$mask<64;$mask++){
        $flags=[];foreach(BackupComponents::KEYS as$i=>$key)$flags[$key]=(bool)($mask&(1<<$i));
        [$m,$keys]=BackupManifest::capture($flags);BackupManifest::validate($m);fws_assert($m['components']===$flags,'Flags lost');
        fws_assert($flags['audit']||!$m['audit_logs'],'Audit data not omitted');fws_assert($flags['appearance']||null===$m['logo'],'Logo not omitted');
        fws_assert(count($keys)===count($m['documents'])+($m['logo']?1:0),'Excluded file remained in stream source list');
        if(!$flags['permissions'])foreach(array_merge($m['folders'],$m['documents'])as$row)fws_assert(json_decode($row['policy'],true)===BackupComponents::private_policy(),'Omitted rights became public');
        if(!$flags['creators'])fws_assert(!str_contains(wp_json_encode($m['users']),'fw_source_creator'),'Creator identity leaked');
        if(!$flags['settings'])fws_assert(!array_diff(array_keys($m['settings']),$flags['appearance']?['appearance']:[]),'Settings not physically omitted');
        if(!$flags['trash'])fws_assert(!array_filter($m['documents'],fn($r)=>!empty($r['trashed_at'])),'Trash not omitted');
    }
    $scenarios=['simplified'=>BackupComponents::defaults(true),'complete'=>BackupComponents::defaults(),'history_private'=>array_replace(BackupComponents::defaults(true),['trash'=>true,'audit'=>true])];$out=['cases'=>[]];
    foreach($scenarios as$name=>$flags){
        $job=BackupService::start($service,'backup',$phrase,'',$flags);do{$job=BackupService::tick($service,$job['id']);}while($job['status']==='working');
        fws_assert($job['status']==='ready','Export did not complete');$state=BackupJobs::load($service,$job['id']);$archive=dirname($proof).'/'.$name.'.fwbackup';copy(BackupJobs::path($service,$job['id'],'fwbackup'),$archive);
        fws_assert(substr(file_get_contents($archive),0,8)==='FWBACK01','Encryption format missing');
        $out['cases'][$name]=['archive'=>$archive,'manifest'=>$state['manifest'],'sha256'=>hash_file('sha256',$archive)];BackupJobs::exclusive(fn()=>BackupJobs::cleanup($service,$state));
    }
    $out['source_key']=get_option(ManagedStorage::OPTION);file_put_contents($proof,wp_json_encode($out));
    WP_CLI::success('Selective source: 64 component combinations validated; three actual encrypted packages exported without omitted data.');
}elseif($phase==='target'){
    fws_assert(str_contains(ABSPATH,'v18uiselect715'),'Wrong selective target fixture');
    $expected=json_decode(file_get_contents($proof),true);$fresh=$repo->documents(0);
    fws_assert(count($fresh)===1&&$fresh[0]['name']==='fresh.pdf','Fresh disposable destination required');
    (new StorageService(FILEWARDEN_STORAGE_DIR,[ABSPATH],true))->remove($fresh[0]['storage_key']);$wpdb->delete($wpdb->prefix.'filewarden_documents',['id'=>$fresh[0]['id']]);
    $ordinary=wp_insert_user(['user_login'=>'selective-reader','user_pass'=>'disposable-test-password','role'=>'subscriber']);
    fws_assert(!get_role('fw_source_reader'),'Source-only role unexpectedly exists in destination');
    $defaults=['filewarden_upload_mb'=>12,'filewarden_trash_days'=>11,'filewarden_audit_days'=>45,'filewarden_language'=>'en_US','filewarden_notification_seconds'=>9,'filewarden_allow_all_types'=>'1',PublicationLogin::OPTION=>['mode'=>'wordpress']];
    $appearance=FrontendAppearance::defaults();$appearance['name']='Destination identity';
    $cases=[['simplified',false],['complete',false],['history_private',false],['complete',true]];$results=[];$previous=[];
    foreach($cases as[$name,$keep]){
        // Only the UUIDs restored by the preceding case can be removed from this disposable target.
        $live=$wpdb->get_results("SELECT uuid,storage_key FROM {$wpdb->prefix}filewarden_documents",ARRAY_A);
        fws_assert(!array_diff(array_column($live,'uuid'),$previous),'Unexpected destination content');
        foreach($live as$row)$storage->remove($row['storage_key']);
        foreach(['documents','folders','audit_logs']as$kind)$wpdb->query($wpdb->prepare('DELETE FROM %i',$wpdb->prefix.'filewarden_'.$kind));
        foreach($defaults as$key=>$value)update_option($key,$value,false);update_option(FrontendAppearance::OPTION,$appearance,false);
        $case=$expected['cases'][$name];$manifest=$case['manifest'];$job=BackupService::start($service,'restore',$phrase,$case['archive']);
        do{$job=BackupService::tick($service,$job['id']);}while($job['status']==='working');fws_assert($job['status']==='review','Package not fully authenticated');
        fws_assert($job['components']===$manifest['components'],'Conditional restore options differ from authenticated manifest');
        $choices=['permissions'=>$keep,'settings'=>$keep,'appearance'=>$keep,'audit'=>$keep||$name==='history_private','creators'=>'current'];
        $roles=[];if($keep)foreach($job['roles']as$role)$roles[$role]='editor';
        if($name==='simplified'){$forged=$choices;$forged['permissions']=true;fws_reject(fn()=>BackupRestore::apply($service,$job['id'],[],[],$forged),'Omitted rights enabled');BackupService::empty_library();}
        $done=BackupRestore::apply($service,$job['id'],$roles,[],$choices);fws_assert($done['status']==='restored','Selective activation failed');
        fws_assert(get_option(ManagedStorage::OPTION)!==$expected['source_key'],'Source storage key transferred');
        $previous=array_column($manifest['documents'],'uuid');
        foreach($manifest['documents']as$source){
            $row=$repo->document($source['uuid'],true);fws_assert($row&&(int)$row['created_by']===1,'Current-account attribution missing');
            $hash=hash_init('sha256');$storage->visit_chunks($row['storage_key'],(int)$row['size'],fn($bytes)=>hash_update($hash,$bytes));fws_assert(hash_final($hash)===$source['sha256'],'Selective restored bytes differ');
            if(!$keep){fws_assert($row['policy']===BackupComponents::private_policy(),'Document not private');fws_assert(empty($row['retained_rules']),'Source retained ACL imported despite omission');$row['trashed_at']=null;fws_assert(!$permissions->can_read($row,new WP_User(0))&&!$permissions->can_read($row,get_userdata($ordinary)),'Private restore exposed content');}
        }
        foreach($manifest['folders']as$source){$row=$repo->folder((int)$source['id']);fws_assert((int)$row['created_by']===1,'Folder creator not attributed');if(!$keep)fws_assert($row['policy']===BackupComponents::private_policy(),'Folder not private');}
        if(!$keep){foreach($defaults as$key=>$value)fws_assert(get_option($key)===$value,'Omitted destination setting overwritten '.$key);fws_assert(FrontendAppearance::current()['name']==='Destination identity','Omitted appearance overwritten');}
        else{fws_assert(FrontendAppearance::current()['name']===$manifest['settings']['appearance']['name'],'Included appearance lost');fws_assert(!FileWarden\ImportPolicy::unrestricted(),'Sensitive settings not reset');}
        $imported=(int)$wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}filewarden_audit_logs WHERE actor_type='imported' OR actor_type LIKE 'source_%'");
        fws_assert($choices['audit']?($imported>0):($imported===0),'Audit inclusion ignored');
        if($choices['audit']){foreach($manifest['audit_logs']as$source){if(!$source['actor_id'])continue;$match=$wpdb->get_row($wpdb->prepare('SELECT actor_id,actor_login FROM %i WHERE event=%s AND created_at=%s AND actor_type=%s LIMIT 1',$wpdb->prefix.'filewarden_audit_logs',$source['event'],$source['created_at'],'imported'),ARRAY_A);fws_assert($match&&(string)$match['actor_id']===(string)$source['actor_id']&&$match['actor_login']===$source['actor_login'],'Historical source actor rewritten');break;}}
        fws_reject(fn()=>BackupService::start($service,'restore',$phrase,$case['archive']),'Nonempty selective destination accepted');
        $results[]=['case'=>$name,'keep_source'=>$keep,'documents'=>count($manifest['documents']),'private'=>!$keep,'passed'=>true];
    }
    file_put_contents(dirname($proof).'/result.json',wp_json_encode(['combinations'=>64,'real_restorations'=>$results,'passed'=>true]));
    WP_CLI::success('Selective target: four real restorations, omitted components, private ACLs, current attribution, destination settings, historical actors and distinct keys passed.');
}else{throw new RuntimeException('Unknown selective stage');}
