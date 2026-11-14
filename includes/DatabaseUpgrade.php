<?php
/** Explicit, resumable engine repair; ALTER TABLE is never described as rollback-safe.
 *
 * @package FileWarden
 */

namespace FileWarden;

/** Convert only the three plugin tables, after operator backup confirmation. */
final class DatabaseUpgrade {
	const CHECKPOINT = 'filewarden_database_upgrade';
	/** Current connection owns the repair operation.
	 *
	 * @var bool
	 */
	private static $running = false;

	/** Permit only the repair connection while a checkpoint exists.
	 *
	 * @throws \RuntimeException While recovery requires operator attention.
	 */
	public static function guard() {
		// A request may have cached an absent checkpoint before waiting on the write gate.
		wp_cache_delete( self::CHECKPOINT, 'options' );
		wp_cache_delete( 'notoptions', 'options' );
		if ( ! self::$running && get_option( self::CHECKPOINT ) ) {
			throw new \RuntimeException( 'Database repair pending.' ); }
	}

	/** Inspect exact local tables; no server defaults are used as evidence.
	 *
	 * @return array State and allowlisted engines.
	 */
	public static function status() {
		global $wpdb;
		$tables   = array_map( static fn( $kind ) => $wpdb->prefix . 'filewarden_' . $kind, array( 'documents', 'folders', 'audit_logs' ) );
		$previous = $wpdb->suppress_errors( true );
		try {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery -- Exact local table metadata, without wildcard names.
			$rows = $wpdb->get_results( $wpdb->prepare( 'SHOW TABLE STATUS WHERE Name IN (%s, %s, %s)', $tables[0], $tables[1], $tables[2] ), ARRAY_A );
			if ( $wpdb->last_error || ! is_array( $rows ) ) {
				return array(
					'state'   => 'unavailable',
					'engines' => array(),
				); }
			$engines = array();
			foreach ( $rows as $row ) {
				if ( in_array( $row['Name'] ?? '', $tables, true ) && is_string( $row['Engine'] ?? null ) ) {
					$engines[ $row['Name'] ] = strtolower( $row['Engine'] ); }
			}
			if ( count( $engines ) !== 3 ) {
				return array(
					'state'   => 'missing',
					'engines' => $engines,
				); }
			$state = array_diff( $engines, array( 'innodb' ) ) ? 'incompatible' : 'ready';
			if ( array_diff( $engines, array( 'innodb', 'myisam' ) ) ) {
				$state = 'unsupported'; }
			ksort( $engines );
			return array(
				'state'   => $state,
				'engines' => $engines,
			);
		} finally {
			$wpdb->suppress_errors( $previous );
		}
	}

	/** Hash exact rows, column definitions and indexes in bounded batches.
	 *
	 * @param string $table Allowlisted plugin table.
	 * @return array Integrity checkpoint, without document or actor contents.
	 * @throws \RuntimeException On an unreadable snapshot.
	 */
	private static function snapshot( $table ) {
		global $wpdb;
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery -- Prepared identifier from the fixed local table set.
		$columns = $wpdb->get_results( $wpdb->prepare( 'SHOW FULL COLUMNS FROM %i', $table ), ARRAY_A );
		if ( $wpdb->last_error || ! $columns ) {
			throw new \RuntimeException( 'Database verification failed.' ); }
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery -- Preserve collation and the next generated ID, in addition to exact records.
		$metadata = $wpdb->get_row( $wpdb->prepare( 'SHOW TABLE STATUS WHERE Name = %s', $table ), ARRAY_A );
		if ( $wpdb->last_error || ! $metadata ) {
			throw new \RuntimeException( 'Database verification failed.' ); }
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery -- Compare logical index definitions, never estimated cardinality.
		$indexes = $wpdb->get_results( $wpdb->prepare( 'SHOW INDEX FROM %i', $table ), ARRAY_A );
		if ( $wpdb->last_error || ! $indexes ) {
			throw new \RuntimeException( 'Database verification failed.' ); }
		$indexes = array_map( static fn( $row ) => array_intersect_key( $row, array_flip( array( 'Key_name', 'Non_unique', 'Seq_in_index', 'Column_name', 'Sub_part', 'Index_type', 'Collation', 'Null' ) ) ), $indexes );
		usort( $indexes, static fn( $a, $b ) => array( $a['Key_name'], (int) $a['Seq_in_index'] ) <=> array( $b['Key_name'], (int) $b['Seq_in_index'] ) );
		$hash  = hash_init( 'sha256' );
		$last  = null;
		$count = 0;
		do {
			$query = null === $last ? $wpdb->prepare( 'SELECT * FROM %i ORDER BY id LIMIT 200', $table ) : $wpdb->prepare( 'SELECT * FROM %i WHERE id > CAST(%s AS UNSIGNED) ORDER BY id LIMIT 200', $table, $last );
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery,WordPress.DB.PreparedSQL.NotPrepared -- Both branches above use prepared identifiers/values; preserve unsigned IDs without PHP integer truncation.
			$rows = $wpdb->get_results( $query, ARRAY_A );
			if ( $wpdb->last_error || ! is_array( $rows ) ) {
				throw new \RuntimeException( 'Database verification failed.' ); }
			foreach ( $rows as $row ) {
				$json = wp_json_encode( $row );
				if ( false === $json ) {
					throw new \RuntimeException( 'Database verification failed.' ); }
				hash_update( $hash, strlen( $json ) . ':' . $json );
				$last = (string) $row['id'];
				++$count;
			}
			$batch_count = count( $rows );
		} while ( 200 === $batch_count );
		return array(
			'rows'    => $count,
			'content' => hash_final( $hash ),
			'columns' => hash( 'sha256', wp_json_encode( $columns ) ),
			'indexes' => hash( 'sha256', wp_json_encode( $indexes ) ),
			'table'   => hash( 'sha256', wp_json_encode( array( $metadata['Collation'], $metadata['Auto_increment'] ) ) ),
		);
	}

