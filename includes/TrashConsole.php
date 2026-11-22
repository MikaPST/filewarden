<?php
/** Global trash administration with no folder tree.
 *
 * @package FileWarden
 */

namespace FileWarden;

/** Restore and irreversible disposal use the existing privileged POST transport. */
final class TrashConsole {
	/** Render authorized global trash.
	 *
	 * @param DocumentService $service Services.
	 * @param AdminController $ui Form helpers.
	 * @throws \DomainException For unavailable trash records.
	 */
	public static function page( DocumentService $service, AdminController $ui ) {
		// phpcs:disable WordPress.Security.NonceVerification.Recommended -- Read-only filters and confirmation preparation; mutations have a POST nonce.
		$search = isset( $_GET['search'] ) && is_string( $_GET['search'] ) ? sanitize_text_field( wp_unslash( $_GET['search'] ) ) : '';
		// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- integer() strictly validates the scalar folder ID.
		$filter = isset( $_GET['trash_folder'] ) && is_string( $_GET['trash_folder'] ) && '' !== $_GET['trash_folder'] ? RestController::integer( wp_unslash( $_GET['trash_folder'] ) ) : null;
		$intent = isset( $_GET['trash_action'] ) && is_string( $_GET['trash_action'] ) ? sanitize_key( $_GET['trash_action'] ) : '';
		$uuid   = isset( $_GET['document'] ) && is_string( $_GET['document'] ) ? sanitize_text_field( wp_unslash( $_GET['document'] ) ) : '';
		// phpcs:enable WordPress.Security.NonceVerification.Recommended
		$rows = DocumentCollection::rows( $service, true, $search, $filter );
		Notice::show( __( 'Only authorized deleted documents are shown. Restoring requires an authorized destination.', 'filewarden' ), 'info', __( 'Trash', 'filewarden' ) );
		echo '<section class="fw-content fw-global-trash"><h2>' . esc_html__( 'Global trash', 'filewarden' ) . '</h2><p>' . esc_html__( 'All deleted documents you are allowed to manage. Automatic retention still applies.', 'filewarden' ) . '</p><form method="get" class="fw-search fw-trash-filters"><input type="hidden" name="page" value="filewarden"><input type="hidden" name="tab" value="trash"><label for="fw-trash-search">' . esc_html__( 'Search by filename', 'filewarden' ) . '<input id="fw-trash-search" type="search" name="search" maxlength="200" value="' . esc_attr( $search ) . '" placeholder="' . esc_attr__( 'Search for a document in the trash...', 'filewarden' ) . '"></label><label for="fw-trash-folder">' . esc_html__( 'Folder', 'filewarden' ) . '<select id="fw-trash-folder" name="trash_folder"><option value="">' . esc_html__( 'All folders', 'filewarden' ) . '</option>';
		foreach ( $service->folder_choices() as $id => $label ) {
			echo '<option value="' . esc_attr( $id ) . '"' . selected( $filter, (string) $id, false ) . '>' . esc_html( $label ) . '</option>'; }
		echo '</select></label><button class="button fw-button-accent"><span class="fw-action-icon fw-icon-search" aria-hidden="true"></span>' . esc_html__( 'Search', 'filewarden' ) . '</button>';
		if ( '' !== $search || null !== $filter ) {
			echo '<a href="' . esc_url(
				add_query_arg(
					array(
						'page' => 'filewarden',
						'tab'  => 'trash',
					),
					admin_url( 'admin.php' )
				)
			) . '">' . esc_html__( 'Reset filters', 'filewarden' ) . '</a>';
		}
		echo '</form><p class="fw-trash-count">';
		/* translators: %s: authorized matching document count. */
		echo esc_html( sprintf( _n( '%s document', '%s documents', count( $rows ), 'filewarden' ), number_format_i18n( count( $rows ) ) ) );
		echo '</p><div class="fw-table-wrap"><table class="widefat"><thead><tr><th scope="col">' . esc_html__( 'Document', 'filewarden' ) . '</th><th scope="col">' . esc_html__( 'Original location', 'filewarden' ) . '</th><th scope="col">' . esc_html__( 'Deleted on', 'filewarden' ) . '</th><th scope="col">' . esc_html__( 'Actions', 'filewarden' ) . '</th></tr></thead><tbody>';
		foreach ( $rows as $row ) {
			$origin    = json_decode( $row['trash_origin'] ?? '', true );
			$icon      = plugins_url( 'assets/icons/' . Publication::icon( $row['name'] ) . '.svg', FILEWARDEN_PATH . 'filewarden.php' );
			$expiry    = strtotime( $row['trashed_at'] . ' UTC' ) + max( 1, (int) get_option( 'filewarden_trash_days', 30 ) ) * DAY_IN_SECONDS;
			$remaining = max( 0, (int) ceil( ( $expiry - time() ) / DAY_IN_SECONDS ) );
			/* translators: %s: days until eligibility for automatic trash cleanup. */
			$retention = $remaining ? sprintf( _n( 'Automatic cleanup in %s day', 'Automatic cleanup in %s days', $remaining, 'filewarden' ), number_format_i18n( $remaining ) ) : __( 'Eligible for automatic cleanup; waiting for the scheduled task.', 'filewarden' );
			echo '<tr><th scope="row"><div class="fw-document-name"><img src="' . esc_url( $icon ) . '" width="38" height="44" alt=""><span>' . esc_html( $row['name'] ) . '</span></div></th><td>' . esc_html( $origin['path'] ?? DocumentCollection::path( $service->repository, (int) $row['folder_id'] ) ?? __( 'Unknown original location', 'filewarden' ) ) . '</td><td>' . esc_html( get_date_from_gmt( $row['trashed_at'], 'Y-m-d H:i:s' ) ) . '<small class="fw-trash-retention">' . esc_html( $retention ) . '</small></td><td>';
			foreach ( array(
				'restore' => __( 'Restore', 'filewarden' ),
				'purge'   => __( 'Delete permanently', 'filewarden' ),
			) as $action => $label ) {
				echo '<a class="button ' . ( 'purge' === $action ? 'fw-button-danger' : '' ) . '" href="' . esc_url(
					add_query_arg(
						array(
							'page'         => 'filewarden',
							'tab'          => 'trash',
							'trash_action' => $action,
							'document'     => $row['uuid'],
						),
						admin_url( 'admin.php' )
					)
				) . '"><span class="fw-action-icon fw-icon-' . ( 'purge' === $action ? 'trash-2' : 'rotate-ccw' ) . '" aria-hidden="true"></span>' . esc_html( $label ) . '</a> ';
			}
			echo '</td></tr>';
		}
		if ( ! $rows ) {
			echo '<tr><td colspan="4">' . esc_html( '' !== $search || null !== $filter ? __( 'No documents match this view.', 'filewarden' ) : __( 'The trash is empty.', 'filewarden' ) ) . '</td></tr>'; }
		echo '</tbody></table></div><p><a class="button fw-button-danger" href="' . esc_url(
			add_query_arg(
				array(
					'page'         => 'filewarden',
					'tab'          => 'trash',
					'trash_action' => 'empty',
				),
				admin_url( 'admin.php' )
			)
		) . '"><span class="fw-action-icon fw-icon-trash-2" aria-hidden="true"></span>' . esc_html__( 'Empty trash', 'filewarden' ) . '</a></p>';
		if ( 'restore' === $intent ) {
			$row = ( new Lifecycle( $service ) )->record( $uuid, true );
			if ( empty( $row['trashed_at'] ) ) {
				throw new \DomainException( 'Document unavailable.' ); }
			echo '<section class="fw-trash-confirm"><h3>' . esc_html( $row['name'] ) . '</h3>';
			$ui->form_start( 'restore' );
			echo '<input type="hidden" name="document" value="' . esc_attr( $uuid ) . '"><input type="hidden" name="folder_id" value="' . esc_attr( $row['folder_id'] ) . '">';
			if ( ! $row['folder_id'] || ! $service->repository->folder( (int) $row['folder_id'] ) ) {
				echo '<p>' . esc_html__( 'The original folder is unavailable. Choose an authorized destination before restoring.', 'filewarden' ) . '</p><label for="fw-restore-destination">' . esc_html__( 'Destination folder', 'filewarden' ) . '</label><select id="fw-restore-destination" name="destination_id" required><option value="">' . esc_html__( 'Select a destination', 'filewarden' ) . '</option>';
				foreach ( $service->folder_choices() as $id => $name ) {
					if ( $id ) {
						echo '<option value="' . esc_attr( $id ) . '">' . esc_html( $name ) . '</option>'; }
				}
				echo '</select>';
			}
			echo '<button class="button button-primary">' . esc_html__( 'Restore', 'filewarden' ) . '</button></form></section>';
		} elseif ( in_array( $intent, array( 'purge', 'empty' ), true ) ) {
			$selection = 'empty' === $intent ? '' : $uuid;
			$review    = TrashService::review( $service, $selection );
			echo '<section class="fw-warning fw-trash-confirm"><h3>' . esc_html__( 'Permanently delete these documents?', 'filewarden' ) . '</h3><p>' . esc_html( number_format_i18n( count( $review['rows'] ) ) ) . ' ' . esc_html__( 'documents selected. This action cannot be undone.', 'filewarden' ) . '</p>';
			if ( 'empty' === $intent ) {
				echo '<p>' . esc_html__( 'This empties all trash documents you are authorized to delete, regardless of the current search or folder filter.', 'filewarden' ) . '</p>';
			}
			$ui->form_start( 'purge_trash' );
			foreach ( array(
				'folder_id' => 0,
				'document'  => $selection,
				'proof'     => $review['proof'],
			) as $name => $value ) {
				echo '<input type="hidden" name="' . esc_attr( $name ) . '" value="' . esc_attr( $value ) . '">'; }
			echo '<label><input type="checkbox" name="confirm" value="1" required> ' . esc_html__( 'I understand these documents cannot be restored.', 'filewarden' ) . '</label><p><button class="button fw-button-danger">' . esc_html__( 'Confirm permanent deletion', 'filewarden' ) . '</button></p></form></section>';
		}
		echo '</section>';
	}
}
