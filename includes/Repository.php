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
	 * @return mixed Operation result.
	 */
	public function document( $uuid ) {
		global $wpdb;
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery -- Custom tables require fresh authorization data and prepared database access.
		return $this->decode( $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}filewarden_documents WHERE uuid = %s", $uuid ), ARRAY_A ) );
	}

	/**
	 * Retrieve a bounded document batch.
	 *
	 * @param mixed $folder folder value.
	 * @param mixed $offset offset value.
	 * @return mixed Operation result.
	 */
	public function documents( $folder, $offset = 0 ) {
		global $wpdb;
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery -- Custom tables require fresh authorization data and prepared database access.
		$rows = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}filewarden_documents WHERE folder_id = %d ORDER BY id ASC LIMIT 100 OFFSET %d", $folder, $offset ), ARRAY_A );
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
		return (bool) $wpdb->get_var( $wpdb->prepare( "SELECT id FROM {$wpdb->prefix}filewarden_documents WHERE folder_id = %d AND name = %s LIMIT 1", $folder, $name ) );
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
}
