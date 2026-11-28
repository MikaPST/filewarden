<?php
/**
 * Private local storage. Never falls back to uploads or a public directory.
 *
 * @package FileWarden
 */

namespace FileWarden;

/**
 * StorageService implementation.
 */
final class StorageService {
	/**
	 * Service state.
	 *
	 * @var string
	 */
	private $configured_path;
	/**
	 * Service state.
	 *
	 * @var array
	 */
	private $public_roots;
	/**
	 * Service state.
	 *
	 * @var bool
	 */
	private $verified;

	/**
	 * Managed encrypted backend when enabled.
	 *
	 * @var ManagedStorage|null
	 */
	private $managed;

	/**
	 * Initialize service dependencies.
	 *
	 * @param mixed               $path path value.
	 * @param array               $public_roots public roots value.
	 * @param mixed               $verified verified value.
	 * @param ManagedStorage|null $managed Managed encrypted backend.
	 * @return mixed Operation result.
	 */
	public function __construct( $path, array $public_roots, $verified = false, ?ManagedStorage $managed = null ) {
		$this->configured_path = $path;
		$this->public_roots    = $public_roots;
		$this->verified        = true === $verified;
		$this->managed         = $managed;
	}

	/**
	 * Check a canonical path against a directory boundary.
	 *
	 * @param mixed $path path value.
	 * @param mixed $root root value.
	 * @return mixed Operation result.
	 */
	public static function within( $path, $root ) {
		$path = rtrim( str_replace( '\\', '/', $path ), '/' );
		$root = rtrim( str_replace( '\\', '/', $root ), '/' );
		if ( '\\' === DIRECTORY_SEPARATOR ) {
			$path = strtolower( $path );
			$root = strtolower( $root );
		}
		return $path === $root || str_starts_with( $path . '/', $root . '/' );
	}

	/**
	 * Verify private storage isolation and availability.
	 *
	 * @throws \RuntimeException When the operation cannot be completed safely.
	 * @return mixed Operation result.
	 */
	public function root() {
		if ( $this->managed ) {
			return $this->managed->root();
		}
		if ( ! $this->verified || ! is_string( $this->configured_path ) || '' === $this->configured_path ) {
			throw new \RuntimeException( 'Private storage must be configured and verified by the server operator.' );
		}
		$root = FileIO::attempt( fn() => realpath( $this->configured_path ) );
		// phpcs:ignore WordPress.WP.AlternativeFunctions -- Private local streams require exclusive creation and byte-exact I/O without filesystem filters.
		if ( false === $root || ! FileIO::attempt( fn() => is_dir( $root ) ) || ! FileIO::attempt( fn() => is_readable( $root ) ) || ! FileIO::attempt( fn() => is_writable( $root ) ) || FileIO::attempt( fn() => is_link( $this->configured_path ) ) ) {
			throw new \RuntimeException( 'Private storage is unavailable.' );
		}
		foreach ( $this->public_roots as $public_root ) {
			if ( ! is_string( $public_root ) || '' === $public_root ) {
				throw new \RuntimeException( 'Public roots must be known.' );
			}
			$public = FileIO::attempt( fn() => realpath( $public_root ) );
			if ( false === $public || self::within( $root, $public ) || self::within( $public, $root ) ) {
				throw new \RuntimeException( 'Private storage overlaps a public directory or public roots cannot be verified.' );
			}
		}
		if ( ! $this->public_roots ) {
			throw new \RuntimeException( 'Public roots must be known.' );
		}
		return $root;
	}

	/**
	 * Resolve an opaque storage key without allowing traversal.
	 *
	 * @param mixed $key key value.
	 * @throws \RuntimeException When the operation cannot be completed safely.
	 * @return mixed Operation result.
	 */
	public function path( $key ) {
		if ( ! is_string( $key ) || ! preg_match( '/^[a-f0-9]{64}$/D', $key ) ) {
			throw new \RuntimeException( 'Invalid storage key.' );
		}
		$root = $this->root();
		$path = $root . DIRECTORY_SEPARATOR . $key . ( $this->managed ? '.bin' : '' );
		if ( FileIO::attempt( fn() => is_link( $path ) ) ) {
			throw new \RuntimeException( 'Invalid storage entry.' );
		}
		return $path;
	}

