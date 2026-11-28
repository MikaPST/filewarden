<?php
/** Authorized, transactional rename and selection operations.
 *
 * @package FileWarden
 */

namespace FileWarden;

/** Share permission and concurrency rules with the existing lifecycle. */
final class LibraryActions {
	/** Validate a name without permitting extension changes.
	 *
	 * @param string      $name Requested name.
	 * @param string|null $previous Original filename, or null for a folder.
	 * @return string Safe name.
	 * @throws \InvalidArgumentException For empty names or extension changes.
	 */
	public static function name( $name, $previous = null ) {
		if ( ! is_string( $name ) || strlen( $name ) > 255 ) {
			throw new \InvalidArgumentException( 'Invalid name.' );
		}
		$name = null === $previous ? sanitize_text_field( trim( $name ) ) : sanitize_file_name( trim( $name ) );
		if ( '' === $name || in_array( $name, array( '.', '..' ), true ) || ( null !== $previous && strtolower( pathinfo( $name, PATHINFO_EXTENSION ) ) !== strtolower( pathinfo( $previous, PATHINFO_EXTENSION ) ) ) ) {
			throw new \InvalidArgumentException( 'Invalid name.' );
		}
		return $name;
	}

	/** Rename only a current readable item; retain every identity and ACL field.
	 *
	 * @param DocumentService $service Services.
	 * @param string          $kind Document or folder.
	 * @param string|int      $id UUID or folder ID.
	 * @param string          $name New name.
	 * @throws \RuntimeException If the operation cannot complete safely.
	 * @throws \DomainException If the operation cannot complete safely.
	 * @throws \InvalidArgumentException If the operation cannot complete safely.
	 * @throws \UnexpectedValueException If the operation cannot complete safely.
	 */
	public static function rename( DocumentService $service, $kind, $id, $name ) {
		global $wpdb;
		$service->require_capability( 'filewarden_edit_files' );
		$service->require_capability( 'filewarden_manage_permissions' );
		if ( ! in_array( $kind, array( 'document', 'folder' ), true ) ) {
			throw new \InvalidArgumentException( 'Invalid item.' );
		}
		$repo = $service->repository;
		$lock = $repo->lock( 'access-rules' );
		try {
			$repo->require_transactions();
			$row = 'document' === $kind ? ( new Lifecycle( $service ) )->record( $id ) : $repo->folder( RestController::integer( $id ) );
			if ( ! $row || ! $service->permissions->can_read( $row, wp_get_current_user() ) ) {
				throw new \DomainException( 'Access denied.' );
			}
			$name   = self::name( $name, 'document' === $kind ? $row['name'] : null );
			$table  = $wpdb->prefix . 'filewarden_' . ( 'document' === $kind ? 'documents' : 'folders' );
			$parent = 'document' === $kind ? 'folder_id' : 'parent_id';
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery,WordPress.DB.PreparedSQL.NotPrepared -- Only the constant active/trash suffix varies; allowlisted identifiers and prepared values under the common writer gate.
			$exists = $wpdb->get_var( $wpdb->prepare( 'SELECT id FROM %i WHERE %i = %d AND name = %s AND id <> %d' . ( 'document' === $kind ? ' AND trashed_at IS NULL' : '' ) . ' LIMIT 1', $table, $parent, $row[ $parent ], $name, $row['id'] ) );
			if ( $exists ) {
				throw new \UnexpectedValueException( 'Duplicate name.' );
			}
			if ( $wpdb->last_error ) {
				throw new \RuntimeException( 'Rename unavailable.' );
			}
			self::transaction(
				static function () use ( $service, $kind, $row, $table, $name, $wpdb ) {
					$service->audit->record( $kind . '_rename_requested', (int) $row['id'] );
				// phpcs:ignore WordPress.DB.DirectDatabaseQuery -- Name is the only mutable field here.
					if ( false === $wpdb->update( $table, array( 'name' => $name ), array( 'id' => $row['id'] ) ) ) {
						throw new \RuntimeException( 'Rename unavailable.' );
					}
					$service->audit->record( $kind . '_renamed', (int) $row['id'] );
				}
			);
		} finally {
			$repo->unlock( $lock );
		}
	}

