<?php
namespace FileWarden\Tests;

use FileWarden\FileIO;
use PHPUnit\Framework\TestCase;

final class FileIOTest extends TestCase {
	public function test_temporary_output_is_private_and_removed_on_close(): void {
		$stream = FileIO::temporary( array( __DIR__ ) );
		$path = stream_get_meta_data($stream)['uri'];
		self::assertTrue(is_file($path));
		fwrite($stream,'private synthetic output');
		fclose($stream);
		clearstatcache(true,$path);
		self::assertFalse(is_file($path));
	}
	public function test_temporary_output_below_a_public_root_is_refused(): void {
		$this->expectException(\RuntimeException::class);
		FileIO::temporary(array(sys_get_temp_dir()));
	}
	public function test_missing_file_does_not_expose_a_warning_or_path(): void {
		$warnings = array();
		set_error_handler( static function ( $level, $message ) use ( &$warnings ) { $warnings[] = $message; return true; } );
		try {
			$result = FileIO::attempt( fn() => fopen( sys_get_temp_dir() . '/missing-' . bin2hex( random_bytes( 16 ) ), 'rb' ) );
			self::assertFalse( $result );
			self::assertSame( array(), $warnings );
			trigger_error( 'outer handler restored', E_USER_WARNING );
			self::assertSame( array( 'outer handler restored' ), $warnings );
		} finally {
			restore_error_handler();
		}
	}
}