	/**
	 * Copy verified upload bytes into an exclusively created private file.
	 *
	 * @param mixed $temporary temporary value.
	 * @param mixed $expected_size expected size value.
	 * @throws \RuntimeException When the operation cannot be completed safely.
	 * @throws \Throwable When the operation cannot be completed safely.
	 * @return mixed Operation result.
	 */
	public function put( $temporary, $expected_size ) {
		$key      = bin2hex( random_bytes( 32 ) );
		$target   = $this->path( $key );
		$file_key = $this->managed ? $this->managed->key( $key, true ) : null;
		// phpcs:ignore WordPress.WP.AlternativeFunctions -- Private local streams require exclusive creation and byte-exact I/O without filesystem filters.
		$input = FileIO::attempt( fn() => fopen( $temporary, 'rb' ) );
		// phpcs:ignore WordPress.WP.AlternativeFunctions -- Private local streams require exclusive creation and byte-exact I/O without filesystem filters.
		$output = FileIO::attempt( fn() => fopen( $target, 'x+b' ) );
		if ( false === $input || false === $output ) {
			if ( is_resource( $input ) ) {
				// phpcs:ignore WordPress.WP.AlternativeFunctions -- Private local streams require exclusive creation and byte-exact I/O without filesystem filters.
				fclose( $input );
			}
			if ( is_resource( $output ) ) {
				// phpcs:ignore WordPress.WP.AlternativeFunctions -- Private local streams require exclusive creation and byte-exact I/O without filesystem filters.
				fclose( $output );
				// phpcs:ignore WordPress.WP.AlternativeFunctions -- Private local streams require exclusive creation and byte-exact I/O without filesystem filters.
				FileIO::attempt( fn() => unlink( $target ) );
			}
			throw new \RuntimeException( 'Storage write failed.' );
		}
		try {
			// phpcs:ignore WordPress.WP.AlternativeFunctions -- Private local streams require exclusive creation and byte-exact I/O without filesystem filters.
			if ( ! FileIO::attempt( fn() => chmod( $target, 0600 ) ) ) {
				throw new \RuntimeException( 'Storage write failed.' );
			}
			if ( $this->managed ) {
				EncryptedStream::transfer( $input, $output, $expected_size, $file_key, true );
			} elseif ( FileIO::attempt( fn() => stream_copy_to_stream( $input, $output, $expected_size + 1 ) ) !== $expected_size ) {
				throw new \RuntimeException( 'Storage write failed.' );
			}
			if ( ! FileIO::attempt( fn() => fflush( $output ) ) ) {
				throw new \RuntimeException( 'Storage write failed.' );
			}
		} catch ( \Throwable $error ) {
			// phpcs:ignore WordPress.WP.AlternativeFunctions -- Private local streams require exclusive creation and byte-exact I/O without filesystem filters.
			fclose( $input );
			// phpcs:ignore WordPress.WP.AlternativeFunctions -- Private local streams require exclusive creation and byte-exact I/O without filesystem filters.
			fclose( $output );
			// phpcs:ignore WordPress.WP.AlternativeFunctions -- Private local streams require exclusive creation and byte-exact I/O without filesystem filters.
			FileIO::attempt( fn() => unlink( $target ) );
			throw $error;
		}
		// phpcs:ignore WordPress.WP.AlternativeFunctions -- Private local streams require exclusive creation and byte-exact I/O without filesystem filters.
		fclose( $input );
		// phpcs:ignore WordPress.WP.AlternativeFunctions -- Private local streams require exclusive creation and byte-exact I/O without filesystem filters.
		fclose( $output );
		return $key;
	}

