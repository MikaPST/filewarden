<?php
/** Authenticated component selection and safe selective restoration.
 *
 * @package FileWarden
 */

namespace FileWarden;

/** Source permissions are never replaced with public access when omitted. */
final class BackupComponents {
	const KEYS = array( 'documents', 'folders', 'trash', 'permissions', 'creators', 'settings', 'appearance', 'audit' );

	/** Canonical complete or simplified preset.
	 *
	 * @param bool $simplified Select the private-transfer preset.
	 */
	public static function defaults( $simplified = false ) {
		return array_replace(
			array_fill_keys( self::KEYS, ! $simplified ),
			array(
				'documents' => true,
				'folders'   => true,
			)
		); }

	/** Strict component flags; absent options preserve the old complete-backup API.
	 *
	 * @param array|null $input Eight exact boolean flags, or six legacy flags.
	 * @throws \InvalidArgumentException On unknown or coerced flags.
	 */
	public static function options( $input = null ) {
		if ( null === $input ) {
			return self::defaults(); }
		if ( is_array( $input ) && count( $input ) === 6 && ! array_diff( array_keys( $input ), array_slice( self::KEYS, 2 ) ) ) {
			$input += array(
				'documents' => true,
				'folders'   => true,
			);
		}
		if ( ! is_array( $input ) || count( $input ) !== count( self::KEYS ) || array_diff( array_keys( $input ), self::KEYS ) ) {
			throw new \InvalidArgumentException( 'Invalid backup components.' ); }
		foreach ( $input as $value ) {
			if ( ! is_bool( $value ) ) {
				throw new \InvalidArgumentException( 'Invalid backup components.' ); }
		}
		if ( $input['documents'] || $input['trash'] ) {
			$input['folders'] = true;
		}
		if ( ! $input['folders'] && ! $input['audit'] ) {
			$input['permissions'] = false;
			$input['creators']    = false;
		}
		if ( ! array_filter( array_intersect_key( $input, array_flip( array( 'documents', 'folders', 'trash', 'settings', 'appearance', 'audit' ) ) ) ) ) {
			throw new \InvalidArgumentException( 'Invalid backup components.' );
		}
		return array_replace( self::defaults(), $input );
	}

	/** Original archives contain the complete component set.
	 *
	 * @param array $manifest Authenticated or untrusted manifest.
	 * @throws \InvalidArgumentException For unknown versions or flags.
	 */
	public static function included( array $manifest ) {
		if ( 1 === ( $manifest['version'] ?? null ) && ! array_key_exists( 'components', $manifest ) ) {
			return self::defaults(); }
		if ( ! in_array( $manifest['version'] ?? null, array( 2, 3 ), true ) || ! array_key_exists( 'components', $manifest ) ) {
			throw new \InvalidArgumentException( 'Invalid backup components.' ); }
		$flags     = self::options( $manifest['components'] );
		$canonical = array_replace( self::defaults(), $manifest['components'] );
		if ( ( 2 === $manifest['version'] && 6 !== count( $manifest['components'] ) ) || ( 3 === $manifest['version'] && ( count( $manifest['components'] ) !== count( self::KEYS ) || $canonical !== $flags ) ) ) {
			throw new \InvalidArgumentException( 'Invalid backup components.' );
		}
		return $flags;
	}

	/** Configuration-only archives cannot modify library identities or content.
	 *
	 * @param array $manifest Validated manifest.
	 * @return bool Configuration-only scope.
	 */
	public static function configuration_only( array $manifest ) {
		$flags = self::included( $manifest );
		return ! $flags['documents'] && ! $flags['folders'] && ! $flags['trash'] && ! $flags['audit'] && empty( $manifest['documents'] ) && empty( $manifest['folders'] ) && empty( $manifest['audit_logs'] ) && ( $flags['settings'] || $flags['appearance'] );
	}

	/** Private administration access; the existing explicit global-read exception still applies. */
	public static function private_policy() {
		return array(
			'type'  => 'roles',
			'roles' => array( 'administrator' ),
		); }

