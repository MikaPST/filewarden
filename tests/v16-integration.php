<?php
/** Opt-in, safe attachment fallback and plugin-only language selection on disposable WordPress. */
if (!defined('WP_CLI') || !WP_CLI || '1' !== getenv('FILEWARDEN_TESTING')) { exit(1); }
global $wpdb;
wp_set_current_user(1);
$v16_audit = new FileWarden\AuditService();
$v16_before_language = get_option('filewarden_language', 'auto');
fw_assert(!FileWarden\ImportPolicy::unrestricted(), 'All-format imports must be off by default.');

foreach (array(
    array('allow_all_types'=>'1'),
    array('allow_all_types'=>'1','all_types_confirmed'=>array('1')),
    array('allow_all_types'=>'yes','all_types_confirmed'=>'1'),
    array('allow_all_types'=>array('1'),'all_types_confirmed'=>'1'),
    array('language'=>'../../outside','notification_seconds'=>'30'),
    array('language'=>array('fr_FR')),
) as $v16_bad) {
    $v16_old_duration=FileWarden\Notice::duration();
    try { FileWarden\SettingsConsole::save($v16_bad,$v16_audit); fw_assert(false,'Invalid or unconfirmed settings were accepted.'); }
    catch (InvalidArgumentException $expected) {}
    fw_assert(!FileWarden\ImportPolicy::unrestricted() && FileWarden\Notice::duration()===$v16_old_duration,'Rejected settings modified state.');
}
wp_set_current_user($users['subscriber']);
try { FileWarden\SettingsConsole::save(array('allow_all_types'=>'1','all_types_confirmed'=>'1'),$v16_audit); fw_assert(false,'A subscriber enabled unrestricted imports.'); }
catch (DomainException $expected) {}
wp_set_current_user(1);

// Create attachment records locally; do not change WordPress's own allowed MIME types.
function fw_v16_media($extension,$body,$declared='image/png') {
    $upload=wp_upload_dir(); wp_mkdir_p($upload['path']);
    $path=$upload['path'].'/fw-v16-'.wp_generate_uuid4().'.'.$extension;
    file_put_contents($path,$body);
    $id=wp_insert_attachment(array('post_title'=>'Inert FileWarden test fixture','post_mime_type'=>$declared,'post_status'=>'inherit'),$path,0,true);
    fw_assert(!is_wp_error($id) && $id>0,'Local media fixture failed.');
    return $id;
}
$v16_destination=$service->create_folder('V16 attachment fixtures',0,array('type'=>'authenticated'));
$v16_sources=array();
$v16_bodies=array(
    'html'=>'<html><body>Inert HTML fixture</body></html>',
    'svg'=>'<svg xmlns="http://www.w3.org/2000/svg"><text>Inert fixture</text></svg>',
    'php'=>'<?php /* Inert fixture: never execute this file. */ ?>',
    'txt'=>'<script>/* inert text fixture */</script>',
    'docm'=>"PK\x03\x04inert macro-format attachment fixture",
    'bin'=>"\x00\x01\x02opaque bytes",
);
foreach ($v16_bodies as $v16_extension=>$v16_body) {
    $v16_sources[$v16_extension]=fw_v16_media($v16_extension,$v16_body);
    try { $service->import_media($v16_sources[$v16_extension],$v16_destination,array('type'=>'inherit')); fw_assert(false,'Strict mode accepted '.$v16_extension); }
    catch (InvalidArgumentException $expected) {}
}
FileWarden\SettingsConsole::save(array('allow_all_types'=>'1','all_types_confirmed'=>'1'),$v16_audit);
fw_assert(FileWarden\ImportPolicy::unrestricted(),'Confirmed opt-in was not saved.');
$ids['v16_opaque']=array();
foreach ($v16_bodies as $v16_extension=>$v16_body) {
    $document=$service->import_media($v16_sources[$v16_extension],$v16_destination,array('type'=>'inherit'));
    $row=$repository->document($document['id']);
    fw_assert('application/octet-stream'===$row['mime'],'Unrestricted content gained an executable or preview MIME.');
    $handle=$storage->open($row['storage_key'],(int)$row['size']);
    fw_assert(stream_get_contents($handle)===$v16_body,'Opaque import changed file bytes.'); fclose($handle);
    $ids['v16_opaque'][$v16_extension]=array('uuid'=>$row['uuid'],'sha256'=>hash('sha256',$v16_body));
}
$v16_png=$service->import_media($ids['media_id'],$v16_destination,array('type'=>'inherit'));
fw_assert('image/png'===$repository->document($v16_png['id'])['mime'],'Validated image preview regressed in all-format mode.');
$v16_empty=fw_v16_media('bin','');
try { $service->import_media($v16_empty,$v16_destination,array('type'=>'inherit')); fw_assert(false,'Empty file accepted in permissive mode.'); }
catch (InvalidArgumentException $expected) {}
wp_delete_attachment($v16_empty,true);