	/** Run an explicit repair, retaining the checkpoint on interruption or failure.
	 *
	 * @param bool $backup Operator confirmed database/key/storage backup and maintenance.
	 * @throws \DomainException Without settings capability.
	 * @throws \InvalidArgumentException Without explicit confirmation.
	 * @throws \RuntimeException On failed conversion or verification.
	 */
	public static function repair( $backup ) {
		global $wpdb;
		if ( ! current_user_can( 'filewarden_manage_settings' ) ) {
			throw new \DomainException( 'Access denied.' ); }
		if ( true !== $backup ) {
			throw new \InvalidArgumentException( 'Confirmation required.' ); }
		$repo          = new Repository();
		$lock          = null;
		$previous      = $wpdb->suppress_errors( true );
		self::$running = true;
		try {
			$lock   = $repo->lock( 'database-repair' );
			$status = self::status();
			if ( ! in_array( $status['state'], array( 'ready', 'incompatible' ), true ) ) {
				throw new \RuntimeException( 'Database verification failed.' ); }
			$checkpoint = get_option( self::CHECKPOINT );
			$repairing  = $checkpoint || 'incompatible' === $status['state'];
			if ( $repairing && ! $checkpoint ) {
				( new AuditService() )->record( 'database_repair_requested' ); }
			$before = array();
			foreach ( $status['engines'] as $table => $engine ) {
				$before[ $table ] = self::snapshot( $table ); }
			if ( $checkpoint && $checkpoint !== $before ) {
				throw new \RuntimeException( 'Database verification failed.' ); }
			update_option( self::CHECKPOINT, $before, false );
			if ( get_option( self::CHECKPOINT ) !== $before ) {
				throw new \RuntimeException( 'Database verification failed.' ); }
			foreach ( $status['engines'] as $table => $engine ) {
				if ( 'myisam' === $engine ) {
					// ALTER commits implicitly: never promise a multi-table rollback or automatic reverse conversion.
					// phpcs:ignore WordPress.DB.DirectDatabaseQuery -- Only exact local allowlisted tables, after explicit operator confirmation.
					if ( false === $wpdb->query( $wpdb->prepare( 'ALTER TABLE %i ENGINE=InnoDB', $table ) ) ) {
						throw new \RuntimeException( 'Database conversion failed.' ); }
				}
				if ( self::snapshot( $table ) !== $before[ $table ] ) {
					throw new \RuntimeException( 'Database verification failed.' ); }
			}
			if ( 'ready' !== self::status()['state'] ) {
				throw new \RuntimeException( 'Database verification failed.' ); }
			delete_option( self::CHECKPOINT );
			if ( get_option( self::CHECKPOINT ) ) {
				throw new \RuntimeException( 'Database verification failed.' ); }
			if ( $repairing ) {
				( new AuditService() )->record( 'database_repaired' ); }
		} finally {
			if ( $lock ) {
				$repo->unlock( $lock ); }
			self::$running = false;
			$wpdb->suppress_errors( $previous );
		}
	}
}
