<?php
/** Chunked portable backups with encrypted checkpoints and recoverable activation.
 *
 * @package FileWarden
 */

namespace FileWarden;

// phpcs:disable WordPress.WP.AlternativeFunctions -- Verified private binary files; no plaintext backup staging or archive extraction.
// phpcs:disable WordPress.DB.DirectDatabaseQuery -- Prepared custom-table writes under the exclusive barrier and a transaction.
/** Export, authenticate, map, stage, then activate an empty library. */
final class BackupService {
	/** Start an owner-bound job; never persist the supplied phrase.
	 *
	 * @param DocumentService $service Services.
	 * @param string          $type Backup or restore.
	 * @param string          $phrase Phrase.
	 * @param string          $upload PHP-verified encrypted upload path for restore.
	 * @return array Public status.
	 * @param array|null      $components Optional export flags.
	 */
	public static function start( DocumentService $service, $type, $phrase, $upload = '', $components = null ) {
		self::capability( $service, $type );
		return BackupJobs::exclusive(
			static function () use ( $service, $type, $phrase, $upload, $components ) {
				$service->repository->require_transactions();
				self::expire( $service );
				$job = array(
					'id'         => bin2hex( random_bytes( 16 ) ),
					'owner'      => get_current_user_id(),
					'type'       => $type,
					'expires'    => time() + BackupJobs::TTL,
					'status'     => 'working',
					'index'      => 0,
					'done_bytes' => 0,
					'staged'     => array(),
					'pending'    => null,
					'files'      => array(),
				);
				try {
					BackupJobs::save( $service, $job );
					BackupJobs::freeze( $job );
					$service->audit->record( 'backup' === $type ? 'backup_requested' : 'backup_analysis_requested' );
					$path = BackupJobs::path( $service, $job['id'], 'fwbackup' );
					if ( 'backup' === $type ) {
						list( $job['manifest'], $job['sources'] ) = BackupManifest::capture( $components );
						$job['files']                             = self::files( $job['manifest'] );
						self::space( $service, array_sum( array_column( $job['files'], 'size' ) ) * 1.05 + BackupManifest::MAX_JSON );
						$salt   = random_bytes( 16 );
						$key    = BackupArchive::key( $phrase, $salt );
						$output = fopen( $path, 'x+b' );
						if ( ! $output || ! chmod( $path, 0600 ) ) {
							throw new \RuntimeException( 'Backup write unavailable.' ); }
						try {
							$job['cursor'] = BackupArchive::create( $output, $key, $salt );
							sodium_memzero( $key );
							$json        = wp_json_encode( $job['manifest'] );
							$json_length = strlen( $json );
							for ( $offset = 0; $offset < $json_length; $offset += BackupArchive::CHUNK ) {
								BackupArchive::push( $output, $job['cursor'], 'M', substr( $json, $offset, BackupArchive::CHUNK ) ); }
							BackupArchive::push( $output, $job['cursor'], 'N' );
							self::checkpoint( $service, $job, $output );
						} finally {
							fclose( $output ); }
					} else {
						BackupRestore::require_transactions();
						if ( ! is_file( $upload ) || filesize( $upload ) < 56 || filesize( $upload ) > wp_max_upload_size() ) {
							throw new \RuntimeException( 'Backup upload limit exceeded.' ); }
						self::space( $service, filesize( $upload ) * 2 + BackupManifest::MAX_JSON );
						if ( ! copy( $upload, $path ) || ! chmod( $path, 0600 ) ) {
							throw new \RuntimeException( 'Private backup upload failed.' ); }
						$input = fopen( $path, 'rb' );
						if ( ! $input ) {
							throw new \RuntimeException( 'Backup read failed.' ); }
						try {
							$job['cursor'] = BackupArchive::open( $input, $phrase );
							$json          = '';
							do {
								list( $kind, $bytes ) = BackupArchive::pull( $input, $job['cursor'] );
								if ( 'M' === $kind && '' !== $bytes ) {
									$json .= $bytes; } elseif ( 'N' !== $kind || '' !== $bytes ) {
									throw new \RuntimeException( 'Backup manifest frame invalid.' ); }
									if ( strlen( $json ) > BackupManifest::MAX_JSON ) {
										throw new \RuntimeException( 'Backup manifest limit exceeded.' ); }
							} while ( 'M' === $kind );
							$job['manifest'] = json_decode( $json, true, 256, JSON_THROW_ON_ERROR );
							BackupManifest::validate( $job['manifest'] );
							if ( ! BackupComponents::configuration_only( $job['manifest'] ) ) {
								self::empty_library();
							}
							$job['files'] = self::files( $job['manifest'] );
							self::space( $service, array_sum( array_column( $job['files'], 'size' ) ) * 1.05 + BackupManifest::MAX_JSON );
							$job['offset'] = ftell( $input );
							BackupJobs::save( $service, $job );
						} finally {
							fclose( $input ); }
					}
				} catch ( \Throwable $error ) {
					BackupJobs::cleanup( $service, $job );
					$service->audit->record( 'backup_failed' );
					throw $error;
				} finally {
					sodium_memzero( $phrase ); }
				return self::status( $job );
			}
		);
	}

