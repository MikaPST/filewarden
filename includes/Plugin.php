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
		add_filter( 'plugin_row_meta', array( self::class, 'row_meta' ), 10, 2 );
		$migration_failed = false;
		// Existing encrypted jobs and repairs keep their recovery UI until the additive migration can run.
		$deferred = in_array( get_option( 'filewarden_schema' ), array( '6', '7', '8' ), true ) && ( get_option( BackupJobs::BARRIER ) || get_option( DatabaseUpgrade::CHECKPOINT ) || get_option( WordPressTables::CHECKPOINT ) );
		if ( ! $deferred && in_array( get_option( 'filewarden_schema' ), array( '1', '2', '3', '4', '5', '6', '7', '8' ), true ) ) {
			try {
				Installer::migrate();
			} catch ( \Throwable $error ) {
				$migration_failed = true;
			}
		}
		if ( $migration_failed || ( ! $deferred && Installer::SCHEMA !== get_option( 'filewarden_schema' ) ) ) {
			add_action(
				'admin_notices',
				static function () {
					Notice::show( __( 'FileWarden requires reactivation to install its database schema.', 'filewarden' ), 'error', __( 'FileWarden unavailable', 'filewarden' ) );
				}
			);
			return;
		}
		Installer::backup_capabilities();
		add_action(
			'init',
			array( Language::class, 'register' ),
			0
		);
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
		add_action( 'rest_api_init', static fn() => LibraryConsole::register( $service ) );
		add_action( 'admin_post_filewarden_content', array( $content, 'serve' ) );
		add_action( 'admin_post_nopriv_filewarden_content', array( $content, 'serve' ) );
		add_action( 'wp_ajax_filewarden_backup', static fn() => BackupConsole::handle( $service ) );
		add_action( 'wp_ajax_filewarden_diagnostic', static fn() => DiagnosticConsole::handle( $service ) );
		add_action( 'wp_ajax_filewarden_wp_tables', array( WordPressTables::class, 'handle' ) );
		add_action( 'admin_post_filewarden_backup_download', static fn() => BackupConsole::download( $service ) );
		add_action(
			'filewarden_audit_cleanup',
			static function () use ( $service ) {
				try {
					BackupJobs::exclusive( static fn() => BackupService::expire( $service ) ); } catch ( \Throwable $error ) {
								// Keep an unverifiable checkpoint frozen; do not guess which bytes are safe to remove.
					return; }
			},
			5
		);
		add_action( 'admin_post_filewarden_audit', array( AuditConsole::class, 'handle' ) );
		add_action( 'admin_menu', array( $admin, 'menu' ) );
		add_action( 'admin_post_filewarden_admin', array( $admin, 'submit' ) );
		add_action( 'filewarden_audit_cleanup', array( AuditService::class, 'cleanup' ) );
		add_action( 'filewarden_audit_cleanup', static fn() => ( new Lifecycle( $service ) )->cleanup() );
		if ( ! get_option( WordPressTables::CHECKPOINT ) && ! wp_next_scheduled( 'filewarden_audit_cleanup' ) ) {
			wp_schedule_event( time() + HOUR_IN_SECONDS, 'hourly', 'filewarden_audit_cleanup' );
		}
	}

	/**
	 * Label only FileWarden's official site link in the native plugin list.
	 *
	 * @param array  $links Existing metadata links.
	 * @param string $file Plugin basename.
	 * @return array Metadata links with the original author/version preserved.
	 */
	public static function row_meta( $links, $file ) {
		if ( plugin_basename( FILEWARDEN_PATH . 'filewarden.php' ) !== $file ) {
			return $links;
		}
		foreach ( $links as $key => $link ) {
			$tag = new \WP_HTML_Tag_Processor( $link );
			if ( $tag->next_tag( 'A' ) && 'https://filewarden.org' === rtrim( (string) $tag->get_attribute( 'href' ), '/' ) ) {
				$links[ $key ] = '<a href="' . esc_url( 'https://filewarden.org' ) . '">filewarden.org</a>';
			}
		}
		return $links;
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
