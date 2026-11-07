<?php
/**
 * Private document viewer. Raw PDF content is only fetched as authorized bytes.
 *
 * @package FileWarden
 */

namespace FileWarden;

/** Render an accessible standalone viewer, also usable inside the admin dialog. */
final class Viewer {
	/**
	 * Render metadata already authorized by the content controller.
	 *
	 * @param array  $document Authorized document.
	 * @param string $url Logical document URL.
	 * @param string $method HTTP method.
	 */
	public static function render( array $document, $url, $method ) {
		header( "Content-Security-Policy: default-src 'none'; script-src 'self' 'wasm-unsafe-eval'; worker-src 'self'; connect-src 'self'; img-src 'self' blob: data:; font-src 'self' blob:; style-src 'self'; frame-ancestors 'self'; base-uri 'none'; form-action 'none'" );
		header( 'X-Frame-Options: SAMEORIGIN' );
		header( 'Content-Type: text/html; charset=UTF-8' );
		if ( 'HEAD' === $method ) {
			return;
		}
		$assets = plugins_url( 'assets/', FILEWARDEN_PATH . 'filewarden.php' );
		$binary = add_query_arg( 'preview', '1', $url );
		$kind   = 'application/pdf' === $document['mime'] ? 'pdf' : ( in_array( $document['mime'], array( 'image/jpeg', 'image/png' ), true ) ? 'image' : 'other' );
		// phpcs:ignore WordPress.WP.EnqueuedResources.NonEnqueuedStylesheet -- Standalone restricted document, deliberately independent of theme/admin hooks.
		echo '<!doctype html><html lang="' . esc_attr( get_bloginfo( 'language' ) ) . '"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width"><title>' . esc_html( $document['name'] ) . '</title><link rel="stylesheet" href="' . esc_url( $assets . 'viewer.css?ver=' . FILEWARDEN_VERSION ) . '"></head><body>';
		echo '<main id="fw-viewer" data-kind="' . esc_attr( $kind ) . '" data-url="' . esc_url( $binary ) . '" data-error="' . esc_attr__( 'Preview unavailable, access expired or document protected by a password. You can try downloading the document.', 'filewarden' ) . '" data-loading="' . esc_attr__( 'Loading document…', 'filewarden' ) . '" data-page-label="' . esc_attr__( 'Page', 'filewarden' ) . '">';
		echo '<header><div class="fw-title"><h1>' . esc_html( $document['name'] ) . '</h1><span>' . esc_html( size_format( $document['size'] ) ) . '</span></div><nav aria-label="' . esc_attr__( 'Document actions', 'filewarden' ) . '"><a href="' . esc_url( $url ) . '" target="_blank" rel="noopener noreferrer">' . esc_html__( 'Open in new tab', 'filewarden' ) . '</a><a href="' . esc_url( add_query_arg( 'download', '1', $url ) ) . '">' . esc_html__( 'Download', 'filewarden' ) . '</a></nav></header>';
		echo '<div class="fw-toolbar" aria-label="' . esc_attr__( 'Viewer controls', 'filewarden' ) . '"><div id="fw-pages"' . ( 'pdf' === $kind ? '' : ' hidden' ) . '><button id="fw-previous" type="button" disabled>' . esc_html__( 'Previous page', 'filewarden' ) . '</button><label for="fw-page">' . esc_html__( 'Page', 'filewarden' ) . '</label> <input id="fw-page" type="number" min="1" value="1" disabled> / <span id="fw-total">—</span> <button id="fw-next" type="button" disabled>' . esc_html__( 'Next page', 'filewarden' ) . '</button></div><label for="fw-zoom">' . esc_html__( 'Zoom', 'filewarden' ) . '</label> <select id="fw-zoom" disabled><option value="fit">' . esc_html__( 'Fit width', 'filewarden' ) . '</option><option value="0.5">50%</option><option value="0.75">75%</option><option value="1">100%</option><option value="1.25">125%</option><option value="1.5">150%</option><option value="2">200%</option></select></div>';
		// phpcs:ignore WordPress.WP.EnqueuedResources.NonEnqueuedScript -- Standalone module with strict CSP; no theme or unrelated plugin scripts are loaded.
		echo '<p id="fw-status" role="status" aria-live="polite">' . esc_html__( 'Loading document…', 'filewarden' ) . '</p><div id="fw-stage"><canvas id="fw-canvas" hidden role="img"></canvas><img id="fw-image" hidden alt="' . esc_attr( $document['name'] ) . '"></div><noscript><p>' . esc_html__( 'Enable JavaScript to use the viewer, or download the document.', 'filewarden' ) . '</p></noscript></main><script type="module" src="' . esc_url( $assets . 'viewer.mjs?ver=' . FILEWARDEN_VERSION ) . '"></script></body></html>';
	}
}
