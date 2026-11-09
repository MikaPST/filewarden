<?php
/**
 * Authorized document transfers and recoverable deletion.
 *
 * @package FileWarden
 */

namespace FileWarden;

/** Keep lifecycle operations independent of the interface. */
final class Lifecycle {
	/**
	 * Shared services.
	 *
	 * @var DocumentService
	 */
	private $service;

	/**
	 * Initialize shared services.
	 *
	 * @param DocumentService $service Shared services.
	 */
	public function __construct( DocumentService $service ) {
		$this->service = $service;
	}

	/**
	 * Authorize a target, including recovery of a trashed record.
	 *
	 * @param string $uuid Document UUID.
	 * @param bool   $trash Include trash.
	 * @return array Authorized record.
	 * @throws \DomainException If unavailable.
	 */
	public function record( $uuid, $trash = false ) {
		$row = $this->service->repository->document( $uuid, $trash );
		if ( ! $row || ! empty( $row['purging'] ) ) {
			throw new \DomainException( 'Document unavailable.' );
		}
		$visible = $row;
		unset( $visible['trashed_at'] );
		if ( ! $this->service->permissions->can_read( $visible, wp_get_current_user() ) ) {
			throw new \DomainException( 'Access denied.' );
		}
		return $row;
	}

	/**
	 * Describe destination permissions and bind confirmation to current rules.
	 *
	 * @param string $uuid Source UUID.
	 * @param int    $destination Destination ID.
	 * @return array Confirmation digest and prospective record.
	 */
	public function plan( $uuid, $destination ) {
		$this->service->require_capability( 'filewarden_manage_permissions' );
		$row = $this->record( $uuid );
		$this->service->require_folder( $destination );
		$prospective              = $row;
		$prospective['folder_id'] = $destination;
		$context                  = array( $row['uuid'], $row['folder_id'], $row['name'], $destination, AccessSummary::chain( $row, $this->service->repository ), AccessSummary::chain( $prospective, $this->service->repository ) );
		return array(
			'proof'  => wp_hash( wp_json_encode( $context ) ),
			'record' => $prospective,
		);
	}

	/**
	 * Move or independently copy bytes after reviewing the destination.
	 *
	 * @param string $operation Move or copy.
	 * @param string $uuid Source UUID.
	 * @param int    $destination Target folder.
	 * @param string $name Desired name.
	 * @param string $proof Reviewed policy digest.
	 * @return array Result metadata.
	 * @throws \InvalidArgumentException If the request or confirmation is invalid.
	 * @throws \UnexpectedValueException On name collision.
	 * @throws \RuntimeException On corrupt copy.
	 */
	public function transfer( $operation, $uuid, $destination, $name, $proof ) {
		if ( ! in_array( $operation, array( 'move', 'copy' ), true ) ) {
			throw new \InvalidArgumentException( 'Invalid operation.' );
		}
		$this->service->require_capability( 'move' === $operation ? 'filewarden_edit_files' : 'filewarden_upload_files' );
		$repo        = $this->service->repository;
		$rules_lock  = $repo->lock( 'access-rules' );
		$lock        = null;
		$folder_lock = null;
		$key         = null;
		try {
			$lock = $repo->lock( 'document:' . $uuid );
			$plan = $this->plan( $uuid, $destination );
			if ( ! hash_equals( $plan['proof'], $proof ) ) {
				throw new \InvalidArgumentException( 'Destination rules changed. Review again.' );
			}
			$row  = $this->record( $uuid );
			$name = sanitize_file_name( $name );
			if ( ! $name || strlen( $name ) > 255 || strtolower( pathinfo( $name, PATHINFO_EXTENSION ) ) !== strtolower( pathinfo( $row['name'], PATHINFO_EXTENSION ) ) ) {
				throw new \InvalidArgumentException( 'Invalid filename.' );
			}
			$folder_lock = $repo->lock( $destination );
			if ( $repo->duplicate( $destination, $name ) ) {
				throw new \UnexpectedValueException( 'Duplicate name.' );
			}
			$this->service->audit->record( 'document_' . $operation . '_requested', (int) $row['id'] );
			if ( 'move' === $operation ) {
				$repo->update_document(
					(int) $row['id'],
					array(
						'folder_id' => $destination,
						'name'      => $name,
					)
				);
			} else {
				$stream = $this->service->storage->open( $row['storage_key'], (int) $row['size'] );
				try {
					$path = stream_get_meta_data( $stream )['uri'];
					$key  = $this->service->storage->put( $path, (int) $row['size'] );
				} finally {
					// phpcs:ignore WordPress.WP.AlternativeFunctions -- Closing deletes the private decrypted temporary file.
					fclose( $stream );
				}
				$verification = $this->service->storage->open( $key, (int) $row['size'] );
				try {
					$hash = hash_init( 'sha256' );
					hash_update_stream( $hash, $verification );
					if ( ! hash_equals( $row['sha256'], hash_final( $hash ) ) ) {
						throw new \RuntimeException( 'Copy verification failed.' );
					}
				} finally {
					// phpcs:ignore WordPress.WP.AlternativeFunctions -- Close private verification stream.
					fclose( $verification );
				}
				unset( $row['id'], $row['created_by'], $row['created_at'] );
				$row['uuid']        = wp_generate_uuid4();
				$row['storage_key'] = $key;
				$row['folder_id']   = $destination;
				$row['name']        = $name;
				$row['id']          = $repo->insert( 'documents', $row );
				$key                = null;
				$uuid               = $row['uuid'];
			}
			$this->service->audit->record( 'document_' . $operation . '_completed', (int) $row['id'] );
			return $this->service->metadata( $repo->document( $uuid ) );
		} finally {
			try {
				if ( null !== $key ) {
					$this->service->storage->remove( $key );
				}
			} finally {
				if ( $folder_lock ) {
					$repo->unlock( $folder_lock );
				}
				if ( $lock ) {
					$repo->unlock( $lock );
				}
				$repo->unlock( $rules_lock );
			}
		}
	}

