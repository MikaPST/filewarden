<?php
/**
 * Centralized, strictly inherited authorization.
 *
 * @package FileWarden
 */

namespace FileWarden;

/**
 * PermissionService implementation.
 */
final class PermissionService {
	/**
	 * Service state.
	 *
	 * @var callable
	 */
	private $folder_lookup;

	/**
	 * Initialize service dependencies.
	 *
	 * @param callable $folder_lookup folder lookup value.
	 * @return mixed Operation result.
	 */
	public function __construct( callable $folder_lookup ) {
		$this->folder_lookup = $folder_lookup;
	}

	/**
	 * Evaluate the object and every ancestor policy.
	 *
	 * @param array $record object value.
	 * @param mixed $user user value.
	 * @return mixed Operation result.
	 */
	public function can_read( array $record, $user ) {
		if ( ! empty( $record['trashed_at'] ) || ! empty( $record['purging'] ) ) {
			return false; }
		// Resolve the complete ancestry first: missing parents and cycles fail closed.
		$policies = array( $record['policy'] ?? null );
		$parent   = (int) ( $record['folder_id'] ?? $record['parent_id'] ?? 0 );
		$seen     = array();
		while ( $parent ) {
			if ( isset( $seen[ $parent ] ) || count( $seen ) >= 100 ) {
				return false;
			}
			$seen[ $parent ] = true;
			$folder          = ( $this->folder_lookup )( $parent );
			if ( ! $folder ) {
				return false;
			}
			$policies[] = $folder['policy'] ?? null;
			$parent     = (int) $folder['parent_id'];
		}
		if ( $user->has_cap( 'filewarden_read_all_files' ) ) {
			return true;
		}
		foreach ( $policies as $policy ) {
			if ( ! Policy::allows( $policy, $user->exists(), $user->roles ) ) {
				return false;
			}
		}
		return true;
	}
}
