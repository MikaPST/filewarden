<?php
/** Bounded content validation for attachment-only document formats.
 *
 * @package FileWarden
 */

namespace FileWarden;

// phpcs:disable WordPress.NamingConventions.ValidVariableName -- ZipArchive exposes the fixed numFiles property.
// phpcs:disable WordPress.WP.AlternativeFunctions -- ZIP streams and anonymous private inspection require native byte-exact I/O.

/** Never execute content or extract archive members into the web filesystem. */
final class FileValidator {
	const MIMES        = array(
		'pdf'  => 'application/pdf',
		'jpg'  => 'image/jpeg',
		'jpeg' => 'image/jpeg',
		'png'  => 'image/png',
		'txt'  => 'text/plain',
		'csv'  => 'text/csv',
		'zip'  => 'application/zip',
		'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
		'xlsx' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
		'pptx' => 'application/vnd.openxmlformats-officedocument.presentationml.presentation',
		'odt'  => 'application/vnd.oasis.opendocument.text',
		'ods'  => 'application/vnd.oasis.opendocument.spreadsheet',
		'odp'  => 'application/vnd.oasis.opendocument.presentation',
	);
	const MAX_ENTRIES  = 1000;
	const MAX_EXPANDED = 104857600;
	const MAX_MEMBER   = 52428800;
	const MAX_RATIO    = 100;

	/** Validate one local file and return its canonical attachment MIME.
	 *
	 * @param string     $path Local upload path.
	 * @param string     $extension Allowlisted extension.
	 * @param bool       $inside_archive Whether nested ZIPs must be rejected.
	 * @param array|null $budget Shared decompression and entry limits.
	 * @return string Canonical MIME.
	 * @throws \InvalidArgumentException For unsafe or mismatched content.
	 */
	public static function validate( $path, $extension, $inside_archive = false, &$budget = null ) {
		$budget ??= array(
			'bytes'   => self::MAX_EXPANDED,
			'entries' => self::MAX_ENTRIES,
		);
		if ( ! isset( self::MIMES[ $extension ] ) || ! is_file( $path ) ) {
			throw new \InvalidArgumentException( 'Unsupported file type.' ); }
		$mime = ( new \finfo( FILEINFO_MIME_TYPE ) )->file( $path );
		if ( in_array( $extension, array( 'pdf', 'jpg', 'jpeg', 'png' ), true ) ) {
			if ( self::MIMES[ $extension ] !== $mime ) {
				throw new \InvalidArgumentException( 'Unsupported file type.' ); }
			if ( 'pdf' === $extension ) {
				// phpcs:ignore WordPress.WP.AlternativeFunctions -- Inspect a local upload signature.
				if ( '%PDF-' !== file_get_contents( $path, false, null, 0, 5 ) ) {
					throw new \InvalidArgumentException( 'Invalid PDF.' ); }
			} elseif ( false === getimagesize( $path ) ) {
				throw new \InvalidArgumentException( 'Invalid image.' ); }
		} elseif ( in_array( $extension, array( 'txt', 'csv' ), true ) ) {
			if ( ! in_array( $mime, array( 'text/plain', 'text/csv', 'text/x-csv', 'application/csv', 'text/tab-separated-values' ), true ) ) {
				throw new \InvalidArgumentException( 'Unsupported file type.' ); }
			// phpcs:ignore WordPress.WP.AlternativeFunctions -- Validate content before private storage.
			$text = file_get_contents( $path );
			if ( is_string( $text ) && str_starts_with( $text, "\xEF\xBB\xBF" ) ) {
				$text = substr( $text, 3 ); }
			if ( false === $text || ! mb_check_encoding( $text, 'UTF-8' ) || str_contains( $text, "\0" ) || preg_match( '/<\s*(?:\?php|script|html|svg|iframe|object|embed)\b|^#!/im', $text ) ) {
				throw new \InvalidArgumentException( 'Active text content is not allowed.' ); }
			if ( 'csv' === $extension && preg_match( '/(?:^|[\r\n,;])\s*["\x27]?\s*(?:[=+@]|-[a-zA-Z])/m', $text ) ) {
				throw new \InvalidArgumentException( 'Active spreadsheet formulas are not allowed.' ); }
		} else {
			if ( $inside_archive && 'zip' === $extension ) {
				throw new \InvalidArgumentException( 'Nested ZIP archives are not allowed.' ); }
			self::archive( $path, $extension, $budget );
		}
		return self::MIMES[ $extension ];
	}

