<?php
/**
 * Minimal foundation UI; the visual document manager is a later milestone.
 *
 * @package FileWarden
 */

namespace FileWarden;

/**
 * AdminController implementation.
 */
final class AdminController {
	/**
	 * Service state.
	 *
	 * @var DocumentService
	 */
	private $service;

	/**
	 * Authorized folder labels for this administration screen.
	 *
	 * @var array|null
	 */
	private $folder_labels;

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
	 * Register the foundation administration page.
	 *
	 * @return mixed Operation result.
	 */
	public function menu() {
		add_menu_page( 'FileWarden', 'FileWarden', 'filewarden_manage_settings', 'filewarden', array( $this, 'page' ), 'dashicons-media-document' );
		add_submenu_page( 'filewarden', __( 'Documents', 'filewarden' ), __( 'Documents', 'filewarden' ), 'filewarden_manage_settings', 'filewarden', array( $this, 'page' ) );
		add_submenu_page( 'filewarden', __( 'Publication', 'filewarden' ), __( 'Publication', 'filewarden' ), 'filewarden_manage_settings', 'filewarden-publication', array( $this, 'publish_page' ) );
		add_submenu_page( 'filewarden', __( 'Settings', 'filewarden' ), __( 'Settings', 'filewarden' ), 'filewarden_manage_settings', 'filewarden-settings', array( $this, 'settings_page' ) );
		add_submenu_page( 'filewarden', __( 'Audit log', 'filewarden' ), __( 'Audit log', 'filewarden' ), 'filewarden_view_audit', 'filewarden-audit', array( $this, 'audit_page' ) );
	}

	/**
	 * Load the native Media Library picker on the documents screen.
	 *
	 * @param string $hook Administration screen hook.
	 */
	public function media_assets( $hook ) {
		if ( 'toplevel_page_filewarden' !== $hook ) {
			return;
		}
		wp_enqueue_style( 'filewarden-admin', plugins_url( 'assets/admin.css', FILEWARDEN_PATH . 'filewarden.php' ), array(), FILEWARDEN_VERSION );
		wp_enqueue_script( 'filewarden-upload', plugins_url( 'assets/admin.js', FILEWARDEN_PATH . 'filewarden.php' ), array(), FILEWARDEN_VERSION, true );
		wp_localize_script(
			'filewarden-upload',
			'filewardenUpload',
			array(
				'sending'   => __( 'Sending file…', 'filewarden' ),
				'verifying' => __( 'Transfer complete. Encrypting and verifying the document in its destination folder…', 'filewarden' ),
				'failed'    => __( 'The server did not confirm saving the file. Check the folder before retrying. HTTP status:', 'filewarden' ),
				'network'   => __( 'Connection interrupted. Check the folder before retrying; the server may still be processing the file.', 'filewarden' ),
			)
		);
		if ( ! current_user_can( 'upload_files' ) ) {
			return;
		}
		wp_enqueue_media();
		wp_enqueue_script( 'filewarden-media', plugins_url( 'assets/media.js', FILEWARDEN_PATH . 'filewarden.php' ), array( 'media-views' ), FILEWARDEN_VERSION, true );
	}

	/**
	 * Link supported media items to the FileWarden import form.
	 *
	 * @param array    $actions Row actions.
	 * @param \WP_Post $post Attachment.
	 * @return array Row actions.
	 */
	public function media_action( $actions, $post ) {
		if ( current_user_can( 'filewarden_manage_settings' ) && current_user_can( 'filewarden_manage_permissions' )
			&& current_user_can( 'filewarden_upload_files' ) && current_user_can( 'upload_files' )
			&& current_user_can( 'edit_post', $post->ID ) && in_array( $post->post_mime_type, DocumentService::TYPES, true ) ) {
			$url                   = add_query_arg(
				array(
					'page'     => 'filewarden',
					'media_id' => $post->ID,
				),
				admin_url( 'admin.php' )
			) . '#filewarden-import';
			$actions['filewarden'] = '<a href="' . esc_url( $url ) . '">' . esc_html__( 'Copy to FileWarden', 'filewarden' ) . '</a>';
		}
		return $actions;
	}

