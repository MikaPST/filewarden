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
		$attributes = shortcode_atts(
			array(
				'folder' => '0',
				'view'   => 'list',
			),
			$attributes,
			'filewarden'
		);
		try {
			$folder = RestController::integer( $attributes['folder'] );
		} catch ( \InvalidArgumentException $error ) {
			return '';
		}
		$url = add_query_arg(
			array(
				'action'    => 'filewarden_folder',
				'folder_id' => $folder,
				'view'      => 'grid' === $attributes['view'] ? 'grid' : 'list',
			),
			admin_url( 'admin-post.php' )
		);
		wp_enqueue_script( 'filewarden-publication-frame', plugins_url( 'assets/publication-frame.js', FILEWARDEN_PATH . 'filewarden.php' ), array(), FILEWARDEN_VERSION, true );
		return '<iframe class="filewarden-publication-frame" title="' . esc_attr__( 'FileWarden documents', 'filewarden' ) . '" src="' . esc_url( $url ) . '" width="100%" height="760" loading="lazy"></iframe>';
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
		header( "Content-Security-Policy: default-src 'none'; style-src 'self'; img-src 'self'; form-action 'self'; frame-ancestors 'self'; base-uri 'none'" );
		header( 'Content-Type: text/html; charset=UTF-8' );
		try {
			// phpcs:ignore WordPress.Security.NonceVerification.Recommended,WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Read-only request; integer() validates input and service enforces ACL.
			$folder = RestController::integer( wp_unslash( $_GET['folder_id'] ?? null ) );
			// phpcs:ignore WordPress.Security.NonceVerification.Recommended,WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Read-only pagination with strict integer validation.
			$offset = RestController::integer( wp_unslash( $_GET['offset'] ?? null ) );
			$service->require_folder( $folder );
			// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only filter, independently authorized.
			$search = isset( $_GET['search'] ) && is_string( $_GET['search'] ) ? substr( sanitize_text_field( wp_unslash( $_GET['search'] ) ), 0, 200 ) : '';
			// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Strict presentation allowlist.
			$view  = isset( $_GET['view'] ) && 'grid' === $_GET['view'] ? 'grid' : 'list';
			$rows  = self::page( $service, $folder, $search, $offset );
			$title = $folder ? ( $service->repository->folder( $folder )['name'] ?? __( 'Documents', 'filewarden' ) ) : __( 'Documents at the root', 'filewarden' );
		} catch ( \Throwable $error ) {
			status_header( 404 );
			echo esc_html__( 'Folder unavailable.', 'filewarden' );
			exit;
		}
		$assets = plugins_url( 'assets/', FILEWARDEN_PATH . 'filewarden.php' );
		// phpcs:ignore WordPress.WP.EnqueuedResources.NonEnqueuedStylesheet -- Standalone private frame independent of theme hooks.
		echo '<!doctype html><html lang="' . esc_attr( get_bloginfo( 'language' ) ) . '"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width"><title>' . esc_html( $title ) . '</title><link rel="stylesheet" href="' . esc_url( $assets . 'publication.css?ver=' . FILEWARDEN_VERSION ) . '"></head><body><main><header><span class="eyebrow">FILEWARDEN</span><h1>' . esc_html( $title ) . '</h1></header>';
		echo '<form method="get" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '"><input type="hidden" name="action" value="filewarden_folder"><input type="hidden" name="folder_id" value="' . esc_attr( $folder ) . '"><label for="fw-search">' . esc_html__( 'Search this folder', 'filewarden' ) . '</label><div class="toolbar"><input id="fw-search" type="search" name="search" maxlength="200" value="' . esc_attr( $search ) . '" placeholder="' . esc_attr__( 'Search documents…', 'filewarden' ) . '"><button name="view" value="' . esc_attr( $view ) . '">' . esc_html__( 'Search', 'filewarden' ) . '</button><div class="views">';
		foreach ( array(
			'list' => __( 'Detailed list', 'filewarden' ),
			'grid' => __( 'Grid', 'filewarden' ),
		) as $layout => $label ) {
			echo '<button name="view" value="' . esc_attr( $layout ) . '" aria-pressed="' . ( $view === $layout ? 'true' : 'false' ) . '">' . esc_html( $label ) . '</button>';
		}
		echo '</div></div></form><section class="documents ' . esc_attr( $view ) . '">';
		$visible = false;
		foreach ( array_slice( $rows, 0, 12 ) as $row ) {
			if ( ! $service->permissions->can_read( $row, wp_get_current_user() ) ) {
				continue;
			}
			$visible = true;
			$url     = $service->metadata( $row )['detail_url'];
			echo '<article class="document"><img class="file-icon" src="' . esc_url( $assets . 'icons/' . self::icon( $row['name'] ) . '.svg' ) . '" alt="" width="64" height="72"><div class="details"><h2><a target="_blank" rel="noopener noreferrer" href="' . esc_url( $url ) . '">' . esc_html( $row['name'] ) . '</a></h2><p class="metadata"><span>' . esc_html( strtoupper( pathinfo( $row['name'], PATHINFO_EXTENSION ) ) ) . '</span><span>' . esc_html( size_format( $row['size'], 1 ) ) . '</span><time>' . esc_html( get_date_from_gmt( $row['created_at'], get_option( 'date_format' ) ) ) . '</time></p></div><div class="actions"><a class="preview" target="_blank" rel="noopener noreferrer" href="' . esc_url( $url ) . '">' . esc_html__( 'Preview', 'filewarden' ) . '<span class="sr-only"> — ' . esc_html( $row['name'] ) . '</span></a><a class="download" href="' . esc_url( add_query_arg( 'download', '1', $url ) ) . '">' . esc_html__( 'Download', 'filewarden' ) . '<span class="sr-only"> — ' . esc_html( $row['name'] ) . '</span></a></div></article>';
		}
		if ( ! $visible ) {
			echo '<p>' . esc_html__( 'No documents are visible in this folder.', 'filewarden' ) . '</p>';
		}
		echo '</section><nav class="pagination">';
		if ( count( $rows ) > 12 || $offset > 0 ) {
			$links = array();
			if ( $offset > 0 ) {
				$links[ max( 0, $offset - 12 ) ] = __( 'Previous', 'filewarden' );
			}
			if ( count( $rows ) > 12 ) {
				$links[ $offset + 12 ] = __( 'Next', 'filewarden' );
			}
			foreach ( $links as $position => $label ) {
				$url = add_query_arg(
					array(
						'action'    => 'filewarden_folder',
						'folder_id' => $folder,
						'offset'    => $position,
						'search'    => $search,
						'view'      => $view,
					),
					admin_url( 'admin-post.php' )
				);
				echo '<p><a href="' . esc_url( $url ) . '">' . esc_html( $label ) . '</a></p>';
			}
		}
		echo '</nav></main></body></html>';
		exit;
	}

	/**
	 * Return a page after authorization and literal name matching.
	 *
	 * @param DocumentService $service Document service.
	 * @param int             $folder Folder ID.
	 * @param string          $search Name filter.
	 * @param int             $offset Visible offset.
	 * @return array Authorized rows, including one lookahead.
	 */
	public static function page( DocumentService $service, $folder, $search, $offset ) {
		$service->require_folder( $folder );
		$result  = array();
		$skipped = 0;
		for ( $batch = 0; ; $batch += 100 ) {
			$rows = $service->repository->documents( $folder, $batch );
			foreach ( $rows as $row ) {
				if ( ! $service->permissions->can_read( $row, wp_get_current_user() ) || ( '' !== $search && false === stripos( remove_accents( $row['name'] ), remove_accents( $search ) ) ) ) {
					continue;
				}
				if ( $skipped++ < $offset ) {
					continue;
				}
				$result[] = $row;
				if ( count( $result ) === 13 ) {
					return $result;
				}
			}
			if ( count( $rows ) < 100 ) {
				return $result;
			}
		}
	}
	/**
	 * Map file extensions to trusted local icons.
	 *
	 * @param string $name Document name.
	 * @return string Icon basename.
	 */
	public static function icon( $name ) {
		$extension = strtolower( pathinfo( $name, PATHINFO_EXTENSION ) );
		$groups    = array(
			'pdf'     => array( 'pdf' ),
			'word'    => array( 'doc', 'docx', 'odt' ),
			'excel'   => array( 'xls', 'xlsx', 'csv', 'ods' ),
			'slides'  => array( 'ppt', 'pptx', 'odp' ),
			'image'   => array( 'jpg', 'jpeg', 'png', 'gif', 'webp' ),
			'archive' => array( 'zip', 'rar', '7z' ),
			'text'    => array( 'txt', 'rtf', 'md' ),
			'audio'   => array( 'mp3', 'wav', 'ogg' ),
			'video'   => array( 'mp4', 'webm', 'mov' ),
		);
		foreach ( $groups as $kind => $extensions ) {
			if ( in_array( $extension, $extensions, true ) ) {
				return $kind;
			}
		}
		return 'file';
	}
}
