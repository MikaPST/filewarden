<?php
/**
 * WordPress-backed persistence. Raw storage records never leave the service layer.
 *
 * @package FileWarden
 */

namespace FileWarden;

/**
 * Repository implementation.
 */
final class Repository {
	/**
	 * Decode a persisted access policy.
	 *
	 * @param mixed $row row value.
	 * @return mixed Operation result.
	 */
	private function decode( $row ) {
		if ( ! $row ) {
			return null;
		}
		$row['policy'] = json_decode( $row['policy'], true );
		if ( ! empty( $row['retained_rules'] ) ) {
			$row['retained_rules'] = json_decode( $row['retained_rules'], true ) ?? array( null ); }
		return $row;
	}

	/**
	 * Retrieve or create a folder through the active transport.
	 *
	 * @param mixed $id id value.
	 * @return mixed Operation result.
	 */
	public function folder( $id ) {
		global $wpdb;
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery -- Custom tables require fresh authorization data and prepared database access.
		return $this->decode( $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}filewarden_folders WHERE id = %d", $id ), ARRAY_A ) );
	}

	/**
	 * Resolve an opaque folder reference. Authorization remains in the service.
	 *
	 * @param string $uuid Valid UUID.
	 * @return mixed Folder record or null.
	 */
	public function folder_by_uuid( $uuid ) {
		global $wpdb;
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery -- Fresh prepared custom-table lookup.
		return $this->decode( $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}filewarden_folders WHERE uuid = %s", $uuid ), ARRAY_A ) );
	}

	/**
	 * Retrieve a document by its logical UUID.
	 *
	 * @param mixed $uuid uuid value.
	 * @param bool  $include_trash Include soft-deleted records for authorized lifecycle operations.
	 * @return mixed Operation result.
	 */
	public function document( $uuid, $include_trash = false ) {
		global $wpdb;
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery -- Custom tables require fresh authorization data and prepared database access.
		return $this->decode( $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}filewarden_documents WHERE uuid = %s AND (%d = 1 OR (trashed_at IS NULL AND purging = 0))", $uuid, $include_trash ? 1 : 0 ), ARRAY_A ) );
	}

