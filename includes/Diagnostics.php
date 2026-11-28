<?php
/** Bounded diagnostic probes; reports contain only explicitly constructed safe fields.
 *
 * @package FileWarden
 */

namespace FileWarden;

/** Diagnostic results are observations, not a security certification. */
final class Diagnostics {
	/** Return fixed translated groups.
	 *
	 * @return array Group labels.
	 */
	public static function groups() {
		return array(
			'environment' => __( 'PHP environment', 'filewarden' ),
			'crypto'      => __( 'Encryption', 'filewarden' ),
			'storage'     => __( 'Private storage', 'filewarden' ),
			'database'    => __( 'Database', 'filewarden' ),
			'capacity'    => __( 'Space and limits', 'filewarden' ),
			'jobs'        => __( 'Active tasks', 'filewarden' ),
		);
	}

	/** Measure exact table engines, with a table-level metadata fallback.
	 *
	 * @param array $tables Fixed server-selected names.
	 * @return string State.
	 */
	private static function tables( array $tables ) {
		global $wpdb;
		$previous = $wpdb->suppress_errors( true );
		try {
			$slots = implode( ',', array_fill( 0, count( $tables ), '%s' ) );
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery,WordPress.DB.PreparedSQL.InterpolatedNotPrepared,WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare -- Exact server-selected identifiers passed as prepared metadata values.
			$rows = $wpdb->get_results( $wpdb->prepare( "SELECT TABLE_NAME AS Name, ENGINE AS Engine FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME IN ($slots)", $tables ), ARRAY_A );
			if ( $wpdb->last_error || count( $rows ?? array() ) !== count( $tables ) ) {
				// phpcs:ignore WordPress.DB.DirectDatabaseQuery,WordPress.DB.PreparedSQL.InterpolatedNotPrepared,WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare -- Table-level fallback uses the same fixed name values.
				$rows = $wpdb->get_results( $wpdb->prepare( "SHOW TABLE STATUS WHERE Name IN ($slots)", $tables ), ARRAY_A );
			}
			$names = array_column( $rows ?? array(), 'Name' );
			sort( $names );
			sort( $tables );
			if ( $wpdb->last_error || $names !== $tables ) {
				return 'unknown';
			}
			return array_filter( $rows, static fn( $row ) => 'innodb' !== strtolower( (string) $row['Engine'] ) ) ? 'blocked' : 'good';
		} finally {
			$wpdb->suppress_errors( $previous );
		}
	}

	/** Check create/read/delete using random bytes, never document contents.
	 *
	 * @param string $root Canonical verified private root.
	 * @return bool Witness fully handled.
	 */
	private static function witness( $root ) {
		$path   = $root . '/diagnostic-' . bin2hex( random_bytes( 16 ) ) . '.tmp';
		$stream = null;
		$passed = false;
		$owned  = false;
		try {
			// phpcs:disable WordPress.WP.AlternativeFunctions -- Exclusive random private witness, removed in finally.
			$stream = FileIO::attempt( fn() => fopen( $path, 'x+b' ) );
			$owned  = is_resource( $stream );
			if ( ! $stream || ! FileIO::attempt( fn() => chmod( $path, 0600 ) ) ) {
				return false;
			}
			$bytes  = random_bytes( 32 );
			$passed = 32 === FileIO::attempt( fn() => fwrite( $stream, $bytes ) ) && FileIO::attempt( fn() => fflush( $stream ) ) && FileIO::attempt( fn() => rewind( $stream ) ) && hash_equals( $bytes, (string) FileIO::attempt( fn() => fread( $stream, 32 ) ) );
		} finally {
			if ( is_resource( $stream ) ) {
				fclose( $stream );
			}
			if ( $owned && ! FileIO::attempt( fn() => unlink( $path ) ) ) {
				$passed = false;
			}
			// phpcs:enable WordPress.WP.AlternativeFunctions
		}
		return $passed;
	}

