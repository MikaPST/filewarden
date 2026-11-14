<?php
/** Acceptance for the actual ten approved evolutions; disposable WordPress only. */
if ( ! defined( 'WP_CLI' ) || ! WP_CLI || '1' !== getenv( 'FILEWARDEN_TESTING' ) ) { exit( 1 ); }
wp_set_current_user( 1 );
$life = new FileWarden\Lifecycle( $service );
$restricted_counter = new WP_User( $users['subscriber'] );
$restricted_counter->add_cap( 'filewarden_manage_permissions' );
wp_set_current_user( $restricted_counter->ID );
fw_assert( array() === FileWarden\DocumentCollection::rows( $service, false ), 'Permission-management capability disclosed recovery without settings access.' );
$restricted_counter->remove_cap( 'filewarden_manage_permissions' );
wp_set_current_user( 1 );
$initial_count = count( FileWarden\DocumentCollection::rows( $service, false ) );
$recovery_fixtures = array();
for ( $i = 0; $i < 3; ++$i ) {
    $plan = $life->plan( $ids['public'], $public_folder );
    $copy = $life->transfer( 'copy', $ids['public'], $public_folder, 'classify-fixture-' . $i . '.pdf', $plan['proof'] );
    $original = $repository->document( $copy['id'] );
    $plan = $life->plan( $copy['id'], 0 );
    $life->transfer( 'move', $copy['id'], 0, $original['name'], $plan['proof'] );
    $after = $repository->document( $copy['id'] );
    fw_assert( $original['storage_key'] === $after['storage_key'] && $original['uuid'] === $after['uuid'], 'Classification changed permanent identity or storage.' );
    fw_assert( ! empty( $after['retained_rules'] ), 'Classification did not retain ancestor restrictions.' );
    $recovery_fixtures[] = $copy['id'];
}
fw_assert( $initial_count + 3 === count( FileWarden\DocumentCollection::rows( $service, false ) ), 'Recovery count did not increase by three.' );
wp_set_current_user( 0 );
foreach ( $recovery_fixtures as $uuid ) { fw_assert( 404 === fw_request( 'GET', '/documents/' . $uuid )->get_status(), 'Classified document remained public.' ); }
fw_reject( fn() => FileWarden\DocumentCollection::rows( $service, false ), DomainException::class, 'Anonymous recovery count was disclosed.' );
wp_set_current_user( 1 );
$life->trash( $recovery_fixtures[0] );
fw_assert( $initial_count + 2 === count( FileWarden\DocumentCollection::rows( $service, false ) ), 'Recovery count included trashed content.' );
fw_reject( fn() => $life->trash( $recovery_fixtures[0], true ), InvalidArgumentException::class, 'Recovery trash restored without a valid destination.' );
$life->trash( $recovery_fixtures[0], true, $public_folder );
fw_assert( $public_folder === (int) $repository->document( $recovery_fixtures[0] )['folder_id'], 'Recovery trash did not restore into the selected folder.' );
$plan = $life->plan( $recovery_fixtures[1], $public_folder );
$life->transfer( 'move', $recovery_fixtures[1], $public_folder, 'reclassified.pdf', $plan['proof'] );
fw_assert( $initial_count + 1 === count( FileWarden\DocumentCollection::rows( $service, false ) ), 'Reclassification did not reduce the recovery count.' );
$ids['recovery_fixture_uuid'] = $recovery_fixtures[2];

// Global trash spans unrelated folders; deletion preserves the original path.
$trash_folder = $service->create_folder( 'Deleted origin fixture', 0, array( 'type' => 'authenticated' ) );
$plan = $life->plan( $ids['public'], $trash_folder );
$trashed = $life->transfer( 'copy', $ids['public'], $trash_folder, 'deleted-origin.pdf', $plan['proof'] );
$life->trash( $trashed['id'] );
$old_record = $repository->document( $trashed['id'], true );
$old_origin = json_decode( $old_record['trash_origin'], true );
fw_assert( 'Deleted origin fixture' === $old_origin['path'], 'Trash original path was not captured.' );
FileWarden\FolderLifecycle::remove( $service, $trash_folder );
$saved = $repository->document( $trashed['id'], true );
fw_assert( $saved['trash_origin'] === $old_record['trash_origin'] && $saved['trashed_at'] === $old_record['trashed_at'] && 0 === (int) $saved['folder_id'], 'Deleting a folder lost trash origin or state.' );
fw_assert( in_array( $trashed['id'], array_column( FileWarden\DocumentCollection::rows( $service, true ), 'uuid' ), true ), 'Global trash did not include a recovered document.' );
fw_reject( fn() => $life->trash( $trashed['id'], true ), InvalidArgumentException::class, 'Missing original folder did not require a new destination.' );
$life->trash( $trashed['id'], true, $public_folder );
fw_assert( $public_folder === (int) $repository->document( $trashed['id'] )['folder_id'], 'Recovered trash restoration did not use the explicit destination.' );

