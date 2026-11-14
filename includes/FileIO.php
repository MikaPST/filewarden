<?php
/**
 * Contain native filesystem warnings which can expose physical paths.
 *
 * @package FileWarden
 */

namespace FileWarden;

/**
 * Native I/O boundary. Callers must check false results and raise safe errors.
 */
final class FileIO {
	/** Open an automatically removed stream outside every known public directory.
	 *
	 * @param array $public_roots Trusted public roots.
	 * @return resource Private temporary stream.
	 * @throws \RuntimeException When isolation cannot be established.
	 * @throws \Throwable After removing an unusable temporary stream.
	 */
	public static function temporary( array $public_roots ) {
		// phpcs:ignore WordPress.WP.AlternativeFunctions -- Anonymous stream, verified before writing sensitive bytes.
		$stream = self::attempt( fn() => tmpfile() );
		try {
			$path = $stream ? self::attempt( fn() => realpath( stream_get_meta_data( $stream )['uri'] ) ) : false;
			if ( ! $path || ! $public_roots ) {
				throw new \RuntimeException( 'Private temporary storage is unavailable.' ); }
			foreach ( $public_roots as $root ) {
				$public = is_string( $root ) && '' !== $root ? self::attempt( fn() => realpath( $root ) ) : false;
				if ( ! $public || StorageService::within( $path, $public ) ) {
					throw new \RuntimeException( 'Private temporary storage cannot be verified.' ); }
			}
			return $stream;
		} catch ( \Throwable $error ) {
			if ( is_resource( $stream ) ) {
				// phpcs:ignore WordPress.WP.AlternativeFunctions -- Closing also removes incomplete sensitive output.
				fclose( $stream ); }
			throw $error;
		}
	}
	/**
	 * Execute one native operation without exposing warning messages.
	 *
	 * @param callable $operation Native filesystem operation.
	 * @return mixed Native result, including false on failure.
	 */
	public static function attempt( callable $operation ) {
		// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_set_error_handler -- Prevent native I/O warnings from disclosing private paths; restore immediately.
		set_error_handler(
			static function () {
				return true;
			},
			E_WARNING
		);
		try {
			return $operation();
		} finally {
			restore_error_handler();
		}
	}
}