	/**
	 * Handle nonce-protected administration mutations.
	 *
	 * @throws \InvalidArgumentException When the operation cannot be completed safely.
	 * @return mixed Operation result.
	 */
	public function submit() {
		$folder  = 0;
		$page    = 'filewarden';
		$created = null;
		try {
			$this->service->require_capability( 'filewarden_manage_settings' );
			check_admin_referer( 'filewarden_admin' );
			$operation = isset( $_POST['operation'] ) && is_string( $_POST['operation'] ) ? sanitize_key( $_POST['operation'] ) : '';
			if ( 'settings' === $operation ) {
				$page = 'filewarden-settings';
				// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- integer() strictly validates scalar nonnegative integers.
				$size = RestController::integer( wp_unslash( $_POST['upload_mb'] ?? null ) );
				// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- integer() strictly validates scalar nonnegative integers.
				$days = RestController::integer( wp_unslash( $_POST['audit_days'] ?? null ) );
				if ( $size < 1 || $size > 1024 || $days < 1 || $days > 3650 ) {
					throw new \InvalidArgumentException( 'Invalid settings.' );
				}
				$this->service->audit->record( 'settings_changed' );
				update_option( 'filewarden_upload_mb', $size );
				update_option( 'filewarden_audit_days', $days );
			} else {
				$type   = isset( $_POST['policy_type'] ) && is_string( $_POST['policy_type'] ) ? sanitize_key( $_POST['policy_type'] ) : '';
				$roles  = isset( $_POST['roles'] ) && is_array( $_POST['roles'] ) ? array_map( 'sanitize_key', wp_unslash( $_POST['roles'] ) ) : array();
				$policy = array(
					'type'  => $type,
					'roles' => 'roles' === $type ? $roles : array(),
				);
				// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- integer() strictly validates scalar nonnegative integers.
				$folder = RestController::integer( wp_unslash( $_POST['folder_id'] ?? null ) );
				if ( 'folder' === $operation ) {
					$name = isset( $_POST['name'] ) && is_string( $_POST['name'] ) ? sanitize_text_field( wp_unslash( $_POST['name'] ) ) : '';
					$this->service->create_folder( $name, $folder, $policy );
				} elseif ( 'upload' === $operation ) {
					// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Upload service validates the original PHP upload structure, provenance, MIME and size.
					$created = $this->service->upload( $_FILES['file'] ?? array(), $folder, $policy );
				} elseif ( 'import' === $operation ) {
					// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- integer() strictly validates the attachment ID.
					$id      = RestController::integer( wp_unslash( $_POST['media_id'] ?? null ) );
					$created = $this->service->import_media( $id, $folder, $policy );
				} else {
					throw new \InvalidArgumentException( 'Invalid operation.' );
				}
			}
			$result = 'success';
		} catch ( \UnexpectedValueException $error ) {
			$result = 'duplicate';
		} catch ( \DomainException $error ) {
			$result = 'denied';
		} catch ( \InvalidArgumentException $error ) {
			$result = match ( $error->getMessage() ) {
				'Invalid filename or file size.', 'Upload exceeds server limit.' => 'size',
				'Unsupported file type.', 'Invalid image.', 'Invalid PDF.' => 'format',
				'Invalid upload.' => 'missing_file',
				'Media unavailable.', 'Media is not a readable local file in WordPress uploads.' => 'media',
				default => 'invalid',
			};
		} catch ( \RuntimeException $error ) {
			$result = match ( $error->getMessage() ) {
				'Upload failed at storage.' => 'storage',
				'Upload failed at database.' => 'database',
				'Upload failed at audit.' => 'audit',
				'Document is busy.' => 'busy',
				default => 'failed',
			};
		} catch ( \Throwable $error ) {
			$result = 'failed';
		}
		wp_safe_redirect(
			add_query_arg(
				array(
					'page'      => $page,
					'folder_id' => $folder,
					'result'    => $result,
					'created'   => $created['id'] ?? false,
				),
				admin_url( 'admin.php' )
			)
		);
		exit;
	}

