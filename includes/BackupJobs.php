<?php
/** Private encrypted checkpoints and the cross-request library write barrier.
 *
 * @package FileWarden
 */

namespace FileWarden;

// phpcs:disable WordPress.WP.AlternativeFunctions -- Private, canonical local files with server-generated names only.
// phpcs:disable WordPress.DB.DirectDatabaseQuery -- Fresh prepared barrier reads must not use a stale option cache.
/** A crashed request keeps the snapshot frozen until recovery or explicit cleanup. */
final class BackupJobs {
	const BARRIER = 'filewarden_backup_barrier';
	const KEY     = 'filewarden_backup_job_key';
	const APPLIED = 'filewarden_backup_applied';
	const TTL     = 3600;

	/** Internal scope, entered only after job authorization and writer locking.
	 *
	 * @var bool
	 */
	private static $internal = false;

	/** Reject other writers, including audit, expiry and settings during a snapshot.
	 *
	 * @throws \RuntimeException While frozen.
	 */
	public static function guard() {
		if ( self::$internal ) {
			return; }
		global $wpdb;
		$value = $wpdb->get_var( $wpdb->prepare( 'SELECT option_value FROM %i WHERE option_name = %s', $wpdb->options, self::BARRIER ) );
		if ( $wpdb->last_error || $value ) {
			throw new \RuntimeException( 'Library backup in progress.' );
		}
	}

	/** Canonical private checkpoint path, never a URL or archive member path.
	 *
	 * @param DocumentService $service Services.
	 * @param string          $id Generated identifier.
	 * @param string          $suffix Allowlisted suffix.
	 * @return string Path.
	 * @throws \RuntimeException If isolation cannot be established.
	 */
	public static function path( DocumentService $service, $id, $suffix = 'state' ) {
		if ( ! preg_match( '/^[a-f0-9]{32}$/D', $id ) || ! in_array( $suffix, array( 'state', 'tmp', 'fwbackup' ), true ) ) {
			throw new \RuntimeException( 'Invalid backup job.' ); }
		$root = $service->storage->root();
		$dir  = $root . DIRECTORY_SEPARATOR . 'backups';
		if ( is_link( $dir ) || ( ! is_dir( $dir ) && ! mkdir( $dir, 0700 ) ) || ! realpath( $dir ) || ! StorageService::within( realpath( $dir ), $root ) ) {
			throw new \RuntimeException( 'Private backup storage unavailable.' ); }
		$path = $dir . DIRECTORY_SEPARATOR . $id . '.' . $suffix;
		if ( is_link( $path ) ) {
			throw new \RuntimeException( 'Invalid backup entry.' ); }
		return $path;
	}

	/** Per-site wrapping key, deliberately absent from exported settings.
	 *
	 * @param DocumentService $service Verified private storage.
	 * @return string Key.
	 * @throws \RuntimeException If lost or malformed.
	 */
	private static function wrapping_key( DocumentService $service ) {
		$key = get_option( self::KEY );
		if ( false === $key ) {
			$probe = self::path( $service, str_repeat( '0', 32 ) );
			if ( glob( dirname( $probe ) . '/*.state' ) ) {
				throw new \RuntimeException( 'Existing backup checkpoint key is missing.' ); }
			add_option( self::KEY, bin2hex( random_bytes( 32 ) ), '', false );
			$key = get_option( self::KEY );
		}
		if ( ! is_string( $key ) || ! preg_match( '/^[a-f0-9]{64}$/D', $key ) ) {
			throw new \RuntimeException( 'Backup checkpoint key unavailable.' ); }
		return hex2bin( $key );
	}

	/** Encrypt an atomic checkpoint before acknowledging a completed unit of work.
	 *
	 * @param DocumentService $service Services.
	 * @param array           $job Job.
	 * @throws \RuntimeException On persistence failure.
	 */
	public static function save( DocumentService $service, array $job ) {
		$plain = wp_json_encode( $job );
		if ( ! is_string( $plain ) || strlen( $plain ) > 16777216 ) {
			throw new \RuntimeException( 'Backup checkpoint limit exceeded.' ); }
		$nonce = random_bytes( SODIUM_CRYPTO_SECRETBOX_NONCEBYTES );
		$bytes = $nonce . sodium_crypto_secretbox( $plain, $nonce, self::wrapping_key( $service ) );
		$tmp   = self::path( $service, $job['id'], 'tmp' );
		if ( strlen( $bytes ) !== file_put_contents( $tmp, $bytes, LOCK_EX ) || ! chmod( $tmp, 0600 ) || ! rename( $tmp, self::path( $service, $job['id'] ) ) ) {
			throw new \RuntimeException( 'Backup checkpoint write failed.' ); }
	}

