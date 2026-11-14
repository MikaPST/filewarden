<?php
/** Categorized settings and measured storage health.
 *
 * @package FileWarden
 */

namespace FileWarden;

/** Save only submitted settings; hidden or absent categories never reset values. */
final class SettingsConsole {
	const FIELDS = array(
		'upload_mb'  => array( 50, 1, 1024 ),
		'trash_days' => array( 30, 1, 3650 ),
		'audit_days' => array( 365, 1, 3650 ),
	);

	/** One registry for navigation and panel headings; extensions may add read-only categories.
	 *
	 * @return array Validated category metadata.
	 */
	public static function sections() {
		$sections = array(
			'general'   => array(
				'label'       => __( 'General', 'filewarden' ),
				'description' => __( 'Personalize how FileWarden behaves.', 'filewarden' ),
				'icon'        => 'sliders-horizontal',
			),
			'storage'   => array(
				'label'       => __( 'Storage and security', 'filewarden' ),
				'description' => __( 'Keep your documents separate from the plugin code.', 'filewarden' ),
				'icon'        => 'shield-check',
			),
			'documents' => array(
				'label'       => __( 'Documents and limits', 'filewarden' ),
				'description' => __( 'Set document upload limits.', 'filewarden' ),
				'icon'        => 'files',
			),
			'retention' => array(
				'label'       => __( 'Retention', 'filewarden' ),
				'description' => __( 'Choose how long deleted documents remain in the trash.', 'filewarden' ),
				'icon'        => 'archive-box',
			),
			'audit'     => array(
				'label'       => __( 'Audit log', 'filewarden' ),
				'description' => __( 'Choose how long recorded events are retained.', 'filewarden' ),
				'icon'        => 'scroll-text',
			),
		);
		$filtered = apply_filters( 'filewarden_settings_sections', $sections );
		foreach ( is_array( $filtered ) ? $filtered : array() as $id => $section ) {
			if ( ! is_string( $id ) || '' === $id || sanitize_key( $id ) !== $id || ! is_array( $section )
				|| ! isset( $section['label'], $section['description'], $section['icon'] )
				|| ! is_string( $section['label'] ) || ! is_string( $section['description'] ) || ! is_string( $section['icon'] ) ) {
				continue; }
			$section['icon'] = sanitize_key( $section['icon'] );
			$sections[ $id ] = $section;
		}
		return $sections;
	}

	/** Validate every submitted value before persisting any setting.
	 *
	 * @param array        $source Posted settings.
	 * @param AuditService $audit Audit persistence.
	 * @throws \DomainException Without settings capability.
	 * @throws \InvalidArgumentException For invalid values.
	 */
	public static function save( array $source, AuditService $audit ) {
		if ( ! current_user_can( 'filewarden_manage_settings' ) ) {
			throw new \DomainException( 'Access denied.' ); }
		$values = array();
		foreach ( self::FIELDS as $name => $bounds ) {
			if ( ! array_key_exists( $name, $source ) ) {
				continue; }
			$value = RestController::integer( wp_unslash( $source[ $name ] ) );
			if ( $value < $bounds[1] || $value > $bounds[2] ) {
				throw new \InvalidArgumentException( 'Invalid settings.' ); }
			$values[ 'filewarden_' . $name ] = $value;
		}
		if ( array_key_exists( 'notification_seconds', $source ) ) {
			$value = RestController::integer( wp_unslash( $source['notification_seconds'] ) );
			if ( ! in_array( $value, Notice::DURATIONS, true ) ) {
				throw new \InvalidArgumentException( 'Invalid notification duration.' ); }
			$values['filewarden_notification_seconds'] = $value;
		}
		if ( ! $values ) {
			throw new \InvalidArgumentException( 'No settings submitted.' ); }
		$audit->record( 'settings_changed' );
		foreach ( $values as $name => $value ) {
			update_option( $name, $value ); }
	}

