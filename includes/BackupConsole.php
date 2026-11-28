<?php
/** Backup settings UI, nonce-protected jobs and owner-only binary delivery.
 *
 * @package FileWarden
 */

namespace FileWarden;

/** Keep public status separate from the encrypted private job checkpoint. */
final class BackupConsole {
	/** Render separate forms outside the global settings form. */
	public static function render() {
		echo '<section id="fw-settings-backup" class="fw-settings-panel fw-backup"><h2 tabindex="-1">' . esc_html__( 'Backup and migration', 'filewarden' ) . '</h2><p>' . esc_html__( 'Transfer your library or configuration in an authenticated encrypted package. WordPress accounts, account passwords and the source storage key are never exported.', 'filewarden' ) . '</p><nav class="fw-tabs" aria-label="' . esc_attr__( 'Backup tasks', 'filewarden' ) . '"><button type="button" data-backup-tab="backup" aria-pressed="true">' . esc_html__( 'Back up', 'filewarden' ) . '</button><button type="button" data-backup-tab="restore" aria-pressed="false">' . esc_html__( 'Restore', 'filewarden' ) . '</button></nav><section data-backup-view="backup"><h3>' . esc_html__( 'Encrypted library backup', 'filewarden' ) . '</h3><p>' . esc_html__( 'Choose the data to include. Keep the backup password separately: FileWarden cannot recover it.', 'filewarden' ) . '</p>';
		if ( current_user_can( 'filewarden_export_library' ) ) {
			echo '<form data-backup-start="backup">';
			self::component_controls();
			echo '<label>' . esc_html__( 'Backup password (at least 16 characters)', 'filewarden' ) . '<input type="password" name="phrase" minlength="16" maxlength="1024" autocomplete="new-password" required></label><label>' . esc_html__( 'Confirm the password', 'filewarden' ) . '<input type="password" name="confirmation" minlength="16" maxlength="1024" autocomplete="new-password" required></label><div class="fw-backup-tools"><button type="button" class="button" data-backup-generate>' . esc_html__( 'Generate a strong password', 'filewarden' ) . '</button><label><input type="checkbox" data-backup-show> ' . esc_html__( 'Show the password', 'filewarden' ) . '</label></div><label><input type="checkbox" name="acknowledge" value="1" required> ' . esc_html__( 'I have saved the password separately and understand that the backup contains the selected data.', 'filewarden' ) . '</label><p><button class="button button-primary" type="submit"><span class="fw-action-icon fw-icon-download" aria-hidden="true"></span>' . esc_html__( 'Create encrypted backup', 'filewarden' ) . '</button></p></form>';
		} else {
			echo '<p>' . esc_html__( 'Your account does not have the dedicated library export capability.', 'filewarden' ) . '</p>'; }
		echo '</section><section data-backup-view="restore" hidden><h3>' . esc_html__( 'Restore a backup', 'filewarden' ) . '</h3><p>' . esc_html__( 'Analysis authenticates the entire package before activation. Choose which available components to restore. Source rights require explicit role mappings; omitting rights restores private administration-only content. No WordPress accounts or privileges are created.', 'filewarden' ) . '</p>';
		if ( current_user_can( 'filewarden_restore_library' ) ) {
			echo '<form data-backup-start="restore" enctype="multipart/form-data"><label>' . esc_html__( 'Encrypted backup (.fwbackup)', 'filewarden' ) . '<input name="file" type="file" accept=".fwbackup" required></label><label>' . esc_html__( 'Backup password', 'filewarden' ) . '<input name="phrase" type="password" minlength="16" maxlength="1024" autocomplete="off" required></label><p><button class="button button-primary" type="submit">' . esc_html__( 'Analyze backup', 'filewarden' ) . '</button></p></form>';
		} else {
			echo '<p>' . esc_html__( 'Your account does not have the dedicated library restoration capability.', 'filewarden' ) . '</p>'; }
		echo '</section><section class="fw-backup-progress" hidden><h3>' . esc_html__( 'Backup progress', 'filewarden' ) . '</h3><progress max="100" value="0"></progress><p role="status" aria-live="polite" data-backup-status></p><div class="fw-backup-actions"><button type="button" class="button" data-backup-resume><span class="fw-action-icon fw-icon-rotate-ccw" aria-hidden="true"></span>' . esc_html__( 'Continue / refresh status', 'filewarden' ) . '</button><button type="button" class="button" data-backup-cancel><span class="fw-action-icon fw-icon-trash-2" aria-hidden="true"></span>' . esc_html__( 'Cancel and clean temporary files', 'filewarden' ) . '</button><a class="button button-primary" data-backup-download hidden><span class="fw-action-icon fw-icon-download" aria-hidden="true"></span>' . esc_html__( 'Download encrypted backup', 'filewarden' ) . '</a></div></section><form class="fw-backup-mappings" hidden><h3>' . esc_html__( 'Review restoration', 'filewarden' ) . '</h3><p data-backup-summary></p><div data-backup-restore-options></div><p class="fw-warning" data-backup-private-note hidden></p><div data-backup-role-mappings></div><div data-backup-user-mappings></div><label><input type="checkbox" name="acknowledge" required> ' . esc_html__( 'I confirm the selected components and the settings to replace. Library data can only be restored into an empty library.', 'filewarden' ) . '</label><p><button type="submit" class="button button-primary">' . esc_html__( 'Restore library', 'filewarden' ) . '</button></p></form><p class="description">';
		/* translators: %s: server upload limit. */
		echo esc_html( sprintf( __( 'Server upload limit: %s. Jobs expire after one hour. This version supports up to 10,000 rows per table, a 4 MB manifest and 256 MB per document. Library writes pause during preparation or restoration.', 'filewarden' ), size_format( wp_max_upload_size() ) ) );
		echo '</p><noscript><p>' . esc_html__( 'Enable JavaScript for resumable encrypted backup jobs. Existing document operations remain available.', 'filewarden' ) . '</p></noscript></section>';
	}

