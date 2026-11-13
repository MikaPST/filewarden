<?php
/** New navigation, recovery and audit acceptance on disposable WordPress only. */
if ( ! defined( 'WP_CLI' ) || ! WP_CLI || '1' !== getenv( 'FILEWARDEN_TESTING' ) ) { exit( 1 ); }
wp_set_current_user( 1 );
$parent = $service->create_folder( 'Navigation public', 0, array( 'type' => 'public' ) );
$nested = $service->create_folder( 'Inherited child', $parent, array( 'type' => 'inherit' ) );
$hidden = $service->create_folder( 'Connected child', $parent, array( 'type' => 'authenticated' ) );
wp_set_current_user( 0 );
fw_assert( array( $nested ) === array_map( 'intval', array_column( FileWarden\FolderNavigation::children( $service, $parent ), 'id' ) ), 'Unauthorized child leaked.' );
fw_assert( 2 === count( FileWarden\FolderNavigation::path( $service, $parent, $nested ) ), 'Nested breadcrumb failed.' );
fw_reject( fn() => FileWarden\FolderNavigation::path( $service, $nested, $parent ), DomainException::class, 'Navigation escaped published root.' );
fw_reject( fn() => FileWarden\FolderNavigation::path( $service, 0, 0 ), DomainException::class, 'Recovery collection published.' );
wp_set_current_user( 1 );
fw_reject( fn() => FileWarden\FolderLifecycle::remove( $service, $parent ), DomainException::class, 'Non-leaf folder deleted.' );
fw_assert( 0 === FileWarden\FolderLifecycle::remove( $service, $nested ), 'Empty leaf failed deletion.' );
$life = new FileWarden\Lifecycle( $service );
$plan = $life->plan( $ids['public'], $hidden );
$recovered = $life->transfer( 'copy', $ids['public'], $hidden, 'recovered.pdf', $plan['proof'] );
$old = $repository->document( $recovered['id'] );
fw_assert( 1 === FileWarden\FolderLifecycle::remove( $service, $hidden ), 'Recovery count incorrect.' );
$after = $repository->document( $recovered['id'] );
fw_assert( 0 === (int) $after['folder_id'] && $old['storage_key'] === $after['storage_key'] && ! empty( $after['retained_rules'] ), 'Recovery lost identity, bytes or restrictions.' );
wp_set_current_user( 0 );
fw_assert( 404 === fw_request( 'GET', '/documents/' . $recovered['id'] )->get_status(), 'Recovered file publicly readable.' );
wp_set_current_user( 1 );
$plan = $life->plan( $recovered['id'], $public_folder );
$life->transfer( 'move', $recovered['id'], $public_folder, 'recovered.pdf', $plan['proof'] );
wp_set_current_user( 0 );
fw_assert( 404 === fw_request( 'GET', '/documents/' . $recovered['id'] )->get_status(), 'Reclassification widened inherited rights.' );
wp_set_current_user( $users['editor'] );
fw_assert( 200 === fw_request( 'GET', '/documents/' . $recovered['id'] )->get_status(), 'Retained authenticated access denied.' );
fw_reject( fn() => FileWarden\AuditHistory::upper(), DomainException::class, 'Audit leaked without capability.' );
wp_set_current_user( 1 );
$filters = FileWarden\AuditHistory::filters( array( 'event' => 'audit_fixture', 'from' => '', 'until' => '' ) );
fw_reject( fn() => FileWarden\AuditHistory::filters( array( 'from' => '2026-02-31T12:00' ) ), InvalidArgumentException::class, 'Invalid date accepted.' );
$service->audit->record( 'audit_fixture' );
$upper = FileWarden\AuditHistory::upper();
$fixture_rows = FileWarden\AuditHistory::rows( $filters, $upper );
fw_assert( get_userdata( 1 )->user_login === $fixture_rows[0]['actor_login'], 'Audit event did not retain the WordPress login identifier.' );
$actor_filter = FileWarden\AuditHistory::filters( array( 'actor_login' => get_userdata( 1 )->user_login ) );
fw_assert( count( FileWarden\AuditHistory::rows( $actor_filter, $upper ) ) > 0, 'Audit actor filter did not find the WordPress user.' );
$review = FileWarden\AuditHistory::review( $filters, $upper );
fw_assert( 1 === $review['count'], 'Purge filter count differs.' );
$service->audit->record( 'audit_fixture' );
fw_reject( fn() => FileWarden\AuditHistory::purge( $filters, $upper, 'invalid' ), InvalidArgumentException::class, 'Unreviewed purge accepted.' );
FileWarden\AuditHistory::purge( $filters, $upper, $review['proof'] );
fw_assert( 1 === count( FileWarden\AuditHistory::rows( $filters, FileWarden\AuditHistory::upper() ) ), 'Purge removed new events outside snapshot.' );
$traces = FileWarden\AuditHistory::filters( array( 'event' => 'audit_purged' ) );
fw_assert( 1 === count( FileWarden\AuditHistory::rows( $traces, FileWarden\AuditHistory::upper() ) ), 'Purge trace missing.' );
fw_assert( 0 === FileWarden\AuditHistory::review( $traces, FileWarden\AuditHistory::upper() )['count'], 'Manual purge can erase its own traces.' );

