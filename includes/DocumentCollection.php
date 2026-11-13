<?php
/** Authorized recovery and global trash collections.
 *
 * @package FileWarden
 */

namespace FileWarden;

/** Apply content ACL before counting, searching or paginating collections. */
final class DocumentCollection {
	/** Return readable rows; trash never depends on the previously selected folder.
	 *
	 * @param DocumentService $service Services.
	 * @param bool            $trash Select trash instead of recovery.
	 * @param string          $search Literal filename search.
	 * @param int|null        $folder Optional original/current folder filter.
	 * @return array Authorized rows.
	 * @throws \RuntimeException On collection read failure.
	 */
	public static function rows( DocumentService $service, $trash, $search = '', $folder = null ) {
		global $wpdb;
		$service->require_capability( $trash ? 'filewarden_delete_files' : 'filewarden_manage_permissions' );
		$where  = $trash ? 'trashed_at IS NOT NULL' : 'trashed_at IS NULL AND folder_id = 0';
		$result = array();
		for ( $offset = 0; ; $offset += 100 ) {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery,WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Fixed allowlisted predicate and prepared pagination.
			$rows = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}filewarden_documents WHERE purging = 0 AND $where ORDER BY id DESC LIMIT 100 OFFSET %d", $offset ), ARRAY_A );
			if ( $wpdb->last_error ) {
				throw new \RuntimeException( 'Collection unavailable.' ); }
			foreach ( $rows as $row ) {
				$row['policy']         = json_decode( $row['policy'], true );
				$row['retained_rules'] = empty( $row['retained_rules'] ) ? array() : json_decode( $row['retained_rules'], true );
				$check                 = $row;
				unset( $check['trashed_at'] );
				if ( ! $service->permissions->can_read( $check, wp_get_current_user() ) || ( '' !== $search && false === stripos( remove_accents( $row['name'] ), remove_accents( $search ) ) ) || ( null !== $folder && (int) $row['folder_id'] !== $folder ) ) {
					continue; }
				$result[] = $row;
			}
			if ( count( $rows ) < 100 ) {
				return $result; }
		}
	}

	/** Snapshot a logical path, never a physical storage location.
	 *
	 * @param Repository $repository Folder repository.
	 * @param int        $folder Folder ID.
	 * @return string|null Known logical path.
	 */
	public static function path( Repository $repository, $folder ) {
		if ( 0 === $folder ) {
			return __( 'Documents to classify', 'filewarden' ); }
		$names = array();
		$seen  = array();
		while ( $folder ) {
			if ( isset( $seen[ $folder ] ) || count( $seen ) >= 100 ) {
				return null; }
			$seen[ $folder ] = true;
			$row             = $repository->folder( $folder );
			if ( ! $row ) {
				return null; }
			array_unshift( $names, $row['name'] );
			$folder = (int) $row['parent_id'];
		}
		return implode( ' / ', $names );
	}
}
