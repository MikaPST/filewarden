<?php
/** Explicit, bounded conversion of shared WordPress metadata tables.
 *
 * @package FileWarden
 */

namespace FileWarden;

// phpcs:disable WordPress.DB.DirectDatabaseQuery -- Only the three fixed current-site table identifiers are permitted.
/** Operator maintenance is required; DDL has no multi-table rollback guarantee. */
final class WordPressTables {
	const CHECKPOINT = 'filewarden_wordpress_table_conversion';
	const MAX_ROWS   = 200000;
	const MAX_BYTES  = 67108864;
	/** Internal converter connection.
	 *
	 * @var bool
	 */
	private static $running = false;

	/** Require both WordPress administration and FileWarden settings privileges.
	 *
	 * @throws \DomainException Without independent administration privileges.
	 */
	public static function authorize() {
		if ( ! current_user_can( 'manage_options' ) || ! current_user_can( 'filewarden_manage_settings' ) ) {
			throw new \DomainException( 'Access denied.' ); }
	}
	/** Block plugin writers while an interrupted conversion awaits verification.
	 *
	 * @throws \RuntimeException On a pending or unreadable checkpoint.
	 */
	public static function guard() {
		if ( self::$running ) {
			return; }
		global $wpdb;
		$state = $wpdb->get_var( $wpdb->prepare( 'SELECT option_value FROM %i WHERE option_name = %s', $wpdb->options, self::CHECKPOINT ) );
		if ( $wpdb->last_error || $state ) {
			throw new \RuntimeException( 'Database repair pending.' ); }
	}
	/** Exact logical table/primary-key pairs. */
	private static function tables() {
		global $wpdb;
		return array(
			'options'  => array( $wpdb->options, 'option_id' ),
			'posts'    => array( $wpdb->posts, 'ID' ),
			'postmeta' => array( $wpdb->postmeta, 'meta_id' ),
		);
	}
	/** Observe exact engines and apply interactive safety bounds.
	 *
	 * @param bool $bounded Enforce helper limits when preparing conversion.
	 * @throws \RuntimeException On missing metadata, unsupported engines or bounds.
	 */
	public static function status( $bounded = true ) {
		global $wpdb;
		$names    = array_column( self::tables(), 0 );
		$previous = $wpdb->suppress_errors( true );
		try {
			$rows = $wpdb->get_results( $wpdb->prepare( 'SHOW TABLE STATUS WHERE Name IN (%s,%s,%s)', $names[0], $names[1], $names[2] ), ARRAY_A );
			if ( $wpdb->last_error || ! is_array( $rows ) || count( $rows ) !== 3 ) {
				throw new \RuntimeException( 'Core table metadata unavailable.' ); }
			$by_name  = array_column( $rows, null, 'Name' );
			$engines  = array();
			$required = 0;
			foreach ( self::tables() as $logical => $table ) {
				$row = $by_name[ $table[0] ] ?? null;
				if ( ! $row || ! in_array( strtolower( (string) $row['Engine'] ), array( 'myisam', 'innodb' ), true ) ) {
					throw new \RuntimeException( 'Core table engine unsupported.' ); }
				$bytes = (int) $row['Data_length'] + (int) $row['Index_length'];
				if ( $bounded && ( $bytes > self::MAX_BYTES || (int) $row['Rows'] > self::MAX_ROWS ) ) {
					throw new \RuntimeException( 'Core table interactive limit.' ); }
				$engines[ $logical ] = strtolower( $row['Engine'] );
				if ( 'myisam' === $engines[ $logical ] ) {
					$required += $bytes * 2 + 16777216; }
			}
			return array(
				'engines'        => $engines,
				'required_bytes' => $required,
			);
		} finally {
			$wpdb->suppress_errors( $previous ); }
	}
	/** Hash full bounded content, schema and indexes; exclude only our checkpoint row.
	 *
	 * @param string $logical Fixed logical table key.
	 * @throws \RuntimeException If bounded verification fails.
	 */
	private static function snapshot( $logical ) {
		global $wpdb;
		list( $table, $key ) = self::tables()[ $logical ];
		$columns             = $wpdb->get_results( $wpdb->prepare( 'SHOW FULL COLUMNS FROM %i', $table ), ARRAY_A );
		$indexes             = $wpdb->get_results( $wpdb->prepare( 'SHOW INDEX FROM %i', $table ), ARRAY_A );
		$metadata            = $wpdb->get_row( $wpdb->prepare( 'SHOW TABLE STATUS WHERE Name = %s', $table ), ARRAY_A );
		if ( $wpdb->last_error || ! $columns || ! $indexes || ! $metadata ) {
			throw new \RuntimeException( 'Core table verification failed.' ); }
		$indexes = array_map( static fn( $row ) => array_intersect_key( $row, array_flip( array( 'Key_name', 'Non_unique', 'Seq_in_index', 'Column_name', 'Sub_part', 'Index_type', 'Collation', 'Null' ) ) ), $indexes );
		usort( $indexes, static fn( $a, $b ) => array( $a['Key_name'], (int) $a['Seq_in_index'] ) <=> array( $b['Key_name'], (int) $b['Seq_in_index'] ) );
		$hash  = hash_init( 'sha256' );
		$last  = '0';
		$count = 0;
		$bytes = 0;
		do {
			$extra = 'options' === $logical ? $wpdb->prepare( ' AND option_name <> %s', self::CHECKPOINT ) : '';
            // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- All interpolated fragments are prepared constant clauses.
			$rows = $wpdb->get_results( $wpdb->prepare( 'SELECT * FROM %i WHERE %i > CAST(%s AS UNSIGNED)', $table, $key, $last ) . $extra . $wpdb->prepare( ' ORDER BY %i LIMIT 200', $key ), ARRAY_A );
			if ( $wpdb->last_error || ! is_array( $rows ) ) {
				throw new \RuntimeException( 'Core table verification failed.' ); }
			foreach ( $rows as $row ) {
				$json = wp_json_encode( $row );
				if ( ! is_string( $json ) ) {
					throw new \RuntimeException( 'Core table verification failed.' ); }
				$bytes += strlen( $json );
				++$count;
				if ( $count > self::MAX_ROWS || $bytes > self::MAX_BYTES ) {
					throw new \RuntimeException( 'Core table interactive limit.' ); }
				hash_update( $hash, strlen( $json ) . ':' . $json );
				$last = (string) $row[ $key ];
			}
			$batch_count = count( $rows );
		} while ( 200 === $batch_count );
		return array(
			'rows'     => $count,
			'data'     => hash_final( $hash ),
			'columns'  => hash( 'sha256', wp_json_encode( $columns ) ),
			'indexes'  => hash( 'sha256', wp_json_encode( $indexes ) ),
			'metadata' => array( $metadata['Collation'], $metadata['Auto_increment'] ),
		);
	}
	/** Update an existing reserved option without advancing its auto-increment.
	 *
	 * @param array $state Verified checkpoint.
	 * @throws \RuntimeException On checkpoint persistence failure.
	 */
	private static function save( array $state ) {
		global $wpdb;
		if ( false === $wpdb->update( $wpdb->options, array( 'option_value' => maybe_serialize( $state ) ), array( 'option_name' => self::CHECKPOINT ) ) ) {
			throw new \RuntimeException( 'Core table checkpoint failed.' ); }
		wp_cache_delete( self::CHECKPOINT, 'options' );
		wp_cache_delete( 'notoptions', 'options' );
	}
	/** Real SQL exclusion while inspecting or converting a table.
	 *
	 * @throws \RuntimeException If the SQL locks cannot be acquired.
	 */
	private static function lock_tables() {
		global $wpdb;
		if ( false === $wpdb->query( $wpdb->prepare( 'LOCK TABLES %i WRITE, %i WRITE, %i WRITE', $wpdb->options, $wpdb->posts, $wpdb->postmeta ) ) ) {
			throw new \RuntimeException( 'Core table locking unavailable.' ); }
	}
	/** Readiness report with a signed review; no DDL or physical path is returned.
	 *
	 * @throws \DomainException For another administrator’s checkpoint.
	 * @throws \RuntimeException If disk capacity is insufficient.
	 */
	public static function preflight() {
		self::authorize();
		global $wpdb;
		$state = get_option( self::CHECKPOINT );
		if ( $state && ( ! is_array( $state ) || (int) ( $state['owner'] ?? 0 ) !== get_current_user_id() ) ) {
			throw new \DomainException( 'Conversion owned by another administrator.' ); }
		$status = self::status();
		$wait   = (int) $wpdb->get_var( 'SELECT @@SESSION.lock_wait_timeout' );
		$wpdb->query( 'SET SESSION lock_wait_timeout = 5' );
		try {
			self::lock_tables(); } finally {
			$wpdb->query( 'UNLOCK TABLES' );
			$wpdb->query( $wpdb->prepare( 'SET SESSION lock_wait_timeout = %d', max( 1, $wait ) ) ); }
			$free     = false;
			$datadir  = $wpdb->get_var( 'SELECT @@datadir' );
			$sql_host = $wpdb->get_var( 'SELECT @@hostname' );
			$local    = 1 === preg_match( '/^(?:localhost|127\.0\.0\.1|\[::1\])(?::[0-9]+)?$/Di', DB_HOST ) && is_string( $sql_host ) && 0 === strcasecmp( $sql_host, (string) gethostname() );
			if ( $local && is_string( $datadir ) && is_dir( $datadir ) && is_readable( $datadir ) ) {
				$free = FileIO::attempt( static fn() => disk_free_space( $datadir ) ); } // phpcs:ignore WordPress.WP.AlternativeFunctions -- Optional observation, not a claim about remote SQL disks.
			if ( false !== $free && $free < $status['required_bytes'] ) {
				throw new \RuntimeException( 'Core table disk insufficient.' ); }
			$time = time();
			return array(
				'engines'        => $status['engines'],
				'disk_measured'  => false !== $free,
				'required_bytes' => $status['required_bytes'],
				'pending'        => (bool) $state,
				'completed'      => count( $state['completed'] ?? array() ),
				'reviewed_at'    => $state['reviewed_at'] ?? $time,
				'proof'          => $state['proof'] ?? wp_hash( wp_json_encode( array( get_current_user_id(), $time, $status['engines'] ) ) ),
			);
	}
	/** Convert at most one table, verify all snapshots and retain a resumable checkpoint.
	 *
	 * @param int    $reviewed_at Signed review timestamp.
	 * @param string $proof Bound review digest.
	 * @param bool   $confirmed Backup and maintenance confirmed.
	 * @param bool   $disk_confirmed Host confirmed remote database disk space.
	 * @throws \InvalidArgumentException On missing explicit confirmation.
	 * @throws \RuntimeException If conversion, verification or recovery cannot finish.
	 * @throws \Throwable After retaining a prepared checkpoint and releasing locks.
	 */
	public static function step( $reviewed_at, $proof, $confirmed, $disk_confirmed ) {
		self::authorize();
		if ( true !== $confirmed || ! is_int( $reviewed_at ) || ! is_string( $proof ) ) {
			throw new \InvalidArgumentException( 'Conversion confirmation required.' ); }
		global $wpdb;
		$repo          = new Repository();
		$lock          = null;
		$sql_locked    = false;
		$created       = false;
		$prepared      = false;
		$previous      = $wpdb->suppress_errors( true );
		self::$running = true;
		$wait          = (int) $wpdb->get_var( 'SELECT @@SESSION.lock_wait_timeout' );
		$wpdb->query( 'SET SESSION lock_wait_timeout = 5' );
		try {
			$lock = $repo->lock( 'wordpress-table-conversion' );
			Repository::require_transactions();
			$review = self::preflight();
			$state  = get_option( self::CHECKPOINT );
			if ( ! $state ) {
				$expected = wp_hash( wp_json_encode( array( get_current_user_id(), $reviewed_at, $review['engines'] ) ) );
				if ( $reviewed_at > time() || time() - $reviewed_at > 600 || ! hash_equals( $expected, $proof ) ) {
					throw new \RuntimeException( 'Core table review changed.' ); }
			}
			if ( ! $review['disk_measured'] && true !== $disk_confirmed ) {
				throw new \RuntimeException( 'Core table disk unknown.' ); }
			if ( ! $state ) {
				$state = array(
					'owner'       => get_current_user_id(),
					'reviewed_at' => $reviewed_at,
					'proof'       => $proof,
					'snapshots'   => array(),
					'completed'   => array(),
				);
				if ( ! add_option( self::CHECKPOINT, $state, '', false ) ) {
					throw new \RuntimeException( 'Core table checkpoint failed.' ); }
				$created = true;
				self::lock_tables();
				$sql_locked = true;
				foreach ( array_keys( self::tables() ) as $logical ) {
					$state['snapshots'][ $logical ] = self::snapshot( $logical ); }
				self::save( $state );
				$prepared = true;
			} else {
				if ( ! hash_equals( (string) ( $state['proof'] ?? '' ), $proof ) || array_keys( $state['snapshots'] ?? array() ) !== array_keys( self::tables() ) ) {
					throw new \RuntimeException( 'Core table checkpoint failed.' ); }
				self::lock_tables();
				$sql_locked = true;
			}
			$converted = false;
			foreach ( self::tables() as $logical => $table ) {
				if ( self::snapshot( $logical ) !== $state['snapshots'][ $logical ] ) {
					throw new \RuntimeException( 'Core table content changed.' ); }
				if ( in_array( $logical, $state['completed'], true ) ) {
					continue; }
				if ( 'myisam' === $review['engines'][ $logical ] && $converted ) {
					continue; }
				if ( 'myisam' === $review['engines'][ $logical ] ) {
					// DDL commits implicitly; reacquire locks because ALTER may release a table lock.
					if ( false === $wpdb->query( $wpdb->prepare( 'ALTER TABLE %i ENGINE=InnoDB, LOCK=EXCLUSIVE', $table[0] ) ) ) {
						throw new \RuntimeException( 'Core table conversion failed.' ); }
					self::lock_tables();
					$converted = true;
					if ( self::snapshot( $logical ) !== $state['snapshots'][ $logical ] ) {
						throw new \RuntimeException( 'Core table verification failed.' ); }
				}
				$state['completed'][] = $logical;
				self::save( $state );
			}
			$wpdb->query( 'UNLOCK TABLES' );
			$sql_locked = false;
			$done       = count( $state['completed'] ) === 3 && ! array_diff( self::status()['engines'], array( 'innodb' ) );
			if ( $done ) {
				if ( empty( $state['audited'] ) ) {
					( new AuditService() )->record( 'wordpress_tables_converted' );
					$state['audited'] = true;
					self::save( $state );
				}
				if ( false === $wpdb->delete( $wpdb->options, array( 'option_name' => self::CHECKPOINT ) ) ) {
					throw new \RuntimeException( 'Core table checkpoint failed.' ); }
				wp_cache_delete( self::CHECKPOINT, 'options' );
				wp_cache_delete( 'notoptions', 'options' );
			}
			return array(
				'done'      => $done,
				'completed' => count( $state['completed'] ),
				'engines'   => self::status()['engines'],
			);
		} catch ( \Throwable $error ) {
			if ( $created && ! $prepared ) {
				$wpdb->delete( $wpdb->options, array( 'option_name' => self::CHECKPOINT ) );
				wp_cache_delete( self::CHECKPOINT, 'options' ); }
			throw $error;
		} finally {
			if ( $sql_locked ) {
				$wpdb->query( 'UNLOCK TABLES' ); }
			$wpdb->query( $wpdb->prepare( 'SET SESSION lock_wait_timeout = %d', max( 1, $wait ) ) );
			if ( $lock ) {
				$repo->unlock( $lock ); }
			self::$running = false;
			$wpdb->suppress_errors( $previous );
		}
	}
	/** Nonce/capability-bound helper; return static guidance instead of SQL errors.
	 *
	 * @throws \InvalidArgumentException On an unknown operation.
	 */
	public static function handle() {
		check_ajax_referer( 'filewarden_wp_tables' );
		nocache_headers();
		global $wpdb;
		$wpdb->suppress_errors( true );
		try {
			self::authorize();
            // phpcs:ignore WordPress.Security.ValidatedSanitizedInput -- Fixed operation and exact scalar flags; IDs and proof validated below.
			$input = wp_unslash( $_POST );
			if ( 'preflight' === ( $input['operation'] ?? '' ) ) {
				wp_send_json_success( self::preflight() ); }
			if ( 'convert' !== ( $input['operation'] ?? '' ) ) {
				throw new \InvalidArgumentException( 'Unknown conversion action.' ); }
			wp_send_json_success( self::step( RestController::integer( $input['reviewed_at'] ?? null ), $input['proof'] ?? '', ( '1' === ( $input['confirmed'] ?? '' ) ), ( '1' === ( $input['disk_confirmed'] ?? '' ) ) ) );
		} catch ( \Throwable $error ) {
			wp_send_json_error(
				array(
					'message'   => __( 'Conversion could not complete. Keep maintenance active and your independent backup. Check SQL permissions, database disk space and table size with your host; review and resume if the data is unchanged. No automatic rollback was attempted.', 'filewarden' ),
					'reference' => 'FWDB_CONVERSION',
				),
				400
			);
		}
	}
}
