<?php
/** Empty installation acceptance; only run against a dedicated disposable database. */
if ( ! defined( 'WP_CLI' ) || ! WP_CLI || '1' !== getenv( 'FILEWARDEN_TESTING' ) ) {
	exit( 1 );
}
function fw_fresh_assert( $condition, $message ) {
	if ( ! $condition ) {
		throw new RuntimeException( $message );
	}
}
wp_set_current_user( 1 );
global $wpdb;
foreach ( array( 'folders', 'documents', 'audit_logs' ) as $table ) {
	fw_fresh_assert( 0 === (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->prefix}filewarden_{$table}" ), 'Fresh acceptance requires empty FileWarden tables.' );
	$engine = $wpdb->get_var( $wpdb->prepare( 'SELECT ENGINE FROM information_schema.TABLES WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME=%s', $wpdb->prefix . 'filewarden_' . $table ) );
	fw_fresh_assert( 'innodb' === strtolower( $engine ), 'Fresh table is not InnoDB.' );
}
fw_fresh_assert( ! FileWarden\ImportPolicy::unrestricted(), 'Fresh installation enabled all formats.' );
fw_fresh_assert( 'auto' === FileWarden\Language::choice(), 'Fresh installation did not select automatic language.' );
$repository = new FileWarden\Repository();
$storage = new FileWarden\StorageService( FILEWARDEN_STORAGE_DIR, array( ABSPATH ), true );
$service = new FileWarden\DocumentService( $repository, new FileWarden\PermissionService( array( $repository, 'folder' ) ), $storage, new FileWarden\AuditService() );
$render = function () use ( $service ) {
	ob_start();
	( new FileWarden\AdminWorkspace( $service, new FileWarden\AdminController( $service ) ) )->render();
	return ob_get_clean();
};
$_GET = array( 'page' => 'filewarden' );
$empty = $render();
fw_fresh_assert( ! str_contains( $empty, 'class="fw-recovery-count"' ), 'An empty recovery collection displays a zero badge.' );
fw_fresh_assert( str_contains( $empty, 'Create your first folder' ), 'Fresh welcome is missing.' );
$temporary = tempnam( sys_get_temp_dir(), 'fw-fresh-' );
$bytes = "%PDF-1.4\n1 0 obj<</Type/Catalog>>endobj\n%%EOF\n";
file_put_contents( $temporary, $bytes );
$key = $storage->put( $temporary, strlen( $bytes ) );
unlink( $temporary );
$uuid = wp_generate_uuid4();
$repository->insert( 'documents', array( 'uuid' => $uuid, 'folder_id' => 0, 'name' => 'fresh.pdf', 'storage_key' => $key, 'mime' => 'application/pdf', 'size' => strlen( $bytes ), 'sha256' => hash( 'sha256', $bytes ), 'policy' => array( 'type' => 'inherit' ) ) );
$_GET = array( 'page' => 'filewarden', 'collection' => 'unfiled', 'panel' => 'move', 'document' => $uuid );
$move = $render();
fw_fresh_assert( str_contains( $move, 'class="fw-recovery-count"' ), 'First recovery document did not create a badge.' );
fw_fresh_assert( str_contains( $move, 'fw-empty-destination' ) && str_contains( $move, 'button button-primary" disabled' ), 'No destination did not disable review with an explanation.' );
fw_fresh_assert( ! str_contains( $move, 'data-classify-later' ), 'Recovery offers a redundant classify-later shortcut.' );
fw_fresh_assert( ! preg_match( '/<select id="fw-destination"[^>]*>\s*<option/', $move ), 'Recovery proposes itself as a destination.' );
$_GET['panel'] = 'copy';
$copy = $render();
fw_fresh_assert( str_contains( $copy, 'fw-empty-destination' ), 'Copy into an absent folder is offered.' );
WP_CLI::success( 'Fresh InnoDB/defaults, zero/nonzero badge and empty move/copy destinations passed.' );
