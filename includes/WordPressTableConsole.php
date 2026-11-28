<?php
/** Explicit shared-table conversion controls; never nested in a mutation form.
 *
 * @package FileWarden
 */

namespace FileWarden;

/** A helper action is not a settings-save side effect. */
final class WordPressTableConsole {
	/** Show only an anomaly or a pending conversion, with administrator-only actions. */
	public static function render() {
		try {
			$status = WordPressTables::status( false );
			$ready  = ! array_diff( $status['engines'], array( 'innodb' ) ); } catch ( \Throwable $error ) {
			$status = array( 'engines' => array() );
			$ready  = false; }
			if ( $ready && ! get_option( WordPressTables::CHECKPOINT ) ) {
				return; }
			echo '<section id="fw-wp-table-conversion" class="fw-wp-conversion" tabindex="-1"><h3>' . esc_html__( 'Prepare WordPress tables for restoration', 'filewarden' ) . '</h3><p>' . esc_html__( 'Restoration needs transactional options, posts and postmeta tables. This voluntary helper converts only these three site tables, never your whole database.', 'filewarden' ) . '</p><ul>';
			foreach ( $status['engines'] as $logical => $engine ) {
				echo '<li><code>' . esc_html( $logical ) . '</code> — ' . esc_html( $engine ) . '</li>'; }
			echo '</ul>';
			if ( ! current_user_can( 'manage_options' ) ) {
				echo '<p>' . esc_html__( 'A WordPress administrator must prepare these shared tables.', 'filewarden' ) . '</p></section>';
				return; }
			echo '<p class="description">' . esc_html__( 'Before conversion, keep an independent full database backup and stop site writes, scheduled tasks and external writers. Each table is verified separately. A conversion can lock WordPress and cannot be rolled back as one transaction.', 'filewarden' ) . '</p><p class="description">' . esc_html__( 'The interactive helper is limited to 200,000 rows and 64 MiB per table. Larger tables, unavailable SQL permissions or unverifiable data require your host. SQL disk space may need confirmation by your host.', 'filewarden' ) . '</p><button type="button" class="button" data-wp-table-check>' . esc_html__( 'Check conversion prerequisites', 'filewarden' ) . '</button><div data-wp-table-review hidden><p data-wp-table-capacity></p><label><input type="checkbox" data-wp-table-backup> ' . esc_html__( 'I have an independent complete database backup available for recovery.', 'filewarden' ) . '</label><label><input type="checkbox" data-wp-table-maintenance> ' . esc_html__( 'Site writes, scheduled tasks and external database writers are stopped for maintenance.', 'filewarden' ) . '</label><label data-wp-table-disk-label><input type="checkbox" data-wp-table-disk> ' . esc_html__( 'My host has confirmed enough free space on the database server for this conversion.', 'filewarden' ) . '</label><button type="button" class="button button-primary" data-wp-table-convert disabled>' . esc_html__( 'Convert / resume the affected tables', 'filewarden' ) . '</button></div><p role="status" aria-live="polite" data-wp-table-status></p><a class="button" href="#fw-settings-diagnostic" data-wp-table-recheck hidden>' . esc_html__( 'Run diagnostic again', 'filewarden' ) . '</a><noscript><p>' . esc_html__( 'Enable JavaScript to use the guided converter, or contact your host.', 'filewarden' ) . '</p></noscript></section>';
	}
	/** Local script with safe messages and its own nonce. */
	public static function assets() {
		wp_enqueue_script( 'filewarden-wp-tables', plugins_url( 'assets/wordpress-tables.js', FILEWARDEN_PATH . 'filewarden.php' ), array( 'filewarden-settings' ), FILEWARDEN_VERSION . '-' . substr( hash_file( 'sha256', FILEWARDEN_PATH . 'assets/wordpress-tables.js' ), 0, 12 ), true );
		wp_localize_script(
			'filewarden-wp-tables',
			'filewardenWordPressTables',
			array(
				'endpoint' => admin_url( 'admin-ajax.php' ),
				'nonce'    => wp_create_nonce( 'filewarden_wp_tables' ),
				'working'  => __( 'Checking or converting the next table. Keep maintenance active.', 'filewarden' ),
				'measured' => __( 'SQL locking passed and measured database disk space covers the current estimate. ALTER permission is checked by the server during conversion.', 'filewarden' ),
				'unknown'  => __( 'SQL locking passed, but database disk space cannot be measured here. Confirm available space with your host before continuing.', 'filewarden' ),
				/* translators: %d: verified shared tables. */
				'progress' => __( '%d of 3 WordPress tables verified.', 'filewarden' ),
				'done'     => __( 'The three WordPress tables are verified as InnoDB. Run diagnostics again before restoring.', 'filewarden' ),
				'error'    => __( 'Conversion could not complete. Keep maintenance active and your independent backup. Check SQL permissions, database disk space and table size with your host; review and resume if the data is unchanged. No automatic rollback was attempted.', 'filewarden' ),
			)
		);
	}
}