	/** Load authenticated state; the caller must still authorize the job owner.
	 *
	 * @param DocumentService $service Services.
	 * @param string          $id ID.
	 * @return array State.
	 * @throws \RuntimeException On invalid state.
	 */
	public static function load( DocumentService $service, $id ) {
		$path = self::path( $service, $id );
		if ( ! is_file( $path ) || filesize( $path ) > 16777300 ) {
			throw new \RuntimeException( 'Backup job unavailable.' ); }
		$bytes = file_get_contents( $path );
		if ( ! is_string( $bytes ) || strlen( $bytes ) < 40 ) {
			throw new \RuntimeException( 'Backup checkpoint invalid.' ); }
		$plain = sodium_crypto_secretbox_open( substr( $bytes, 24 ), substr( $bytes, 0, 24 ), self::wrapping_key( $service ) );
		$job   = false === $plain ? null : json_decode( $plain, true, 256 );
		if ( ! is_array( $job ) || ( $job['id'] ?? null ) !== $id ) {
			throw new \RuntimeException( 'Backup checkpoint invalid.' ); }
		return $job;
	}

	/** Serialize trusted job work with every ordinary FileWarden writer.
	 *
	 * @param callable $action Authorized work.
	 * @return mixed Result.
	 */
	public static function exclusive( callable $action ) {
		$previous       = self::$internal;
		self::$internal = true;
		$repo           = new Repository();
		$lock           = null;
		try {
			$lock = $repo->lock( 'backup-job' );
			return FileIO::attempt( $action );
		} finally {
			if ( $lock ) {
				$repo->unlock( $lock ); }
			self::$internal = $previous;
		}
	}

	/** Freeze mutations durably before releasing the request's connection lock.
	 *
	 * @param array $job Owner, expiry and type.
	 * @throws \RuntimeException If another job already owns the gate.
	 */
	public static function freeze( array $job ) {
		if ( ! add_option(
			self::BARRIER,
			wp_json_encode(
				array(
					'id'      => $job['id'],
					'expires' => $job['expires'],
				)
			),
			'',
			false
		) ) {
			throw new \RuntimeException( 'Another library backup is active.' ); }
	}

	/** Check a job is still the only snapshot owner.
	 *
	 * @param array $job State.
	 * @throws \RuntimeException If its lease was released or expired.
	 */
	public static function require_frozen( array $job ) {
		$value = json_decode( get_option( self::BARRIER, '{}' ), true );
		if ( ( $value['id'] ?? '' ) !== $job['id'] || $job['expires'] < time() ) {
			throw new \RuntimeException( 'Backup job expired.' ); }
	}

	/** Release only the barrier owned by this exact job.
	 *
	 * @param array $job State.
	 */
	public static function release( array $job ) {
		$value = json_decode( get_option( self::BARRIER, '{}' ), true );
		if ( ( $value['id'] ?? '' ) === $job['id'] ) {
			delete_option( self::BARRIER ); }
	}

	/** Verify owner and dedicated capability afresh on every operation/download.
	 *
	 * @param array $job State.
	 * @throws \DomainException If unauthorized.
	 */
	public static function authorize( array $job ) {
		if ( get_current_user_id() !== (int) $job['owner'] || ! current_user_can( 'filewarden_manage_settings' ) || ! current_user_can( 'backup' === $job['type'] ? 'filewarden_export_library' : 'filewarden_restore_library' ) ) {
			throw new \DomainException( 'Backup access denied.' ); }
	}

	/** Cleanup recorded staging only; committed records keep their live bytes.
	 *
	 * @param DocumentService $service Services.
	 * @param array           $job State.
	 * @param bool            $forget Delete the state too.
	 * @throws \RuntimeException If the operation cannot complete safely.
	 */
	public static function cleanup( DocumentService $service, array $job, $forget = true ) {
		$committed = 'restored' === $job['status'] || get_option( self::APPLIED ) === $job['id'];
		if ( $committed && isset( $job['staged']['logo'] ) ) {
			$service->storage->remove( $job['staged']['logo'] ); }
		if ( 'restore' === $job['type'] && ! $committed ) {
			$keys = array_values( $job['staged'] ?? array() );
			if ( ! empty( $job['pending'] ) ) {
				$keys[] = $job['pending']; }
			foreach ( array_unique( $keys ) as $key ) {
				$service->storage->remove( $key ); }
			if ( ! empty( $job['logo_path'] ) ) {
				$uploads = wp_upload_dir();
				if ( ! StorageService::within( $job['logo_path'], $uploads['basedir'] ) || is_link( $job['logo_path'] ) ) {
					throw new \RuntimeException( 'Invalid staged logo.' ); }
				if ( is_file( $job['logo_path'] ) && ! unlink( $job['logo_path'] ) ) {
					throw new \RuntimeException( 'Logo cleanup failed.' ); }
			}
		}
		foreach ( $forget ? array( 'fwbackup', 'tmp', 'state' ) : array( 'fwbackup', 'tmp' ) as $suffix ) {
			$path = self::path( $service, $job['id'], $suffix );
			if ( is_file( $path ) && ! unlink( $path ) ) {
				throw new \RuntimeException( 'Backup cleanup failed.' ); }
		}
		self::release( $job );
	}
}
