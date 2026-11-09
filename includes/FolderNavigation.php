<?php
/**
 * Authorized navigation scoped to a published folder.
 *
 * @package FileWarden
 */

namespace FileWarden;

/** Never expose a breadcrumb outside the published subtree. */
final class FolderNavigation {
	/**
	 * Resolve an authorized path from the publication boundary to its current folder.
	 *
	 * @param DocumentService $service Services.
	 * @param int             $root Published boundary.
	 * @param int             $folder Current folder.
	 * @return array Authorized path.
	 * @throws \DomainException For recovery collection, cycles or escaped boundaries.
	 */
	public static function path( DocumentService $service, $root, $folder ) {
		if ( $root < 1 || $folder < 1 ) {
			throw new \DomainException( 'Folder unavailable.' ); }
		$path = array();
		$seen = array();
		while ( $folder ) {
			if ( isset( $seen[ $folder ] ) || count( $seen ) >= 100 ) {
				throw new \DomainException( 'Folder unavailable.' ); }
			$seen[ $folder ] = true;
			$service->require_folder( $folder );
			$row = $service->repository->folder( $folder );
			array_unshift( $path, $row );
			if ( $root === $folder ) {
				return $path; }
			$folder = (int) $row['parent_id'];
		}
		throw new \DomainException( 'Folder unavailable.' );
	}

	/**
	 * Return only readable immediate children.
	 *
	 * @param DocumentService $service Services.
	 * @param int             $folder Authorized folder.
	 * @return array Children.
	 */
	public static function children( DocumentService $service, $folder ) {
		$service->require_folder( $folder );
		$result = array();
		for ( $offset = 0; ; $offset += 100 ) {
			$rows = $service->repository->folders( $folder, $offset );
			foreach ( $rows as $row ) {
				if ( $service->permissions->can_read( $row, wp_get_current_user() ) ) {
					$result[] = $row; }
			}
			if ( count( $rows ) < 100 ) {
				return $result; }
		}
	}
}