	/** Remove omitted metadata before encryption, never just hide its controls.
	 *
	 * @param array $manifest Full logical source snapshot.
	 * @param array $components Validated choices.
	 * @throws \InvalidArgumentException For invalid component data.
	 */
	public static function export( array $manifest, array $components ) {
		$components             = self::options( $components );
		$manifest['version']    = 3;
		$manifest['components'] = $components;
		$manifest['documents']  = array_values( array_filter( $manifest['documents'], static fn( $row ) => empty( $row['trashed_at'] ) ? $components['documents'] : $components['trash'] ) );
		if ( ! $components['folders'] ) {
			$manifest['folders'] = array();
		}
		if ( ! $components['audit'] ) {
			$manifest['audit_logs'] = array(); }
		if ( ! $components['settings'] ) {
			$manifest['settings'] = $components['appearance'] ? array_intersect_key( $manifest['settings'], array( 'appearance' => true ) ) : array(); }
		if ( ! $components['appearance'] ) {
			unset( $manifest['settings']['appearance'] );
			$manifest['logo'] = null; }
		$users = array();
		foreach ( array( 'folders', 'documents' ) as $kind ) {
			foreach ( $manifest[ $kind ] as &$row ) {
				if ( ! $components['creators'] ) {
					$row['created_by'] = 0; }
				$id           = (string) $row['created_by'];
				$users[ $id ] = $components['creators'] ? ( $manifest['users'][ $id ] ?? '' ) : '';
				if ( ! $components['permissions'] ) {
					$row['policy'] = wp_json_encode( self::private_policy() );
					if ( 'documents' === $kind ) {
						$row['retained_rules'] = null; }
				}
			}
			unset( $row );
		}
		$manifest['users'] = $users;
		if ( ! $components['permissions'] ) {
			foreach ( $manifest['audit_logs'] as &$row ) {
				if ( ! empty( $row['resource_rules'] ) ) {
					$rules = json_decode( $row['resource_rules'], true );
					if ( ! is_array( $rules ) ) {
						throw new \InvalidArgumentException( 'Invalid backup manifest.' ); }
					$rules['policies']     = array( self::private_policy() );
					$row['resource_rules'] = wp_json_encode( $rules );
				}
			}
			unset( $row );
		}
		return $manifest;
	}

	/** Validate omission promises before reusing the original full graph validator.
	 *
	 * @param array $manifest Untrusted decoded manifest.
	 * @throws \InvalidArgumentException When an omission promise is broken.
	 */
	public static function validation( array $manifest ) {
		$flags = self::included( $manifest );
		if ( 1 === $manifest['version'] ) {
			return $manifest; }
		if ( array_diff( array_keys( $manifest ), array( 'version', 'components', 'schema', 'root_uuid', 'folders', 'documents', 'audit_logs', 'users', 'settings', 'logo' ) ) ) {
			throw new \InvalidArgumentException( 'Invalid backup manifest.' ); }
		foreach ( array( 'folders', 'documents', 'audit_logs', 'users', 'settings' ) as $key ) {
			if ( ! is_array( $manifest[ $key ] ?? null ) ) {
				throw new \InvalidArgumentException( 'Invalid backup manifest.' ); }
		}
		if ( ( ! $flags['folders'] && $manifest['folders'] ) || array_filter( $manifest['documents'], static fn( $row ) => ! is_array( $row ) || ( empty( $row['trashed_at'] ) ? ! $flags['documents'] : ! $flags['trash'] ) ) ) {
			throw new \InvalidArgumentException( 'Invalid backup components.' );
		}
		$private = self::private_policy();
		$users   = array();
		foreach ( array( 'folders', 'documents' ) as $kind ) {
			foreach ( $manifest[ $kind ] as $row ) {
				if ( ! is_array( $row ) || ( ! $flags['trash'] && ! empty( $row['trashed_at'] ) ) ) {
					throw new \InvalidArgumentException( 'Invalid backup manifest.' ); }
				if ( ! $flags['permissions'] && ( ! is_string( $row['policy'] ?? null ) || json_decode( $row['policy'], true ) !== $private || ! empty( $row['retained_rules'] ) ) ) {
					throw new \InvalidArgumentException( 'Invalid backup components.' ); }
				if ( ! $flags['creators'] ) {
					if ( 0 !== ( $row['created_by'] ?? null ) ) {
						throw new \InvalidArgumentException( 'Invalid backup components.' ); }
					$users[0] = '';
				}
			}
		}
		if ( ! $flags['creators'] && $manifest['users'] !== $users ) {
			throw new \InvalidArgumentException( 'Invalid backup components.' ); }
		if ( ! $flags['audit'] && $manifest['audit_logs'] ) {
			throw new \InvalidArgumentException( 'Invalid backup components.' ); }
		if ( ! $flags['permissions'] ) {
			foreach ( $manifest['audit_logs'] as $row ) {
				if ( ! is_array( $row ) ) {
					throw new \InvalidArgumentException( 'Invalid backup manifest.' ); }
				if ( ! empty( $row['resource_rules'] ) ) {
					$rules = json_decode( $row['resource_rules'], true );
					if ( ! is_array( $rules ) || ( $rules['policies'] ?? null ) !== array( $private ) ) {
						throw new \InvalidArgumentException( 'Invalid backup components.' ); }
				}
			}
		}
		$setting_keys = $flags['settings'] ? array_merge( array_keys( SettingsConsole::FIELDS ), array( 'language', 'notification_seconds' ) ) : array();
		if ( $flags['appearance'] ) {
			$setting_keys[] = 'appearance'; }
		if ( array_diff( array_keys( $manifest['settings'] ), $setting_keys ) || ! array_key_exists( 'logo', $manifest ) || ( ! $flags['appearance'] && null !== $manifest['logo'] ) ) {
			throw new \InvalidArgumentException( 'Invalid backup components.' ); }
		if ( ! $flags['settings'] ) {
			foreach ( SettingsConsole::FIELDS as $key => $bounds ) {
				$manifest['settings'][ $key ] = $bounds[0]; }
			$manifest['settings']['language']             = 'auto';
			$manifest['settings']['notification_seconds'] = 10;
		}
		if ( ! $flags['appearance'] ) {
			$manifest['settings']['appearance'] = FrontendAppearance::defaults(); }
		$manifest['version'] = 1;
		unset( $manifest['components'] );
		return $manifest;
	}

