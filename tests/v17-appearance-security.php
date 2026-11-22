<?php
/** Reject forged public logos on an explicitly disposable loopback WordPress. */
if (!defined('WP_CLI') || !WP_CLI || '1' !== getenv('FILEWARDEN_TESTING') || !in_array(wp_parse_url(home_url(),PHP_URL_HOST),array('127.0.0.1','localhost','::1'),true)) {exit(1);}
$v17_guard_previous=get_current_user_id();wp_set_current_user(1);
$v17_guard_upload=wp_upload_dir();$v17_guard_ids=array();$v17_guard_paths=array();
$v17_guard_make=static function($suffix,$bytes,$mime)use($v17_guard_upload,&$v17_guard_ids,&$v17_guard_paths){
 $path=trailingslashit($v17_guard_upload['path']).wp_generate_uuid4().$suffix;
 file_put_contents($path,$bytes);$v17_guard_paths[]=$path;
 $id=wp_insert_attachment(array('post_mime_type'=>$mime,'post_title'=>'V17 disposable logo security','post_status'=>'inherit'),$path);
 if(is_wp_error($id)||!$id)throw new RuntimeException('Cannot create synthetic logo.');
 $v17_guard_ids[]=$id;return$id;
};
$v17_guard_reject=static function($callback,$label){
 try{$callback();}catch(InvalidArgumentException|DomainException $error){return;}
 throw new RuntimeException('Unsafe public logo accepted: '.$label);
};
try{
 $png=file_get_contents(FILEWARDEN_PATH.'assets/branding/filewarden-logo-512.png');
 $valid=$v17_guard_make('.png',$png,'image/png');
 $url=FileWarden\FrontendAppearance::logo_url($valid,true);
 if(!str_contains($url,'.png'))throw new RuntimeException('Valid PNG rejected.');
 $jpeg=base64_decode('/9j/4AAQSkZJRgABAQEAYABgAAD/2wBDAAMCAgMCAgMDAwMEAwMEBQgFBQQEBQoHBwYIDAoMDAsKCwsNDhIQDQ4RDgsLEBYQERMUFRUVDA8XGBYUGBIUFRT/2wBDAQMEBAUEBQkFBQkUDQsNFBQUFBQUFBQUFBQUFBQUFBQUFBQUFBQUFBQUFBQUFBQUFBQUFBQUFBQUFBQUFBQUFBT/wAARCAABAAEDASIAAhEBAxEB/8QAHwAAAQUBAQEBAQEAAAAAAAAAAAECAwQFBgcICQoL/8QAtRAAAgEDAwIEAwUFBAQAAAF9AQIDAAQRBRIhMUEGE1FhByJxFDKBkaEII0KxwRVS0fAkM2JyggkKFhcYGRolJicoKSo0NTY3ODk6Q0RFRkdISUpTVFVWV1hZWmNkZWZnaGlqc3R1dnd4eXqDhIWGh4iJipKTlJWWl5iZmqKjpKWmp6ipqrKztLW2t7i5usLDxMXGx8jJytLT1NXW19jZ2uHi4+Tl5ufo6erx8vP09fb3+Pn6/8QAHwEAAwEBAQEBAQEBAQAAAAAAAAECAwQFBgcICQoL/8QAtREAAgECBAQDBAcFBAQAAQJ3AAECAxEEBSExBhJBUQdhcRMiMoEIFEKRobHBCSMzUvAVYnLRChYkNOEl8RcYGRomJygpKjU2Nzg5OkNERUZHSElKU1RVVldYWVpjZGVmZ2hpanN0dXZ3eHl6goOEhYaHiImKkpOUlZaXmJmaoqOkpaanqKmqsrO0tba3uLm6wsPExcbHyMnK0tPU1dbX2Nna4uPk5ebn6Onq8vP09fb3+Pn6/9oADAMBAAIRAxEAPwD80qKKK/qs8Q//2Q==',true);
 $valid_jpeg=$v17_guard_make('.jpg',$jpeg,'image/jpeg');
 if(!str_contains(FileWarden\FrontendAppearance::logo_url($valid_jpeg,true),'.jpg'))throw new RuntimeException('Valid JPEG rejected.');
 foreach(array(
  array('.png','<?php echo "active"; ?>','image/png','renamed active content'),
  array('.png',$png,'image/jpeg','forged attachment MIME'),
  array('.jpg',$png,'image/png','wrong extension'),
  array('.png',$png.str_repeat('x',2*MB_IN_BYTES),'image/png','oversized image'),
  array('.png',substr_replace($png,pack('N',6001),16,4),'image/png','oversized dimensions'),
 )as$case){$id=$v17_guard_make($case[0],$case[1],$case[2]);$v17_guard_reject(fn()=>FileWarden\FrontendAppearance::logo_url($id,true),$case[3]);}
 $filter=static fn($value,$id)=>$id===$valid?'https://example.test/logo.png':$value;
 add_filter('wp_get_attachment_url',$filter,10,2);
 $v17_guard_reject(fn()=>FileWarden\FrontendAppearance::logo_url($valid,true),'remote image');
 remove_filter('wp_get_attachment_url',$filter,10);
 $filter=static fn($value,$id)=>$id===$valid?str_replace('http:','https:',$value):$value;
 add_filter('wp_get_attachment_url',$filter,10,2);
 $v17_guard_reject(fn()=>FileWarden\FrontendAppearance::logo_url($valid,true),'different origin scheme');
 remove_filter('wp_get_attachment_url',$filter,10);
 $outside=tempnam(sys_get_temp_dir(),'fw-logo-');file_put_contents($outside,$png);$v17_guard_paths[]=$outside;
 $filter=static fn($path,$id)=>$id===$valid?$outside:$path;
 add_filter('get_attached_file',$filter,10,2);
 $v17_guard_reject(fn()=>FileWarden\FrontendAppearance::logo_url($valid,true),'outside uploads');
 remove_filter('get_attached_file',$filter,10);
 $user=wp_create_user('fw_logo_guard_'.wp_rand(100000,999999),wp_generate_password());
 if(is_wp_error($user))throw new RuntimeException('Synthetic permission user failed.');
 try{
  $account=new WP_User($user);$account->set_role('subscriber');$account->add_cap('filewarden_manage_settings');wp_set_current_user($user);
  $input=FileWarden\FrontendAppearance::defaults();$input['logo_id']=$valid;
  $v17_guard_reject(fn()=>FileWarden\FrontendAppearance::save($input,new FileWarden\AuditService()),'settings capability without media rights');
 }finally{wp_set_current_user(1);require_once ABSPATH.'wp-admin/includes/user.php';wp_delete_user($user);}
 WP_CLI::success('V17 public logo: actual MIME/extension, active-content, size/dimension, origin/path and separate media permission refusals passed.');
}finally{
 wp_set_current_user(1);
 foreach($v17_guard_ids as$id)wp_delete_attachment($id,true);
 foreach($v17_guard_paths as$path){if(is_file($path))unlink($path);}
 wp_set_current_user($v17_guard_previous);
}