	/** Explain export choices and their restoration consequences. */
	private static function component_controls() {
		echo '<fieldset class="fw-backup-components"><legend>' . esc_html__( 'Backup contents', 'filewarden' ) . '</legend><div class="fw-backup-presets"><label><input type="radio" name="backup_profile" value="complete" checked> ' . esc_html__( 'Complete backup', 'filewarden' ) . '</label><label><input type="radio" name="backup_profile" value="custom"> ' . esc_html__( 'Custom backup', 'filewarden' ) . '</label></div><p data-backup-complete-summary>' . esc_html__( 'Includes documents, folders, trash, access rules, creator references, settings, public appearance and audit history.', 'filewarden' ) . '</p><div data-backup-custom-options hidden>';
		$groups = array(
			'library'       => array( __( 'Library', 'filewarden' ), array( 'documents', 'folders', 'trash' ) ),
			'metadata'      => array( __( 'Associated information', 'filewarden' ), array( 'permissions', 'creators' ) ),
			'configuration' => array( __( 'Configuration', 'filewarden' ), array( 'settings', 'appearance' ) ),
			'history'       => array( __( 'History', 'filewarden' ), array( 'audit' ) ),
		);
		$texts  = BackupComponents::labels();
		foreach ( $groups as $group => $data ) {
			echo '<fieldset data-backup-group="' . esc_attr( $group ) . '"><legend>' . esc_html( $data[0] ) . '</legend>';
			foreach ( $data[1] as $key ) {
				echo '<label class="fw-backup-component"><input type="checkbox" data-backup-component="' . esc_attr( $key ) . '" checked><span><strong>' . esc_html( $texts[ $key ][0] ) . '</strong><small>' . esc_html( $texts[ $key ][1] ) . '</small></span></label>';
			}
			echo '</fieldset>';
		}
		echo '</div><p data-backup-contents-summary role="status"></p><p class="description">' . esc_html__( 'WordPress accounts, account passwords, source encryption keys and role privileges are never transferred. Excluding components removes their data from the encrypted package.', 'filewarden' ) . '</p></fieldset>';
	}