	/** Render real categories and health, without a security claim beyond the measured checks.
	 *
	 * @param DocumentService $service Services.
	 * @param AdminController $ui Form helpers.
	 */
	public static function page( DocumentService $service, AdminController $ui ) {
		if ( ! current_user_can( 'filewarden_manage_settings' ) ) {
			return; }
		$status  = 'unverified';
		$message = __( 'Not verified', 'filewarden' );
		try {
			$service->storage->root();
			$status  = 'verified';
			$message = __( 'Storage directory checks passed.', 'filewarden' );
		} catch ( \Throwable $error ) {
			$status  = str_contains( $error->getMessage(), 'verified' ) ? 'unverified' : 'critical';
			$message = __( 'Storage protection could not be verified. File operations remain blocked. Check your hosting configuration.', 'filewarden' );
		}
		$checked = time();
		update_option( 'filewarden_storage_checked_at', $checked, false );
		$warning = ! class_exists( '\ZipArchive' ) || wp_max_upload_size() < (int) get_option( 'filewarden_upload_mb', 50 ) * MB_IN_BYTES;
		if ( ! class_exists( '\DOMDocument' ) ) {
			$warning = true; }
		$labels = array(
			'unverified' => __( 'Not verified', 'filewarden' ),
			'verified'   => __( 'Verified', 'filewarden' ),
			'critical'   => __( 'Critical', 'filewarden' ),
		);
		echo '<div class="wrap fw-workspace fw-settings">';
		AdminBrand::heading( __( 'FileWarden settings', 'filewarden' ) );
		echo '<section class="fw-content fw-health" data-storage-status="' . esc_attr( $status ) . '"><div class="fw-health-summary"><span class="fw-action-icon fw-icon-shield-check" aria-hidden="true"></span><h2>' . esc_html__( 'FileWarden health', 'filewarden' ) . '</h2><strong class="fw-health-status">' . esc_html__( 'Storage', 'filewarden' ) . ' : ' . esc_html( $labels[ $status ] ) . '</strong><span class="fw-health-check">' . esc_html__( 'Last check', 'filewarden' ) . ' <time datetime="' . esc_attr( gmdate( 'c', $checked ) ) . '">' . esc_html( wp_date( 'Y-m-d H:i:s T', $checked ) ) . '</time></span></div>';
		if ( 'verified' !== $status ) {
			echo '<p class="fw-health-alert">' . esc_html( $message ) . '</p>'; }
		if ( $warning ) {
			echo '<p class="fw-health-warning">' . esc_html__( 'Warning: server upload limits or ZIP support restrict available imports.', 'filewarden' ) . '</p>'; }
		echo '<details class="fw-health-details"><summary>' . esc_html__( 'Details', 'filewarden' ) . '</summary><dl><dt>' . esc_html__( 'Storage', 'filewarden' ) . '</dt><dd><strong>' . esc_html( $labels[ $status ] ) . '</strong> — ' . esc_html( $message ) . '</dd><dt>' . esc_html__( 'Configuration', 'filewarden' ) . '</dt><dd>' . esc_html( $warning ? __( 'Warning: server upload limits or ZIP support restrict available imports.', 'filewarden' ) : __( 'Required upload capabilities are available.', 'filewarden' ) ) . '</dd><dt>' . esc_html__( 'Last check', 'filewarden' ) . '</dt><dd><time>' . esc_html( wp_date( 'Y-m-d H:i:s T', $checked ) ) . '</time></dd></dl></details></section>';
		$database = DatabaseUpgrade::status();
		$pending  = (bool) get_option( DatabaseUpgrade::CHECKPOINT );
		if ( 'ready' !== $database['state'] || $pending ) {
			echo '<p class="fw-database-alert">' . esc_html__( 'Database attention required. Review Storage and security; folder deletion remains blocked until transactional tables are verified.', 'filewarden' ) . '</p>'; }
		if ( $warning ) {
			Notice::show( __( 'Server limits or missing PHP extensions restrict imports. Review Storage and security before uploading.', 'filewarden' ), 'warning' ); }
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only post/redirect feedback, never authorization.
		$result = isset( $_GET['result'] ) && is_string( $_GET['result'] ) ? sanitize_key( wp_unslash( $_GET['result'] ) ) : '';
		if ( $result ) {
			if ( 'database_repaired' === $result && Notice::verified( $result ) ) {
				Notice::show( __( 'The three FileWarden tables use InnoDB. Their records and indexes were verified. Folder deletion can now be retried.', 'filewarden' ), 'success', __( 'Database verified', 'filewarden' ) );
			} elseif ( 'database_failed' === $result ) {
				Notice::show( __( 'Database repair could not be confirmed. No table was reset. A partial conversion may have occurred; review the database and retry the repair after checking your backup.', 'filewarden' ), 'error', __( 'Database repair incomplete', 'filewarden' ), true );
			} elseif ( 'success' !== $result || Notice::verified( $result ) ) {
				Notice::show( 'success' === $result ? __( 'Settings saved.', 'filewarden' ) : __( 'Settings could not be saved.', 'filewarden' ), 'success' === $result ? 'success' : 'error', 'success' === $result ? __( 'Settings saved', 'filewarden' ) : __( 'Settings not saved', 'filewarden' ) ); }
		}
		$sections = self::sections();
		echo '<div class="fw-settings-layout"><nav class="fw-settings-nav" aria-label="' . esc_attr__( 'Settings categories', 'filewarden' ) . '"><p class="fw-settings-kicker">' . esc_html__( 'Preferences', 'filewarden' ) . '</p><div class="fw-settings-links">';
		foreach ( $sections as $id => $section ) {
			$icon = plugins_url( 'assets/icons/' . $section['icon'] . '.svg', FILEWARDEN_PATH . 'filewarden.php' );
			echo '<a href="#fw-settings-' . esc_attr( $id ) . '" data-settings-section="' . esc_attr( $id ) . '"><span class="fw-settings-icon" aria-hidden="true" style="' . esc_attr( '--fw-settings-icon:url(' . $icon . ')' ) . '"></span><span class="fw-settings-label">' . esc_html( $section['label'] ) . '</span></a>'; }
		echo '</div></nav><section class="fw-content">';
		$ui->form_start( 'settings' );
		foreach ( $sections as $id => $section ) {
			echo '<section id="fw-settings-' . esc_attr( $id ) . '" class="fw-settings-panel"><h2 tabindex="-1">' . esc_html( $section['label'] ) . '</h2><p class="fw-settings-description">' . esc_html( $section['description'] ) . '</p>';
			if ( 'general' === $id ) {
				echo '<p class="fw-settings-version">FileWarden ' . esc_html( FILEWARDEN_VERSION ) . '</p><p>' . esc_html__( 'Single-site document management. Publishing and document permissions remain independent of these limits.', 'filewarden' ) . '</p><h3>' . esc_html__( 'Notifications', 'filewarden' ) . '</h3><p><label for="fw-notification-seconds">' . esc_html__( 'Automatic display duration', 'filewarden' ) . '</label><select id="fw-notification-seconds" name="notification_seconds" aria-describedby="fw-notification-help">';
				foreach ( Notice::DURATIONS as $seconds ) {
					/* translators: %d: notification duration in seconds. */
					$duration_label = sprintf( __( '%d seconds', 'filewarden' ), $seconds );
					if ( 10 === $seconds ) {
						$duration_label .= ' — ' . __( 'default', 'filewarden' ); }
					echo '<option value="' . esc_attr( $seconds ) . '"' . selected( Notice::duration(), $seconds, false ) . '>' . esc_html( $duration_label ) . '</option>';
				}
				echo '</select></p><p id="fw-notification-help" class="description">' . esc_html__( 'Set how long notifications remain visible before they disappear automatically.', 'filewarden' ) . '</p>'; }
			if ( 'storage' === $id ) {
				echo '<p>' . esc_html__( 'Documents are stored separately from the plugin. Storage operations remain blocked until protection is verified.', 'filewarden' ) . '</p><p>' . esc_html( $message ) . '</p>'; }
			if ( 'storage' === $id ) {
				self::database_panel( $database, $pending ); }
			$field = match ( $id ) {
				'documents' => 'upload_mb', 'retention' => 'trash_days', 'audit' => 'audit_days', default => null };
			if ( $field ) {
				$label = match ( $field ) {
					'upload_mb' => __( 'Upload limit (MB, also bounded by server limits)', 'filewarden' ), 'trash_days' => __( 'Trash retention (days)', 'filewarden' ), default => __( 'Audit retention (days)', 'filewarden' ) };
				$bounds = self::FIELDS[ $field ];
				echo '<p><label for="fw-setting-' . esc_attr( $field ) . '">' . esc_html( $label ) . '</label><input type="number" id="fw-setting-' . esc_attr( $field ) . '" name="' . esc_attr( $field ) . '" min="' . esc_attr( $bounds[1] ) . '" max="' . esc_attr( $bounds[2] ) . '" value="' . esc_attr( get_option( 'filewarden_' . $field, $bounds[0] ) ) . '" required></p>';
			}
			if ( 'documents' === $id ) {
				echo '<p>' . esc_html__( 'ZIP files remain downloadable archives; they are inspected but never extracted automatically.', 'filewarden' ) . '</p><p>' . esc_html__( 'Executable files, active content, macros and unsafe archives are refused.', 'filewarden' ) . '</p>'; }
			if ( 'audit' === $id ) {
				echo '<a class="button" href="' . esc_url( admin_url( 'admin.php?page=filewarden-audit' ) ) . '">' . esc_html__( 'Open audit log', 'filewarden' ) . '</a>'; }
			echo '</section>';
		}
		submit_button( __( 'Save settings', 'filewarden' ) );
		echo '</form>';
		$ui->form_start( 'database_repair' );
		echo '</form></section></div></div>';
	}

