<?php
/** Test-only MU fixture: copy explicitly into disposable WordPress, never ship in the plugin ZIP. */
if ( ! defined('ABSPATH') || '1' !== getenv('FILEWARDEN_TESTING') ) { return; }
add_action('init',static function(){
    if(!in_array($_SERVER['REMOTE_ADDR']??'',array('127.0.0.1','::1'),true)||!current_user_can('filewarden_manage_settings')){return;}
    if('1'===($_GET['fw_settings_fixture']??'')){
        add_filter('filewarden_settings_sections',static function($sections){$sections['acceptance-extra']=array('label'=>'Extra fixture category','description'=>'Registry acceptance description','icon'=>'files');return $sections;});
    }
    if('POST'!==($_SERVER['REQUEST_METHOD']??'')||'delete_folder'!==($_POST['operation']??'')){return;}
    $mode=$_POST['fw_engine_fixture']??'';
    if(!is_string($mode)||!in_array($mode,array('restricted-metadata','unverified-engines','transaction-failure','recovery-failure'),true)){return;}
    global $wpdb;$wpdb->suppress_errors(true);
    add_filter('query',static function($query)use($mode,$wpdb){
        $metadata=str_contains($query,'SELECT ENGINE FROM information_schema.TABLES');
        $status=str_starts_with($query,'SHOW TABLE STATUS WHERE Name IN');
        $fail='restricted-metadata'===$mode&&$metadata;
        $fail=$fail||('unverified-engines'===$mode&&($metadata||$status));
        $fail=$fail||('transaction-failure'===$mode&&'START TRANSACTION'===$query);
        $fail=$fail||('recovery-failure'===$mode&&str_starts_with($query,'UPDATE `'.$wpdb->prefix.'filewarden_documents`')&&str_contains($query,'retained_rules'));
        return $fail?'FILEWARDEN_CORRECTION_INTENTIONAL_FAILURE':$query;
    });
});
