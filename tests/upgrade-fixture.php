<?php
/** Real ZIP-to-ZIP upgrade acceptance on the disposable installation only. */
if ( ! defined( 'WP_CLI' ) || ! WP_CLI || '1' !== getenv( 'FILEWARDEN_TESTING' ) ) { exit( 1 ); }
$upgrade_file = getenv( 'FILEWARDEN_UPGRADE_FIXTURE' );
if ( ! $upgrade_file ) { throw new RuntimeException( 'Explicit upgrade fixture path required.' ); }
wp_set_current_user( 1 );
$repo = new FileWarden\Repository();
$permissions = new FileWarden\PermissionService( array( $repo, 'folder' ) );
$storage = new FileWarden\StorageService( FILEWARDEN_STORAGE_DIR, array( ABSPATH ), true );
$service = new FileWarden\DocumentService( $repo, $permissions, $storage, new FileWarden\AuditService() );
if ( 'before' === getenv( 'FILEWARDEN_UPGRADE_STAGE' ) ) {
    if ( getenv('FILEWARDEN_PREVIOUS_SCHEMA') !== get_option( 'filewarden_schema' ) || getenv('FILEWARDEN_PREVIOUS_VERSION') !== FILEWARDEN_VERSION ) { throw new RuntimeException( 'Previous ZIP did not activate the expected version and schema.' ); }
    $settings = array('filewarden_upload_mb'=>41,'filewarden_trash_days'=>17,'filewarden_audit_days'=>256);
    foreach($settings as $key=>$value) update_option($key,$value);
    $folder = $service->create_folder( 'Upgrade fixture', 0, array( 'type' => 'authenticated' ) );
    $bytes = "%PDF-1.4\n1 0 obj<</Type/Catalog>>endobj\n%%EOF\n";
    $temp = tempnam( sys_get_temp_dir(), 'fw-upgrade-' ); file_put_contents( $temp, $bytes );
    $uuid = wp_generate_uuid4();
    $repo->insert( 'documents', array( 'uuid' => $uuid, 'folder_id' => $folder, 'name' => 'upgrade-fixture.pdf', 'storage_key' => $storage->put( $temp, strlen( $bytes ) ), 'mime' => 'application/pdf', 'size' => strlen( $bytes ), 'sha256' => hash( 'sha256', $bytes ), 'policy' => array( 'type' => 'inherit' ) ) );
    unlink( $temp );
    $service->audit->record('upgrade_history_fixture');
    global $wpdb;
    $audit = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}filewarden_audit_logs ORDER BY id",ARRAY_A);
    file_put_contents( $upgrade_file, wp_json_encode( array( 'document' => $repo->document( $uuid ), 'folder' => $repo->folder( $folder ), 'bytes_sha256' => hash( 'sha256', $bytes ), 'settings'=>$settings, 'audit'=>$audit ) ) );
    WP_CLI::success( 'Previous release ZIP activated and persistent upgrade fixture saved.' );
} else {
    $before = json_decode( file_get_contents( $upgrade_file ), true );
    if ( '6' !== get_option( 'filewarden_schema' ) || '0.1.13-dev' !== FILEWARDEN_VERSION ) { throw new RuntimeException( 'ZIP update did not install the corrective version and schema 6.' ); }
    // Numeric options become strings when read back in a fresh WordPress process.
    foreach($before['settings'] as $key=>$value) { if((string)get_option($key)!==(string)$value) throw new RuntimeException('ZIP upgrade reset an existing setting: '.$key); }
    global $wpdb;
    foreach($before['audit'] as $row) {
        $saved = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->prefix}filewarden_audit_logs WHERE id = %d",$row['id']),ARRAY_A);
        foreach($row as $key=>$value) { if($saved[$key]!==$value) throw new RuntimeException('ZIP upgrade changed existing audit history.'); }
    }
    $after = $repo->document( $before['document']['uuid'] );
    foreach ( $before['document'] as $key => $value ) { if ( $after[$key] !== $value ) { throw new RuntimeException( 'ZIP upgrade changed existing document field: ' . $key ); } }
    if ( $repo->folder( (int) $before['folder']['id'] ) !== $before['folder'] ) { throw new RuntimeException( 'ZIP upgrade changed an existing folder.' ); }
    $stream = $storage->open( $after['storage_key'], (int) $after['size'] );
    $hash = hash( 'sha256', stream_get_contents( $stream ) ); fclose( $stream );
    if ( $hash !== $before['bytes_sha256'] ) { throw new RuntimeException( 'ZIP upgrade damaged document bytes.' ); }
    FileWarden\Installer::migrate();
    if ( $repo->document( $after['uuid'] ) !== $after ) { throw new RuntimeException( 'Repeated upgrade changed data.' ); }
    ob_start();
    (new FileWarden\AdminWorkspace($service,new FileWarden\AdminController($service)))->render();
    $empty_workspace = ob_get_clean();
    if (!str_contains($empty_workspace, 'fw-recovery-count">(0)</span>')) { throw new RuntimeException('Empty installed recovery counter did not explicitly show zero.'); }
    WP_CLI::success( 'Actual ZIP upgrade preserved document bytes, UUID, policies, folders and storage keys.' );
}
