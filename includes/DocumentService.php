<?php
/**
 * Document operations, with authorization independent of transport.
 *
 * @package FileWarden
 */

namespace FileWarden;

/**
 * DocumentService implementation.
 */
final class DocumentService {
	const TYPES = array(
		'pdf'  => 'application/pdf',
		'jpg'  => 'image/jpeg',
		'jpeg' => 'image/jpeg',
		'png'  => 'image/png',
	);
	/**
	 * Service state.
	 *
	 * @var Repository
	 */
	public $repository;
	/**
	 * Service state.
	 *
	 * @var PermissionService
	 */
	public $permissions;
	/**
	 * Service state.
	 *
	 * @var StorageService
	 */
	public $storage;
	/**
	 * Service state.
	 *
	 * @var AuditService
	 */
	public $audit;

	/**
	 * Initialize service dependencies.
	 *
	 * @param Repository        $repository repository value.
	 * @param PermissionService $permissions permissions value.
	 * @param StorageService    $storage storage value.
	 * @param AuditService      $audit audit value.
	 * @return mixed Operation result.
	 */
	public function __construct( Repository $repository, PermissionService $permissions, StorageService $storage, AuditService $audit ) {
		$this->repository  = $repository;
		$this->permissions = $permissions;
		$this->storage     = $storage;
		$this->audit       = $audit;
	}

	/**
	 * Require an independent WordPress capability.
	 *
	 * @param mixed $capability capability value.
	 * @throws \DomainException When the operation cannot be completed safely.
	 * @return mixed Operation result.
	 */
	public function require_capability( $capability ) {
		if ( ! current_user_can( $capability ) ) {
			$this->audit->record( 'access_denied' );
			throw new \DomainException( 'Access denied.' );
		}
	}

	/**
	 * Require read access to an existing parent folder.
	 *
	 * @param mixed $id id value.
	 * @throws \DomainException When the operation cannot be completed safely.
	 * @return mixed Operation result.
	 */
	public function require_folder( $id ) {
		if ( 0 === $id ) {
			return;
		}
		$folder = $this->repository->folder( $id );
		if ( ! $folder || ! $this->permissions->can_read( $folder, wp_get_current_user() ) ) {
			$this->audit->record( 'access_denied' );
			throw new \DomainException( 'Access denied.' );
		}
	}

	/**
	 * Resolve a logical document and enforce read authorization.
	 *
	 * @param mixed $uuid uuid value.
	 * @throws \DomainException When the operation cannot be completed safely.
	 * @return mixed Operation result.
	 */
	public function get( $uuid ) {
		$document = $this->repository->document( $uuid );
		if ( ! $document || ! $this->permissions->can_read( $document, wp_get_current_user() ) ) {
			$this->audit->record( 'access_denied' );
			throw new \DomainException( 'Document unavailable.' );
		}
		return $document;
	}

	/**
	 * Build public metadata from an already authorized record.
	 *
	 * @param array $document document value.
	 * @return mixed Operation result.
	 */
	public function metadata( array $document ) {
		return array(
			'id'         => $document['uuid'],
			'name'       => $document['name'],
			'folder_id'  => (int) $document['folder_id'],
			'mime'       => $document['mime'],
			'size'       => (int) $document['size'],
			'created_at' => $document['created_at'],
			'detail_url' => add_query_arg(
				array(
					'action'   => 'filewarden_content',
					'document' => $document['uuid'],
				),
				admin_url( 'admin-post.php' )
			),
		);
	}

	/**
	 * Create a folder after capability and ancestry checks.
	 *
	 * @param mixed $name name value.
	 * @param mixed $parent_id parent value.
	 * @param mixed $policy policy value.
	 * @throws \InvalidArgumentException When the operation cannot be completed safely.
	 * @return mixed Operation result.
	 */
	public function create_folder( $name, $parent_id, $policy ) {
		$this->require_capability( 'filewarden_manage_permissions' );
		$this->require_folder( $parent_id );
		$policy = Policy::validate( $policy, array_keys( wp_roles()->roles ) );
		$name   = sanitize_text_field( $name );
		if ( '' === $name || strlen( $name ) > 255 ) {
			throw new \InvalidArgumentException( 'Invalid folder name.' );
		}
		$this->audit->record( 'folder_create_requested', $parent_id );
		$id = $this->repository->insert(
			'folders',
			array(
				'name'      => $name,
				'parent_id' => $parent_id,
				'policy'    => $policy,
			)
		);
		$this->audit->record( 'folder_created', $id );
		return $id;
	}

