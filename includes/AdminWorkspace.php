<?php
/**
 * Task-oriented document workspace with contextual actions.
 *
 * @package FileWarden
 */

namespace FileWarden;

/** Server-rendered navigation works independently of JavaScript. */
final class AdminWorkspace {
	/**
	 * Shared authorization and document operations.
	 *
	 * @var DocumentService
	 */
	private $service;
	/**
	 * Shared forms.
	 *
	 * @var AdminController
	 */
	private $ui;
	/**
	 * Selected folder.
	 *
	 * @var int
	 */
	private $folder = 0;
	/**
	 * Active task.
	 *
	 * @var string
	 */
	private $tab = 'documents';

	/**
	 * Initialize the workspace.
	 *
	 * @param DocumentService $service Shared services.
	 * @param AdminController $ui Shared forms.
	 */
	public function __construct( DocumentService $service, AdminController $ui ) {
		$this->service = $service;
		$this->ui      = $ui;
	}

	/**
	 * Read a scalar display parameter; never authorizes an operation.
	 *
	 * @param string $key Query field.
	 * @param string $fallback Default value.
	 * @return string Sanitized scalar.
	 */
	private function input( $key, $fallback = '' ) {
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only display; each object is authorized separately.
		return isset( $_GET[ $key ] ) && is_string( $_GET[ $key ] ) ? sanitize_text_field( wp_unslash( $_GET[ $key ] ) ) : $fallback;
	}

	/**
	 * Build a local administration URL.
	 *
	 * @param array $args Overrides.
	 * @return string URL.
	 */
	private function url( array $args = array() ) {
		return add_query_arg(
			array_merge(
				array(
					'page'       => 'filewarden',
					'folder_id'  => $this->folder,
					'tab'        => $this->tab,
					'collection' => $this->input( 'collection' ) === 'unfiled' ? 'unfiled' : false,
				),
				$args
			),
			admin_url( 'admin.php' )
		);
	}

