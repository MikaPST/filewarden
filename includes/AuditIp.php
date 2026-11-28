<?php
/** Opt-in, expiring audit IPs with explicit trusted-proxy boundaries.
 *
 * @package FileWarden
 */

namespace FileWarden;

/** Never trust client forwarding headers without a server-configured proxy. */
final class AuditIp {
	/** Events whose network origin helps investigate sensitive operations. */
	const EVENTS = array( 'access_denied', 'document_policy_changed', 'folder_policy_changed', 'document_purged', 'folder_deleted_files_recovered', 'settings_changed', 'all_file_types_enabled', 'all_file_types_disabled', 'backup_requested', 'backup_created', 'backup_download_started', 'backup_analysis_requested', 'backup_restore_requested', 'backup_restored', 'backup_failed', 'backup_restore_failed', 'audit_export_requested', 'audit_purged', 'database_repair_requested', 'database_repaired', 'wordpress_tables_converted' );

	/** Test an IP against an explicitly configured exact address or CIDR.
	 *
	 * @param string $ip Address.
	 * @param string $network Trusted address/network.
	 * @return bool Trusted.
	 */
	public static function trusted( $ip, $network ) {
		if ( ! is_string( $ip ) || ! is_string( $network ) ) {
			return false;
		}
		$parts = explode( '/', $network );
		if ( count( $parts ) > 2 || ! filter_var( $ip, FILTER_VALIDATE_IP ) || ! filter_var( $parts[0], FILTER_VALIDATE_IP ) ) {
			return false;
		}
		$address = inet_pton( $ip );
		$base    = inet_pton( $parts[0] );
		if ( strlen( $address ) !== strlen( $base ) ) {
			return false;
		}
		$maximum = strlen( $address ) * 8;
		$bits    = $parts[1] ?? (string) $maximum;
		if ( ! preg_match( '/^(0|[1-9][0-9]{0,2})$/D', $bits ) || (int) $bits > $maximum ) {
			return false;
		}
		$whole = intdiv( (int) $bits, 8 );
		$rest  = (int) $bits % 8;
		return substr( $address, 0, $whole ) === substr( $base, 0, $whole ) && ( 0 === $rest || ( ord( $address[ $whole ] ) & ( 255 << ( 8 - $rest ) ) ) === ( ord( $base[ $whole ] ) & ( 255 << ( 8 - $rest ) ) ) );
	}

	/** Resolve the first untrusted hop from the server backwards.
	 *
	 * @param mixed $remote Server REMOTE_ADDR.
	 * @param mixed $forwarded X-Forwarded-For, ignored by default.
	 * @param array $proxies Explicit server configuration.
	 * @return string Canonical address or empty.
	 */
	public static function resolve( $remote, $forwarded, array $proxies = array() ) {
		if ( ! is_string( $remote ) || ! filter_var( $remote, FILTER_VALIDATE_IP ) ) {
			return '';
		}
		$remote     = inet_ntop( inet_pton( $remote ) );
		$is_trusted = static function ( $ip ) use ( $proxies ) {
			foreach ( array_slice( $proxies, 0, 64 ) as $proxy ) {
				if ( self::trusted( $ip, $proxy ) ) {
					return true;
				}
			}
			return false;
		};
		if ( ! $is_trusted( $remote ) || ! is_string( $forwarded ) || strlen( $forwarded ) > 4096 ) {
			return $remote;
		}
		$chain = array_map( 'trim', explode( ',', $forwarded ) );
		if ( count( $chain ) > 16 ) {
			return $remote;
		}
		foreach ( $chain as $ip ) {
			if ( ! filter_var( $ip, FILTER_VALIDATE_IP ) ) {
				return $remote;
			}
		}
		for ( $index = count( $chain ) - 1; $index >= 0; --$index ) {
			if ( ! $is_trusted( $chain[ $index ] ) ) {
				return inet_ntop( inet_pton( $chain[ $index ] ) );
			}
		}
		return $remote;
	}

	/** Columns exist only after the repeatable additive migration.
	 *
	 * @return bool Available.
	 */
	public static function available() {
		return in_array( get_option( 'filewarden_schema' ), array( '7', '8', '9' ), true );
	}

	/** Retain addresses no longer than the audit rows themselves.
	 *
	 * @return int Days.
	 */
	public static function days() {
		return min( max( 1, (int) get_option( 'filewarden_audit_days', 365 ) ), max( 1, min( 3650, (int) get_option( 'filewarden_audit_ip_days', 30 ) ) ) );
	}

	/** Capture only opted-in HTTP events, never fabricate an origin for CLI/cron.
	 *
	 * @param string $event Code.
	 * @param bool   $system Background execution.
	 * @return string Validated IP.
	 */
	public static function capture( $event, $system ) {
		if ( $system || '1' !== get_option( 'filewarden_audit_collect_ip', '0' ) || ! in_array( $event, self::EVENTS, true ) ) {
			return '';
		}
		// phpcs:ignore WordPress.Security.ValidatedSanitizedInput -- Raw server/header values are validated as literal IPs; no HTML or SQL is emitted here.
		return self::resolve( $_SERVER['REMOTE_ADDR'] ?? '', $_SERVER['HTTP_X_FORWARDED_FOR'] ?? '', defined( 'FILEWARDEN_TRUSTED_PROXIES' ) && is_array( FILEWARDEN_TRUSTED_PROXIES ) ? FILEWARDEN_TRUSTED_PROXIES : array() );
	}

	/** Hide expired or malformed IPs immediately, even before scheduled erasure.
	 *
	 * @param array $row Stored/imported event.
	 * @return string Nonexpired IP or empty.
	 */
	public static function visible( array $row ) {
		$ip      = $row['actor_ip'] ?? '';
		$created = strtotime( ( $row['created_at'] ?? '' ) . ' UTC' );
		$expires = strtotime( ( $row['ip_expires_at'] ?? '' ) . ' UTC' );
		if ( ! is_string( $ip ) || ! filter_var( $ip, FILTER_VALIDATE_IP ) || ! $created || ! $expires || min( $expires, $created + self::days() * DAY_IN_SECONDS ) <= time() ) {
			return '';
		}
		return $ip;
	}
}