	/** Prepare a bound preview for at most fifty authorized selected records.
	 *
	 * @param DocumentService $service Services.
	 * @param array           $ids UUID selection.
	 * @param string          $operation Move or restore.
	 * @param int             $destination Destination folder (zero is recovery).
	 * @return array Review and proof.
	 * @throws \InvalidArgumentException If the operation cannot complete safely.
	 */
	public static function review( DocumentService $service, array $ids, $operation, $destination ) {
		$service->require_capability( in_array( $operation, array( 'restore', 'trash' ), true ) ? 'filewarden_delete_files' : 'filewarden_edit_files' );
		if ( 'trash' !== $operation ) {
			$service->require_capability( 'filewarden_manage_permissions' );
		}
		if ( ! in_array( $operation, array( 'move', 'restore', 'trash' ), true ) || ! $ids || count( $ids ) > 50 || count( $ids ) !== count( array_unique( $ids ) ) || ( 'restore' === $operation && $destination < 1 ) ) {
			throw new \InvalidArgumentException( 'Invalid selection.' );
		}
		if ( 'trash' === $operation ) {
			$destination = 0;
		} else {
			$service->require_folder( $destination );
		}
		if ( ! $destination && 'trash' !== $operation ) {
			$service->require_capability( 'filewarden_manage_settings' );
		}
		$rows      = array();
		$context   = array( get_current_user_id(), $operation, $destination );
		$lifecycle = new Lifecycle( $service );
		foreach ( $ids as $uuid ) {
			if ( ! is_string( $uuid ) || ! wp_is_uuid( $uuid, 4 ) ) {
				throw new \InvalidArgumentException( 'Invalid selection.' );
			}
			$row = $lifecycle->record( $uuid, 'restore' === $operation );
			if ( ( 'restore' === $operation ) !== ! empty( $row['trashed_at'] ) || ( 'move' === $operation && (int) $row['folder_id'] === $destination ) ) {
				throw new \InvalidArgumentException( 'Invalid selection.' );
			}
			$prospective              = $row;
			$prospective['folder_id'] = 'trash' === $operation || ( 'restore' === $operation && (int) $row['folder_id'] > 0 && $service->repository->folder( (int) $row['folder_id'] ) ) ? $row['folder_id'] : $destination;
			$context[]                = array( $row['uuid'], $row['name'], $row['folder_id'], $row['trashed_at'] ?? null, AccessSummary::chain( $row, $service->repository ), AccessSummary::chain( $prospective, $service->repository ) );
			$rows[]                   = array(
				'uuid'   => $uuid,
				'name'   => $row['name'],
				'access' => AccessSummary::effective( $prospective, $service->repository )['label'],
			);
		}
		return array(
			'rows'  => $rows,
			'proof' => wp_hash( wp_json_encode( $context ) ),
		);
	}

	/** Execute the whole reviewed selection or roll all metadata and audit writes back.
	 *
	 * @param DocumentService $service Services.
	 * @param array           $ids Selection.
	 * @param string          $operation Move or restore.
	 * @param int             $destination Destination.
	 * @param string          $proof Reviewed digest.
	 * @throws \InvalidArgumentException If the operation cannot complete safely.
	 */
	public static function bulk( DocumentService $service, array $ids, $operation, $destination, $proof ) {
		$repo = $service->repository;
		$lock = $repo->lock( 'access-rules' );
		try {
			$repo->require_transactions();
			$review = self::review( $service, $ids, $operation, $destination );
			if ( ! hash_equals( $review['proof'], $proof ) ) {
				throw new \InvalidArgumentException( 'Destination rules changed. Review again.' );
			}
			self::transaction(
				static function () use ( $service, $ids, $operation, $destination ) {
					$lifecycle = new Lifecycle( $service );
					foreach ( $ids as $uuid ) {
						if ( 'trash' === $operation ) {
							$lifecycle->trash( $uuid );
						} elseif ( 'restore' === $operation ) {
							$lifecycle->trash( $uuid, true, $destination );
						} else {
							$plan = $lifecycle->plan( $uuid, $destination );
							$row  = $lifecycle->record( $uuid );
							$lifecycle->transfer( 'move', $uuid, $destination, $row['name'], $plan['proof'] );
						}
					}
				}
			);
		} finally {
			$repo->unlock( $lock );
		}
	}

	/** Execute a metadata-only transaction (never a substitute for filesystem recovery).
	 *
	 * @param callable $action Atomic writes.
	 * @throws \RuntimeException If the operation cannot complete safely.
	 * @throws \Throwable If the operation cannot complete safely.
	 */
	public static function transaction( callable $action ) {
		global $wpdb;
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery -- Explicit atomic metadata operation.
		if ( false === $wpdb->query( 'START TRANSACTION' ) ) {
			throw new \RuntimeException( 'Transaction unavailable.' );
		}
		try {
			$action();
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery -- Commit only after every selected operation succeeds.
			if ( false === $wpdb->query( 'COMMIT' ) ) {
				throw new \RuntimeException( 'Commit unavailable.' );
			}
		} catch ( \Throwable $error ) {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery -- Failed selection has no partial completion.
			$wpdb->query( 'ROLLBACK' );
			throw $error;
		}
	}
}