	/**
	 * Render a form with its action and CSRF nonce.
	 *
	 * @param mixed $operation operation value.
	 * @param mixed $multipart multipart value.
	 * @return mixed Operation result.
	 */
	private function form_start( $operation, $multipart = false ) {
		echo '<form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '"' . ( $multipart ? ' enctype="multipart/form-data" data-filewarden-upload="true"' : '' ) . '>';
		echo '<input type="hidden" name="action" value="filewarden_admin"><input type="hidden" name="operation" value="' . esc_attr( $operation ) . '">';
		wp_nonce_field( 'filewarden_admin' );
	}

	/**
	 * Render the parent and access-policy controls.
	 *
	 * @param mixed $prefix prefix value.
	 * @param mixed $folder folder value.
	 * @return mixed Operation result.
	 */
	private function policy_fields( $prefix, $folder ) {
		echo '<p>';
		$this->folder_picker( $prefix . '-parent', $folder, __( 'Destination folder', 'filewarden' ) );
		echo '</p>';
		echo '<p><label for="' . esc_attr( $prefix ) . '-policy">' . esc_html__( 'Access policy', 'filewarden' ) . '</label> <select name="policy_type" id="' . esc_attr( $prefix ) . '-policy"><option value="authenticated">' . esc_html__( 'Authenticated users', 'filewarden' ) . '</option><option value="roles">' . esc_html__( 'Selected roles', 'filewarden' ) . '</option><option value="public">' . esc_html__( 'Public', 'filewarden' ) . '</option></select></p>';
		echo '<fieldset><legend>' . esc_html__( 'Roles (only for selected-role policies)', 'filewarden' ) . '</legend>';
		foreach ( wp_roles()->roles as $key => $role ) {
			echo '<label><input type="checkbox" name="roles[]" value="' . esc_attr( $key ) . '"> ' . esc_html( translate_user_role( $role['name'] ) ) . '</label> ';
		}
		echo '</fieldset><p>' . esc_html__( 'Every parent folder also restricts access.', 'filewarden' ) . '</p>';
	}

	/**
	 * Render visible folders by name, including their hierarchy.
	 *
	 * @param string $id Input ID.
	 * @param int    $folder Selected folder.
	 * @param string $label Input label.
	 */
	private function folder_picker( $id, $folder, $label ) {
		echo '<label for="' . esc_attr( $id ) . '">' . esc_html( $label ) . '</label> <select id="' . esc_attr( $id ) . '" name="folder_id">';
		$this->folder_labels ??= $this->service->folder_choices();
		foreach ( $this->folder_labels as $value => $name ) {
			echo '<option value="' . esc_attr( $value ) . '"' . selected( $folder, $value, false ) . '>' . esc_html( $name ) . '</option>';
		}
		echo '</select>';
	}

