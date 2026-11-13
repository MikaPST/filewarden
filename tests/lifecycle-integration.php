<?php
/** Included by integration.php on the disposable WordPress installation only. */
if ( ! defined( 'WP_CLI' ) || ! WP_CLI || '1' !== getenv( 'FILEWARDEN_TESTING' ) ) { exit( 1 ); }

function fw_reject( $callback, $exception, $message ) {
    try { $callback(); } catch ( Throwable $error ) {
        fw_assert( $error instanceof $exception, $message . ': unexpected ' . get_class( $error ) );
        return;
    }
    throw new RuntimeException( $message );
}
wp_set_current_user( 1 );
// Upgrade the previous document schema with existing bytes and policies intact.
$before = $repository->document( $ids['public'] );
$wpdb->query( "ALTER TABLE {$wpdb->prefix}filewarden_documents DROP INDEX trashed_at, DROP COLUMN trashed_at, DROP COLUMN purging" );
update_option( 'filewarden_schema', '2' );
FileWarden\Installer::migrate();
FileWarden\Installer::migrate();
$after = $repository->document( $ids['public'] );
ksort( $before );
ksort( $after );
fw_assert( '5' === get_option( 'filewarden_schema' ) && $before === $after, 'Schema 2 upgrade changed existing document.' );

