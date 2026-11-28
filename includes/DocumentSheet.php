<?php
/** Server-rendered private document sheet and access explanation.
 *
 * @package FileWarden
 */

namespace FileWarden;

/** Render only records already authorized by the document service. */
final class DocumentSheet {
	/** Render the actual intersecting rules, not an approximation in JavaScript.
	 *
	 * @param DocumentService $service Services.
	 * @param array           $row Authorized record.
	 */
	public static function access( DocumentService $service, array $row ) {
		$rules     = $service->permissions->rules( $row );
		$effective = AccessSummary::effective( $row, $service->repository );
		$reason    = 0 === (int) $row['folder_id'] ? __( 'Not published: reserved for authorized library managers.', 'filewarden' ) : ( ! empty( $row['retained_rules'] ) ? __( 'Previous location restrictions still apply to this document.', 'filewarden' ) : ( __( 'Public', 'filewarden' ) === $effective['label'] ? __( 'This document is public.', 'filewarden' ) : __( 'Access is limited by the file rule and its folder hierarchy.', 'filewarden' ) ) );
		echo '<p class="fw-access-reason">' . esc_html( $reason ) . '</p>';
		echo '<section class="fw-access-explanation"><details><summary>' . esc_html__( 'Understand access', 'filewarden' ) . '</summary><ul class="fw-access-rules fw-access-steps">';
		self::rule( __( 'File rule', 'filewarden' ), array_shift( $rules ), 'file' );
		foreach ( (array) ( $row['retained_rules'] ?? array() ) as $unused ) {
			self::rule( __( 'Previous location restrictions', 'filewarden' ), array_shift( $rules ), 'retained' );
		}
		$parent = (int) $row['folder_id'];
		$first  = true;
		while ( $parent ) {
			$folder = $service->repository->folder( $parent );
			self::rule( ( $first ? __( 'Current folder', 'filewarden' ) : __( 'Ancestor folder', 'filewarden' ) ) . ' · ' . $folder['name'], array_shift( $rules ), $first ? 'folder' : 'ancestor' );
			$parent = (int) $folder['parent_id'];
			$first  = false;
		}
		echo '</ul><p class="fw-access-principle">' . esc_html__( 'Access requires all applicable rules. A public file cannot override a restricted folder.', 'filewarden' ) . '</p>';
		if ( 0 === (int) $row['folder_id'] ) {
			echo '<p class="fw-warning">' . esc_html__( 'Documents to classify is not published. Only authorized library managers can access this collection.', 'filewarden' ) . '</p>';
		}
		echo '<p class="description">' . esc_html__( 'Administrators with global content-read permission are an explicit exception. This does not grant editing rights.', 'filewarden' ) . '</p></details></section>';
	}

	/** Explain one already validated rule without recalculating authorization.
	 *
	 * @param string $title Source label, never a fabricated former folder path.
	 * @param array  $policy Validated policy from the authoritative chain.
	 * @param string $source File, folder, ancestor or retained.
	 */
	private static function rule( $title, array $policy, $source ) {
		$file       = 'file' === $source;
		$retained   = 'retained' === $source;
		$restricted = in_array( $policy['type'], array( 'authenticated', 'roles' ), true );
		$note       = match ( $policy['type'] ) {
			'inherit' => $file ? __( 'The file follows its folder rules.', 'filewarden' ) : __( 'This folder follows its parent folder rules.', 'filewarden' ),
			'public' => $file ? __( 'The file adds no restriction.', 'filewarden' ) : __( 'This folder adds no restriction.', 'filewarden' ),
			'authenticated' => $file ? __( 'The file requires sign-in.', 'filewarden' ) : __( 'This folder requires sign-in.', 'filewarden' ),
			default => $file ? __( 'The file is reserved for the listed roles.', 'filewarden' ) : __( 'This folder is reserved for the listed roles.', 'filewarden' ),
		};
		if ( $retained ) {
			$note = $restricted ? __( 'This condition is retained from the previous location.', 'filewarden' ) : __( 'This retained rule adds no restriction.', 'filewarden' );
		}
		$icon = match ( $source ) {
			'file' => 'scroll-text', 'retained' => 'info', 'ancestor' => 'files', default => 'folder-open' };
		echo '<li class="fw-access-step' . ( $restricted ? ' is-restrictive' : '' ) . ( $retained ? ' is-retained' : '' ) . '"><span class="fw-action-icon fw-icon-' . esc_attr( $icon ) . '" aria-hidden="true"></span><div class="fw-access-step-content"><div class="fw-access-step-title"><strong>' . esc_html( $title ) . '</strong><span class="fw-access-step-value">' . esc_html( AccessSummary::label( $policy ) ) . '</span></div><p>' . esc_html( $note ) . '</p></div></li>';
	}

