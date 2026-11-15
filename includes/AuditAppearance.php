<?php
/** Presentation only: never infer completion from an unknown event code.
 *
 * @package FileWarden
 */

namespace FileWarden;

/** Semantic groups for the simplified audit view only. */
final class AuditAppearance {
	/** Return a fixed, translated category and decorative icon.
	 *
	 * @param string $event Stored event code.
	 * @return array Tone, label and icon.
	 */
	public static function event( $event ) {
		$groups = array(
			'info'    => array( 'audit_export_requested', 'preview_requested', 'download_started', 'download_head', 'upload_requested', 'folder_create_requested', 'document_move_requested', 'document_copy_requested', 'document_restored_requested' ),
			'config'  => array( 'settings_changed', 'all_file_types_enabled', 'all_file_types_disabled', 'document_policy_changed', 'folder_policy_changed', 'document_policy_change_requested', 'folder_policy_change_requested', 'database_repair_requested', 'database_repaired' ),
			'success' => array( 'document_uploaded', 'folder_created', 'document_restored', 'document_copy_completed', 'document_move_completed' ),
			'warning' => array( 'document_trashed_requested', 'document_trashed', 'document_purge_requested', 'folder_delete_requested' ),
			'deleted' => array( 'document_purged', 'folder_deleted_files_recovered', 'audit_purged' ),
			'error'   => array( 'access_denied', 'upload_failed_storage', 'upload_failed_database', 'upload_failed_audit' ),
		);
		$styles = array(
			'info'    => array( __( 'Information', 'filewarden' ), 'info' ),
			'config'  => array( __( 'Configuration and access', 'filewarden' ), 'shield-check' ),
			'success' => array( __( 'Completed', 'filewarden' ), 'circle-check' ),
			'warning' => array( __( 'Trash and deletion requests', 'filewarden' ), 'archive-box' ),
			'deleted' => array( __( 'Deleted', 'filewarden' ), 'trash-2' ),
			'error'   => array( __( 'Error or refusal', 'filewarden' ), 'circle-x' ),
			'neutral' => array( __( 'Other event', 'filewarden' ), 'scroll-text' ),
		);
		foreach ( $groups as $tone => $events ) {
			if ( in_array( $event, $events, true ) ) {
				return array( $tone, $styles[ $tone ][0], $styles[ $tone ][1] ); }
		}
		return array( 'neutral', $styles['neutral'][0], $styles['neutral'][1] );
	}
}
