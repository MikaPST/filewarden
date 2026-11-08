<?php
/**
 * Stable public folder references, separate from internal relationships.
 *
 * @package FileWarden
 */

namespace FileWarden;

/** UUID publication with compatibility for existing numeric shortcodes. */
final class FolderReference {

	/**
	 * Resolve a public reference without granting access.
	 *
	 * @param mixed $reference UUID or legacy integer.
	 * @return int Internal folder ID.
	 * @throws \InvalidArgumentException For malformed or unknown references.
	 */
	public static function resolve( $reference ) {
		if ( is_string( $reference ) && wp_is_uuid( $reference, 4 ) ) {
			$reference = strtolower( $reference );
			if ( get_option( 'filewarden_root_uuid' ) === $reference ) {
				return 0;
			}
			$row = ( new Repository() )->folder_by_uuid( $reference );
			if ( ! $row ) {
				throw new \InvalidArgumentException( 'Unknown folder.' );
			}
			return (int) $row['id'];
		}
		if ( null === $reference || '' === $reference ) {
			throw new \InvalidArgumentException( 'Missing folder reference.' );
		}
		return RestController::integer( $reference );
	}

	/**
	 * Retrieve the permanent UUID, including for the virtual root.
	 *
	 * @param int $id Internal folder ID.
	 * @return string Public UUID.
	 * @throws \InvalidArgumentException For unavailable references.
	 */
	public static function uuid( $id ) {
		$uuid = 0 === $id ? get_option( 'filewarden_root_uuid' ) : ( ( new Repository() )->folder( $id )['uuid'] ?? null );
		if ( ! is_string( $uuid ) || ! wp_is_uuid( $uuid, 4 ) ) {
			throw new \InvalidArgumentException( 'Folder reference unavailable.' );
		}
		return $uuid;
	}
}