	/** Metadata and optional description, with a native POST fallback.
	 *
	 * @param DocumentService $service Services.
	 * @param AdminController $ui Form transport.
	 * @param array           $row Authorized record.
	 * @param string          $rights_url Current rights action.
	 */
	public static function render( DocumentService $service, AdminController $ui, array $row, $rights_url ) {
		$url = $service->metadata( $row )['detail_url'];
		echo '<div class="fw-document-sheet"><p class="fw-sheet-location"><span class="fw-action-icon fw-icon-folder" aria-hidden="true"></span>' . esc_html( DocumentCollection::path( $service->repository, (int) $row['folder_id'] ) ) . '</p><div class="fw-sheet-actions"><a class="button" data-filewarden-preview target="_blank" rel="noopener noreferrer" href="' . esc_url( $url ) . '"><span class="fw-action-icon fw-icon-eye" aria-hidden="true"></span>' . esc_html__( 'Preview', 'filewarden' ) . '</a><a class="button button-primary" href="' . esc_url( add_query_arg( 'download', '1', $url ) ) . '"><span class="fw-action-icon fw-icon-download" aria-hidden="true"></span>' . esc_html__( 'Download', 'filewarden' ) . '</a></div>';
		echo '<section class="fw-sheet-section"><h3>' . esc_html__( 'Description', 'filewarden' ) . '</h3><p class="fw-description-text">' . esc_html( ! empty( $row['description'] ) ? $row['description'] : __( 'No description yet.', 'filewarden' ) ) . '</p>';
		if ( current_user_can( 'filewarden_edit_files' ) && current_user_can( 'filewarden_manage_permissions' ) ) {
			echo '<details class="fw-description-editor"><summary>' . esc_html__( 'Edit description', 'filewarden' ) . '</summary>';
			$ui->form_start( 'description' );
			echo '<input type="hidden" name="document" value="' . esc_attr( $row['uuid'] ) . '"><input type="hidden" name="folder_id" value="' . esc_attr( $row['folder_id'] ) . '"><label for="fw-sheet-description">' . esc_html__( 'Description (optional)', 'filewarden' ) . '</label><textarea id="fw-sheet-description" name="description" data-description rows="4">' . esc_textarea( $row['description'] ?? '' ) . '</textarea><output class="fw-description-count">' . esc_html( preg_match_all( '/./us', $row['description'] ?? '' ) ) . ' / 400</output><p class="description">' . esc_html__( 'Plain text, up to 400 characters. Clear the field to remove the description.', 'filewarden' ) . '</p><div class="fw-sheet-actions"><button class="button button-primary">' . esc_html__( 'Save description', 'filewarden' ) . '</button><button type="reset" class="button" data-description-cancel>' . esc_html__( 'Cancel', 'filewarden' ) . '</button></div></form></details>';
		}
		echo '</section><section class="fw-sheet-section"><h3>' . esc_html__( 'Document information', 'filewarden' ) . '</h3><dl class="fw-sheet-metadata">';
		$creator = get_userdata( (int) $row['created_by'] );
		$fields  = array(
			__( 'Format', 'filewarden' )               => strtoupper( pathinfo( $row['name'], PATHINFO_EXTENSION ) ),
			__( 'Size', 'filewarden' )                 => size_format( (int) $row['size'] ),
			__( 'Imported on', 'filewarden' )          => get_date_from_gmt( $row['created_at'], get_option( 'date_format' ) . ' ' . get_option( 'time_format' ) ),
			__( 'Added to this folder', 'filewarden' ) => empty( $row['folder_added_at'] ) ? '—' : get_date_from_gmt( $row['folder_added_at'], get_option( 'date_format' ) . ' ' . get_option( 'time_format' ) ),
			__( 'Creator', 'filewarden' )              => $creator ? $creator->display_name : __( 'Unavailable', 'filewarden' ),
		);
		foreach ( $fields as $label => $value ) {
			echo '<dt>' . esc_html( $label ) . '</dt><dd>' . esc_html( $value ) . '</dd>';
		}
		echo '</dl></section><section class="fw-sheet-section"><h3>' . esc_html__( 'Effective access', 'filewarden' ) . '</h3><p class="filewarden-policy">' . esc_html( AccessSummary::effective( $row, $service->repository )['label'] ) . '</p>';
		self::access( $service, $row );
		if ( current_user_can( 'filewarden_manage_permissions' ) ) {
			echo '<a class="button fw-button-permissions" href="' . esc_url( $rights_url ) . '"><span class="fw-action-icon fw-icon-shield-check" aria-hidden="true"></span>' . esc_html__( 'Edit access rules', 'filewarden' ) . '</a>';
		}
		echo '</section></div>';
	}
}
