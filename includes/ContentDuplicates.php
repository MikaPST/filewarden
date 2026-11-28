<?php
/** Private exact-content comparison, never a global existence oracle.
 *
 * @package FileWarden
 */

namespace FileWarden;

/** Confirm hashes by comparing original bytes in bounded buffers. */
final class ContentDuplicates {
	/** Return the first readable exact match among active files only.
	 *
	 * @param DocumentService $service Services.
	 * @param string          $path Validated original source.
	 * @param int             $size Source length.
	 * @param string          $digest Server SHA-256.
	 * @return array|null Authorized record or null.
	 * @throws \RuntimeException On query or readable-content integrity failure.
	 */
	public static function find( DocumentService $service, $path, $size, $digest ) {
		global $wpdb;
		$cursor = 0;
		do {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery -- Prepared indexed, bounded, active-only scan. Authorization precedes opening bytes or disclosing a match.
			$ids = $wpdb->get_results( $wpdb->prepare( 'SELECT id,uuid FROM %i WHERE size = %d AND sha256 = %s AND id > %d AND trashed_at IS NULL AND purging = 0 ORDER BY id LIMIT 100', $wpdb->prefix . 'filewarden_documents', $size, $digest, $cursor ), ARRAY_A );
			if ( $wpdb->last_error ) {
				throw new \RuntimeException( 'Content comparison unavailable.' );
			}
			foreach ( $ids as $id ) {
				$cursor = (int) $id['id'];
				$row    = $service->repository->document( $id['uuid'] );
				if ( ! $row || ! $service->permissions->can_read( $row, wp_get_current_user() ) ) {
					continue;
				}
				$existing = $service->storage->open( $row['storage_key'], (int) $row['size'] );
				// phpcs:ignore WordPress.WP.AlternativeFunctions -- Original validated private source, never a URL.
				$source = fopen( $path, 'rb' );
				if ( ! $source ) {
					// phpcs:ignore WordPress.WP.AlternativeFunctions -- Close private stream.
					fclose( $existing );
					throw new \RuntimeException( 'Content comparison unavailable.' );
				}
				try {
					if ( self::equal( $source, $existing, $size ) ) {
						return $row;
					}
				} finally {
					// phpcs:ignore WordPress.WP.AlternativeFunctions -- Close both private streams, including decrypted temporary storage.
					fclose( $source );
					// phpcs:ignore WordPress.WP.AlternativeFunctions -- Close private stream.
					fclose( $existing );
				}
			}
			$more = count( $ids ) === 100;
		} while ( $more );
		return null;
	}

	/** Compare exactly the advertised length, then require EOF.
	 *
	 * @param resource $left Original stream.
	 * @param resource $right Existing private stream.
	 * @param int      $size Original size.
	 * @return bool Exact equality.
	 */
	public static function equal( $left, $right, $size ) {
		for ( $remaining = $size; $remaining > 0; $remaining -= strlen( $a ) ) {
			$length = min( 65536, $remaining );
			$a      = self::read( $left, $length );
			$b      = self::read( $right, $length );
			if ( strlen( $a ) !== $length || $a !== $b ) {
				return false;
			}
		}
		return '' === self::read( $left, 1 ) && '' === self::read( $right, 1 );
	}

	/** Read a bounded chunk, tolerating short stream reads.
	 *
	 * @param resource $stream Private stream.
	 * @param int      $length Bounded length.
	 * @return string Bytes.
	 * @throws \RuntimeException On read failure.
	 */
	private static function read( $stream, $length ) {
		$value = '';
		while ( ! feof( $stream ) ) {
			if ( strlen( $value ) >= $length ) {
				break;
			}
			// phpcs:ignore WordPress.WP.AlternativeFunctions -- Bounded private binary read.
			$chunk = fread( $stream, $length - strlen( $value ) );
			if ( false === $chunk || ( '' === $chunk && ! feof( $stream ) ) ) {
				throw new \RuntimeException( 'Content comparison unavailable.' );
			}
			$value .= $chunk;
		}
		return $value;
	}

	/** Bind a confirmation to this user, session, content, destination and current candidate rules.
	 *
	 * @param array           $row Authorized candidate.
	 * @param int             $folder Destination.
	 * @param string          $name Original name.
	 * @param string          $digest Server digest.
	 * @param DocumentService $service Services.
	 * @return string Opaque session-bound confirmation.
	 */
	public static function proof( array $row, $folder, $name, $digest, DocumentService $service ) {
		return wp_hash( wp_json_encode( array( get_current_user_id(), wp_get_session_token(), wp_nonce_tick(), $row['uuid'], AccessSummary::chain( $row, $service->repository ), $folder, $name, $digest ) ) );
	}
}
