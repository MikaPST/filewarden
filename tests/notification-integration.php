<?php
/** Settings and feedback integrity on disposable WordPress only. Included by integration.php. */
wp_set_current_user( 1 );
$notice_option = 'filewarden_notification_seconds';
delete_option( $notice_option );
fw_assert( 10 === FileWarden\Notice::duration(), 'New installations must default to ten seconds.' );
$preserved = array();
foreach ( array( 'upload_mb', 'trash_days', 'audit_days' ) as $field ) {
    $preserved[$field] = get_option('filewarden_' . $field);
}
foreach ( array(5,10,15,20,30) as $seconds ) {
    FileWarden\SettingsConsole::save( array('notification_seconds'=>(string)$seconds), $service->audit );
    fw_assert( $seconds === FileWarden\Notice::duration(), 'An allowed notification duration was not persisted.' );
    foreach ($preserved as $field=>$value) fw_assert($value === get_option('filewarden_'.$field), 'Notification settings reset another category.');
}
foreach ( array('0','6','31','10.5','-1','5x',array('10')) as $invalid ) {
    fw_reject(fn()=>FileWarden\SettingsConsole::save(array('notification_seconds'=>$invalid,'upload_mb'=>'27'),$service->audit),InvalidArgumentException::class,'Forged duration accepted.');
    fw_assert(30 === FileWarden\Notice::duration() && $preserved['upload_mb'] === get_option('filewarden_upload_mb'), 'Invalid duration caused a partial settings update.');
}
FileWarden\SettingsConsole::save(array('audit_days'=>(string)$preserved['audit_days']),$service->audit);
fw_assert(30 === FileWarden\Notice::duration(),'Saving another category reset notification duration.');
wp_set_current_user($users['subscriber']);
fw_reject(fn()=>FileWarden\SettingsConsole::save(array('notification_seconds'=>'5'),$service->audit),DomainException::class,'Read capability granted settings writes.');
wp_set_current_user(1);
update_option($notice_option,6);
fw_assert(10 === FileWarden\Notice::duration(),'Invalid stored duration did not fall back safely.');
update_option($notice_option,10);
$_GET = array('fw_operation'=>'upload','folder_id'=>'1','created'=>'fixture');
$_GET['fw_feedback'] = FileWarden\Notice::receipt('success','upload',1,'fixture');
fw_assert(FileWarden\Notice::verified('success'),'Genuine feedback receipt rejected.');
fw_assert(!FileWarden\Notice::verified('policy_saved'),'Receipt was valid for a different outcome.');
$_GET['created'] = 'different';
fw_assert(!FileWarden\Notice::verified('success'),'Receipt accepted a modified document identity.');
$_GET['created'] = 'fixture';
wp_set_current_user($users['subscriber']);
fw_assert(!FileWarden\Notice::verified('success'),'Feedback receipt transferred between WordPress users.');
wp_set_current_user(1); $_GET = array('result'=>'success');
fw_assert(!FileWarden\Notice::verified('success'),'Query string alone produced a verified success.');
$feedback_user = wp_insert_user(array('user_login'=>'fw_feedback_reader','user_pass'=>'disposable-feedback-password','role'=>'subscriber'));
fw_assert(!is_wp_error($feedback_user),'Feedback reader fixture failed.');
$feedback_actor = new WP_User($feedback_user);
$feedback_actor->add_cap('filewarden_manage_settings');
wp_set_current_user($feedback_user);
$private_feedback = $repository->document($ids['private']);
$_GET = array('result'=>'success','fw_operation'=>'upload','folder_id'=>'0','created'=>$ids['private']);
$_GET['fw_feedback'] = FileWarden\Notice::receipt('success','upload',0,$ids['private']);
ob_start();
(new ReflectionMethod(FileWarden\AdminWorkspace::class,'notices'))->invoke(new FileWarden\AdminWorkspace($service,new FileWarden\AdminController($service)));
$restricted_feedback = ob_get_clean();
fw_assert(str_contains($restricted_feedback,'notice-success') && !str_contains($restricted_feedback,$private_feedback['name']), 'Changed read access hid all confirmed feedback or exposed a private name.');
wp_set_current_user(1);
$_GET = array();
ob_start();
FileWarden\Notice::show('<script>secret()</script>','error','<img src=x onerror=secret()>');
$escaped_notice = ob_get_clean();
fw_assert(!str_contains($escaped_notice,'<script>') && !str_contains($escaped_notice,'<img'),'Notification text allowed HTML injection.');
fw_assert(str_contains($escaped_notice,'data-fw-persistent="true"') && str_contains($escaped_notice,'role="alert"'),'Critical notification fallback was not persistent and accessible.');
FileWarden\Installer::migrate();
fw_assert(10 === FileWarden\Notice::duration(),'Repeated migration reset notification settings.');
WP_CLI::success('Notification settings, allowed values, partial saves, capabilities, feedback integrity and escaped fallback passed.');
