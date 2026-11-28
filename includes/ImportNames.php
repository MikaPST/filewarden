<?php
/** Non-destructive names for independently confirmed document copies.
 *
 * @package FileWarden
 */

namespace FileWarden;

/** Naming is advisory until the writer gate rechecks the chosen destination. */
final class ImportNames {
	/** Suggest a numbered filename, preserving extension and valid UTF-8.
	 *
	 * @param string   $name Sanitized original filename.
	 * @param callable $used Destination name lookup under the writer gate.
	 * @return string Available suggestion, never an overwrite instruction.
	 * @throws \InvalidArgumentException If no bounded safe suggestion is available.
	 */
	public static function available( $name, callable $used ) {
		$extension  = pathinfo( $name, PATHINFO_EXTENSION );
		$stem       = '' !== $extension ? substr( $name, 0, -strlen( $extension ) - 1 ) : $name;
		$characters = preg_split( '//u', $stem, -1, PREG_SPLIT_NO_EMPTY );
		if ( false === $characters ) {
			throw new \InvalidArgumentException( 'Invalid filename.' );
		}
		for ( $number = 2; $number <= 1000; ++$number ) {
			$suffix = '-' . $number . ( '' !== $extension ? '.' . $extension : '' );
			$base   = $characters;
			while ( $base ) {
				if ( strlen( implode( '', $base ) ) + strlen( $suffix ) <= 255 ) {
					break;
				}
				array_pop( $base );
			}
			if ( ! $base ) {
				break;
			}
			$candidate = implode( '', $base ) . $suffix;
			if ( ! $used( $candidate ) ) {
				return $candidate;
			}
		}
		throw new \InvalidArgumentException( 'Choose another filename.' );
	}
}