	/**
	 * Render the selected task and an optional action inspector.
	 *
	 * @throws \DomainException Caught locally for unavailable tasks.
	 */
	public function render() {
		ob_start();
		try {
			$this->folder = RestController::integer( $this->input( 'folder_id', '0' ) );
			$this->service->require_folder( $this->folder );
			$this->tab = $this->input( 'tab', 'documents' );
			if ( ! in_array( $this->tab, array( 'documents', 'add', 'folder', 'trash' ), true ) ) {
				$this->tab = 'documents';
			}
			if ( $this->input( 'media_id' ) ) {
				$this->tab = 'add';
			}
			$current = $this->folder ? $this->service->repository->folder( $this->folder ) : array(
				'name'      => 'unfiled' === $this->input( 'collection' ) || 'trash' === $this->tab ? __( 'Documents to classify', 'filewarden' ) : __( 'Your folders', 'filewarden' ),
				'policy'    => array( 'type' => 'authenticated' ),
				'parent_id' => 0,
			);
			echo '<div class="wrap filewarden-admin fw-workspace"><header class="fw-heading"><div><span class="fw-brand">FILEWARDEN</span><h1>' . esc_html__( 'Document library', 'filewarden' ) . '</h1></div><span class="fw-version">' . esc_html( FILEWARDEN_VERSION ) . '</span></header>';
			$this->notices();
			$tabs = array( 'documents' => __( 'Documents', 'filewarden' ) );
			if ( current_user_can( 'filewarden_upload_files' ) && current_user_can( 'filewarden_manage_permissions' ) ) {
				$tabs['add'] = __( 'Add documents', 'filewarden' );
			}
			if ( current_user_can( 'filewarden_manage_permissions' ) ) {
				$tabs['folder'] = __( 'Create folder', 'filewarden' );
			}
			if ( current_user_can( 'filewarden_delete_files' ) ) {
				$tabs['trash'] = __( 'Trash', 'filewarden' );
			}
			echo '<nav class="fw-tabs" aria-label="' . esc_attr__( 'Document tasks', 'filewarden' ) . '">';
			foreach ( $tabs as $tab => $label ) {
				echo '<a href="' . esc_url( $this->url( array( 'tab' => $tab ) ) ) . '"' . ( $tab === $this->tab ? ' aria-current="page"' : '' ) . '>' . esc_html( $label ) . '</a>';
			}
			echo '</nav>';
			if ( ! isset( $tabs[ $this->tab ] ) ) {
				throw new \DomainException( 'Access denied.' );
			}
			$panel = $this->input( 'panel' );
			echo '<div class="fw-layout' . ( $panel ? ' has-panel' : '' ) . '"><aside class="fw-tree"><h2>' . esc_html__( 'Folders', 'filewarden' ) . '</h2><a class="fw-root fw-unfiled" href="' . esc_url(
				$this->url(
					array(
						'folder_id'  => 0,
						'collection' => 'unfiled',
					)
				)
			) . '">' . esc_html__( 'Documents to classify', 'filewarden' ) . '</a>';
			$this->tree( 0, array() );
			if ( isset( $tabs['folder'] ) ) {
				echo '<a class="fw-tree-create" href="' . esc_url( $this->url( array( 'tab' => 'folder' ) ) ) . '">+ ' . esc_html__( 'Create folder', 'filewarden' ) . '</a>';
			}
			echo '</aside><section class="fw-content"><div class="fw-folder-heading"><div><span class="fw-eyebrow">' . esc_html__( 'Current folder', 'filewarden' ) . '</span><h2>' . esc_html( $current['name'] ) . '</h2></div><div class="filewarden-access"><span class="filewarden-policy">' . esc_html( $this->folder ? AccessSummary::effective( $current, $this->service->repository )['label'] : __( 'Not published', 'filewarden' ) ) . '</span>';
			if ( $this->folder && current_user_can( 'filewarden_manage_permissions' ) ) {
				echo '<a class="button" href="' . esc_url( $this->url( array( 'panel' => 'folder_rights' ) ) ) . '">' . esc_html__( 'Edit access rules', 'filewarden' ) . '</a>';
			}
			if ( $this->folder && $this->can_action( 'delete_folder' ) ) {
				echo '<a class="button" href="' . esc_url( $this->url( array( 'panel' => 'delete_folder' ) ) ) . '">' . esc_html__( 'Delete folder', 'filewarden' ) . '</a>'; }
			echo '</div></div>';
			if ( 'documents' === $this->tab || 'trash' === $this->tab ) {
				if ( ! $this->folder && 'unfiled' !== $this->input( 'collection' ) && 'documents' === $this->tab ) {
					echo '<p>' . esc_html__( 'Choose a folder or create your first folder to add documents.', 'filewarden' ) . '</p><div class="fw-folder-cards">';
					$this->tree( 0, array() );
					echo '</div>';
				} else {
					$this->listing(); }
			} elseif ( 'add' === $this->tab ) {
				echo '<section class="fw-task"><h2>' . esc_html__( 'Add documents', 'filewarden' ) . '</h2><p>' . esc_html__( 'Choose a source, then check the destination and access rules.', 'filewarden' ) . '</p>';
				try {
					$this->service->storage->root();
					$this->ui->document_form( $this->folder );
				} catch ( \Throwable $error ) {
					echo '<p class="fw-warning">' . esc_html__( 'Storage blocked. Check FileWarden settings before uploading.', 'filewarden' ) . '</p>';
				}
				echo '</section>';
			} else {
				echo '<section class="fw-task"><h2>' . esc_html__( 'Create folder', 'filewarden' ) . '</h2>';
				$this->ui->form_start( 'folder' );
				echo '<p><label for="fw-name">' . esc_html__( 'Folder name', 'filewarden' ) . '</label><input id="fw-name" name="name" maxlength="255" required></p>';
				$this->ui->policy_fields( 'folder', $this->folder );
				submit_button( __( 'Create folder', 'filewarden' ) );
				echo '</form></section>';
			}
			echo '</section>';
			if ( $panel ) {
				$this->panel( $panel, $current );
			}
			echo '</div><p class="fw-footnote">' . esc_html__( 'Development version. Validate deployment security before sharing confidential documents.', 'filewarden' ) . '</p></div>';
			ob_end_flush();
		} catch ( \Throwable $error ) {
			ob_end_clean();
			echo '<div class="notice notice-error"><p>' . esc_html__( 'This folder or action is unavailable. Return to Documents and check your permissions.', 'filewarden' ) . '</p></div>';
		}
	}

