<?php
/**
 * Audit console and nonce-protected export/purge transport.
 *
 * @package FileWarden
 */

namespace FileWarden;

/** Restricted console with one filter scope shared by screen and exports. */
final class AuditConsole {
	/**
	 * Read scalar filters only; authorization is independent.
	 *
	 * @param array $source Input.
	 * @return array Fields.
	 */
	private static function input( array $source ) {
		$fields = array();
		foreach ( array( 'event', 'from', 'until' ) as $key ) {
			$fields[ $key ] = isset( $source[ $key ] ) && is_string( $source[ $key ] ) ? sanitize_text_field( wp_unslash( $source[ $key ] ) ) : '';
		}
		return $fields;
	}
	/** Render the console without exposing a log to unauthorized visitors. */
	public static function page() {
		try {
			AuditHistory::authorize();
            // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only filters, validated below.
			$input = self::input( $_GET );
            // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Feedback only, never used as proof of a mutation.
			if ( isset( $_GET['purged'] ) && '1' === $_GET['purged'] ) {
				echo '<div class="notice notice-success"><p>' . esc_html__( 'Selected events purged. A purge trace has been retained.', 'filewarden' ) . '</p></div>'; }
			$filters = AuditHistory::filters( $input );
			$upper   = AuditHistory::upper();
            // phpcs:ignore WordPress.Security.NonceVerification.Recommended,WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Read-only cursor strictly validated.
			$cursor = isset( $_GET['before'] ) ? min( $upper, RestController::integer( wp_unslash( $_GET['before'] ) ) ) : $upper;
			$rows   = AuditHistory::rows( $filters, $cursor );
			echo '<div class="wrap fw-workspace"><header class="fw-heading"><div><span class="fw-brand">FILEWARDEN</span><h1>' . esc_html__( 'Audit log', 'filewarden' ) . '</h1></div></header><div class="fw-audit-layout"><section class="fw-content"><h2>' . esc_html__( 'Recorded events', 'filewarden' ) . '</h2><p>' . esc_html__( 'Times shown in the site timezone:', 'filewarden' ) . ' ' . esc_html( wp_timezone_string() ) . '</p><div class="fw-terminal" role="log" aria-label="' . esc_attr__( 'Audit log', 'filewarden' ) . '">';
			foreach ( array_slice( $rows, 0, 50 ) as $row ) {
				echo '<div class="fw-log-line"><time>' . esc_html( get_date_from_gmt( $row['created_at'], 'Y-m-d H:i:s' ) ) . '</time><strong>' . esc_html( $row['event'] ) . '</strong><span>' . esc_html( sprintf( 'User #%d · Object/count #%d', $row['actor_id'], $row['object_id'] ) ) . '</span></div>';
			}
			if ( ! $rows ) {
				echo '<p>' . esc_html__( 'No matching events.', 'filewarden' ) . '</p>'; }
			echo '</div>';
			if ( count( $rows ) > 50 ) {
				echo '<p><a class="button" href="' . esc_url(
					add_query_arg(
						$input + array(
							'page'   => 'filewarden-audit',
							'before' => (int) $rows[49]['id'] - 1,
						),
						admin_url( 'admin.php' )
					)
				) . '">' . esc_html__( 'Older events', 'filewarden' ) . '</a></p>'; }
			echo '</section><aside class="fw-content fw-audit-controls"><h2>' . esc_html__( 'Filters and exports', 'filewarden' ) . '</h2><form method="get"><input type="hidden" name="page" value="filewarden-audit"><p><label for="fw-event">' . esc_html__( 'Event type', 'filewarden' ) . '</label><select id="fw-event" name="event"><option value="">' . esc_html__( 'All events', 'filewarden' ) . '</option>';
			global $wpdb;
            // phpcs:ignore WordPress.DB.DirectDatabaseQuery -- Fixed bounded query, after audit authorization.
			$events = $wpdb->get_col( "SELECT DISTINCT event FROM {$wpdb->prefix}filewarden_audit_logs ORDER BY event LIMIT 200" );
			foreach ( $events as $event ) {
				echo '<option value="' . esc_attr( $event ) . '"' . selected( $input['event'], $event, false ) . '>' . esc_html( $event ) . '</option>'; }
			echo '</select></p>';
			foreach ( array(
				'from'  => __( 'From', 'filewarden' ),
				'until' => __( 'Until', 'filewarden' ),
			) as $key => $label ) {
				echo '<p><label for="fw-' . esc_attr( $key ) . '">' . esc_html( $label ) . '</label><input id="fw-' . esc_attr( $key ) . '" name="' . esc_attr( $key ) . '" type="datetime-local" value="' . esc_attr( $input[ $key ] ) . '"></p>'; }
			echo '<button class="button button-primary">' . esc_html__( 'Apply filters', 'filewarden' ) . '</button>';
			if ( current_user_can( 'filewarden_manage_audit' ) ) {
				echo ' <button class="button" name="review" value="1">' . esc_html__( 'Review purge', 'filewarden' ) . '</button>'; }
			echo '</form><hr><p>' . esc_html__( 'Exports use the current filters. Export before purging if you need a copy.', 'filewarden' ) . '</p><div class="fw-export-actions">';
			foreach ( array( 'csv', 'txt' ) as $format ) {
				$url = wp_nonce_url(
					add_query_arg(
						$input + array(
							'action' => 'filewarden_audit',
							'format' => $format,
							'upper'  => $upper,
						),
						admin_url( 'admin-post.php' )
					),
					'filewarden_audit'
				);
				echo '<a class="button" href="' . esc_url( $url ) . '">' . esc_html( strtoupper( $format ) ) . '</a> ';
			}
			echo '</div>';
            // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Review is read-only; purge POST requires a fresh nonce and capability.
			if ( isset( $_GET['review'] ) && current_user_can( 'filewarden_manage_audit' ) ) {
				$review = AuditHistory::review( $filters, $upper );
				echo '<div class="fw-warning"><h3>' . esc_html__( 'Irreversible purge', 'filewarden' ) . '</h3><p>' . esc_html( number_format_i18n( $review['count'] ) ) . ' ' . esc_html__( 'events selected. New events and purge traces are excluded.', 'filewarden' ) . '</p>';
				if ( $review['count'] > 0 && $review['count'] <= 10000 ) {
					echo '<form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '">';
					wp_nonce_field( 'filewarden_audit' );
					foreach ( $input + array(
						'action'    => 'filewarden_audit',
						'operation' => 'purge',
						'upper'     => $upper,
						'proof'     => $review['proof'],
					) as $key => $value ) {
						echo '<input type="hidden" name="' . esc_attr( $key ) . '" value="' . esc_attr( $value ) . '">'; }
					echo '<p><label><input type="checkbox" name="confirm" value="1" required> ' . esc_html__( 'I understand these events cannot be restored.', 'filewarden' ) . '</label></p><button class="button">' . esc_html__( 'Permanently purge selected events', 'filewarden' ) . '</button></form>';
				} else {
					echo '<p>' . esc_html__( 'Select between 1 and 10000 events by narrowing the filters.', 'filewarden' ) . '</p>'; }
				echo '</div>';
			}
			echo '</aside></div></div>';
		} catch ( \Throwable $error ) {
			echo '<div class="notice notice-error"><p>' . esc_html__( 'Audit unavailable. Check your permissions and date filters.', 'filewarden' ) . '</p></div>'; }
	}
	/**
	 * Handle a reviewed purge or stream an authorized export.
	 *
	 * @throws \InvalidArgumentException Caught and rendered for invalid confirmations.
	 */
	public static function handle() {
		try {
			AuditHistory::authorize();
			check_admin_referer( 'filewarden_audit' );
			$post = 'POST' === sanitize_text_field( wp_unslash( $_SERVER['REQUEST_METHOD'] ?? '' ) );
            // phpcs:ignore WordPress.Security.NonceVerification -- Nonce verified above; filters validated by filters().
			$source  = $post ? $_POST : $_GET;
			$input   = self::input( $source );
			$filters = AuditHistory::filters( $input );
			$upper   = RestController::integer( wp_unslash( $source['upper'] ?? null ) );
			if ( $post ) {
				if ( 'purge' !== ( $source['operation'] ?? '' ) || '1' !== ( $source['confirm'] ?? '' ) ) {
					throw new \InvalidArgumentException( 'Confirmation required.' ); }
				$proof = isset( $source['proof'] ) && is_string( $source['proof'] ) ? sanitize_text_field( wp_unslash( $source['proof'] ) ) : '';
				AuditHistory::purge( $filters, $upper, $proof );
				wp_safe_redirect(
					add_query_arg(
						$input + array(
							'page'   => 'filewarden-audit',
							'purged' => '1',
						),
						admin_url( 'admin.php' )
					)
				);
				exit;
			}
			$format = $source['format'] ?? '';
			if ( ! in_array( $format, array( 'csv', 'txt' ), true ) ) {
				throw new \InvalidArgumentException( 'Invalid export.' ); }
			( new AuditService() )->record( 'audit_export_requested' );
			nocache_headers();
			header( 'X-Content-Type-Options: nosniff' );
			header( 'Content-Type: ' . ( 'csv' === $format ? 'text/csv' : 'text/plain' ) . '; charset=UTF-8' );
			header( 'Content-Disposition: attachment; filename="filewarden-audit.' . $format . '"' );
            // phpcs:ignore WordPress.WP.AlternativeFunctions -- Stream download without creating a public temporary file.
			$stream  = fopen( 'php://output', 'wb' );
			$headers = array( 'id', 'created_at_UTC', 'event', 'actor_id', 'object_id_or_count' );
			self::line( $stream, $headers, $format );
			do {
				$rows = AuditHistory::rows( $filters, $upper, 1000 );
				foreach ( $rows as $row ) {
					self::line( $stream, array_values( $row ), $format );
					$upper = (int) $row['id'] - 1; }
				$batch_count = count( $rows );
			} while ( 1000 === $batch_count );
            // phpcs:ignore WordPress.WP.AlternativeFunctions -- Close the response stream.
			fclose( $stream );
		} catch ( \Throwable $error ) {
			wp_die( esc_html__( 'Audit action refused. Review your filters, permissions and confirmation.', 'filewarden' ), '', array( 'response' => 400 ) ); }
		exit;
	}
	/**
	 * Write plain text safely, including spreadsheet formula protection.
	 *
	 * @param resource $stream Response stream.
	 * @param array    $values Cells.
	 * @param string   $format CSV or text.
	 */
	private static function line( $stream, array $values, $format ) {
		$values = array_map(
			static function ( $value ) {
				$value = preg_replace( '/[\r\n\t]/', ' ', (string) $value );
				return preg_match( '/^\s*[=+@-]/', $value ) ? "'" . $value : $value;
			},
			$values
		);
		if ( 'csv' === $format ) {
			fputcsv( $stream, $values, ',', '"', '' ); } else {
            // phpcs:ignore WordPress.WP.AlternativeFunctions -- Text response stream.
			fwrite( $stream, implode( "\t", $values ) . "\n" );
			}
	}
}
