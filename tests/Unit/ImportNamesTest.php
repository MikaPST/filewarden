<?php
use FileWarden\ImportNames;
use PHPUnit\Framework\TestCase;

final class ImportNamesTest extends TestCase {
    public function test_numbered_copy_preserves_extension_and_avoids_used_names():void {
        self::assertSame('document-3.PDF',ImportNames::available('document.PDF',fn($name)=>$name==='document-2.PDF'));
        self::assertSame('README-2',ImportNames::available('README',fn()=>false));
    }
    public function test_long_unicode_stem_stays_valid_and_within_database_limit():void {
        $name=ImportNames::available(str_repeat('é',125).'.pdf',fn()=>false);
        self::assertLessThanOrEqual(255,strlen($name));self::assertSame(1,preg_match('//u',$name));self::assertStringEndsWith('-2.pdf',$name);
    }
    public function test_exhausted_names_refuse_instead_of_overwriting():void {
        $this->expectException(InvalidArgumentException::class);
        ImportNames::available('document.pdf',fn()=>true);
    }
}