	/** Run measured checks with the settings capability; never repair or weaken a guard.
	 *
	 * @param DocumentService $service Shared services.
	 * @return array Safe report.
	 * @throws \DomainException Without authorization.
	 * @throws \RuntimeException When a required check cannot start.
	 */
	public static function run( DocumentService $service ) {
		if ( ! current_user_can( 'filewarden_manage_settings' ) ) {
			throw new \DomainException( 'Access denied.' );
		}
		global $wpdb;
		$checks = array();
		$add    = static function ( $id, $group, $label, $state, $message, $reference = '' ) use ( &$checks ) {
			$check = array(
				'id'        => $id,
				'group'     => $group,
				'label'     => $label,
				'state'     => $state,
				'message'   => $message,
				'reference' => $reference,
			);
			if ( 'good' !== $state && in_array( $id, array( 'wp_tables', 'plugin_tables' ), true ) ) {
				$check['action'] = 'wp_tables' === $id ? 'wordpress_tables' : 'plugin_tables';
			}
			$checks[] = $check;
		};
		/* translators: 1: PHP version, 2: configured PHP memory limit. */
		$add( 'php', 'environment', __( 'Minimum PHP version', 'filewarden' ), version_compare( PHP_VERSION, '8.2', '>=' ) ? 'good' : 'blocked', sprintf( __( 'PHP %1$s; configured memory: %2$s. This checks the minimum version, not complete hosting compatibility.', 'filewarden' ), PHP_VERSION, sanitize_text_field( ini_get( 'memory_limit' ) ) ) );
		try {
			CryptoSupport::stream();
			$key                     = sodium_crypto_secretstream_xchacha20poly1305_keygen();
			list( $writer, $header ) = sodium_crypto_secretstream_xchacha20poly1305_init_push( $key );
			$reader                  = sodium_crypto_secretstream_xchacha20poly1305_init_pull( $header, $key );
			$cipher                  = sodium_crypto_secretstream_xchacha20poly1305_push( $writer, 'FileWarden diagnostic', '', SODIUM_CRYPTO_SECRETSTREAM_XCHACHA20POLY1305_TAG_FINAL );
			$result                  = sodium_crypto_secretstream_xchacha20poly1305_pull( $reader, $cipher );
			sodium_memzero( $key );
			if ( array( 'FileWarden diagnostic', SODIUM_CRYPTO_SECRETSTREAM_XCHACHA20POLY1305_TAG_FINAL ) !== $result ) {
				throw new \RuntimeException( 'Sodium streaming failed.' );
			}
			$add( 'stream', 'crypto', __( 'Authenticated encryption', 'filewarden' ), 'good', __( 'Sodium encrypted and authenticated a temporary in-memory witness.', 'filewarden' ) );
		} catch ( \Throwable $error ) {
			$safe = BackupErrors::response( in_array( $error->getMessage(), array( 'Sodium extension missing.', 'Sodium streaming unavailable.' ), true ) ? $error : new \RuntimeException( 'Sodium streaming failed.' ) );
			$add( 'stream', 'crypto', __( 'Authenticated encryption', 'filewarden' ), 'blocked', $safe['message'], $safe['code'] );
		}
		try {
			if ( ! CryptoSupport::native() ) {
				throw new \RuntimeException( 'Sodium extension missing.' ); }
			$key = BackupArchive::key( bin2hex( random_bytes( 24 ) ), random_bytes( 16 ) );
			sodium_memzero( $key );
			$add( 'argon', 'crypto', __( 'Argon2id key derivation', 'filewarden' ), 'good', __( 'A temporary key was derived with the same bounded parameters used by backups.', 'filewarden' ) );
		} catch ( \Throwable $error ) {
			$safe = BackupErrors::response( $error );
			$add( 'argon', 'crypto', __( 'Argon2id key derivation', 'filewarden' ), 'blocked', $safe['message'], $safe['code'] );
		}
		$root = null;
		try {
			$root = $service->storage->root();
			$add( 'protection', 'storage', __( 'Storage protection', 'filewarden' ), 'good', __( 'The configured storage isolation checks passed for this request.', 'filewarden' ) );
		} catch ( \Throwable $error ) {
			$add( 'protection', 'storage', __( 'Storage protection', 'filewarden' ), 'unknown', __( 'Private storage protection could not be verified. Review Storage and security; file operations remain blocked.', 'filewarden' ), 'FWB_STORAGE_VERIFICATION' );
		}
		$writable = false;
		if ( $root ) {
			try {
				$writable = self::witness( $root );
			} catch ( \Throwable $error ) {
				$writable = false;
			}
		}
		$add( 'write', 'storage', __( 'Private create, read and cleanup', 'filewarden' ), $root ? ( $writable ? 'good' : 'blocked' ) : 'unknown', $writable ? __( 'The private random witness was written, read and removed successfully.', 'filewarden' ) : __( 'The private write/read/cleanup check did not complete. Ask your host to check permissions and quotas.', 'filewarden' ), $writable ? '' : 'FWB_STORAGE_WRITE' );
		foreach ( array(
			'plugin_tables' => array( __( 'FileWarden tables', 'filewarden' ), array( $wpdb->prefix . 'filewarden_documents', $wpdb->prefix . 'filewarden_folders', $wpdb->prefix . 'filewarden_audit_logs' ) ),
			'wp_tables'     => array( __( 'WordPress restoration tables', 'filewarden' ), array( $wpdb->options, $wpdb->posts, $wpdb->postmeta ) ),
		) as $id => $item ) {
			$state   = self::tables( $item[1] );
			$message = match ( $state ) {
				'good' => __( 'All required tables were found and use InnoDB.', 'filewarden' ),
				'blocked' => __( 'At least one required table is nontransactional. No automatic conversion was performed.', 'filewarden' ),
				default => __( 'The required tables or their engines could not be verified.', 'filewarden' ),
			};
			$add( $id, 'database', $item[0], $state, $message, 'good' === $state ? '' : 'FWB_DATABASE' );
		}
		$lock = null;
		try {
			$lock = $service->repository->lock( 'diagnostic-metadata' );
			BackupManifest::capture();
			$add( 'manifest', 'database', __( 'Backup metadata consistency', 'filewarden' ), 'good', __( 'The library metadata passed the backup schema and size checks without creating an archive.', 'filewarden' ) );
		} catch ( \Throwable $error ) {
			$safe = BackupErrors::response( $error );
			$add( 'manifest', 'database', __( 'Backup metadata consistency', 'filewarden' ), 'FWB_BUSY' === $safe['code'] ? 'unknown' : 'blocked', $safe['message'], $safe['code'] );
		} finally {
			if ( $lock ) {
				$service->repository->unlock( $lock );
			}
		}
		$free = false;
		if ( $root && function_exists( 'disk_free_space' ) ) {
			$free = FileIO::attempt( fn() => disk_free_space( $root ) ); // phpcs:ignore WordPress.WP.AlternativeFunctions -- Verified canonical storage, report includes only the numeric result.
		}
		/* translators: %s: measured disk space. */
		$add( 'disk', 'capacity', __( 'Free storage space', 'filewarden' ), false === $free ? 'unknown' : ( $free < 20971520 ? 'blocked' : 'good' ), false === $free ? __( 'Free space could not be measured. Backup remains blocked until its capacity check succeeds.', 'filewarden' ) : sprintf( __( '%s available now. Each backup rechecks its actual requirements and safety reserve.', 'filewarden' ), size_format( $free, 1 ) ), false === $free ? 'FWB_SPACE_UNKNOWN' : '' );
		/* translators: %s: effective PHP upload limit. */
		$add( 'upload', 'capacity', __( 'Restoration upload limit', 'filewarden' ), wp_max_upload_size() > 0 ? 'good' : 'blocked', sprintf( __( '%s per uploaded backup. This limit does not apply to creating an export.', 'filewarden' ), size_format( wp_max_upload_size() ) ) );
		$barrier = (bool) get_option( BackupJobs::BARRIER );
		$repair  = (bool) get_option( DatabaseUpgrade::CHECKPOINT ) || (bool) get_option( WordPressTables::CHECKPOINT );
		$add( 'jobs', 'jobs', __( 'Library write availability', 'filewarden' ), $barrier || $repair ? 'unknown' : 'good', $barrier ? __( 'A backup or restoration holds the write barrier. Resume or cancel that task from Backup and migration.', 'filewarden' ) : ( $repair ? __( 'A database repair is pending. Review Storage and security.', 'filewarden' ) : __( 'No FileWarden backup or repair barrier is active.', 'filewarden' ) ) );
		return array(
			'format'         => 1,
			'checked_at_UTC' => gmdate( 'c' ),
			'plugin'         => array(
				'version' => FILEWARDEN_VERSION,
				'schema'  => (string) get_option( 'filewarden_schema' ),
			),
			'environment'    => array(
				'native_sodium'        => CryptoSupport::native(),
				'php'                  => PHP_VERSION,
				'wordpress'            => sanitize_text_field( get_bloginfo( 'version' ) ),
				'configured_memory'    => sanitize_text_field( ini_get( 'memory_limit' ) ),
				'execution_seconds'    => (int) ini_get( 'max_execution_time' ),
				'restore_upload_bytes' => wp_max_upload_size(),
			),
			'checks'         => $checks,
		);
	}
}
