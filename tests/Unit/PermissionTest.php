<?php
namespace FileWarden\Tests;

use FileWarden\PermissionService;
use FileWarden\Policy;
use PHPUnit\Framework\TestCase;

final class PermissionTest extends TestCase {
	private function user( $roles = array(), $override = false ) {
		return new class( $roles, $override ) {
			public $roles;
			private $override;
			public function __construct( $roles, $override ) { $this->roles = $roles; $this->override = $override; }
			public function exists() { return (bool) $this->roles; }
			public function has_cap( $cap ) { return 'filewarden_read_all_files' === $cap && $this->override; }
		};
	}

	public function test_public_child_cannot_open_private_parent(): void {
		$service = new PermissionService( fn( $id ) => array( 'parent_id' => 0, 'policy' => array( 'type' => 'authenticated' ) ) );
		$doc = array( 'folder_id' => 1, 'policy' => array( 'type' => 'public' ) );
		self::assertFalse( $service->can_read( $doc, $this->user() ) );
		self::assertTrue( $service->can_read( $doc, $this->user( array( 'subscriber' ) ) ) );
	}

	public function test_all_ancestors_must_allow_user(): void {
		$folders = array(
			1 => array( 'parent_id' => 0, 'policy' => array( 'type' => 'roles', 'roles' => array( 'editor' ) ) ),
			2 => array( 'parent_id' => 1, 'policy' => array( 'type' => 'roles', 'roles' => array( 'subscriber' ) ) ),
		);
		$service = new PermissionService( fn( $id ) => $folders[ $id ] ?? null );
		$doc = array( 'folder_id' => 2, 'policy' => array( 'type' => 'public' ) );
		self::assertFalse( $service->can_read( $doc, $this->user( array( 'subscriber' ) ) ) );
		self::assertFalse( $service->can_read( $doc, $this->user( array( 'editor' ) ) ) );
		self::assertTrue( $service->can_read( $doc, $this->user( array( 'subscriber', 'editor' ) ) ) );
	}

	public function test_read_override_is_explicit_not_role_name(): void {
		$service = new PermissionService( fn() => null );
		$doc = array( 'policy' => array( 'type' => 'roles', 'roles' => array( 'subscriber' ) ) );
		self::assertFalse( $service->can_read( $doc, $this->user( array( 'administrator' ) ) ) );
		self::assertTrue( $service->can_read( $doc, $this->user( array( 'administrator' ), true ) ) );
	}

	public function test_missing_parent_and_cycle_fail_closed_even_with_override(): void {
		$doc = array( 'folder_id' => 1, 'policy' => array( 'type' => 'public' ) );
		$missing = new PermissionService( fn() => null );
		$cycle = new PermissionService( fn() => array( 'parent_id' => 1, 'policy' => array( 'type' => 'public' ) ) );
		self::assertFalse( $missing->can_read( $doc, $this->user( array( 'administrator' ), true ) ) );
		self::assertFalse( $cycle->can_read( $doc, $this->user( array( 'administrator' ), true ) ) );
	}

	public function test_unknown_and_missing_policies_deny_access(): void {
		$service = new PermissionService( fn() => null );
		self::assertFalse( $service->can_read( array(), $this->user( array( 'editor' ) ) ) );
		self::assertFalse( Policy::allows( array( 'type' => 'unknown' ), true, array( 'editor' ) ) );
	}

	public function test_valid_policy_is_normalized(): void {
		self::assertSame( array( 'type' => 'roles', 'roles' => array( 'editor' ) ), Policy::validate( array( 'type' => 'roles', 'roles' => array( 'editor', 'editor' ) ), array( 'editor' ) ) );
	}

	/** @dataProvider invalid_policies */
	public function test_invalid_policy_is_rejected( $policy ): void {
		$this->expectException( \InvalidArgumentException::class );
		Policy::validate( $policy, array( 'editor' ) );
	}

	public static function invalid_policies(): array {
		return array( array( null ), array( array() ), array( array( 'type' => 'roles', 'roles' => array() ) ), array( array( 'type' => 'roles', 'roles' => array( 'unknown' ) ) ), array( array( 'type' => 'public', 'roles' => array( 'editor' ) ) ), array( array( 'type' => 'public', 'override' => true ) ), array( array( 'type' => 'roles', 'roles' => 'editor' ) ) );
	}

    public function test_inherit_follows_folder_without_weakening_it(): void {
        $policy = Policy::validate( array( 'type' => 'inherit' ), array(), true );
        $document = array( 'folder_id' => 1, 'policy' => $policy );
        $public = new PermissionService( fn() => array( 'parent_id' => 0, 'policy' => array( 'type' => 'public' ) ) );
        $private = new PermissionService( fn() => array( 'parent_id' => 0, 'policy' => array( 'type' => 'authenticated' ) ) );
        self::assertTrue( $public->can_read( $document, $this->user() ) );
        self::assertFalse( $private->can_read( $document, $this->user() ) );
        self::assertTrue( $private->can_read( $document, $this->user( array( 'subscriber' ) ) ) );
        $document['policy'] = array( 'type' => 'authenticated' );
        self::assertFalse( $public->can_read( $document, $this->user() ) );
    }
    public function test_folders_cannot_select_document_inheritance(): void {
        $this->expectException( \InvalidArgumentException::class );
        Policy::validate( array( 'type' => 'inherit' ), array() );
    }
    public function test_trash_and_purging_deny_even_global_read(): void {
        $service = new PermissionService( fn() => null );
        foreach ( array( array( 'trashed_at' => '2026-01-01 00:00:00' ), array( 'purging' => 1 ) ) as $state ) {
            $record = $state + array( 'policy' => array( 'type' => 'public' ) );
            self::assertFalse( $service->can_read( $record, $this->user() ) );
            self::assertFalse( $service->can_read( $record, $this->user( array( 'administrator' ), true ) ) );
        }
    }
}
