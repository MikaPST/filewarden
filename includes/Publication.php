<?php
/**
 * Publish document folders in WordPress content.
 *
 * @package FileWarden
 */

namespace FileWarden;

/**
 * Keep user-specific lists out of the surrounding page cache.
 */
final class Publication {
	/**
	 * Register shortcode and the browser endpoints.
	 *
	 * @param DocumentService $service Document service.
	 */
	public static function register( DocumentService $service ) {
		add_shortcode( 'filewarden', array( self::class, 'shortcode' ) );
		$serve = static fn() => self::serve( $service );
		add_action( 'admin_post_filewarden_folder', $serve );
		add_action( 'admin_post_nopriv_filewarden_folder', $serve );
	}

	/**
	 * Render a same-origin frame with no personalized information in the parent page.
	 *
	 * @param array|string $attributes Shortcode attributes.
	 * @return string Frame markup.
	 */
	public static function shortcode( $attributes ) {
		$attributes = shortcode_atts( array( 'folder' => '0' ), $attributes, 'filewarden' );
		try {
			$folder = RestController::integer( $attributes['folder'] );
		} catch ( \InvalidArgumentException $error ) {
			return '';
		}
		$url = add_query_arg(
			array(
				'action'    => 'filewarden_folder',
				'folder_id' => $folder,
			),
			admin_url( 'admin-post.php' )
		);
		return '<iframe title="' . esc_attr__( 'FileWarden documents', 'filewarden' ) . '" src="' . esc_url( $url ) . '" width="100%" height="440" loading="lazy"></iframe>';
	}

	/**
	 * Render an authorized document batch for the browser's current session.
	 *
	 * @param DocumentService $service Document service.
	 */
	public static function serve( DocumentService $service ) {
		nocache_headers();
		header( 'Cache-Control: private, no-store, max-age=0' );
		header( 'Vary: Cookie' );
		header( 'X-Frame-Options: SAMEORIGIN' );
		header( 'X-Content-Type-Options: nosniff' );
		header( "Content-Security-Policy: default-src 'none'; frame-ancestors 'self'; base-uri 'none'" );
		header( 'Content-Type: text/html; charset=UTF-8' );
		try {
			// phpcs:ignore WordPress.Security.NonceVerification.Recommended,WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Read-only request; integer() validates input and service enforces ACL.
			$folder = RestController::integer( wp_unslash( $_GET['folder_id'] ?? null ) );
			// phpcs:ignore WordPress.Security.NonceVerification.Recommended,WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Read-only pagination with strict integer validation.
			$offset = RestController::integer( wp_unslash( $_GET['offset'] ?? null ) );
			$service->require_folder( $folder );
			$rows  = $service->repository->documents( $folder, $offset );
			$title = $folder ? ( $service->repository->folder( $folder )['name'] ?? __( 'Documents', 'filewarden' ) ) : __( 'Documents at the root', 'filewarden' );
		} catch ( \Throwable $error ) {
			status_header( 404 );
			echo esc_html__( 'Folder unavailable.', 'filewarden' );
			exit;
		}
		echo '<!doctype html><html lang="' . esc_attr( get_bloginfo( 'language' ) ) . '"><meta charset="utf-8"><meta name="viewport" content="width=device-width"><title>' . esc_html( $title ) . '</title><main><h1>' . esc_html( $title ) . '</h1>';
		$visible = false;
		foreach ( $rows as $row ) {
			if ( ! $service->permissions->can_read( $row, wp_get_current_user() ) ) {
				continue;
			}
			$visible = true;
			$url     = $service->metadata( $row )['detail_url'];
			echo '<p><a target="_blank" rel="noopener noreferrer" href="' . esc_url( $url ) . '">' . esc_html( $row['name'] ) . '</a></p>';
		}
		if ( ! $visible ) {
			echo '<p>' . esc_html__( 'No documents are visible in this folder.', 'filewarden' ) . '</p>';
		}
		if ( count( $rows ) === 100 || $offset > 0 ) {
			$links = array();
			if ( $offset > 0 ) {
				$links[ max( 0, $offset - 100 ) ] = __( 'Previous', 'filewarden' );
			}
			if ( count( $rows ) === 100 ) {
				$links[ $offset + 100 ] = __( 'Next', 'filewarden' );
			}
			foreach ( $links as $position => $label ) {
				$url = add_query_arg(
					array(
						'action'    => 'filewarden_folder',
						'folder_id' => $folder,
						'offset'    => $position,
					),
					admin_url( 'admin-post.php' )
				);
				echo '<p><a href="' . esc_url( $url ) . '">' . esc_html( $label ) . '</a></p>';
			}
		}
		echo '</main></html>';
		exit;
	}
}