	/**
	 * Remove an unreferenced private file idempotently.
	 *
	 * @param mixed $key key value.
	 * @throws \RuntimeException When the operation cannot be completed safely.
	 * @return mixed Operation result.
	 */
	public function remove( $key ) {
		$path = $this->path( $key );
		// phpcs:ignore WordPress.WP.AlternativeFunctions -- Private local streams require exclusive creation and byte-exact I/O without filesystem filters.
		if ( FileIO::attempt( fn() => is_file( $path ) ) && ! FileIO::attempt( fn() => unlink( $path ) ) ) {
			throw new \RuntimeException( 'Storage cleanup failed.' );
		}
	}

	/**
	 * Open an authorized file and verify its stored size.
	 *
	 * @param mixed $key key value.
	 * @param mixed $expected_size expected size value.
	 * @throws \RuntimeException When the operation cannot be completed safely.
	 * @return mixed Operation result.
	 */
	public function open( $key, $expected_size ) {
		$path = $this->path( $key );
		if ( ! FileIO::attempt( fn() => is_file( $path ) ) || ! FileIO::attempt( fn() => is_readable( $path ) ) ) {
			throw new \RuntimeException( 'File unavailable.' );
		}
		// phpcs:ignore WordPress.WP.AlternativeFunctions -- Private local streams require exclusive creation and byte-exact I/O without filesystem filters.
		$handle = FileIO::attempt( fn() => fopen( $path, 'rb' ) );
		if ( false === $handle ) {
			throw new \RuntimeException( 'File unavailable.' );
		}
		if ( $this->managed ) {
			return $this->decrypt( $handle, $key, (int) $expected_size );
		}
		$stat = FileIO::attempt( fn() => fstat( $handle ) );
		if ( ! $stat || (int) $stat['size'] !== (int) $expected_size ) {
			// phpcs:ignore WordPress.WP.AlternativeFunctions -- Private local streams require exclusive creation and byte-exact I/O without filesystem filters.
			fclose( $handle );
			throw new \RuntimeException( 'File integrity check failed.' );
		}
		return $handle;
	}

	/** Visit authenticated file chunks directly without plaintext temporary storage.
	 *
	 * @param string   $id Storage key.
	 * @param int      $size Expected plaintext size.
	 * @param callable $consume Bounded plaintext consumer.
	 * @throws \RuntimeException On unavailable, truncated or altered bytes.
	 */
	public function visit_chunks( $id, $size, callable $consume ) {
		$input = FileIO::attempt( fn() => fopen( $this->path( $id ), 'rb' ) ); // phpcs:ignore WordPress.WP.AlternativeFunctions
		if ( ! $input ) {
			throw new \RuntimeException( 'File unavailable.' );
		}
		try {
			if ( $this->managed ) {
				$key   = $this->managed->key( $id );
				$state = sodium_crypto_secretstream_xchacha20poly1305_init_pull( BackupArchive::read( $input, 24 ), $key );
				sodium_memzero( $key );
			}
			$remaining = $size;
			while ( $remaining > 0 ) {
				$length = min( 65536, $remaining );
				$bytes  = BackupArchive::read( $input, $length + ( $this->managed ? 17 : 0 ) );
				if ( $this->managed ) {
					$plain = sodium_crypto_secretstream_xchacha20poly1305_pull( $state, $bytes );
					$tag   = $remaining === $length ? SODIUM_CRYPTO_SECRETSTREAM_XCHACHA20POLY1305_TAG_FINAL : SODIUM_CRYPTO_SECRETSTREAM_XCHACHA20POLY1305_TAG_MESSAGE;
					if ( false === $plain || $plain[1] !== $tag ) {
						throw new \RuntimeException( 'File integrity check failed.' );
					}
					$bytes = $plain[0];
				}
				$consume( $bytes );
				$remaining -= $length;
			}
			if ( '' !== fread( $input, 1 ) || ! feof( $input ) ) { // phpcs:ignore WordPress.WP.AlternativeFunctions
				throw new \RuntimeException( 'File integrity check failed.' );
			}
		} finally {
			fclose( $input ); // phpcs:ignore WordPress.WP.AlternativeFunctions
		}
	}

