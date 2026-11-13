<?php
/**
 * Minimal audit records: no filenames, IP addresses, tokens or filesystem paths.
 *
 * @package FileWarden
 */

namespace FileWarden;

/**
 * AuditService implementation.
 */
final class AuditService {
	/**
	 * Persist a minimal audit event or fail closed.
	 *
	 * @param mixed $event event value.
	 * @param mixed $object_id object id value.
	 * @throws \RuntimeException When the operation cannot be completed safely.
	 * @return mixed Operation result.
	 */
	public function record( $event, $object_id = 0 ) {
		global $wpdb;
		$actor_id    = get_current_user_id();
		$actor       = $actor_id ? get_userdata( $actor_id ) : false;
		$actor_login = $actor ? (string) $actor->user_login : '';
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery -- Custom tables require fresh authorization data and prepared database access.
		$result = $wpdb->insert(
			$wpdb->prefix . 'filewarden_audit_logs',
			array(
				'actor_id'    => $actor_id,
				'actor_login' => $actor_login,
				'event'       => $event,
				'object_id'   => $object_id,
				'created_at'  => current_time( 'mysql', true ),
			),
			array( '%d', '%s', '%s', '%d', '%s' )
		);
		if ( false === $result ) {
			throw new \RuntimeException( 'Audit unavailable.' );
		}
	}

	/**
	 * Remove one expired audit batch.
	 *
	 * @return mixed Operation result.
	 */
	public static function cleanup() {
		global $wpdb;
		$days = max( 1, (int) get_option( 'filewarden_audit_days', 365 ) );
		$end  = gmdate( 'Y-m-d H:i:s', time() - $days * DAY_IN_SECONDS );
		// Bounded, repeatable cleanup: additional batches run on subsequent cron ticks.
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery -- Custom tables require fresh authorization data and prepared database access.
		$wpdb->query( $wpdb->prepare( "DELETE FROM {$wpdb->prefix}filewarden_audit_logs WHERE created_at < %s LIMIT 10000", $end ) );
	}

	/**
	 * Return recent audit events to authorized users.
	 *
	 * @return mixed Operation result.
	 */
	public function recent() {
		global $wpdb;
		if ( ! current_user_can( 'filewarden_view_audit' ) ) {
			return array();
		}
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery -- Custom tables require fresh authorization data and prepared database access.
		return $wpdb->get_results( "SELECT actor_id, actor_login, event, object_id, created_at FROM {$wpdb->prefix}filewarden_audit_logs ORDER BY id DESC LIMIT 50", ARRAY_A ) ?? array();
	}
}
