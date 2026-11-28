<?php
/** Explicit cross-site identity mappings and recoverable empty-library activation.
 *
 * @package FileWarden
 */

namespace FileWarden;

// phpcs:disable WordPress.DB.DirectDatabaseQuery -- Fixed allowlisted tables and values, held under the common writer gate and InnoDB transaction.
// phpcs:disable WordPress.WP.AlternativeFunctions -- Only the explicitly public, validated logo is written outside private staging.
/** The commit marker lives in the same transaction as the restored metadata. */
final class BackupRestore {
	/** Activate only an authenticated complete package with explicit mappings.
	 *
	 * @param DocumentService $service Services.
	 * @param string          $id Job ID.
	 * @param array           $roles Source role to existing destination role.
	 * @param array           $users Source creator to existing destination user.
	 * @return array Public completion.
	 * @param array|null      $choices Optional restore choices.
	 */
	public static function apply( DocumentService $service, $id, array $roles, array $users, $choices = null ) {
		global $wpdb;
		return BackupJobs::exclusive(
			static function () use ( $service, $id, $roles, $users, $wpdb, $choices ) {
				$job = BackupJobs::load( $service, $id );
				BackupJobs::authorize( $job );
				if ( 'restore' !== $job['type'] ) {
					throw new \DomainException( 'Invalid restoration job.' ); }
				if ( get_option( BackupJobs::APPLIED ) === $id ) {
					$job['status'] = 'restored';
					BackupJobs::save( $service, $job );
					BackupJobs::cleanup( $service, $job, false );
					return BackupService::status( $job );
				}
				BackupJobs::require_frozen( $job );
				if ( 'review' !== $job['status'] && 'activating' !== $job['status'] ) {
					throw new \RuntimeException( 'Full backup authentication required.' ); }
				if ( ! BackupComponents::configuration_only( $job['manifest'] ) ) {
					BackupService::empty_library();
				}
				self::require_transactions();
				$manifest = $job['manifest'];
				$required = BackupManifest::validate( $manifest );
				$choices  = BackupComponents::restoration( $manifest, $choices );
				if ( BackupComponents::configuration_only( $manifest ) && ! $choices['settings'] && ! $choices['appearance'] ) {
					throw new \InvalidArgumentException( 'Invalid restoration choices.' );
				}
				if ( ! $choices['audit'] ) {
					$content               = $manifest;
					$content['audit_logs'] = array();
					$required              = BackupManifest::validate( $content ); }
				$policy = static fn( $source ) => $choices['permissions'] ? self::policy( $source, $roles ) : BackupComponents::private_policy();
				if ( ! $choices['permissions'] ) {
					$required = array(); }
				$known = array_keys( wp_roles()->roles );
				foreach ( $required as $role ) {
					if ( ! is_string( $roles[ $role ] ?? null ) || ! in_array( $roles[ $role ], $known, true ) ) {
						throw new \InvalidArgumentException( 'Map every source role explicitly.' ); }
				}
				if ( 'current' === $choices['creators'] ) {
					$users = array_fill_keys( array_keys( $manifest['users'] ), get_current_user_id() ); }
				foreach ( $manifest['users'] as $source => $login ) {
					$target = RestController::integer( $users[ $source ] ?? null );
					if ( ! $target || ! get_userdata( $target ) ) {
						throw new \InvalidArgumentException( 'Map every source creator explicitly.' ); }
				}
				if ( count( $job['staged'] ) !== count( $job['files'] ) ) {
					throw new \RuntimeException( 'Backup staging incomplete.' ); }
				// Recheck every staged object's authenticated bytes immediately before activation.
				foreach ( $job['files'] as $file ) {
					$hash = hash_init( 'sha256' );
					$service->storage->visit_chunks( $job['staged'][ $file['uuid'] ], (int) $file['size'], static fn( $bytes ) => hash_update( $hash, $bytes ) );
					if ( ! hash_equals( $file['sha256'], hash_final( $hash ) ) ) {
						throw new \RuntimeException( 'Staged file integrity failed.' ); }
				}
				$appearance = $choices['appearance'] ? FrontendAppearance::validate( $manifest['settings']['appearance'] ) : FrontendAppearance::current();
				$logo_bytes = '';
				if ( $choices['appearance'] && $manifest['logo'] ) {
					$service->require_capability( 'upload_files' );
					$service->storage->visit_chunks(
						$job['staged']['logo'],
						(int) $manifest['logo']['size'],
						static function ( $bytes ) use ( &$logo_bytes ) {
							$logo_bytes .= $bytes;
						}
					);
					$image = getimagesizefromstring( $logo_bytes );
					if ( ! $image || $image[0] > 6000 || $image[1] > 6000 || $image['mime'] !== $manifest['logo']['mime'] || ( new \finfo( FILEINFO_MIME_TYPE ) )->buffer( $logo_bytes ) !== $image['mime'] ) {
						throw new \RuntimeException( 'Restored logo invalid.' ); }
					$uploads = wp_upload_dir();
					if ( $uploads['error'] || ! is_dir( $uploads['path'] ) || is_link( $uploads['path'] ) ) {
						throw new \RuntimeException( 'Logo upload unavailable.' ); }
					$job['logo_path'] = $uploads['path'] . '/filewarden-restored-' . $id . ( 'image/png' === $image['mime'] ? '.png' : '.jpg' );
				}
				$job['status']          = 'activating';
				$job['restore_choices'] = $choices;
				BackupJobs::save( $service, $job );
				if ( false === $wpdb->query( 'START TRANSACTION' ) ) {
					throw new \RuntimeException( 'Restoration transaction unavailable.' ); }
				$options = array();
				try {
					$service->audit->record( 'backup_restore_requested' );
					$source_uuids = array(
						'folders'   => array_column( $manifest['folders'], 'uuid', 'id' ),
						'documents' => array_column( $manifest['documents'], 'uuid', 'id' ),
					);
					foreach ( array( 'folders', 'documents', 'audit_logs' ) as $kind ) {
						if ( 'audit_logs' === $kind && ! $choices['audit'] ) {
							continue; }
						foreach ( $manifest[ $kind ] as $row ) {
							if ( 'audit_logs' === $kind ) {
								unset( $row['id'] );
								$row['actor_ip']      = AuditIp::visible( $row );
								$row['ip_expires_at'] = $row['actor_ip'] ? $row['ip_expires_at'] : null;
								$row['actor_type']    = $row['actor_id'] ? 'imported' : ( in_array( $row['actor_type'], array( 'system', 'visitor', 'unknown' ), true ) ? 'source_' . $row['actor_type'] : ( str_starts_with( $row['actor_type'], 'source_' ) ? $row['actor_type'] : 'imported' ) );
								if ( ! empty( $row['resource_rules'] ) ) {
									$rules             = json_decode( $row['resource_rules'], true );
									$rules['policies'] = $choices['permissions'] ? array_map( $policy, $rules['policies'] ) : array( BackupComponents::private_policy() );
									$source_kind       = 'folder' === $row['resource_type'] ? 'folders' : ( 'document' === $row['resource_type'] ? 'documents' : '' );
									$source_uuid       = $source_uuids[ $source_kind ][ $row['object_id'] ] ?? null;
									if ( $source_uuid ) {
										$rules['source_uuid'] = $source_uuid; }
									$row['resource_rules'] = wp_json_encode( $rules );
								}
							} else {
								$row['created_by'] = (int) $users[ $row['created_by'] ];
								$row['policy']     = wp_json_encode( $policy( json_decode( $row['policy'], true ) ) );
								if ( 'documents' === $kind ) {
									$row['storage_key'] = $job['staged'][ $row['uuid'] ];
									$row['purging']     = 0;
									if ( ! $choices['permissions'] ) {
										$row['retained_rules'] = null; } elseif ( ! empty( $row['retained_rules'] ) ) {
										$row['retained_rules'] = wp_json_encode( array_map( $policy, json_decode( $row['retained_rules'], true ) ) ); }
								}
							}
							if ( false === $wpdb->insert( $wpdb->prefix . 'filewarden_' . $kind, $row ) ) {
								throw new \RuntimeException( 'Restoration metadata write failed.' ); }
						}
					}
					if ( '' !== $logo_bytes ) {
						$output = fopen( $job['logo_path'], 'x+b' );
						if ( ! $output ) {
							throw new \RuntimeException( 'Logo write failed.' ); }
						try {
							BackupArchive::write( $output, $logo_bytes );
							if ( ! fflush( $output ) ) {
												throw new \RuntimeException( 'Logo write failed.' );
							}
						} finally {
										fclose( $output ); }
						$logo_id = wp_insert_attachment(
							array(
								'post_mime_type' => $manifest['logo']['mime'],
								'post_title'     => 'FileWarden',
								'post_status'    => 'inherit',
							),
							$job['logo_path'],
							0,
							true
						);
						if ( is_wp_error( $logo_id ) ) {
							throw new \RuntimeException( 'Logo registration failed.' ); }
						$appearance['logo_id'] = $logo_id;
					}
					$options = array(
						'filewarden_root_uuid' => BackupComponents::configuration_only( $manifest ) ? get_option( 'filewarden_root_uuid' ) : $manifest['root_uuid'],
						BackupJobs::APPLIED    => $id,
					);
					if ( $choices['appearance'] ) {
						$options[ FrontendAppearance::OPTION ] = $appearance; }
					if ( $choices['settings'] ) {
						$options += array(
							PublicationLogin::OPTION      => array( 'mode' => 'wordpress' ),
							'filewarden_allow_all_types'  => false,
							'filewarden_audit_collect_ip' => '0',
							'filewarden_audit_ip_days'    => $manifest['settings']['audit_ip_days'] ?? 30,
						);
						foreach ( array( 'upload_mb', 'trash_days', 'audit_days', 'language', 'notification_seconds' ) as $key ) {
							$options[ 'filewarden_' . $key ] = $manifest['settings'][ $key ]; }
					}
					foreach ( $options as $key => $value ) {
						if ( false === $wpdb->query( $wpdb->prepare( 'INSERT INTO %i (option_name, option_value, autoload) VALUES (%s, %s, %s) ON DUPLICATE KEY UPDATE option_value = VALUES(option_value), autoload = VALUES(autoload)', $wpdb->options, $key, maybe_serialize( $value ), 'off' ) ) ) {
							throw new \RuntimeException( 'Restoration settings write failed.' ); }
					}
					$service->audit->record( 'backup_restored' );
					if ( false === $wpdb->query( 'COMMIT' ) ) {
						throw new \RuntimeException( 'Restoration commit failed.' ); }
				} catch ( \Throwable $error ) {
					$rolled_back = $wpdb->query( 'ROLLBACK' );
					self::clear_options( array_keys( $options ) );
					$marker = $wpdb->get_var( $wpdb->prepare( 'SELECT option_value FROM %i WHERE option_name = %s', $wpdb->options, BackupJobs::APPLIED ) );
					if ( $wpdb->last_error || false === $rolled_back ) {
						// An uncertain commit stays frozen. A later request resolves the transactional marker.
						throw new \RuntimeException( 'Restoration outcome requires recovery.' );
					}
					if ( $marker !== $id ) {
						if ( ! empty( $job['logo_path'] ) && is_file( $job['logo_path'] ) ) {
							unlink( $job['logo_path'] ); }
						$job['status'] = 'review';
						BackupJobs::save( $service, $job );
						$service->audit->record( 'backup_restore_failed' );
						throw $error;
					}
					// A lost COMMIT acknowledgement cannot turn committed live files into staging cleanup.
				}
				self::clear_options( array_keys( $options ) );
				// Once COMMIT is acknowledged, cleanup must never remove live document keys.
				$job['status'] = 'restored';
				BackupJobs::save( $service, $job );
				if ( isset( $job['staged']['logo'] ) ) {
					$service->storage->remove( $job['staged']['logo'] ); }
				BackupJobs::cleanup( $service, $job, false );
				return BackupService::status( $job );
			}
		);
	}

