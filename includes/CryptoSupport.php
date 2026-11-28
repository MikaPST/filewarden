<?php
/** Native backup cryptography requirements; WordPress polyfills are not proof of availability.
 *
 * @package FileWarden
 */

namespace FileWarden;

/** Distinguish missing native support from an actual cryptographic failure. */
final class CryptoSupport {
	/** Return whether PHP loaded the native extension.
	 *
	 * @return bool Native module present.
	 */
	public static function native() {
		return extension_loaded( 'sodium' );
	}

	/** Verify the complete streaming API before invoking it.
	 *
	 * @throws \RuntimeException If the native API is absent or incomplete.
	 */
	public static function stream() {
		if ( ! self::native() ) {
			throw new \RuntimeException( 'Sodium extension missing.' ); }
		foreach ( array( 'keygen', 'init_push', 'init_pull', 'push', 'pull' ) as $method ) {
			$function = 'sodium_crypto_secretstream_xchacha20poly1305_' . $method;
			if ( ! function_exists( $function ) || ! ( new \ReflectionFunction( $function ) )->isInternal() ) {
				throw new \RuntimeException( 'Sodium streaming unavailable.' );
			}
		}
		if ( ! function_exists( 'sodium_memzero' ) || ! defined( 'SODIUM_CRYPTO_SECRETSTREAM_XCHACHA20POLY1305_TAG_FINAL' ) ) {
			throw new \RuntimeException( 'Sodium streaming unavailable.' );
		}
	}
}
