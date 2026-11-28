<?php
/** V19 migration, portable component scopes and configuration-only activation on disposable data. */
if (!defined('WP_CLI') || !WP_CLI || getenv('FILEWARDEN_TESTING')!=='1' || !str_contains(ABSPATH,'.tools') || !in_array(wp_parse_url(home_url(),PHP_URL_HOST),['127.0.0.1','localhost'],true)) exit(1);
use FileWarden\{Repository,PermissionService,StorageService,DocumentService,AuditService,BackupService,BackupJobs,BackupRestore,BackupManifest,BackupComponents,FrontendAppearance,PublicationLogin,Lifecycle,LibraryQuery};
function fw19_assert($ok,$why){if(!$ok)throw new RuntimeException($why);}
function fw19_refuse($fn,$why){try{$fn();}catch(Throwable $e){return $e->getMessage();}throw new RuntimeException($why);}
global $wpdb;wp_set_current_user(1);
$repo=new Repository();$storage=new StorageService(FILEWARDEN_STORAGE_DIR,[ABSPATH],true);$service=new DocumentService($repo,new PermissionService([$repo,'folder']),$storage,new AuditService());
fw19_assert(FILEWARDEN_VERSION==='0.1.19-dev'&&get_option('filewarden_schema')==='8','Wrong candidate/schema');
$old=$wpdb->get_row("SELECT * FROM {$wpdb->prefix}filewarden_documents WHERE name='upgrade-fixture.pdf' LIMIT 1",ARRAY_A);
if($old)fw19_assert($old['folder_added_at']===null,'Migration invented an old folder arrival date');
$suffix=bin2hex(random_bytes(3));$a=$service->create_folder('V19 source '.$suffix,0,['type'=>'public']);$b=$service->create_folder('V19 destination '.$suffix,0,['type'=>'public']);
$temp=tempnam(sys_get_temp_dir(),'fw19-');$bytes='V19 disposable private content';file_put_contents($temp,$bytes);$uuid=wp_generate_uuid4();
$repo->insert('documents',['uuid'=>$uuid,'folder_id'=>$a,'name'=>'v19-fixture-'.$suffix.'.txt','storage_key'=>$storage->put($temp,strlen($bytes)),'mime'=>'text/plain','size'=>strlen($bytes),'sha256'=>hash('sha256',$bytes),'policy'=>['type'=>'inherit']]);unlink($temp);
$doc=$repo->document($uuid);fw19_assert($doc['folder_added_at']===$doc['created_at'],'Import has no arrival timestamp');
$wpdb->update($wpdb->prefix.'filewarden_documents',['folder_added_at'=>'2000-01-01 00:00:00'],['id'=>$doc['id']]);$plan=(new Lifecycle($service))->plan($uuid,$b);(new Lifecycle($service))->transfer('move',$uuid,$b,$doc['name'],$plan['proof']);$moved=$repo->document($uuid);
fw19_assert($moved['folder_added_at']>'2000-01-01 00:00:00'&&$moved['created_at']===$doc['created_at']&&$moved['storage_key']===$doc['storage_key'],'Move changed original metadata or missed arrival');
$arrival=$moved['folder_added_at'];(new Lifecycle($service))->trash($uuid);(new Lifecycle($service))->trash($uuid,true);fw19_assert($repo->document($uuid)['folder_added_at']===$arrival,'Same-folder restore changed arrival');
$palettes=[];foreach(['dark','terracotta']as$p){$value=FrontendAppearance::defaults();$value['preset']=$p;$value['colors']=FrontendAppearance::presets()[$p];$value['name']='Custom identity';$checked=FrontendAppearance::validate($value);fw19_assert($checked['preset']===$p&&$checked['name']==='Custom identity'&&!FrontendAppearance::contrast_failures($value['colors']),'Approved palette failed validation');$palettes[$p]=FrontendAppearance::contrast_report($value['colors']);}
foreach(['1252',' [elementor-template id="1252"] ',"[elementor-template id='1252']",'[elementor-template  id = "1252" ]']as$input)fw19_assert(PublicationLogin::validate(['mode'=>'elementor','popup_id'=>$input])['popup_id']===1252,'Popup ID not parsed');
foreach(['[elementor-template id="1252" id="2"]','[other id="1252"]','[elementor-template id="0"]','[elementor-template id="2147483648"]','[elementor-template id="1252"]<script>','[elementor-template id="1252"][x]']as$input)fw19_refuse(fn()=>PublicationLogin::validate(['mode'=>'elementor','popup_id'=>$input]),'Unsafe shortcode accepted');
$masks=0;for($mask=0;$mask<256;$mask++){$flags=[];foreach(BackupComponents::KEYS as$i=>$key)$flags[$key]=(bool)($mask&(1<<$i));try{$flags=BackupComponents::options($flags);}catch(InvalidArgumentException $e){continue;}[$manifest,$sources]=BackupManifest::capture($flags);BackupManifest::validate($manifest);fw19_assert(count($sources)===count($manifest['documents'])+($manifest['logo']?1:0),'Excluded bytes remain in source list');if(!$flags['documents'])fw19_assert(!array_filter($manifest['documents'],fn($r)=>empty($r['trashed_at'])),'Active document leaked');if(!$flags['folders'])fw19_assert(!$manifest['folders'],'Folder metadata leaked');$masks++;}
$snapshot=fn()=>[$wpdb->get_results("SELECT * FROM {$wpdb->prefix}filewarden_documents ORDER BY id",ARRAY_A),$wpdb->get_results("SELECT * FROM {$wpdb->prefix}filewarden_folders ORDER BY id",ARRAY_A),get_option('filewarden_root_uuid')];
$password='disposable v19 backup password 2026';$cases=[];
foreach(['settings','appearance','both']as$case){
 $flags=array_fill_keys(BackupComponents::KEYS,false);$flags['settings']=$case!=='appearance';$flags['appearance']=$case!=='settings';
 update_option('filewarden_upload_mb',41);$appearance=FrontendAppearance::defaults();$appearance['preset']='dark';$appearance['colors']=FrontendAppearance::presets()['dark'];$appearance['name']='V19 source identity';update_option(FrontendAppearance::OPTION,$appearance);
 $job=BackupService::start($service,'backup',$password,'',$flags);do{$job=BackupService::tick($service,$job['id']);}while($job['status']==='working');$source=BackupJobs::load($service,$job['id']);fw19_assert(BackupComponents::configuration_only($source['manifest'])&&!$source['files'],'Configuration archive contains library bytes');
 $path=BackupJobs::path($service,$job['id'],'fwbackup');$archive=file_get_contents($path);fw19_assert(!str_contains($archive,$password)&&!str_contains($archive,'V19 source identity'),'Plaintext archive leak');
 update_option('filewarden_upload_mb',12);$destination=FrontendAppearance::defaults();$destination['name']='V19 destination identity';update_option(FrontendAppearance::OPTION,$destination);$before=$snapshot();
 fw19_refuse(fn()=>BackupService::start($service,'restore','wrong password but long enough',$path),'Wrong password accepted');fw19_assert($snapshot()===$before,'Failed analysis changed library');
 $restore=BackupService::start($service,'restore',$password,$path);do{$restore=BackupService::tick($service,$restore['id']);}while($restore['status']==='working');fw19_assert($restore['configuration_only']&&$restore['status']==='review','Configuration scope missing');
 $choices=BackupComponents::restoration(BackupJobs::load($service,$restore['id'])['manifest']);$choices['creators']='current';
 $done=BackupRestore::apply($service,$restore['id'],[],[],$choices);fw19_assert($done['status']==='restored'&&$snapshot()===$before,'Configuration restoration changed populated library');
 fw19_assert((int)get_option('filewarden_upload_mb')===($flags['settings']?41:12),'Settings choice not honored');fw19_assert(FrontendAppearance::current()['name']===($flags['appearance']?'V19 source identity':'V19 destination identity'),'Appearance choice not honored');
 $cases[]=$case;
}
// Full-content archives still refuse populated destinations.
$full=BackupService::start($service,'backup',$password);do{$full=BackupService::tick($service,$full['id']);}while($full['status']==='working');$fullPath=BackupJobs::path($service,$full['id'],'fwbackup');$before=$snapshot();$error=fw19_refuse(fn()=>BackupService::start($service,'restore',$password,$fullPath),'Library archive merged into existing data');fw19_assert($error==='Restoration requires an empty FileWarden library.'&&$snapshot()===$before,'Nonempty target refusal changed');
// Export the authenticated full archive for an independent empty destination and legacy-format tests.
$out=getenv('FILEWARDEN_V19_RESULT');fw19_assert($out&&str_contains($out,'.tools'),'Explicit isolated evidence path required');$portable=dirname($out).'/v19-full.fwbackup';copy($fullPath,$portable);$fullState=BackupJobs::load($service,$full['id']);
file_put_contents($out,wp_json_encode(['version'=>FILEWARDEN_VERSION,'schema'=>get_option('filewarden_schema'),'component_combinations'=>$masks,'configuration_cases'=>$cases,'palettes'=>$palettes,'archive'=>$portable,'manifest'=>$fullState['manifest'],'browser'=>['folder'=>$b,'parent'=>$a,'document'=>$uuid,'name'=>$doc['name']]],JSON_PRETTY_PRINT));
WP_CLI::success('V19: additive arrival migration, moves, popup parser, eight-pair palettes, '.$masks.' component combinations, three encrypted configuration restorations on populated library, wrong password and nonempty library refusal passed.');
