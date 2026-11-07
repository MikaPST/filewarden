<?php
/** Exercise WordPress's actual Sodium fallback without the native extension. */
if ( extension_loaded( 'sodium' ) || ! isset( $argv[1] ) ) {
	throw new RuntimeException( 'Run with php -n and the WordPress sodium_compat autoloader path.' );
}
require $argv[1];
require __DIR__ . '/bootstrap.php';

foreach ( array( 65535, 65536, 65537, 150000 ) as $size ) {
	$input = fopen( 'php://memory', 'w+b' );
	$cipher = fopen( 'php://memory', 'w+b' );
	$output = fopen( 'php://memory', 'w+b' );
	try {
		$bytes = random_bytes( $size );
		$key = sodium_crypto_secretstream_xchacha20poly1305_keygen();
		fwrite( $input, $bytes );
		rewind( $input );
		\FileWarden\EncryptedStream::transfer( $input, $cipher, $size, $key, true );
		rewind( $cipher );
		\FileWarden\EncryptedStream::transfer( $cipher, $output, $size, $key, false );
		rewind( $output );
		if ( stream_get_contents( $output ) !== $bytes ) {
			throw new RuntimeException( 'Fallback roundtrip mismatch.' );
		}
		fseek( $cipher, 40 );
		$byte = fread( $cipher, 1 );
		fseek( $cipher, 40 );
		fwrite( $cipher, $byte ^ "\x01" );
		rewind( $cipher );
		$rejected = false;
		try {
			\FileWarden\EncryptedStream::transfer( $cipher, $output, $size, $key, false );
		} catch ( RuntimeException $error ) {
			$rejected = true;
		}
		if ( ! $rejected ) {
			throw new RuntimeException( 'Tampered fallback ciphertext accepted.' );
		}
	} finally {
		fclose( $input );
		fclose( $cipher );
		fclose( $output );
	}
}
echo "WordPress Sodium fallback: frame boundaries, roundtrip and tampering passed.\n";
