<?php
/** Read-only administration queries with authorization before matching/counting.
 *
 * @package FileWarden
 */

namespace FileWarden;

/** Keep global search from disclosing inaccessible resources or ancestor names. */
final class LibraryQuery {
	/** Normalize display-only filters using fixed sort keys.
	 *
	 * @param array $input Untrusted query.
	 * @return array Safe query.
	 */
	public static function filters( array $input ) {
		$get       = static fn( $key, $fallback = '' ) => isset( $input[ $key ] ) && is_string( $input[ $key ] ) ? sanitize_text_field( wp_unslash( $input[ $key ] ) ) : $fallback;
		$sort      = $get( 'sort', 'date_desc' );
		$extension = strtolower( $get( 'type' ) );
		return array(
			'search'   => substr( $get( 'search' ), 0, 200 ),
			'scope'    => 'all' === $get( 'scope' ) ? 'all' : 'folder',
			'sort'     => in_array( $sort, array( 'name_asc', 'name_desc', 'date_asc', 'date_desc', 'size_asc', 'size_desc', 'type_asc', 'type_desc' ), true ) ? $sort : 'date_desc',
			'min_size' => preg_match( '/^(0|[1-9][0-9]{0,6})$/D', $get( 'min_size' ) ) ? $get( 'min_size' ) : '',
			'max_size' => preg_match( '/^(0|[1-9][0-9]{0,6})$/D', $get( 'max_size' ) ) ? $get( 'max_size' ) : '',
			'type'     => preg_match( '/^[a-z0-9]{1,12}$/D', $extension ) ? $extension : '',
			'after'    => preg_match( '/^\d{4}-\d{2}-\d{2}$/D', $get( 'after' ) ) ? $get( 'after' ) : '',
			'before'   => preg_match( '/^\d{4}-\d{2}-\d{2}$/D', $get( 'before' ) ) ? $get( 'before' ) : '',
		);
	}

	/** Return only matching authorized rows; counts are taken from this result.
	 *
	 * @param DocumentService $service Services.
	 * @param int|null        $folder Folder or null for the authorized library.
	 * @param array           $filters Normalized filters.
	 * @param bool            $trash Search trash instead.
	 * @return array Authorized matches.
	 * @throws \RuntimeException On a query failure.
	 */
	public static function rows( DocumentService $service, $folder, array $filters, $trash = false ) {
		global $wpdb;
		$service->require_capability( $trash ? 'filewarden_delete_files' : 'filewarden_manage_permissions' );
		$after  = $filters['after'] ? get_gmt_from_date( $filters['after'] . ' 00:00:00' ) : '';
		$before = $filters['before'] ? get_gmt_from_date( $filters['before'] . ' 23:59:59' ) : '';
		$rows   = array();
		for ( $offset = 0; ; $offset += 100 ) {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery,WordPress.DB.PreparedSQL.NotPrepared -- Only two constant NULL clauses; constant clauses and prepared identifiers/pagination; no user SQL fragments.
			$batch = $wpdb->get_results( $wpdb->prepare( 'SELECT * FROM %i WHERE purging = 0 AND trashed_at IS ' . ( $trash ? 'NOT NULL' : 'NULL' ) . ' ORDER BY id LIMIT 100 OFFSET %d', $wpdb->prefix . 'filewarden_documents', $offset ), ARRAY_A );
			if ( $wpdb->last_error ) {
				throw new \RuntimeException( 'Library unavailable.' );
			}
			foreach ( $batch as $row ) {
				$row['policy']         = json_decode( $row['policy'], true );
				$row['retained_rules'] = empty( $row['retained_rules'] ) ? array() : json_decode( $row['retained_rules'], true );
				$visible               = $row;
				unset( $visible['trashed_at'] );
				if ( ! $service->permissions->can_read( $visible, wp_get_current_user() ) ) {
					continue;
				}
				if ( null !== $folder && (int) $row['folder_id'] !== $folder ) {
					continue;
				}
				if ( '' !== $filters['search'] && false === stripos( remove_accents( $row['name'] ), remove_accents( $filters['search'] ) ) && false === stripos( remove_accents( $row['description'] ?? '' ), remove_accents( $filters['search'] ) ) ) {
					continue;
				}
				if ( '' !== $filters['type'] && strtolower( pathinfo( $row['name'], PATHINFO_EXTENSION ) ) !== $filters['type'] ) {
					continue;
				}
				if ( ( $after && ( empty( $row['folder_added_at'] ) || $row['folder_added_at'] < $after ) ) || ( $before && ( empty( $row['folder_added_at'] ) || $row['folder_added_at'] > $before ) ) ) {
					continue;
				}
				if ( ( '' !== $filters['min_size'] && (int) $row['size'] < (int) $filters['min_size'] * 1024 ) || ( '' !== $filters['max_size'] && (int) $row['size'] > (int) $filters['max_size'] * 1024 ) ) {
					continue;
				}
				$row['description_match'] = '' !== $filters['search'] && false === stripos( remove_accents( $row['name'] ), remove_accents( $filters['search'] ) );
				$row['location']          = DocumentCollection::path( $service->repository, (int) $row['folder_id'] );
				$rows[]                   = $row;
			}
			if ( count( $batch ) < 100 ) {
				break;
			}
		}
		list( $key, $direction ) = explode( '_', $filters['sort'] );
		usort(
			$rows,
			static function ( $a, $b ) use ( $key, $direction ) {
				if ( 'date' === $key && empty( $a['folder_added_at'] ) !== empty( $b['folder_added_at'] ) ) {
					return empty( $a['folder_added_at'] ) ? 1 : -1;
				}
				$result = match ( $key ) {
					'size' => (int) $a['size'] <=> (int) $b['size'],
					'type' => strnatcasecmp( pathinfo( $a['name'], PATHINFO_EXTENSION ), pathinfo( $b['name'], PATHINFO_EXTENSION ) ),
					'name' => strnatcasecmp( remove_accents( $a['name'] ), remove_accents( $b['name'] ) ),
					default => strcmp( $a['folder_added_at'] ?? '', $b['folder_added_at'] ?? '' ),
				};
				$result = $result ? $result : (int) $a['id'] <=> (int) $b['id'];
				return 'desc' === $direction ? -$result : $result;
			}
		);
		return $rows;
	}
}