	/** Write a new private object from bounded chunks, never a plaintext temporary file.
	 *
	 * @param string   $id Server-generated storage key recorded in a recovery checkpoint.
	 * @param int      $size Expected size.
	 * @param callable $produce Consumer-driven chunk producer.
	 * @throws \RuntimeException If the operation cannot complete safely.
	 * @throws \Throwable If the operation cannot complete safely.
	 */
	public function receive_chunks( $id, $size, callable $produce ) {
		$state  = null;
		$key    = $this->managed ? $this->managed->key( $id, true ) : null;
		$output = FileIO::attempt( fn() => fopen( $this->path( $id ), 'x+b' ) ); // phpcs:ignore WordPress.WP.AlternativeFunctions
		if ( ! $output ) {
			throw new \RuntimeException( 'Storage write failed.' );
		}
		try {
			if ( ! chmod( $this->path( $id ), 0600 ) ) { // phpcs:ignore WordPress.WP.AlternativeFunctions
				throw new \RuntimeException( 'Storage write failed.' );
			}
			if ( $this->managed ) {
				list( $state, $header ) = sodium_crypto_secretstream_xchacha20poly1305_init_push( $key );
				BackupArchive::write( $output, $header );
				sodium_memzero( $key );
			}
			$remaining = $size;
			$produce(
				static function ( $bytes ) use ( $output, &$state, &$remaining ) {
					if ( ! is_string( $bytes ) || strlen( $bytes ) !== min( 65536, $remaining ) || $remaining < 1 ) {
						throw new \RuntimeException( 'Invalid storage chunk.' );
					}
					$remaining -= strlen( $bytes );
					if ( null !== $state ) {
						$bytes = sodium_crypto_secretstream_xchacha20poly1305_push( $state, $bytes, '', $remaining ? SODIUM_CRYPTO_SECRETSTREAM_XCHACHA20POLY1305_TAG_MESSAGE : SODIUM_CRYPTO_SECRETSTREAM_XCHACHA20POLY1305_TAG_FINAL );
					}
					BackupArchive::write( $output, $bytes );
				}
			);
			if ( $remaining || ! fflush( $output ) ) { // phpcs:ignore WordPress.WP.AlternativeFunctions
				throw new \RuntimeException( 'Storage write failed.' );
			}
		} catch ( \Throwable $error ) {
			fclose( $output ); // phpcs:ignore WordPress.WP.AlternativeFunctions
			$this->remove( $id );
			throw $error;
		}
		fclose( $output ); // phpcs:ignore WordPress.WP.AlternativeFunctions
	}

	/** Authenticate a private stored file into a seekable temporary stream.
	 *
	 * @param resource $input Encrypted stream.
	 * @param string   $id File identifier.
	 * @param int      $size Expected plaintext size.
	 * @return resource Seekable plaintext.
	 * @throws \RuntimeException If private temporary storage is unavailable.
	 * @throws \Throwable On authentication or I/O failure.
	 */
	private function decrypt( $input, $id, $size ) {
		$output = null;
		try {
			$file_key = $this->managed->key( $id );
			$output   = FileIO::attempt( fn() => tmpfile() );
			$path     = $output ? FileIO::attempt( fn() => realpath( stream_get_meta_data( $output )['uri'] ) ) : false;
			if ( ! $path || ! $this->public_roots ) {
				throw new \RuntimeException( 'Private temporary storage is unavailable.' );
			}
			foreach ( $this->public_roots as $root ) {
				$public = $root ? FileIO::attempt( fn() => realpath( $root ) ) : false;
				if ( ! $public || self::within( $path, $public ) ) {
					throw new \RuntimeException( 'Private temporary storage cannot be verified.' );
				}
			}
			EncryptedStream::transfer( $input, $output, $size, $file_key, false );
			if ( ! rewind( $output ) ) {
				throw new \RuntimeException( 'Temporary stream could not be rewound.' );
			}
			return $output;
		} catch ( \Throwable $error ) {
			if ( is_resource( $output ) ) {
				// phpcs:ignore WordPress.WP.AlternativeFunctions -- Closing removes the private temporary file.
				fclose( $output );
			}
			throw $error;
		} finally {
			// phpcs:ignore WordPress.WP.AlternativeFunctions -- Close the encrypted input stream.
			fclose( $input );
		}
	}
}
