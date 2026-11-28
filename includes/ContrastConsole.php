<?php
/** Text contrast report, shared by progressive HTML and the draft editor.
 *
 * @package FileWarden
 */

namespace FileWarden;

/** Present actual text pairs rather than a whole-site accessibility claim. */
final class ContrastConsole {
	/** Localized report configuration.
	 *
	 * @return array Configuration.
	 */
	public static function configuration() {
		return array(
			'pairs'      => FrontendAppearance::contrast_pairs(),
			'labels'     => array(
				__( 'Main text / interface background', 'filewarden' ),
				__( 'Main text / cards', 'filewarden' ),
				__( 'Button text / button', 'filewarden' ),
				__( 'Button text / hover', 'filewarden' ),
				__( 'Links / cards', 'filewarden' ),
				__( 'Links / interface background', 'filewarden' ),
				__( 'Secondary text / cards', 'filewarden' ),
				__( 'Secondary text / interface background', 'filewarden' ),
			),
			'aa'         => __( 'Good contrast — AA', 'filewarden' ),
			'aaa'        => __( 'Enhanced contrast — AAA', 'filewarden' ),
			'improve'    => __( 'Needs improvement', 'filewarden' ),
			'incomplete' => __( 'Incomplete — score not calculated', 'filewarden' ),
			'compliant'  => __( 'text contrasts pass', 'filewarden' ),
		);
	}

	/** Server-render the saved palette; retain useful results without JavaScript.
	 *
	 * @param array $colors Palette.
	 */
	public static function render( array $colors ) {
		$config = self::configuration();
		$ratios = array_values( FrontendAppearance::contrast_report( $colors ) );
		$count  = count( array_filter( $ratios, static fn( $ratio ) => null !== $ratio && $ratio >= 4.5 ) );
		$level  = count( $ratios ) === $count ? ( min( $ratios ) >= 7 ? 'aaa' : 'aa' ) : 'improve';
		if ( in_array( null, $ratios, true ) ) {
			$level = 'incomplete';
		}
		echo '<section class="fw-contrast-report" data-level="' . esc_attr( $level ) . '"><h4><span class="fw-action-icon fw-icon-shield-check" aria-hidden="true"></span>' . esc_html__( 'Text readability', 'filewarden' ) . '</h4><p data-contrast-summary role="status">' . esc_html( $config[ $level ] . ( 'incomplete' === $level ? '' : ' · ' . $count . '/' . count( $ratios ) . ' ' . $config['compliant'] ) ) . '</p><div class="fw-contrast-segments" aria-hidden="true">';
		foreach ( $ratios as $ratio ) {
			echo '<span data-pass="' . ( null !== $ratio && $ratio >= 4.5 ? 'true' : 'false' ) . '"></span>';
		}
		echo '</div><details><summary>' . esc_html__( 'View contrast details', 'filewarden' ) . '</summary><ul>';
		foreach ( $ratios as $index => $ratio ) {
			echo '<li><span>' . esc_html( $config['labels'][ $index ] ) . '</span> <strong data-contrast-ratio>' . esc_html( null === $ratio ? $config['incomplete'] : number_format_i18n( $ratio, 2 ) . ':1 · ' . ( $ratio >= 7 ? 'AAA' : ( $ratio >= 4.5 ? 'AA' : $config['improve'] ) ) ) . '</strong></li>';
		}
		echo '</ul></details><p class="description">' . esc_html__( 'These text contrasts do not certify the accessibility of the whole site.', 'filewarden' ) . '</p></section>';
	}
}