	/** Inspect every ZIP member with bounded decompression, without extraction.
	 *
	 * @param string $path Local file.
	 * @param string $extension Archive/document type.
	 * @param array  $budget Shared decompression budget.
	 * @throws \InvalidArgumentException On invalid content or resource limits.
	 */
	private static function archive( $path, $extension, array &$budget ) {
		if ( ! class_exists( '\ZipArchive' ) ) {
			throw new \InvalidArgumentException( 'ZIP validation requires the PHP ZIP extension.' ); }
		$archive = new \ZipArchive();
		if ( true !== $archive->open( $path, \ZipArchive::RDONLY | \ZipArchive::CHECKCONS ) ) {
			throw new \InvalidArgumentException( 'Invalid ZIP container.' ); }
		try {
			if ( $archive->numFiles < 1 || $archive->numFiles > self::MAX_ENTRIES ) {
				throw new \InvalidArgumentException( 'Archive entry limit exceeded.' ); }
			$names  = array();
			$total  = 0;
			$office = 'zip' !== $extension;
			for ( $index = 0; $index < $archive->numFiles; ++$index ) {
				$entry = $archive->statIndex( $index );
				$name  = $entry['name'];
				if ( preg_match( '#(?:^/|^[a-z]:|\\\\|\x00|%2e|%2f|%5c|(?:^|/)\.{1,2}(?:/|$))#i', $name ) || isset( $names[ strtolower( $name ) ] ) ) {
					throw new \InvalidArgumentException( 'Unsafe archive path.' ); }
				$names[ strtolower( $name ) ] = true;
				$archive->getExternalAttributesIndex( $index, $system, $attributes );
				if ( ( ( $attributes >> 16 ) & 0170000 ) === 0120000 || ! empty( $entry['encryption_method'] ) ) {
					throw new \InvalidArgumentException( 'Encrypted archives and symbolic links are not allowed.' ); }
				$total           += $entry['size'];
				$budget['bytes'] -= $entry['size'];
				--$budget['entries'];
				if ( $budget['bytes'] < 0 || $budget['entries'] < 0 ) {
					throw new \InvalidArgumentException( 'Archive expansion limit exceeded.' ); }
				if ( $entry['size'] > self::MAX_MEMBER || $total > self::MAX_EXPANDED || ( $entry['size'] > 0 && $entry['size'] / max( 1, $entry['comp_size'] ) > self::MAX_RATIO ) ) {
					throw new \InvalidArgumentException( 'Archive expansion limit exceeded.' ); }
				if ( str_ends_with( $name, '/' ) ) {
					continue; }
				$member_extension = strtolower( pathinfo( $name, PATHINFO_EXTENSION ) );
				if ( preg_match( '#(?:vba|macros?|activex|embeddings?|scripts?)(?:/|\.|$)|\.(?:bin|exe|dll|so|js|mjs|php\d*|phtml|html?|svg|vbs|ps1|bat|cmd|sh|py|jar|com|scr|lnk)$#i', $name ) ) {
					throw new \InvalidArgumentException( 'Active archive content is not allowed.' ); }
				$stream = $archive->getStream( $name );
				if ( false === $stream ) {
					throw new \InvalidArgumentException( 'Unreadable archive member.' ); }
				// phpcs:ignore WordPress.WP.AlternativeFunctions -- Anonymous temporary files are checked against public roots before writing.
				$temp = tmpfile();
				if ( false === $temp ) {
					fclose( $stream );
					throw new \InvalidArgumentException( 'Private archive inspection unavailable.' ); }
				try {
					$temp_path = stream_get_meta_data( $temp )['uri'];
					self::private_temporary( $temp_path );
					$hash = hash_init( 'crc32b' );
					$read = 0;
					while ( ! feof( $stream ) ) {
						// phpcs:ignore WordPress.WP.AlternativeFunctions -- Bounded archive inspection, no public extraction.
						$chunk = fread( $stream, 65536 );
						if ( false === $chunk ) {
							throw new \InvalidArgumentException( 'Unreadable archive member.' ); }
						$read += strlen( $chunk );
						if ( $read > $entry['size'] || $read > self::MAX_MEMBER ) {
							throw new \InvalidArgumentException( 'Archive expansion limit exceeded.' ); }
						hash_update( $hash, $chunk );
						// phpcs:ignore WordPress.WP.AlternativeFunctions -- Write to a verified private anonymous stream.
						if ( strlen( $chunk ) !== fwrite( $temp, $chunk ) ) {
							throw new \InvalidArgumentException( 'Archive inspection write failed.' ); }
					}
					if ( $read !== $entry['size'] || sprintf( '%08x', $entry['crc'] ) !== hash_final( $hash ) ) {
						throw new \InvalidArgumentException( 'Archive integrity check failed.' ); }
					fflush( $temp );
					if ( $office ) {
						if ( ! in_array( $member_extension, array( 'xml', 'rels', 'jpg', 'jpeg', 'png', 'gif', 'wmf', 'emf', 'ttf', 'odttf', 'fnt', 'rdf', '' ), true ) ) {
							throw new \InvalidArgumentException( 'Unsupported Office container member.' ); }
						if ( in_array( $member_extension, array( 'xml', 'rels', 'rdf' ), true ) ) {
							// phpcs:ignore WordPress.WP.AlternativeFunctions -- Scan XML as text; never resolve entities or execute macros.
							$xml = file_get_contents( $temp_path );
							if ( preg_match( '/<!DOCTYPE|<!ENTITY|TargetMode\s*=\s*["\x27]External|office:script|macroEnabled|vbaProject/i', $xml ) ) {
								throw new \InvalidArgumentException( 'Active Office content is not allowed.' ); }
							self::office_xml( $xml );
						}
					} else {
						self::validate( $temp_path, $member_extension, true, $budget ); }
				} finally {
					// phpcs:ignore WordPress.WP.AlternativeFunctions -- Closing removes the private temporary bytes, including after failure.
					fclose( $temp );
					fclose( $stream );
				}
			}
			if ( $office ) {
				$required = array(
					'docx' => 'word/document.xml',
					'xlsx' => 'xl/workbook.xml',
					'pptx' => 'ppt/presentation.xml',
					'odt'  => 'content.xml',
					'ods'  => 'content.xml',
					'odp'  => 'content.xml',
				);
				if ( ! isset( $names[ $required[ $extension ] ] ) ) {
					throw new \InvalidArgumentException( 'Office container does not match its extension.' ); }
				$main  = self::office_xml( $archive->getFromName( $required[ $extension ] ) );
				$roots = array(
					'docx' => 'document',
					'xlsx' => 'workbook',
					'pptx' => 'presentation',
					'odt'  => 'document-content',
					'ods'  => 'document-content',
					'odp'  => 'document-content',
				);
				if ( $main->documentElement->localName !== $roots[ $extension ] ) {
					throw new \InvalidArgumentException( 'Office container does not match its extension.' ); }
				if ( in_array( $extension, array( 'odt', 'ods', 'odp' ), true ) ) {
					if ( self::MIMES[ $extension ] !== $archive->getFromName( 'mimetype' ) ) {
						throw new \InvalidArgumentException( 'Office container does not match its extension.' ); }
				} elseif ( ! isset( $names['[content_types].xml'] ) ) {
					throw new \InvalidArgumentException( 'Invalid Office container.' ); }
			}
		} finally {
			$archive->close(); }
	}

