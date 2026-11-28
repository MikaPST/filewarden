<?php
/** Disposable preproduction-correction fixture and native WordPress metadata checks. */
require __DIR__.'/acceptance-path.php';
use FileWarden\{Plugin,Repository,PermissionService,StorageService,DocumentService,AuditService,FrontendAppearance};
function fw19ui_assert($ok,$message){if(!$ok)throw new RuntimeException($message);}
wp_set_current_user(1);global $wpdb;
$links=['Version 0.1.19-dev','By <a href="https://michael-pastor.fr">Michaël PASTOR</a>','<a href="https://filewarden.org/" aria-label="Visit plugin site">Visit plugin site</a>','<a href="https://example.test/help">Help</a>'];
$changed=apply_filters('plugin_row_meta',$links,plugin_basename(FILEWARDEN_PATH.'filewarden.php'));
fw19ui_assert($changed[0]===$links[0]&&$changed[1]===$links[1]&&$changed[3]===$links[3]&&$changed[2]==='<a href="https://filewarden.org">filewarden.org</a>','Native metadata changed unrelated links');
fw19ui_assert(Plugin::row_meta($links,'another/plugin.php')===$links,'Another plugin was changed');
$repo=new Repository();$storage=new StorageService(FILEWARDEN_STORAGE_DIR,[ABSPATH],true);$service=new DocumentService($repo,new PermissionService([$repo,'folder']),$storage,new AuditService());
$parent=$service->create_folder('Documents UI '.bin2hex(random_bytes(3)),0,['type'=>'authenticated']);
$child=$service->create_folder('Sous-dossier public',$parent,['type'=>'public']);$service->create_folder('Documents réservés',$parent,['type'=>'inherit']);
$objects=['<< /Type /Catalog /Pages 2 0 R >>','<< /Type /Pages /Count 1 /Kids [3 0 R] >>','<< /Type /Page /Parent 2 0 R /MediaBox [0 0 400 500] /Resources << /Font << /F1 4 0 R >> >> /Contents 5 0 R >>','<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica >>'];
$stream="BT /F1 20 Tf 40 400 Td (Local UI fixture) Tj ET\n";$objects[]='<< /Length '.strlen($stream)." >>\nstream\n".$stream.'endstream';$pdf="%PDF-1.4\n";$offsets=[0];foreach($objects as$i=>$object){$offsets[]=strlen($pdf);$pdf.=($i+1)." 0 obj\n".$object."\nendobj\n";}$xref=strlen($pdf);$pdf.="xref\n0 6\n0000000000 65535 f \n";foreach(array_slice($offsets,1)as$offset)$pdf.=sprintf("%010d 00000 n \n",$offset);$pdf.="trailer\n<< /Size 6 /Root 1 0 R >>\nstartxref\n".$xref."\n%%EOF\n";
$uuids=[];
foreach(['2025-02-19-RC-SAGI-TER-Conseil-Syndical.pdf','LOGOSLH2-test.pdf','LOGOSLH2.pdf']as$i=>$name){$tmp=tempnam(sys_get_temp_dir(),'fw-ui-');file_put_contents($tmp,$pdf);$uuid=wp_generate_uuid4();$id=$repo->insert('documents',['uuid'=>$uuid,'folder_id'=>$parent,'name'=>$name,'mime'=>'application/pdf','size'=>strlen($pdf),'storage_key'=>$storage->put($tmp,strlen($pdf)),'sha256'=>hash('sha256',$pdf),'policy'=>['type'=>$i?'public':'inherit']]);unlink($tmp);$wpdb->update($wpdb->prefix.'filewarden_documents',['folder_added_at'=>$i?null:'2026-11-27 23:41:00'],['id'=>$id]);$uuids[]=$uuid;}
$appearance=FrontendAppearance::defaults();$appearance['preset']='dark';$appearance['colors']=FrontendAppearance::presets()['dark'];FrontendAppearance::save($appearance,new AuditService());
update_option('filewarden_language','en_US');update_option('filewarden_publication_login',['mode'=>'wordpress','url'=>'']);
$folder=$repo->folder($parent);$out=getenv('FILEWARDEN_UI_RESULT');fw19ui_assert($out&&str_contains($out,'.tools'),'Explicit private evidence path required');file_put_contents($out,wp_json_encode(['folder'=>$parent,'public_folder_id'=>$parent,'folder_uuid'=>$folder['uuid'],'child'=>$child,'documents'=>$uuids,'baseline'=>$repo->documents($parent)],JSON_PRETTY_PRINT));
WP_CLI::success('V19 corrections: native metadata hook preserves author/version/other plugins; isolated three-document and nested-folder fixture ready.');
