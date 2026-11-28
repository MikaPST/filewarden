<?php
use FileWarden\AuditIp;
use FileWarden\AuditEvents;
use FileWarden\PublicationLogin;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\RunInSeparateProcess;
use PHPUnit\Framework\Attributes\PreserveGlobalState;

final class AuditIpTest extends TestCase {
    public function test_forwarded_headers_require_an_explicit_trusted_server_hop(): void {
        self::assertSame('203.0.113.5',AuditIp::resolve('203.0.113.5','198.51.100.9'));
        self::assertSame('203.0.113.5',AuditIp::resolve('203.0.113.5','198.51.100.9',['10.0.0.0/8']));
        self::assertSame('203.0.113.22',AuditIp::resolve('10.0.0.2','198.51.100.9, 203.0.113.22, 10.0.0.1',['10.0.0.0/8']));
        self::assertSame('10.0.0.2',AuditIp::resolve('10.0.0.2',"203.0.113.2\r\nforged: yes",['10.0.0.0/8']));
        self::assertSame('10.0.0.2',AuditIp::resolve('10.0.0.2',implode(',',array_fill(0,17,'203.0.113.2')),['10.0.0.0/8']));
        self::assertSame('',AuditIp::resolve('invalid','198.51.100.9'));
    }
    public function test_ipv4_ipv6_and_cidr_boundaries_are_strict(): void {
        self::assertTrue(AuditIp::trusted('10.20.30.40','10.0.0.0/8'));
        self::assertFalse(AuditIp::trusted('11.20.30.40','10.0.0.0/8'));
        self::assertTrue(AuditIp::trusted('2001:db8::1234','2001:db8::/32'));
        self::assertFalse(AuditIp::trusted('2001:db9::1','2001:db8::/32'));
        self::assertFalse(AuditIp::trusted('2001:db8::1','2001:db8::/129'));
        self::assertFalse(AuditIp::trusted('127.0.0.1','::1/128'));
        self::assertSame('2001:db8::1',AuditIp::resolve('2001:0db8:0000:0000:0000:0000:0000:0001','bad'));
    }
    public function test_unknown_events_never_imply_completion_and_empty_choices_stay_empty(): void {
        self::assertSame(['other','unknown'],AuditEvents::classify('unknown_requested'));
        self::assertSame(['download','started'],AuditEvents::classify('download_started'));
        self::assertSame(['copy','success'],AuditEvents::classify('document_copy_completed'));
        self::assertSame([],AuditEvents::selection(['none'],['copy'=>'Copy','move'=>'Move']));
        self::assertSame(['copy'],AuditEvents::selection(['none','copy','copy'],['copy'=>'Copy','move'=>'Move']));
        self::assertSame(['copy','move'],AuditEvents::selection('', ['copy'=>'Copy','move'=>'Move']));
        $this->expectException(InvalidArgumentException::class);
        AuditEvents::selection(['copy',["' OR 1=1"]],['copy'=>'Copy','move'=>'Move']);
    }
    public function test_popup_ids_are_canonical_and_encoded_actions_are_rejected(): void {
        self::assertSame(['mode'=>'elementor','url'=>'','popup_id'=>1252],PublicationLogin::validate(['mode'=>'elementor','popup_id'=>'1252','url'=>'javascript:ignored']));
        foreach(['0','-1','01','1e3','2147483648','#elementor-action%3Aaction%3Dpopup%3Aopen',[],true]as$id){
            try{PublicationLogin::validate(['mode'=>'elementor','popup_id'=>$id]);self::fail('Invalid popup accepted');}
            catch(InvalidArgumentException $error){self::assertSame('Invalid Elementor popup identifier.',$error->getMessage());}
        }
    }
    #[RunInSeparateProcess]
    #[PreserveGlobalState(false)]
    public function test_expired_ips_are_hidden_without_waiting_for_cron_or_extending_retention(): void {
        if(!defined('DAY_IN_SECONDS'))define('DAY_IN_SECONDS',86400);
        function get_option($key,$default=false){return $GLOBALS['fw_test_options'][$key]??$default;}
        $GLOBALS['fw_test_options']=['filewarden_audit_days'=>365,'filewarden_audit_ip_days'=>30];
        $row=['actor_ip'=>'203.0.113.8','created_at'=>gmdate('Y-m-d H:i:s',time()-86400),'ip_expires_at'=>gmdate('Y-m-d H:i:s',time()+86400)];
        self::assertSame('203.0.113.8',AuditIp::visible($row));
        $row['ip_expires_at']=gmdate('Y-m-d H:i:s',time()-1);self::assertSame('',AuditIp::visible($row));
        $row['ip_expires_at']=gmdate('Y-m-d H:i:s',time()+86400);$row['created_at']=gmdate('Y-m-d H:i:s',time()-31*86400);self::assertSame('',AuditIp::visible($row));
        $GLOBALS['fw_test_options']['filewarden_audit_days']=1;$row['created_at']=gmdate('Y-m-d H:i:s',time()-2*86400);self::assertSame('',AuditIp::visible($row));
        $row['actor_ip']='malicious"><script>';self::assertSame('',AuditIp::visible($row));
    }
}
