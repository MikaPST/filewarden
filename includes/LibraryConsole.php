<?php
/** Shared selection controls and nonce-protected administration REST actions.
 *
 * @package FileWarden
 */

namespace FileWarden;

/** UI transport never substitutes for per-item service authorization. */
final class LibraryConsole {
	/** Register the private operation endpoint.
	 *
	 * @param DocumentService $service Services.
	 */
	public static function register( DocumentService $service ) {
		register_rest_route(
			'filewarden/v1',
			'/library-action',
			array(
				'methods'             => 'POST',
				'permission_callback' => static fn() => current_user_can( 'filewarden_manage_settings' ),
				'callback'            => static function ( $request ) use ( $service ) {
					if ( ! wp_verify_nonce( $request->get_header( 'X-WP-Nonce' ), 'wp_rest' ) ) {
						return new \WP_Error( 'filewarden_nonce', __( 'Your session expired. Reload the page.', 'filewarden' ), array( 'status' => 403 ) );
					}
					try {
						$input = $request->get_json_params();
						if ( ! is_array( $input ) || ! is_string( $input['operation'] ?? null ) ) {
							throw new \InvalidArgumentException( 'Invalid operation.' );
						}
						if ( 'description' === $input['operation'] ) {
							DocumentDescription::save( $service, $input['id'] ?? '', $input['description'] ?? null );
						} elseif ( 'rename' === $input['operation'] ) {
							LibraryActions::rename( $service, $input['kind'] ?? '', $input['id'] ?? '', $input['name'] ?? '' );
						} else {
							$ids         = is_array( $input['ids'] ?? null ) ? $input['ids'] : array();
							$operation   = $input['operation'];
							$destination = RestController::integer( $input['destination'] ?? null );
							if ( empty( $input['confirm'] ) ) {
								return LibraryActions::review( $service, $ids, $operation, $destination );
							}
							LibraryActions::bulk( $service, $ids, $operation, $destination, is_string( $input['proof'] ?? null ) ? $input['proof'] : '' );
						}
						return array( 'message' => __( 'Operation completed.', 'filewarden' ) );
					} catch ( \UnexpectedValueException $error ) {
						return new \WP_Error( 'filewarden_duplicate', __( 'A name already exists in the destination. No selected item was changed.', 'filewarden' ), array( 'status' => 409 ) );
					} catch ( \Throwable $error ) {
						return new \WP_Error( 'filewarden_operation', __( 'Operation refused. Check your permissions, selection, destination and names. Review again if the library changed. No selected item was changed.', 'filewarden' ), array( 'status' => 400 ) );
					}
				},
			)
		);
	}

	/** Local scripts and their translated messages. */
	public static function assets() {
		wp_enqueue_script( 'filewarden-library', plugins_url( 'assets/library.js', FILEWARDEN_PATH . 'filewarden.php' ), array( 'filewarden-notices' ), FILEWARDEN_VERSION . '-' . substr( hash_file( 'sha256', FILEWARDEN_PATH . 'assets/library.js' ), 0, 12 ), true );
		wp_enqueue_script( 'filewarden-action-dialog', plugins_url( 'assets/action-dialog.js', FILEWARDEN_PATH . 'filewarden.php' ), array( 'filewarden-library' ), FILEWARDEN_VERSION . '-' . substr( hash_file( 'sha256', FILEWARDEN_PATH . 'assets/action-dialog.js' ), 0, 12 ), true );
		wp_localize_script(
			'filewarden-library',
			'filewardenLibrary',
			array(
				'endpoint'         => rest_url( 'filewarden/v1/library-action' ),
				'nonce'            => wp_create_nonce( 'wp_rest' ),
				'descriptionLimit' => __( 'Use no more than 400 characters.', 'filewarden' ),
				'rename'           => __( 'Rename', 'filewarden' ),
				'name'             => __( 'New name (keep the file extension)', 'filewarden' ),
				'folderName'       => __( 'Folder name', 'filewarden' ),
				'cancel'           => __( 'Cancel', 'filewarden' ),
				'confirm'          => __( 'Confirm', 'filewarden' ),
				'review'           => __( 'Review the selected documents', 'filewarden' ),
				'trash'            => __( 'Move selection to trash', 'filewarden' ),
				'trashHelp'        => __( 'These documents will disappear from publications. You can restore them from Trash before retention expires.', 'filewarden' ),
				'close'            => __( 'Close', 'filewarden' ),
				'error'            => __( 'The server could not confirm the operation. Reload before trying again.', 'filewarden' ),
				'destination'      => __( 'Destination folder', 'filewarden' ),
				'check'            => __( 'Review destination', 'filewarden' ),
				'clear'            => __( 'Deselect all', 'filewarden' ),
				'selected'         => __( 'documents selected (maximum 50)', 'filewarden' ),
				'reload'           => __( 'Operation completed. Reloading the library.', 'filewarden' ),
			)
		);
	}