	/** Render safe post-operation feedback. */
	private function notices() {
		$result = $this->input( 'result' );
		if ( ! $result ) {
			return;
		}
		$messages = array(
			'policy_saved' => __( 'Access rules saved. The most restrictive rules apply immediately.', 'filewarden' ),
			'success'      => __( 'Operation completed.', 'filewarden' ),
			'duplicate'    => __( 'A file with this name exists. Rename it or cancel. Versions arrive in a later milestone.', 'filewarden' ),
			'failed'       => __( 'Operation refused or unavailable. Check permissions, inputs and storage configuration.', 'filewarden' ),
			'storage'      => __( 'The file could not be written or read back from protected storage. No document was added.', 'filewarden' ),
			'database'     => __( 'FileWarden could not confirm that the document was recorded in the selected folder. Check the list before retrying.', 'filewarden' ),
			'audit'        => __( 'The file was saved, but the audit entry could not be confirmed. Check the folder before retrying.', 'filewarden' ),
			'busy'         => __( 'Another operation is using this folder. Try again in a moment.', 'filewarden' ),
			'denied'       => __( 'You do not have permission to perform this operation.', 'filewarden' ),
			'size'         => __( 'The file is empty or exceeds the allowed upload size. Check FileWarden settings and your PHP upload limit.', 'filewarden' ),
			'format'       => __( 'Choose a valid PDF, JPEG or PNG file.', 'filewarden' ),
			'missing_file' => __( 'No complete upload was received. Select the file again and check the server upload limit.', 'filewarden' ),
			'media'        => __( 'Choose a local Media Library file that you are allowed to edit.', 'filewarden' ),
			'invalid'      => __( 'Check the folder, access policy and form fields.', 'filewarden' ),
		);

		$success = in_array( $result, array( 'success', 'policy_saved' ), true );
		$message = $success ? __( 'Operation completed.', 'filewarden' ) : __( 'Operation refused. Check your permissions, the destination, filename and storage settings.', 'filewarden' );
		$message = $messages[ $result ] ?? $message;
		if ( 'policy_saved' === $result ) {
			$message = __( 'Access rules saved. The most restrictive rules apply immediately.', 'filewarden' );
		} elseif ( 'duplicate' === $result ) {
			$message = __( 'A file with this name already exists. Choose another name or destination.', 'filewarden' );
		} elseif ( 'review' === $result ) {
			$message = __( 'The source or destination rules changed. Review the destination again before confirming.', 'filewarden' );
		}
		echo '<div role="status" class="notice ' . ( $success ? 'notice-success' : 'notice-error' ) . '"><p>' . esc_html( $message ) . '</p></div>';
		$uuid = $this->input( 'created' );
		if ( $success && wp_is_uuid( $uuid, 4 ) ) {
			try {
				$row    = $this->service->get( $uuid );
				$labels = $this->service->folder_choices();
				/* translators: 1: document name, 2: destination path. */
				echo '<div class="notice notice-success"><p>' . esc_html( sprintf( __( 'File %1$s saved and verified in %2$s.', 'filewarden' ), $row['name'], $labels[ (int) $row['folder_id'] ] ?? '' ) ) . '</p></div>';
			} catch ( \Throwable $error ) {
				// Do not reveal a record whose authorization has changed.
				return;
			}
		}
	}

	/**
	 * Render authorized collapsible folders only.
	 *
	 * @param int   $parent_id Parent ID.
	 * @param array $seen Ancestry guard.
	 */
	private function tree( $parent_id, array $seen ) {
		if ( count( $seen ) >= 100 || isset( $seen[ $parent_id ] ) ) {
			return;
		}
		$seen[ $parent_id ] = true;
		echo '<ul>';
		for ( $offset = 0; ; $offset += 100 ) {
			$rows = $this->service->repository->folders( $parent_id, $offset );
			foreach ( $rows as $row ) {
				if ( ! $this->service->permissions->can_read( $row, wp_get_current_user() ) ) {
					continue;
				}
				echo '<li><details open><summary><a href="' . esc_url( $this->url( array( 'folder_id' => (int) $row['id'] ) ) ) . '"' . ( $this->folder === (int) $row['id'] ? ' aria-current="location"' : '' ) . '><span class="fw-folder-icon" aria-hidden="true"></span> ' . esc_html( $row['name'] ) . '</a></summary>';
				$this->tree( (int) $row['id'], $seen );
				echo '</details></li>';
			}
			if ( count( $rows ) < 100 ) {
				break;
			}
		}
		echo '</ul>';
	}

