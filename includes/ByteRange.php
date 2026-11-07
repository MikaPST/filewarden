<?php
/**
 * Single byte-range parser; multipart ranges are deliberately rejected.
 *
 * @package FileWarden
 */

namespace FileWarden;

/**
 * ByteRange implementation.
 */
final class ByteRange {
	/**
	 * Parse one satisfiable HTTP byte range.
	 *
	 * @param mixed $header header value.
	 * @param mixed $size size value.
	 * @throws \InvalidArgumentException When the operation cannot be completed safely.
	 * @return mixed Operation result.
	 */
	public static function parse( $header, $size ) {
		if ( ! is_string( $header ) || $size < 1 || ! preg_match( '/^bytes=(\d*)-(\d*)$/D', $header, $matches ) || ( '' === $matches[1] && '' === $matches[2] ) ) {
			throw new \InvalidArgumentException( 'Unsatisfiable range.' );
		}
		if ( '' === $matches[1] ) {
			$suffix = (int) $matches[2];
			if ( $suffix < 1 ) {
				throw new \InvalidArgumentException( 'Unsatisfiable range.' );
			}
			return array( max( 0, $size - $suffix ), $size - 1 );
		}
		$start = (int) $matches[1];
		$end   = '' === $matches[2] ? $size - 1 : min( (int) $matches[2], $size - 1 );
		if ( $start >= $size || $end < $start ) {
			throw new \InvalidArgumentException( 'Unsatisfiable range.' );
		}
		return array( $start, $end );
	}
}