	/** Register translated job UI configuration.
	 *
	 * @param DocumentService $service Services.
	 */
	public static function assets( DocumentService $service ) {
		$resume = null;
		try {
			$probe = BackupJobs::path( $service, str_repeat( '0', 32 ) );
			$paths = glob( dirname( $probe ) . '/*.state' );
			usort( $paths, static fn( $a, $b ) => filemtime( $b ) <=> filemtime( $a ) ); // phpcs:ignore WordPress.WP.AlternativeFunctions
			foreach ( array_slice( $paths, 0, 100 ) as $path ) {
				$job = BackupJobs::load( $service, basename( $path, '.state' ) );
				if ( get_current_user_id() === (int) $job['owner'] && $job['expires'] >= time() ) {
					BackupJobs::authorize( $job );
					$resume = BackupService::status( $job );
					break; }
			}
		} catch ( \Throwable $error ) {
			$resume = null; }
		$roles = array();
		foreach ( wp_roles()->roles as $key => $role ) {
			$roles[ $key ] = translate_user_role( $role['name'] ); }
		$users = array();
		if ( current_user_can( 'filewarden_restore_library' ) ) {
			foreach ( get_users(
				array(
					'number' => 10000,
					'fields' => array( 'ID', 'user_login' ),
				)
			) as $user ) {
				$users[ $user->ID ] = $user->user_login; }
		}
		wp_enqueue_script( 'filewarden-backup', plugins_url( 'assets/backup.js', FILEWARDEN_PATH . 'filewarden.php' ), array( 'filewarden-notices' ), FILEWARDEN_VERSION . '-' . substr( hash_file( 'sha256', FILEWARDEN_PATH . 'assets/backup.js' ), 0, 12 ), true ); // phpcs:ignore WordPress.WP.AlternativeFunctions
		wp_localize_script(
			'filewarden-backup',
			'filewardenBackup',
			array(
				'endpoint'       => admin_url( 'admin-ajax.php' ),
				'nonce'          => wp_create_nonce( 'filewarden_backup' ),
				'resume'         => $resume,
				'roles'          => $roles,
				'components'     => BackupComponents::labels(),
				'emptySelection' => __( 'Select at least one backup component.', 'filewarden' ),
				'configOnly'     => __( 'Configuration only: review the settings and appearance to replace. Existing library documents and folders will be preserved.', 'filewarden' ),
				'contents'       => __( 'Selected contents', 'filewarden' ),
				'restoreOptions' => __( 'Components to restore', 'filewarden' ),
				'private'        => __( 'Access rules will not be imported. All restored documents and folders will be private to administration until you deliberately configure their access. Existing global-read privileges remain applicable.', 'filewarden' ),
				'currentCreator' => __( 'Attribute all restored items to my account', 'filewarden' ),
				'mapCreators'    => __( 'Choose attribution for each source creator', 'filewarden' ),
				'users'          => $users,
				'working'        => __( 'Authenticated files prepared', 'filewarden' ),
				'review'         => __( 'Package authenticated. Review the available components and access mode before restoring.', 'filewarden' ),
				'ready'          => __( 'Encrypted backup ready. Download it and keep the password separately.', 'filewarden' ),
				'restored'       => __( 'Library restored with the confirmed access mode. Review permissions and the site before sharing documents.', 'filewarden' ),
				'error'          => __( 'The request was interrupted. Use Continue to check the server before retrying. Temporary files remain private.', 'filewarden' ),
				'phraseError'    => __( 'The passwords must match and contain at least 16 characters.', 'filewarden' ),
				'choose'         => __( 'Choose explicitly', 'filewarden' ),
				'role'           => __( 'Source role', 'filewarden' ),
				'user'           => __( 'Source creator', 'filewarden' ),
				'summary'        => __( 'folders / documents / trash / audit events', 'filewarden' ),
				'cancelled'      => __( 'Job cancelled. Temporary files cleaned.', 'filewarden' ),
			)
		);
	}

