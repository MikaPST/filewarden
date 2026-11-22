<?php
/** Final UI structure and the native WordPress Shortcode block on a disposable site only. */
if(!defined('WP_CLI')||!WP_CLI||getenv('FILEWARDEN_TESTING')!=='1'||!in_array(wp_parse_url(home_url(),PHP_URL_HOST),array('127.0.0.1','localhost','::1'),true))exit(1);
$last_user=get_current_user_id();wp_set_current_user(1);
try{
 $last_fixture=json_decode(file_get_contents(getenv('FILEWARDEN_FIXTURES')),true);
 $last_block='<!-- wp:shortcode -->[filewarden folder="'.$last_fixture['public_folder_uuid'].'" view="grid" denied="editorial"]<!-- /wp:shortcode -->';
 $last_content=apply_filters('the_content',$last_block);
 if(!str_contains($last_content,'filewarden-publication-frame')||!str_contains($last_content,$last_fixture['public_folder_uuid'])||str_contains($last_content,'storage_key'))throw new RuntimeException('Native WordPress Shortcode block did not embed the protected publication.');
 $last_repo=new FileWarden\Repository();$last_service=new FileWarden\DocumentService($last_repo,new FileWarden\PermissionService(array($last_repo,'folder')),new FileWarden\StorageService(FILEWARDEN_STORAGE_DIR,array(ABSPATH),true),new FileWarden\AuditService());
 ob_start();FileWarden\Publisher::page($last_service);$last_html=ob_get_clean();
 if(!preg_match('/<nav class="fw-tabs fw-publication-tabs".*?<\/nav><section class="fw-content fw-publication-settings"/s',$last_html))throw new RuntimeException('Publication tabs remain inside the settings card.');
 if(!preg_match('/<div class="fw-publication-output"><section class="fw-content fw-shortcode-card".*?<\/section><section class="fw-content fw-preview-box"/s',$last_html))throw new RuntimeException('Separate shortcode output card is not above the preview.');
 if(1!==substr_count($last_html,'data-preview-config="authorized"')||2!==substr_count($last_html,'data-preview-config="denied"')||1!==substr_count($last_html,'id="fw-shortcode"'))throw new RuntimeException('Context controls or unique generated shortcode are missing.');
 WP_CLI::success('Final V17 integration: native WordPress shortcode block, external tabs, separate output card and preserved contextual form controls passed.');
}finally{wp_set_current_user($last_user);}
