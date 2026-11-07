<?php
/** Build an explicit runtime allowlist; never package development tools or secrets. */
$root = dirname( __DIR__ );
$entrypoint = file_get_contents( $root . '/filewarden.php' );
if ( ! is_string( $entrypoint ) || ! preg_match( '/^ \* Version: ([0-9]+\.[0-9]+\.[0-9]+(?:-[a-zA-Z0-9]+(?:[.-][a-zA-Z0-9]+)*)?)\s*$/m', $entrypoint, $matches ) ) {
	throw new RuntimeException( 'Invalid plugin version.' );
}
$version = $matches[1];
if ( ! str_contains( $entrypoint, "define( 'FILEWARDEN_VERSION', '$version' );" ) ) {
	throw new RuntimeException( 'Plugin header and runtime versions differ.' );
}
if ( isset( $argv[1] ) && 'v' . $version !== $argv[1] ) {
	throw new RuntimeException( 'Git tag must match the plugin version: v' . $version );
}
$archive = 'filewarden-' . $version . '.zip';
$output = $root . '/build';
if ( ! is_dir( $output ) && ! mkdir( $output, 0755 ) ) {
	throw new RuntimeException( 'Cannot create build directory.' );
}
$zip = new ZipArchive();
if ( true !== $zip->open( $output . '/' . $archive, ZipArchive::CREATE | ZipArchive::OVERWRITE ) ) {
	throw new RuntimeException( 'Cannot create ZIP.' );
}
$files = array( 'filewarden.php', 'uninstall.php', 'readme.txt', 'README.md', 'LICENSE', 'docs/setup.md', 'assets/media.js', 'assets/admin.js', 'assets/admin.css', 'assets/preview.js', 'assets/viewer.mjs', 'assets/viewer.css' );
foreach ( new RecursiveIteratorIterator( new RecursiveDirectoryIterator( $root . '/assets/pdfjs', FilesystemIterator::SKIP_DOTS ) ) as $file ) {
	if ( $file->isFile() && ! $file->isLink() ) {
		$files[] = str_replace( '\\', '/', substr( $file->getPathname(), strlen( $root ) + 1 ) );
	}
}
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
echo "Created build/$archive\n";
