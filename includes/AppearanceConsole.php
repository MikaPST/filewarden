<?php
/** Site-wide public appearance controls, separate from publication permissions.
 *
 * @package FileWarden
 */

namespace FileWarden;

/** Render persistent settings beside the existing authorized publication preview. */
final class AppearanceConsole {
	/** Render the progressive settings form using the existing administration POST.
	 *
	 * @param DocumentService $service Shared services.
	 */
	public static function form( DocumentService $service ) {
		$value = FrontendAppearance::current();
		$ui    = new AdminController( $service );
		echo '<section class="fw-appearance"><h2>' . esc_html__( 'Public appearance', 'filewarden' ) . '</h2><p class="description">' . esc_html__( 'One appearance for all FileWarden publications on this site. Changes apply only after saving.', 'filewarden' ) . '</p>';
		$ui->form_start( 'appearance' );
		echo '<input type="hidden" name="appearance[version]" value="1"><section class="fw-appearance-section"><h3>' . esc_html__( 'Color palette', 'filewarden' ) . '</h3><div class="fw-appearance-presets">';
		$labels             = array(
			'filewarden' => __( 'FileWarden', 'filewarden' ),
			'neutral'    => __( 'Neutral', 'filewarden' ),
			'nature'     => __( 'Nature', 'filewarden' ),
			'dark'       => __( 'FileWarden dark', 'filewarden' ),
			'terracotta' => __( 'Terracotta', 'filewarden' ),
			'custom'     => __( 'Custom', 'filewarden' ),
		);
		$palettes           = FrontendAppearance::presets();
		$palettes['custom'] = $value['colors'];
		$captions           = array(
			'filewarden' => __( 'Default', 'filewarden' ),
			'neutral'    => __( 'Simple and light', 'filewarden' ),
			'nature'     => __( 'Soft green', 'filewarden' ),
			'dark'       => __( 'Deep navy and turquoise', 'filewarden' ),
			'terracotta' => __( 'Warm earth and cream', 'filewarden' ),
			'custom'     => __( 'Your colors', 'filewarden' ),
		);
		foreach ( $palettes as $key => $colors ) {
			$swatch  = 'dark' === $key ? array( $colors['background'], $colors['surface'], $colors['accent'] ) : array( $colors['button'], $colors['accent'], $colors['background'] );
			$palette = '--fw-palette-button:' . $swatch[0] . ';--fw-palette-accent:' . $swatch[1] . ';--fw-palette-background:' . $swatch[2];
			echo '<button type="button" class="fw-appearance-preset" data-preset="' . esc_attr( $key ) . '" aria-pressed="' . ( $value['preset'] === $key ? 'true' : 'false' ) . '"><span class="fw-palette" aria-hidden="true"><span class="fw-palette-' . esc_attr( $key ) . '" style="' . esc_attr( $palette ) . '"></span></span><strong>' . esc_html( $labels[ $key ] ) . '</strong><small>' . esc_html( $captions[ $key ] ) . '</small></button>';
		}
		echo '</div><input type="hidden" name="appearance[preset]" value="' . esc_attr( $value['preset'] ) . '"><p class="description">' . esc_html__( 'Choose a preset palette or customize your colors while keeping your custom name and logo.', 'filewarden' ) . '</p></section><details class="fw-appearance-section" open><summary><span class="fw-action-icon fw-icon-palette" aria-hidden="true"></span>' . esc_html__( 'Interface colors', 'filewarden' ) . '</summary>';
		$color_labels = array(
			'background'  => __( 'Interface background', 'filewarden' ),
			'surface'     => __( 'Cards and fields', 'filewarden' ),
			'text'        => __( 'Main text', 'filewarden' ),
			'button'      => __( 'Button background', 'filewarden' ),
			'button_text' => __( 'Button text', 'filewarden' ),
			'link'        => __( 'Links and action icons', 'filewarden' ),
			'hover'       => __( 'Button hover', 'filewarden' ),
			'muted'       => __( 'Secondary text', 'filewarden' ),
			'border'      => __( 'Borders', 'filewarden' ),
			'accent'      => __( 'Decorative accent', 'filewarden' ),
		);
		foreach ( $color_labels as $key => $label ) {
			if ( 'hover' === $key ) {
				echo '<details class="fw-appearance-advanced"><summary>' . esc_html__( 'Additional colors', 'filewarden' ) . '</summary>';
			}
			echo '<div class="fw-appearance-color"><label for="fw-appearance-' . esc_attr( $key ) . '">' . esc_html( $label ) . '</label><input type="color" id="fw-appearance-' . esc_attr( $key ) . '" data-color-picker="' . esc_attr( $key ) . '" value="' . esc_attr( $value['colors'][ $key ] ) . '" aria-label="' . esc_attr( $label ) . '"><input name="appearance[colors][' . esc_attr( $key ) . ']" data-color="' . esc_attr( $key ) . '" value="' . esc_attr( $value['colors'][ $key ] ) . '" pattern="#[a-fA-F0-9]{6}" maxlength="7" required aria-label="' . esc_attr( $label ) . '"></div>';
		}
		echo '</details><p class="fw-appearance-contrast" role="status"></p>';
		ContrastConsole::render( $value['colors'] );
		echo '</details><details class="fw-appearance-section"><summary>' . esc_html__( 'Public name and logo', 'filewarden' ) . '</summary><p><label for="fw-public-name">' . esc_html__( 'Name displayed to visitors', 'filewarden' ) . '</label><input id="fw-public-name" name="appearance[name]" value="' . esc_attr( $value['name'] ) . '" maxlength="64"></p>';
		try {
			$logo = FrontendAppearance::logo_url( $value['logo_id'] );
		} catch ( \Throwable $error ) {
			$logo = FrontendAppearance::logo_url();
		}
		echo '<div class="fw-appearance-logo"><img src="' . esc_url( $logo ) . '" alt="' . esc_attr__( 'Selected public logo', 'filewarden' ) . '" width="44" height="44"><span class="fw-logo-name">' . esc_html( $value['logo_id'] ? get_the_title( $value['logo_id'] ) : __( 'Official FileWarden logo', 'filewarden' ) ) . '</span></div><input type="hidden" name="appearance[logo_id]" value="' . esc_attr( $value['logo_id'] ) . '">';
		if ( current_user_can( 'upload_files' ) ) {
			echo '<button type="button" class="button fw-button-accent" data-appearance-logo><span class="fw-action-icon fw-icon-upload" aria-hidden="true"></span>' . esc_html__( 'Import a logo', 'filewarden' ) . '</button>';
		}
		echo '<button type="button" class="button-link" data-appearance-logo-reset>' . esc_html__( 'FileWarden logo', 'filewarden' ) . '</button><p class="description">' . esc_html__( 'Public PNG or JPEG image, up to 2 MB and 6000 pixels per side. File-type restrictions for logos always apply.', 'filewarden' ) . '</p>';
		echo '<div class="fw-appearance-checks">';
		foreach ( array(
			'show_logo' => __( 'Show the logo', 'filewarden' ),
			'show_name' => __( 'Show the name', 'filewarden' ),
		) as $key => $label ) {
			echo '<input type="hidden" name="appearance[' . esc_attr( $key ) . ']" value="0"><label class="fw-appearance-check"><input type="checkbox" name="appearance[' . esc_attr( $key ) . ']" value="1"' . checked( $value[ $key ], true, false ) . '> ' . esc_html( $label ) . '</label>';
		}
		echo '</div></details><p class="fw-appearance-dirty" role="status"></p><p class="submit"><button class="button button-primary" type="submit">' . esc_html__( 'Save appearance', 'filewarden' ) . '</button></p><button type="button" class="button-link" data-appearance-reset>' . esc_html__( 'Restore FileWarden appearance', 'filewarden' ) . '</button><p class="description">' . esc_html__( 'Restoring changes the preview first. Save to apply it to the site.', 'filewarden' ) . '</p></form></section>';
		// phpcs:disable WordPress.Security.NonceVerification.Recommended -- Feedback only; a signed receipt is required for success.
		if ( isset( $_GET['fw_operation'] ) && 'appearance' === $_GET['fw_operation'] && isset( $_GET['result'] ) ) {
			$success = 'success' === $_GET['result'];
			if ( ! $success || Notice::verified( 'success' ) ) {
				Notice::show( $success ? __( 'The public appearance was saved for the whole site.', 'filewarden' ) : __( 'Appearance could not be saved. Check the color contrast, public name and logo permissions or format.', 'filewarden' ), $success ? 'success' : 'error' );
			}
		}
		// phpcs:enable WordPress.Security.NonceVerification.Recommended
	}

