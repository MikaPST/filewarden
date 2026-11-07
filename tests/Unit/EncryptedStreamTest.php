<?php
namespace FileWarden\Tests;

use FileWarden\EncryptedStream;
use PHPUnit\Framework\TestCase;

final class EncryptedStreamTest extends TestCase {
	protected function setUp(): void {
		if ( ! extension_loaded( 'sodium' ) ) {
			self::markTestSkipped( 'Sodium is required for managed storage.' );
		}
	}

	private function convert( $bytes, $size, $key, $encrypt ) {
		$input  = fopen( 'php://memory', 'w+b' );
		$output = fopen( 'php://memory', 'w+b' );
		try {
			fwrite( $input, $bytes );
			rewind( $input );
			EncryptedStream::transfer( $input, $output, $size, $key, $encrypt );
			rewind( $output );
			return stream_get_contents( $output );
		} finally {
			fclose( $input );
			fclose( $output );
		}
	}

	public function test_multiframe_roundtrip(): void {
		$key = random_bytes( 32 );
		$plain = str_repeat( 'Private document.', 5000 );
		$cipher = $this->convert( $plain, strlen( $plain ), $key, true );
		self::assertStringNotContainsString( 'Private document.', $cipher );
		self::assertSame( $plain, $this->convert( $cipher, strlen( $plain ), $key, false ) );
	}

	/** @dataProvider invalid_ciphertexts */
	public function test_invalid_ciphertext_fails_closed( $change ): void {
		$key = random_bytes( 32 );
		$plain = str_repeat( 'A', 65537 );
		$cipher = $this->convert( $plain, strlen( $plain ), $key, true );
		$this->expectException( \RuntimeException::class );
		if ( 'wrong key' === $change ) {
			$key = random_bytes( 32 );
		} elseif ( 'truncated' === $change ) {
			$cipher = substr( $cipher, 0, -1 );
		} elseif ( 'trailing' === $change ) {
			$cipher .= 'x';
		} else {
			$cipher[40] = $cipher[40] ^ "\x01";
		}
		$this->convert( $cipher, strlen( $plain ), $key, false );
	}

	public static function invalid_ciphertexts(): array {
		return array( array( 'wrong key' ), array( 'truncated' ), array( 'trailing' ), array( 'altered' ) );
	}
}
