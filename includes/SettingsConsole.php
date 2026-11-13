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
			$message = $error instanceof \RuntimeException ? $error->getMessage() : __( 'Unexpected storage error.', 'filewarden' );
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
		echo '<div class="wrap fw-workspace fw-settings"><header class="fw-heading"><h1>' . esc_html__( 'FileWarden settings', 'filewarden' ) . '</h1></header><section class="fw-content fw-health" data-storage-status="' . esc_attr( $status ) . '"><h2>' . esc_html__( 'FileWarden health', 'filewarden' ) . '</h2><dl><dt>' . esc_html__( 'Storage', 'filewarden' ) . '</dt><dd><strong>' . esc_html( $labels[ $status ] ) . '</strong> — ' . esc_html( $message ) . '</dd><dt>' . esc_html__( 'Configuration', 'filewarden' ) . '</dt><dd>' . esc_html( $warning ? __( 'Warning: server upload limits or ZIP support restrict available imports.', 'filewarden' ) : __( 'Required upload capabilities are available.', 'filewarden' ) ) . '</dd><dt>' . esc_html__( 'Last check', 'filewarden' ) . '</dt><dd><time>' . esc_html( wp_date( 'Y-m-d H:i:s T', $checked ) ) . '</time></dd></dl></section>';
		if ( $warning ) {
			Notice::show( __( 'Server limits or missing PHP extensions restrict imports. Review Storage and security before uploading.', 'filewarden' ), 'warning' ); }
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only post/redirect feedback, never authorization.
		$result = isset( $_GET['result'] ) && is_string( $_GET['result'] ) ? sanitize_key( wp_unslash( $_GET['result'] ) ) : '';
		if ( $result ) {
			Notice::show( 'success' === $result ? __( 'Settings saved.', 'filewarden' ) : __( 'Settings could not be saved.', 'filewarden' ), 'success' === $result ? 'success' : 'error' ); }
		$sections = array(
			'general'   => __( 'General', 'filewarden' ),
			'storage'   => __( 'Storage and security', 'filewarden' ),
			'documents' => __( 'Documents and limits', 'filewarden' ),
			'retention' => __( 'Retention', 'filewarden' ),
			'audit'     => __( 'Audit log', 'filewarden' ),
		);
		echo '<div class="fw-settings-layout"><nav class="fw-settings-nav" aria-label="' . esc_attr__( 'Settings categories', 'filewarden' ) . '">';
		foreach ( $sections as $id => $label ) {
			echo '<a href="#fw-settings-' . esc_attr( $id ) . '" data-settings-section="' . esc_attr( $id ) . '">' . esc_html( $label ) . '</a>'; }
		echo '</nav><section class="fw-content">';
		$ui->form_start( 'settings' );
		foreach ( $sections as $id => $label ) {
			echo '<section id="fw-settings-' . esc_attr( $id ) . '" class="fw-settings-panel"><h2 tabindex="-1">' . esc_html( $label ) . '</h2>';
			if ( 'general' === $id ) {
				echo '<p>FileWarden ' . esc_html( FILEWARDEN_VERSION ) . '</p><p>' . esc_html__( 'Single-site document management. Publishing and document permissions remain independent of these limits.', 'filewarden' ) . '</p>'; }
			if ( 'storage' === $id ) {
				echo '<p>' . esc_html__( 'Documents are stored separately from the plugin. Storage operations remain blocked until protection is verified.', 'filewarden' ) . '</p><p>' . esc_html( $message ) . '</p>'; }
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
		echo '</form></section></div></div>';
	}
}
