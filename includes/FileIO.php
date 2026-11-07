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