	/** Render authorized file rows and contextual actions. */
	private function listing() {
		$trash  = 'trash' === $this->tab;
		$offset = RestController::integer( $this->input( 'offset', '0' ) );
		$search = substr( $this->input( 'search' ), 0, 200 );
		if ( $trash ) {
			$this->service->require_capability( 'filewarden_delete_files' );
			$rows = $this->service->repository->documents( $this->folder, $offset, true );
			/* translators: %d: trash retention days. */
			echo '<p class="fw-warning">' . esc_html( sprintf( __( 'Trash for this folder. Restore files before %d days; expired files are permanently removed by scheduled cleanup.', 'filewarden' ), (int) get_option( 'filewarden_trash_days', 30 ) ) ) . '</p>';
		} else {
			$rows = Publication::page( $this->service, $this->folder, $search, $offset );
			echo '<form class="fw-search" method="get" action="' . esc_url( admin_url( 'admin.php' ) ) . '"><input type="hidden" name="page" value="filewarden"><input type="hidden" name="collection" value="unfiled"><input type="hidden" name="folder_id" value="' . esc_attr( $this->folder ) . '"><label class="screen-reader-text" for="fw-search">' . esc_html__( 'Search documents', 'filewarden' ) . '</label><input id="fw-search" type="search" name="search" value="' . esc_attr( $search ) . '" placeholder="' . esc_attr__( 'Search this folder', 'filewarden' ) . '"><button class="button">' . esc_html__( 'Search', 'filewarden' ) . '</button></form>';
		}
		echo '<div class="fw-table-wrap"><table class="widefat filewarden-documents"><thead><tr><th scope="col">' . esc_html__( 'Document', 'filewarden' ) . '</th><th scope="col">' . esc_html__( 'Size', 'filewarden' ) . '</th><th scope="col">' . esc_html__( 'File rule', 'filewarden' ) . '</th><th scope="col">' . esc_html__( 'Effective access', 'filewarden' ) . '</th><th scope="col">' . esc_html__( 'Actions', 'filewarden' ) . '</th></tr></thead><tbody>';
		$count = 0;
		$limit = $trash ? 100 : 12;
		foreach ( array_slice( $rows, 0, $limit ) as $row ) {
			$check = $row;
			unset( $check['trashed_at'] );
			if ( ! $this->service->permissions->can_read( $check, wp_get_current_user() ) ) {
				continue;
			}
			++$count;
			if ( ! empty( $row['retained_rules'] ) ) {
				echo '<p class="fw-warning">' . esc_html__( 'Recovered file: restrictions from its former folder remain in force after reclassification.', 'filewarden' ) . '</p>'; }
			$effective = AccessSummary::effective( $row, $this->service->repository );
			$url       = $this->service->metadata( $row )['detail_url'];
			$icon      = plugins_url( 'assets/icons/' . Publication::icon( $row['name'] ) . '.svg', FILEWARDEN_PATH . 'filewarden.php' );
			echo '<tr><th scope="row"><div class="fw-document-name"><img src="' . esc_url( $icon ) . '" width="38" height="44" alt="">';
			if ( $trash ) {
				echo esc_html( $row['name'] );
			} else {
				echo '<a data-filewarden-preview target="_blank" rel="noopener noreferrer" href="' . esc_url( $url ) . '">' . esc_html( $row['name'] ) . '</a>';
			}
			echo '</div></th><td>' . esc_html( size_format( $row['size'] ) ) . '</td><td class="filewarden-rules">' . esc_html( AccessSummary::label( $row['policy'] ) ) . '</td><td><span class="filewarden-policy">' . esc_html( $trash ? __( 'Unavailable in trash', 'filewarden' ) : $effective['label'] ) . '</span>';
			if ( $effective['conflict'] ) {
				echo '<p class="fw-public-warning">' . esc_html__( 'Not public: restricted by its folder.', 'filewarden' ) . '</p>';
			}
			echo '</td><td><div class="fw-row-actions">';
			if ( ! $trash ) {
				echo '<a class="button" data-filewarden-preview target="_blank" rel="noopener noreferrer" href="' . esc_url( $url ) . '" aria-label="' . esc_attr__( 'Preview', 'filewarden' ) . '"><span class="dashicons dashicons-visibility" aria-hidden="true"></span></a><a class="button" href="' . esc_url( add_query_arg( 'download', '1', $url ) ) . '" aria-label="' . esc_attr__( 'Download', 'filewarden' ) . '"><span class="dashicons dashicons-download" aria-hidden="true"></span></a>';
			}
			$actions = $trash ? array( 'restore' => __( 'Restore', 'filewarden' ) ) : array(
				'rights' => __( 'Edit access rules', 'filewarden' ),
				'move'   => __( 'Move', 'filewarden' ),
				'copy'   => __( 'Copy', 'filewarden' ),
				'trash'  => __( 'Move to trash', 'filewarden' ),
			);
			echo '<details class="fw-context"><summary aria-label="' . esc_attr__( 'More actions', 'filewarden' ) . '">⋯</summary><div class="fw-context-menu">';
			foreach ( $actions as $action => $label ) {
				if ( ! $this->can_action( $action ) ) {
					continue;
				}
				echo '<a href="' . esc_url(
					$this->url(
						array(
							'panel'    => $action,
							'document' => $row['uuid'],
						)
					)
				) . '">' . esc_html( $label ) . '</a>';
			}
			echo '</div></details></div></td></tr>';
		}
		if ( ! $count ) {
			echo '<tr><td colspan="5" class="fw-empty">' . esc_html__( 'No documents match this view.', 'filewarden' ) . '</td></tr>';
		}
		echo '</tbody></table></div><nav class="fw-pagination">';
		foreach ( array(
			-1 => __( 'Previous', 'filewarden' ),
			1  => __( 'Next', 'filewarden' ),
		) as $direction => $label ) {
			if ( ( -1 === $direction && $offset > 0 ) || ( 1 === $direction && count( $rows ) >= ( $trash ? 100 : 13 ) ) ) {
				echo '<a class="button" href="' . esc_url(
					$this->url(
						array(
							'offset' => max( 0, $offset + $direction * $limit ),
							'search' => $search,
						)
					)
				) . '">' . esc_html( $label ) . '</a>';
			}
		}
		echo '</nav><p class="fw-explanation">' . esc_html__( 'A file can restrict access, never broaden the access allowed by its folder. Global-read administrators remain an explicit exception.', 'filewarden' ) . '</p>';
		if ( ! $trash && $this->folder ) {
			echo '<a class="button" href="' . esc_url(
				add_query_arg(
					array(
						'page'      => 'filewarden-publication',
						'folder_id' => $this->folder,
					),
					admin_url( 'admin.php' )
				)
			) . '">' . esc_html__( 'Publish this folder', 'filewarden' ) . '</a>';
		}
	}