	/** Render an explicit repair offer; controls belong to a separate non-nested form.
	 *
	 * @param array $database Measured table engines.
	 * @param bool  $pending Interrupted repair checkpoint.
	 */
	private static function database_panel( array $database, $pending ) {
		echo '<div class="fw-database-panel"><h3>' . esc_html__( 'Transactional database tables', 'filewarden' ) . '</h3>';
		$messages = array(
			'ready'        => __( 'The three FileWarden tables use InnoDB.', 'filewarden' ),
			'incompatible' => __( 'Existing MyISAM tables prevent safe folder deletion. Convert the three FileWarden tables to InnoDB after backing up.', 'filewarden' ),
			'missing'      => __( 'A FileWarden table is missing or its engine is unreadable. Ask your host to verify the installation before repairing.', 'filewarden' ),
			'unavailable'  => __( 'Table metadata could not be read. Ask your host to check database permissions. No engine change can be offered safely.', 'filewarden' ),
			'unsupported'  => __( 'An unsupported database engine was found. Ask your host to review it; automatic conversion is unavailable.', 'filewarden' ),
		);
		echo '<p>' . esc_html( $messages[ $database['state'] ] ) . '</p><ul>';
		foreach ( $database['engines'] as $table => $engine ) {
			echo '<li><code>' . esc_html( $table ) . '</code> : ' . esc_html( $engine ) . '</li>'; }
		echo '</ul>';
		if ( $pending ) {
			echo '<p class="fw-database-alert">' . esc_html__( 'A repair checkpoint remains. FileWarden writes are paused until the integrity check and repair succeed. Do not delete this checkpoint or reset the tables.', 'filewarden' ) . '</p>'; }
		if ( 'incompatible' === $database['state'] || ( $pending && 'ready' === $database['state'] ) ) {
			echo '<p>' . esc_html__( 'Back up the complete database, encryption key and document storage first. Pause external writers and allow time and free disk space for the conversion. ALTER TABLE commits each table separately; a failed repair may need to be resumed. Document files are not moved.', 'filewarden' ) . '</p><p><label><input type="checkbox" name="backup_confirmed" value="1" form="fw-database-repair" required> ' . esc_html__( 'I have a complete backup and have paused external writes for this maintenance operation.', 'filewarden' ) . '</label></p><button class="button" type="submit" form="fw-database-repair"><span class="fw-action-icon fw-icon-shield-check" aria-hidden="true"></span>' . esc_html__( 'Verify and repair database tables', 'filewarden' ) . '</button>'; }
		echo '</div>';
	}
}
