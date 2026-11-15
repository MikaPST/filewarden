<?php
/** Loopback-only acceptance setup; excluded from every runtime ZIP. */
if (!defined('ABSPATH') || '1'!==getenv('FILEWARDEN_TESTING'))return;
add_action('init',static function(){
    if ('POST'!==($_SERVER['REQUEST_METHOD']??'') || 'fw_v16_locale_fixture'!==($_POST['action']??''))return;
    if (!in_array($_SERVER['REMOTE_ADDR']??'',array('127.0.0.1','::1'),true) || !current_user_can('filewarden_manage_settings'))wp_send_json_error(null,403);
    check_admin_referer('filewarden_admin');
    $site=$_POST['site']??'en_US';$user=$_POST['user']??'en_US';$color=$_POST['color']??'fresh';
    if(!in_array($site,array('en_US','fr_FR','de_DE','fr_CA'),true)||!in_array($user,array('en_US','fr_FR'),true)||!in_array($color,array('fresh','sunrise','ectoplasm','light'),true))wp_send_json_error(null,400);
    update_option('WPLANG','en_US'===$site?'':$site);
    update_user_meta(get_current_user_id(),'locale',$user);
    update_user_meta(get_current_user_id(),'admin_color',$color);
    wp_send_json_success(array('site'=>get_option('WPLANG'),'user'=>get_user_locale(),'color'=>get_user_option('admin_color')));
});
