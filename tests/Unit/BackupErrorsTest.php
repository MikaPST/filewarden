<?php
use FileWarden\BackupErrors;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\RunInSeparateProcess;
use PHPUnit\Framework\Attributes\PreserveGlobalState;

final class BackupErrorsTest extends TestCase {
    public function test_failures_have_specific_safe_categories(): void {
        $cases=[
            'Access denied.'=>'FWB_PERMISSION',
            'Invalid backup phrase.'=>'FWB_PHRASE',
            'Sodium is required.'=>'FWB_SODIUM_UNAVAILABLE',
            'Sodium password derivation failed.'=>'FWB_SODIUM_DERIVATION',
            'Another library backup is active.'=>'FWB_BUSY',
            'Transactional storage required.'=>'FWB_DATABASE',
            'HTTP verification failed. The public witness must be readable and the protected witness denied.'=>'FWB_STORAGE_VERIFICATION',
            'Backup checkpoint write failed.'=>'FWB_STORAGE_WRITE',
            'Backup free space unavailable.'=>'FWB_SPACE_UNKNOWN',
            'Insufficient backup storage.'=>'FWB_SPACE_LOW',
            'Backup manifest limit exceeded.'=>'FWB_LIMIT',
            'Invalid backup manifest.'=>'FWB_METADATA',
            'Backup authentication failed.'=>'FWB_INTEGRITY',
            'Map every source role explicitly.'=>'FWB_MAPPINGS',
            'Restoration requires an empty FileWarden library.'=>'FWB_TARGET_NOT_EMPTY',
            'Backup job expired.'=>'FWB_EXPIRED',
            'Backup checkpoint invalid.'=>'FWB_CHECKPOINT',
        ];
        foreach($cases as$message=>$code)self::assertSame($code,BackupErrors::code(new RuntimeException($message)));
    }

    #[RunInSeparateProcess]
    #[PreserveGlobalState(false)]
    public function test_public_response_never_discloses_unknown_exception_or_previous_cause(): void {
        if(!function_exists('__')){function __($text,$domain=''){return $text;}}
        $secret='DO-NOT-EXPOSE-phrase /private/site/backups/hidden.state <script>';
        $error=new RuntimeException($secret,0,new RuntimeException($secret));
        $response=BackupErrors::response($error);
        self::assertSame('FWB_UNEXPECTED',$response['code']);
        self::assertSame(['code','message'],array_keys($response));
        self::assertStringNotContainsString($secret,json_encode($response));
        self::assertStringNotContainsString('/private/',json_encode($response));
        self::assertStringContainsString('FWB_UNEXPECTED',$response['message']);
        $known=BackupErrors::response(new RuntimeException('Invalid backup phrase.',0,$error));
        self::assertSame('FWB_PHRASE',$known['code']);
        self::assertStringNotContainsString($secret,json_encode($known));
    }
}