	/** Handle authenticated job work; raw exception details never reach clients.
	 *
	 * @param DocumentService $service Services.
	 * @throws \InvalidArgumentException If the operation cannot complete safely.
	 */
	public static function handle( DocumentService $service ) {
		check_ajax_referer( 'filewarden_backup' );
		// phpcs:disable WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Strict scalar/schema checks below and complete service validation. Backup passwords must remain byte-exact.
		try {
			$operation = isset( $_POST['operation'] ) && is_string( $_POST['operation'] ) ? sanitize_key( $_POST['operation'] ) : '';
			if ( in_array( $operation, array( 'backup', 'restore' ), true ) ) {
				$phrase = isset( $_POST['phrase'] ) && is_string( $_POST['phrase'] ) ? wp_unslash( $_POST['phrase'] ) : '';
				if ( 'backup' === $operation && ( '1' !== wp_unslash( $_POST['acknowledge'] ?? '' ) || ! is_string( $_POST['confirmation'] ?? null ) || ! hash_equals( $phrase, wp_unslash( $_POST['confirmation'] ) ) ) ) {
					throw new \InvalidArgumentException( 'Backup confirmation required.' ); }
				$upload = '';
				if ( 'restore' === $operation ) {
					$file = $_FILES['file'] ?? array();
					if ( UPLOAD_ERR_OK !== ( $file['error'] ?? null ) || ! is_string( $file['tmp_name'] ?? null ) || ! is_uploaded_file( $file['tmp_name'] ) ) {
						throw new \InvalidArgumentException( 'Invalid backup upload.' ); }
					$upload = $file['tmp_name'];
				}
				$components = null;
				if ( 'backup' === $operation && isset( $_POST['components'] ) ) {
					if ( ! is_string( $_POST['components'] ) ) {
						throw new \InvalidArgumentException( 'Invalid backup components.' ); }
					$components = BackupComponents::options( json_decode( wp_unslash( $_POST['components'] ), true, 16, JSON_THROW_ON_ERROR ) );
				}
				$result = BackupService::start( $service, $operation, $phrase, $upload, $components );
			} else {
				$id = isset( $_POST['id'] ) && is_string( $_POST['id'] ) ? sanitize_text_field( wp_unslash( $_POST['id'] ) ) : '';
				if ( 'tick' === $operation ) {
					$result = BackupService::tick( $service, $id ); } elseif ( 'apply' === $operation ) {
					if ( '1' !== wp_unslash( $_POST['acknowledge'] ?? '' ) ) {
						throw new \InvalidArgumentException( 'Restoration confirmation required.' ); }
					$roles = json_decode( wp_unslash( is_string( $_POST['roles'] ?? null ) ? $_POST['roles'] : '' ), true, 32, JSON_THROW_ON_ERROR );
					$users = json_decode( wp_unslash( is_string( $_POST['users'] ?? null ) ? $_POST['users'] : '' ), true, 32, JSON_THROW_ON_ERROR );
					if ( ! is_array( $roles ) || ! is_array( $users ) ) {
						throw new \InvalidArgumentException( 'Invalid mappings.' ); }
					$choices = null;
					if ( isset( $_POST['choices'] ) ) {
						if ( ! is_string( $_POST['choices'] ) ) {
							throw new \InvalidArgumentException( 'Invalid restoration choices.' ); }
						$choices = json_decode( wp_unslash( $_POST['choices'] ), true, 16, JSON_THROW_ON_ERROR );
						if ( ! is_array( $choices ) ) {
							throw new \InvalidArgumentException( 'Invalid restoration choices.' ); }
					}
					$result = BackupRestore::apply( $service, $id, $roles, $users, $choices );
					} elseif ( 'cancel' === $operation ) {
						$result = BackupJobs::exclusive(
							static function () use ( $service, $id ) {
								$job = BackupJobs::load( $service, $id );
								BackupJobs::authorize( $job );
								BackupJobs::cleanup( $service, $job );
								$service->audit->record( 'backup_cancelled' );
								return array( 'status' => 'cancelled' );
							}
						);
					} else {
						throw new \InvalidArgumentException( 'Invalid backup operation.' ); }
			}
			if ( 'ready' === ( $result['status'] ?? '' ) ) {
				$result['download'] = self::download_url( $result['id'] ); }
			wp_send_json_success( $result );
		} catch ( \Throwable $error ) {
			wp_send_json_error( BackupErrors::response( $error ), 400 );
		}
		// phpcs:enable WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
	}