	/**
	 * Render authorized diagnostics and foundation controls.
	 *
	 * @return mixed Operation result.
	 */
	public function page() {
		if ( ! current_user_can( 'filewarden_manage_settings' ) ) {
			return;
		}
		echo '<div class="wrap filewarden-admin"><h1>FileWarden — ' . esc_html( FILEWARDEN_VERSION ) . '</h1><p>' . esc_html__( 'Development foundation. Do not upload confidential documents before deployment security checks.', 'filewarden' ) . '</p>';
		$messages = array(
			'success'      => __( 'Operation completed.', 'filewarden' ),
			'duplicate'    => __( 'A file with this name exists. Rename it or cancel. Versions arrive in a later milestone.', 'filewarden' ),
			'failed'       => __( 'Operation refused or unavailable. Check permissions, inputs and storage configuration.', 'filewarden' ),
			'storage'      => __( 'The file could not be written or read back from protected storage. No document was added.', 'filewarden' ),
			'database'     => __( 'FileWarden could not confirm that the document was recorded in the selected folder. Check the list before retrying.', 'filewarden' ),
			'audit'        => __( 'The file was saved, but the audit entry could not be confirmed. Check the folder before retrying.', 'filewarden' ),
			'busy'         => __( 'Another operation is using this folder. Try again in a moment.', 'filewarden' ),
			'denied'       => __( 'You do not have permission to add a file in the selected folder.', 'filewarden' ),
			'size'         => __( 'The file is empty or exceeds the allowed upload size. Check FileWarden settings and your PHP upload limit.', 'filewarden' ),
			'format'       => __( 'Choose a valid PDF, JPEG or PNG file.', 'filewarden' ),
			'missing_file' => __( 'No complete upload was received. Select the file again and check the server upload limit.', 'filewarden' ),
			'media'        => __( 'Choose a local Media Library file that you are allowed to edit.', 'filewarden' ),
			'invalid'      => __( 'Check the folder, access policy and form fields.', 'filewarden' ),
		);
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only navigation; authorization is independently enforced.
		$result = isset( $_GET['result'] ) && is_string( $_GET['result'] ) ? sanitize_key( $_GET['result'] ) : '';
		if ( isset( $messages[ $result ] ) ) {
			echo '<div role="status" class="notice ' . ( 'success' === $result ? 'notice-success' : 'notice-error' ) . '"><p>' . esc_html( $messages[ $result ] ) . '</p></div>';
		}
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only confirmation; document access is checked again below.
		$created = isset( $_GET['created'] ) && is_string( $_GET['created'] ) ? sanitize_text_field( wp_unslash( $_GET['created'] ) ) : '';
		if ( 'success' === $result && wp_is_uuid( $created ) ) {
			try {
				$document = $this->service->metadata( $this->service->get( $created ) );
			} catch ( \Throwable $error ) {
				$document = null;
			}
			if ( $document ) {
				$this->folder_labels ??= $this->service->folder_choices();
				$destination           = $this->folder_labels[ $document['folder_id'] ] ?? __( 'Selected folder', 'filewarden' );
				/* translators: 1: document name, 2: folder path. */
				$confirmation = sprintf( __( 'File %1$s saved and verified in %2$s.', 'filewarden' ), $document['name'], $destination );
				echo '<div class="notice notice-success"><p>' . esc_html( $confirmation ) . ' <a href="' . esc_url( $document['detail_url'] ) . '">' . esc_html__( 'Open document', 'filewarden' ) . '</a></p></div>';
			}
		}
		echo '<details class="filewarden-diagnostics"><summary>' . esc_html__( 'Storage diagnostics', 'filewarden' ) . '</summary>';
		$storage_ready = false;
		try {
			$this->service->storage->root();
			$storage_ready = true;
			echo '<p>' . esc_html__( 'Storage directory checks passed.', 'filewarden' ) . '</p>';
			echo '<p>' . esc_html__( 'FileWarden folders are virtual. In managed mode, all encrypted files are stored in wp-content/filewarden-storage under opaque names.', 'filewarden' ) . '</p>';
		} catch ( \Throwable $error ) {
			$message = $error instanceof \RuntimeException ? $error->getMessage() : __( 'Unexpected storage error.', 'filewarden' );
			echo '<div class="notice notice-error inline"><p>' . esc_html__( 'Storage blocked.', 'filewarden' ) . ' ' . esc_html( $message ) . '</p></div>';
		}
		echo '</details>';
		if ( ! $storage_ready ) {
			echo '<div class="notice notice-error inline"><p>' . esc_html__( 'Storage blocked. Open Storage diagnostics for details.', 'filewarden' ) . '</p></div>';
		}
		try {
			// phpcs:ignore WordPress.Security.NonceVerification.Recommended,WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Read-only navigation; authorization is independently enforced; integer() strictly validates scalar nonnegative integers.
			$folder = RestController::integer( wp_unslash( $_GET['folder_id'] ?? null ) );
			// phpcs:ignore WordPress.Security.NonceVerification.Recommended,WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Read-only navigation; authorization is independently enforced; integer() strictly validates scalar nonnegative integers.
			$offset = RestController::integer( wp_unslash( $_GET['offset'] ?? null ) );
			$this->service->require_folder( $folder );
		} catch ( \Throwable $error ) {
			echo '<p>' . esc_html__( 'Folder unavailable.', 'filewarden' ) . '</p></div>';
			return;
		}
		$folders               = $this->service->repository->folders( $folder, $offset );
		$documents             = $this->service->repository->documents( $folder, $offset );
		$this->folder_labels ??= $this->service->folder_choices();
		echo '<h2>' . esc_html__( 'Documents in', 'filewarden' ) . ' ' . esc_html( $this->folder_labels[ $folder ] ?? '' ) . '</h2><form method="get"><input type="hidden" name="page" value="filewarden">';
		$this->folder_picker( 'fw-browse-folder', $folder, __( 'Current folder', 'filewarden' ) );
		submit_button( __( 'Open folder', 'filewarden' ), 'secondary', 'submit', false );
		echo '</form><p><a class="button button-primary" href="#fw-add-document">' . esc_html__( 'Add a document to this folder', 'filewarden' ) . '</a></p><h3>' . esc_html__( 'Subfolders', 'filewarden' ) . '</h3><ul class="filewarden-folders">';
		foreach ( $folders as $row ) {
			if ( $this->service->permissions->can_read( $row, wp_get_current_user() ) ) {
				$url = add_query_arg(
					array(
						'page'      => 'filewarden',
						'folder_id' => $row['id'],
					),
					admin_url( 'admin.php' )
				);
				echo '<li><a href="' . esc_url( $url ) . '">' . esc_html( $row['name'] ) . '</a> (#' . esc_html( $row['id'] ) . ')</li>';
			}
		}
		echo '</ul><h3>' . esc_html__( 'Files in this folder', 'filewarden' ) . '</h3><table class="widefat striped"><thead><tr><th scope="col">' . esc_html__( 'Document', 'filewarden' ) . '</th><th scope="col">' . esc_html__( 'Size', 'filewarden' ) . '</th><th scope="col">' . esc_html__( 'Actions', 'filewarden' ) . '</th></tr></thead><tbody>';
		$visible = false;
		foreach ( $documents as $row ) {
			if ( $this->service->permissions->can_read( $row, wp_get_current_user() ) ) {
				$visible = true;
				$url     = $this->service->metadata( $row )['detail_url'];
				echo '<tr><th scope="row"><a href="' . esc_url( $url ) . '">' . esc_html( $row['name'] ) . '</a></th><td>' . esc_html( size_format( $row['size'] ) ) . '</td><td><a href="' . esc_url( $url ) . '">' . esc_html__( 'Preview', 'filewarden' ) . '</a> · <a href="' . esc_url( add_query_arg( 'download', '1', $url ) ) . '">' . esc_html__( 'Download', 'filewarden' ) . '</a></td></tr>';
			}
		}
		if ( ! $visible ) {
			echo '<tr><td colspan="3">' . esc_html__( 'No visible documents in this folder. Choose another folder or add a file here.', 'filewarden' ) . '</td></tr>';
		}
		echo '</tbody></table>';
		$links = array();
		if ( $offset > 0 ) {
			$links[ max( 0, $offset - 100 ) ] = __( 'Previous batch', 'filewarden' );
		}
		if ( count( $folders ) === 100 || count( $documents ) === 100 ) {
			$links[ $offset + 100 ] = __( 'Next batch', 'filewarden' );
		}
		echo '<p>';
		foreach ( $links as $next => $label ) {
			$url = add_query_arg(
				array(
					'page'      => 'filewarden',
					'folder_id' => $folder,
					'offset'    => $next,
				),
				admin_url( 'admin.php' )
			);
			echo '<a href="' . esc_url( $url ) . '">' . esc_html( $label ) . '</a> ';
		}
		echo '</p>';
		$publish = add_query_arg(
			array(
				'page'      => 'filewarden-publication',
				'folder_id' => $folder,
			),
			admin_url( 'admin.php' )
		);
		echo '<p><a class="button" href="' . esc_url( $publish ) . '">' . esc_html__( 'Publish this folder', 'filewarden' ) . '</a></p>';
		if ( current_user_can( 'filewarden_manage_permissions' ) ) {
			echo '<h2>' . esc_html__( 'Create folder', 'filewarden' ) . '</h2>';
			$this->form_start( 'folder' );
			echo '<p><label for="fw-name">' . esc_html__( 'Folder name', 'filewarden' ) . '</label> <input id="fw-name" name="name" maxlength="255" required></p>';
			$this->policy_fields( 'folder', $folder );
			submit_button( __( 'Create folder', 'filewarden' ) );
			echo '</form>';
			if ( current_user_can( 'filewarden_upload_files' ) ) {
				echo '<h2 id="fw-add-document">' . esc_html__( 'Upload PDF, JPEG or PNG', 'filewarden' ) . '</h2>';
				if ( $storage_ready ) {
					$this->form_start( 'upload', true );
					/* translators: %s: effective maximum file size. */
					echo '<p>' . esc_html( sprintf( __( 'Maximum file size: %s. Saving is confirmed only after storage and destination verification.', 'filewarden' ), size_format( min( max( 1, (int) get_option( 'filewarden_upload_mb', 50 ) ) * MB_IN_BYTES, wp_max_upload_size() ) ) ) ) . '</p>';
					echo '<p><label for="fw-file">' . esc_html__( 'File', 'filewarden' ) . '</label> <input type="file" id="fw-file" name="file" accept=".pdf,.jpg,.jpeg,.png" required></p>';
					$this->policy_fields( 'upload', $folder );
					submit_button( __( 'Upload', 'filewarden' ) );
					echo '</form>';
					if ( current_user_can( 'upload_files' ) ) {
						$this->import_form( $folder );
					}
				} else {
					echo '<p>' . esc_html__( 'Uploads are unavailable. Follow the storage diagnostic above and reload this page.', 'filewarden' ) . '</p>';
				}
			}
		}
		echo '</div>';
	}

