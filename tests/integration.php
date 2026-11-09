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
	if ( 'GET' === $method || 'HEAD' === $method ) {
		$request->set_query_params( $params );
	} else {
		$request->set_body_params( $params );
	}
	return rest_get_server()->dispatch( $request );
}

wp_set_current_user( 1 );
FileWarden\Installer::activate();
FileWarden\Installer::migrate();
fw_assert( '3' === get_option( 'filewarden_schema' ), 'Repeatable migration failed.' );

$repository = new FileWarden\Repository();
$permission = new FileWarden\PermissionService( array( $repository, 'folder' ) );
$storage    = new FileWarden\StorageService( FILEWARDEN_STORAGE_DIR, array( ABSPATH ), true );
$service    = new FileWarden\DocumentService( $repository, $permission, $storage, new FileWarden\AuditService() );
$folder     = $service->create_folder( 'Restricted', 0, array( 'type' => 'roles', 'roles' => array( 'editor' ) ) );
$child      = $service->create_folder( 'Public child', $folder, array( 'type' => 'public' ) );
// Recreate the actual v1 folder layout and upgrade without deleting records.
global $wpdb;
$wpdb->query( "ALTER TABLE {$wpdb->prefix}filewarden_folders DROP INDEX uuid, DROP COLUMN uuid" );
update_option( 'filewarden_schema', '1' );
FileWarden\Installer::migrate();
$folder_uuid = FileWarden\FolderReference::uuid( $folder );
$child_uuid = FileWarden\FolderReference::uuid( $child );
$root_uuid = FileWarden\FolderReference::uuid( 0 );
fw_assert( wp_is_uuid( $folder_uuid, 4 ) && $folder_uuid !== $child_uuid, 'Existing folders did not receive unique UUIDs.' );
FileWarden\Installer::migrate();
fw_assert( $folder_uuid === FileWarden\FolderReference::uuid( $folder ) && $root_uuid === FileWarden\FolderReference::uuid( 0 ), 'Repeated migration changed permanent references.' );
fw_assert( $child === FileWarden\FolderReference::resolve( $child_uuid ) && $child === FileWarden\FolderReference::resolve( (string) $child ), 'UUID and legacy references differ.' );
fw_assert( 0 === FileWarden\FolderReference::resolve( $root_uuid ), 'Root UUID did not resolve.' );
fw_assert( str_contains( FileWarden\Publication::shortcode( array( 'folder' => $child_uuid ) ), $child_uuid ), 'Shortcode omitted UUID.' );
fw_assert( str_contains( FileWarden\Publication::shortcode( array( 'folder' => $child ) ), $child_uuid ), 'Legacy shortcode was not converted.' );
foreach ( array( wp_generate_uuid4(), 'invalid', '', array() ) as $invalid ) {
    try {
        FileWarden\FolderReference::resolve( $invalid );
        fw_assert( false, 'Invalid reference resolved to a folder.' );
    } catch ( \InvalidArgumentException $expected ) { }
}


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
	fw_assert( ! isset( $service->folder_choices()[ $child ] ), 'Private folder appeared in the selector.' );
	fw_assert( 404 === fw_request( 'GET', '/documents/' . $ids['private'] )->get_status(), 'Private document metadata leaked.' );
	fw_assert( 200 === fw_request( 'GET', '/documents/' . $ids['public'] )->get_status(), 'Public document denied.' );
	fw_assert( 404 === fw_request( 'GET', '/documents', array( 'folder_id' => $child ) )->get_status(), 'Private folder leaked.' );
	fw_assert( fw_request( 'POST', '/folders', array( 'name' => 'Forbidden', 'policy' => array( 'type' => 'public' ) ) )->get_status() >= 400, 'Unauthorized folder write.' );
	fw_assert( fw_request( 'POST', '/documents' )->get_status() >= 400, 'Unauthorized upload.' );
}
wp_set_current_user( $users['editor'] );
fw_assert( 'Restricted / Public child' === ( $service->folder_choices()[ $child ] ?? null ), 'Authorized folder path missing from selector.' );
$response = fw_request( 'GET', '/documents/' . $ids['private'] );
fw_assert( 200 === $response->get_status(), 'Authorized editor denied.' );
$data = $response->get_data();
fw_assert( ! isset( $data['storage_key'] ) && ! isset( $data['sha256'] ), 'Storage metadata leaked.' );
fw_assert( str_contains( $response->get_headers()['Cache-Control'], 'no-store' ), 'Missing private cache headers.' );
$listing = fw_request( 'GET', '/documents', array( 'folder_id' => $child ) );
fw_assert( 200 === $listing->get_status() && array( $ids['private'] ) === array_column( $listing->get_data(), 'id' ), 'Authorized folder listing did not return its document.' );

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
$png = base64_decode( 'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO+a82cAAAAASUVORK5CYII=' );
$media = wp_upload_bits( 'filewarden-fixture.png', null, $png );
fw_assert( ! $media['error'], 'Media fixture upload failed.' );
$ids['media_id'] = wp_insert_attachment( array( 'post_mime_type' => 'image/png', 'post_title' => 'FileWarden fixture', 'post_status' => 'inherit' ), $media['file'], 0, true );
fw_assert( ! is_wp_error( $ids['media_id'] ) && $ids['media_id'] > 0, 'Media attachment fixture failed.' );
$ids['folder_id'] = $child;
$ids['folder_uuid'] = $child_uuid;
$ids['root_uuid'] = $root_uuid;
file_put_contents( getenv( 'FILEWARDEN_FIXTURES' ), wp_json_encode( $ids ) );
// Publication must scan beyond raw batches, then paginate only authorized results.
$search_folder = $service->create_folder( 'Search fixtures', 0, array( 'type' => 'public' ) );
fw_assert( wp_is_uuid( FileWarden\FolderReference::uuid( $search_folder ), 4 ), 'New folder UUID missing.' );
$template = $repository->document( $ids['public'] );
unset( $template['id'], $template['created_at'], $template['created_by'] );
for ( $i = 0; $i < 115; $i++ ) {
    $record = $template;
    $record['uuid'] = wp_generate_uuid4();
    // Metadata-only search fixtures never serve binary content.
    $record['storage_key'] = bin2hex( random_bytes( 32 ) );
    $record['folder_id'] = $search_folder;
    $record['name'] = ( $i < 101 ? 'Hidden-' : 'Visible-' ) . $i . '.pdf';
    $record['policy'] = array( 'type' => $i < 101 ? 'authenticated' : 'public' );
    $repository->insert( 'documents', $record );
}
wp_set_current_user( 0 );
fw_assert( array() === FileWarden\Publication::page( $service, $search_folder, 'Hidden', 0 ), 'Search leaked restricted names.' );
$first = FileWarden\Publication::page( $service, $search_folder, 'visible', 0 );
fw_assert( 13 === count( $first ) && 'Visible-101.pdf' === $first[0]['name'], 'Authorized pagination stopped at raw batch boundary.' );
$second = FileWarden\Publication::page( $service, $search_folder, 'visible', 12 );
fw_assert( 2 === count( $second ) && 'Visible-113.pdf' === $second[0]['name'], 'Visible pagination skipped or duplicated results.' );
fw_assert( array() === FileWarden\Publication::page( $service, $search_folder, '%', 0 ), 'Search treated literal percent as a wildcard.' );
try {
    FileWarden\Publication::page( $service, $child, '', 0 );
    fw_assert( false, 'Publication bypassed inherited folder policy.' );
} catch ( \DomainException $error ) {
    // Expected denial.
}
wp_set_current_user( 1 );