	/**
	 * Trash or restore without removing the encrypted bytes.
	 *
	 * @param string $uuid Document UUID.
	 * @param bool   $restore Restore instead of trash.
	 * @return int Parent folder ID.
	 * @throws \UnexpectedValueException If restoration collides with an active name.
	 */
	public function trash( $uuid, $restore = false ) {
		$this->service->require_capability( 'filewarden_delete_files' );
		$repo        = $this->service->repository;
		$lock        = $repo->lock( 'document:' . $uuid );
		$folder_lock = null;
		try {
			$row    = $this->record( $uuid, true );
			$folder = (int) $row['folder_id'];
			if ( empty( $row['trashed_at'] ) === $restore ) {
				return $folder;
			}
			$folder_lock = $repo->lock( $folder );
			if ( $restore && $repo->duplicate( $folder, $row['name'] ) ) {
				throw new \UnexpectedValueException( 'Duplicate name.' );
			}
			if ( $restore ) {
				$stream = $this->service->storage->open( $row['storage_key'], (int) $row['size'] );
				// phpcs:ignore WordPress.WP.AlternativeFunctions -- Close verified private stream before restoring metadata.
				fclose( $stream );
			}
			$event = $restore ? 'document_restored' : 'document_trashed';
			$this->service->audit->record( $event . '_requested', (int) $row['id'] );
			$repo->update_document( (int) $row['id'], array( 'trashed_at' => $restore ? null : current_time( 'mysql', true ) ) );
			$this->service->audit->record( $event, (int) $row['id'] );
			return $folder;
		} finally {
			if ( $folder_lock ) {
				$repo->unlock( $folder_lock );
			}
			$repo->unlock( $lock );
		}
	}

	/** Purge expired trash in a retry-safe bounded cron batch. */
	public function cleanup() {
		$repo   = $this->service->repository;
		$cutoff = gmdate( 'Y-m-d H:i:s', time() - max( 1, (int) get_option( 'filewarden_trash_days', 30 ) ) * DAY_IN_SECONDS );
		foreach ( $repo->expired( $cutoff ) as $uuid ) {
			$lock = null;
			try {
				$lock = $repo->lock( 'document:' . $uuid );
				$row  = $repo->document( $uuid, true );
				if ( ! $row || ( empty( $row['purging'] ) && ( empty( $row['trashed_at'] ) || $cutoff <= $row['trashed_at'] ) ) ) {
					continue;
				}
				$this->service->audit->record( 'document_purge_requested', (int) $row['id'] );
				$repo->update_document( (int) $row['id'], array( 'purging' => 1 ) );
				$this->service->storage->remove( $row['storage_key'] );
				$repo->delete_purged( (int) $row['id'] );
				$this->service->audit->record( 'document_purged', (int) $row['id'] );
			} catch ( \Throwable $error ) {
				// Leave the tombstone for the next cron run; never reactivate failed purges.
				break;
			} finally {
				if ( $lock ) {
					$repo->unlock( $lock );
				}
			}
		}
	}
}
