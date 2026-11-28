<?php
/** A bounded logical export schema; source keys, paths and SQL never cross sites.
 *
 * @package FileWarden
 */

namespace FileWarden;

// phpcs:disable WordPress.DB.DirectDatabaseQuery -- Explicit prepared queries of the three plugin tables under the common snapshot gate.
/** Validate the complete dependency graph before restoration becomes available. */
final class BackupManifest {
	const MAX_JSON = 4194304;
	const MAX_FILE = 268435456;
	const MAX_ROWS = 10000;

	/** Capture a consistent logical snapshot while the write gate is held.
	 *
	 * @return array Manifest and private source storage references.
	 * @throws \RuntimeException On incomplete snapshots or configured limits.
	 * @param array|null $components Optional export flags.
	 */
	public static function capture( $components = null ) {
		global $wpdb;
		$components = BackupComponents::options( $components );
		$manifest   = array(
			'version'    => 1,
			'schema'     => (string) get_option( 'filewarden_schema' ),
			'root_uuid'  => get_option( 'filewarden_root_uuid' ),
			'folders'    => array(),
			'documents'  => array(),
			'audit_logs' => array(),
			'users'      => array(),
			'settings'   => array(),
			'logo'       => null,
		);
		$keys       = array();
		foreach ( array( 'folders', 'documents', 'audit_logs' ) as $kind ) {
			if ( ( 'audit_logs' === $kind && ! $components['audit'] ) || ( 'folders' === $kind && ! $components['folders'] ) || ( 'documents' === $kind && ! $components['documents'] && ! $components['trash'] ) ) {
				continue; }
			$where   = 'documents' === $kind ? ( ! $components['trash'] ? ' WHERE trashed_at IS NULL' : ( ! $components['documents'] ? ' WHERE trashed_at IS NOT NULL' : '' ) ) : '';
			$columns = match ( $kind ) {
				'folders' => 'name,policy', 'documents' => 'name,description,policy,retained_rules,trash_origin,mime', default => 'actor_login,event,actor_type,resource_type,resource_name,resource_rules' };
			if ( '9' !== get_option( 'filewarden_schema' ) ) {
				$columns = str_replace( 'description,', '', $columns );
			}
			// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Column lists are fixed literals, never archive or request data.
			$budget = $wpdb->get_var( $wpdb->prepare( "SELECT COALESCE(SUM(LENGTH(CONCAT_WS('', $columns))),0) FROM %i$where", $wpdb->prefix . 'filewarden_' . $kind ) );
			if ( $wpdb->last_error || (int) $budget > self::MAX_JSON ) {
				throw new \RuntimeException( 'Backup manifest limit exceeded.' ); }
			// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- The optional WHERE clause is a fixed server literal.
			$rows = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM %i$where ORDER BY id LIMIT %d", $wpdb->prefix . 'filewarden_' . $kind, self::MAX_ROWS + 1 ), ARRAY_A );
			if ( $wpdb->last_error || count( $rows ) > self::MAX_ROWS ) {
				throw new \RuntimeException( 'Backup manifest limit exceeded.' );
			}
			foreach ( $rows as &$row ) {
				if ( 'documents' === $kind ) {
					if ( ! empty( $row['purging'] ) ) {
						throw new \RuntimeException( 'Cleanup must finish before backup.' );
					}
					$keys[ $row['uuid'] ] = $row['storage_key'];
					unset( $row['storage_key'], $row['purging'] );
				}
				if ( isset( $row['created_by'] ) ) {
					$id                       = (string) $row['created_by'];
					$user                     = get_userdata( (int) $id );
					$manifest['users'][ $id ] = $user ? $user->user_login : '';
				}
				if ( 'audit_logs' === $kind && array_key_exists( 'actor_ip', $row ) ) {
					$row['actor_ip'] = AuditIp::visible( $row );
					if ( ! $row['actor_ip'] ) {
						$row['ip_expires_at'] = null;
					}
				}
				if ( 'audit_logs' === $kind && empty( $row['actor_login'] ) && ! empty( $row['actor_id'] ) ) {
					$user               = get_userdata( (int) $row['actor_id'] );
					$row['actor_login'] = $user ? $user->user_login : '';
				}
			}
			unset( $row );
			$manifest[ $kind ] = $rows;
		}
		if ( $components['settings'] ) {
			foreach ( SettingsConsole::FIELDS as $field => $bounds ) {
				$manifest['settings'][ $field ] = (int) get_option( 'filewarden_' . $field, $bounds[0] );
			}
			$manifest['settings']['language']             = Language::choice();
			$manifest['settings']['notification_seconds'] = Notice::duration();
		}
		if ( $components['appearance'] ) {
			$manifest['settings']['appearance'] = FrontendAppearance::current();
			$logo_id                            = $manifest['settings']['appearance']['logo_id'];
			if ( $logo_id ) {
				FrontendAppearance::logo_url( $logo_id );
				$path             = get_attached_file( $logo_id );
				$manifest['logo'] = array(
					'size'   => filesize( $path ),
					'sha256' => hash_file( 'sha256', $path ),
					'mime'   => get_post_mime_type( $logo_id ),
				); // phpcs:ignore WordPress.WP.AlternativeFunctions
				$keys['logo']     = $path;
			}
			$manifest['settings']['appearance']['logo_id'] = 0;
		}
		$manifest = BackupComponents::export( $manifest, $components );
		$keys     = array_intersect_key( $keys, array_fill_keys( array_merge( array_column( $manifest['documents'], 'uuid' ), $manifest['logo'] ? array( 'logo' ) : array() ), true ) );
		self::validate( $manifest );
		if ( strlen( wp_json_encode( $manifest ) ) > self::MAX_JSON ) {
			throw new \RuntimeException( 'Backup manifest limit exceeded.' );
		}
		return array( $manifest, $keys );
	}

