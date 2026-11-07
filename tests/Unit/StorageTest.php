<?php
namespace FileWarden\Tests;

use FileWarden\StorageService;
use PHPUnit\Framework\TestCase;

final class StorageTest extends TestCase {
	private $base;
	protected function setUp(): void {
		$this->base = sys_get_temp_dir() . '/filewarden-test-' . bin2hex( random_bytes( 8 ) );
		mkdir( $this->base );
		mkdir( $this->base . '/public' );
		mkdir( $this->base . '/private' );
	}
	protected function tearDown(): void {
		foreach ( array( 'public', 'private' ) as $folder ) {
			foreach ( glob( $this->base . '/' . $folder . '/*' ) as $file ) { unlink( $file ); }
			rmdir( $this->base . '/' . $folder );
		}
		if ( is_file( $this->base . '/input' ) ) { unlink( $this->base . '/input' ); }
		rmdir( $this->base );
	}
	private function storage( $verified = true ): StorageService {
		return new StorageService( $this->base . '/private', array( $this->base . '/public' ), $verified );
	}
	public function test_unverified_storage_is_blocked(): void {
		$this->expectException( \RuntimeException::class );
		$this->storage( false )->root();
	}
	public function test_storage_inside_public_root_is_blocked(): void {
		$this->expectException( \RuntimeException::class );
		( new StorageService( $this->base . '/public', array( $this->base . '/public' ), true ) )->root();
	}
	public function test_storage_parent_of_public_root_is_blocked(): void {
		$this->expectException( \RuntimeException::class );
		( new StorageService( $this->base, array( $this->base . '/public' ), true ) )->root();
	}
	public function test_unknown_public_root_is_blocked(): void {
		$this->expectException( \RuntimeException::class );
		( new StorageService( $this->base . '/private', array( $this->base . '/missing' ), true ) )->root();
	}
	public function test_empty_public_root_is_blocked(): void {
		$this->expectException( \RuntimeException::class );
		( new StorageService( $this->base . '/private', array( '' ), true ) )->root();
	}
	public function test_no_public_roots_is_blocked(): void {
		$this->expectException( \RuntimeException::class );
		( new StorageService( $this->base . '/private', array(), true ) )->root();
	}
	public function test_path_boundary_does_not_confuse_siblings(): void {
		self::assertFalse( StorageService::within( '/site/private-files', '/site/private' ) );
		self::assertTrue( StorageService::within( '/site/private/file', '/site/private' ) );
	}
	public function test_traversal_is_rejected(): void {
		$this->expectException( \RuntimeException::class );
		$this->storage()->path( '../secret' );
	}
	public function test_roundtrip_and_idempotent_removal(): void {
		file_put_contents( $this->base . '/input', 'test content' );
		$storage = $this->storage();
		$key = $storage->put( $this->base . '/input', 12 );
		self::assertMatchesRegularExpression( '/^[a-f0-9]{64}$/D', $key );
		$handle = $storage->open( $key, 12 );
		self::assertSame( 'test content', stream_get_contents( $handle ) );
		fclose( $handle );
		$storage->remove( $key );
		$storage->remove( $key );
		self::assertFileDoesNotExist( $storage->path( $key ) );
	}
	public function test_incomplete_write_is_removed(): void {
		file_put_contents( $this->base . '/input', 'short' );
		try {
			$this->storage()->put( $this->base . '/input', 10 );
			self::fail( 'Expected a failed write.' );
		} catch ( \RuntimeException $error ) {
			self::assertSame( array(), glob( $this->base . '/private/*' ) );
		}
	}
	public function test_wrong_size_fails_integrity_check(): void {
		file_put_contents( $this->base . '/input', 'content' );
		$key = $this->storage()->put( $this->base . '/input', 7 );
		$this->expectException( \RuntimeException::class );
		$this->storage()->open( $key, 8 );
	}
}