	/**
	 * Match menu visibility to the separate mutation capabilities.
	 *
	 * @param string $action Action name.
	 * @return bool Allowed capability set.
	 */
	private function can_action( $action ) {
		return match ( $action ) {
			'rights', 'folder_rights' => current_user_can( 'filewarden_manage_permissions' ),
			'move' => current_user_can( 'filewarden_edit_files' ) && current_user_can( 'filewarden_manage_permissions' ),
			'copy' => current_user_can( 'filewarden_upload_files' ) && current_user_can( 'filewarden_manage_permissions' ),
			'delete_folder' => current_user_can( 'filewarden_delete_files' ) && current_user_can( 'filewarden_manage_permissions' ),
			'trash', 'restore' => current_user_can( 'filewarden_delete_files' ),
			default => false,
		};
	}

	/**
	 * Render the requested action without exposing unrelated private records.
	 *
	 * @param string $action Action name.
	 * @param array  $current Current folder.
	 * @throws \DomainException Caught locally for unavailable actions.
	 */
	private function panel( $action, array $current ) {
		echo '<aside class="fw-inspector" aria-label="' . esc_attr__( 'Document action', 'filewarden' ) . '"><a class="fw-close button" href="' . esc_url( $this->url() ) . '" aria-label="' . esc_attr__( 'Close panel', 'filewarden' ) . '">×</a>';
		try {
			if ( ! $this->can_action( $action ) ) {
				throw new \DomainException( 'Access denied.' );
			}
			$lifecycle = new Lifecycle( $this->service );
			$is_folder = in_array( $action, array( 'folder_rights', 'delete_folder' ), true );
			if ( $is_folder && ! $this->folder ) {
				throw new \DomainException( 'Root has no editable rule.' );
			}
			$row = $is_folder ? $current : $lifecycle->record( $this->input( 'document' ), 'restore' === $action );
			echo '<h2 tabindex="-1" class="fw-inspector-title"><img width="32" height="38" alt="" src="' . esc_url( plugins_url( 'assets/icons/' . ( $is_folder ? 'folder' : Publication::icon( $row['name'] ) ) . '.svg', FILEWARDEN_PATH . 'filewarden.php' ) ) . '">' . esc_html( $row['name'] ) . '</h2>';
			$effective = AccessSummary::effective( $row, $this->service->repository );
			echo '<p class="fw-eyebrow">' . esc_html__( 'Effective access', 'filewarden' ) . '</p><p class="filewarden-policy">' . esc_html( ( 'restore' === $action ? __( 'Unavailable in trash', 'filewarden' ) : $effective['label'] ) ) . '</p>';
			if ( 'delete_folder' === $action ) {
				if ( $this->service->repository->folders( $this->folder ) ) {
					echo '<p class="fw-warning">' . esc_html__( 'Remove subfolders first. No files have been changed.', 'filewarden' ) . '</p>'; } else {
					$this->ui->form_start( 'delete_folder' );
					$this->hidden( 'folder_id', $this->folder );
					echo '<p class="fw-warning">' . esc_html__( 'Delete this folder? Its files will be recovered in Documents to classify, unpublished and without automatic deletion. Existing file restrictions will be retained.', 'filewarden' ) . '</p>';
					submit_button( __( 'Confirm folder deletion', 'filewarden' ), 'primary', 'confirm_action', false );
					echo '</form>';
					}
			} elseif ( 'rights' === $action || $is_folder ) {
				$parent           = $row;
				$parent['policy'] = array( 'type' => 'inherit' );
				$parent_rules     = AccessSummary::effective( $parent, $this->service->repository );
				$restricted       = __( 'Public', 'filewarden' ) !== $parent_rules['label'];
				echo '<p>' . esc_html__( 'Folder restrictions:', 'filewarden' ) . ' <strong>' . esc_html( $parent_rules['label'] ) . '</strong></p><div data-public-restricted="' . ( $restricted ? 'true' : 'false' ) . '">';
				$this->ui->form_start( $is_folder ? 'folder_policy' : 'document_policy' );
				$this->hidden( 'folder_id', $is_folder ? $this->folder : $row['folder_id'] );
				if ( ! $is_folder ) {
					$this->hidden( 'document', $row['uuid'] );
				}
				$this->ui->policy_controls( 'inspector', $row['policy'], ! $is_folder || (int) $row['parent_id'] > 0 );
				if ( $is_folder ) {
					echo '<p class="description">' . esc_html__( 'Changing this folder immediately changes access to its documents. File-specific restrictions are preserved.', 'filewarden' ) . '</p>';
				}
				echo '<p class="fw-warning fw-public-warning"' . ( $effective['conflict'] ? '' : ' hidden' ) . '>' . esc_html( $is_folder ? __( 'This folder cannot be made public while an ancestor is restricted. All ancestor restrictions remain in force.', 'filewarden' ) : __( 'This file cannot be made public here. Its folder is restricted. Selecting Public does not remove the folder restrictions: unauthorized visitors will still not see or download this file.', 'filewarden' ) ) . '</p>';
				submit_button( __( 'Save access rules', 'filewarden' ), 'primary', 'save_policy', false );
				echo '</form></div>';
			} elseif ( 'move' === $action || 'copy' === $action ) {
				$this->transfer_panel( $action, $row, $lifecycle );
			} else {
				$this->ui->form_start( $action );
				$this->hidden( 'document', $row['uuid'] );
				$this->hidden( 'folder_id', $row['folder_id'] );
				echo '<p>' . esc_html( 'restore' === $action ? __( 'Restore this file in its original folder with its existing rules.', 'filewarden' ) : __( 'The file will disappear from listings and its preview/download links will stop working. You can restore it from Trash before retention expires.', 'filewarden' ) ) . '</p>';
				submit_button( 'restore' === $action ? __( 'Restore', 'filewarden' ) : __( 'Move to trash', 'filewarden' ), 'primary', 'confirm_action', false );
				echo '</form>';
			}
		} catch ( \Throwable $error ) {
			echo '<p class="fw-warning">' . esc_html__( 'This action is unavailable. Check permissions and select an available document or destination.', 'filewarden' ) . '</p>';
		}
		echo '<a class="fw-cancel" href="' . esc_url( $this->url() ) . '">' . esc_html__( 'Cancel', 'filewarden' ) . '</a></aside>';
	}

