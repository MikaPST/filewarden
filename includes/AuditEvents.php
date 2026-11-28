<?php
/** Stable action/status taxonomy; original event codes remain unchanged.
 *
 * @package FileWarden
 */

namespace FileWarden;

/** Unknown events never imply successful completion. */
final class AuditEvents {
	/** Known events mapped to action and outcome. */
	const CATALOG = array(
		'document_description_changed'     => array( 'description', 'success' ),
		'upload_requested'                 => array( 'upload', 'requested' ),
		'document_uploaded'                => array( 'upload', 'success' ),
		'upload_failed_storage'            => array( 'upload', 'failure' ),
		'upload_failed_database'           => array( 'upload', 'failure' ),
		'upload_failed_audit'              => array( 'upload', 'failure' ),
		'document_copy_requested'          => array( 'copy', 'requested' ),
		'document_copy_completed'          => array( 'copy', 'success' ),
		'document_move_requested'          => array( 'move', 'requested' ),
		'document_move_completed'          => array( 'move', 'success' ),
		'document_restored_requested'      => array( 'restore', 'requested' ),
		'document_restored'                => array( 'restore', 'success' ),
		'document_trashed_requested'       => array( 'trash', 'requested' ),
		'document_trashed'                 => array( 'trash', 'success' ),
		'document_purge_requested'         => array( 'purge', 'requested' ),
		'document_purged'                  => array( 'purge', 'success' ),
		'document_rename_requested'        => array( 'rename', 'requested' ),
		'document_renamed'                 => array( 'rename', 'success' ),
		'folder_rename_requested'          => array( 'rename', 'requested' ),
		'folder_renamed'                   => array( 'rename', 'success' ),
		'folder_create_requested'          => array( 'folder_create', 'requested' ),
		'folder_created'                   => array( 'folder_create', 'success' ),
		'folder_delete_requested'          => array( 'folder_delete', 'requested' ),
		'folder_deleted_files_recovered'   => array( 'folder_delete', 'success' ),
		'document_policy_change_requested' => array( 'permissions', 'requested' ),
		'document_policy_changed'          => array( 'permissions', 'success' ),
		'folder_policy_change_requested'   => array( 'permissions', 'requested' ),
		'folder_policy_changed'            => array( 'permissions', 'success' ),
		'access_denied'                    => array( 'access', 'denied' ),
		'preview_requested'                => array( 'preview', 'requested' ),
		'download_started'                 => array( 'download', 'started' ),
		'download_head'                    => array( 'download', 'requested' ),
		'settings_changed'                 => array( 'settings', 'success' ),
		'all_file_types_enabled'           => array( 'settings', 'success' ),
		'all_file_types_disabled'          => array( 'settings', 'success' ),
		'database_repair_requested'        => array( 'database', 'requested' ),
		'database_repaired'                => array( 'database', 'success' ),
		'wordpress_tables_converted'       => array( 'database', 'success' ),
		'audit_export_requested'           => array( 'audit', 'requested' ),
		'audit_purged'                     => array( 'audit', 'success' ),
		'backup_requested'                 => array( 'backup', 'requested' ),
		'backup_created'                   => array( 'backup', 'success' ),
		'backup_download_started'          => array( 'backup', 'started' ),
		'backup_downloaded'                => array( 'backup', 'success' ),
		'backup_analysis_requested'        => array( 'backup', 'requested' ),
		'backup_analysis_completed'        => array( 'backup', 'success' ),
		'backup_restore_requested'         => array( 'backup', 'requested' ),
		'backup_restored'                  => array( 'backup', 'success' ),
		'backup_cancelled'                 => array( 'backup', 'cancelled' ),
		'backup_failed'                    => array( 'backup', 'failure' ),
		'backup_restore_failed'            => array( 'backup', 'failure' ),
	);

	/** Return translated action choices.
	 *
	 * @return array Labels.
	 */
	public static function actions() {
		return array(
			'upload'        => __( 'Document upload', 'filewarden' ),
			'description'   => __( 'Document description change', 'filewarden' ),
			'copy'          => __( 'Document copy', 'filewarden' ),
			'move'          => __( 'Document move', 'filewarden' ),
			'restore'       => __( 'Document restoration', 'filewarden' ),
			'trash'         => __( 'Move to trash', 'filewarden' ),
			'purge'         => __( 'Permanent document deletion', 'filewarden' ),
			'rename'        => __( 'Document or folder rename', 'filewarden' ),
			'folder_create' => __( 'Folder creation', 'filewarden' ),
			'folder_delete' => __( 'Folder deletion', 'filewarden' ),
			'permissions'   => __( 'Permission change', 'filewarden' ),
			'access'        => __( 'Access control', 'filewarden' ),
			'preview'       => __( 'Document preview', 'filewarden' ),
			'download'      => __( 'Document download', 'filewarden' ),
			'settings'      => __( 'Settings change', 'filewarden' ),
			'database'      => __( 'Database maintenance', 'filewarden' ),
			'audit'         => __( 'Audit maintenance and export', 'filewarden' ),
			'backup'        => __( 'Backup and restoration', 'filewarden' ),
			'other'         => __( 'Other event', 'filewarden' ),
		);
	}

	/** Return translated status choices.
	 *
	 * @return array Labels.
	 */
	public static function statuses() {
		return array(
			'requested' => __( 'Request received', 'filewarden' ),
			'success'   => __( 'Succeeded', 'filewarden' ),
			'denied'    => __( 'Denied', 'filewarden' ),
			'failure'   => __( 'Failed', 'filewarden' ),
			'started'   => __( 'Started', 'filewarden' ),
			'cancelled' => __( 'Cancelled', 'filewarden' ),
			'unknown'   => __( 'Undetermined', 'filewarden' ),
		);
	}

	/** Classify a known code without guessing outcomes.
	 *
	 * @param string $event Original code.
	 * @return array Action and outcome.
	 */
	public static function classify( $event ) {
		return self::CATALOG[ $event ] ?? array( 'other', 'unknown' );
	}

	/** Validate a bounded CSV/multiple-choice field; none is an explicit empty scope.
	 *
	 * @param mixed $value Submitted values.
	 * @param array $choices Valid keys.
	 * @return array Canonical keys.
	 * @throws \InvalidArgumentException On malformed choices.
	 */
	public static function selection( $value, array $choices ) {
		if ( '' === $value || null === $value ) {
			return array_keys( $choices );
		}
		if ( is_string( $value ) && strlen( $value ) <= 512 ) {
			$value = explode( ',', $value );
		}
		if ( ! is_array( $value ) || count( $value ) > count( $choices ) + 1 ) {
			throw new \InvalidArgumentException( 'Invalid audit choices.' );
		}
		foreach ( $value as $key ) {
			if ( ! is_string( $key ) || ( 'none' !== $key && ! array_key_exists( $key, $choices ) ) ) {
				throw new \InvalidArgumentException( 'Invalid audit choice.' );
			}
		}
		return array_values( array_intersect( array_keys( $choices ), $value ) );
	}
}
