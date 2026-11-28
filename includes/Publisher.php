<?php
/**
 * Live shortcode configuration, independent of content permissions.
 *
 * @package FileWarden
 */

namespace FileWarden;

/** Server-rendered configuration has a fallback when scripting is unavailable. */
final class Publisher {
	/**
	 * Render an authorized folder picker and live publication preview.
	 *
	 * @param DocumentService $service Services.
	 */
	public static function page( DocumentService $service ) {
		if ( ! current_user_can( 'filewarden_manage_settings' ) ) {
			return; }
		$choices = $service->folder_choices();
		unset( $choices[0] );
		echo '<div class="wrap fw-workspace fw-publisher" lang="' . esc_attr( Language::html() ) . '">';
		AdminBrand::heading( __( 'Publication', 'filewarden' ) );
		if ( ! $choices ) {
			echo '<section class="fw-content"><p>' . esc_html__( 'Create a folder before publishing documents.', 'filewarden' ) . '</p><a class="button" href="' . esc_url( admin_url( 'admin.php?page=filewarden&tab=folder' ) ) . '">' . esc_html__( 'Create folder', 'filewarden' ) . '</a></section></div>';
			return; }
        // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only configuration; choices are authorized above.
		$selected = isset( $_GET['folder_id'] ) && is_scalar( $_GET['folder_id'] ) ? absint( $_GET['folder_id'] ) : 0;
		if ( ! isset( $choices[ $selected ] ) ) {
			$selected = array_key_first( $choices ); }
        // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Presentation allowlist.
		$view = isset( $_GET['view'] ) && 'grid' === $_GET['view'] ? 'grid' : 'list';
        // phpcs:ignore WordPress.Security.NonceVerification.Recommended,WordPress.Security.ValidatedSanitizedInput -- denied_style() strictly allowlists scalar values.
		$denied    = Publication::denied_style( $_GET['denied'] ?? 'centered' );
		$reference = FolderReference::uuid( $selected );
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Initial presentation only, no mutation or success assertion.
		$appearance = isset( $_GET['fw_operation'] ) && 'appearance' === $_GET['fw_operation'];
		echo '<nav class="fw-tabs fw-publication-tabs" role="tablist" aria-label="' . esc_attr__( 'Publication', 'filewarden' ) . '" hidden>';
		foreach ( array(
			'integration' => __( 'Folder integration', 'filewarden' ),
			'appearance'  => __( 'Public appearance', 'filewarden' ),
		) as $key => $label ) {
			echo '<button type="button" role="tab" id="fw-publication-tab-' . esc_attr( $key ) . '" aria-selected="' . ( 'integration' === $key ? 'true' : 'false' ) . '" aria-controls="fw-publication-panel-' . esc_attr( $key ) . '" data-publication-tab="' . esc_attr( $key ) . '"><span class="fw-action-icon fw-icon-' . ( 'integration' === $key ? 'code' : 'palette' ) . '" aria-hidden="true"></span>' . esc_html( $label ) . '</button>';
		}
		echo '</nav>';
		self::shortcode_card( $reference, $view, $denied );
		echo '<section class="fw-content fw-publication-settings" data-initial-tab="' . ( $appearance ? 'appearance' : 'integration' ) . '"><div class="fw-publication-panel" id="fw-publication-panel-integration" role="tabpanel" aria-labelledby="fw-publication-tab-integration"><h2>' . esc_html__( 'Folder integration', 'filewarden' ) . '</h2><form id="fw-publisher" method="get" data-endpoint="' . esc_url( admin_url( 'admin-post.php' ) ) . '" data-return="' . esc_url( admin_url( 'admin.php?page=filewarden-publication' ) ) . '" data-nonce="' . esc_attr( wp_create_nonce( 'filewarden-preview' ) ) . '"><input type="hidden" name="page" value="filewarden-publication"><p><label for="fw-publish-folder">' . esc_html__( 'Folder to publish', 'filewarden' ) . '</label><select id="fw-publish-folder" name="folder_id">';
		foreach ( $choices as $id => $label ) {
			echo '<option value="' . esc_attr( $id ) . '" data-uuid="' . esc_attr( FolderReference::uuid( $id ) ) . '"' . selected( $id, $selected, false ) . '>' . esc_html( $label ) . '</option>'; }
		echo '</select></p><div data-preview-config="authorized">';
		self::options(
			'view',
			__( 'Display', 'filewarden' ),
			array(
				'list' => __( 'Detailed list', 'filewarden' ),
				'grid' => __( 'Grid', 'filewarden' ),
			),
			$view
		);
		echo '</div><div data-preview-config="denied">';
		self::options(
			'denied',
			__( 'Unavailable space design', 'filewarden' ),
			array(
				'compact'   => __( 'Compact', 'filewarden' ),
				'centered'  => __( 'Centered card', 'filewarden' ),
				'editorial' => __( 'Editorial banner', 'filewarden' ),
			),
			$denied
		);
		echo '</div><button class="button fw-publisher-fallback">' . esc_html__( 'Update preview', 'filewarden' ) . '</button></form>';
		self::login_form( $service );
		echo '</div><div class="fw-publication-panel" id="fw-publication-panel-appearance" role="tabpanel" aria-labelledby="fw-publication-tab-appearance">';
		AppearanceConsole::form( $service );
		echo '</div></section><div class="fw-publication-output">';
		echo '<section class="fw-content fw-preview-box"><div class="fw-preview-toolbar"><h2>' . esc_html__( 'Preview', 'filewarden' ) . '</h2><div class="fw-preview-controls"><div class="fw-preview-modes" role="tablist" aria-label="' . esc_attr__( 'Preview state', 'filewarden' ) . '"><button type="button" role="tab" aria-selected="true" data-preview-mode="authorized"><span class="fw-action-icon fw-icon-shield-check" aria-hidden="true"></span>' . esc_html__( 'Authorized', 'filewarden' ) . '</button><button type="button" role="tab" aria-selected="false" data-preview-mode="denied"><span class="fw-action-icon fw-icon-lock-keyhole" aria-hidden="true"></span>' . esc_html__( 'Access denied', 'filewarden' ) . '</button></div>';
		echo '<div class="fw-appearance-devices" role="group" aria-label="' . esc_attr__( 'Preview device', 'filewarden' ) . '" hidden>';
		foreach ( array(
			'desktop' => __( 'Desktop preview', 'filewarden' ),
			'mobile'  => __( 'Mobile preview', 'filewarden' ),
		) as $device => $label ) {
			echo '<button type="button" class="button fw-preview-device" aria-label="' . esc_attr( $label ) . '" aria-pressed="' . ( 'desktop' === $device ? 'true' : 'false' ) . '" data-preview-device="' . esc_attr( $device ) . '"><span class="fw-action-icon fw-icon-' . ( 'desktop' === $device ? 'monitor' : 'smartphone' ) . '" aria-hidden="true"></span><span class="fw-action-tooltip" aria-hidden="true">' . esc_html( $label ) . '</span></button>';
		}
		echo '</div></div><label class="fw-preview-fallback"><input type="checkbox" id="fw-preview-denied"> ' . esc_html__( 'Preview anonymous unavailable state', 'filewarden' ) . '</label></div><p>' . esc_html__( 'The live preview uses your account permissions. Visitors may see fewer documents.', 'filewarden' ) . '</p><p id="fw-preview-status" role="status" data-loading="' . esc_attr__( 'Loading preview…', 'filewarden' ) . '" data-ready="' . esc_attr__( 'Preview updated.', 'filewarden' ) . '" data-failure="' . esc_attr__( 'Preview unavailable. Change a setting to retry.', 'filewarden' ) . '"></p>';
        // phpcs:disable WordPress.Security.EscapeOutput.OutputNotEscaped -- Shortcode renderer escapes all frame attributes.
		echo '<div id="fw-preview-live-panel" role="tabpanel" aria-labelledby="fw-preview-mode-authorized">';
		echo Publication::shortcode(
			array(
				'folder' => $reference,
				'view'   => $view,
				'denied' => $denied,
			)
		);
		// phpcs:enable WordPress.Security.EscapeOutput.OutputNotEscaped
		echo '</div></section></div></div>';
	}

