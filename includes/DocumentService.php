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
	 * @throws \UnexpectedValueException When the operation cannot be completed safely.
	 * @throws \Throwable When the operation cannot be completed safely.
	 * @return mixed Operation result.
	 */
	public function upload( array $file, $folder, $policy ) {
		$this->require_capability( 'filewarden_upload_files' );
		// This milestone requires explicit policy management; delegated upload defaults follow later.
		$this->require_capability( 'filewarden_manage_permissions' );
		$this->require_folder( $folder );
		$policy = Policy::validate( $policy, array_keys( wp_roles()->roles ) );
		$this->storage->root();
		if ( ! isset( $file['error'], $file['tmp_name'], $file['name'] ) || UPLOAD_ERR_OK !== $file['error'] || ! is_string( $file['tmp_name'] ) || ! is_string( $file['name'] ) || ! is_uploaded_file( $file['tmp_name'] ) ) {
			throw new \InvalidArgumentException( 'Invalid upload.' );
		}
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
		$lock = $this->repository->lock( $folder );
		$key  = null;
		try {
			if ( $this->repository->duplicate( $folder, $name ) ) {
				throw new \UnexpectedValueException( 'Rename this file before uploading. Version creation is not available yet.' );
			}
			$this->audit->record( 'upload_requested', $folder );
			$key  = $this->storage->put( $file['tmp_name'], $size );
			$uuid = wp_generate_uuid4();
			$id   = $this->repository->insert(
				'documents',
				array(
					'uuid'        => $uuid,
					'folder_id'   => $folder,
					'name'        => $name,
					'storage_key' => $key,
					'mime'        => $mime,
					'size'        => $size,
					'sha256'      => FileIO::attempt( fn() => hash_file( 'sha256', $file['tmp_name'] ) ),
					'policy'      => $policy,
				)
			);
			// Once persisted, never delete the referenced file if audit logging subsequently fails.
			$key = null;
			$this->audit->record( 'document_uploaded', $id );
			return $this->metadata( $this->repository->document( $uuid ) );
		} catch ( \Throwable $error ) {
			if ( null !== $key ) {
				$this->storage->remove( $key );
			}
			throw $error;
		} finally {
			$this->repository->unlock( $lock );
		}
	}
}