	/** Parse package XML without DTD/entities and reject executable Office features.
	 *
	 * @param string $xml Package member.
	 * @return \DOMDocument Parsed structure.
	 * @throws \InvalidArgumentException For invalid or active XML.
	 */
	private static function office_xml( $xml ) {
		if ( ! class_exists( '\DOMDocument' ) || ! is_string( $xml ) || preg_match( '/<!DOCTYPE|<!ENTITY/i', $xml ) ) {
			throw new \InvalidArgumentException( 'Invalid Office XML.' ); }
		$previous = libxml_use_internal_errors( true );
		try {
			$document                     = new \DOMDocument();
			$document->resolveExternals   = false;
			$document->substituteEntities = false;
			$document->validateOnParse    = false;
			if ( ! $document->loadXML( $xml, LIBXML_NONET ) ) {
				throw new \InvalidArgumentException( 'Invalid Office XML.' ); }
			if ( null !== $document->doctype ) {
				throw new \InvalidArgumentException( 'Invalid Office XML.' ); }
			foreach ( $document->getElementsByTagName( '*' ) as $node ) {
				$instruction = '';
				foreach ( $node->attributes as $attribute ) {
					if ( 'TargetMode' === $attribute->localName && 0 === strcasecmp( $attribute->value, 'External' ) ) {
						throw new \InvalidArgumentException( 'Active Office content is not allowed.' ); }
					if ( preg_match( '/macroEnabled|vbaProject|ActiveX/i', $attribute->value ) ) {
						throw new \InvalidArgumentException( 'Active Office content is not allowed.' ); }
					if ( 'instr' === $attribute->localName ) {
						$instruction = $attribute->value; }
				}
				if ( in_array( $node->localName, array( 'script', 'scripts', 'event-listener', 'event-listeners' ), true ) || str_contains( (string) $node->namespaceURI, 'opendocument:xmlns:script' ) ) {
					throw new \InvalidArgumentException( 'Active Office content is not allowed.' ); }
				if ( in_array( $node->localName, array( 'instrText', 'fldSimple', 'f' ), true ) && preg_match( '/DDE(?:AUTO)?\b|INCLUDETEXT|WEBSERVICE\s*\(|HYPERLINK\s*\(|RTD\s*\(|\|/i', $node->textContent . ' ' . $instruction ) ) {
					throw new \InvalidArgumentException( 'Active Office content is not allowed.' ); }
			}
			return $document;
		} finally {
			libxml_clear_errors();
			libxml_use_internal_errors( $previous ); }
	}