	/** A restore may omit available components, never enable metadata absent from its archive.
	 *
	 * @param array      $manifest Authenticated manifest.
	 * @param array|null $input Explicit restoration choices.
	 * @throws \InvalidArgumentException For absent or invalid components.
	 */
	public static function restoration( array $manifest, $input = null ) {
		$flags               = self::included( $manifest );
		$choices             = array_intersect_key( $flags, array_flip( array( 'permissions', 'settings', 'appearance', 'audit' ) ) );
		$choices['creators'] = $flags['creators'] ? 'map' : 'current';
		if ( null === $input ) {
			return $choices; }
		if ( ! is_array( $input ) || count( $input ) !== count( $choices ) || array_diff( array_keys( $input ), array_keys( $choices ) ) ) {
			throw new \InvalidArgumentException( 'Invalid restoration choices.' ); }
		foreach ( array( 'permissions', 'settings', 'appearance', 'audit' ) as $key ) {
			if ( ! is_bool( $input[ $key ] ) || ( $input[ $key ] && ! $flags[ $key ] ) ) {
				throw new \InvalidArgumentException( 'Invalid restoration choices.' ); }
			$choices[ $key ] = $input[ $key ];
		}
		if ( ! in_array( $input['creators'], $flags['creators'] ? array( 'map', 'current' ) : array( 'current' ), true ) ) {
			throw new \InvalidArgumentException( 'Invalid restoration choices.' ); }
		$choices['creators'] = $input['creators'];
		return $choices;
	}

	/** Translated labels and explanations shared by both screens. */
	public static function labels() {
		return array(
			'documents'   => array( __( 'Include documents', 'filewarden' ), __( 'Include active files and their metadata. Their folder structure is included automatically.', 'filewarden' ) ),
			'folders'     => array( __( 'Include folder structure', 'filewarden' ), __( 'Recreate the folder tree, even without its documents.', 'filewarden' ) ),
			'trash'       => array( __( 'Include trash', 'filewarden' ), __( 'Also restore deleted documents still retained in the source library.', 'filewarden' ) ),
			'permissions' => array( __( 'Keep access rules', 'filewarden' ), __( 'Match the roles used by source rules to existing destination roles. If omitted, restored content is private to administration.', 'filewarden' ) ),
			'creators'    => array( __( 'Keep creator attribution', 'filewarden' ), __( 'Retain source creator references for optional individual attribution. Otherwise all restored items belong to the restoring account. No WordPress account is created.', 'filewarden' ) ),
			'settings'    => array( __( 'Include FileWarden settings', 'filewarden' ), __( 'Transfer limits, retention, language and notification duration. Sensitive security options are reset when importing settings.', 'filewarden' ) ),
			'appearance'  => array( __( 'Include public appearance', 'filewarden' ), __( 'Transfer the public name, colors and validated logo. Otherwise keep the destination appearance.', 'filewarden' ) ),
			'audit'       => array( __( 'Include audit history', 'filewarden' ), __( 'Includes historical actors and any unexpired recorded IPs. Imported events remain identified as source history.', 'filewarden' ) ),
		);
	}
}
