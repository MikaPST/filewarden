<?php
use FileWarden\PublicationLogin;
use PHPUnit\Framework\TestCase;

final class PublicationLoginTest extends TestCase {
    public function test_native_mode_discards_custom_destination_without_changing_wordpress_behavior(): void {
        self::assertSame(['mode'=>'wordpress','url'=>''],PublicationLogin::validate(['mode'=>'wordpress','url'=>'javascript:alert(1)']));
    }

    public function test_complete_custom_addresses_preserve_query_fragment_and_external_identity_provider(): void {
        foreach (['https://example.test/connexion/','https://identity.example.test/login?client=site&return=%2Fdocuments#login','http://127.0.0.1:8096/connexion/','HTTPS://example.test/login'] as $url) {
            self::assertSame(['mode'=>'custom','url'=>$url],PublicationLogin::validate(['mode'=>'custom','url'=>$url]));
        }
    }

    public function test_unsafe_or_ambiguous_custom_addresses_are_refused(): void {
        foreach (['javascript:alert(1)','data:text/html,<script>alert(1)</script>','//example.test/login','/connexion/','https://user:password@example.test/','https://example.test/\evil',"https://example.test/\r\nLocation: /",'https://example.test/%0afoo','https://example.test/'.str_repeat('x',2048),'',[],null] as $url) {
            try { PublicationLogin::validate(['mode'=>'custom','url'=>$url]);self::fail('Unsafe address accepted'); }
            catch (InvalidArgumentException $error) { self::assertSame('Invalid login destination.',$error->getMessage()); }
        }
    }

    public function test_browser_flags_cannot_invent_a_login_mode(): void {
        foreach (['external',true,0,[],null] as $mode) {
            try { PublicationLogin::validate(['mode'=>$mode,'url'=>'https://example.test/login']);self::fail('Invalid mode accepted'); }
            catch (InvalidArgumentException $error) { self::assertSame('Invalid login destination.',$error->getMessage()); }
        }
    }
}
