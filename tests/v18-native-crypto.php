<?php
/** Run with an ini deliberately omitting Sodium, on a disposable local WordPress only. */
if (!defined('WP_CLI') || !WP_CLI || getenv('FILEWARDEN_TESTING')!=='1' || !str_contains(ABSPATH,'.tools') || !in_array(wp_parse_url(home_url(),PHP_URL_HOST),['127.0.0.1','localhost'],true))exit(1);
use FileWarden\{Repository,PermissionService,StorageService,DocumentService,AuditService,Diagnostics,CryptoSupport,BackupService,BackupErrors};
if(extension_loaded('sodium'))throw new RuntimeException('This test needs the no-Sodium ini');
wp_set_current_user(1);$repo=new Repository();$service=new DocumentService($repo,new PermissionService([$repo,'folder']),new StorageService(FILEWARDEN_STORAGE_DIR,[ABSPATH],true),new AuditService());
// WordPress supplies compatibility functions; their existence must never imply a native KDF/stream implementation.
if(!function_exists('sodium_crypto_secretstream_xchacha20poly1305_init_push'))throw new RuntimeException('WordPress Sodium polyfill not loaded');
$before=glob(FILEWARDEN_STORAGE_DIR.'/diagnostic-*');$report=Diagnostics::run($service);$rows=array_column($report['checks'],null,'id');
foreach(['stream','argon']as$id){if($rows[$id]['reference']!=='FWB_SODIUM_MISSING'||$rows[$id]['state']!=='blocked')throw new RuntimeException('Native-module absence misdiagnosed');}
if($report['environment']['native_sodium']||$before!==glob(FILEWARDEN_STORAGE_DIR.'/diagnostic-*'))throw new RuntimeException('Wrong native state or leaked witness');
try{BackupService::start($service,'backup','a valid disposable long phrase');throw new LogicException('Backup bypassed native requirement');}catch(RuntimeException $e){if(BackupErrors::code($e)!=='FWB_SODIUM_MISSING')throw $e;}
WP_CLI::success('Native crypto: real WordPress polyfill present, native Sodium absent, actionable diagnosis and blocked backup verified.');
