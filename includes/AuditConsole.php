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
		foreach ( array( 'event', 'actor_login', 'from', 'until' ) as $key ) {
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
            // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only display preference.
			$view = isset( $_GET['view'] ) && is_string( $_GET['view'] ) && 'technical' === sanitize_key( wp_unslash( $_GET['view'] ) ) ? 'technical' : 'simple';
            // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Feedback only, never used as proof of a mutation.
			if ( isset( $_GET['purged'] ) && '1' === $_GET['purged'] ) {
				echo '<div class="notice notice-success"><p>' . esc_html__( 'Selected events purged. A purge trace has been retained.', 'filewarden' ) . '</p></div>'; }
			$filters = AuditHistory::filters( $input );
			$upper   = AuditHistory::upper();
            // phpcs:ignore WordPress.Security.NonceVerification.Recommended,WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Read-only cursor strictly validated.
			$cursor = isset( $_GET['before'] ) ? min( $upper, RestController::integer( wp_unslash( $_GET['before'] ) ) ) : $upper;
			$rows   = AuditHistory::rows( $filters, $cursor );
			echo '<div class="wrap fw-workspace"><header class="fw-heading"><div><span class="fw-brand">FILEWARDEN</span><h1>' . esc_html__( 'Audit log', 'filewarden' ) . '</h1></div></header><div class="fw-audit-layout"><section class="fw-content"><h2>' . esc_html__( 'Recorded events', 'filewarden' ) . '</h2><p>' . esc_html__( 'Times shown in the site timezone:', 'filewarden' ) . ' ' . esc_html( wp_timezone_string() ) . '</p><nav class="fw-audit-views" aria-label="' . esc_attr__( 'Event display mode', 'filewarden' ) . '">';
			foreach ( array(
				'simple'    => __( 'Simplified', 'filewarden' ),
				'technical' => __( 'Technical', 'filewarden' ),
			) as $mode => $label ) {
				$view_url = add_query_arg(
					$input + array(
						'page'   => 'filewarden-audit',
						'view'   => $mode,
						'before' => $cursor,
					),
					admin_url( 'admin.php' )
				);
				echo '<a class="button ' . ( $view === $mode ? 'button-primary' : '' ) . '" href="' . esc_url( $view_url ) . '"' . ( $view === $mode ? ' aria-current="page"' : '' ) . '>' . esc_html( $label ) . '</a> ';
			}
			echo '</nav><div class="fw-terminal" role="log" aria-label="' . esc_attr__( 'Audit log', 'filewarden' ) . '">';
			foreach ( array_slice( $rows, 0, 50 ) as $row ) {
				$login     = self::actor_login( $row );
				$technical = self::export_record( $row );
				if ( 'technical' === $view ) {
					echo '<details class="fw-log-line"><summary><time>' . esc_html( get_date_from_gmt( $row['created_at'], 'Y-m-d H:i:s' ) ) . '</time> <strong>' . esc_html( $row['event'] ) . '</strong> <span>' . esc_html( sprintf( 'WordPress: %s · #%d', $login ? $login : __( 'Unavailable', 'filewarden' ), $row['actor_id'] ) ) . '</span></summary><pre><code>' . esc_html( wp_json_encode( $technical, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE ) ) . '</code></pre></details>';
				} else {
					$actor_id = (int) $row['actor_id'];
					if ( 0 === $actor_id ) {
						$actor = match ( $row['actor_type'] ?? '' ) {
							'visitor' => __( 'Visitor not logged in', 'filewarden' ), 'system' => __( 'System action', 'filewarden' ), default => __( 'Unknown legacy actor', 'filewarden' ) };
					} elseif ( $login ) {
						/* translators: 1: WordPress account login; 2: numeric WordPress user ID. */
						$actor = sprintf( __( '%1$s · WordPress user #%2$d', 'filewarden' ), $login, $actor_id );
					} else {
						/* translators: %d: numeric WordPress user ID. */
						$actor = sprintf( __( 'WordPress user #%d', 'filewarden' ), $actor_id );
					}
					$resource = self::resource_name( $row );
					/* translators: 1: audit record ID; 2: related object ID or event count. */
					$detail = sprintf( __( 'Record #%1$d · related ID or count #%2$d', 'filewarden' ), $row['id'], $row['object_id'] );
					if ( $resource ) {
						$detail .= ' · ' . $resource; }
					echo '<details class="fw-log-line"><summary><time>' . esc_html( get_date_from_gmt( $row['created_at'], 'Y-m-d H:i:s' ) ) . '</time> <strong>' . esc_html( self::event_label( $row['event'] ) ) . '</strong> <span>' . esc_html( $actor ) . '</span></summary><p>' . esc_html( $detail ) . '</p><pre><code>' . esc_html( wp_json_encode( $technical, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE ) ) . '</code></pre></details>';
				}
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
							'view'   => $view,
						),
						admin_url( 'admin.php' )
					)
				) . '">' . esc_html__( 'Older events', 'filewarden' ) . '</a></p>'; }
			echo '</section><aside class="fw-content fw-audit-controls"><h2>' . esc_html__( 'Filters and exports', 'filewarden' ) . '</h2><form id="fw-audit-filters" method="get"><input type="hidden" name="view" value="' . esc_attr( $view ) . '"><input type="hidden" name="page" value="filewarden-audit"><p><label for="fw-event">' . esc_html__( 'Event type', 'filewarden' ) . '</label><select id="fw-event" name="event"><option value="">' . esc_html__( 'All events', 'filewarden' ) . '</option>';
			global $wpdb;
            // phpcs:ignore WordPress.DB.DirectDatabaseQuery -- Fixed bounded query, after audit authorization.
			$events = $wpdb->get_col( "SELECT DISTINCT event FROM {$wpdb->prefix}filewarden_audit_logs ORDER BY event LIMIT 200" );
			foreach ( $events as $event ) {
				echo '<option value="' . esc_attr( $event ) . '"' . selected( $input['event'], $event, false ) . '>' . esc_html( self::event_label( $event ) ) . '</option>'; }
			echo '</select></p><details class="fw-audit-advanced"' . ( $input['actor_login'] ? ' open' : '' ) . '><summary>' . esc_html__( 'Advanced filters', 'filewarden' ) . '</summary><p><label for="fw-actor-login">' . esc_html__( 'WordPress username', 'filewarden' ) . '</label><input id="fw-actor-login" name="actor_login" type="search" maxlength="60" value="' . esc_attr( $input['actor_login'] ) . '"></p></details>';
			foreach ( array(
				'from'  => __( 'From', 'filewarden' ),
				'until' => __( 'Until', 'filewarden' ),
			) as $key => $label ) {
				echo '<p><label for="fw-' . esc_attr( $key ) . '">' . esc_html( $label ) . '</label><input id="fw-' . esc_attr( $key ) . '" name="' . esc_attr( $key ) . '" type="datetime-local" value="' . esc_attr( $input[ $key ] ) . '"></p>'; }
			echo '<button class="button button-primary">' . esc_html__( 'Apply filters', 'filewarden' ) . '</button>';
			$reset_url = add_query_arg(
				array(
					'page' => 'filewarden-audit',
					'view' => $view,
				),
				admin_url( 'admin.php' )
			);
			echo ' <a class="button" href="' . esc_url( $reset_url ) . '">' . esc_html__( 'Reset', 'filewarden' ) . '</a>';

			echo '</form><hr><h3>' . esc_html__( 'Export events', 'filewarden' ) . '</h3><p>' . esc_html__( 'Exports use all events matching the applied filters, regardless of the current page or display mode.', 'filewarden' ) . '</p><div class="fw-export-actions">';
			foreach ( array( 'csv', 'txt', 'jsonl' ) as $format ) {
				$url   = wp_nonce_url(
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
				$icons = array(
					'csv'   => 'dashicons-media-spreadsheet',
					'txt'   => 'dashicons-media-text',
					'jsonl' => 'dashicons-editor-code',
				);
				/* translators: %s: export format (CSV, TXT, or JSONL). */
				$progress_message = sprintf( __( 'Preparing your %s download…', 'filewarden' ), strtoupper( $format ) );
				/* translators: %s: export format (CSV, TXT, or JSONL). */
				$download_label = sprintf( __( 'Download %s audit file', 'filewarden' ), strtoupper( $format ) );
				echo '<a class="button fw-export-button fw-export-' . esc_attr( $format ) . '" data-export-message="' . esc_attr( $progress_message ) . '" data-export-success="' . esc_attr__( 'File received. Your browser has been asked to download it.', 'filewarden' ) . '" data-export-error="' . esc_attr__( 'Download failed. Check your connection and permissions, then retry.', 'filewarden' ) . '" href="' . esc_url( $url ) . '"><span class="dashicons ' . esc_attr( $icons[ $format ] ) . '" aria-hidden="true"></span><span>' . esc_html( strtoupper( $format ) ) . '</span><span class="screen-reader-text">' . esc_html( $download_label ) . '</span></a> ';
			}
			echo '</div><p class="fw-export-status" aria-live="polite" role="status"></p><hr><h3>' . esc_html__( 'Audit maintenance', 'filewarden' ) . '</h3><p>' . esc_html__( 'Export the selected events before purging if you need a copy.', 'filewarden' ) . '</p>';
			if ( current_user_can( 'filewarden_manage_audit' ) ) {
				echo '<form method="get" class="fw-purge-applied-scope">';
				foreach ( $input + array(
					'page'   => 'filewarden-audit',
					'view'   => $view,
					'review' => 1,
				) as $key => $value ) {
					echo '<input type="hidden" name="' . esc_attr( $key ) . '" value="' . esc_attr( $value ) . '">'; }
				echo '<button class="button fw-button-danger"><span class="dashicons dashicons-trash" aria-hidden="true"></span> ' . esc_html__( 'Prepare purge', 'filewarden' ) . '</button></form>'; }
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
					echo '<p><label><input type="checkbox" name="confirm" value="1" required> ' . esc_html__( 'I understand these events cannot be restored.', 'filewarden' ) . '</label></p><button class="button fw-button-danger"><span class="dashicons dashicons-trash" aria-hidden="true"></span> ' . esc_html__( 'Permanently purge selected events', 'filewarden' ) . '</button></form>';
				} else {
					echo '<p>' . esc_html__( 'Select between 1 and 10000 events by narrowing the filters.', 'filewarden' ) . '</p>'; }
				echo '</div>';
			}
			echo '</aside></div></div>';
		} catch ( \Throwable $error ) {
			echo '<div class="notice notice-error"><p>' . esc_html__( 'Audit unavailable. Check your permissions and date filters.', 'filewarden' ) . '</p></div>'; }
	}
	/**
	 * Resolve the stored WordPress login, including pre-migration audit events.
	 *
	 * @param array $row Audit record.
	 * @return string WordPress login or empty string.
	 */
	private static function actor_login( array $row ) {
		if ( ! empty( $row['actor_login'] ) ) {
			return (string) $row['actor_login']; }
		$user = ! empty( $row['actor_id'] ) ? get_userdata( (int) $row['actor_id'] ) : false;
		return $user ? (string) $user->user_login : '';
	}
	/**
	 * Resolve a current document or folder name when the event's numeric ID identifies one.
	 *
	 * @param array $row Audit record.
	 * @return string Authorized resource name or empty string.
	 */
	private static function resource_name( array $row ) {
		$event = (string) $row['event'];
		$id    = (int) $row['object_id'];
		if ( $id < 1 ) {
			return ''; }
		$type = $row['resource_type'] ?? '';
		if ( ! $type ) {
			$type = str_starts_with( $event, 'document_' ) || in_array( $event, array( 'preview_requested', 'download_started', 'download_head' ), true ) ? 'document' : ( str_starts_with( $event, 'folder_' ) || 'upload_requested' === $event || str_starts_with( $event, 'upload_failed_' ) ? 'folder' : '' ); }
		if ( ! $type ) {
			return ''; }
		$repo   = new Repository();
		$record = 'document' === $type ? $repo->document_by_id( $id, true ) : $repo->folder( $id );
		$user   = wp_get_current_user();
		if ( $record ) {
			unset( $record['trashed_at'], $record['purging'] );
			return ( new PermissionService( array( $repo, 'folder' ) ) )->can_read( $record, $user ) ? (string) ( ! empty( $row['resource_name'] ) ? $row['resource_name'] : $record['name'] ) : '';
		}
		$saved = json_decode( $row['resource_rules'] ?? '', true );
		if ( empty( $saved['policies'] ) || empty( $row['resource_name'] ) ) {
			return ''; }
		if ( ! empty( $saved['recovery'] ) && ! ( $user->has_cap( 'filewarden_manage_settings' ) && $user->has_cap( 'filewarden_manage_permissions' ) ) ) {
			return ''; }
		foreach ( $saved['policies'] as $policy ) {
			if ( ! $user->has_cap( 'filewarden_read_all_files' ) && ! Policy::allows( $policy, $user->exists(), $user->roles ) ) {
				return ''; }
		}
		return (string) $row['resource_name'];
	}

	/** Normalize one authorized audit record for both views and versioned exports.
	 *
	 * @param array $row Database event.
	 * @return array Stable schema 2 record.
	 */
	private static function export_record( array $row ) {
		$date  = \DateTimeImmutable::createFromFormat( '!Y-m-d H:i:s', $row['created_at'], new \DateTimeZone( 'UTC' ) );
		$event = (string) $row['event'];
		$type  = $row['resource_type'] ?? '';
		return array(
			'schema_version'     => 2,
			'id'                 => (int) $row['id'],
			'created_at_UTC'     => $date ? $date->format( 'Y-m-d\TH:i:s\Z' ) : null,
			'event'              => $event,
			'actor_id'           => (int) $row['actor_id'],
			'actor_login'        => self::actor_login( $row ) ? self::actor_login( $row ) : null,
			'actor_type'         => ! empty( $row['actor_type'] ) ? $row['actor_type'] : ( $row['actor_id'] ? 'user' : 'unknown' ),
			'resource_type'      => $type ? $type : null,
			'resource_id'        => $type ? (int) $row['object_id'] : null,
			'resource_name'      => self::resource_name( $row ) ? self::resource_name( $row ) : null,
			'outcome'            => str_contains( $event, 'denied' ) ? 'denied' : ( str_contains( $event, 'failed' ) ? 'failure' : ( str_ends_with( $event, '_requested' ) ? 'requested' : 'success' ) ),
			'object_id_or_count' => (int) $row['object_id'],
		);
	}

	/**
	 * Translate known event codes and keep unknown codes readable.
	 *
	 * @param string $event Event code.
	 * @return string Human-readable label.
	 */
	private static function event_label( $event ) {
		$labels = array(
			'folder_policy_change_requested'   => __( 'Folder permission change requested', 'filewarden' ),
			'folder_policy_changed'            => __( 'Folder permissions changed', 'filewarden' ),
			'access_denied'                    => __( 'Access denied', 'filewarden' ),
			'document_uploaded'                => __( 'Document added', 'filewarden' ),
			'document_policy_change_requested' => __( 'Document permission change requested', 'filewarden' ),
			'document_policy_changed'          => __( 'Document permissions changed', 'filewarden' ),
			'document_move_requested'          => __( 'Document move requested', 'filewarden' ),
			'document_move_completed'          => __( 'Document moved', 'filewarden' ),
			'document_copy_requested'          => __( 'Document copy requested', 'filewarden' ),
			'document_copy_completed'          => __( 'Document copied', 'filewarden' ),
			'document_trashed_requested'       => __( 'Document removal requested', 'filewarden' ),
			'document_trashed'                 => __( 'Document moved to trash', 'filewarden' ),
			'document_restored_requested'      => __( 'Document restoration requested', 'filewarden' ),
			'document_restored'                => __( 'Document restored', 'filewarden' ),
			'document_purge_requested'         => __( 'Document purge requested', 'filewarden' ),
			'document_purged'                  => __( 'Document permanently deleted', 'filewarden' ),
			'folder_created'                   => __( 'Folder created', 'filewarden' ),
			'folder_create_requested'          => __( 'Folder creation requested', 'filewarden' ),
			'folder_delete_requested'          => __( 'Folder deletion requested', 'filewarden' ),
			'settings_changed'                 => __( 'Settings changed', 'filewarden' ),
			'audit_export_requested'           => __( 'Audit export requested', 'filewarden' ),
			'audit_purged'                     => __( 'Audit events purged', 'filewarden' ),
			'preview_requested'                => __( 'Document preview requested', 'filewarden' ),
			'download_started'                 => __( 'Document download started', 'filewarden' ),
			'download_head'                    => __( 'Document metadata requested', 'filewarden' ),
			'upload_requested'                 => __( 'Document upload requested', 'filewarden' ),
			'folder_deleted_files_recovered'   => __( 'Folder deleted; documents recovered', 'filewarden' ),
		);
		if ( isset( $labels[ $event ] ) ) {
			return $labels[ $event ]; }
		if ( str_starts_with( (string) $event, 'upload_failed_' ) ) {
			return __( 'Document upload failed', 'filewarden' ); }
		return ucwords( str_replace( '_', ' ', (string) $event ) );
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
			if ( ! in_array( $format, array( 'csv', 'txt', 'jsonl' ), true ) ) {
				throw new \InvalidArgumentException( 'Invalid export.' ); }
			( new AuditService() )->record( 'audit_export_requested' );
			nocache_headers();
			header( 'X-Content-Type-Options: nosniff' );
			header( 'Content-Type: ' . ( 'csv' === $format ? 'text/csv' : ( 'jsonl' === $format ? 'application/x-ndjson' : 'text/plain' ) ) . '; charset=UTF-8' );
			header( 'Content-Disposition: attachment; filename="filewarden-audit-' . wp_date( 'Y-m-d' ) . '.' . $format . '"' );
            // phpcs:ignore WordPress.WP.AlternativeFunctions -- Stream download without creating a public temporary file.
			$stream  = fopen( 'php://output', 'wb' );
			$headers = array( 'schema_version', 'id', 'created_at_UTC', 'event', 'actor_id', 'actor_login', 'actor_type', 'resource_type', 'resource_id', 'resource_name', 'outcome', 'object_id_or_count' );
			if ( 'jsonl' !== $format ) {
				self::line( $stream, $headers, $format ); }
			do {
				$rows = AuditHistory::rows( $filters, $upper, 1000 );
				foreach ( $rows as $row ) {
					self::line( $stream, self::export_record( $row ), $format );
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
	 * @throws \RuntimeException When a JSONL record cannot be encoded.
	 */
	private static function line( $stream, array $values, $format ) {
		if ( array_is_list( $values ) && 6 === count( $values ) && is_numeric( $values[0] ) ) {
			$values = self::export_record( array_combine( array( 'id', 'created_at', 'event', 'actor_id', 'actor_login', 'object_id' ), $values ) );
		}
		if ( 'jsonl' === $format ) {
			$json = wp_json_encode( $values, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES );
			if ( false === $json ) {
				throw new \RuntimeException( 'Audit JSONL encoding failed.' ); }
            // phpcs:ignore WordPress.WP.AlternativeFunctions -- Stream a versioned, authorized JSON record.
			if ( false === fwrite( $stream, $json . "\n" ) ) {
				throw new \RuntimeException( 'Audit stream failed.' ); }
			return;
		}
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
