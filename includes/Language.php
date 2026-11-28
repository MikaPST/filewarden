<?php
/** Plugin-only language selection, independent of the WordPress interface locale.
 *
 * @package FileWarden
 */

namespace FileWarden;

/** Keep locale choices extensible without changing another translation domain. */
final class Language {

	const LANGUAGES = array(
		'fr_FR' => 'Français',
		'en_US' => 'English',
	);

	/** Locale identifiers are a fixed registry, never paths supplied by a request.
	 *
	 * @return array Allowed settings values.
	 */
	public static function choices() {
		return array_merge( array( 'auto' ), array_keys( self::LANGUAGES ) );
	}

	/** Read a validated persistent choice.
	 *
	 * @return string Selected locale or auto.
	 */
	public static function choice() {
		$value = get_option( 'filewarden_language', 'auto' );
		return in_array( $value, self::choices(), true ) ? $value : 'auto';
	}

	/** Resolve the site language, never the administrator's personal language.
	 *
	 * @return string Available catalog locale.
	 */
	public static function locale() {
		// WordPress may switch the request locale to the user's profile in administration.
		return self::resolve( get_option( 'WPLANG', 'en_US' ), self::choice() );
	}

	/** Resolve a choice against the supported catalogs, including regional variants.
	 *
	 * @param mixed  $site Site locale, independent of the user profile.
	 * @param string $choice Explicit choice or auto.
	 * @return string Supported locale, English on an unknown language.
	 */
	public static function resolve( $site, $choice = 'auto' ) {
		if ( is_string( $choice ) && isset( self::LANGUAGES[ $choice ] ) ) {
			return $choice;
		}
		if ( is_string( $site ) ) {
			$base = strtolower( explode( '_', str_replace( '-', '_', $site ) )[0] );
			foreach ( self::LANGUAGES as $locale => $name ) {
				if ( strtolower( explode( '_', $locale )[0] ) === $base ) {
					return $locale;
				}
			}
		}
		return 'en_US';
	}

	/** Plugin text language for assistive technology.
	 *
	 * @return string BCP 47 language.
	 */
	public static function html() {
		return str_replace( '_', '-', self::locale() );
	}

	/** Redirect only FileWarden's catalog, including WordPress JIT loading.
	 *
	 * @param string $file Requested translation file.
	 * @param string $domain Translation domain.
	 * @return string Translation file, unchanged for other plugins/core.
	 */
	public static function catalog( $file, $domain ) {
		return 'filewarden' === $domain ? FILEWARDEN_PATH . 'languages/filewarden-' . self::locale() . '.mo' : $file;
	}

	/** Register at init, before translating the plugin UI.
	 */
	public static function register() {
		add_filter( 'load_textdomain_mofile', array( self::class, 'catalog' ), 20, 2 );
		add_filter( 'load_translation_file', array( self::class, 'catalog' ), 20, 2 );
		// Explicit loading preserves the independent choice and bundled fallback outside the directory.
		load_textdomain( 'filewarden', FILEWARDEN_PATH . 'languages/filewarden-' . self::locale() . '.mo' );
	}
}
