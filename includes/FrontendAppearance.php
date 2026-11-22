<?php
/** Validated, site-wide appearance; never participates in authorization.
 *
 * @package FileWarden
 */

namespace FileWarden;

/** Store presentation values, not arbitrary CSS, HTML or remote asset URLs. */
final class FrontendAppearance {
	/** Public appearance option. */
	const OPTION = 'filewarden_frontend_appearance';

	/** Return the approved initial palettes.
	 *
	 * @return array Preset colors.
	 */
	public static function presets() {
		return array(
			'filewarden' => array(
				'background'  => '#f4f7fb',
				'surface'     => '#ffffff',
				'text'        => '#1c2942',
				'button'      => '#1c2942',
				'button_text' => '#ffffff',
				'link'        => '#3b556d',
				'hover'       => '#3b556d',
				'muted'       => '#53667f',
				'border'      => '#dce4ee',
				'accent'      => '#5fc2ba',
			),
			'neutral'    => array(
				'background'  => '#f7f7f8',
				'surface'     => '#ffffff',
				'text'        => '#253044',
				'button'      => '#334155',
				'button_text' => '#ffffff',
				'link'        => '#475569',
				'hover'       => '#253044',
				'muted'       => '#576475',
				'border'      => '#dce1e7',
				'accent'      => '#94a3b8',
			),
			'nature'     => array(
				'background'  => '#edf3ee',
				'surface'     => '#ffffff',
				'text'        => '#203e34',
				'button'      => '#285749',
				'button_text' => '#ffffff',
				'link'        => '#2f6553',
				'hover'       => '#1e4438',
				'muted'       => '#526b5e',
				'border'      => '#d5e3d9',
				'accent'      => '#a7cfb6',
			),
		);
	}

	/** Safe defaults, also used after deleting a custom logo.
	 *
	 * @return array Appearance.
	 */
	public static function defaults() {
		return array(
			'version'   => 1,
			'preset'    => 'filewarden',
			'colors'    => self::presets()['filewarden'],
			'name'      => 'FILEWARDEN',
			'logo_id'   => 0,
			'show_logo' => true,
			'show_name' => true,
		);
	}

	/** Validate complete stored or submitted values before any persistence.
	 *
	 * @param array $input Appearance values.
	 * @return array Canonical appearance.
	 * @throws \InvalidArgumentException For invalid fields or inaccessible text.
	 */
	public static function validate( array $input ) {
		$result = self::defaults();
		if ( ! is_string( $input['preset'] ?? null ) || ! in_array( $input['preset'], array( 'filewarden', 'neutral', 'nature', 'custom' ), true ) ) {
			throw new \InvalidArgumentException( 'Invalid appearance.' );
		}
		$result['preset'] = $input['preset'];
		foreach ( $result['colors'] as $key => $fallback ) {
			$value = $input['colors'][ $key ] ?? null;
			if ( ! is_string( $value ) || ! preg_match( '/^#[a-f0-9]{6}$/iD', $value ) ) {
				throw new \InvalidArgumentException( 'Invalid appearance color.' );
			}
			$result['colors'][ $key ] = strtolower( $value );
		}
		if ( 'custom' !== $result['preset'] && self::presets()[ $result['preset'] ] !== $result['colors'] ) {
			$result['preset'] = 'custom';
		}
		if ( ! is_string( $input['name'] ?? null ) || ! preg_match( '/^[^<>\x00-\x1f]{0,64}$/uD', $input['name'] ) ) {
			throw new \InvalidArgumentException( 'Invalid public name.' );
		}
		$result['name']    = trim( $input['name'] );
		$result['logo_id'] = RestController::integer( $input['logo_id'] ?? 0 );
		foreach ( array( 'show_logo', 'show_name' ) as $key ) {
			$value = $input[ $key ] ?? null;
			if ( ! in_array( $value, array( true, false, '0', '1', 0, 1 ), true ) ) {
				throw new \InvalidArgumentException( 'Invalid appearance visibility.' );
			}
			$result[ $key ] = in_array( $value, array( true, '1', 1 ), true );
		}
		if ( $result['show_name'] && '' === $result['name'] ) {
			throw new \InvalidArgumentException( 'Invalid public name.' );
		}
		if ( self::contrast_failures( $result['colors'] ) ) {
			throw new \InvalidArgumentException( 'Insufficient appearance contrast.' );
		}
		return $result;
	}