	/** Render a direct edit control.
	 *
	 * @param string     $kind Item type.
	 * @param string|int $id Identity.
	 * @param string     $name Current name.
	 */
	public static function rename_button( $kind, $id, $name ) {
		if ( ! current_user_can( 'filewarden_edit_files' ) || ! current_user_can( 'filewarden_manage_permissions' ) ) {
			return;
		}
		echo '<button type="button" class="button fw-rename fw-rename-' . esc_attr( $kind ) . '" data-rename-kind="' . esc_attr( $kind ) . '" data-rename-id="' . esc_attr( $id ) . '" data-rename-name="' . esc_attr( $name ) . '" aria-label="' . esc_attr( __( 'Rename', 'filewarden' ) . ': ' . $name ) . '"><span class="fw-action-icon fw-icon-pencil" aria-hidden="true"></span><span class="screen-reader-text">' . esc_html__( 'Rename', 'filewarden' ) . '</span><span class="fw-action-tooltip" aria-hidden="true">' . esc_html( 'folder' === $kind ? __( 'Rename folder', 'filewarden' ) : __( 'Rename', 'filewarden' ) ) . '</span></button>';
	}

	/** A selection bar is separate from search and the per-item forms.
	 *
	 * @param DocumentService $service Services.
	 * @param bool            $trash Trash mode.
	 * @param int|null        $exclude Current folder, when all displayed rows share it.
	 */
	public static function selection( DocumentService $service, $trash = false, $exclude = null ) {
		$can_move  = current_user_can( 'filewarden_manage_permissions' ) && current_user_can( $trash ? 'filewarden_delete_files' : 'filewarden_edit_files' );
		$can_trash = ! $trash && current_user_can( 'filewarden_delete_files' );
		if ( ! $can_move && ! $can_trash ) {
			return; }
		echo '<form class="fw-selection" data-operation="' . ( $trash ? 'restore' : 'move' ) . '"><div class="fw-selection-count"><output aria-live="polite"></output></div>';
		if ( $can_move ) {
			echo '<template data-selection-destination><label class="fw-destination-field">' . esc_html( $trash ? __( 'Fallback destination if the original folder is unavailable', 'filewarden' ) : __( 'Destination folder', 'filewarden' ) ) . '<select name="destination" required><option value="">' . esc_html__( 'Select a destination', 'filewarden' ) . '</option>';
			foreach ( $service->folder_choices() as $id => $label ) {
				if ( ( ! $id && $trash ) || ( null !== $exclude && $id === $exclude ) ) {
					continue; }
				echo '<option value="' . esc_attr( $id ) . '">' . esc_html( $id ? $label : __( 'Documents to classify — Not published', 'filewarden' ) ) . '</option>';
			}
			echo '</select></label></template><button type="submit" class="button fw-button-accent" disabled><span class="fw-action-icon fw-icon-' . ( $trash ? 'rotate-ccw' : 'folder-check' ) . '" aria-hidden="true"></span>' . esc_html( $trash ? __( 'Restore selection', 'filewarden' ) : __( 'Move / classify selection', 'filewarden' ) ) . '</button>';
		}
		if ( $can_trash ) {
			echo '<button type="button" class="button fw-button-danger" data-bulk-trash disabled><span class="fw-action-icon fw-icon-trash-2" aria-hidden="true"></span>' . esc_html__( 'Move selection to trash', 'filewarden' ) . '</button>';
		}
		echo '<button type="button" class="button fw-selection-clear" data-clear-selection>' . esc_html__( 'Deselect all', 'filewarden' ) . '</button></form>';
	}

	/** Render a header selection control. */
	public static function select_header() {
		echo '<label class="fw-header-selection"><input type="checkbox" data-select-visible aria-label="' . esc_attr__( 'Select visible documents', 'filewarden' ) . '"><span class="screen-reader-text">' . esc_html__( 'Select visible documents', 'filewarden' ) . '</span></label>';
	}

	/** Read-only header sorting preserves filters and resets pagination.
	 *
	 * @param string $label Header text.
	 * @param string $key Allowlisted sort key.
	 * @param array  $context Fixed navigation context.
	 */
	public static function sort_header( $label, $key, array $context ) {
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only authorized query.
		$filters       = LibraryQuery::filters( $_GET );
		$active        = str_starts_with( $filters['sort'], $key . '_' );
		$descending    = $active && str_ends_with( $filters['sort'], '_desc' );
		$query         = $context + $filters;
		$query['sort'] = $key . ( $active ? ( $descending ? '_asc' : '_desc' ) : ( 'date' === $key ? '_desc' : '_asc' ) );
		echo '<span class="fw-sort-heading" data-sort-state="' . esc_attr( $active ? ( $descending ? 'descending' : 'ascending' ) : 'none' ) . '"><a href="' . esc_url( add_query_arg( $query, admin_url( 'admin.php' ) ) ) . '">' . esc_html( $label ) . '<span class="fw-sort-chevron" aria-hidden="true"></span></a></span>';
	}

