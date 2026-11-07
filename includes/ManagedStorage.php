<?php
/**
 * Provision and verify encrypted storage on shared hosting.
 *
 * @package FileWarden
 */

namespace FileWarden;

/**
 * Managed storage and its independent database key.
 */
final class ManagedStorage {
	const DIRECTORY = 'filewarden-storage';
	const OPTION    = 'filewarden_managed_key';

	/**
	 * Whether HTTP verification succeeded in this request.
	 *
	 * @var bool
	 */
	private $verified = false;

	/**
	 * Verify direct access against a public control and a protected witness.
	 *
	 * @return string Canonical storage directory.
	 * @throws \RuntimeException If provisioning or verification fails.
	 */
	public function root() {
		$directory = WP_CONTENT_DIR . '/' . self::DIRECTORY;
		if ( ! function_exists( 'sodium_crypto_secretstream_xchacha20poly1305_init_push' ) ) {
			throw new \RuntimeException( esc_html__( 'Enable the PHP Sodium extension for this site, then reload this page.', 'filewarden' ) );
		}
		if ( is_link( $directory ) ) {
			throw new \RuntimeException( esc_html__( 'The FileWarden storage directory is a symbolic link. Use a regular directory.', 'filewarden' ) );
		}
		// phpcs:ignore WordPress.WP.AlternativeFunctions -- Check permissions for the PHP process that writes local files.
		if ( ! FileIO::attempt( fn() => wp_mkdir_p( $directory ) ) || ! FileIO::attempt( fn() => is_writable( $directory ) ) ) {
			throw new \RuntimeException( esc_html__( 'PHP cannot create or write the FileWarden storage directory. Check the owner and permissions of wp-content and filewarden-storage with your host.', 'filewarden' ) );
		}
		$root = realpath( $directory );
		if ( false === $root || dirname( $root ) !== realpath( WP_CONTENT_DIR ) ) {
			throw new \RuntimeException( 'Managed storage directory is invalid.' );
		}
		if ( ! $this->verified ) {
			$rules = $root . '/.htaccess';
			if ( ! file_exists( $rules ) ) {
				$this->write( $rules, "Require all denied\n" );
			}
			$this->probe( $root );
			$this->verified = true;
		}
		return $root;
	}

	/**
	 * Create an exclusive fixture or server rule without replacing existing files.
	 *
	 * @param string $path Destination.
	 * @param string $bytes File content.
	 * @throws \RuntimeException If creation fails.
	 */
	private function write( $path, $bytes ) {
		// phpcs:disable WordPress.WP.AlternativeFunctions -- Exclusive local file creation and cleanup.
		$handle = FileIO::attempt( fn() => fopen( $path, 'xb' ) );
		if ( false === $handle ) {
			throw new \RuntimeException( 'Managed storage cannot create a verification file or protection rule.' );
		}
		$written = FileIO::attempt( fn() => fwrite( $handle, $bytes ) );
		fclose( $handle );
		if ( strlen( $bytes ) !== $written ) {
			FileIO::attempt( fn() => unlink( $path ) );
			throw new \RuntimeException( 'Managed storage could not write its verification file.' );
		}
		// phpcs:enable WordPress.WP.AlternativeFunctions
	}

	/**
	 * Check anonymous HTTPS responses without following redirects.
	 *
	 * @param string $root Verified directory path.
	 * @throws \RuntimeException If either result is inconclusive.
	 */
	private function probe( $root ) {
		$name      = bin2hex( random_bytes( 32 ) ) . '.bin';
		$token     = bin2hex( random_bytes( 32 ) );
		$control   = WP_CONTENT_DIR . '/' . $name;
		$protected = $root . '/' . $name;
		$base      = content_url( '/', 'https' );
		if ( wp_parse_url( $base, PHP_URL_HOST ) !== wp_parse_url( home_url(), PHP_URL_HOST ) ) {
			throw new \RuntimeException( 'Managed storage requires a same-host HTTPS content URL.' );
		}
		$this->write( $control, $token );
		try {
			$this->write( $protected, $token );
			try {
				$args    = array(
					'redirection'         => 0,
					'timeout'             => 4,
					'limit_response_size' => 65536,
					'headers'             => array( 'Cache-Control' => 'no-cache' ),
				);
				$public  = wp_safe_remote_get( $base . $name, $args );
				$private = wp_safe_remote_get( $base . self::DIRECTORY . '/' . $name, $args );
				if ( 200 !== wp_remote_retrieve_response_code( $public ) || wp_remote_retrieve_body( $public ) !== $token
					|| ! in_array( wp_remote_retrieve_response_code( $private ), array( 403, 404 ), true )
					|| str_contains( wp_remote_retrieve_body( $private ), $token ) ) {
					throw new \RuntimeException( 'HTTP verification failed. The public witness must be readable and the protected witness denied.' );
				}
			} finally {
				// phpcs:ignore WordPress.WP.AlternativeFunctions -- Remove this request's exclusive witness.
				FileIO::attempt( fn() => unlink( $protected ) );
			}
		} finally {
			// phpcs:ignore WordPress.WP.AlternativeFunctions -- Remove this request's exclusive witness.
			FileIO::attempt( fn() => unlink( $control ) );
		}
	}

	/**
	 * Derive a per-file key from the site's independent random master key.
	 *
	 * @param string $id Opaque file identifier.
	 * @param bool   $create Whether to initialize an unused store.
	 * @return string File key.
	 * @throws \RuntimeException If the master key is unavailable.
	 */
	public function key( $id, $create = false ) {
		$value = get_option( self::OPTION, false );
		if ( false === $value && $create ) {
			$files = glob( $this->root() . '/*.bin' );
			if ( array() === $files ) {
				add_option( self::OPTION, bin2hex( sodium_crypto_secretstream_xchacha20poly1305_keygen() ), '', false );
				$value = get_option( self::OPTION, false );
			}
		}
		if ( ! is_string( $value ) || ! preg_match( '/^[a-f0-9]{64}$/D', $value ) ) {
			throw new \RuntimeException( 'Managed storage key is missing or invalid.' );
		}
		return sodium_crypto_generichash( 'FileWarden:v1:' . $id, hex2bin( $value ), 32 );
	}
}
