<?php
/** Corrective batch checks on disposable WordPress; integration.php provides guarded fixtures. */
wp_set_current_user(1);
$blocked_metadata=static function($query){
    return str_contains($query,'SELECT ENGINE FROM information_schema.TABLES')?'FILEWARDEN_SIMULATED_METADATA_DENIAL':$query;
};
add_filter('query',$blocked_metadata);
try {
    FileWarden\Repository::require_transactions();
    $leaf=$service->create_folder('Restricted metadata empty leaf',0,array('type'=>'authenticated'));
    fw_assert(0===FileWarden\FolderLifecycle::remove($service,$leaf),'Table-level metadata fallback did not permit a verified transactional deletion.');
    fw_assert(null===$repository->folder($leaf),'Empty leaf remained after verified deletion.');
} finally { remove_filter('query',$blocked_metadata); }
$blocked_all=static function($query){
    return str_contains($query,'SELECT ENGINE FROM information_schema.TABLES')||str_starts_with($query,'SHOW TABLE STATUS WHERE Name IN')?'FILEWARDEN_SIMULATED_METADATA_DENIAL':$query;
};
$protected_leaf=$service->create_folder('Unverifiable metadata leaf',0,array('type'=>'authenticated'));
add_filter('query',$blocked_all);
try { fw_reject(fn()=>FileWarden\FolderLifecycle::remove($service,$protected_leaf),RuntimeException::class,'Deletion accepted unverifiable transaction support.'); }
finally { remove_filter('query',$blocked_all); }
fw_assert(null!==$repository->folder($protected_leaf),'Unverifiable metadata removed a folder.');
$nontransactional=static function($query){
    return str_contains($query,'SELECT ENGINE FROM information_schema.TABLES')?"SELECT 'MyISAM' AS ENGINE UNION ALL SELECT 'InnoDB' UNION ALL SELECT 'InnoDB'":$query;
};
add_filter('query',$nontransactional);
try { fw_reject(fn()=>FileWarden\FolderLifecycle::remove($service,$protected_leaf),RuntimeException::class,'Deletion accepted a nontransactional table.'); }
finally { remove_filter('query',$nontransactional); }
fw_assert(null!==$repository->folder($protected_leaf),'Nontransactional metadata removed a folder.');
FileWarden\FolderLifecycle::remove($service,$protected_leaf);

// A new category extends both navigation and panel definitions without modifying JavaScript.
$extra=static function($sections){$sections['acceptance-extra']=array('label'=>'Extra fixture category','description'=>'Registry acceptance description','icon'=>'files');$sections['../bad']=array('label'=>'bad','description'=>'bad','icon'=>'bad');return $sections;};
add_filter('filewarden_settings_sections',$extra);
try {
    $sections=FileWarden\SettingsConsole::sections();
    fw_assert(6===count($sections)&&!isset($sections['../bad']),'Category registry lost defaults or accepted an invalid ID.');
    ob_start();FileWarden\SettingsConsole::page($service,new FileWarden\AdminController($service));$settings_markup=ob_get_clean();
    fw_assert(str_contains($settings_markup,'data-settings-section="acceptance-extra"')&&str_contains($settings_markup,'id="fw-settings-acceptance-extra"'),'Extra category did not produce both a link and panel.');
    fw_assert(str_contains($settings_markup,'Registry acceptance description'),'Extra panel lost its description.');
} finally {remove_filter('filewarden_settings_sections',$extra);}
fw_assert(5===count(FileWarden\SettingsConsole::sections()),'Fixture category leaked into ordinary settings.');
$version=new ReflectionMethod(FileWarden\AdminController::class,'asset_version');
foreach(array('assets/admin.css','assets/notices.js','assets/settings.js')as$asset){
    fw_assert(FILEWARDEN_VERSION.'-'.substr(hash_file('sha256',FILEWARDEN_PATH.$asset),0,12)===$version->invoke(null,$asset),'Asset URL does not identify its actual contents.');
}
ob_start();FileWarden\Notice::show('Owned fallback','success');$fallback=ob_get_clean();
fw_assert(str_contains($fallback,'class="fw-notice fw-notice-success"')&&!str_contains($fallback,'class="notice '),'FileWarden feedback is still an ordinary WordPress notice.');
WP_CLI::success('Corrective acceptance: verified table metadata fallback, fail-closed engine checks, real leaf deletion, extensible settings and content-addressed assets passed.');
