<?php
/** Explicit permissive imports remain protected, non-executable attachments.
 *
 * @package FileWarden
 */

namespace FileWarden;

/** The strict validator remains the default and is never weakened globally. */
final class ImportPolicy {

	/** Accept only the exact persisted opt-in value.
	 *
	 * @param bool $fresh Refresh after waiting on the common write gate.
	 * @return bool Whether all formats were explicitly enabled.
	 */
	public static function unrestricted( $fresh = false ) {
		if ( $fresh ) {
			global $wpdb;
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery -- Read the exact persisted opt-in after the write gate, without flushing unrelated option caches.
			$value = $wpdb->get_var( $wpdb->prepare( 'SELECT option_value FROM %i WHERE option_name = %s', $wpdb->options, 'filewarden_allow_all_types' ) );
			return '1' === $value;
		}
		return in_array( get_option( 'filewarden_allow_all_types', '0' ), array( '1', 1 ), true );
	}

	/** Validate ordinary formats; refused content may only become an opaque attachment.
	 *
	 * @param string $path Verified local file.
	 * @param string $extension Sanitized extension.
	 * @return string Safe response type, not a client-supplied MIME.
	 * @throws \InvalidArgumentException When strict validation refuses an import.
	 */
	public static function mime( $path, $extension ) {
		try {
			return FileValidator::validate( $path, $extension );
		} catch ( \InvalidArgumentException $error ) {
			if ( ! self::unrestricted() ) {
				throw $error;
			}
			// Never reflect executable/HTML/SVG MIME types or grant inline preview.
			return 'application/octet-stream';
		}
	}
}