	/**
	 * Validate and persist an authorized document upload.
	 *
	 * @param array $file file value.
	 * @param mixed $folder folder value.
	 * @param mixed $policy policy value.
	 * @throws \InvalidArgumentException When the operation cannot be completed safely.
	 * @return mixed Operation result.
	 */
	public function upload( array $file, $folder, $policy ) {
		if ( in_array( $file['error'] ?? null, array( UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE ), true ) ) {
			throw new \InvalidArgumentException( 'Upload exceeds server limit.' );
		}
		if ( ! isset( $file['error'], $file['tmp_name'], $file['name'] ) || UPLOAD_ERR_OK !== $file['error'] || ! is_string( $file['tmp_name'] ) || ! is_string( $file['name'] ) || ! is_uploaded_file( $file['tmp_name'] ) ) {
			throw new \InvalidArgumentException( 'Invalid upload.' );
		}
		return $this->store( $file, $folder, $policy );
	}

	/**
	 * List readable folder paths for the current administrator's selectors.
	 *
	 * @return array<int,string> Folder labels indexed by ID.
	 */
	public function folder_choices() {
		$choices = array( 0 => __( 'Root folder', 'filewarden' ) );
		$pending = array( 0 );
		$user    = wp_get_current_user();
		while ( $pending ) {
			$parent_id = array_pop( $pending );
			$offset    = 0;
			do {
				$rows = $this->repository->folders( $parent_id, $offset );
				foreach ( $rows as $row ) {
					$id = (int) $row['id'];
					if ( isset( $choices[ $id ] ) || ! $this->permissions->can_read( $row, $user ) ) {
						continue;
					}
					$choices[ $id ] = $parent_id ? $choices[ $parent_id ] . ' / ' . $row['name'] : $row['name'];
					$pending[]      = $id;
				}
				$offset  += 100;
				$has_more = 100 === count( $rows );
			} while ( $has_more );
		}
		return $choices;
	}

	/**
	 * Copy a readable local WordPress attachment into protected storage.
	 *
	 * @param int   $id Attachment ID.
	 * @param int   $folder Destination folder.
	 * @param array $policy Destination access policy.
	 * @return array Document metadata.
	 * @throws \InvalidArgumentException If the source is not an accessible local attachment.
	 */
	public function import_media( $id, $folder, $policy ) {
		$this->require_capability( 'upload_files' );
		if ( 'attachment' !== get_post_type( $id ) || ! current_user_can( 'edit_post', $id ) ) {
			throw new \InvalidArgumentException( 'Media unavailable.' );
		}
		$uploads = wp_get_upload_dir();
		$base    = FileIO::attempt( fn() => realpath( $uploads['basedir'] ) );
		$source  = get_attached_file( $id, true );
		$path    = is_string( $source ) ? FileIO::attempt( fn() => realpath( $source ) ) : false;
		if ( ! $base || ! $path || ! StorageService::within( $path, $base ) || ! is_file( $path ) || ! is_readable( $path ) ) {
			throw new \InvalidArgumentException( 'Media is not a readable local file in WordPress uploads.' );
		}
		return $this->store(
			array(
				'tmp_name' => $path,
				'name'     => wp_basename( $path ),
			),
			$folder,
			$policy
		);
	}