	/** One resumable unit: one bounded file, or final stream authentication.
	 *
	 * @param DocumentService $service Services.
	 * @param string          $id Job ID.
	 * @return array Public progress.
	 */
	public static function tick( DocumentService $service, $id ) {
		return BackupJobs::exclusive(
			static function () use ( $service, $id ) {
				$job = BackupJobs::load( $service, $id );
				BackupJobs::authorize( $job );
				if ( $job['expires'] < time() ) {
					BackupJobs::cleanup( $service, $job );
					throw new \RuntimeException( 'Backup job expired.' ); }
				if ( 'restore' === $job['type'] && 'activating' === $job['status'] ) {
					$job = BackupRestore::recover( $service, $job ); }
				if ( 'working' !== $job['status'] ) {
					return self::status( $job ); }
				BackupJobs::require_frozen( $job );
				$stream = fopen( BackupJobs::path( $service, $id, 'fwbackup' ), 'backup' === $job['type'] ? 'r+b' : 'rb' );
				if ( ! $stream || 0 !== fseek( $stream, $job['offset'] ) ) {
					throw new \RuntimeException( 'Backup checkpoint cannot be resumed.' ); }
				try {
					$stat = fstat( $stream );
					if ( ! $stat || $stat['size'] < $job['offset'] ) {
						throw new \RuntimeException( 'Backup checkpoint is truncated.' ); }
					if ( 'backup' === $job['type'] && ! ftruncate( $stream, $job['offset'] ) ) {
						throw new \RuntimeException( 'Backup resume failed.' ); }
					if ( ! empty( $job['pending'] ) ) {
						$service->storage->remove( $job['pending'] );
						$job['pending'] = null; }
					if ( $job['index'] < count( $job['files'] ) ) {
						$file = $job['files'][ $job['index'] ];
						if ( 'backup' === $job['type'] ) {
							self::export_file( $service, $job, $file, $stream ); } else {
							self::stage_file( $service, $job, $file, $stream ); }
							++$job['index'];
							$job['done_bytes'] += (int) $file['size'];
							self::checkpoint( $service, $job, $stream );
					} else {
						if ( 'backup' === $job['type'] ) {
							BackupArchive::push( $stream, $job['cursor'], 'Z' ); } elseif ( array( 'Z', '' ) !== BackupArchive::pull( $stream, $job['cursor'] ) ) {
							throw new \RuntimeException( 'Missing final authentication.' ); }
							$service->audit->record( 'backup' === $job['type'] ? 'backup_created' : 'backup_analysis_completed' );
							$job['status'] = 'backup' === $job['type'] ? 'ready' : 'review';
							self::checkpoint( $service, $job, $stream );
							if ( 'backup' === $job['type'] ) {
								BackupJobs::release( $job ); }
					}
				} catch ( \Throwable $error ) {
					fclose( $stream );
					BackupJobs::cleanup( $service, BackupJobs::load( $service, $id ) );
					$service->audit->record( 'backup_failed' );
					throw $error;
				}
				fclose( $stream );
				return self::status( $job );
			}
		);
	}

	/** Canonical file order is part of the authenticated logical schema.
	 *
	 * @param array $manifest Manifest.
	 * @return array File entries.
	 */
	private static function files( array $manifest ) {
		$files = array();
		foreach ( $manifest['documents'] as $row ) {
			$files[] = array(
				'uuid'   => $row['uuid'],
				'size'   => (int) $row['size'],
				'sha256' => $row['sha256'],
				'mime'   => $row['mime'],
			); }
		if ( $manifest['logo'] ) {
			$files[] = array_merge( array( 'uuid' => 'logo' ), $manifest['logo'] ); }
		return $files;
	}

