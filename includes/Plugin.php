<?php
/**
 * Runtime wiring.
 *
 * @package FileWarden
 */

namespace FileWarden;

/**
 * Plugin implementation.
 */
final class Plugin {
	/**
	 * Register services and WordPress hooks after schema validation.
	 *
	 * @return mixed Operation result.
	 */
	public static function boot() {
		if ( is_multisite() ) {
			return;
		}
		if ( Installer::SCHEMA !== get_option( 'filewarden_schema' ) ) {
			add_action(
				'admin_notices',
				static function () {
					echo '<div class="notice notice-error"><p>' . esc_html__( 'FileWarden requires reactivation to install its database schema.', 'filewarden' ) . '</p></div>';
				}
			);
			return;
		}
		$repository  = new Repository();
		$permissions = new PermissionService( array( $repository, 'folder' ) );
		$roots       = array( ABSPATH, WP_CONTENT_DIR );
		// DOCUMENT_ROOT must be provided by the web server, not by a request parameter.
		// phpcs:ignore WordPress.Security.ValidatedSanitizedInput -- Server configuration path must remain exact; canonicalized and checked by StorageService.
		$roots[] = isset( $_SERVER['DOCUMENT_ROOT'] ) ? (string) $_SERVER['DOCUMENT_ROOT'] : '';
		if ( defined( 'FILEWARDEN_PUBLIC_ROOTS' ) && is_array( FILEWARDEN_PUBLIC_ROOTS ) ) {
			$roots = array_merge( $roots, FILEWARDEN_PUBLIC_ROOTS );
		}
		$storage = new StorageService(
			defined( 'FILEWARDEN_STORAGE_DIR' ) ? FILEWARDEN_STORAGE_DIR : '',
			array_unique( $roots ),
			defined( 'FILEWARDEN_STORAGE_VERIFIED' ) && true === FILEWARDEN_STORAGE_VERIFIED,
			defined( 'FILEWARDEN_STORAGE_DIR' ) ? null : new ManagedStorage()
		);
		$audit   = new AuditService();
		$service = new DocumentService( $repository, $permissions, $storage, $audit );
		$rest    = new RestController( $service );
		$content = new ContentController( $service );
		$admin   = new AdminController( $service );
		Publication::register( $service );
		add_filter( 'media_row_actions', array( $admin, 'media_action' ), 10, 2 );
		add_action( 'admin_enqueue_scripts', array( $admin, 'media_assets' ) );
		add_action( 'rest_api_init', array( $rest, 'register' ) );
		add_action( 'admin_post_filewarden_content', array( $content, 'serve' ) );
		add_action( 'admin_post_nopriv_filewarden_content', array( $content, 'serve' ) );
		add_action( 'admin_menu', array( $admin, 'menu' ) );
		add_action( 'admin_post_filewarden_admin', array( $admin, 'submit' ) );
		add_action( 'filewarden_audit_cleanup', array( AuditService::class, 'cleanup' ) );
		if ( ! wp_next_scheduled( 'filewarden_audit_cleanup' ) ) {
			wp_schedule_event( time() + HOUR_IN_SECONDS, 'hourly', 'filewarden_audit_cleanup' );
		}
	}

	/**
	 * Remove scheduled work without deleting documents.
	 *
	 * @return mixed Operation result.
	 */
	public static function deactivate() {
		wp_clear_scheduled_hook( 'filewarden_audit_cleanup' );
	}
}