	/**
	 * Render the import form for a selected or newly picked attachment.
	 *
	 * @param int $folder Current folder.
	 */
	private function import_form( $folder ) {
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only preselection; the POST has its own nonce.
		$id    = isset( $_GET['media_id'] ) ? absint( $_GET['media_id'] ) : 0;
		$title = $id && 'attachment' === get_post_type( $id ) && current_user_can( 'edit_post', $id ) ? get_the_title( $id ) : '';
		echo '<h2 id="filewarden-import">' . esc_html__( 'Import from Media Library', 'filewarden' ) . '</h2>';
		echo '<p>' . esc_html__( 'FileWarden creates a protected copy. The original stays in WordPress Media and may remain publicly accessible.', 'filewarden' ) . '</p>';
		$this->form_start( 'import' );
		echo '<input type="hidden" id="fw-media-id" name="media_id" value="' . esc_attr( $title ? $id : 0 ) . '">';
		echo '<p><button type="button" class="button" id="fw-media-picker">' . esc_html__( 'Choose a media file', 'filewarden' ) . '</button> <span id="fw-media-title" role="status">' . esc_html( $title ) . '</span></p>';
		$this->policy_fields( 'import', $folder );
		submit_button( __( 'Copy to FileWarden', 'filewarden' ) );
		echo '</form>';
	}