$life = new FileWarden\Lifecycle( $service );
$destination = $service->create_folder( 'Lifecycle private', 0, array( 'type' => 'authenticated' ) );
$source_uuid = $ids['public'];
$proof = $life->plan( $source_uuid, $destination )['proof'];
fw_reject( fn() => $life->transfer( 'copy', $source_uuid, $destination, 'copy.pdf', '' ), InvalidArgumentException::class, 'Unreviewed transfer accepted.' );
fw_reject( fn() => $life->transfer( 'copy', $source_uuid, $destination, 'copy.php', $proof ), InvalidArgumentException::class, 'Copy changed extension.' );
$service->change_policy( 'folder', $destination, array( 'type' => 'roles', 'roles' => array( 'editor' ) ) );
fw_reject( fn() => $life->transfer( 'copy', $source_uuid, $destination, 'copy.pdf', $proof ), InvalidArgumentException::class, 'Stale destination review accepted.' );
$proof = $life->plan( $source_uuid, $destination )['proof'];
$service->change_policy( 'document', $source_uuid, array( 'type' => 'authenticated' ) );
fw_reject( fn() => $life->transfer( 'copy', $source_uuid, $destination, 'copy.pdf', $proof ), InvalidArgumentException::class, 'Stale file policy accepted.' );
$service->change_policy( 'document', $source_uuid, array( 'type' => 'public' ) );
$proof = $life->plan( $source_uuid, $destination )['proof'];
$copy = $life->transfer( 'copy', $source_uuid, $destination, 'copy.pdf', $proof );
$copied = $repository->document( $copy['id'] );
fw_assert( $source_uuid !== $copy['id'] && $before['storage_key'] !== $copied['storage_key'], 'Copy reused source identity or bytes.' );
$stream = $storage->open( $copied['storage_key'], (int) $copied['size'] );
fw_assert( $bytes === stream_get_contents( $stream ), 'Copied content changed.' );
fclose( $stream );
fw_assert( $repository->document( $source_uuid )['policy'] === $copied['policy'], 'Copy changed explicit policy.' );
$summary = FileWarden\AccessSummary::effective( $copied, $repository );
fw_assert( $summary['conflict'] && str_contains( $summary['label'], 'Editor' ), 'Public file restriction missing from effective access.' );
wp_set_current_user( 0 );
fw_assert( 404 === fw_request( 'GET', '/documents/' . $copy['id'] )->get_status(), 'Public copy bypassed private destination.' );
wp_set_current_user( $users['subscriber'] );
fw_reject( fn() => $life->trash( $source_uuid ), DomainException::class, 'Read override granted deletion.' );
fw_reject( fn() => $life->transfer( 'copy', $source_uuid, $public_folder, 'copy.pdf', $proof ), DomainException::class, 'Read override granted copy.' );
wp_set_current_user( 1 );
// Capability separation: management permission does not imply edit/upload/delete.
$actor = new WP_User( $users['editor'] );
$actor->add_cap( 'filewarden_manage_permissions' );
wp_set_current_user( 0 ); wp_set_current_user( $actor->ID );
$plan = $life->plan( $copy['id'], $public_folder );
foreach ( array( 'move', 'copy' ) as $operation ) {
    fw_reject( fn() => $life->transfer( $operation, $copy['id'], $public_folder, 'copy.pdf', $plan['proof'] ), DomainException::class, 'Permission management granted ' . $operation );
}
$actor->remove_cap( 'filewarden_manage_permissions' );
wp_set_current_user( 1 );
// Move preserves permanent link, private binary and own rule; only ancestry changes.
$move_plan = $life->plan( $copy['id'], $public_folder );
$moved = $life->transfer( 'move', $copy['id'], $public_folder, 'moved-copy.pdf', $move_plan['proof'] );
$moved_record = $repository->document( $moved['id'] );
fw_assert( $copy['id'] === $moved['id'] && $copied['storage_key'] === $moved_record['storage_key'] && $copied['policy'] === $moved_record['policy'], 'Move changed identity, bytes or policy.' );
wp_set_current_user( 0 );
fw_assert( 200 === fw_request( 'GET', '/documents/' . $moved['id'] )->get_status(), 'Move failed to apply new public ancestry.' );
wp_set_current_user( 1 );
$plan = $life->plan( $moved['id'], $public_folder );
fw_reject( fn() => $life->transfer( 'copy', $moved['id'], $public_folder, 'public.pdf', $plan['proof'] ), UnexpectedValueException::class, 'Copy overwrote an active filename.' );
// Trash denies all read routes, including administrator override; restore is idempotent.
$life->trash( $moved['id'] );
$life->trash( $moved['id'] );
fw_assert( null === $repository->document( $moved['id'] ), 'Trashed document still resolved publicly.' );
fw_assert( 404 === fw_request( 'GET', '/documents/' . $moved['id'] )->get_status(), 'Admin read override served trash.' );
fw_assert( ! in_array( $moved['id'], array_column( FileWarden\Publication::page( $service, 0, 'moved-copy', 0 ), 'uuid' ), true ), 'Publication listed trash.' );
$life->trash( $moved['id'], true );
$life->trash( $moved['id'], true );
fw_assert( $moved_record === $repository->document( $moved['id'] ), 'Restore changed document data.' );
// A replacement filename blocks restoration without overwriting either file.
$life->trash( $moved['id'] );
$plan = $life->plan( $source_uuid, $public_folder );
$collision = $life->transfer( 'copy', $source_uuid, $public_folder, 'moved-copy.pdf', $plan['proof'] );
fw_reject( fn() => $life->trash( $moved['id'], true ), UnexpectedValueException::class, 'Restoration overwrote a replacement.' );
fw_assert( null === $repository->document( $moved['id'] ) && null !== $repository->document( $collision['id'] ), 'Failed restore changed active file.' );
$life->trash( $collision['id'] );
$life->trash( $moved['id'], true );
// Cleanup deletes only expired trash and tolerates an interrupted purge tombstone.
$life->trash( $moved['id'] );
$repository->update_document( (int) $moved_record['id'], array( 'trashed_at' => '2000-01-01 00:00:00' ) );
$life->cleanup(); $life->cleanup();
fw_assert( null === $repository->document( $moved['id'], true ), 'Expired trash metadata remained.' );
fw_reject( fn() => $storage->open( $moved_record['storage_key'], (int) $moved_record['size'] ), RuntimeException::class, 'Expired binary remained readable.' );
fw_assert( null !== $repository->document( $source_uuid ), 'Cleanup deleted active source.' );
fw_assert( null !== $repository->document( $collision['id'], true ), 'Cleanup deleted unexpired trash.' );
$collision_record = $repository->document( $collision['id'], true );
$repository->update_document( (int) $collision_record['id'], array( 'purging' => 1 ) );
$storage->remove( $collision_record['storage_key'] );
fw_reject( fn() => $life->trash( $collision['id'], true ), DomainException::class, 'Interrupted purge was restored.' );
$life->cleanup();
fw_assert( null === $repository->document( $collision['id'], true ), 'Interrupted purge did not finish.' );
$stream = $storage->open( $before['storage_key'], (int) $before['size'] );
fw_assert( $bytes === stream_get_contents( $stream ), 'Copy cleanup damaged original bytes.' );
fclose( $stream );
wp_set_current_user( 1 );
