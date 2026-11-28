<?php
use FileWarden\ContentDuplicates;
use FileWarden\PermissionService;
use PHPUnit\Framework\TestCase;

final class ContentDuplicatesTest extends TestCase {
    private function compare(string $a,string $b,int $length):bool {
        $left=fopen('php://temp','w+b');$right=fopen('php://temp','w+b');
        fwrite($left,$a);fwrite($right,$b);rewind($left);rewind($right);
        try{return ContentDuplicates::equal($left,$right,$length);}finally{fclose($left);fclose($right);}
    }
    public function test_original_bytes_across_buffer_boundaries():void {
        $a=str_repeat("abc\0XYZ",20000);
        self::assertTrue($this->compare($a,$a,strlen($a)));
        $b=$a;$b[65536]='Q';self::assertFalse($this->compare($a,$b,strlen($a)));
        self::assertFalse($this->compare($a,$a.'extra',strlen($a)));
        self::assertFalse($this->compare($a,substr($a,0,-1),strlen($a)));
        self::assertFalse($this->compare($a,$a,strlen($a)+1));
    }
    public function test_invalid_retained_rules_fail_closed_even_for_global_reader():void {
        $user=new class{public $roles=[];public function has_cap($cap){return true;}public function exists(){return true;}};
        $permissions=new PermissionService(fn($id)=>null);
        self::assertFalse($permissions->can_read(['policy'=>['type'=>'public'],'retained_rules'=>[null],'folder_id'=>0],$user));
        self::assertFalse($permissions->can_read(['policy'=>['type'=>'public'],'folder_id'=>11],$user));
    }
}
