<?php
/**
 * Audit actors and ACL-protected logical resource snapshots; no IPs, tokens or storage paths.
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
	 * @param array $snapshot Optional pre-deletion resource snapshot.
	 * @throws \RuntimeException When the operation cannot be completed safely.
	 * @return mixed Operation result.
	 */
	public function record( $event, $object_id = 0, array $snapshot = array() ) {
		global $wpdb;
		$actor_id    = get_current_user_id();
		$actor       = $actor_id ? get_userdata( $actor_id ) : false;
		$actor_login = $actor ? (string) $actor->user_login : '';
		$system      = ( function_exists( 'wp_doing_cron' ) && wp_doing_cron() ) || ( defined( 'WP_CLI' ) && WP_CLI );
		$type        = str_starts_with( $event, 'document_' ) || in_array( $event, array( 'download_started', 'download_head', 'preview_requested' ), true ) ? 'document' : ( str_starts_with( $event, 'folder_' ) || 'upload_requested' === $event || str_starts_with( $event, 'upload_failed_' ) ? 'folder' : '' );
		$name        = '';
		$rules       = null;
		if ( $object_id && $type ) {
			$repo     = new Repository();
			$resource = $snapshot ? $snapshot : ( 'document' === $type ? $repo->document_by_id( $object_id, true ) : $repo->folder( $object_id ) );
			if ( $resource ) {
				if ( is_string( $resource['policy'] ?? null ) ) {
					$resource['policy'] = json_decode( $resource['policy'], true ); }
				if ( is_string( $resource['retained_rules'] ?? null ) ) {
					$resource['retained_rules'] = json_decode( $resource['retained_rules'], true ); }
				try {
					$rules = wp_json_encode(
						array(
							'policies' => AccessSummary::chain( $resource, $repo ),
							'recovery' => isset( $resource['folder_id'] ) && 0 === (int) $resource['folder_id'],
						)
					);
					$name  = (string) $resource['name'];
				} catch ( \DomainException $error ) {
					$rules = null; }
			}
		}
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery -- Custom tables require fresh authorization data and prepared database access.
		$result = $wpdb->insert(
			$wpdb->prefix . 'filewarden_audit_logs',
			array(
				'actor_id'       => $actor_id,
				'actor_login'    => $actor_login,
				'actor_type'     => $actor_id ? 'user' : ( $system ? 'system' : 'visitor' ),
				'resource_type'  => $type,
				'resource_name'  => $name,
				'resource_rules' => $rules,
				'event'          => $event,
				'object_id'      => $object_id,
				'created_at'     => current_time( 'mysql', true ),
			),
			array( '%d', '%s', '%s', '%s', '%s', '%s', '%s', '%d', '%s' )
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