	/** Relative luminance for strict six-digit colors.
	 *
	 * @param string $hex Canonical color.
	 * @return float Luminance.
	 */
	private static function luminance( $hex ) {
		$channels = array();
		foreach ( array( 1, 3, 5 ) as $offset ) {
			$value      = hexdec( substr( $hex, $offset, 2 ) ) / 255;
			$channels[] = $value <= 0.04045 ? $value / 12.92 : ( ( $value + 0.055 ) / 1.055 ) ** 2.4;
		}
		return 0.2126 * $channels[0] + 0.7152 * $channels[1] + 0.0722 * $channels[2];
	}

	/** Check every small-text pairing used by the public interface.
	 *
	 * @param array $colors Canonical colors.
	 * @return array Failing pairs, never a full accessibility certification.
	 */
	public static function contrast_failures( array $colors ) {
		$failures = array();
		foreach ( array( array( 'text', 'background' ), array( 'text', 'surface' ), array( 'button_text', 'button' ), array( 'button_text', 'hover' ), array( 'link', 'surface' ), array( 'link', 'background' ), array( 'muted', 'surface' ), array( 'muted', 'background' ) ) as $pair ) {
			$a = self::luminance( $colors[ $pair[0] ] );
			$b = self::luminance( $colors[ $pair[1] ] );
			if ( ( max( $a, $b ) + 0.05 ) / ( min( $a, $b ) + 0.05 ) < 4.5 ) {
				$failures[] = implode( '/', $pair );
			}
		}
		return $failures;
	}

	/** Read persistent values; malformed stored CSS never reaches a response.
	 *
	 * @return array Safe appearance.
	 */
	public static function current() {
		$input = get_option( self::OPTION, self::defaults() );
		try {
			return is_array( $input ) ? self::validate( $input ) : self::defaults();
		} catch ( \InvalidArgumentException $error ) {
			return self::defaults();
		}
	}

	/** Accept only local, genuinely decoded PNG/JPEG public WordPress media.
	 *
	 * @param int  $id Attachment ID.
	 * @param bool $authorize Whether this is a new privileged selection.
	 * @return string Validated public image URL or the official logo.
	 * @throws \InvalidArgumentException For unsuitable or unavailable media.
	 * @throws \DomainException For unauthorized media selection.
	 */
	public static function logo_url( $id = 0, $authorize = false ) {
		if ( ! $id ) {
			return plugins_url( 'assets/branding/filewarden-logo-512.png', FILEWARDEN_PATH . 'filewarden.php' );
		}
		if ( $authorize && ( ! current_user_can( 'upload_files' ) || ! current_user_can( 'edit_post', $id ) ) ) {
			throw new \DomainException( 'Access denied.' );
		}
		$path    = get_attached_file( $id );
		$uploads = wp_upload_dir();
		$base    = realpath( $uploads['basedir'] );
		$path    = is_string( $path ) ? realpath( $path ) : false;
		if ( ! $path || ! $base || ! str_starts_with( wp_normalize_path( $path ), trailingslashit( wp_normalize_path( $base ) ) ) || ! is_readable( $path ) || filesize( $path ) > 2 * MB_IN_BYTES ) {
			throw new \InvalidArgumentException( 'Invalid public logo.' );
		}
		$extension = strtolower( pathinfo( $path, PATHINFO_EXTENSION ) );
		$image     = wp_getimagesize( $path );
		$mime      = get_post_mime_type( $id );
		$url       = wp_get_attachment_url( $id );
		$origin    = static function ( $value ) {
			$parts = wp_parse_url( $value );
			return array( $parts['scheme'] ?? '', $parts['host'] ?? '', $parts['port'] ?? ( 'https' === ( $parts['scheme'] ?? '' ) ? 443 : 80 ) );
		};
		if ( 'attachment' !== get_post_type( $id ) || ! in_array( $extension, array( 'png', 'jpg', 'jpeg' ), true ) || ! $image || ! in_array( $image['mime'] ?? '', array( 'image/png', 'image/jpeg' ), true ) || $image['mime'] !== $mime || $image[0] > 6000 || $image[1] > 6000 || ( 'png' === $extension ? 'image/png' : 'image/jpeg' ) !== $mime || ! $url || $origin( $url ) !== $origin( home_url() ) ) {
			throw new \InvalidArgumentException( 'Invalid public logo.' );
		}
		return $url;
	}

