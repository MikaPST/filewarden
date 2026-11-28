<?php
/** Only explicitly disposable loopback installations may create acceptance data. */
if(!defined('WP_CLI')||!WP_CLI||getenv('FILEWARDEN_TESTING')!=='1'||wp_parse_url(home_url(),PHP_URL_HOST)!=='127.0.0.1')exit(1);
$fwAcceptanceRoot=wp_normalize_path(ABSPATH);
if(!str_contains($fwAcceptanceRoot,'/.tools/v19-dev/')&&!in_array(rtrim($fwAcceptanceRoot,'/'),['/tmp/fw-wordpress','/tmp/fw-restore'],true))exit(1);
function fw_acceptance_path($name){
 if(!preg_match('/^[a-z0-9-]+\.(json|fwbackup)$/D',$name))throw new RuntimeException('Invalid acceptance filename');
 $directory=getenv('FILEWARDEN_ACCEPTANCE_DIR')?:dirname(__DIR__).'/.tools/v19-dev';$actual=realpath($directory);
 $allowed=[wp_normalize_path(dirname(__DIR__).'/.tools/v19-dev'),wp_normalize_path(dirname(__DIR__).'/.tools/alpha-publish'),'/tmp/.tools/filewarden-alpha'];
 if(!$actual||!in_array(wp_normalize_path($actual),$allowed,true))throw new RuntimeException('Explicit private acceptance directory required');
 return $actual.'/'.$name;
}