	/**
	 * Two-stage transfer: inspect destination, then submit a bound confirmation.
	 *
	 * @param string    $action Move or copy.
	 * @param array     $row Source.
	 * @param Lifecycle $lifecycle Transfer operations.
	 */
	private function transfer_panel( $action, array $row, Lifecycle $lifecycle ) {
		$destination = RestController::integer( $this->input( 'folder_id', '0' ) );
		if ( '' !== $this->input( 'destination_id' ) ) {
			$destination = RestController::integer( $this->input( 'destination_id' ) );
		}
		echo '<form method="get" action="' . esc_url( admin_url( 'admin.php' ) ) . '">';
		foreach ( array(
			'page'      => 'filewarden',
			'tab'       => 'documents',
			'folder_id' => $this->folder,
			'panel'     => $action,
			'document'  => $row['uuid'],
		) as $key => $value ) {
			$this->hidden( $key, $value );
		}
		echo '<p><label for="fw-destination">' . esc_html__( 'Destination folder', 'filewarden' ) . '</label><select id="fw-destination" name="destination_id">';
		foreach ( $this->service->folder_choices() as $id => $label ) {
			if ( ! $id ) {
				continue; }
			echo '<option value="' . esc_attr( $id ) . '"' . selected( $destination, $id, false ) . '>' . esc_html( $label ) . '</option>';
		}
		echo '</select></p><button class="button">' . esc_html__( 'Review destination', 'filewarden' ) . '</button></form>';
		if ( '' === $this->input( 'destination_id' ) ) {
			return;
		}
		$plan      = $lifecycle->plan( $row['uuid'], $destination );
		$effective = AccessSummary::effective( $plan['record'], $this->service->repository );
		echo '<div class="fw-destination-review"><h3>' . esc_html__( 'Access at destination', 'filewarden' ) . '</h3><p class="filewarden-policy">' . esc_html( $effective['label'] ) . '</p><p>' . esc_html__( 'The file rule is preserved; inherited rules change with the destination. Moving or copying into a public folder can broaden access. Check this result before confirming.', 'filewarden' ) . '</p></div>';
		$this->ui->form_start( $action );
		foreach ( array(
			'document'       => $row['uuid'],
			'folder_id'      => $this->folder,
			'destination_id' => $destination,
			'proof'          => $plan['proof'],
		) as $key => $value ) {
			$this->hidden( $key, $value );
		}
		echo '<p><label for="fw-transfer-name">' . esc_html__( 'Filename', 'filewarden' ) . '</label><input id="fw-transfer-name" name="name" value="' . esc_attr( $row['name'] ) . '" maxlength="255" required></p>';
		submit_button( 'move' === $action ? __( 'Confirm move', 'filewarden' ) : __( 'Confirm copy', 'filewarden' ), 'primary', 'confirm_action', false );
		echo '</form>';
	}

	/**
	 * Escape hidden form fields consistently.
	 *
	 * @param string $name Field name.
	 * @param mixed  $value Field value.
	 */
	private function hidden( $name, $value ) {
		echo '<input type="hidden" name="' . esc_attr( $name ) . '" value="' . esc_attr( $value ) . '">';
	}
}