// A mid-recovery database failure rolls back earlier updates and preserves the folder.
$rollback_folder = $service->create_folder( 'Recovery rollback fixture', 0, array( 'type' => 'authenticated' ) );
$rollback_rows = array();
for ( $i = 0; $i < 2; ++$i ) {
    $plan = $life->plan( $ids['public'], $rollback_folder );
    $copy = $life->transfer( 'copy', $ids['public'], $rollback_folder, 'rollback-' . $i . '.pdf', $plan['proof'] );
    $rollback_rows[$copy['id']] = $repository->document( $copy['id'], true );
}
$writes = 0;
$fail_second = static function ( $sql ) use ( &$writes, $wpdb ) {
    if ( str_starts_with( $sql, 'UPDATE `' . $wpdb->prefix . 'filewarden_documents`' ) && str_contains( $sql, '`folder_id` = 0' ) && ++$writes === 2 ) {
        return 'FILEWARDEN_INTENTIONAL_RECOVERY_FAILURE';
    }
    return $sql;
};
$previous_errors = $wpdb->suppress_errors( true );
add_filter( 'query', $fail_second );
try { fw_reject( fn() => FileWarden\FolderLifecycle::remove( $service, $rollback_folder ), RuntimeException::class, 'Injected recovery failure was accepted.' ); }
finally { remove_filter( 'query', $fail_second ); $wpdb->suppress_errors( $previous_errors ); }
fw_assert( 2 === $writes && null !== $repository->folder( $rollback_folder ), 'Rollback fixture did not exercise the second write or lost its folder.' );
foreach ( $rollback_rows as $uuid => $before ) {
    fw_assert( $before === $repository->document( $uuid, true ), 'Failed folder deletion partially moved a document.' );
}

// Explicit disposal needs a current review, never permits active files or read-only users.
$life->trash( $trashed['id'] );
$review = FileWarden\TrashService::review( $service, $trashed['id'] );
fw_assert( 1 === count( $review['rows'] ), 'Single-item purge review had an incorrect scope.' );
fw_reject( fn() => FileWarden\TrashService::purge( $service, $trashed['id'], 'invalid' ), InvalidArgumentException::class, 'Invalid trash review accepted.' );
$active_review = FileWarden\TrashService::review( $service, $ids['public'] );
fw_reject( fn() => FileWarden\TrashService::purge( $service, $ids['public'], $active_review['proof'] ), InvalidArgumentException::class, 'Active document was permanently purged.' );
fw_assert( 1 === FileWarden\TrashService::purge( $service, $trashed['id'], $review['proof'] ), 'Confirmed single-item purge failed.' );
fw_assert( null === $repository->document( $trashed['id'], true ), 'Purged document metadata remained.' );
fw_reject( fn() => $storage->open( $saved['storage_key'], (int) $saved['size'] ), RuntimeException::class, 'Purged document bytes remained.' );
wp_set_current_user( $users['subscriber'] );
fw_reject( fn() => FileWarden\TrashService::review( $service ), DomainException::class, 'Global read capability granted trash disposal.' );
wp_set_current_user( 1 );