	/** Refuse inspection files that could be reachable below a public root.
	 *
	 * @param string $path Generated temporary filename.
	 * @throws \InvalidArgumentException On an unsafe temporary directory.
	 */
	private static function private_temporary( $path ) {
		$roots = array();
		if ( defined( 'ABSPATH' ) ) {
			$roots[] = ABSPATH; }
		if ( defined( 'WP_CONTENT_DIR' ) ) {
			$roots[] = WP_CONTENT_DIR; }
		if ( isset( $_SERVER['DOCUMENT_ROOT'] ) && is_string( $_SERVER['DOCUMENT_ROOT'] ) && '' !== $_SERVER['DOCUMENT_ROOT'] ) {
			// phpcs:ignore WordPress.Security.ValidatedSanitizedInput -- Trusted server configuration; keep exact paths for canonical checks.
			$roots[] = $_SERVER['DOCUMENT_ROOT']; }
		foreach ( $roots as $root ) {
			$canonical = realpath( $root );
			if ( false === $canonical || str_starts_with( strtolower( str_replace( '\\', '/', $path ) ), rtrim( strtolower( str_replace( '\\', '/', $canonical ) ), '/' ) . '/' ) ) {
				throw new \InvalidArgumentException( 'Private archive inspection unavailable.' ); }
		}
	}
}