	/** Build an expiring, owner-checked route, never expose a physical filename.
	 *
	 * @param string $id Job ID.
	 * @return string URL.
	 */
	private static function download_url( $id ) {
		// JSON requires a raw URL. wp_nonce_url() would HTML-escape the separators.
		return add_query_arg(
			array(
				'action'   => 'filewarden_backup_download',
				'job'      => $id,
				'_wpnonce' => wp_create_nonce( 'filewarden_backup_download_' . $id ),
			),
			admin_url( 'admin-post.php' )
		);
	}

	/** Deliver a ready authenticated backup after capability, owner and expiry checks.
	 *
	 * @param DocumentService $service Services.
	 * @throws \RuntimeException On an interrupted binary read.
	 */
	public static function download( DocumentService $service ) {
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- The object nonce is checked below.
		$id = isset( $_GET['job'] ) && is_string( $_GET['job'] ) ? sanitize_text_field( wp_unslash( $_GET['job'] ) ) : '';
		check_admin_referer( 'filewarden_backup_download_' . $id );
		try {
			list( $input, $length ) = BackupJobs::exclusive(
				static function () use ( $service, $id ) {
					$job = BackupJobs::load( $service, $id );
					BackupJobs::authorize( $job );
					if ( 'backup' !== $job['type'] || 'ready' !== $job['status'] || $job['expires'] < time() ) {
						throw new \DomainException( 'Backup download unavailable.' ); }
					$input = fopen( BackupJobs::path( $service, $id, 'fwbackup' ), 'rb' ); // phpcs:ignore WordPress.WP.AlternativeFunctions
					if ( ! $input ) {
						throw new \RuntimeException( 'Backup read failed.' ); }
					$stat = fstat( $input ); // phpcs:ignore WordPress.WP.AlternativeFunctions
					try {
						if ( ! $stat || $stat['size'] !== $job['offset'] ) {
							throw new \RuntimeException( 'Backup read failed.' ); }
						$service->audit->record( 'backup_download_started' );
					} catch ( \Throwable $error ) {
						fclose( $input ); // phpcs:ignore WordPress.WP.AlternativeFunctions -- Close the private binary stream.
						throw $error; } // phpcs:ignore WordPress.WP.AlternativeFunctions
					return array( $input, $stat['size'] );
				}
			);
			// Do not hold the library writer gate during a potentially long browser download.
			nocache_headers();
			header( 'Cache-Control: no-store, private' );
			header( 'Content-Type: application/octet-stream' );
			header( 'X-Content-Type-Options: nosniff' );
			header( 'Content-Disposition: attachment; filename="filewarden-library.fwbackup"' );
			header( 'Content-Length: ' . $length );
			try {
				while ( ! feof( $input ) ) {
					$bytes = FileIO::attempt( fn() => fread( $input, 65536 ) ); // phpcs:ignore WordPress.WP.AlternativeFunctions
					if ( false === $bytes ) {
						throw new \RuntimeException( 'Backup read failed.' ); }
					echo $bytes; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Opaque encrypted attachment; never markup.
				}
			} finally {
				fclose( $input ); } // phpcs:ignore WordPress.WP.AlternativeFunctions
		} catch ( \Throwable $error ) {
			if ( ! headers_sent() ) {
				wp_die( esc_html__( 'Backup download unavailable or expired.', 'filewarden' ), '', array( 'response' => 403 ) ); }
		}
		exit;
	}
}
