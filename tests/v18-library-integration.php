<?php
/** Run only against disposable loopback WordPress installations. */
if (!defined('WP_CLI') || !WP_CLI || getenv('FILEWARDEN_TESTING')!=='1' || !in_array(wp_parse_url(home_url(),PHP_URL_HOST),['127.0.0.1','localhost'],true)) exit(1);
use FileWarden\{Repository,PermissionService,StorageService,DocumentService,AuditService,LibraryActions,LibraryQuery,Lifecycle,BackupService,BackupJobs,BackupRestore,BackupManifest};
function fw18_assert($ok,$message){if(!$ok)throw new RuntimeException($message);}
function fw18_reject($callback,$message){try{$callback();}catch(Throwable $error){return $error->getMessage();}throw new RuntimeException($message);}
wp_set_current_user(1);FileWarden\Installer::backup_capabilities();
fw18_assert(LibraryQuery::filters(['type'=>'PDF'])['type']==='pdf','Uppercase file-extension filter ignored');
$repo=new Repository();$permissions=new PermissionService([$repo,'folder']);$storage=new StorageService(FILEWARDEN_STORAGE_DIR,[ABSPATH],true);$service=new DocumentService($repo,$permissions,$storage,new AuditService());
$suffix=bin2hex(random_bytes(4));
$a=$service->create_folder('Source '.$suffix,0,['type'=>'public']);$b=$service->create_folder('Destination '.$suffix,0,['type'=>'roles','roles'=>['editor']]);
$private=$service->create_folder('Hidden '.$suffix,0,['type'=>'roles','roles'=>['subscriber']]);
$add=function($name,$folder,$bytes)use($service){$upload=wp_upload_bits($name,null,$bytes);fw18_assert(!$upload['error'],'fixture upload');$media=wp_insert_attachment(['post_title'=>$name,'post_mime_type'=>'text/plain','post_status'=>'inherit'],$upload['file']);return $service->import_media($media,$folder,['type'=>'inherit']);};
$alpha=$add('Alpha-'.$suffix.'.txt',$a,str_repeat('safe fixture text ',10000));$beta=$add('Beta-'.$suffix.'.txt',$a,'small beta fixture');$hidden=$add('Hidden-'.$suffix.'.txt',$private,'must not leak');
$before=$repo->document($alpha['id']);LibraryActions::rename($service,'document',$alpha['id'],'Renamed-'.$suffix.'.TXT');$after=$repo->document($alpha['id']);
foreach(['uuid','storage_key','sha256','policy','folder_id']as$key)fw18_assert($before[$key]===$after[$key],'rename altered '.$key);
fw18_reject(fn()=>LibraryActions::rename($service,'document',$alpha['id'],'bad.php'),'extension change accepted');
LibraryActions::rename($service,'folder',$a,'Renamed folder '.$suffix);fw18_assert($repo->folder($a)['uuid']!==null,'folder UUID lost');
fw18_reject(fn()=>LibraryActions::rename($service,'document',$beta['id'],$after['name']),'duplicate rename accepted');
$ids=[$alpha['id'],$beta['id']];$review=LibraryActions::review($service,$ids,'move',$b);
LibraryActions::bulk($service,$ids,'move',$b,$review['proof']);foreach($ids as$id)fw18_assert((int)$repo->document($id)['folder_id']===$b,'bulk move incomplete');
$rollbackReview=LibraryActions::review($service,$ids,'move',$a);$collision=$add('collision-'.$suffix.'.txt',$a,'collision fixture');LibraryActions::rename($service,'document',$collision['id'],$repo->document($beta['id'])['name']);
fw18_reject(fn()=>LibraryActions::bulk($service,$ids,'move',$a,$rollbackReview['proof']),'partial move accepted');
foreach($ids as$id)fw18_assert((int)$repo->document($id)['folder_id']===$b,'failed selection left partial changes');
$stale=LibraryActions::review($service,$ids,'move',$a);$service->change_policy('folder',$a,['type'=>'authenticated']);
fw18_reject(fn()=>LibraryActions::bulk($service,$ids,'move',$a,$stale['proof']),'stale confirmation accepted');
$editor=wp_insert_user(['user_login'=>'fw18_editor_'.$suffix,'user_pass'=>'disposable-test-password','role'=>'editor']);$user=get_userdata($editor);foreach(['filewarden_manage_settings','filewarden_manage_permissions','filewarden_edit_files']as$cap)$user->add_cap($cap);
wp_set_current_user($editor);$filters=LibraryQuery::filters(['scope'=>'all','search'=>$suffix,'sort'=>'size_desc']);$rows=LibraryQuery::rows($service,null,$filters);
fw18_assert(!in_array($hidden['id'],array_column($rows,'uuid'),true),'global search leaked forbidden document');fw18_assert(count($rows)>=3,'global search missed authorized files');
foreach($rows as$row)fw18_assert(!str_contains($row['location'],'Hidden'),'global search leaked parent path');
fw18_reject(fn()=>LibraryActions::rename($service,'document',$hidden['id'],'leak.txt'),'rename bypassed ACL');
fw18_reject(fn()=>BackupService::start($service,'backup','disposable backup phrase'),'settings or edit granted full backup');
wp_set_current_user(1);$life=new Lifecycle($service);foreach($ids as$id)$life->trash($id);
$restore=LibraryActions::review($service,$ids,'restore',$a);LibraryActions::bulk($service,$ids,'restore',$a,$restore['proof']);foreach($ids as$id)fw18_assert(empty($repo->document($id,true)['trashed_at']),'bulk restoration incomplete');
$filters=LibraryQuery::filters(['scope'=>'all','search'=>$suffix,'type'=>'txt','sort'=>'size_asc']);$rows=LibraryQuery::rows($service,null,$filters);fw18_assert(count($rows)>=4,'combined filters lost files');for($i=1;$i<count($rows);$i++)fw18_assert((int)$rows[$i-1]['size']<=(int)$rows[$i]['size'],'sort order incorrect');
$capUser=wp_insert_user(['user_login'=>'fw18_readonly_'.$suffix,'user_pass'=>'disposable-test-password','role'=>'subscriber']);$reader=get_userdata($capUser);$reader->add_cap('filewarden_read_all_files');$reader->add_cap('filewarden_manage_settings');wp_set_current_user($capUser);
fw18_reject(fn()=>LibraryActions::rename($service,'folder',$a,'forbidden'),'global read granted edits');wp_set_current_user(1);
global $wpdb;
$oldTimezone=get_option('timezone_string');$originalDate=$repo->document($alpha['id'])['folder_added_at'];
try{update_option('timezone_string','Europe/Paris');$wpdb->update($wpdb->prefix.'filewarden_documents',['folder_added_at'=>'2026-11-22 23:30:00'],['uuid'=>$alpha['id']]);
 $dated=LibraryQuery::filters(['scope'=>'all','search'=>$repo->document($alpha['id'])['name'],'after'=>'2026-11-23','before'=>'2026-11-23','min_size'=>'1','max_size'=>'1000']);
 fw18_assert(count(LibraryQuery::rows($service,null,$dated))===1,'local date boundary or size filter differs from WordPress');
 $dated['before']='2026-11-22';fw18_assert(count(LibraryQuery::rows($service,null,$dated))===0,'inconsistent date interval returned files');
}finally{update_option('timezone_string',$oldTimezone);$wpdb->update($wpdb->prefix.'filewarden_documents',['folder_added_at'=>$originalDate],['uuid'=>$alpha['id']]);}
WP_CLI::success('v18: rename identity, extension/duplicates, authorized global search, combined sorting/filters, atomic selection rollback, stale reviews and distinct capabilities passed.');
