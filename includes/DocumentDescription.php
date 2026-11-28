<?php
/** Optional private document descriptions.
 *
 * @package FileWarden
 */

namespace FileWarden;

/** Plain text with a Unicode code-point limit, independent of mbstring. */
final class DocumentDescription {

	/** Validate before any write; never silently truncate.
	 *
	 * @param mixed $value User text.
	 * @return string Plain text.
	 * @throws \InvalidArgumentException On invalid UTF-8 or excessive length.
	 */
	public static function validate( $value ) {
		if ( ! is_string( $value ) || strlen( $value ) > 1600 || 1 !== preg_match( '//u', $value ) || preg_match_all( '/./us', $value ) > 400 ) {
			throw new \InvalidArgumentException( 'Description exceeds 400 characters or is invalid.' );
		}
		return sanitize_textarea_field( $value );
	}

	/** Edit only this metadata under the shared writer and access-rule locks.
	 *
	 * @param DocumentService $service Shared services.
	 * @param string          $uuid Logical identifier.
	 * @param mixed           $value Plain text.
	 * @throws \InvalidArgumentException If the logical identifier is invalid.
	 */
	public static function save( DocumentService $service, $uuid, $value ) {
		$service->require_capability( 'filewarden_edit_files' );
		$service->require_capability( 'filewarden_manage_permissions' );
		if ( ! is_string( $uuid ) || ! wp_is_uuid( $uuid, 4 ) ) {
			throw new \InvalidArgumentException( 'Invalid document.' );
		}
		$value = self::validate( $value );
		$lock  = $service->repository->lock( 'access-rules' );
		$doc   = null;
		try {
			$doc = $service->repository->lock( 'document:' . $uuid );
			$row = $service->get( $uuid );
			$service->repository->require_transactions();
			LibraryActions::transaction(
				static function () use ( $service, $row, $value ) {
					$service->repository->update_document( (int) $row['id'], array( 'description' => $value ) );
					$service->audit->record( 'document_description_changed', (int) $row['id'] );
				}
			);
		} finally {
			if ( $doc ) {
				$service->repository->unlock( $doc );
			}
			$service->repository->unlock( $lock );
		}
	}
}