$v16_limit=get_option('filewarden_upload_mb',50);
FileWarden\SettingsConsole::save(array('upload_mb'=>'1'),$v16_audit);
fw_assert(FileWarden\ImportPolicy::unrestricted(),'Saving another category reset the opt-in.');
$v16_large=fw_v16_media('bin',str_repeat('x',MB_IN_BYTES+1));
try { $service->import_media($v16_large,$v16_destination,array('type'=>'inherit')); fw_assert(false,'Size limit bypassed in permissive mode.'); }
catch (InvalidArgumentException $expected) {}
wp_delete_attachment($v16_large,true);
FileWarden\SettingsConsole::save(array('upload_mb'=>(string)$v16_limit,'allow_all_types'=>'0'),$v16_audit);
fw_assert(!FileWarden\ImportPolicy::unrestricted(),'Disabling did not restore strict validation.');
foreach ($v16_bodies as $v16_extension=>$v16_body) {
    try { $service->import_media($v16_sources[$v16_extension],$v16_destination,array('type'=>'inherit')); fw_assert(false,'Strict validation did not return.'); }
    catch (InvalidArgumentException $expected) {}
    fw_assert('application/octet-stream'===$repository->document($ids['v16_opaque'][$v16_extension]['uuid'])['mime'],'Disabling rewrote an existing attachment.');
    wp_delete_attachment($v16_sources[$v16_extension],true);
}
foreach (array('all_file_types_enabled','all_file_types_disabled') as $v16_event) {
    $v16_log=$wpdb->get_row($wpdb->prepare("SELECT actor_id,actor_login FROM {$wpdb->prefix}filewarden_audit_logs WHERE event=%s ORDER BY id DESC LIMIT 1",$v16_event),ARRAY_A);
    fw_assert($v16_log && (int)$v16_log['actor_id']===1 && $v16_log['actor_login']==='fw_admin','Import policy change lacks its WordPress actor.');
}

foreach (array('fr_FR','en_US','auto') as $v16_choice) {
    FileWarden\SettingsConsole::save(array('language'=>$v16_choice),$v16_audit);
    fw_assert(FileWarden\Language::choice()===$v16_choice,'Language preference was not persisted.');
}
fw_assert(FileWarden\Language::catalog('/other/plugin.mo','another-plugin')==='/other/plugin.mo','Plugin language changed another domain.');
update_option('filewarden_allow_all_types',array('1'));
fw_assert(!FileWarden\ImportPolicy::unrestricted(),'Malformed persisted opt-in was accepted.');
update_option('filewarden_allow_all_types','0');
update_option('filewarden_language',$v16_before_language);
file_put_contents(getenv('FILEWARDEN_FIXTURES'),wp_json_encode($ids));
WP_CLI::success('V16 opt-in confirmation/capabilities, opaque attachment bytes, image preview, size limits, strict restoration, actors and persistent language checks passed.');
