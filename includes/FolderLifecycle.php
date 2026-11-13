<?php
/**
 * Recover documents before deleting a folder, without weakening inherited access.
 *
 * @package FileWarden
 */

namespace FileWarden;

// phpcs:disable WordPress.DB.DirectDatabaseQuery -- Prepared custom-table operations require fresh state and a transaction.
/** Folder deletion is independent of transport and cannot recursively remove content. */
final class FolderLifecycle {
	/**
	 * Delete one authorized leaf folder and recover all its documents atomically.
	 *
	 * @param DocumentService $service Services.
	 * @param int             $id Folder ID.
	 * @return int Recovered count.
	 * @throws \DomainException On unauthorized objects or non-leaf folders.
	 * @throws \RuntimeException On failed persistence.
	 */
	public static function remove( DocumentService $service, $id ) {
		global $wpdb;
		$service->require_capability( 'filewarden_delete_files' );
		$service->require_capability( 'filewarden_manage_permissions' );
		if ( $id < 1 ) {
			throw new \DomainException( 'System collection cannot be deleted.' ); }
		Repository::require_transactions();
		$rules_lock  = $service->repository->lock( 'access-rules' );
		$folder_lock = null;
		$transaction = false;
		try {
			$folder_lock = $service->repository->lock( $id );
			$service->require_folder( $id );
			if ( $service->repository->folders( $id ) ) {
				throw new \DomainException( 'Remove subfolders first.' ); }
			$snapshot = $service->repository->folder( $id );
			$parents  = AccessSummary::chain( $snapshot, $service->repository );
			$service->audit->record( 'folder_delete_requested', $id );
			if ( false === $wpdb->query( 'START TRANSACTION' ) ) {
				throw new \RuntimeException( 'Recovery transaction unavailable.' ); }
			$transaction = true;
			$count       = 0;
			do {
				$rows = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}filewarden_documents WHERE folder_id = %d LIMIT 100 FOR UPDATE", $id ), ARRAY_A );
				if ( $wpdb->last_error ) {
					throw new \RuntimeException( 'Recovery read failed.' ); }
				foreach ( $rows as $row ) {
					$check           = $row;
					$check['policy'] = json_decode( $row['policy'], true );
					$retained        = empty( $row['retained_rules'] ) ? array() : json_decode( $row['retained_rules'], true );
					if ( ! is_array( $retained ) ) {
						throw new \RuntimeException( 'Recovery rules invalid.' ); }
					$check['retained_rules'] = $retained;
					unset( $check['trashed_at'], $check['purging'] );
					if ( ! $service->permissions->can_read( $check, wp_get_current_user() ) ) {
						throw new \DomainException( 'Access denied.' ); }
					$saved = wp_json_encode( array_merge( $retained, $parents ) );
					if ( false === $wpdb->update(
						$wpdb->prefix . 'filewarden_documents',
						array(
							'folder_id'      => 0,
							'retained_rules' => $saved,
							'trash_origin'   => ! empty( $row['trash_origin'] ) ? $row['trash_origin'] : ( ! empty( $row['trashed_at'] ) ? wp_json_encode(
								array(
									'folder_id' => $id,
									'path'      => DocumentCollection::path( $service->repository, $id ),
								)
							) : null ),
						),
						array( 'id' => $row['id'] ),
						array( '%d', '%s', '%s' ),
						array( '%d' )
					) ) {
						throw new \RuntimeException( 'Recovery write failed.' ); }
					++$count;
				}
				$batch_count = count( $rows );
			} while ( 100 === $batch_count );
			if ( 1 !== $wpdb->delete( $wpdb->prefix . 'filewarden_folders', array( 'id' => $id ), array( '%d' ) ) ) {
				throw new \RuntimeException( 'Folder deletion failed.' ); }
			$service->audit->record( 'folder_deleted_files_recovered', $id, $snapshot );
			if ( false === $wpdb->query( 'COMMIT' ) ) {
				throw new \RuntimeException( 'Recovery commit failed.' ); }
			$transaction = false;
			return $count;
		} finally {
			if ( $transaction ) {
				$wpdb->query( 'ROLLBACK' ); }
			if ( $folder_lock ) {
				$service->repository->unlock( $folder_lock ); }
			$service->repository->unlock( $rules_lock );
		}
	}
}
