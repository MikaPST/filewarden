<?php
/**
 * REST transport; all sensitive decisions also live in services.
 *
 * @package FileWarden
 */

namespace FileWarden;

/**
 * RestController implementation.
 */
final class RestController {
	/**
	 * Service state.
	 *
	 * @var DocumentService
	 */
	private $service;

	/**
	 * Initialize service dependencies.
	 *
	 * @param DocumentService $service service value.
	 * @return mixed Operation result.
	 */
	public function __construct( DocumentService $service ) {
		$this->service = $service;
	}

	/**
	 * Register versioned REST endpoints.
	 *
	 * @return mixed Operation result.
	 */
	public function register() {
		$read = array( 'permission_callback' => '__return_true' );
		register_rest_route(
			'filewarden/v1',
			'/documents',
			array(
				array_merge(
					$read,
					array(
						'methods'  => 'GET',
						'callback' => array( $this, 'documents' ),
					)
				),
				array(
					'methods'             => 'POST',
					'callback'            => array( $this, 'upload' ),
					'permission_callback' => array( $this, 'can_upload' ),
				),
			)
		);
		register_rest_route(
			'filewarden/v1',
			'/documents/(?P<uuid>[a-f0-9-]{36})',
			array_merge(
				$read,
				array(
					'methods'  => 'GET',
					'callback' => array( $this, 'document' ),
				)
			)
		);
		register_rest_route(
			'filewarden/v1',
			'/folders',
			array(
				array_merge(
					$read,
					array(
						'methods'  => 'GET',
						'callback' => array( $this, 'folders' ),
					)
				),
				array(
					'methods'             => 'POST',
					'callback'            => array( $this, 'folder' ),
					'permission_callback' => array( $this, 'can_manage' ),
				),
			)
		);
	}

	/**
	 * Check permission-management capability.
	 *
	 * @return mixed Operation result.
	 */
	public function can_manage() {
		return current_user_can( 'filewarden_manage_permissions' );
	}

	/**
	 * Check upload and explicit-policy capabilities.
	 *
	 * @return mixed Operation result.
	 */
	public function can_upload() {
		return $this->can_manage() && current_user_can( 'filewarden_upload_files' );
	}

	/**
	 * Validate a nonnegative integer identifier or offset.
	 *
	 * @param mixed $value value value.
	 * @throws \InvalidArgumentException When the operation cannot be completed safely.
	 * @return mixed Operation result.
	 */
	public static function integer( $value ) {
		if ( null === $value ) {
			return 0;
		}
		if ( ! is_scalar( $value ) || false === filter_var(
			$value,
			FILTER_VALIDATE_INT,
			array(
				'options' => array(
					'min_range' => 0,
					'max_range' => 2147483647,
				),
			)
		) ) {
			throw new \InvalidArgumentException( 'Invalid identifier.' );
		}
		return (int) $value;
	}

	/**
	 * Execute an operation with private caching and safe errors.
	 *
	 * @param callable $operation operation value.
	 * @param mixed    $status status value.
	 * @return mixed Operation result.
	 */
	private function response( callable $operation, $status = 200 ) {
		try {
			$data = $operation();
		} catch ( \DomainException $error ) {
			$data   = array(
				'code'    => 'filewarden_unavailable',
				'message' => __( 'Resource unavailable.', 'filewarden' ),
			);
			$status = 404;
		} catch ( \InvalidArgumentException $error ) {
			$data   = array(
				'code'    => 'filewarden_invalid',
				'message' => __( 'Invalid input, file type or size.', 'filewarden' ),
			);
			$status = 400;
		} catch ( \UnexpectedValueException $error ) {
			$data   = array(
				'code'    => 'filewarden_duplicate',
				'message' => __( 'Rename the file or cancel. Version creation is not available yet.', 'filewarden' ),
			);
			$status = 409;
		} catch ( \Throwable $error ) {
			$data   = array(
				'code'    => 'filewarden_failed',
				'message' => __( 'Operation unavailable. Check FileWarden diagnostics.', 'filewarden' ),
			);
			$status = 503;
		}
		return new \WP_REST_Response(
			$data,
			$status,
			array(
				'Cache-Control'          => 'private, no-store, max-age=0',
				'Vary'                   => 'Cookie, Authorization',
				'X-Content-Type-Options' => 'nosniff',
			)
		);
	}

	/**
	 * Retrieve a document by its logical UUID.
	 *
	 * @param mixed $request request value.
	 * @return mixed Operation result.
	 */
	public function document( $request ) {
		return $this->response( fn() => $this->service->metadata( $this->service->get( $request['uuid'] ) ) );
	}

	/**
	 * Retrieve a bounded document batch.
	 *
	 * @param mixed $request request value.
	 * @return mixed Operation result.
	 */
	public function documents( $request ) {
		return $this->response(
			function () use ( $request ) {
				$folder = self::integer( $request['folder_id'] );
				$this->service->require_folder( $folder );
				$rows   = $this->service->repository->documents( $folder, self::integer( $request['offset'] ) );
				$result = array();
				foreach ( $rows as $row ) {
					if ( $this->service->permissions->can_read( $row, wp_get_current_user() ) ) {
						$result[] = $this->service->metadata( $row );
					}
				}
				return $result;
			}
		);
	}

	/**
	 * Retrieve a bounded folder batch.
	 *
	 * @param mixed $request request value.
	 * @return mixed Operation result.
	 */
	public function folders( $request ) {
		return $this->response(
			function () use ( $request ) {
				$parent = self::integer( $request['parent_id'] );
				$this->service->require_folder( $parent );
				$rows   = $this->service->repository->folders( $parent, self::integer( $request['offset'] ) );
				$result = array();
				foreach ( $rows as $row ) {
					if ( $this->service->permissions->can_read( $row, wp_get_current_user() ) ) {
						$result[] = array(
							'id'        => (int) $row['id'],
							'name'      => $row['name'],
							'parent_id' => (int) $row['parent_id'],
						);
					}
				}
				return $result;
			}
		);
	}

	/**
	 * Decode a JSON or form policy.
	 *
	 * @param mixed $request request value.
	 * @return mixed Operation result.
	 */
	private function policy( $request ) {
		$policy = $request->get_param( 'policy' );
		return is_string( $policy ) ? json_decode( $policy, true ) : $policy;
	}

	/**
	 * Validate and persist an authorized document upload.
	 *
	 * @param mixed $request request value.
	 * @return mixed Operation result.
	 */
	public function upload( $request ) {
		return $this->response(
			function () use ( $request ) {
				$files = $request->get_file_params();
				return $this->service->upload( $files['file'] ?? array(), self::integer( $request['folder_id'] ), $this->policy( $request ) );
			},
			201
		);
	}

	/**
	 * Retrieve or create a folder through the active transport.
	 *
	 * @param mixed $request request value.
	 * @throws \InvalidArgumentException When the operation cannot be completed safely.
	 * @return mixed Operation result.
	 */
	public function folder( $request ) {
		return $this->response(
			function () use ( $request ) {
				if ( ! is_string( $request['name'] ) ) {
						throw new \InvalidArgumentException( 'Invalid name.' );
				}
				return array( 'id' => $this->service->create_folder( $request['name'], self::integer( $request['parent_id'] ), $this->policy( $request ) ) );
			},
			201
		);
	}
}
