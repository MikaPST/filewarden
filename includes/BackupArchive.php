<?php
/** Versioned authenticated streaming container. No SQL, extraction, or PHP serialization.
 *
 * @package FileWarden
 */

namespace FileWarden;

// phpcs:disable WordPress.PHP.DiscouragedPHPFunctions -- Base64 encodes binary Sodium states inside encrypted JSON checkpoints, never executable code.
// phpcs:disable WordPress.WP.AlternativeFunctions -- Exact private binary stream I/O; no remote URLs or user-controlled paths.
/** Bounded Argon2id parameters and authenticated frame ordering. */
final class BackupArchive {
	const MAGIC = 'FWBACK01';
	const CHUNK = 65536;
	const OPS   = 2;
	const MEM   = 67108864;

	/** Derive a portable key; the archive header cannot request more resources.
	 *
	 * @param string $password Secret phrase.
	 * @param string $salt Random salt.
	 * @return string Encryption key.
	 * @throws \InvalidArgumentException On invalid parameters.
	 * @throws \RuntimeException If password derivation is unavailable or fails.
	 */
	public static function key( $password, $salt ) {
		// WordPress can run without mbstring. PCRE validates UTF-8 and counts code points.
		if ( ! is_string( $password ) || strlen( $password ) > 1024 || 1 !== preg_match( '/\A.{16,}\z/us', $password ) || ! is_string( $salt ) || 16 !== strlen( $salt ) ) {
			throw new \InvalidArgumentException( 'Invalid backup phrase.' );
		}
		if ( ! CryptoSupport::native() || ! function_exists( 'sodium_crypto_pwhash' ) || ! ( new \ReflectionFunction( 'sodium_crypto_pwhash' ) )->isInternal() || ! defined( 'SODIUM_CRYPTO_PWHASH_ALG_ARGON2ID13' ) ) {
			throw new \RuntimeException( 'Sodium password derivation unavailable.' );
		}
		try {
			return sodium_crypto_pwhash( 32, $password, $salt, self::OPS, self::MEM, SODIUM_CRYPTO_PWHASH_ALG_ARGON2ID13 );
		} catch ( \SodiumException ) {
			throw new \RuntimeException( 'Sodium password derivation failed.' );
		}
	}

	/** Start a new output stream.
	 *
	 * @param resource $stream Output.
	 * @param string   $key Derived key.
	 * @param string   $salt Salt.
	 * @return array Persistable encrypted-checkpoint state.
	 */
	public static function create( $stream, $key, $salt ) {
		list( $state, $header ) = sodium_crypto_secretstream_xchacha20poly1305_init_push( $key );
		$prefix                 = self::MAGIC . $salt . pack( 'NN', self::OPS, self::MEM ) . $header;
		self::write( $stream, $prefix );
		return array(
			'state'  => base64_encode( $state ),
			'header' => base64_encode( $prefix ),
		);
	}

	/** Authenticate format and reject KDF abuse before deriving anything.
	 *
	 * @param resource $stream Input.
	 * @param string   $password Phrase.
	 * @return array Decoder checkpoint.
	 * @throws \RuntimeException For unsupported or altered headers.
	 */
	public static function open( $stream, $password ) {
		$prefix = self::read( $stream, 56 );
		if ( substr( $prefix, 0, 8 ) !== self::MAGIC || substr( $prefix, 24, 8 ) !== pack( 'NN', self::OPS, self::MEM ) ) {
			throw new \RuntimeException( 'Unsupported backup header.' );
		}
		$key   = self::key( $password, substr( $prefix, 8, 16 ) );
		$state = sodium_crypto_secretstream_xchacha20poly1305_init_pull( substr( $prefix, 32, 24 ), $key );
		sodium_memzero( $key );
		return array(
			'state'  => base64_encode( $state ),
			'header' => base64_encode( $prefix ),
		);
	}

