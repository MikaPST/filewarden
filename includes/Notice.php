<?php
/** Accessible administration feedback.
 *
 * @package FileWarden
 */

namespace FileWarden;

/** One consistent notification vocabulary across administration screens. */
final class Notice {
	/** Print a notification; critical errors never expire automatically.
	 *
	 * @param string $message Plain message.
	 * @param string $type success, info, warning or error.
	 */
	public static function show( $message, $type = 'info' ) {
		$type = in_array( $type, array( 'success', 'info', 'warning', 'error' ), true ) ? $type : 'info';
		echo '<div class="notice notice-' . esc_attr( $type ) . ' fw-notice" role="' . ( 'error' === $type ? 'alert' : 'status' ) . '"><p>' . esc_html( $message ) . '</p></div>';
	}
}
