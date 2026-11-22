<?php
/** Shared official identity for FileWarden administration screens.
 *
 * @package FileWarden
 */

namespace FileWarden;

/** Keep the official artwork separate from functional navigation icons. */
final class AdminBrand {
	/** Preserve the outline and use WordPress's native menu-state colors. */
	public static function menu_style() {
		$icon = plugins_url( 'assets/icons/folder-lock.svg', FILEWARDEN_PATH . 'filewarden.php' );
		echo '<style>#adminmenu #toplevel_page_filewarden .wp-menu-image:before{content:"";display:inline-block;width:20px;height:20px;padding:0;margin-top:7px;background:currentColor;mask:url("' . esc_url( $icon ) . '") center/contain no-repeat;-webkit-mask:url("' . esc_url( $icon ) . '") center/contain no-repeat}</style>';
	}

	/** Render the shared header.
	 *
	 * @param string $title Translated page title.
	 */
	public static function heading( $title ) {
		$logo = plugins_url( 'assets/branding/filewarden-logo-512.png', FILEWARDEN_PATH . 'filewarden.php' );
		echo '<header class="fw-heading"><div class="fw-heading-identity"><div class="fw-brand-row"><img class="fw-brand-logo" src="' . esc_url( $logo ) . '" width="56" height="56" alt=""><span class="fw-brand">FILEWARDEN</span></div><h1>' . esc_html( $title ) . '</h1></div><span class="fw-version">' . esc_html( FILEWARDEN_VERSION ) . '</span></header>';
	}
}