	/**
	 * Retrieve a bounded document batch.
	 *
	 * @param mixed $folder folder value.
	 * @param mixed $offset offset value.
	 * @param bool  $trash List trashed instead of active files.
	 * @return mixed Operation result.
	 */
	public function documents( $folder, $offset = 0, $trash = false ) {
		global $wpdb;
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery -- Custom tables require fresh authorization data and prepared database access.
		$rows = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}filewarden_documents WHERE folder_id = %d AND purging = 0 AND ((%d = 0 AND trashed_at IS NULL) OR (%d = 1 AND trashed_at IS NOT NULL)) ORDER BY id ASC LIMIT 100 OFFSET %d", $folder, $trash ? 1 : 0, $trash ? 1 : 0, $offset ), ARRAY_A );
		return array_map( array( $this, 'decode' ), $rows ?? array() );
	}

	/**
	 * Retrieve a bounded folder batch.
	 *
	 * @param mixed $parent_id parent value.
	 * @param mixed $offset offset value.
	 * @return mixed Operation result.
	 */
	public function folders( $parent_id, $offset = 0 ) {
		global $wpdb;
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery -- Custom tables require fresh authorization data and prepared database access.
		$rows = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}filewarden_folders WHERE parent_id = %d ORDER BY id ASC LIMIT 100 OFFSET %d", $parent_id, $offset ), ARRAY_A );
		return array_map( array( $this, 'decode' ), $rows ?? array() );
	}

	/**
	 * Check for an existing name in the target folder.
	 *
	 * @param mixed $folder folder value.
	 * @param mixed $name name value.
	 * @return mixed Operation result.
	 */
	public function duplicate( $folder, $name ) {
		global $wpdb;
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery -- Custom tables require fresh authorization data and prepared database access.
		return (bool) $wpdb->get_var( $wpdb->prepare( "SELECT id FROM {$wpdb->prefix}filewarden_documents WHERE folder_id = %d AND name = %s AND trashed_at IS NULL AND purging = 0 LIMIT 1", $folder, $name ) );
	}

	/**
	 * Persist a new folder or document record.
	 *
	 * @param mixed $kind kind value.
	 * @param array $data data value.
	 * @throws \InvalidArgumentException When the operation cannot be completed safely.
	 * @throws \RuntimeException When the operation cannot be completed safely.
	 * @return mixed Operation result.
	 */
	public function insert( $kind, array $data ) {
		global $wpdb;
		if ( ! in_array( $kind, array( 'documents', 'folders' ), true ) ) {
			throw new \InvalidArgumentException( 'Invalid record type.' );
		}
		if ( 'folders' === $kind ) {
			$data['uuid'] = wp_generate_uuid4(); }
		if ( isset( $data['retained_rules'] ) && is_array( $data['retained_rules'] ) ) {
			$data['retained_rules'] = wp_json_encode( $data['retained_rules'] ); }
		$data['policy']     = wp_json_encode( $data['policy'] );
		$data['created_by'] = get_current_user_id();
		$data['created_at'] = current_time( 'mysql', true );
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery -- Custom tables require fresh authorization data and prepared database access.
		if ( false === $wpdb->insert( $wpdb->prefix . 'filewarden_' . $kind, $data ) ) {
			throw new \RuntimeException( 'Database write failed.' );
		}
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery -- Custom tables require fresh authorization data and prepared database access.
		return (int) $wpdb->insert_id;
	}

	/**
	 * Acquire an upload name lock.
	 *
	 * @param mixed $folder folder value.
	 * @throws \RuntimeException When the operation cannot be completed safely.
	 * @return mixed Operation result.
	 */
	public function lock( $folder ) {
		global $wpdb;
		// Serialize a whole folder: database collations may equate differently spelled names.
		$key = 'fw_' . substr( hash( 'sha256', $wpdb->prefix . ':' . $folder ), 0, 60 );
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery -- Custom tables require fresh authorization data and prepared database access.
		if ( '1' !== (string) $wpdb->get_var( $wpdb->prepare( 'SELECT GET_LOCK(%s, 5)', $key ) ) ) {
			throw new \RuntimeException( 'Document is busy.' );
		}
		return $key;
	}

	/**
	 * Release an upload name lock.
	 *
	 * @param mixed $key key value.
	 * @return mixed Operation result.
	 */
	public function unlock( $key ) {
		global $wpdb;
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery -- Custom tables require fresh authorization data and prepared database access.
		$wpdb->get_var( $wpdb->prepare( 'SELECT RELEASE_LOCK(%s)', $key ) );
	}

	/**
	 * Update only the explicit policy using a fixed table allowlist.
	 *
	 * @param string $kind Record table.
	 * @param int    $id Internal ID.
	 * @param array  $policy Validated policy.
	 * @throws \RuntimeException When persistence fails.
	 * @throws \InvalidArgumentException For an unknown table.
	 */
	public function update_policy( $kind, $id, array $policy ) {
		global $wpdb;
		if ( ! in_array( $kind, array( 'folders', 'documents' ), true ) ) {
			throw new \InvalidArgumentException( 'Invalid record type.' );
		}
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery -- Prepared update on allowlisted custom tables, no stale permission cache.
		if ( false === $wpdb->update( $wpdb->prefix . 'filewarden_' . $kind, array( 'policy' => wp_json_encode( $policy ) ), array( 'id' => $id ), array( '%s' ), array( '%d' ) ) ) {
			throw new \RuntimeException( 'Policy update failed.' );
		}
	}

	/**
	 * Update lifecycle fields without accepting arbitrary columns.
	 *
	 * @param int   $id Document ID.
	 * @param array $fields Validated lifecycle fields.
	 * @throws \RuntimeException On persistence failure.
	 * @throws \InvalidArgumentException For unknown columns.
	 */
	public function update_document( $id, array $fields ) {
		global $wpdb;
		if ( array_diff( array_keys( $fields ), array( 'folder_id', 'name', 'trashed_at', 'purging' ) ) ) {
			throw new \InvalidArgumentException( 'Invalid fields.' ); }
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery -- wpdb prepares values for a fixed custom table.
		if ( false === $wpdb->update( $wpdb->prefix . 'filewarden_documents', $fields, array( 'id' => $id ) ) ) {
			throw new \RuntimeException( 'Document update failed.' ); }
	}
	/**
	 * Retrieve a bounded purge batch.
	 *
	 * @param string $cutoff UTC cutoff.
	 * @return array Candidate UUIDs.
	 */
	public function expired( $cutoff ) {
		global $wpdb;
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery -- Prepared maintenance query.
		return $wpdb->get_col( $wpdb->prepare( "SELECT uuid FROM {$wpdb->prefix}filewarden_documents WHERE purging = 1 OR trashed_at < %s LIMIT 25", $cutoff ) );
	}
	/**
	 * Remove a tombstone only after binary removal.
	 *
	 * @param int $id Purging document ID.
	 * @throws \RuntimeException On persistence failure.
	 */
	public function delete_purged( $id ) {
		global $wpdb;
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery -- Never delete an active document.
		if ( false === $wpdb->delete(
			$wpdb->prefix . 'filewarden_documents',
			array(
				'id'      => $id,
				'purging' => 1,
			)
		) ) {
			throw new \RuntimeException( 'Purge failed.' ); }
	}

	/**
	 * Fail closed rather than pretend rollback protects nontransactional tables.
	 *
	 * @throws \RuntimeException When transactions cannot protect the operation.
	 */
	public static function require_transactions() {
		global $wpdb;
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery -- Verify engine capabilities before destructive transactions.
		$engines = $wpdb->get_col( $wpdb->prepare( 'SELECT ENGINE FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME IN (%s, %s, %s)', $wpdb->prefix . 'filewarden_documents', $wpdb->prefix . 'filewarden_folders', $wpdb->prefix . 'filewarden_audit_logs' ) );
		if ( 3 !== count( $engines ) || array_filter( $engines, static fn( $engine ) => 'innodb' !== strtolower( $engine ) ) ) {
			throw new \RuntimeException( 'Transactional storage required.' ); }
	}
}