	/** Validate every logical row and reference before writing any live metadata.
	 *
	 * @param array $manifest Untrusted decoded JSON.
	 * @return array Roles requiring explicit destination mappings.
	 * @throws \InvalidArgumentException For an incompatible or malformed manifest.
	 */
	public static function validate( array $manifest ) {
		$manifest = BackupComponents::validation( $manifest );
		$fail     = static function () {
			throw new \InvalidArgumentException( 'Invalid backup manifest.' );
		};
		if ( array_diff( array_keys( $manifest ), array( 'version', 'schema', 'root_uuid', 'folders', 'documents', 'audit_logs', 'users', 'settings', 'logo' ) ) || 1 !== ( $manifest['version'] ?? null ) || ! in_array( $manifest['schema'] ?? null, array( '6', '7', '8', '9' ), true ) || ! wp_is_uuid( $manifest['root_uuid'] ?? '', 4 ) ) {
			$fail();
		}
		foreach ( array( 'folders', 'documents', 'audit_logs', 'users', 'settings' ) as $key ) {
			if ( ! is_array( $manifest[ $key ] ?? null ) || count( $manifest[ $key ] ) > self::MAX_ROWS ) {
				$fail();
			}
		}
		$roles  = array();
		$policy = static function ( $json ) use ( &$roles, $fail ) {
			if ( ! is_string( $json ) || strlen( $json ) > 16384 ) {
				$fail(); }
			$value = json_decode( $json, true );
			if ( ! is_array( $value ) || ! is_array( $value['roles'] ?? array() ) ) {
				$fail(); }
			foreach ( $value['roles'] ?? array() as $role ) {
				if ( ! is_string( $role ) || ! preg_match( '/^[a-zA-Z0-9_-]{1,100}$/D', $role ) ) {
					$fail(); }
				$roles[] = $role;
			}
			Policy::validate( $value, $roles, true );
		};
		$ids    = array();
		$uuids  = array();
		foreach ( array( 'folders', 'documents' ) as $kind ) {
			$ids[ $kind ] = array();
			foreach ( $manifest[ $kind ] as $row ) {
				$allowed = 'folders' === $kind ? array( 'id', 'uuid', 'parent_id', 'name', 'policy', 'created_by', 'created_at' ) : array( 'id', 'uuid', 'folder_id', 'name', 'policy', 'created_by', 'created_at', 'folder_added_at', 'description', 'retained_rules', 'trashed_at', 'trash_origin', 'mime', 'size', 'sha256' );
				if ( 'documents' === $kind && isset( $row['description'] ) && DocumentDescription::validate( $row['description'] ) !== $row['description'] ) {
					$fail();
				}
				if ( 'documents' === $kind && isset( $row['folder_added_at'] ) && ! self::date( $row['folder_added_at'] ) ) {
					$fail();
				}
				if ( ! is_array( $row ) || array_diff( array_keys( $row ), $allowed ) || ! self::number( $row['id'] ?? null, 1 ) || isset( $ids[ $kind ][ $row['id'] ] ) || ! wp_is_uuid( $row['uuid'] ?? '', 4 ) || isset( $uuids[ strtolower( $row['uuid'] ) ] ) || ! is_string( $row['name'] ?? null ) || '' === trim( $row['name'] ) || strlen( $row['name'] ) > 255 || preg_match( '/[\x00-\x1f]/', $row['name'] ) || ! self::date( $row['created_at'] ?? '' ) || ! self::number( $row['created_by'] ?? null ) || ! array_key_exists( $row['created_by'], $manifest['users'] ) ) {
					$fail(); }
				$ids[ $kind ][ $row['id'] ]          = $row;
				$uuids[ strtolower( $row['uuid'] ) ] = true;
				$policy( $row['policy'] ?? null );
				if ( 'documents' === $kind ) {
					if ( ! in_array( $row['mime'] ?? null, array_merge( array_values( FileValidator::MIMES ), array( 'application/octet-stream' ) ), true ) ) {
						$fail(); }
					if ( ! self::number( $row['size'] ?? null, 1 ) || (int) $row['size'] > self::MAX_FILE || ! preg_match( '/^[a-f0-9]{64}$/D', $row['sha256'] ?? '' ) || ! is_string( $row['mime'] ?? null ) || ! preg_match( '#^[a-z0-9.+-]+/[a-z0-9.+-]+$#iD', $row['mime'] ) || strlen( $row['mime'] ) > 100 || ( null !== ( $row['trashed_at'] ?? null ) && ! self::date( $row['trashed_at'] ) ) ) {
						$fail(); }
					if ( ! empty( $row['retained_rules'] ) ) {
						$rules = json_decode( $row['retained_rules'], true );
						if ( ! is_array( $rules ) || count( $rules ) > 200 ) {
							$fail(); }
						foreach ( $rules as $rule ) {
							$policy( wp_json_encode( $rule ) ); }
					}
					if ( ! empty( $row['trash_origin'] ) ) {
						$origin = json_decode( $row['trash_origin'], true );
						if ( ! is_array( $origin ) || array_diff( array_keys( $origin ), array( 'folder_id', 'path' ) ) || ! self::number( $origin['folder_id'] ?? null ) || ! is_string( $origin['path'] ?? null ) || strlen( $origin['path'] ) > 25500 ) {
							$fail(); }
					}
				}
			}
		}
		foreach ( $manifest['folders'] as $row ) {
			Policy::validate( json_decode( $row['policy'], true ), $roles, (int) ( $row['parent_id'] ?? 0 ) > 0 );
			$next = $row['parent_id'] ?? null;
			$seen = array( $row['id'] => true );
			while ( '0' !== (string) $next ) {
				if ( ! self::number( $next, 1 ) || ! isset( $ids['folders'][ $next ] ) || isset( $seen[ $next ] ) || count( $seen ) >= 100 ) {
					$fail(); }
				$seen[ $next ] = true;
				$next          = $ids['folders'][ $next ]['parent_id'];
			}
		}
		foreach ( $manifest['documents'] as $row ) {
			if ( ! self::number( $row['folder_id'] ?? null ) || ( (int) $row['folder_id'] > 0 && ! isset( $ids['folders'][ $row['folder_id'] ] ) ) ) {
				$fail(); }
		}
		foreach ( $manifest['users'] as $id => $login ) {
			if ( ! self::number( $id ) || ! is_string( $login ) || strlen( $login ) > 60 ) {
				$fail(); }
		}
		foreach ( $manifest['audit_logs'] as $row ) {
			$allowed = array( 'id', 'actor_id', 'actor_login', 'actor_type', 'actor_ip', 'ip_expires_at', 'event', 'object_id', 'created_at', 'resource_type', 'resource_name', 'resource_rules' );
			if ( ! is_array( $row ) || array_diff( array_keys( $row ), $allowed ) || ! self::number( $row['id'] ?? null, 1 ) || ! self::number( $row['actor_id'] ?? null ) || ! self::number( $row['object_id'] ?? null ) || ! self::date( $row['created_at'] ?? '' ) || ! preg_match( '/^[a-z0-9_]{1,64}$/D', $row['event'] ?? '' ) || ! is_string( $row['actor_type'] ?? null ) || ! in_array( $row['actor_type'], array( '', 'user', 'system', 'visitor', 'unknown', 'imported', 'source_user', 'source_system', 'source_visitor', 'source_unknown' ), true ) || ! is_string( $row['actor_login'] ?? null ) || strlen( $row['actor_login'] ) > 60 || ! is_string( $row['resource_name'] ?? null ) || strlen( $row['resource_name'] ) > 255 || ! in_array( $row['resource_type'] ?? null, array( '', 'folder', 'document' ), true ) ) {
				$fail(); }
			if ( isset( $row['actor_ip'] ) && ( ! is_string( $row['actor_ip'] ) || ( '' !== $row['actor_ip'] && ! filter_var( $row['actor_ip'], FILTER_VALIDATE_IP ) ) ) ) {
				$fail();
			}
			if ( null !== ( $row['ip_expires_at'] ?? null ) && ! self::date( $row['ip_expires_at'] ) ) {
				$fail();
			}
			if ( ! empty( $row['resource_rules'] ) ) {
				$rules = json_decode( $row['resource_rules'], true );
				if ( ! is_array( $rules ) || array_diff( array_keys( $rules ), array( 'policies', 'recovery', 'source_uuid' ) ) || ! is_array( $rules['policies'] ?? null ) || count( $rules['policies'] ) > 200 ) {
					$fail(); }
				if ( isset( $rules['source_uuid'] ) && ! wp_is_uuid( $rules['source_uuid'], 4 ) ) {
					$fail(); }
				foreach ( $rules['policies'] as $rule ) {
					$policy( wp_json_encode( $rule ) ); }
			}
		}
		foreach ( SettingsConsole::FIELDS as $key => $bounds ) {
			$value = $manifest['settings'][ $key ] ?? ( 'audit_ip_days' === $key ? 30 : null );
			if ( ! is_int( $value ) || $value < $bounds[1] || $value > $bounds[2] ) {
				$fail(); }
		}
		if ( ! in_array( $manifest['settings']['language'] ?? null, Language::choices(), true ) || ! in_array( $manifest['settings']['notification_seconds'] ?? null, Notice::DURATIONS, true ) || ! is_array( $manifest['settings']['appearance'] ?? null ) ) {
			$fail(); }
		FrontendAppearance::validate( $manifest['settings']['appearance'] );
		if ( 0 !== $manifest['settings']['appearance']['logo_id'] ) {
			$fail(); }
		if ( ! array_key_exists( 'logo', $manifest ) || ( null !== $manifest['logo'] && ( ! is_array( $manifest['logo'] ) || ! in_array( $manifest['logo']['mime'] ?? null, array( 'image/png', 'image/jpeg' ), true ) || ! self::number( $manifest['logo']['size'] ?? null, 1 ) || (int) $manifest['logo']['size'] > 2097152 || ! preg_match( '/^[a-f0-9]{64}$/D', $manifest['logo']['sha256'] ?? '' ) ) ) ) {
			$fail(); }
		return array_values( array_unique( $roles ) );
	}

	/** Strict unsigned identifiers, bounded for portable signed database integers.
	 *
	 * @param mixed $value Value.
	 * @param int   $minimum Lower bound.
	 * @return bool Valid.
	 */
	private static function number( $value, $minimum = 0 ) {
		return ( is_int( $value ) || is_string( $value ) ) && preg_match( '/^(0|[1-9][0-9]{0,14})$/D', (string) $value ) && (int) $value >= $minimum;
	}

	/** Strict UTC dates, not executable expressions or guessed timestamps.
	 *
	 * @param mixed $value Value.
	 * @return bool Valid date.
	 */
	private static function date( $value ) {
		if ( ! is_string( $value ) ) {
			return false; }
		$date = \DateTimeImmutable::createFromFormat( '!Y-m-d H:i:s', $value, new \DateTimeZone( 'UTC' ) );
		return $date && $date->format( 'Y-m-d H:i:s' ) === $value;
	}
}
