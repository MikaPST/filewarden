<?php
/**
 * Pure access-policy rules, shared by all controllers.
 *
 * @package FileWarden
 */

namespace FileWarden;

/**
 * Policy implementation.
 */
final class Policy {
	/**
	 * Validate and normalize an access policy.
	 *
	 * @param mixed $policy policy value.
	 * @param array $known_roles known roles value.
	 * @throws \InvalidArgumentException When the operation cannot be completed safely.
	 * @return mixed Operation result.
	 */
	public static function validate( $policy, array $known_roles ) {
		if ( ! is_array( $policy ) || ! isset( $policy['type'] ) || array_diff( array_keys( $policy ), array( 'type', 'roles' ) ) ) {
			throw new \InvalidArgumentException( 'Invalid policy.' );
		}
		if ( ! in_array( $policy['type'], array( 'public', 'authenticated', 'roles' ), true ) ) {
			throw new \InvalidArgumentException( 'Invalid policy type.' );
		}
		$roles = $policy['roles'] ?? array();
		if ( ! is_array( $roles ) || ! array_is_list( $roles ) ) {
			throw new \InvalidArgumentException( 'Invalid roles.' );
		}
		foreach ( $roles as $role ) {
			if ( ! is_string( $role ) || ! in_array( $role, $known_roles, true ) ) {
				throw new \InvalidArgumentException( 'Unknown role.' );
			}
		}
		if ( ( 'roles' === $policy['type'] && ! $roles ) || ( 'roles' !== $policy['type'] && $roles ) ) {
			throw new \InvalidArgumentException( 'Invalid policy roles.' );
		}
		return array(
			'type'  => $policy['type'],
			'roles' => array_values( array_unique( $roles ) ),
		);
	}

	/**
	 * Evaluate one policy against the current identity.
	 *
	 * @param mixed $policy policy value.
	 * @param mixed $authenticated authenticated value.
	 * @param array $roles roles value.
	 * @return mixed Operation result.
	 */
	public static function allows( $policy, $authenticated, array $roles ) {
		if ( ! is_array( $policy ) ) {
			return false;
		}
		switch ( $policy['type'] ?? '' ) {
			case 'public':
				return true;
			case 'authenticated':
				return $authenticated;
			case 'roles':
				return $authenticated && is_array( $policy['roles'] ?? null ) && (bool) array_intersect( $roles, $policy['roles'] );
			default:
				return false;
		}
	}
}
