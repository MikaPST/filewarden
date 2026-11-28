<?php
use FileWarden\BackupArchive;
use FileWarden\FrontendAppearance;
use FileWarden\Publication;
use PHPUnit\Framework\TestCase;

final class BackupArchiveTest extends TestCase {
    public function test_phrase_validation_counts_unicode_characters_and_keeps_key_compatibility(): void {
        $salt=str_repeat('s',16);
        foreach([str_repeat('é',16),str_repeat('🔒',16),str_repeat('a',15)."\n",str_repeat('a',1024)] as $phrase) {
            self::assertSame(sodium_crypto_pwhash(32,$phrase,$salt,BackupArchive::OPS,BackupArchive::MEM,SODIUM_CRYPTO_PWHASH_ALG_ARGON2ID13),BackupArchive::key($phrase,$salt));
        }
    }
    public function test_invalid_phrase_encoding_character_count_and_byte_limit_are_rejected(): void {
        foreach([str_repeat('é',15),str_repeat('🔒',15),str_repeat('a',15),str_repeat('a',1025),str_repeat('a',16)."\xff"] as $phrase) {
            try {BackupArchive::key($phrase,str_repeat('s',16));self::fail('Invalid phrase accepted');}
            catch(InvalidArgumentException $error) {self::assertSame('Invalid backup phrase.',$error->getMessage());}
        }
    }
    private function archive(): string {
        $stream=fopen('php://memory','w+b');$salt=random_bytes(16);$key=BackupArchive::key('a distinct test secret phrase',$salt);
        $cursor=BackupArchive::create($stream,$key,$salt);
        BackupArchive::push($stream,$cursor,'M','{"secret":"never plaintext"}');
        BackupArchive::push($stream,$cursor,'N');
        BackupArchive::push($stream,$cursor,'D',str_repeat("\x00\xFFsensitive",5000));
        BackupArchive::push($stream,$cursor,'Z');
        rewind($stream);$bytes=stream_get_contents($stream);fclose($stream);return $bytes;
    }
    private function readAll(string $bytes,string $phrase='a distinct test secret phrase'): array {
        $stream=fopen('php://memory','w+b');fwrite($stream,$bytes);rewind($stream);
        try {$cursor=BackupArchive::open($stream,$phrase);$rows=[];do {$row=BackupArchive::pull($stream,$cursor);$rows[]=$row;}while($row[0]!=='Z');return $rows;}
        finally {fclose($stream);}
    }
    public function test_real_round_trip_including_binary_and_finality(): void {
        $bytes=$this->archive();self::assertStringNotContainsString('never plaintext',$bytes);self::assertStringNotContainsString('sensitive',$bytes);
        $rows=$this->readAll($bytes);self::assertSame('M',$rows[0][0]);self::assertSame(str_repeat("\x00\xFFsensitive",5000),$rows[2][1]);self::assertSame(['Z',''],$rows[3]);
        self::assertNotSame($bytes,$this->archive());
    }
    public function test_wrong_password_alteration_truncation_append_and_kdf_abuse_fail(): void {
        $bytes=$this->archive();$altered=$bytes;$altered[75]=chr(ord($altered[75])^1);$kdf=$bytes; $kdf=substr_replace($kdf,pack('N',2147483647),28,4);
        foreach([[$bytes,'incorrect secret phrase'],[$altered,null],[substr($bytes,0,-1),null],[$bytes.'extra',null],[$kdf,null],[substr($bytes,0,40),null]] as [$input,$phrase]) {
            try {$this->readAll($input,$phrase??'a distinct test secret phrase');self::fail('Invalid container accepted');}
            catch(RuntimeException $error) {self::assertNotSame('',$error->getMessage());}
        }
    }
    public function test_reordered_authenticated_frames_are_rejected(): void {
        $bytes=$this->archive();$a=unpack('N',substr($bytes,56,4))[1]+4;$b=unpack('N',substr($bytes,56+$a,4))[1]+4;
        $reordered=substr($bytes,0,56).substr($bytes,56+$a,$b).substr($bytes,56,$a).substr($bytes,56+$a+$b);
        $this->expectException(RuntimeException::class);$this->readAll($reordered);
    }
    public function test_oversized_frame_cannot_allocate_unbounded_memory(): void {
        $bytes=substr_replace($this->archive(),pack('N',0xffffffff),56,4);
        $this->expectException(RuntimeException::class);$this->readAll($bytes);
    }
    public function test_latest_dedicated_icons_and_unknown_fallbacks_exist(): void {
        $extensions=['pdf','docx','xlsx','ppt','pptx','odt','ods','odp','csv','txt','md','jpg','jpeg','png','svg','zip','rar','7z','epub','html','json','xml','yaml','sql','rtf','tif','tiff','eml','msg'];
        foreach($extensions as $extension) {self::assertSame($extension,Publication::icon('name.'.strtoupper($extension)));self::assertFileExists(dirname(__DIR__,2).'/assets/icons/'.$extension.'.svg');}
        foreach(['unknown','name.unknown','no-extension','name.'] as $name) self::assertSame('file',Publication::icon($name));
        self::assertSame('audio',Publication::icon('sound.MP3'));self::assertSame('video',Publication::icon('film.mov'));
        self::assertFileDoesNotExist(dirname(__DIR__,2).'/assets/icons/slides.svg');self::assertFileDoesNotExist(dirname(__DIR__,2).'/assets/icons/archive.svg');
    }
    public function test_contrast_report_preserves_unrounded_thresholds_and_incomplete_values(): void {
        $colors=FrontendAppearance::presets()['filewarden'];$report=FrontendAppearance::contrast_report($colors);self::assertCount(8,$report);self::assertSame([],FrontendAppearance::contrast_failures($colors));
        $colors['surface']='#ffffff';$colors['text']='#777777';$report=FrontendAppearance::contrast_report($colors);self::assertLessThan(4.5,$report['text/surface']);self::assertContains('text/surface',FrontendAppearance::contrast_failures($colors));
        $colors['text']='#767676';self::assertGreaterThan(4.5,FrontendAppearance::contrast_report($colors)['text/surface']);
        $colors['text']='#000000';self::assertSame(21.0,FrontendAppearance::contrast_report($colors)['text/surface']);
        $colors['text']='#ff';self::assertNull(FrontendAppearance::contrast_report($colors)['text/surface']);
    }
}
