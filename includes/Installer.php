<?php
/**
 * Database and capability installation.
 *
 * @package FileWarden
 */

namespace FileWarden;

/**
 * Installer implementation.
 */
final class Installer {
	const SCHEMA       = '5';
	const CAPABILITIES = array( 'filewarden_read_all_files', 'filewarden_upload_files', 'filewarden_edit_files', 'filewarden_delete_files', 'filewarden_manage_permissions', 'filewarden_manage_settings', 'filewarden_view_audit', 'filewarden_manage_audit' );

	/**
	 * Install the single-site schema and administrator capabilities.
	 *
	 * @param mixed $network_wide network wide value.
	 * @return mixed Operation result.
	 */
	public static function activate( $network_wide = false ) {
		if ( is_multisite() || $network_wide ) {
			wp_die( esc_html__( 'FileWarden currently supports single-site WordPress only.', 'filewarden' ) );
		}
		self::migrate();
		$role = get_role( 'administrator' );
		if ( $role ) {
			foreach ( self::CAPABILITIES as $capability ) {
				$role->add_cap( $capability );
			}
		}
	}

	/**
	 * Apply repeatable schema migrations.
	 *
	 * @throws \RuntimeException When the operation cannot be completed safely.
	 * @return mixed Operation result.
	 */
	public static function migrate() {
		global $wpdb;
		$repository = new Repository();
		$lock       = $repository->lock( -1 );
		try {
			require_once ABSPATH . 'wp-admin/includes/upgrade.php';
			$charset = $wpdb->get_charset_collate();
			$prefix  = $wpdb->prefix . 'filewarden_';
			$sql     = array(
				"CREATE TABLE {$prefix}folders (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			uuid char(36) DEFAULT NULL,
			parent_id bigint(20) unsigned NOT NULL DEFAULT 0,
			name varchar(255) NOT NULL,
			policy longtext NOT NULL,
			created_by bigint(20) unsigned NOT NULL,
			created_at datetime NOT NULL,
			PRIMARY KEY  (id),
			UNIQUE KEY uuid (uuid),
			KEY parent_id (parent_id)
			) $charset;",
				"CREATE TABLE {$prefix}documents (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			uuid char(36) NOT NULL,
			folder_id bigint(20) unsigned NOT NULL DEFAULT 0,
			name varchar(255) NOT NULL,
			storage_key char(64) NOT NULL,
            retained_rules longtext DEFAULT NULL,
            trashed_at datetime DEFAULT NULL,
            purging tinyint(1) NOT NULL DEFAULT 0,
			mime varchar(100) NOT NULL,
			size bigint(20) unsigned NOT NULL,
			sha256 char(64) NOT NULL,
			policy longtext NOT NULL,
			created_by bigint(20) unsigned NOT NULL,
			created_at datetime NOT NULL,
			PRIMARY KEY  (id),
			UNIQUE KEY uuid (uuid),
			UNIQUE KEY storage_key (storage_key),
			KEY folder_id (folder_id),
            KEY trashed_at (trashed_at)
			) $charset;",
				"CREATE TABLE {$prefix}audit_logs (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			actor_id bigint(20) unsigned NOT NULL DEFAULT 0,
			actor_login varchar(60) NOT NULL DEFAULT '',
			event varchar(64) NOT NULL,
			object_id bigint(20) unsigned NOT NULL DEFAULT 0,
			created_at datetime NOT NULL,
			PRIMARY KEY  (id),
			KEY created_at (created_at)
			) $charset;",
			);
			foreach ( $sql as $statement ) {
				dbDelta( $statement );
				if ( $wpdb->last_error ) {
					throw new \RuntimeException( 'FileWarden schema installation failed.' );
				}
			}
			// Nullable unique UUIDs allow upgrades with existing rows; conditional writes are retry-safe.
			do {
				// phpcs:ignore WordPress.DB.DirectDatabaseQuery -- Bounded migration of custom records.
				$ids = $wpdb->get_col( $wpdb->prepare( 'SELECT id FROM %i WHERE uuid IS NULL LIMIT 100', $prefix . 'folders' ) );
				if ( $wpdb->last_error ) {
					throw new \RuntimeException( 'Folder migration read failed.' ); }
				foreach ( $ids as $id ) {
					// phpcs:ignore WordPress.DB.DirectDatabaseQuery -- Prepared, conditional backfill preserves existing references.
					if ( false === $wpdb->query( $wpdb->prepare( 'UPDATE %i SET uuid = %s WHERE id = %d AND uuid IS NULL', $prefix . 'folders', wp_generate_uuid4(), $id ) ) ) {
						throw new \RuntimeException( 'Folder UUID migration failed.' );
					}
				}
				$count = count( $ids );
			} while ( 100 === $count );
			add_option( 'filewarden_root_uuid', wp_generate_uuid4(), '', false );
			if ( ! wp_is_uuid( get_option( 'filewarden_root_uuid' ), 4 ) ) {
				throw new \RuntimeException( 'Invalid root UUID.' ); }
			if ( self::SCHEMA !== get_option( 'filewarden_schema' ) && get_role( 'administrator' ) ) {
				get_role( 'administrator' )->add_cap( 'filewarden_manage_audit' ); }
			update_option( 'filewarden_schema', self::SCHEMA, false );
		} finally {
			$repository->unlock( $lock ); }
	}
}