	/** Keep the integration output visible beside either settings tab.
	 *
	 * @param string $reference Permanent folder reference.
	 * @param string $view List or grid.
	 * @param string $denied Unavailable-space style.
	 */
	private static function shortcode_card( $reference, $view, $denied ) {
		echo '<section class="fw-content fw-shortcode-card" aria-labelledby="fw-shortcode-title"><h2 id="fw-shortcode-title"><span class="fw-action-icon fw-icon-code" aria-hidden="true"></span>' . esc_html__( 'Shortcode', 'filewarden' ) . '</h2><p id="fw-shortcode-help" class="description">' . esc_html__( 'Copy this code into the WordPress Shortcode block or the Elementor Shortcode widget.', 'filewarden' ) . '</p><div class="fw-shortcode-row"><textarea id="fw-shortcode" aria-label="' . esc_attr__( 'Shortcode', 'filewarden' ) . '" aria-describedby="fw-shortcode-help" rows="2" readonly>' . esc_textarea( '[filewarden folder="' . $reference . '" view="' . $view . '" denied="' . $denied . '"]' ) . '</textarea><button type="button" class="button" id="fw-copy-shortcode" hidden><span class="fw-action-icon fw-icon-files" aria-hidden="true"></span>' . esc_html__( 'Copy', 'filewarden' ) . '</button></div><p id="fw-copy-status" role="status" data-success="' . esc_attr__( 'Shortcode copied.', 'filewarden' ) . '" data-failure="' . esc_attr__( 'Copy failed. Select the shortcode and copy it manually.', 'filewarden' ) . '"></p></section>';
	}

