<?php
/** Build an explicit runtime allowlist; never package development tools or secrets. */
$root = dirname( __DIR__ );
$output = $root . '/build';
if ( ! is_dir( $output ) && ! mkdir( $output, 0755 ) ) {
	throw new RuntimeException( 'Cannot create build directory.' );
}
$zip = new ZipArchive();
if ( true !== $zip->open( $output . '/filewarden-0.1.0-dev.zip', ZipArchive::CREATE | ZipArchive::OVERWRITE ) ) {
	throw new RuntimeException( 'Cannot create ZIP.' );
}
$files = array( 'filewarden.php', 'uninstall.php', 'readme.txt', 'README.md', 'LICENSE', 'docs/setup.md' );
foreach ( glob( $root . '/includes/*.php' ) as $file ) {
	$files[] = 'includes/' . basename( $file );
}
foreach ( $files as $file ) {
	if ( ! is_file( $root . '/' . $file ) || ! $zip->addFile( $root . '/' . $file, 'filewarden/' . $file ) ) {
		throw new RuntimeException( 'Missing or unreadable runtime file: ' . $file );
	}
}
if ( ! $zip->close() ) {
	throw new RuntimeException( 'ZIP finalization failed.' );
}
echo "Created build/filewarden-0.1.0-dev.zip\n";