	/** Progressive compact sort and collapsible filters, within the search form.
	 *
	 * @param array $filters Normalized display query.
	 * @param bool  $scope Offer whole-library search.
	 * @param array $context Fixed page and folder context for reset links.
	 */
	public static function filters( array $filters, $scope = true, array $context = array() ) {
		if ( $scope ) {
			echo '<label class="fw-search-field">' . esc_html__( 'Search in', 'filewarden' ) . '<select name="scope">';
			foreach ( array(
				'folder' => __( 'Current folder', 'filewarden' ),
				'all'    => __( 'Authorized library', 'filewarden' ),
			) as $key => $label ) {
				echo '<option value="' . esc_attr( $key ) . '"' . selected( $filters['scope'], $key, false ) . '>' . esc_html( $label ) . '</option>';
			}
			echo '</select></label>';
		}
		echo '</div><button type="submit" class="button fw-button-accent"><span class="fw-action-icon fw-icon-search" aria-hidden="true"></span>' . esc_html__( 'Search', 'filewarden' ) . '</button><input type="hidden" name="sort" value="' . esc_attr( $filters['sort'] ) . '">';
		$fields = array(
			'type'     => __( 'File extension', 'filewarden' ),
			'after'    => __( 'Added after', 'filewarden' ),
			'before'   => __( 'Added before', 'filewarden' ),
			'min_size' => __( 'Minimum size (KB)', 'filewarden' ),
			'max_size' => __( 'Maximum size (KB)', 'filewarden' ),
		);
		$active = array_filter( array_intersect_key( $filters, $fields ), static fn( $value ) => '' !== $value );
		$badge  = $active ? '<span class="fw-filter-count">' . esc_html( count( $active ) ) . '</span>' : '';
		echo '<button type="button" class="button fw-filter-toggle" data-filter-toggle aria-expanded="false" aria-controls="fw-library-filter-panel" hidden><span class="fw-action-icon fw-icon-sliders-horizontal" aria-hidden="true"></span>' . esc_html__( 'Filters', 'filewarden' ) . wp_kses_post( $badge ) . '<span aria-hidden="true">⌄</span></button></div><details class="fw-library-filters"><summary>' . esc_html__( 'Filters', 'filewarden' ) . wp_kses_post( $badge ) . '</summary><div id="fw-library-filter-panel" class="fw-filter-panel"><div class="fw-filter-groups"><fieldset><legend>' . esc_html__( 'File type', 'filewarden' ) . '</legend>';
		foreach ( $fields as $key => $label ) {
			if ( 'after' === $key || 'min_size' === $key ) {
				echo '</fieldset><fieldset><legend>' . esc_html( 'after' === $key ? __( 'Added during', 'filewarden' ) : __( 'File size', 'filewarden' ) ) . '</legend>';
			}
			$type = 'type' === $key ? 'text' : ( in_array( $key, array( 'after', 'before' ), true ) ? 'date' : 'number' );
			echo '<label>' . esc_html( $label ) . '<input type="' . esc_attr( $type ) . '" name="' . esc_attr( $key ) . '" value="' . esc_attr( $filters[ $key ] ) . '"' . ( 'number' === $type ? ' min="0" max="9999999"' : ( 'text' === $type ? ' maxlength="12" placeholder="PDF"' : '' ) ) . '></label>';
		}
		$query = $context + $filters;
		echo '</fieldset></div>';
		$clean = array_diff_key( $query, $fields );
		echo '<div class="fw-filter-actions"><button type="submit" class="button button-primary">' . esc_html__( 'Apply filters', 'filewarden' ) . '</button><a class="button" href="' . esc_url( add_query_arg( $clean, admin_url( 'admin.php' ) ) ) . '">' . esc_html__( 'Reset filters', 'filewarden' ) . '</a></div></div></details>';
		if ( $active ) {
			echo '<div class="fw-filter-chips" aria-label="' . esc_attr__( 'Active filters', 'filewarden' ) . '">';
			foreach ( $active as $key => $value ) {
				$remove = $query;
				unset( $remove[ $key ] );
				/* translators: %s: filter label. */
				echo '<a href="' . esc_url( add_query_arg( $remove, admin_url( 'admin.php' ) ) ) . '" aria-label="' . esc_attr( sprintf( __( 'Remove filter: %s', 'filewarden' ), $fields[ $key ] ) ) . '">' . esc_html( $fields[ $key ] . ': ' . $value ) . '<span aria-hidden="true">×</span></a>';
			}
			echo '</div>';
		}
	}
}
