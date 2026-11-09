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
		if ( false === strpos( $hook, 'filewarden' ) ) {
			return;
		}
		wp_enqueue_style( 'filewarden-admin', plugins_url( 'assets/admin.css', FILEWARDEN_PATH . 'filewarden.php' ), array(), FILEWARDEN_VERSION );
		if ( 'toplevel_page_filewarden' !== $hook ) {
			if ( 'filewarden_page_filewarden-publication' === $hook ) {
				wp_enqueue_script( 'filewarden-publisher', plugins_url( 'assets/publisher.js', FILEWARDEN_PATH . 'filewarden.php' ), array(), FILEWARDEN_VERSION, true ); }
			return;
		}
		wp_enqueue_script( 'filewarden-preview', plugins_url( 'assets/preview.js', FILEWARDEN_PATH . 'filewarden.php' ), array(), FILEWARDEN_VERSION, true );
		wp_localize_script(
			'filewarden-preview',
			'filewardenPreview',
			array(
				'title' => __( 'Document preview', 'filewarden' ),
				'close' => __( 'Close preview', 'filewarden' ),
			)
		);
		wp_enqueue_script( 'filewarden-workspace', plugins_url( 'assets/workspace.js', FILEWARDEN_PATH . 'filewarden.php' ), array(), FILEWARDEN_VERSION, true );
		wp_enqueue_script( 'filewarden-upload', plugins_url( 'assets/admin.js', FILEWARDEN_PATH . 'filewarden.php' ), array(), FILEWARDEN_VERSION, true );
		wp_localize_script(
			'filewarden-upload',
			'filewardenUpload',
			array(
				'sending'   => __( 'Sending file…', 'filewarden' ),
				'verifying' => __( 'Transfer complete. Encrypting and verifying the document in its destination folder…', 'filewarden' ),
				'failed'    => __( 'The server did not confirm saving the file. Check the folder before retrying. HTTP status:', 'filewarden' ),
				'network'   => __( 'Connection interrupted. Check the folder before retrying; the server may still be processing the file.', 'filewarden' ),
				'media'     => __( 'Choose a file from the Media Library first.', 'filewarden' ),
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
			) . '#fw-add-document';
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
		$folder    = 0;
		$page      = 'filewarden';
		$created   = null;
		$operation = '';
		try {
			$this->service->require_capability( 'filewarden_manage_settings' );
			check_admin_referer( 'filewarden_admin' );
			$operation = isset( $_POST['operation'] ) && is_string( $_POST['operation'] ) ? sanitize_key( $_POST['operation'] ) : '';
			if ( 'add_document' === $operation ) {
				$source = isset( $_POST['source'] ) && is_string( $_POST['source'] ) ? sanitize_key( $_POST['source'] ) : '';
				if ( ! in_array( $source, array( 'upload', 'import' ), true ) ) {
					throw new \InvalidArgumentException( 'Invalid document source.' );
				}
				$operation = $source;
			}
			if ( 'settings' === $operation ) {
				$page = 'filewarden-settings';
				// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- integer() strictly validates scalar nonnegative integers.
				$size = RestController::integer( wp_unslash( $_POST['upload_mb'] ?? null ) );
				// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- integer() strictly validates scalar nonnegative integers.
				$days = RestController::integer( wp_unslash( $_POST['audit_days'] ?? null ) );
				if ( $size < 1 || $size > 1024 || $days < 1 || $days > 3650 ) {
					throw new \InvalidArgumentException( 'Invalid settings.' );
				}
                // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Strict integer validation.
				$trash_days = RestController::integer( wp_unslash( $_POST['trash_days'] ?? '30' ) );
				if ( $trash_days < 1 || $trash_days > 3650 ) {
					throw new \InvalidArgumentException( 'Invalid retention.' ); }
				$this->service->audit->record( 'settings_changed' );
				update_option( 'filewarden_upload_mb', $size );
				update_option( 'filewarden_audit_days', $days );
				update_option( 'filewarden_trash_days', $trash_days );
			} else {
				$type   = isset( $_POST['policy_type'] ) && is_string( $_POST['policy_type'] ) ? sanitize_key( $_POST['policy_type'] ) : '';
				$roles  = isset( $_POST['roles'] ) && is_array( $_POST['roles'] ) ? array_map( 'sanitize_key', wp_unslash( $_POST['roles'] ) ) : array();
				$policy = array(
					'type'  => $type,
					'roles' => 'roles' === $type ? $roles : array(),
				);
				// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- integer() strictly validates scalar nonnegative integers.
				$folder = RestController::integer( wp_unslash( $_POST['folder_id'] ?? null ) );
				if ( in_array( $operation, array( 'move', 'copy', 'trash', 'restore' ), true ) ) {
					$uuid = isset( $_POST['document'] ) && is_string( $_POST['document'] ) ? sanitize_text_field( wp_unslash( $_POST['document'] ) ) : '';
					if ( ! wp_is_uuid( $uuid, 4 ) ) {
						throw new \InvalidArgumentException( 'Invalid document.' ); }
					$lifecycle = new Lifecycle( $this->service );
					if ( in_array( $operation, array( 'trash', 'restore' ), true ) ) {
						$folder = $lifecycle->trash( $uuid, 'restore' === $operation );
					} else {
                        // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Strict integer validation.
						$destination = RestController::integer( wp_unslash( $_POST['destination_id'] ?? null ) );
						$name        = isset( $_POST['name'] ) && is_string( $_POST['name'] ) ? sanitize_file_name( wp_unslash( $_POST['name'] ) ) : '';
						$proof       = isset( $_POST['proof'] ) && is_string( $_POST['proof'] ) ? sanitize_text_field( wp_unslash( $_POST['proof'] ) ) : '';
						$created     = $lifecycle->transfer( $operation, $uuid, $destination, $name, $proof );
						$folder      = $destination;
					}
				} elseif ( 'delete_folder' === $operation ) {
					FolderLifecycle::remove( $this->service, $folder );
					$folder = 0;
				} elseif ( 'folder_policy' === $operation ) {
					$folder = $this->service->change_policy( 'folder', $folder, $policy );
				} elseif ( 'document_policy' === $operation ) {
					$uuid = isset( $_POST['document'] ) && is_string( $_POST['document'] ) ? sanitize_text_field( wp_unslash( $_POST['document'] ) ) : '';
					if ( ! wp_is_uuid( $uuid, 4 ) ) {
						throw new \InvalidArgumentException( 'Invalid document.' ); }
					$folder = $this->service->change_policy( 'document', $uuid, $policy );
				} elseif ( 'folder' === $operation ) {
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
			$result = in_array( $operation, array( 'folder_policy', 'document_policy' ), true ) ? 'policy_saved' : 'success';
		} catch ( \UnexpectedValueException $error ) {
			$result = 'duplicate';
		} catch ( \DomainException $error ) {
			$result = 'denied';
		} catch ( \InvalidArgumentException $error ) {
			$result = match ( $error->getMessage() ) {
				'Destination rules changed. Review again.' => 'review',
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
					'page'       => $page,
					'folder_id'  => $folder,
					'result'     => $result,
					'created'    => $created['id'] ?? false,
					'collection' => 0 === $folder ? 'unfiled' : false,
					'tab'        => ! in_array( $result, array( 'success', 'policy_saved' ), true ) && in_array( $operation, array( 'upload', 'import' ), true ) ? 'add' : 'documents',
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
	public function form_start( $operation, $multipart = false ) {
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
	public function policy_fields( $prefix, $folder ) {
		echo '<p>';
		$this->folder_picker( $prefix . '-parent', $folder, __( 'Destination folder', 'filewarden' ) );
		echo '</p>';
		$document = 'document' === $prefix;
		$this->policy_controls(
			$prefix,
			array(
				'type'  => $document || $folder > 0 ? 'inherit' : 'authenticated',
				'roles' => array(),
			),
			true
		);
	}

	/**
	 * Render policy controls shared by creation and editing forms.
	 *
	 * @param string $prefix Unique input prefix.
	 * @param array  $policy Current explicit rule.
	 * @param bool   $document Whether inheritance may be selected.
	 */
	public function policy_controls( $prefix, array $policy, $document ) {
		$choices  = $document ? array( 'inherit' => __( 'Inherit from folder', 'filewarden' ) ) : array();
		$choices += array(
			'public'        => __( 'Public', 'filewarden' ),
			'authenticated' => __( 'Authenticated users', 'filewarden' ),
			'roles'         => __( 'Selected roles', 'filewarden' ),
		);
		echo '<p><label for="' . esc_attr( $prefix ) . '-policy">' . esc_html__( 'Access policy', 'filewarden' ) . '</label> <select name="policy_type" id="' . esc_attr( $prefix ) . '-policy">';
		foreach ( $choices as $value => $label ) {
			echo '<option value="' . esc_attr( $value ) . '"' . selected( $policy['type'] ?? '', $value, false ) . '>' . esc_html( $label ) . '</option>';
		}
		echo '</select></p><fieldset class="fw-role-options"><legend>' . esc_html__( 'Roles (only for selected-role policies)', 'filewarden' ) . '</legend>';
		foreach ( wp_roles()->roles as $key => $role ) {
			echo '<label><input type="checkbox" name="roles[]" value="' . esc_attr( $key ) . '"' . checked( in_array( $key, $policy['roles'] ?? array(), true ), true, false ) . '> ' . esc_html( translate_user_role( $role['name'] ) ) . '</label> ';
		}
		echo '</fieldset><p class="description">' . esc_html__( 'The most restrictive rules apply: those of the file and its folder. A Public file stays private when its folder is restricted.', 'filewarden' ) . '</p>';
		if ( $document ) {
			echo '<p class="description">' . esc_html__( 'Inherit: automatically follow the folder rules.', 'filewarden' ) . '</p>';
		}
	}

	/**
	 * Render visible folders by name, including their hierarchy.
	 *
	 * @param string $id Input ID.
	 * @param int    $folder Selected folder.
	 * @param string $label Input label.
	 */
	public function folder_picker( $id, $folder, $label ) {
		echo '<label for="' . esc_attr( $id ) . '">' . esc_html( $label ) . '</label> <select id="' . esc_attr( $id ) . '" name="folder_id">';
		$this->folder_labels ??= $this->service->folder_choices();
		foreach ( $this->folder_labels as $value => $name ) {
			if ( 0 === $value && 'folder-parent' !== $id ) {
				continue; }
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
			return; }
		( new AdminWorkspace( $this->service, $this ) )->render();
	}

	/**
	 * Render one document form with a shared destination and two source options.
	 *
	 * @param int $folder Current folder.
	 */
	public function document_form( $folder ) {
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only preselection; the POST has its own nonce.
		$id    = isset( $_GET['media_id'] ) ? absint( $_GET['media_id'] ) : 0;
		$title = current_user_can( 'upload_files' ) && $id && 'attachment' === get_post_type( $id ) && current_user_can( 'edit_post', $id ) ? get_the_title( $id ) : '';
		$this->form_start( 'add_document', true );
		echo '<fieldset class="filewarden-sources"><legend>' . esc_html__( 'Choose a source', 'filewarden' ) . '</legend>';
		echo '<label><input type="radio" name="source" value="upload"' . checked( ! $title, true, false ) . '> ' . esc_html__( 'From my computer', 'filewarden' ) . '</label>';
		if ( current_user_can( 'upload_files' ) ) {
			echo '<label><input type="radio" name="source" value="import"' . checked( (bool) $title, true, false ) . '> ' . esc_html__( 'From the Media Library', 'filewarden' ) . '</label>';
		}
		echo '</fieldset><fieldset data-filewarden-source="upload"><legend>' . esc_html__( 'Choose a PDF, JPEG or PNG', 'filewarden' ) . '</legend>';
		/* translators: %s: effective maximum file size. */
		echo '<p>' . esc_html( sprintf( __( 'Maximum file size: %s. Saving is confirmed only after storage and destination verification.', 'filewarden' ), size_format( min( max( 1, (int) get_option( 'filewarden_upload_mb', 50 ) ) * MB_IN_BYTES, wp_max_upload_size() ) ) ) ) . '</p>';
		echo '<p><label for="fw-file">' . esc_html__( 'File', 'filewarden' ) . '</label> <input type="file" id="fw-file" name="file" accept=".pdf,.jpg,.jpeg,.png"></p></fieldset>';
		if ( current_user_can( 'upload_files' ) ) {
			echo '<fieldset data-filewarden-source="import"><legend>' . esc_html__( 'Choose an existing document', 'filewarden' ) . '</legend>';
			echo '<p>' . esc_html__( 'FileWarden creates a protected copy. The original stays in WordPress Media and may remain publicly accessible.', 'filewarden' ) . '</p>';
			echo '<input type="hidden" id="fw-media-id" name="media_id" value="' . esc_attr( $title ? $id : 0 ) . '">';
			echo '<p><button type="button" class="button" id="fw-media-picker">' . esc_html__( 'Choose a media file', 'filewarden' ) . '</button> <span id="fw-media-title" role="status">' . esc_html( $title ) . '</span></p></fieldset>';
		}
		$this->policy_fields( 'document', $folder );
		submit_button( __( 'Add to this folder', 'filewarden' ), 'primary', 'filewarden_save' );
		echo '</form>';
	}

	/**
	 * Show the shortcode and its current-session preview.
	 */
	public function publish_page() {
		Publisher::page( $this->service );
	}

	/**
	 * Render settings in their own administration screen.
	 */
	public function settings_page() {
		if ( ! current_user_can( 'filewarden_manage_settings' ) ) {
			return;
		}
		echo '<div class="wrap fw-workspace fw-settings"><header class="fw-heading"><h1>' . esc_html__( 'FileWarden settings', 'filewarden' ) . '</h1></header>';
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only notice from the POST redirect.
		if ( isset( $_GET['result'] ) && 'success' === $_GET['result'] ) {
			echo '<div class="notice notice-success"><p>' . esc_html__( 'Settings saved.', 'filewarden' ) . '</p></div>';
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only notice from the POST redirect.
		} elseif ( isset( $_GET['result'] ) ) {
			echo '<div class="notice notice-error"><p>' . esc_html__( 'Settings could not be saved.', 'filewarden' ) . '</p></div>';
		}
		echo '<section class="fw-content filewarden-diagnostics"><h2>' . esc_html__( 'Storage diagnostics', 'filewarden' ) . '</h2>';
		try {
			$this->service->storage->root();
			echo '<p>' . esc_html__( 'Storage directory checks passed.', 'filewarden' ) . '</p>';
			echo '<p>' . esc_html__( 'FileWarden folders are virtual. In managed mode, all encrypted files are stored in wp-content/filewarden-storage under opaque names.', 'filewarden' ) . '</p>';
		} catch ( \Throwable $error ) {
			$message = $error instanceof \RuntimeException ? $error->getMessage() : __( 'Unexpected storage error.', 'filewarden' );
			echo '<div class="notice notice-error inline"><p>' . esc_html__( 'Storage blocked.', 'filewarden' ) . ' ' . esc_html( $message ) . '</p></div>';
		}
		echo '</section><section class="fw-content"><h2>' . esc_html__( 'Limits and retention', 'filewarden' ) . '</h2>';
		$this->form_start( 'settings' );
		echo '<p><label for="fw-size">' . esc_html__( 'Upload limit (MB, also bounded by server limits)', 'filewarden' ) . '</label> <input type="number" id="fw-size" name="upload_mb" min="1" max="1024" value="' . esc_attr( get_option( 'filewarden_upload_mb', 50 ) ) . '" required></p>';
		echo '<p><label for="fw-days">' . esc_html__( 'Audit retention (days)', 'filewarden' ) . '</label> <input type="number" id="fw-days" name="audit_days" min="1" max="3650" value="' . esc_attr( get_option( 'filewarden_audit_days', 365 ) ) . '" required></p>';
		echo '<p><label for="fw-trash-days">' . esc_html__( 'Trash retention (days)', 'filewarden' ) . '</label> <input type="number" id="fw-trash-days" name="trash_days" min="1" max="3650" value="' . esc_attr( get_option( 'filewarden_trash_days', 30 ) ) . '" required></p><p class="description">' . esc_html__( 'Expired trashed files are permanently removed by scheduled cleanup. Active documents are never removed by this setting.', 'filewarden' ) . '</p>';
		submit_button( __( 'Save settings', 'filewarden' ) );
		echo '</form></section></div>';
	}

	/**
	 * Render the restricted audit screen.
	 */
	public function audit_page() {
		AuditConsole::page();
	}
}