// Audit snapshots survive deletion; JSONL schema is versioned and uses UTC ISO 8601.
$audit_rows = FileWarden\AuditHistory::rows( FileWarden\AuditHistory::filters( array( 'event' => 'document_purged' ) ), FileWarden\AuditHistory::upper() );
fw_assert( 'deleted-origin.pdf' === $audit_rows[0]['resource_name'] && 'user' === $audit_rows[0]['actor_type'], 'Purge audit lost resource or actor snapshots.' );
$export = new ReflectionMethod( FileWarden\AuditConsole::class, 'export_record' );
$event = $export->invoke( null, $audit_rows[0] );
fw_assert( 2 === $event['schema_version'] && 1 === preg_match( '/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}Z$/', $event['created_at_UTC'] ), 'Audit JSONL version or UTC ISO 8601 date is missing.' );
fw_assert( 'deleted-origin.pdf' === $event['resource_name'], 'Authorized audit export lost a deleted resource name.' );
$malformed = $audit_rows[0];
$malformed['resource_rules'] = '{"policies":"invalid"}';
fw_assert( '' === (new ReflectionMethod(FileWarden\AuditConsole::class,'resource_name'))->invoke(null,$malformed), 'Invalid stored resource rules disclosed a deleted name.' );
wp_set_current_user( 0 );
fw_assert( '' === (new ReflectionMethod(FileWarden\AuditConsole::class,'resource_name'))->invoke(null,$audit_rows[0]), 'Deleted private resource snapshot leaked to an anonymous reader.' );
$service->audit->record( 'audit_system_fixture' );
wp_set_current_user( 1 );
$system_rows = FileWarden\AuditHistory::rows( FileWarden\AuditHistory::filters( array('event'=>'audit_system_fixture') ), FileWarden\AuditHistory::upper() );
fw_assert( 'system' === $system_rows[0]['actor_type'] && 0 === (int) $system_rows[0]['actor_id'], 'CLI action was labelled as an anonymous visitor.' );
$legacy = $system_rows[0]; $legacy['actor_type'] = '';
fw_assert( 'unknown' === $export->invoke(null,$legacy)['actor_type'], 'Legacy actor zero was invented as visitor or system.' );
fw_assert( 'unknown' === $export->invoke(null,$legacy)['outcome'], 'Unknown legacy event was invented as successful.' );
$wpdb->insert($wpdb->prefix.'filewarden_audit_logs',array('actor_id'=>0,'actor_login'=>'','event'=>'audit_legacy_unknown','object_id'=>0,'created_at'=>current_time('mysql',true)),array('%d','%s','%s','%d','%s'));

// Failure beyond the first export batch must not produce a successful partial download.
$export_reads = 0;
$fail_export = static function($sql) use (&$export_reads) {
    if (str_starts_with($sql,'SELECT id, created_at, event,') && str_contains($sql,"'audit_export_fixture'") && str_contains($sql,'LIMIT 1000') && ++$export_reads === 2) return 'FILEWARDEN_INTENTIONAL_EXPORT_FAILURE';
    return $sql;
};
$previous_errors = $wpdb->suppress_errors(true);
add_filter('query',$fail_export);
try {
    fw_reject(fn()=>(new ReflectionMethod(FileWarden\AuditConsole::class,'prepare_export'))->invoke(null, FileWarden\AuditHistory::filters(array('event'=>'audit_export_fixture')), FileWarden\AuditHistory::upper(), 'jsonl'), RuntimeException::class, 'Partial export was treated as complete.');
} finally { remove_filter('query',$fail_export); $wpdb->suppress_errors($previous_errors); }
fw_assert(2 === $export_reads, 'Export failure did not exercise the second batch.');

// Partial settings save cannot reset any other category.
update_option( 'filewarden_trash_days', 47 );
update_option( 'filewarden_audit_days', 123 );
FileWarden\SettingsConsole::save( array( 'upload_mb' => '25' ), $service->audit );
fw_assert( 47 === get_option( 'filewarden_trash_days' ) && 123 === get_option( 'filewarden_audit_days' ) && 25 === get_option( 'filewarden_upload_mb' ), 'Partial settings save reset another category.' );
fw_reject( fn() => FileWarden\SettingsConsole::save( array( 'upload_mb' => '26', 'audit_days' => '0' ), $service->audit ), InvalidArgumentException::class, 'Invalid settings combination accepted.' );
fw_assert( 25 === get_option( 'filewarden_upload_mb' ), 'Invalid settings combination partially changed values.' );
update_option( 'filewarden_upload_mb', 50 ); update_option( 'filewarden_trash_days', 30 ); update_option( 'filewarden_audit_days', 365 );
file_put_contents( getenv( 'FILEWARDEN_FIXTURES' ), wp_json_encode( $ids ) );
$zip_fixture = tempnam( sys_get_temp_dir(), 'fw-archive-' );
$zip = new ZipArchive(); $zip->open( $zip_fixture, ZipArchive::OVERWRITE ); $zip->addFromString( 'readme.txt', "Safe downloadable archive\n" ); $zip->close();
$ids['safe_zip_base64'] = base64_encode( file_get_contents( $zip_fixture ) );
$zip = new ZipArchive(); $zip->open( $zip_fixture, ZipArchive::OVERWRITE ); $zip->addFromString( 'payload.php', '<?php echo 1;' ); $zip->close();
$ids['unsafe_zip_base64'] = base64_encode( file_get_contents( $zip_fixture ) ); unlink( $zip_fixture );
file_put_contents( getenv( 'FILEWARDEN_FIXTURES' ), wp_json_encode( $ids ) );
WP_CLI::success( 'Ten-evolution acceptance: recovery counter, classification, global trash, origin, explicit restoration, permanent purge, audit snapshots and partial settings passed.' );