	/** Recover an interrupted activation using its transactional marker.
	 *
	 * @param DocumentService $service Services.
	 * @param array           $job Authenticated private checkpoint.
	 * @return array Recovered checkpoint.
	 * @throws \RuntimeException If the outcome cannot safely be established.
	 */
	public static function recover( DocumentService $service, array $job ) {
		global $wpdb;
		BackupJobs::authorize( $job );
		$marker = $wpdb->get_var( $wpdb->prepare( 'SELECT option_value FROM %i WHERE option_name = %s', $wpdb->options, BackupJobs::APPLIED ) );
		if ( $wpdb->last_error ) {
			throw new \RuntimeException( 'Restoration outcome requires recovery.' ); }
		if ( $marker === $job['id'] ) {
			$job['status'] = 'restored';
			BackupJobs::save( $service, $job );
			BackupJobs::cleanup( $service, $job, false );
			return $job;
		}
		BackupJobs::require_frozen( $job );
		if ( ! BackupComponents::configuration_only( $job['manifest'] ) ) {
			BackupService::empty_library();
		}
		if ( ! empty( $job['logo_path'] ) ) {
			$uploads = wp_upload_dir();
			if ( ! StorageService::within( $job['logo_path'], $uploads['basedir'] ) || is_link( $job['logo_path'] ) ) {
				throw new \RuntimeException( 'Invalid staged logo.' ); }
			if ( is_file( $job['logo_path'] ) && ! unlink( $job['logo_path'] ) ) {
				throw new \RuntimeException( 'Logo cleanup failed.' ); }
			unset( $job['logo_path'] );
		}
		$job['status'] = 'review';
		BackupJobs::save( $service, $job );
		return $job;
	}

