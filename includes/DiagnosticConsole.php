<?php
/** Settings-only, nonce-protected diagnostics.
 *
 * @package FileWarden
 */

namespace FileWarden;

/** Read results locally; never repair or expose raw errors. */
final class DiagnosticConsole {
	/** Render outside the settings save form. */
	public static function render() {
		echo '<section id="fw-settings-diagnostic" class="fw-settings-panel fw-diagnostic"><h2 tabindex="-1"><span class="fw-action-icon fw-icon-stethoscope" aria-hidden="true"></span>' . esc_html__( 'Diagnostic', 'filewarden' ) . '</h2><p class="fw-settings-description">' . esc_html__( 'Check prerequisites and identify the causes of blocked operations.', 'filewarden' ) . '</p><div class="fw-diagnostic-actions"><button type="button" class="button button-primary" data-diagnostic-run disabled><span class="fw-action-icon fw-icon-stethoscope" aria-hidden="true"></span>' . esc_html__( 'Run diagnostic', 'filewarden' ) . '</button><button type="button" class="button" data-diagnostic-download disabled><span class="fw-action-icon fw-icon-download" aria-hidden="true"></span>' . esc_html__( 'Technical report', 'filewarden' ) . '</button></div><p data-diagnostic-status role="status" aria-live="polite">' . esc_html__( 'No diagnostic has been run in this page.', 'filewarden' ) . '</p><div data-diagnostic-checks></div><details class="fw-diagnostic-report" hidden><summary>' . esc_html__( 'View report contents', 'filewarden' ) . '</summary><p>' . esc_html__( 'Only versions, limits and measured results are included. No passwords, keys, physical paths, IP addresses, document names or account identities.', 'filewarden' ) . '</p><pre></pre></details><p class="description">' . esc_html__( 'Diagnostics do not repair tables or modify documents. Successful checks are not a complete security or compatibility certification.', 'filewarden' ) . '</p><noscript><p>' . esc_html__( 'Enable JavaScript to run diagnostics and download the report.', 'filewarden' ) . '</p></noscript></section>';
	}

	/** Load local UI and safe translated labels. */
	public static function assets() {
		wp_enqueue_script( 'filewarden-diagnostic', plugins_url( 'assets/diagnostic.js', FILEWARDEN_PATH . 'filewarden.php' ), array( 'filewarden-notices' ), FILEWARDEN_VERSION . '-' . substr( hash_file( 'sha256', FILEWARDEN_PATH . 'assets/diagnostic.js' ), 0, 12 ), true ); // phpcs:ignore WordPress.WP.AlternativeFunctions -- Content-address the same-version corrective candidate.
		wp_localize_script(
			'filewarden-diagnostic',
			'filewardenDiagnostic',
			array(
				'storageURL' => admin_url( 'admin.php?page=filewarden-settings&repair=wordpress#fw-settings-storage' ),
				'convert'    => __( 'Prepare conversion to InnoDB', 'filewarden' ),
				'storage'    => __( 'Open Storage and security', 'filewarden' ),
				'endpoint'   => admin_url( 'admin-ajax.php' ),
				'nonce'      => wp_create_nonce( 'filewarden_diagnostic' ),
				'groups'     => Diagnostics::groups(),
				'states'     => array(
					'good'    => __( 'Verified', 'filewarden' ),
					'blocked' => __( 'Blocking', 'filewarden' ),
					'unknown' => __( 'Not verified', 'filewarden' ),
				),
				'working'    => __( 'Running the server checks…', 'filewarden' ),
				'good'       => __( 'No blocking issue was detected by these checks.', 'filewarden' ),
				'blocked'    => __( 'An operation requires attention. Open the details below.', 'filewarden' ),
				'unknown'    => __( 'Diagnostic incomplete. Some checks could not be verified.', 'filewarden' ),
				'failed'     => __( 'Diagnostic unavailable. Check your session and permissions, then retry.', 'filewarden' ),
				'downloaded' => __( 'The browser has been asked to download the technical report.', 'filewarden' ),
			)
		);
	}

	/** Run only for a current authorized user and valid diagnostic nonce.
	 *
	 * @param DocumentService $service Shared services.
	 */
	public static function handle( DocumentService $service ) {
		check_ajax_referer( 'filewarden_diagnostic' );
		if ( ! current_user_can( 'filewarden_manage_settings' ) ) {
			wp_send_json_error( array( 'message' => __( 'Diagnostic access denied.', 'filewarden' ) ), 403 );
		}
		nocache_headers();
		try {
			wp_send_json_success( Diagnostics::run( $service ) );
		} catch ( \Throwable $error ) {
			wp_send_json_error( array( 'message' => __( 'Diagnostic could not complete. No automatic repair was performed.', 'filewarden' ) ), 400 );
		}
	}
}