	/**
	 * Show the shortcode and its current-session preview.
	 */
	public function publish_page() {
		if ( ! current_user_can( 'filewarden_manage_settings' ) ) {
			return;
		}
		echo '<div class="wrap"><h1>' . esc_html__( 'FileWarden publication', 'filewarden' ) . '</h1>';
		try {
			// phpcs:ignore WordPress.Security.NonceVerification.Recommended,WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Read-only navigation; integer() validates input.
			$folder = RestController::integer( wp_unslash( $_GET['folder_id'] ?? null ) );
			$this->service->require_folder( $folder );
			echo '<form method="get"><input type="hidden" name="page" value="filewarden-publication">';
			$this->folder_picker( 'fw-publish-folder', $folder, __( 'Folder to publish', 'filewarden' ) );
			submit_button( __( 'Show shortcode', 'filewarden' ), 'secondary', 'submit', false );
			echo '</form><p><label for="fw-shortcode">' . esc_html__( 'Copy into the Elementor Shortcode widget', 'filewarden' ) . '</label> <input type="text" readonly id="fw-shortcode" value="' . esc_attr( '[filewarden folder="' . $folder . '"]' ) . '"></p>';
			// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- The renderer escapes all dynamic frame attributes.
			echo Publication::shortcode( array( 'folder' => $folder ) );
		} catch ( \Throwable $error ) {
			echo '<p>' . esc_html__( 'Folder unavailable.', 'filewarden' ) . '</p>';
		}
		echo '</div>';
	}