	/**
	 * Validate and persist a file whose provenance was checked by the caller.
	 *
	 * @param array $file File path and original name.
	 * @param int   $folder Destination folder.
	 * @param array $policy Destination access policy.
	 * @return array Document metadata.
	 * @throws \InvalidArgumentException If the file is invalid.
	 * @throws \UnexpectedValueException If the name already exists.
	 * @throws \RuntimeException If persistence or verification fails.
	 * @throws \Throwable If storage fails.
	 */
	private function store( array $file, $folder, $policy ) {
		$this->require_capability( 'filewarden_upload_files' );
		// This milestone requires explicit policy management; delegated upload defaults follow later.
		$this->require_capability( 'filewarden_manage_permissions' );
		$this->require_folder( $folder );
		$policy = Policy::validate( $policy, array_keys( wp_roles()->roles ) );
		$this->storage->root();
		$name  = sanitize_file_name( $file['name'] );
		$size  = FileIO::attempt( fn() => filesize( $file['tmp_name'] ) );
		$limit = min( max( 1, (int) get_option( 'filewarden_upload_mb', 50 ) ) * MB_IN_BYTES, wp_max_upload_size() );
		if ( '' === $name || strlen( $name ) > 255 || false === $size || $size < 1 || $size > $limit ) {
			throw new \InvalidArgumentException( 'Invalid filename or file size.' );
		}
		$extension = strtolower( pathinfo( $name, PATHINFO_EXTENSION ) );
		$mime      = FileIO::attempt( fn() => ( new \finfo( FILEINFO_MIME_TYPE ) )->file( $file['tmp_name'] ) );
		if ( ! isset( self::TYPES[ $extension ] ) || self::TYPES[ $extension ] !== $mime ) {
			throw new \InvalidArgumentException( 'Unsupported file type.' );
		}
		if ( str_starts_with( $mime, 'image/' ) && false === FileIO::attempt( fn() => getimagesize( $file['tmp_name'] ) ) ) {
			throw new \InvalidArgumentException( 'Invalid image.' );
		}
		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents -- Read the signature of a verified local upload.
		if ( 'application/pdf' === $mime && '%PDF-' !== FileIO::attempt( fn() => file_get_contents( $file['tmp_name'], false, null, 0, 5 ) ) ) {
			throw new \InvalidArgumentException( 'Invalid PDF.' );
		}
		$lock  = $this->repository->lock( $folder );
		$key   = null;
		$stage = null;
		try {
			if ( $this->repository->duplicate( $folder, $name ) ) {
				throw new \UnexpectedValueException( 'Rename this file before uploading. Version creation is not available yet.' );
			}
			$this->audit->record( 'upload_requested', $folder );
			$stage  = 'storage';
			$digest = FileIO::attempt( fn() => hash_file( 'sha256', $file['tmp_name'] ) );
			if ( false === $digest ) {
				throw new \RuntimeException( 'File verification failed.' );
			}
			$key    = $this->storage->put( $file['tmp_name'], $size );
			$handle = $this->storage->open( $key, $size );
			try {
				$hash = hash_init( 'sha256' );
				if ( FileIO::attempt( fn() => hash_update_stream( $hash, $handle ) ) !== $size || ! hash_equals( $digest, hash_final( $hash ) ) ) {
					throw new \RuntimeException( 'File verification failed.' );
				}
			} finally {
				// phpcs:ignore WordPress.WP.AlternativeFunctions -- Close the verified stream and release any temporary file.
				fclose( $handle );
			}
			$stage = 'database';
			$uuid  = wp_generate_uuid4();
			$id    = $this->repository->insert(
				'documents',
				array(
					'uuid'        => $uuid,
					'folder_id'   => $folder,
					'name'        => $name,
					'storage_key' => $key,
					'mime'        => $mime,
					'size'        => $size,
					'sha256'      => $digest,
					'policy'      => $policy,
				)
			);
			// Once persisted, never delete the referenced file if audit logging subsequently fails.
			$key    = null;
			$record = $this->repository->document( $uuid );
			if ( ! $record || (int) $record['folder_id'] !== $folder || (int) $record['id'] !== $id ) {
				throw new \RuntimeException( 'Destination verification failed.' );
			}
			$stage = 'audit';
			$this->audit->record( 'document_uploaded', $id );
			return $this->metadata( $record );
		} catch ( \Throwable $error ) {
			if ( null !== $key ) {
				$this->storage->remove( $key );
			}
			if ( $stage ) {
				$this->audit->record( 'upload_failed_' . $stage, $folder );
				// phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped -- Stage is an internal literal and callers map it to safe notices.
				throw new \RuntimeException( 'Upload failed at ' . $stage . '.', 0, $error );
			}
			throw $error;
		} finally {
			$this->repository->unlock( $lock );
		}
	}
}
