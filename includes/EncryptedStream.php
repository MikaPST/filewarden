<?php
/**
 * Authenticated, bounded-memory file encryption.
 *
 * @package FileWarden
 */

namespace FileWarden;

/**
 * Fixed-size Sodium secretstream frames with authenticated termination.
 */
final class EncryptedStream {
	private const CHUNK = 65536;

	/**
	 * Transfer a file between plaintext and encrypted streams.
	 *
	 * @param resource $input Input stream.
	 * @param resource $output Output stream.
	 * @param int      $size Expected plaintext size.
	 * @param string   $key Per-file key.
	 * @param bool     $encrypt Whether the output is encrypted.
	 * @throws \RuntimeException If the stream is invalid or incomplete.
	 */
	public static function transfer( $input, $output, $size, $key, $encrypt ) {
		if ( $size < 1 ) {
			throw new \RuntimeException( 'Invalid encrypted file size.' );
		}
		if ( $encrypt ) {
			list( $state, $header ) = sodium_crypto_secretstream_xchacha20poly1305_init_push( $key );
			self::write( $output, $header );
		} else {
			$header = self::read( $input, SODIUM_CRYPTO_SECRETSTREAM_XCHACHA20POLY1305_HEADERBYTES );
			$state  = sodium_crypto_secretstream_xchacha20poly1305_init_pull( $header, $key );
		}
		while ( $size > 0 ) {
			$length = min( self::CHUNK, $size );
			$size  -= $length;
			$tag    = $size ? SODIUM_CRYPTO_SECRETSTREAM_XCHACHA20POLY1305_TAG_MESSAGE : SODIUM_CRYPTO_SECRETSTREAM_XCHACHA20POLY1305_TAG_FINAL;
			if ( $encrypt ) {
				$chunk = self::read( $input, $length );
				self::write( $output, sodium_crypto_secretstream_xchacha20poly1305_push( $state, $chunk, '', $tag ) );
			} else {
				$chunk = self::read( $input, $length + SODIUM_CRYPTO_SECRETSTREAM_XCHACHA20POLY1305_ABYTES );
				$frame = sodium_crypto_secretstream_xchacha20poly1305_pull( $state, $chunk );
				if ( false === $frame || $tag !== $frame[1] ) {
					throw new \RuntimeException( 'Encrypted file authentication failed.' );
				}
				self::write( $output, $frame[0] );
			}
		}
		// phpcs:ignore WordPress.WP.AlternativeFunctions -- Check the native input stream for trailing data.
		if ( '' !== FileIO::attempt( fn() => fread( $input, 1 ) ) || ! feof( $input ) ) {
			throw new \RuntimeException( 'Unexpected file length.' );
		}
	}

	/**
	 * Read an entire frame.
	 *
	 * @param resource $input Input stream.
	 * @param int      $length Required length.
	 * @return string Frame bytes.
	 * @throws \RuntimeException On a short read.
	 */
	private static function read( $input, $length ) {
		$bytes = FileIO::attempt( fn() => stream_get_contents( $input, $length ) );
		if ( false === $bytes || strlen( $bytes ) !== $length ) {
			throw new \RuntimeException( 'Incomplete file frame.' );
		}
		return $bytes;
	}

	/**
	 * Write an entire frame.
	 *
	 * @param resource $output Output stream.
	 * @param string   $bytes Frame bytes.
	 * @throws \RuntimeException On a short write.
	 */
	private static function write( $output, $bytes ) {
		// phpcs:ignore WordPress.WP.AlternativeFunctions -- Write bytes to an existing native stream.
		if ( strlen( $bytes ) !== FileIO::attempt( fn() => fwrite( $output, $bytes ) ) ) {
			throw new \RuntimeException( 'Incomplete file write.' );
		}
	}
}
