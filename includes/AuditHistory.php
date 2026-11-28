<?php
/**
 * Filtered, exportable audit history with explicitly reviewed purges.
 *
 * @package FileWarden
 */

namespace FileWarden;

// phpcs:disable WordPress.DB.DirectDatabaseQuery -- Fresh prepared access to custom audit tables.
/** Separate audit administration from content-management permissions. */
final class AuditHistory {
	/**
	 * Validate filters and convert local dates to stored UTC.
	 *
	 * @param array $input Input fields.
	 * @return array Normalized query filters.
	 * @throws \InvalidArgumentException For malformed dates or event names.
	 */
	public static function filters( array $input ) {
		$result = array(
			'event'       => '',
			'actor_login' => '',
			'from'        => '',
			'until'       => '',
		);
		foreach ( $result as $key => $unused ) {
			$value = $input[ $key ] ?? '';
			if ( ! is_string( $value ) ) {
				throw new \InvalidArgumentException( 'Invalid filter.' ); }
			if ( '' === $value ) {
				continue; }
			if ( 'event' === $key ) {
				if ( ! preg_match( '/^[a-z0-9_]{1,64}$/D', $value ) ) {
					throw new \InvalidArgumentException( 'Invalid event.' ); }
				$result[ $key ] = $value;
			} elseif ( 'actor_login' === $key ) {
				if ( strlen( $value ) > 60 ) {
					throw new \InvalidArgumentException( 'Invalid actor.' ); }
				$result[ $key ] = $value;
			} else {
				$date = \DateTimeImmutable::createFromFormat( '!Y-m-d\TH:i', $value, wp_timezone() );
				if ( ! $date || $date->format( 'Y-m-d\TH:i' ) !== $value ) {
					throw new \InvalidArgumentException( 'Invalid date.' ); }
				$result[ $key ] = $date->setTimezone( new \DateTimeZone( 'UTC' ) )->format( 'Y-m-d H:i:' ) . ( 'until' === $key ? '59' : '00' );
			}
		}
		$result['actions']  = AuditEvents::selection( $input['actions'] ?? '', AuditEvents::actions() );
		$result['statuses'] = AuditEvents::selection( $input['statuses'] ?? '', AuditEvents::statuses() );
		if ( $result['actor_login'] ) {
			$user = get_user_by( 'login', $result['actor_login'] );
			if ( $user ) {
				$result['actor_id'] = (int) $user->ID; }
		}
		if ( $result['from'] && $result['until'] && $result['from'] > $result['until'] ) {
			throw new \InvalidArgumentException( 'Invalid date range.' ); }
		return $result;
	}
	/**
	 * Bind values for the fixed predicate. CSV entries come only from the fixed event catalog.
	 *
	 * @param array $filters Normalized filters.
	 * @param int   $upper Snapshot maximum ID.
	 * @param bool  $purge Exclude purge traces.
	 * @return array Bound values, never SQL fragments.
	 */
	private static function values( array $filters, $upper, $purge = false ) {
		$scoped = isset( $filters['actions'], $filters['statuses'] );
		$codes  = array();
		$other  = false;
		if ( $scoped ) {
			foreach ( AuditEvents::CATALOG as $event => $kind ) {
				if ( in_array( $kind[0], $filters['actions'], true ) && in_array( $kind[1], $filters['statuses'], true ) ) {
					$codes[] = $event;
				}
			}
			$other = in_array( 'other', $filters['actions'], true ) && in_array( 'unknown', $filters['statuses'], true );
		}
		$event = empty( $filters['event'] ) ? '' : $filters['event'];
		$from  = empty( $filters['from'] ) ? '' : $filters['from'];
		$until = empty( $filters['until'] ) ? '' : $filters['until'];
		$actor = empty( $filters['actor_login'] ) ? '' : $filters['actor_login'];
		$id    = $actor && ! empty( $filters['actor_id'] ) ? (int) $filters['actor_id'] : 0;
		$codes = implode( ',', $codes );
		return array( $upper, $event, $event, $from, $from, $until, $until, (int) $scoped, $codes, $codes, (int) $other, implode( ',', array_keys( AuditEvents::CATALOG ) ), $actor, $actor, $id, $id, (int) $purge );
	}
	/**
	 * Enforce independent audit read permission.
	 *
	 * @throws \DomainException On denial.
	 */
	public static function authorize() {
		if ( ! current_user_can( 'filewarden_view_audit' ) ) {
			throw new \DomainException( 'Access denied.' ); }
	}
	/**
	 * Read the current snapshot boundary.
	 *
	 * @return int Maximum event ID.
	 */
	public static function upper() {
		global $wpdb;
		self::authorize();
		return (int) $wpdb->get_var( "SELECT MAX(id) FROM {$wpdb->prefix}filewarden_audit_logs" );
	}
	/**
	 * Fetch a bounded descending page within the snapshot.
	 *
	 * @param array $filters Normalized filters.
	 * @param int   $upper Snapshot/cursor boundary.
	 * @param int   $limit Maximum rows.
	 * @return array Events.
	 * @throws \RuntimeException On query failure.
	 */
	public static function rows( array $filters, $upper, $limit = 51 ) {
		global $wpdb;
		self::authorize();
		$values = self::values( $filters, $upper );
		$rows   = AuditIp::available()
			? $wpdb->get_results(
				$wpdb->prepare(
					'SELECT id, created_at, event, actor_id, actor_login, object_id, actor_type, resource_type, resource_name, resource_rules, actor_ip, ip_expires_at FROM %i WHERE ' . "id <= %d
		AND (%s = '' OR event = %s)
		AND (%s = '' OR created_at >= NULLIF(%s, ''))
		AND (%s = '' OR created_at <= NULLIF(%s, ''))
		AND (%d = 0 OR ((%s <> '' AND FIND_IN_SET(CAST(event AS BINARY), %s) > 0) OR (%d = 1 AND FIND_IN_SET(CAST(event AS BINARY), %s) = 0)))
		AND (%s = '' OR actor_login = %s OR (%d > 0 AND actor_login = '' AND actor_id = %d AND actor_type NOT IN ('imported','source_system','source_user','source_visitor','source_unknown')))
		AND (%d = 0 OR event <> 'audit_purged')" . ' ORDER BY id DESC LIMIT %d',
					$wpdb->prefix . 'filewarden_audit_logs',
					$values[0],
					$values[1],
					$values[2],
					$values[3],
					$values[4],
					$values[5],
					$values[6],
					$values[7],
					$values[8],
					$values[9],
					$values[10],
					$values[11],
					$values[12],
					$values[13],
					$values[14],
					$values[15],
					$values[16],
					min( 1000, max( 1, $limit ) )
				),
				ARRAY_A
			)
			: $wpdb->get_results(
				$wpdb->prepare(
					'SELECT id, created_at, event, actor_id, actor_login, object_id, actor_type, resource_type, resource_name, resource_rules FROM %i WHERE ' . "id <= %d
		AND (%s = '' OR event = %s)
		AND (%s = '' OR created_at >= NULLIF(%s, ''))
		AND (%s = '' OR created_at <= NULLIF(%s, ''))
		AND (%d = 0 OR ((%s <> '' AND FIND_IN_SET(CAST(event AS BINARY), %s) > 0) OR (%d = 1 AND FIND_IN_SET(CAST(event AS BINARY), %s) = 0)))
		AND (%s = '' OR actor_login = %s OR (%d > 0 AND actor_login = '' AND actor_id = %d AND actor_type NOT IN ('imported','source_system','source_user','source_visitor','source_unknown')))
		AND (%d = 0 OR event <> 'audit_purged')" . ' ORDER BY id DESC LIMIT %d',
					$wpdb->prefix . 'filewarden_audit_logs',
					$values[0],
					$values[1],
					$values[2],
					$values[3],
					$values[4],
					$values[5],
					$values[6],
					$values[7],
					$values[8],
					$values[9],
					$values[10],
					$values[11],
					$values[12],
					$values[13],
					$values[14],
					$values[15],
					$values[16],
					min( 1000, max( 1, $limit ) )
				),
				ARRAY_A
			);
		if ( $wpdb->last_error ) {
			throw new \RuntimeException( 'Audit query failed.' ); }
		return $rows ?? array();
	}
	/**
	 * Describe and sign the exact purge scope for this actor.
	 *
	 * @param array $filters Filters.
	 * @param int   $upper Snapshot boundary.
	 * @return array Count and proof.
	 * @throws \DomainException On denial.
	 * @throws \RuntimeException On query failure.
	 */
	public static function review( array $filters, $upper ) {
		global $wpdb;
		self::authorize();
		if ( ! current_user_can( 'filewarden_manage_audit' ) ) {
			throw new \DomainException( 'Access denied.' ); }
		$values = self::values( $filters, $upper, true );
		$count  = (int) $wpdb->get_var(
			$wpdb->prepare(
				'SELECT COUNT(*) FROM %i WHERE ' . "id <= %d
		AND (%s = '' OR event = %s)
		AND (%s = '' OR created_at >= NULLIF(%s, ''))
		AND (%s = '' OR created_at <= NULLIF(%s, ''))
		AND (%d = 0 OR ((%s <> '' AND FIND_IN_SET(CAST(event AS BINARY), %s) > 0) OR (%d = 1 AND FIND_IN_SET(CAST(event AS BINARY), %s) = 0)))
		AND (%s = '' OR actor_login = %s OR (%d > 0 AND actor_login = '' AND actor_id = %d AND actor_type NOT IN ('imported','source_system','source_user','source_visitor','source_unknown')))
		AND (%d = 0 OR event <> 'audit_purged')",
				$wpdb->prefix . 'filewarden_audit_logs',
				$values[0],
				$values[1],
				$values[2],
				$values[3],
				$values[4],
				$values[5],
				$values[6],
				$values[7],
				$values[8],
				$values[9],
				$values[10],
				$values[11],
				$values[12],
				$values[13],
				$values[14],
				$values[15],
				$values[16]
			)
		);
		if ( $wpdb->last_error ) {
			throw new \RuntimeException( 'Audit query failed.' ); }
		return array(
			'count' => $count,
			'proof' => wp_hash( wp_json_encode( array( get_current_user_id(), $filters, $upper, $count ) ) ),
		);
	}
	/**
	 * Purge a bounded, reviewed snapshot; new events and purge traces survive.
	 *
	 * @param array  $filters Filters.
	 * @param int    $upper Snapshot boundary.
	 * @param string $proof Reviewed scope digest.
	 * @throws \InvalidArgumentException On stale review or oversized purge.
	 * @throws \RuntimeException On database failure.
	 * @throws \Throwable For rollback after audit persistence failure.
	 */
	public static function purge( array $filters, $upper, $proof ) {
		global $wpdb;
		$review = self::review( $filters, $upper );
		if ( ! hash_equals( $review['proof'], $proof ) || $review['count'] < 1 || $review['count'] > 10000 ) {
			throw new \InvalidArgumentException( 'Review the purge scope (1–10000 events).' ); }
		Repository::require_transactions();
		$lock = ( new Repository() )->lock( 'audit-purge' );
		try {
			$review = self::review( $filters, $upper );
			if ( ! hash_equals( $review['proof'], $proof ) ) {
				throw new \InvalidArgumentException( 'Review changed scope.' ); }
			if ( false === $wpdb->query( 'START TRANSACTION' ) ) {
				throw new \RuntimeException( 'Audit transaction failed.' ); }
			try {
				$values  = self::values( $filters, $upper, true );
				$deleted = $wpdb->query(
					$wpdb->prepare(
						'DELETE FROM %i WHERE ' . "id <= %d
		AND (%s = '' OR event = %s)
		AND (%s = '' OR created_at >= NULLIF(%s, ''))
		AND (%s = '' OR created_at <= NULLIF(%s, ''))
		AND (%d = 0 OR ((%s <> '' AND FIND_IN_SET(CAST(event AS BINARY), %s) > 0) OR (%d = 1 AND FIND_IN_SET(CAST(event AS BINARY), %s) = 0)))
		AND (%s = '' OR actor_login = %s OR (%d > 0 AND actor_login = '' AND actor_id = %d AND actor_type NOT IN ('imported','source_system','source_user','source_visitor','source_unknown')))
		AND (%d = 0 OR event <> 'audit_purged')",
						$wpdb->prefix . 'filewarden_audit_logs',
						$values[0],
						$values[1],
						$values[2],
						$values[3],
						$values[4],
						$values[5],
						$values[6],
						$values[7],
						$values[8],
						$values[9],
						$values[10],
						$values[11],
						$values[12],
						$values[13],
						$values[14],
						$values[15],
						$values[16]
					)
				);
				if ( false === $deleted || $deleted !== $review['count'] ) {
					throw new \RuntimeException( 'Audit purge changed; review again.' ); }
				( new AuditService() )->record( 'audit_purged', $deleted );
				if ( false === $wpdb->query( 'COMMIT' ) ) {
					throw new \RuntimeException( 'Audit commit failed.' ); }
			} catch ( \Throwable $error ) {
				$wpdb->query( 'ROLLBACK' );
				throw $error; }
		} finally {
			( new Repository() )->unlock( $lock ); }
	}
}
