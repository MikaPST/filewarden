<?php
/** Public sign-in destination, independent of document authorization.
 *
 * @package FileWarden
 */

namespace FileWarden;

/** Only settings-capable users can replace the site's public sign-in link. */
final class PublicationLogin {

	/** Site-wide option, without a document schema migration. */
	const OPTION = 'filewarden_publication_login';

	/** Validate a complete setting without fetching the destination.
	 *
	 * @param array $input Submitted setting.
	 * @return array Canonical setting.
	 * @throws \InvalidArgumentException For invalid modes or unsafe URLs.
	 */
	public static function validate( array $input ) {
		$mode = $input['mode'] ?? null;
		if ( ! in_array( $mode, array( 'wordpress', 'custom' ), true ) ) {
			throw new \InvalidArgumentException( 'Invalid login destination.' );
		}
		// phpcs:ignore WordPress.WP.CapitalPDangit.MisspelledInText -- Internal option identifier, not a product label.
		if ( 'wordpress' === $mode ) {
			return array(
				'mode' => 'wordpress',
				'url'  => '',
			);
		}
		$url = $input['url'] ?? null;
		if ( ! is_string( $url ) || strlen( $url ) > 2048 || preg_match( '/[\x00-\x20\x7f\\\\]|%0[ad0]/i', $url ) || ! filter_var( $url, FILTER_VALIDATE_URL ) ) {
			throw new \InvalidArgumentException( 'Invalid login destination.' );
		}
		// phpcs:ignore WordPress.WP.AlternativeFunctions.parse_url_parse_url -- Pure validator; FILTER_VALIDATE_URL has already required an absolute URL, without request-relative parsing.
		$parts = parse_url( $url );
		if ( ! $parts || ! in_array( strtolower( $parts['scheme'] ?? '' ), array( 'https', 'http' ), true ) || empty( $parts['host'] ) || isset( $parts['user'] ) || isset( $parts['pass'] ) ) {
			throw new \InvalidArgumentException( 'Invalid login destination.' );
		}
		return array(
			'mode' => 'custom',
			'url'  => $url,
		);
	}

	/** Return saved values, or the native WordPress behavior.
	 *
	 * @return array Setting.
	 */
	public static function current() {
		try {
			$value = get_option( self::OPTION, array( 'mode' => 'wordpress' ) );
			return is_array( $value ) ? self::validate( $value ) : self::validate( array( 'mode' => 'wordpress' ) );
		} catch ( \InvalidArgumentException $error ) {
			return self::validate( array( 'mode' => 'wordpress' ) );
		}
	}

	/** Keep native filters/return behavior; custom URLs are used literally.
	 *
	 * @param string $return_url Previously validated local return page.
	 * @return string Public sign-in destination.
	 */
	public static function destination( $return_url ) {
		$value = self::current();
		return 'custom' === $value['mode'] ? $value['url'] : wp_login_url( $return_url );
	}

	/** Save only through independently checked settings authorization.
	 *
	 * @param array        $input Submitted setting.
	 * @param AuditService $audit Existing audit service.
	 * @throws \DomainException For unauthorized writes.
	 * @throws \RuntimeException When persistence cannot be confirmed.
	 */
	public static function save( array $input, AuditService $audit ) {
		if ( ! current_user_can( 'filewarden_manage_settings' ) ) {
			throw new \DomainException( 'Access denied.' );
		}
		$value      = self::validate( $input );
		$repository = new Repository();
		$lock       = $repository->lock( 'access-rules' );
		try {
			update_option( self::OPTION, $value, false );
			if ( get_option( self::OPTION ) !== $value ) {
				throw new \RuntimeException( 'Login settings save failed.' );
			}
			$audit->record( 'settings_changed', 0 );
		} finally {
			$repository->unlock( $lock );
		}
	}
}