	/** Export/decrypt directly into backup encryption; no plaintext disk copy.
	 *
	 * @param DocumentService $service Services.
	 * @param array           $job State.
	 * @param array           $file File.
	 * @param resource        $output Container stream.
	 * @throws \RuntimeException If the operation cannot complete safely.
	 */
	private static function export_file( DocumentService $service, array &$job, array $file, $output ) {
		BackupArchive::push( $output, $job['cursor'], 'F', $file['uuid'] );
		$hash    = hash_init( 'sha256' );
		$consume = static function ( $bytes ) use ( &$job, $output, $hash ) {
			hash_update( $hash, $bytes );
			BackupArchive::push( $output, $job['cursor'], 'D', $bytes );
		};
		if ( 'logo' === $file['uuid'] ) {
			$input = fopen( $job['sources']['logo'], 'rb' );
			if ( ! $input ) {
				throw new \RuntimeException( 'Logo unavailable.' ); }
			try {
				for ( $remaining = (int) $file['size']; $remaining > 0; $remaining -= strlen( $bytes ) ) {
					$bytes = BackupArchive::read( $input, min( BackupArchive::CHUNK, $remaining ) );
					$consume( $bytes ); }
			} finally {
				fclose( $input ); }
		} else {
			$service->storage->visit_chunks( $job['sources'][ $file['uuid'] ], (int) $file['size'], $consume ); }
		if ( ! hash_equals( $file['sha256'], hash_final( $hash ) ) ) {
			throw new \RuntimeException( 'Source integrity failed.' ); }
		BackupArchive::push( $output, $job['cursor'], 'E' );
	}

	/** Authenticate a full file into a new destination-private storage object.
	 *
	 * @param DocumentService $service Services.
	 * @param array           $job State.
	 * @param array           $file File metadata.
	 * @param resource        $input Input container.
	 * @throws \RuntimeException If the operation cannot complete safely.
	 */
	private static function stage_file( DocumentService $service, array &$job, array $file, $input ) {
		if ( array( 'F', $file['uuid'] ) !== BackupArchive::pull( $input, $job['cursor'] ) ) {
			throw new \RuntimeException( 'Backup file order invalid.' ); }
		$job['pending'] = bin2hex( random_bytes( 32 ) );
		// Persist the planned key BEFORE creating bytes; the input cursor remains at the last committed file.
		$checkpoint            = BackupJobs::load( $service, $job['id'] );
		$checkpoint['pending'] = $job['pending'];
		BackupJobs::save( $service, $checkpoint );
		$hash = hash_init( 'sha256' );
		$service->storage->receive_chunks(
			$job['pending'],
			(int) $file['size'],
			static function ( $consume ) use ( &$job, $input, $file, $hash ) {
				for ( $remaining = (int) $file['size']; $remaining > 0; $remaining -= strlen( $bytes ) ) {
					list( $kind, $bytes ) = BackupArchive::pull( $input, $job['cursor'] );
					if ( 'D' !== $kind || strlen( $bytes ) !== min( BackupArchive::CHUNK, $remaining ) ) {
						throw new \RuntimeException( 'Backup file length invalid.' ); }
					if ( $remaining === (int) $file['size'] ) {
						$mime = ( new \finfo( FILEINFO_MIME_TYPE ) )->buffer( $bytes );
						if ( in_array( $file['mime'], array( 'application/pdf', 'image/png', 'image/jpeg' ), true ) && ( $file['mime'] !== $mime || ( 'application/pdf' === $mime && ! str_starts_with( $bytes, '%PDF-' ) ) ) ) {
							throw new \RuntimeException( 'Backup preview type invalid.' ); }
					}
					hash_update( $hash, $bytes );
					$consume( $bytes );
				}
				if ( array( 'E', '' ) !== BackupArchive::pull( $input, $job['cursor'] ) || ! hash_equals( $file['sha256'], hash_final( $hash ) ) ) {
					throw new \RuntimeException( 'Backup file integrity failed.' ); }
			}
		);
		$job['staged'][ $file['uuid'] ] = $job['pending'];
		$job['pending']                 = null;
	}

