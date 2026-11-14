<?php
/** Accessible administration feedback.
 *
 * @package FileWarden
 */

namespace FileWarden;

/** One consistent notification vocabulary across administration screens. */
final class Notice {
	const DURATIONS = array( 5, 10, 15, 20, 30 );

	/** Read a validated duration, including installations without the new option.
	 *
	 * @return int Seconds.
	 */
	public static function duration() {
		$value = (int) get_option( 'filewarden_notification_seconds', 10 );
		return in_array( $value, self::DURATIONS, true ) ? $value : 10;
	}

	/** Localized short titles shared with asynchronous feedback.
	 *
	 * @return array Titles by type.
	 */
	public static function titles() {
		return array(
			'success' => __( 'Operation completed', 'filewarden' ),
			'info'    => __( 'Information', 'filewarden' ),
			'warning' => __( 'Attention required', 'filewarden' ),
			'error'   => __( 'Operation unavailable', 'filewarden' ),
		);
	}

	/** Bind feedback to an actual server outcome and the current WordPress user.
	 *
	 * @param string $result Outcome code.
	 * @param string $operation Operation code.
	 * @param int    $folder Destination.
	 * @param string $created Created UUID, if any.
	 * @return string Nonce used only for feedback integrity, never authorization.
	 */
	public static function receipt( $result, $operation = '', $folder = 0, $created = '' ) {
		return wp_create_nonce( 'filewarden-feedback|' . $result . '|' . $operation . '|' . $folder . '|' . $created );
	}

	/** Verify a result link without trusting query strings as successful operations.
	 *
	 * @param string $result Expected result code.
	 * @return bool Whether the server signed this outcome for this user.
	 */
	public static function verified( $result ) {
		$values = array();
		foreach ( array( 'fw_feedback', 'fw_operation', 'folder_id', 'created' ) as $key ) {
			// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Feedback proof is verified below; never used to authorize an operation.
			$values[ $key ] = isset( $_GET[ $key ] ) && is_string( $_GET[ $key ] ) ? sanitize_text_field( wp_unslash( $_GET[ $key ] ) ) : '';
		}
		return (bool) wp_verify_nonce( $values['fw_feedback'], 'filewarden-feedback|' . $result . '|' . $values['fw_operation'] . '|' . ( $values['folder_id'] ? $values['folder_id'] : '0' ) . '|' . $values['created'] );
	}

	/** Print a notification; critical errors never expire automatically.
	 *
	 * @param string    $message Plain message.
	 * @param string    $type success, info, warning or error.
	 * @param string    $title Optional short title.
	 * @param bool|null $persistent Whether manual closure or intervention is required.
	 */
	public static function show( $message, $type = 'info', $title = '', $persistent = null ) {
		$type       = in_array( $type, array( 'success', 'info', 'warning', 'error' ), true ) ? $type : 'info';
		$persistent = null === $persistent ? in_array( $type, array( 'error', 'warning' ), true ) : (bool) $persistent;
		$title      = $title ? $title : self::titles()[ $type ];
		echo '<div class="fw-notice fw-notice-' . esc_attr( $type ) . '" data-fw-persistent="' . ( $persistent ? 'true' : 'false' ) . '" role="' . ( 'error' === $type ? 'alert' : 'status' ) . '"><h2 class="fw-notice-title">' . esc_html( $title ) . '</h2><p>' . esc_html( $message ) . '</p></div>';
	}
}