	/** Provide translated interface messages and safe palette data to the editor.
	 *
	 * @return array Localized editor configuration.
	 */
	public static function configuration() {
		return array(
			'current'       => FrontendAppearance::current(),
			'defaults'      => FrontendAppearance::defaults(),
			'presets'       => FrontendAppearance::presets(),
			'contrast'      => ContrastConsole::configuration(),
			'officialLogo'  => FrontendAppearance::logo_url(),
			'contrastOK'    => __( 'Text contrast checked: at least 4.5:1.', 'filewarden' ),
			'contrastError' => __( 'Improve text contrast or enter valid six-digit color codes before saving.', 'filewarden' ),
			'dirty'         => __( 'Unsaved appearance changes', 'filewarden' ),
			'clean'         => __( 'No appearance changes', 'filewarden' ),
			'resetConfirm'  => __( 'Restore FileWarden colors, name and logo? Save afterwards to apply the change.', 'filewarden' ),
			'leave'         => __( 'Leave this form without saving your changes?', 'filewarden' ),
			'logoTitle'     => __( 'Choose a public PNG or JPEG logo', 'filewarden' ),
			'logoChoose'    => __( 'Use this logo', 'filewarden' ),
			'logoError'     => __( 'Choose a local PNG or JPEG image. The server will also verify its type and dimensions before saving.', 'filewarden' ),
			'officialLabel' => __( 'Official FileWarden logo', 'filewarden' ),
			'desktop'       => __( 'Desktop preview', 'filewarden' ),
			'mobile'        => __( 'Mobile preview', 'filewarden' ),
		);
	}
}
