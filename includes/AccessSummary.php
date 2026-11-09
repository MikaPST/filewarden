<?php
/**
 * Human-readable policy descriptions, never an authorization mechanism.
 *
 * @package FileWarden
 */

namespace FileWarden;

/** Describe stored policies without implying that inheritance is bypassed. */
final class AccessSummary {

	/**
	 * Describe one rule. Escape at the output boundary.
	 *
	 * @param mixed $policy Stored policy.
	 * @return string Localized rule.
	 */
	public static function label( $policy ) {
		switch ( $policy['type'] ?? '' ) {
			case 'inherit':
				return __( 'Inherit from folder', 'filewarden' );
			case 'public':
				return __( 'Public', 'filewarden' );
			case 'authenticated':
				return __( 'Authenticated users', 'filewarden' );
			case 'roles':
				if ( ! is_array( $policy['roles'] ?? null ) || ! $policy['roles'] ) {
					return __( 'Access unavailable', 'filewarden' );
				}
				$roles = array();
				foreach ( $policy['roles'] as $slug ) {
					if ( ! is_string( $slug ) ) {
						return __( 'Access unavailable', 'filewarden' );
					}
					$roles[] = isset( wp_roles()->roles[ $slug ] ) ? translate_user_role( wp_roles()->roles[ $slug ]['name'] ) : $slug;
				}
				return __( 'Selected roles:', 'filewarden' ) . ' ' . implode( ', ', $roles );
			default:
				return __( 'Access unavailable', 'filewarden' );
		}
	}

	/**
	 * Resolve all applicable rules for display and transfer confirmation.
	 *
	 * @param array      $record Target record.
	 * @param Repository $repository Folder lookup.
	 * @return array Ordered policy chain.
	 * @throws \DomainException For broken ancestry.
	 */
	public static function chain( array $record, Repository $repository ) {
		$rules  = array( $record['policy'] ?? null );
		$parent = (int) ( $record['folder_id'] ?? $record['parent_id'] ?? 0 );
		$seen   = array();
		while ( $parent ) {
			if ( isset( $seen[ $parent ] ) || count( $seen ) >= 100 ) {
				throw new \DomainException( 'Invalid ancestry.' );
			}
			$seen[ $parent ] = true;
			$folder          = $repository->folder( $parent );
			if ( ! $folder ) {
				throw new \DomainException( 'Missing ancestor.' );
			}
			$rules[] = $folder['policy'];
			$parent  = (int) $folder['parent_id'];
		}
		return $rules;
	}
	/**
	 * Describe the intersection, without merging unrelated role predicates.
	 *
	 * @param array      $record Target record.
	 * @param Repository $repository Folder lookup.
	 * @return array Label and public-conflict flag.
	 */
	public static function effective( array $record, Repository $repository ) {
		try {
			$rules = self::chain( $record, $repository );
		} catch ( \DomainException $error ) {
			return array(
				'label'    => __( 'Access unavailable', 'filewarden' ),
				'conflict' => true,
			);
		}
		$labels            = array();
		$restricted_parent = false;
		foreach ( $rules as $index => $policy ) {
			if ( ! in_array( $policy['type'] ?? '', array( 'public', 'inherit' ), true ) ) {
				$labels[] = self::label( $policy );
				if ( $index > 0 ) {
					$restricted_parent = true;
				}
			}
		}
		return array(
			'label'    => $labels ? implode( ' ∩ ', array_unique( $labels ) ) : __( 'Public', 'filewarden' ),
			'conflict' => 'public' === ( $record['policy']['type'] ?? '' ) && $restricted_parent,
		);
	}
}