	/** Require transactional core metadata too, without converting WordPress tables.
	 *
	 * @throws \RuntimeException If atomic option/logo activation cannot be established.
	 */
	public static function require_transactions() {
		global $wpdb;
		Repository::require_transactions();
		$rows     = $wpdb->get_results( $wpdb->prepare( 'SHOW TABLE STATUS WHERE Name IN (%s, %s, %s)', $wpdb->options, $wpdb->posts, $wpdb->postmeta ), ARRAY_A );
		$names    = array_column( $rows ?? array(), 'Name' );
		$expected = array( $wpdb->options, $wpdb->posts, $wpdb->postmeta );
		sort( $names );
		sort( $expected );
		if ( $wpdb->last_error || $names !== $expected || array_filter( $rows, static fn( $row ) => 'innodb' !== strtolower( (string) $row['Engine'] ) ) ) {
			throw new \RuntimeException( 'Restoration requires transactional WordPress metadata tables.' );
		}
	}

	/** Remap role references without granting roles or broadening to public.
	 *
	 * @param array $policy Validated source policy.
	 * @param array $mapping Explicit destination mapping.
	 * @return array Destination policy.
	 */
	private static function policy( array $policy, array $mapping ) {
		$policy['roles'] = array_map( static fn( $role ) => $mapping[ $role ], $policy['roles'] ?? array() );
		return Policy::validate( $policy, array_keys( wp_roles()->roles ), true );
	}

	/** Invalidate option caches after direct transactional writes or rollback.
	 *
	 * @param array $keys Option names.
	 */
	private static function clear_options( array $keys ) {
		foreach ( $keys as $key ) {
			wp_cache_delete( $key, 'options' ); }
		wp_cache_delete( 'alloptions', 'options' );
		wp_cache_delete( 'notoptions', 'options' );
	}
}