	/** Render site-wide connection settings independently of the shortcode form.
	 *
	 * @param DocumentService $service Shared services.
	 */
	private static function login_form( DocumentService $service ) {
		$value = PublicationLogin::current();
		$ui    = new AdminController( $service );
		echo '<section class="fw-publication-login" data-preview-config="denied" data-wordpress-url="' . esc_url( wp_login_url( admin_url( 'admin.php?page=filewarden-publication' ) ) ) . '"><h3><span class="fw-action-icon fw-icon-log-in" aria-hidden="true"></span>' . esc_html__( 'Visitor sign-in', 'filewarden' ) . '</h3><p class="description">' . esc_html__( 'Shared by all publications on this site.', 'filewarden' ) . '</p>';
		$ui->form_start( 'publication_login' );
		echo '<fieldset><legend>' . esc_html__( 'Sign-in button destination', 'filewarden' ) . '</legend>';
		foreach ( array(
			'wordpress' => __( 'WordPress sign-in — default', 'filewarden' ),
			'custom'    => __( 'Custom sign-in page', 'filewarden' ),
			'elementor' => __( 'Elementor popup', 'filewarden' ),
		) as $mode => $label ) {
			echo '<label class="fw-login-mode"><input type="radio" name="publication_login[mode]" value="' . esc_attr( $mode ) . '"' . checked( $value['mode'], $mode, false ) . '> ' . esc_html( $label ) . '</label>';
			if ( 'elementor' === $mode ) {
				echo '<details class="fw-login-help"><summary aria-label="' . esc_attr__( 'About Elementor popup sign-in', 'filewarden' ) . '">ⓘ</summary><p>' . esc_html__( 'Find your popup in the Shortcode column. For [elementor-template id="1252"], enter 1252 or paste the shortcode. Elementor Pro must be available on the containing page; otherwise WordPress sign-in is used.', 'filewarden' ) . '</p><a href="' . esc_url(
					add_query_arg(
						array(
							'post_type'              => 'elementor_library',
							'tabs_group'             => 'popup',
							'elementor_library_type' => 'popup',
						),
						admin_url( 'edit.php' )
					)
				) . '" target="_blank" rel="noopener noreferrer">' . esc_html__( 'Open Elementor popups (new tab)', 'filewarden' ) . '</a></details>';
			}
		}
		echo '</fieldset><div class="fw-login-elementor"><p><label for="fw-login-popup">' . esc_html__( 'Elementor popup ID', 'filewarden' ) . '</label><input id="fw-login-popup" type="text" maxlength="100" name="publication_login[popup_id]" aria-describedby="fw-login-popup-help" value="' . esc_attr( $value['popup_id'] ?? '' ) . '" placeholder="1252"><small id="fw-login-popup-help">' . esc_html__( 'Enter the ID or paste the Elementor shortcode.', 'filewarden' ) . '</small></p><p class="description">' . esc_html__( 'The popup opens on the page containing the publication. WordPress sign-in remains the fallback.', 'filewarden' ) . '</p></div><div class="fw-login-custom"><p><label for="fw-login-url">' . esc_html__( 'Sign-in page URL', 'filewarden' ) . '</label><input type="url" id="fw-login-url" name="publication_login[url]" maxlength="2048" value="' . esc_attr( $value['url'] ) . '" placeholder="https://example.test/sign-in/"></p><p class="description">' . esc_html__( 'The custom page controls where visitors go after signing in.', 'filewarden' ) . '</p><a class="fw-login-check" target="_blank" rel="noopener noreferrer" href="' . esc_url( PublicationLogin::destination( home_url( '/' ) ) ) . '"><span class="fw-action-icon fw-icon-external-link" aria-hidden="true"></span>' . esc_html__( 'Open page to check', 'filewarden' ) . '</a></div><p class="fw-login-validation" role="status" data-invalid="' . esc_attr__( 'Enter a complete HTTP or HTTPS URL without credentials.', 'filewarden' ) . '" data-invalid-popup="' . esc_attr__( 'Enter a positive Elementor popup ID.', 'filewarden' ) . '"></p><p class="submit"><button class="button fw-button-accent" type="submit"><span class="fw-action-icon fw-icon-save" aria-hidden="true"></span>' . esc_html__( 'Save sign-in settings', 'filewarden' ) . '</button></p></form></section>';
		// phpcs:disable WordPress.Security.NonceVerification.Recommended -- Presentation feedback; success requires the signed current-user receipt.
		if ( isset( $_GET['fw_operation'] ) && 'publication_login' === $_GET['fw_operation'] && isset( $_GET['result'] ) ) {
			$success = 'success' === $_GET['result'];
			if ( ! $success || Notice::verified( 'success' ) ) {
				Notice::show( $success ? __( 'Sign-in settings saved for all site publications.', 'filewarden' ) : __( 'Sign-in settings could not be saved. Check the URL and your permissions.', 'filewarden' ), $success ? 'success' : 'error' );
			}
		}
		// phpcs:enable WordPress.Security.NonceVerification.Recommended
	}
	/**
	 * Render joined radio buttons, still usable without scripts.
	 *
	 * @param string $name Group.
	 * @param string $legend Label.
	 * @param array  $options Choices.
	 * @param string $selected Selected choice.
	 */
	private static function options( $name, $legend, array $options, $selected ) {
		echo '<fieldset class="fw-segment-field"><legend>' . esc_html( $legend ) . '</legend><div class="fw-segments">';
		foreach ( $options as $value => $label ) {
			$icon = 'grid' === $value ? 'screenoptions' : ( 'list' === $value ? 'list-view' : ( 'centered' === $value ? 'align-center' : ( 'compact' === $value ? 'align-left' : 'welcome-widgets-menus' ) ) );
			echo '<label><input type="radio" name="' . esc_attr( $name ) . '" value="' . esc_attr( $value ) . '"' . checked( $selected, $value, false ) . '><span class="dashicons dashicons-' . esc_attr( $icon ) . '" aria-hidden="true"></span><span>' . esc_html( $label ) . '</span></label>';
		}
		echo '</div></fieldset>';
	}
}
