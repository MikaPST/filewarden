<?php
/**
 * Safe, actionable backup failures without exposing exception details.
 *
 * @package FileWarden
 */

namespace FileWarden;

/** Translate only known failures; never return paths, phrases or stack traces. */
final class BackupErrors {
	/**
	 * Classify fixed internal messages, not arbitrary exception text.
	 *
	 * @param \Throwable $error Internal failure.
	 * @return string Safe reference.
	 */
	public static function code( \Throwable $error ) {
		return match ( $error->getMessage() ) {
			'Invalid backup components.', 'Invalid restoration choices.' => 'FWB_COMPONENTS',
			'Sodium extension missing.' => 'FWB_SODIUM_MISSING',
			'Sodium streaming unavailable.' => 'FWB_SODIUM_STREAM_API',
			'Sodium streaming failed.' => 'FWB_SODIUM_STREAM_FAILURE',
			'Access denied.', 'Backup access denied.' => 'FWB_PERMISSION',
			'Invalid backup phrase.', 'Backup confirmation required.' => 'FWB_PHRASE',
			'Sodium is required.', 'Sodium password derivation unavailable.' => 'FWB_SODIUM_UNAVAILABLE',
			'Sodium password derivation failed.' => 'FWB_SODIUM_DERIVATION',
			'Another library backup is active.', 'Library backup in progress.', 'Document is busy.', 'Database repair pending.' => 'FWB_BUSY',
			'Transactional storage required.', 'Table metadata unavailable.', 'Plugin table missing.', 'Restoration requires transactional WordPress metadata tables.' => 'FWB_DATABASE',
			'HTTP verification failed. The public witness must be readable and the protected witness denied.', 'Managed storage requires a same-host HTTPS content URL.' => 'FWB_STORAGE_VERIFICATION',
			'Private backup storage unavailable.', 'Private storage is unavailable.', 'Backup write unavailable.', 'Backup checkpoint write failed.', 'Private backup upload failed.' => 'FWB_STORAGE_WRITE',
			'Backup free space unavailable.' => 'FWB_SPACE_UNKNOWN',
			'Insufficient backup storage.' => 'FWB_SPACE_LOW',
			'Backup manifest limit exceeded.', 'Backup checkpoint limit exceeded.', 'Backup upload limit exceeded.' => 'FWB_LIMIT',
			'Invalid backup manifest.', 'Invalid policy.', 'Invalid policy type.', 'Invalid roles.', 'Unknown role.', 'Invalid policy roles.', 'Cleanup must finish before backup.' => 'FWB_METADATA',
			'Unsupported backup header.', 'Invalid backup frame.', 'Invalid backup frame size.', 'Backup authentication failed.', 'Invalid backup termination.', 'Backup is truncated.', 'Missing final authentication.', 'Backup file integrity failed.', 'Source integrity failed.' => 'FWB_INTEGRITY',
			'Map every source role explicitly.', 'Map every source creator explicitly.' => 'FWB_MAPPINGS',
			'Restoration requires an empty FileWarden library.' => 'FWB_TARGET_NOT_EMPTY',
			'Backup job expired.' => 'FWB_EXPIRED',
			'Backup checkpoint key unavailable.', 'Existing backup checkpoint key is missing.', 'Backup checkpoint invalid.', 'Backup checkpoint is truncated.', 'Backup job unavailable.' => 'FWB_CHECKPOINT',
			default => 'FWB_UNEXPECTED',
		};
	}

	/**
	 * Return translated static guidance and a safe reference.
	 *
	 * @param \Throwable $error Internal failure.
	 * @return array Public response.
	 */
	public static function response( \Throwable $error ) {
		$code    = self::code( $error );
		$message = match ( $code ) {
			'FWB_COMPONENTS' => __( 'The selected backup components or restoration choices are invalid. Review the available choices; omitted access rules always restore private administration-only content.', 'filewarden' ),
			'FWB_SODIUM_MISSING' => __( 'The native PHP Sodium extension is not enabled. Activate Sodium in your hosting PHP settings or contact your host, then run diagnostics again.', 'filewarden' ),
			'FWB_SODIUM_STREAM_API' => __( 'PHP Sodium is enabled, but the required authenticated-streaming functions are unavailable. Ask your host to check the native extension and its libsodium version.', 'filewarden' ),
			'FWB_SODIUM_STREAM_FAILURE' => __( 'The native Sodium encryption test failed. Ask your host to investigate the PHP extension; no backup encryption check was bypassed.', 'filewarden' ),
			'FWB_PERMISSION' => __( 'Your account lacks a required backup or restoration permission.', 'filewarden' ),
			'FWB_PHRASE' => __( 'Confirm an identical backup password of at least 16 characters, at most 1024 bytes, and acknowledge that you saved it separately.', 'filewarden' ),
			'FWB_SODIUM_UNAVAILABLE' => __( 'The PHP Sodium password-derivation functions are unavailable. Ask your host to enable native Sodium with Argon2id support.', 'filewarden' ),
			'FWB_SODIUM_DERIVATION' => __( 'Sodium could not derive the backup key. Ask your host to check available process memory and Argon2id support.', 'filewarden' ),
			'FWB_BUSY' => __( 'Another operation or backup is active. Resume or cancel your existing backup, or retry after the other operation finishes.', 'filewarden' ),
			'FWB_DATABASE' => __( 'The required database tables are missing, nontransactional or their engines cannot be verified. Check the database status in Storage and security.', 'filewarden' ),
			'FWB_STORAGE_VERIFICATION' => __( 'The HTTPS protection check of private storage failed. Check the storage status in Storage and security; backup cannot bypass this check.', 'filewarden' ),
			'FWB_STORAGE_WRITE' => __( 'The server could not create or save private backup files. Ask your host to check storage permissions and quotas.', 'filewarden' ),
			'FWB_SPACE_UNKNOWN' => __( 'The server could not determine free backup space. Ask your host to check access to disk-space information.', 'filewarden' ),
			'FWB_SPACE_LOW' => __( 'There is not enough free space for this backup operation and its safety reserve.', 'filewarden' ),
			'FWB_LIMIT' => __( 'A backup metadata or upload limit was reached. Check the documented limits; the server upload limit applies to restoration.', 'filewarden' ),
			'FWB_METADATA' => __( 'Library metadata is incompatible or inconsistent, or a purge is incomplete. Keep your documents and report this reference for diagnosis.', 'filewarden' ),
			'FWB_INTEGRITY' => __( 'The package or a source file failed verification. For restoration, check the backup password and use the original unmodified backup. No integrity check can be bypassed.', 'filewarden' ),
			'FWB_MAPPINGS' => __( 'Select an existing destination role and creator for every source entry before confirming restoration.', 'filewarden' ),
			'FWB_TARGET_NOT_EMPTY' => __( 'Restoration requires an empty destination library, including trash. This requirement does not apply to creating a backup.', 'filewarden' ),
			'FWB_EXPIRED' => __( 'The backup job has expired. Refresh its status and start again after temporary files have been cleaned.', 'filewarden' ),
			'FWB_CHECKPOINT' => __( 'The private backup checkpoint cannot be verified or resumed. Keep existing files and report this reference for diagnosis.', 'filewarden' ),
			default => __( 'The backup operation failed unexpectedly. Keep your documents and report this reference and the operation you were performing.', 'filewarden' ),
		};
		return array(
			'code'    => $code,
			/* translators: 1: safe explanation, 2: non-sensitive support reference. */
			'message' => sprintf( __( '%1$s Reference: %2$s.', 'filewarden' ), $message, $code ),
		);
	}
}