	/** Flush before acknowledging persisted progress.
	 *
	 * @param DocumentService $service Services.
	 * @param array           $job State.
	 * @param resource        $stream Archive stream.
	 * @throws \RuntimeException If the operation cannot complete safely.
	 */
	private static function checkpoint( DocumentService $service, array &$job, $stream ) {
		if ( ! fflush( $stream ) ) {
			throw new \RuntimeException( 'Backup flush failed.' ); }
		$job['offset'] = ftell( $stream );
		BackupJobs::save( $service, $job );
	}

	/** Require the dedicated capability, not just content read or settings access.
	 *
	 * @param DocumentService $service Services.
	 * @param string          $type Job type.
	 * @throws \InvalidArgumentException If the operation cannot complete safely.
	 */
	public static function capability( DocumentService $service, $type ) {
		if ( ! in_array( $type, array( 'backup', 'restore' ), true ) ) {
			throw new \InvalidArgumentException( 'Invalid backup type.' ); }
		$service->require_capability( 'filewarden_manage_settings' );
		$service->require_capability( 'backup' === $type ? 'filewarden_export_library' : 'filewarden_restore_library' );
		CryptoSupport::stream();
	}

	/** Reject merge/replacement, including pending physical purges.
	 *
	 * @throws \RuntimeException If not empty or unreadable.
	 */
	public static function empty_library() {
		global $wpdb;
		foreach ( array( 'folders', 'documents' ) as $kind ) {
			$count = $wpdb->get_var( $wpdb->prepare( 'SELECT COUNT(*) FROM %i', $wpdb->prefix . 'filewarden_' . $kind ) );
			if ( $wpdb->last_error || '0' !== (string) $count ) {
				throw new \RuntimeException( 'Restoration requires an empty FileWarden library.' ); }
		}
	}

	/** Preflight disk capacity without treating unknown capacity as sufficient.
	 *
	 * @param DocumentService $service Services.
	 * @param float           $required Required bytes including authenticated framing.
	 * @throws \RuntimeException If the operation cannot complete safely.
	 */
	private static function space( DocumentService $service, $required ) {
		$free = disk_free_space( $service->storage->root() );
		if ( false === $free ) {
			throw new \RuntimeException( 'Backup free space unavailable.' );
		}
		if ( $free < $required + 16777216 ) {
			throw new \RuntimeException( 'Insufficient backup storage.' ); }
	}

	/** Public status omits keys, paths, policies and source content.
	 *
	 * @param array $job State.
	 * @return array Progress and mapping requirements only after full authentication.
	 */
	public static function status( array $job ) {
		$result = array(
			'id'        => $job['id'],
			'type'      => $job['type'],
			'status'    => $job['status'],
			'completed' => $job['index'],
			'total'     => count( $job['files'] ),
			'bytes'     => $job['done_bytes'],
			'expires'   => $job['expires'],
		);
		if ( 'review' === $job['status'] ) {
			$result['configuration_only'] = BackupComponents::configuration_only( $job['manifest'] );
			$result['components']         = BackupComponents::included( $job['manifest'] );
			$result['roles']              = $result['components']['permissions'] ? BackupManifest::validate( $job['manifest'] ) : array();
			$content                      = $job['manifest'];
			$content['audit_logs']        = array();
			$result['content_roles']      = $result['components']['permissions'] ? BackupManifest::validate( $content ) : array();
			$result['users']              = $job['manifest']['users'];
			$result['summary']            = array(
				'folders'   => count( $job['manifest']['folders'] ),
				'documents' => count( $job['manifest']['documents'] ),
				'trash'     => count( array_filter( $job['manifest']['documents'], static fn( $row ) => ! empty( $row['trashed_at'] ) ) ),
				'audit'     => count( $job['manifest']['audit_logs'] ),
				'logo'      => null !== $job['manifest']['logo'],
			);
		}
		return $result;
	}

	/** Clean expired jobs, including interrupted staging, under the writer gate.
	 *
	 * @param DocumentService $service Services.
	 */
	public static function expire( DocumentService $service ) {
		$probe = BackupJobs::path( $service, str_repeat( '0', 32 ) );
		foreach ( array_slice( glob( dirname( $probe ) . '/*.state' ), 0, 100 ) as $path ) {
			$id  = basename( $path, '.state' );
			$job = BackupJobs::load( $service, $id );
			if ( $job['expires'] < time() ) {
				BackupJobs::cleanup( $service, $job ); }
		}
	}
}
