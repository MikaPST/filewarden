<?php
if (!defined('WP_CLI') || !WP_CLI || getenv('FILEWARDEN_TESTING')!=='1' || !str_contains(ABSPATH,'.tools') || !in_array(wp_parse_url(home_url(),PHP_URL_HOST),['127.0.0.1','localhost'],true))exit(1);
use FileWarden\{Repository,PermissionService,StorageService,DocumentService,AuditService};
wp_set_current_user(1);$repo=new Repository();$service=new DocumentService($repo,new PermissionService([$repo,'folder']),new StorageService(FILEWARDEN_STORAGE_DIR,[ABSPATH],true),new AuditService());
$suffix=bin2hex(random_bytes(3));$folder=$service->create_folder('UI browser '.$suffix,0,['type'=>'public']);$target=$service->create_folder('Nested destination',$folder,['type'=>'public']);$empty=$service->create_folder('Empty folder '.$suffix,0,['type'=>'public']);
$add=function($name,$where)use($service){$u=wp_upload_bits($name,null,'isolated browser fixture');$id=wp_insert_attachment(['post_title'=>$name,'post_mime_type'=>'text/plain','post_status'=>'inherit'],$u['file']);return $service->import_media($id,$where,['type'=>'inherit']);};
$doc=$add('UI-browser-'.$suffix.'.txt',$folder);$unfiled=$add('UI-unfiled-'.$suffix.'.txt',0);
$out=getenv('FILEWARDEN_UI_FIXTURES');if(!$out||!str_contains($out,'.tools'))throw new RuntimeException('Isolated fixture metadata required');
file_put_contents($out,wp_json_encode(['folder'=>$folder,'target'=>$target,'document'=>$doc['id'],'unfiled'=>$unfiled['id'],'empty'=>$repo->folder($empty)['uuid'],'name'=>$doc['name']]));WP_CLI::success('Disposable browser fixtures prepared.');
