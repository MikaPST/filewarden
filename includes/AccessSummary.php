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
}