// Schema 4 databases gain the login snapshot without losing audit history.
$audit_count_before = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->prefix}filewarden_audit_logs" );
$wpdb->query( "ALTER TABLE {$wpdb->prefix}filewarden_audit_logs DROP COLUMN actor_login" );
update_option( 'filewarden_schema', '4' );
FileWarden\Installer::migrate();
fw_assert( '5' === get_option( 'filewarden_schema' ), 'Schema 4 audit upgrade did not complete.' );
fw_assert( $audit_count_before === (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->prefix}filewarden_audit_logs" ), 'Schema 4 audit upgrade lost history.' );
$service->audit->record( 'audit_actor_snapshot' );
$actor_rows = FileWarden\AuditHistory::rows( FileWarden\AuditHistory::filters( array( 'event' => 'audit_actor_snapshot' ) ), FileWarden\AuditHistory::upper() );
fw_assert( get_userdata( 1 )->user_login === $actor_rows[0]['actor_login'], 'Migrated audit table did not store the WordPress login.' );
$wpdb->insert(
	$wpdb->prefix . 'filewarden_audit_logs',
	array( 'actor_id' => 1, 'actor_login' => '', 'event' => 'audit_legacy_actor', 'object_id' => 0, 'created_at' => current_time( 'mysql', true ) ),
	array( '%d', '%s', '%s', '%d', '%s' )
);
$legacy_actor_filter = FileWarden\AuditHistory::filters( array( 'actor_login' => get_userdata( 1 )->user_login ) );
fw_assert( count( FileWarden\AuditHistory::rows( $legacy_actor_filter, FileWarden\AuditHistory::upper() ) ) > 1, 'Current WordPress login did not resolve legacy audit records.' );
$stream = fopen( 'php://temp', 'w+' );
$line   = new ReflectionMethod( FileWarden\AuditConsole::class, 'line' );
$line->setAccessible( true );
$line->invoke( null, $stream, array( 123, '2026-11-13 16:45:00', 'audit_fixture', 1, 'fw_admin', 42 ), 'jsonl' );
rewind( $stream );
$jsonl = json_decode( stream_get_contents( $stream ), true );
fclose( $stream );
fw_assert( is_array( $jsonl ) && 'fw_admin' === $jsonl['actor_login'] && 1 === $jsonl['actor_id'] && 42 === $jsonl['object_id_or_count'], 'JSONL audit record schema or field types are invalid.' );
$stream = fopen( 'php://temp', 'w+' );
$line->invoke( null, $stream, array( 124, '2026-11-13 16:45:00', '=1+1', 1, 'fw_admin', 42 ), 'csv' );
rewind( $stream );
$csv_line = stream_get_contents( $stream );
fclose( $stream );
fw_assert( false !== strpos( $csv_line, "'=1+1" ), 'CSV export did not neutralize spreadsheet formulas.' );

// A full filtered export must traverse more than one server batch and screen page.
for ( $i = 0; $i < 1005; ++$i ) {
	$service->audit->record( 'audit_export_fixture', $i );
}
$export_filters = FileWarden\AuditHistory::filters( array( 'event' => 'audit_export_fixture' ) );
$first_batch = FileWarden\AuditHistory::rows( $export_filters, FileWarden\AuditHistory::upper(), 1000 );
fw_assert( 1000 === count( $first_batch ), 'Audit export batch did not contain 1000 events.' );
$second_batch = FileWarden\AuditHistory::rows( $export_filters, (int) $first_batch[999]['id'] - 1, 1000 );
fw_assert( 5 === count( $second_batch ), 'Audit export cursor lost or duplicated events.' );
fw_assert( array() === FileWarden\AuditHistory::rows( FileWarden\AuditHistory::filters( array( 'actor_login' => "' OR 1=1 --" ) ), FileWarden\AuditHistory::upper() ), 'Audit actor input changed the SQL filter scope.' );
$stored_actor = new ReflectionMethod( FileWarden\AuditConsole::class, 'actor_login' );
fw_assert( 'fw_deleted_actor' === $stored_actor->invoke( null, array( 'actor_id' => 999999999, 'actor_login' => 'fw_deleted_actor' ) ), 'Stored login disappeared when its WordPress account was unavailable.' );
fw_assert( '' === $stored_actor->invoke( null, array( 'actor_id' => 999999999, 'actor_login' => '' ) ), 'Legacy event invented a login for a missing WordPress account.' );

// Audit read permission alone must not grant purge or private document metadata.
$audit_reader = get_userdata( $users['subscriber'] );
$audit_reader->add_cap( 'filewarden_view_audit' );
wp_set_current_user( $audit_reader->ID );
FileWarden\AuditHistory::authorize();
fw_reject( fn() => FileWarden\AuditHistory::review( $export_filters, $upper ), DomainException::class, 'Audit read capability granted purge review.' );
$resource = new ReflectionMethod( FileWarden\AuditConsole::class, 'resource_name' );
$private_record = $repository->document( $ids['private'] );
fw_assert( '' === $resource->invoke( null, array( 'event' => 'document_uploaded', 'object_id' => $private_record['id'] ) ), 'Audit reader learned a private document name without content access.' );
fw_assert( '' === $resource->invoke( null, array( 'event' => 'folder_created', 'object_id' => $folder ) ), 'Audit reader learned a private folder name without content access.' );
$audit_reader->remove_cap( 'filewarden_view_audit' );
wp_set_current_user( 1 );