	/** Privileged, audited persistence, serialized with maintenance-sensitive writes.
	 *
	 * @param array        $input Submitted appearance.
	 * @param AuditService $audit Existing audit service.
	 * @throws \DomainException For unavailable settings capability.
	 * @throws \RuntimeException When saving is not confirmed.
	 */
	public static function save( array $input, AuditService $audit ) {
		if ( ! current_user_can( 'filewarden_manage_settings' ) ) {
			throw new \DomainException( 'Access denied.' );
		}
		$value = self::validate( $input );
		self::logo_url( $value['logo_id'], self::current()['logo_id'] !== $value['logo_id'] );
		$repository = new Repository();
		$lock       = $repository->lock( 'access-rules' );
		try {
			update_option( self::OPTION, $value, false );
			if ( get_option( self::OPTION ) !== $value ) {
				throw new \RuntimeException( 'Appearance save failed.' );
			}
			$audit->record( 'settings_changed', 0 );
		} finally {
			$repository->unlock( $lock );
		}
	}

	/** Per-response CSP nonce; no unsafe-inline permission is added.
	 *
	 * @return string Nonce.
	 */
	public static function nonce() {
		static $nonce;
		$nonce ??= bin2hex( random_bytes( 24 ) );
		return $nonce;
	}

	/** Emit only whitelisted CSS variables in a nonced standalone frame.
	 *
	 * @return string Trusted style markup.
	 */
	public static function style() {
		$css = 'body.fw-public-theme{';
		foreach ( self::current()['colors'] as $key => $value ) {
			$css .= '--fw-public-' . str_replace( '_', '-', $key ) . ':' . $value . ';';
		}
		return '<style id="fw-public-appearance" nonce="' . esc_attr( self::nonce() ) . '">' . $css . '}</style>';
	}

	/** Identify public stylesheet contents when replacing a same-version ZIP.
	 *
	 * @param string $name Allowlisted public stylesheet name.
	 * @return string Plugin-owned URL with a content digest.
	 * @throws \InvalidArgumentException For an unknown stylesheet.
	 */
	public static function stylesheet_url( $name ) {
		if ( ! in_array( $name, array( 'publication', 'viewer' ), true ) ) {
			throw new \InvalidArgumentException( 'Invalid public stylesheet.' );
		}
		$file    = 'assets/' . $name . '.css';
		$hash    = hash_file( 'sha256', FILEWARDEN_PATH . $file );
		$version = FILEWARDEN_VERSION . '-' . substr( $hash ? $hash : 'unavailable', 0, 12 );
		return add_query_arg( 'ver', $version, plugins_url( $file, FILEWARDEN_PATH . 'filewarden.php' ) );
	}

	/** Public identity has no document metadata or authorization side effects.
	 *
	 * @return string Escaped identity markup.
	 */
	public static function brand() {
		$value = self::current();
		$html  = '<div class="fw-public-brand">';
		if ( $value['show_logo'] ) {
			try {
				$logo = self::logo_url( $value['logo_id'] );
			} catch ( \Throwable $error ) {
				$logo = self::logo_url();
			}
			$html .= '<img class="fw-public-logo" src="' . esc_url( $logo ) . '" width="38" height="38" alt="">';
		}
		if ( $value['show_name'] ) {
			$html .= '<span class="eyebrow fw-public-name">' . esc_html( $value['name'] ) . '</span>';
		}
		return $html . '</div>';
	}
}