	/**
	 * Render settings in their own administration screen.
	 */
	public function settings_page() {
		if ( ! current_user_can( 'filewarden_manage_settings' ) ) {
			return;
		}
		echo '<div class="wrap"><h1>' . esc_html__( 'FileWarden settings', 'filewarden' ) . '</h1>';
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only notice from the POST redirect.
		if ( isset( $_GET['result'] ) && 'success' === $_GET['result'] ) {
			echo '<div class="notice notice-success"><p>' . esc_html__( 'Settings saved.', 'filewarden' ) . '</p></div>';
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only notice from the POST redirect.
		} elseif ( isset( $_GET['result'] ) ) {
			echo '<div class="notice notice-error"><p>' . esc_html__( 'Settings could not be saved.', 'filewarden' ) . '</p></div>';
		}
		$this->form_start( 'settings' );
		echo '<p><label for="fw-size">' . esc_html__( 'Upload limit (MB, also bounded by server limits)', 'filewarden' ) . '</label> <input type="number" id="fw-size" name="upload_mb" min="1" max="1024" value="' . esc_attr( get_option( 'filewarden_upload_mb', 50 ) ) . '" required></p>';
		echo '<p><label for="fw-days">' . esc_html__( 'Audit retention (days)', 'filewarden' ) . '</label> <input type="number" id="fw-days" name="audit_days" min="1" max="3650" value="' . esc_attr( get_option( 'filewarden_audit_days', 365 ) ) . '" required></p>';
		submit_button( __( 'Save settings', 'filewarden' ) );
		echo '</form></div>';
	}

	/**
	 * Render the restricted audit screen.
	 */
	public function audit_page() {
		if ( current_user_can( 'filewarden_view_audit' ) ) {
			echo '<div class="wrap"><h1>' . esc_html__( 'Recent audit events', 'filewarden' ) . '</h1><ul>';
			foreach ( $this->service->audit->recent() as $row ) {
				echo '<li>' . esc_html( $row['created_at'] . ' UTC — ' . $row['event'] . ' — user #' . $row['actor_id'] . ' — object #' . $row['object_id'] ) . '</li>';
			}
			echo '</ul></div>';
		}
	}
}
