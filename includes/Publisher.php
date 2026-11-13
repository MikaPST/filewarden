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
		echo '<div class="wrap fw-workspace fw-publisher"><header class="fw-heading"><div><span class="fw-brand">FILEWARDEN</span><h1>' . esc_html__( 'Publication', 'filewarden' ) . '</h1></div></header>';
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
		echo '<section class="fw-content"><form id="fw-publisher" method="get" data-endpoint="' . esc_url( admin_url( 'admin-post.php' ) ) . '" data-return="' . esc_url( admin_url( 'admin.php?page=filewarden-publication' ) ) . '" data-nonce="' . esc_attr( wp_create_nonce( 'filewarden-preview' ) ) . '"><input type="hidden" name="page" value="filewarden-publication"><p><label for="fw-publish-folder">' . esc_html__( 'Folder to publish', 'filewarden' ) . '</label><select id="fw-publish-folder" name="folder_id">';
		foreach ( $choices as $id => $label ) {
			echo '<option value="' . esc_attr( $id ) . '" data-uuid="' . esc_attr( FolderReference::uuid( $id ) ) . '"' . selected( $id, $selected, false ) . '>' . esc_html( $label ) . '</option>'; }
		echo '</select></p>';
		self::options(
			'view',
			__( 'Display', 'filewarden' ),
			array(
				'list' => __( 'Detailed list', 'filewarden' ),
				'grid' => __( 'Grid', 'filewarden' ),
			),
			$view
		);
		echo '<div data-preview-config="denied">';
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
		echo '</div><button class="button fw-publisher-fallback">' . esc_html__( 'Update preview', 'filewarden' ) . '</button></form><hr><label for="fw-shortcode">' . esc_html__( 'Copy into the Elementor Shortcode widget', 'filewarden' ) . '</label><div class="fw-shortcode-row"><textarea id="fw-shortcode" rows="2" readonly>' . esc_textarea( '[filewarden folder="' . $reference . '" view="' . $view . '" denied="' . $denied . '"]' ) . '</textarea><button type="button" class="button" id="fw-copy-shortcode" hidden>' . esc_html__( 'Copy', 'filewarden' ) . '</button></div><p id="fw-copy-status" role="status" data-success="' . esc_attr__( 'Shortcode copied.', 'filewarden' ) . '" data-failure="' . esc_attr__( 'Copy failed. Select the shortcode and copy it manually.', 'filewarden' ) . '"></p></section><section class="fw-content fw-preview-box"><div class="fw-preview-toolbar"><h2>' . esc_html__( 'Preview', 'filewarden' ) . '</h2><div class="fw-preview-modes" role="tablist" aria-label="' . esc_attr__( 'Preview state', 'filewarden' ) . '"><button type="button" role="tab" aria-selected="true" data-preview-mode="authorized">' . esc_html__( 'Authorized display', 'filewarden' ) . '</button><button type="button" role="tab" aria-selected="false" data-preview-mode="denied">' . esc_html__( 'Access denied', 'filewarden' ) . '</button></div><label class="fw-preview-fallback"><input type="checkbox" id="fw-preview-denied"> ' . esc_html__( 'Preview anonymous unavailable state', 'filewarden' ) . '</label></div><p>' . esc_html__( 'The live preview uses your account permissions. Visitors may see fewer documents.', 'filewarden' ) . '</p><p id="fw-preview-status" role="status" data-loading="' . esc_attr__( 'Loading preview…', 'filewarden' ) . '" data-ready="' . esc_attr__( 'Preview updated.', 'filewarden' ) . '" data-failure="' . esc_attr__( 'Preview unavailable. Change a setting to retry.', 'filewarden' ) . '"></p>';
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
		echo '</div></section></div>';
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
