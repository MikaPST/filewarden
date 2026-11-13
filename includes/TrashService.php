<?php
/** Reviewed manual trash disposal.
 *
 * @package FileWarden
 */

namespace FileWarden;

/** Bind irreversible deletion to the exact readable trash selected by this user. */
final class TrashService {
	/** Review a single item or the global authorized trash.
	 *
	 * @param DocumentService $service Services.
	 * @param string          $uuid Optional single-item UUID.
	 * @return array Review and proof.
	 */
	public static function review( DocumentService $service, $uuid = '' ) {
		$rows = DocumentCollection::rows( $service, true );
		if ( '' !== $uuid ) {
			$rows = array_values( array_filter( $rows, static fn( $row ) => $uuid === $row['uuid'] ) ); }
		$state = array_map( static fn( $row ) => array( $row['uuid'], $row['trashed_at'], AccessSummary::chain( $row, $service->repository ) ), $rows );
		return array(
			'rows'  => $rows,
			'proof' => wp_hash( wp_json_encode( array( get_current_user_id(), $uuid, $state ) ) ),
		);
	}

	/** Permanently dispose only the confirmed selection; failed tombstones remain retryable.
	 *
	 * @param DocumentService $service Services.
	 * @param string          $uuid Single UUID or empty for all authorized trash.
	 * @param string          $proof Reviewed selection digest.
	 * @return int Deleted count.
	 * @throws \InvalidArgumentException For absent/stale confirmation.
	 */
	public static function purge( DocumentService $service, $uuid, $proof ) {
		$service->require_capability( 'filewarden_delete_files' );
		$repo  = $service->repository;
		$rules = $repo->lock( 'access-rules' );
		try {
			$review = self::review( $service, $uuid );
			if ( ! $review['rows'] || ! hash_equals( $review['proof'], $proof ) ) {
				throw new \InvalidArgumentException( 'Trash selection changed. Review again.' ); }
			$service->storage->root();
			$count = 0;
			foreach ( $review['rows'] as $row ) {
				$lock = $repo->lock( 'document:' . $row['uuid'] );
				try {
					$current = ( new Lifecycle( $service ) )->record( $row['uuid'], true );
					if ( empty( $current['trashed_at'] ) || $current['trashed_at'] !== $row['trashed_at'] ) {
						throw new \InvalidArgumentException( 'Trash selection changed. Review again.' ); }
					$service->audit->record( 'document_purge_requested', (int) $row['id'] );
					$repo->update_document( (int) $row['id'], array( 'purging' => 1 ) );
					$service->storage->remove( $row['storage_key'] );
					$repo->delete_purged( (int) $row['id'] );
					$service->audit->record( 'document_purged', (int) $row['id'], $row );
					++$count;
				} finally {
					$repo->unlock( $lock ); }
			}
			return $count;
		} finally {
			$repo->unlock( $rules ); }
	}
}
