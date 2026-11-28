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
$files = array( 'filewarden.php', 'uninstall.php', 'readme.txt', 'README.md', 'LICENSE', 'docs/setup.md', 'docs/deployment.md', 'languages/filewarden-fr_FR.mo', 'languages/filewarden-fr_FR.po', 'languages/filewarden-en_US.mo', 'languages/filewarden-en_US.po', 'assets/publication-frame.js', 'assets/publication.css', 'assets/media.js', 'assets/admin.js', 'assets/admin.css', 'assets/admin-controls.css', 'assets/workspace.js', 'assets/notices.js', 'assets/settings.js', 'assets/upload-queue.js', 'assets/publisher.js', 'assets/preview.js', 'assets/viewer.mjs', 'assets/viewer.css' );
foreach ( new RecursiveIteratorIterator( new RecursiveDirectoryIterator( $root . '/assets/pdfjs', FilesystemIterator::SKIP_DOTS ) ) as $file ) {
	if ( $file->isFile() && ! $file->isLink() ) {
		$files[] = str_replace( '\\', '/', substr( $file->getPathname(), strlen( $root ) + 1 ) );
	}
}
foreach ( glob( $root . '/assets/icons/*.svg' ) as $file ) {
$files[] = 'assets/icons/' . basename( $file );
}
$files[] = 'assets/icons/LICENSE-lucide.txt';
$files[] = 'assets/appearance.js';
$files[] = 'assets/library.js';
$files[] = 'assets/action-dialog.js';
$files[] = 'assets/backup.js';
$files[] = 'assets/diagnostic.js';
$files[] = 'assets/wordpress-tables.js';
$files[] = 'assets/audit-filters.js';
$files[] = 'docs/backup.md';
$files[] = 'docs/releases/v' . $version . '.md';
$files[] = 'assets/illustrations/folder-welcome.png';
$files[] = 'assets/branding/filewarden-logo-512.png';
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

