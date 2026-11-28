<?php
use FileWarden\BackupComponents;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use PHPUnit\Framework\Attributes\PreserveGlobalState;

#[RunTestsInSeparateProcesses]
#[PreserveGlobalState(false)]
final class BackupComponentsTest extends TestCase {
    protected function setUp(): void {
        if (!function_exists('wp_json_encode')) { function wp_json_encode($data) { return json_encode($data); } }
    }
    private function source(): array {
        return ['version'=>1,'schema'=>'7','root_uuid'=>'fixture','folders'=>[['created_by'=>'27','policy'=>'{"type":"public"}']],
            'documents'=>[['created_by'=>'27','policy'=>'{"type":"public"}','trashed_at'=>null,'retained_rules'=>'[{"type":"authenticated"}]'],['created_by'=>'28','policy'=>'{"type":"public"}','trashed_at'=>'2026-11-23 12:00:00']],
            'audit_logs'=>[['actor_login'=>'historical','actor_id'=>999,'resource_rules'=>'{"policies":[{"type":"public"}]}']],
            'users'=>[27=>'source-creator',28=>'deleted-creator'],'settings'=>['language'=>'fr_FR','appearance'=>['name'=>'Source']],'logo'=>['size'=>123]];
    }
    public function test_presets_and_old_archive_compatibility(): void {
        self::assertSame(array_fill_keys(BackupComponents::KEYS,true),BackupComponents::options());
        self::assertSame(BackupComponents::defaults(),BackupComponents::included(['version'=>1]));
        self::assertSame(array_replace(array_fill_keys(BackupComponents::KEYS,false),['documents'=>true,'folders'=>true]),BackupComponents::defaults(true));
        self::assertSame('map',BackupComponents::restoration(['version'=>1])['creators']);
    }
    public function test_options_reject_incomplete_unknown_or_coerced_values(): void {
        foreach([[],['permissions'=>false],BackupComponents::defaults()+['secret'=>true],array_replace(BackupComponents::defaults(),['permissions'=>'false']),array_replace(BackupComponents::defaults(),['audit'=>0])]as$bad){
            try{BackupComponents::options($bad);self::fail('Invalid flags accepted');}catch(InvalidArgumentException $e){self::assertSame('Invalid backup components.',$e->getMessage());}
        }
    }
    public function test_simplified_export_physically_removes_optional_data_and_never_becomes_public(): void {
        $m=BackupComponents::export($this->source(),BackupComponents::defaults(true));
        self::assertSame(3,$m['version']);self::assertCount(1,$m['documents']);self::assertSame([],$m['audit_logs']);self::assertSame([],$m['settings']);self::assertNull($m['logo']);self::assertSame([0=>''],$m['users']);
        foreach(array_merge($m['folders'],$m['documents'])as$row){self::assertSame(0,$row['created_by']);self::assertSame(BackupComponents::private_policy(),json_decode($row['policy'],true));}
        self::assertNull($m['documents'][0]['retained_rules']);self::assertStringNotContainsString('source-creator',json_encode($m));
        self::assertSame('current',BackupComponents::restoration($m)['creators']);
    }
    public function test_permission_omission_sanitizes_audit_policy_but_preserves_historical_actor(): void {
        $flags=BackupComponents::defaults();$flags['permissions']=false;$flags['creators']=false;
        $m=BackupComponents::export($this->source(),$flags);
        self::assertSame('historical',$m['audit_logs'][0]['actor_login']);self::assertSame(999,$m['audit_logs'][0]['actor_id']);
        self::assertSame([BackupComponents::private_policy()],json_decode($m['audit_logs'][0]['resource_rules'],true)['policies']);
    }
    public function test_restore_cannot_enable_omitted_metadata_or_force_missing_creator_maps(): void {
        $m=BackupComponents::export($this->source(),BackupComponents::defaults(true));
        $choices=BackupComponents::restoration($m);self::assertFalse($choices['permissions']);self::assertFalse($choices['settings']);
        foreach(['permissions','settings','appearance','audit','creators']as$key){$bad=$choices;$bad[$key]=$key==='creators'?'map':true;
            try{BackupComponents::restoration($m,$bad);self::fail('Absent component enabled');}catch(InvalidArgumentException $e){self::assertSame('Invalid restoration choices.',$e->getMessage());}
        }
    }
    public function test_forged_manifests_cannot_hide_public_policy_or_attribution_behind_omission_flags(): void {
        $m=BackupComponents::export($this->source(),BackupComponents::defaults(true));
        $mutations=[fn(&$x)=>$x['folders'][0]['policy']='{"type":"public"}',fn(&$x)=>$x['documents'][0]['created_by']=27,fn(&$x)=>$x['users']=[27=>'source'],fn(&$x)=>$x['audit_logs']=[['event'=>'secret']],fn(&$x)=>$x['settings']=['language'=>'fr_FR'],fn(&$x)=>$x['logo']=['size'=>99]];
        foreach($mutations as$mutate){$bad=$m;$mutate($bad);try{BackupComponents::validation($bad);self::fail('Omission promise broken');}catch(InvalidArgumentException $e){self::assertContains($e->getMessage(),['Invalid backup components.','Invalid backup manifest.']);}}
    }
    public function test_v18_six_flag_archives_keep_their_original_library_scope(): void {
        $six=array_fill_keys(array_slice(BackupComponents::KEYS,2),false);
        $included=BackupComponents::included(['version'=>2,'components'=>$six]);
        self::assertTrue($included['documents']); self::assertTrue($included['folders']);
        self::assertFalse($included['permissions']);
    }
    public function test_configuration_only_export_has_no_library_or_creator_metadata(): void {
        $flags=array_fill_keys(BackupComponents::KEYS,false);$flags['settings']=true;$flags['appearance']=true;
        $m=BackupComponents::export($this->source(),$flags);
        self::assertSame([],$m['documents']);self::assertSame([],$m['folders']);self::assertSame([],$m['users']);self::assertSame([],$m['audit_logs']);
        self::assertTrue(BackupComponents::configuration_only($m));
        self::assertSame('Source',$m['settings']['appearance']['name']);
    }
    public function test_documents_and_trash_require_their_folder_structure(): void {
        foreach(['documents','trash']as$key){$flags=array_fill_keys(BackupComponents::KEYS,false);$flags[$key]=true;
            self::assertTrue(BackupComponents::options($flags)['folders']);
            $m=BackupComponents::export($this->source(),$flags);
            self::assertCount(1,$m['documents']);self::assertFalse(BackupComponents::configuration_only($m));
            self::assertSame($key==='trash',!empty($m['documents'][0]['trashed_at']));
        }
    }
    public function test_empty_selection_and_metadata_without_content_are_refused(): void {
        foreach([[],['creators'=>true],['permissions'=>true]]as$changes){
            try{BackupComponents::options(array_replace(array_fill_keys(BackupComponents::KEYS,false),$changes));self::fail('Empty backup accepted');}
            catch(InvalidArgumentException $e){self::assertSame('Invalid backup components.',$e->getMessage());}
        }
    }
    public function test_folder_and_audit_archives_are_never_configuration_only(): void {
        foreach(['folders','audit']as$key){$flags=array_fill_keys(BackupComponents::KEYS,false);$flags[$key]=true;$flags['settings']=true;
            self::assertFalse(BackupComponents::configuration_only(BackupComponents::export($this->source(),$flags)));
        }
    }
    public function test_v3_omission_flags_cannot_smuggle_documents(): void {
        $flags=array_fill_keys(BackupComponents::KEYS,false);$flags['settings']=true;
        $m=BackupComponents::export($this->source(),$flags);$m['documents']=$this->source()['documents'];
        $this->expectException(InvalidArgumentException::class);BackupComponents::validation($m);
    }
}
