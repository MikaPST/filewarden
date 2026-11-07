<?php
/**
 * Cookie-authenticated browser route with authorization on every GET/HEAD.
 *
 * @package FileWarden
 */

namespace FileWarden;

/**
 * ContentController implementation.
 */
final class ContentController {
	/**
	 * Service state.
	 *
	 * @var DocumentService
	 */
	private $service;

	/**
	 * Initialize service dependencies.
	 *
	 * @param DocumentService $service service value.
	 * @return mixed Operation result.
	 */
	public function __construct( DocumentService $service ) {
		$this->service = $service;
	}

	/**
	 * Serve a protected detail page or binary response.
	 *
	 * @throws \DomainException When the operation cannot be completed safely.
	 * @throws \RuntimeException When the operation cannot be completed safely.
	 * @return mixed Operation result.
	 */
	public function serve() {
		nocache_headers();
		header( 'Cache-Control: private, no-store, max-age=0' );
		header( 'Vary: Cookie, Authorization' );
		header( 'X-Content-Type-Options: nosniff' );
		header( 'Referrer-Policy: no-referrer' );
		$method = sanitize_text_field( wp_unslash( $_SERVER['REQUEST_METHOD'] ?? '' ) );
		if ( ! in_array( $method, array( 'GET', 'HEAD' ), true ) ) {
			header( 'Allow: GET, HEAD' );
			status_header( 405 );
			exit;
		}
		try {
			// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only navigation; authorization is independently enforced.
			$uuid = isset( $_GET['document'] ) && is_string( $_GET['document'] ) ? sanitize_text_field( wp_unslash( $_GET['document'] ) ) : '';
			if ( ! wp_is_uuid( $uuid ) ) {
				throw new \DomainException( 'Unavailable.' );
			}
			$document = $this->service->get( $uuid );
			// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only navigation; authorization is independently enforced.
			$download = isset( $_GET['download'] ) && '1' === $_GET['download'];
			if ( ! $download ) {
				header( "Content-Security-Policy: default-src 'none'; frame-ancestors 'none'; base-uri 'none'" );
				header( 'Content-Type: text/html; charset=UTF-8' );
				if ( 'HEAD' === $method ) {
					exit;
				}
				$url = add_query_arg( 'download', '1', $this->service->metadata( $document )['detail_url'] );
				echo '<!doctype html><html lang="' . esc_attr( get_bloginfo( 'language' ) ) . '"><meta charset="utf-8"><meta name="viewport" content="width=device-width"><title>' . esc_html( $document['name'] ) . '</title><main><h1>' . esc_html( $document['name'] ) . '</h1><p>' . esc_html( size_format( $document['size'] ) ) . '</p><a href="' . esc_url( $url ) . '">' . esc_html__( 'Download', 'filewarden' ) . '</a></main></html>';
				exit;
			}
			$handle = $this->service->storage->open( $document['storage_key'], $document['size'] );
			$size   = (int) $document['size'];
			$start  = 0;
			$end    = $size - 1;
			$range  = 'GET' === $method && 'application/pdf' === $document['mime'] && isset( $_SERVER['HTTP_RANGE'] ) && ! isset( $_SERVER['HTTP_IF_RANGE'] );
			if ( $range ) {
				try {
					// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- ByteRange validates the exact header syntax and bounds.
					list( $start, $end ) = ByteRange::parse( wp_unslash( $_SERVER['HTTP_RANGE'] ), $size );
				} catch ( \InvalidArgumentException $error ) {
					// phpcs:ignore WordPress.WP.AlternativeFunctions -- Private local streams require exclusive creation and byte-exact I/O without filesystem filters.
					fclose( $handle );
					header( 'Content-Range: bytes */' . $size );
					status_header( 416 );
					exit;
				}
			}
			$this->service->audit->record( 'HEAD' === $method ? 'download_head' : 'download_started', (int) $document['id'] );
			if ( 0 !== fseek( $handle, $start ) ) {
				throw new \RuntimeException( 'Read failed.' );
			}
		} catch ( \Throwable $error ) {
			if ( isset( $handle ) && is_resource( $handle ) ) {
				// phpcs:ignore WordPress.WP.AlternativeFunctions -- Private local streams require exclusive creation and byte-exact I/O without filesystem filters.
				fclose( $handle );
			}
			status_header( $error instanceof \DomainException ? 404 : 503 );
			exit;
		}
		while ( ob_get_level() > 0 ) {
			if ( ! ob_end_clean() ) {
				break;
			}
		}
		status_header( $range ? 206 : 200 );
		header( 'Content-Type: ' . $document['mime'] );
		header( "Content-Security-Policy: sandbox; default-src 'none'" );
		header( 'Content-Disposition: attachment; filename="document.' . pathinfo( $document['name'], PATHINFO_EXTENSION ) . '"; filename*=UTF-8\'\'' . rawurlencode( $document['name'] ) );
		header( 'Content-Length: ' . ( $end - $start + 1 ) );
		header( 'Accept-Ranges: ' . ( 'application/pdf' === $document['mime'] ? 'bytes' : 'none' ) );
		if ( $range ) {
			header( "Content-Range: bytes $start-$end/$size" );
		}
		if ( 'HEAD' !== $method ) {
			$remaining = $end - $start + 1;
			while ( $remaining > 0 && ! feof( $handle ) && ! connection_aborted() ) {
				// phpcs:ignore WordPress.WP.AlternativeFunctions -- Private local streams require exclusive creation and byte-exact I/O without filesystem filters.
				$chunk = fread( $handle, min( 65536, $remaining ) );
				if ( false === $chunk || '' === $chunk ) {
					break;
				}
				echo $chunk; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Authorized binary bytes.
				$remaining -= strlen( $chunk );
			}
		}
		// phpcs:ignore WordPress.WP.AlternativeFunctions -- Private local streams require exclusive creation and byte-exact I/O without filesystem filters.
		fclose( $handle );
		exit;
	}
}