	/** Append one bounded, ordered authenticated record.
	 *
	 * @param resource $stream Output.
	 * @param array    $cursor Secret checkpoint, wrapped at rest by BackupJobs.
	 * @param string   $kind Record kind.
	 * @param string   $bytes Payload.
	 * @throws \RuntimeException For invalid frames.
	 */
	public static function push( $stream, array &$cursor, $kind, $bytes = '' ) {
		if ( strlen( $kind ) !== 1 || ! str_contains( 'MNFDEZ', $kind ) || strlen( $bytes ) > self::CHUNK ) {
			throw new \RuntimeException( 'Invalid backup frame.' );
		}
		$state  = base64_decode( $cursor['state'], true );
		$cipher = sodium_crypto_secretstream_xchacha20poly1305_push( $state, $kind . $bytes, base64_decode( $cursor['header'], true ), 'Z' === $kind ? SODIUM_CRYPTO_SECRETSTREAM_XCHACHA20POLY1305_TAG_FINAL : SODIUM_CRYPTO_SECRETSTREAM_XCHACHA20POLY1305_TAG_MESSAGE );
		self::write( $stream, pack( 'N', strlen( $cipher ) ) . $cipher );
		$cursor['state'] = base64_encode( $state );
	}

	/** Read exactly one authenticated frame and require finality only at Z.
	 *
	 * @param resource $stream Input.
	 * @param array    $cursor Secret checkpoint.
	 * @return array Kind and plaintext.
	 * @throws \RuntimeException On tampering, truncation, reordering or oversized frames.
	 */
	public static function pull( $stream, array &$cursor ) {
		$length = unpack( 'Nlength', self::read( $stream, 4 ) )['length'];
		if ( $length < 18 || $length > self::CHUNK + 18 ) {
			throw new \RuntimeException( 'Invalid backup frame size.' );
		}
		$state  = base64_decode( $cursor['state'], true );
		$result = sodium_crypto_secretstream_xchacha20poly1305_pull( $state, self::read( $stream, $length ), base64_decode( $cursor['header'], true ) );
		if ( false === $result ) {
			throw new \RuntimeException( 'Backup authentication failed.' );
		}
		list( $plain, $tag ) = $result;
		$kind                = substr( $plain, 0, 1 );
		if ( ! str_contains( 'MNFDEZ', $kind ) || ( 'Z' === $kind ? SODIUM_CRYPTO_SECRETSTREAM_XCHACHA20POLY1305_TAG_FINAL : SODIUM_CRYPTO_SECRETSTREAM_XCHACHA20POLY1305_TAG_MESSAGE ) !== $tag || ( 'Z' === $kind && ( '' !== fread( $stream, 1 ) || ! feof( $stream ) ) ) ) {
			throw new \RuntimeException( 'Invalid backup termination.' );
		}
		$cursor['state'] = base64_encode( $state );
		return array( $kind, substr( $plain, 1 ) );
	}

	/** Exact read with a bounded caller-controlled size.
	 *
	 * @param resource $stream Input.
	 * @param int      $length Bytes.
	 * @return string Bytes.
	 * @throws \RuntimeException On short reads.
	 */
	public static function read( $stream, $length ) {
		$value = FileIO::attempt( fn() => stream_get_contents( $stream, $length ) );
		if ( ! is_string( $value ) || strlen( $value ) !== $length ) {
			throw new \RuntimeException( 'Backup is truncated.' );
		}
		return $value;
	}

	/** Complete writes, including disks returning partial writes.
	 *
	 * @param resource $stream Output.
	 * @param string   $bytes Bytes.
	 * @throws \RuntimeException On I/O failure.
	 */
	public static function write( $stream, $bytes ) {
		while ( '' !== $bytes ) {
			$count = FileIO::attempt( fn() => fwrite( $stream, $bytes ) );
			if ( ! $count ) {
				throw new \RuntimeException( 'Backup write failed.' );
			}
			$bytes = substr( $bytes, $count );
		}
	}
}
