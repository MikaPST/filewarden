<?php
namespace FileWarden\Tests;

use FileWarden\ByteRange;
use PHPUnit\Framework\TestCase;

final class ByteRangeTest extends TestCase {
	/** @dataProvider valid_ranges */
	public function test_valid_ranges( $header, $expected ): void {
		self::assertSame( $expected, ByteRange::parse( $header, 100 ) );
	}
	public static function valid_ranges(): array {
		return array( array( 'bytes=0-0', array( 0, 0 ) ), array( 'bytes=90-', array( 90, 99 ) ), array( 'bytes=-10', array( 90, 99 ) ), array( 'bytes=0-200', array( 0, 99 ) ), array( 'bytes=-200', array( 0, 99 ) ) );
	}
	/** @dataProvider invalid_ranges */
	public function test_invalid_ranges( $header ): void {
		$this->expectException( \InvalidArgumentException::class );
		ByteRange::parse( $header, 100 );
	}
	public static function invalid_ranges(): array {
		return array_map( fn( $value ) => array( $value ), array( 'bytes=100-', 'bytes=50-10', 'bytes=-0', 'bytes=-', 'bytes=0-1,5-9', 'items=0-1', "bytes=0-1\r\n", 'bytes=9999999999999999999999999-', array() ) );
	}
	public function test_empty_file_rejects_range(): void {
		$this->expectException( \InvalidArgumentException::class );
		ByteRange::parse( 'bytes=0-', 0 );
	}
}