$policy_folder = $service->create_folder( 'Policy tests', 0, array( 'type' => 'public' ) );
$ids['policy_folder'] = $policy_folder;
$ids['policy_folder_uuid'] = FileWarden\FolderReference::uuid( $policy_folder );
$temp = tempnam( sys_get_temp_dir(), 'fw-policy-' );
file_put_contents( $temp, $bytes );
foreach ( array( 'inherit', 'authenticated', 'public' ) as $type ) {
    $record = $template;
    $record['uuid'] = wp_generate_uuid4();
    $record['folder_id'] = $policy_folder;
    $record['name'] = 'policy-' . $type . '.pdf';
    $record['storage_key'] = $storage->put( $temp, strlen( $bytes ) );
    $record['policy'] = array( 'type' => $type );
    $repository->insert( 'documents', $record );
    $ids['policy_' . $type] = $record['uuid'];
}
unlink( $temp );
wp_set_current_user( 0 );
fw_assert( 2 === count( FileWarden\Publication::page( $service, $policy_folder, '', 0 ) ), 'Public folder leaked restricted file or hid inherited files.' );
foreach ( array( 0, $users['editor'], $users['subscriber'] ) as $actor ) {
    wp_set_current_user( $actor );
    try {
        $service->change_policy( 'document', $ids['policy_authenticated'], array( 'type' => 'public' ) );
        fw_assert( false, 'Read access granted policy mutation.' );
    } catch ( \DomainException $expected ) { }
}
wp_set_current_user( 1 );
$service->change_policy( 'folder', $policy_folder, array( 'type' => 'authenticated' ) );
wp_set_current_user( 0 );
foreach ( array( 'inherit', 'authenticated', 'public' ) as $type ) {
    fw_assert( ! $permission->can_read( $repository->document( $ids['policy_' . $type] ), wp_get_current_user() ), 'File rule overrode a restricted folder.' );
}
wp_set_current_user( 1 );
$service->change_policy( 'folder', $policy_folder, array( 'type' => 'public' ) );
fw_assert( 'authenticated' === $repository->document( $ids['policy_authenticated'] )['policy']['type'], 'Folder update overwrote explicit document rule.' );
fw_assert( 'inherit' === $repository->document( $ids['policy_inherit'] )['policy']['type'], 'Folder update copied a policy into inherited document.' );
fw_assert( $ids['policy_folder_uuid'] === FileWarden\FolderReference::uuid( $policy_folder ), 'Policy edit changed UUID.' );
try {
    $service->change_policy( 'folder', $policy_folder, array( 'type' => 'roles', 'roles' => array() ) );
    fw_assert( false, 'Empty selected-role rule accepted.' );
} catch ( \InvalidArgumentException $expected ) { }
fw_assert( 'public' === $repository->folder( $policy_folder )['policy']['type'], 'Rejected policy changed folder.' );
file_put_contents( getenv( 'FILEWARDEN_FIXTURES' ), wp_json_encode( $ids ) );
require __DIR__ . '/lifecycle-integration.php';
WP_CLI::success( 'Integration assertions passed.' );
