<?php
/** Run only through wp eval-file on a disposable integration installation. */
if ( ! defined( 'WP_CLI' ) || ! WP_CLI || '1' !== getenv( 'FILEWARDEN_TESTING' ) ) {
	exit( 1 );
}

function fw_assert( $condition, $message ) {
	if ( ! $condition ) {
		throw new RuntimeException( $message );
	}
}

function fw_request( $method, $route, $params = array() ) {
	$request = new WP_REST_Request( $method, '/filewarden/v1' . $route );
	$request->set_body_params( $params );
	return rest_get_server()->dispatch( $request );
}

wp_set_current_user( 1 );
FileWarden\Installer::activate();
FileWarden\Installer::migrate();
fw_assert( '1' === get_option( 'filewarden_schema' ), 'Repeatable migration failed.' );

$repository = new FileWarden\Repository();
$permission = new FileWarden\PermissionService( array( $repository, 'folder' ) );
$storage    = new FileWarden\StorageService( FILEWARDEN_STORAGE_DIR, array( ABSPATH ), true );
$service    = new FileWarden\DocumentService( $repository, $permission, $storage, new FileWarden\AuditService() );
$folder     = $service->create_folder( 'Restricted', 0, array( 'type' => 'roles', 'roles' => array( 'editor' ) ) );
$child      = $service->create_folder( 'Public child', $folder, array( 'type' => 'public' ) );

$temporary = tempnam( sys_get_temp_dir(), 'fw-integration-' );
$bytes = "%PDF-1.4\n1 0 obj<</Type/Catalog>>endobj\n%%EOF\n";
file_put_contents( $temporary, $bytes );
$ids = array();
foreach ( array( 'private' => $child, 'public' => 0 ) as $kind => $parent ) {
	$key = $storage->put( $temporary, strlen( $bytes ) );
	$uuid = wp_generate_uuid4();
	$repository->insert( 'documents', array( 'uuid' => $uuid, 'folder_id' => $parent, 'name' => $kind . '.pdf', 'storage_key' => $key, 'mime' => 'application/pdf', 'size' => strlen( $bytes ), 'sha256' => hash( 'sha256', $bytes ), 'policy' => array( 'type' => 'public' ) ) );
	$ids[ $kind ] = $uuid;
}
unlink( $temporary );

// Direct REST dispatch tests exercise real database records and real WP capabilities.
$users = array();
foreach ( array( 'subscriber', 'editor' ) as $role ) {
	$id = wp_insert_user( array( 'user_login' => 'fw_' . $role, 'user_pass' => 'disposable-test-password', 'role' => $role, 'user_email' => $role . '@example.test' ) );
	fw_assert( ! is_wp_error( $id ), 'User fixture failed.' );
	$users[ $role ] = $id;
}
foreach ( array( 0, $users['subscriber'] ) as $id ) {
	wp_set_current_user( $id );
	fw_assert( 404 === fw_request( 'GET', '/documents/' . $ids['private'] )->get_status(), 'Private document metadata leaked.' );
	fw_assert( 200 === fw_request( 'GET', '/documents/' . $ids['public'] )->get_status(), 'Public document denied.' );
	fw_assert( 404 === fw_request( 'GET', '/documents', array( 'folder_id' => $child ) )->get_status(), 'Private folder leaked.' );
	fw_assert( fw_request( 'POST', '/folders', array( 'name' => 'Forbidden', 'policy' => array( 'type' => 'public' ) ) )->get_status() >= 400, 'Unauthorized folder write.' );
	fw_assert( fw_request( 'POST', '/documents' )->get_status() >= 400, 'Unauthorized upload.' );
}
wp_set_current_user( $users['editor'] );
$response = fw_request( 'GET', '/documents/' . $ids['private'] );
fw_assert( 200 === $response->get_status(), 'Authorized editor denied.' );
$data = $response->get_data();
fw_assert( ! isset( $data['storage_key'] ) && ! isset( $data['sha256'] ), 'Storage metadata leaked.' );
fw_assert( str_contains( $response->get_headers()['Cache-Control'], 'no-store' ), 'Missing private cache headers.' );

// Grant only read override: it must never grant write permissions.
$reader = new WP_User( $users['subscriber'] );
$reader->add_cap( 'filewarden_read_all_files' );
wp_set_current_user( 0 );
wp_set_current_user( $reader->ID );
fw_assert( 200 === fw_request( 'GET', '/documents/' . $ids['private'] )->get_status(), 'Explicit read override failed.' );
fw_assert( fw_request( 'POST', '/documents' )->get_status() >= 400, 'Read override granted upload.' );

// Changing ancestor policy must apply immediately, without stale ACL caching.
wp_set_current_user( $users['editor'] );
global $wpdb;
$wpdb->update( $wpdb->prefix . 'filewarden_folders', array( 'policy' => wp_json_encode( array( 'type' => 'roles', 'roles' => array( 'administrator' ) ) ) ), array( 'id' => $folder ) );
fw_assert( 404 === fw_request( 'GET', '/documents/' . $ids['private'] )->get_status(), 'Revoked access remained cached.' );
$wpdb->update( $wpdb->prefix . 'filewarden_folders', array( 'policy' => wp_json_encode( array( 'type' => 'roles', 'roles' => array( 'editor' ) ) ) ), array( 'id' => $folder ) );

wp_set_current_user( 1 );
$lock = $repository->lock( 0 );
fw_assert( $repository->duplicate( 0, 'public.pdf' ), 'Duplicate detection failed.' );
$repository->unlock( $lock );
$wpdb->insert( $wpdb->prefix . 'filewarden_audit_logs', array( 'event' => 'expired_fixture', 'created_at' => '2000-01-01 00:00:00' ) );
FileWarden\AuditService::cleanup();
FileWarden\AuditService::cleanup();
fw_assert( '0' === (string) $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->prefix}filewarden_audit_logs WHERE event = 'expired_fixture'" ), 'Audit cleanup failed.' );
file_put_contents( getenv( 'FILEWARDEN_FIXTURES' ), wp_json_encode( $ids ) );
WP_CLI::success( 'Integration assertions passed.' );
