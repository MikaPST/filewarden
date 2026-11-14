/** Real correction paths on disposable installations only; no production documents. */
const assert=require('node:assert/strict');
const crypto=require('node:crypto');
module.exports=async({context,base,name})=>{
 const page=await context.newPage();
 const docs=base+'/wp-admin/admin.php?page=filewarden';
 const settings=base+'/wp-admin/admin.php?page=filewarden-settings';
 const prefix='Correction-'+name+'-'+Date.now();let sequence=0;
 const errors=[];page.on('pageerror',error=>errors.push(error.message));
 const dismiss=()=>page.evaluate(()=>document.querySelectorAll('.fw-notice[data-fw-notice]').forEach(el=>window.FileWardenNotices.dismiss(el)));
 const leaf=async label=>{
  await page.goto(docs+'&tab=folder');
  const title=prefix+'-'+label+'-'+(++sequence);
  await page.locator('#fw-name').fill(title);
  await page.locator('#folder-parent').selectOption('0');
  await page.locator('form:has(#fw-name) button[type=submit]').click();await page.waitForURL(/result=success/);
  assert.ok(await page.locator('.fw-notice-success').isVisible());await dismiss();
  const link=page.locator('.fw-tree-folder').filter({hasText:title});const href=await link.getAttribute('href');
  return {title,id:new URL(href).searchParams.get('folder_id'),href};
 };
 const remove=async(folder,mode)=>{
  await page.goto(folder.href);await dismiss();
  await page.locator('a[href*="panel=delete_folder"]').click();await page.waitForLoadState('load');
  const form=page.locator('.fw-inspector form:has(input[value=delete_folder])');
  if(mode)await form.evaluate((form,mode)=>{const field=document.createElement('input');field.type='hidden';field.name='fw_engine_fixture';field.value=mode;form.append(field);},mode);
  await form.locator('button[type=submit]').click();await page.waitForURL(url=>url.searchParams.has('result'));
  return new URL(page.url()).searchParams.get('result');
 };
 try{
  await page.setViewportSize({width:1280,height:900});await page.goto(settings);
  for(const selector of ['#filewarden-admin-css','#filewarden-notices-js','#filewarden-settings-js']){
   const element=page.locator(selector);const url=await element.getAttribute(selector.endsWith('-css')?'href':'src');
   const response=await context.request.get(url);assert.equal(response.status(),200);
   const digest=crypto.createHash('sha256').update(await response.body()).digest('hex').slice(0,12);
   assert.equal(new URL(url).searchParams.get('ver'),'0.1.15-dev-'+digest,'The loaded asset URL has no content digest');
  }
  const panel=page.locator('.fw-settings-layout>.fw-content');const before=await panel.boundingBox();
  await page.locator('[name=notification_seconds]').focus();
  await page.evaluate(()=>window.FileWardenNotices.show({type:'info',message:'Overlay geometry fixture',persistent:true}));
  const after=await panel.boundingBox();assert.deepEqual(after,before,'Floating feedback moved or resized settings');
  const region=page.locator('.fw-notice-region');
  assert.equal(await region.evaluate(el=>getComputedStyle(el).position),'fixed');
  assert.equal(await region.evaluate(el=>el.parentNode===document.body),true);
  assert.equal(await page.locator('[name=notification_seconds]').evaluate(el=>el===document.activeElement),true,'Showing feedback stole focus');
  await page.addStyleTag({content:'.notice,.updated,div.error{display:none!important}.fw-workspace{transform:translateZ(0);overflow:hidden}'});
  assert.ok(await page.locator('.fw-notice-info').isVisible(),'Generic WordPress notice hiding concealed a FileWarden notification');
  const top=await region.evaluate(el=>el.getBoundingClientRect().top);
  const bar=await page.locator('#wpadminbar').boundingBox();assert.ok(top>=bar.y+bar.height);
  await page.evaluate(()=>window.scrollTo(0,200));
  assert.ok(await region.evaluate(el=>el.getBoundingClientRect().top>=0));
  await page.evaluate(()=>{
   const dialog=document.createElement('dialog');dialog.className='filewarden-preview-dialog';
   const close=document.createElement('button');close.textContent='Close test dialog';dialog.append(close);document.body.append(dialog);dialog.showModal();
   window.FileWardenNotices.show({type:'error',message:'Modal-layer acceptance',persistent:true});
  });
  await page.waitForFunction(()=>document.querySelector('.fw-notice-region').parentNode.matches('dialog[open]'));
  assert.equal(await page.locator('.fw-notice-error').evaluate(el=>{const r=el.getBoundingClientRect();return document.elementFromPoint(r.x+30,r.y+25)?.closest('.fw-notice')===el;}),true,'Native dialog top layer concealed feedback');
  await page.evaluate(()=>document.querySelector('dialog.filewarden-preview-dialog').close());
  await page.waitForFunction(()=>document.querySelector('.fw-notice-region').parentNode===document.body);await dismiss();

  // Real panels, values and category registry; switching must preserve this document object.
  await page.goto(settings);await page.evaluate(()=>window.__fwSameDocument='keep');
  await page.locator('[name=notification_seconds]').selectOption('5');
  await page.locator('[data-settings-section=documents]').click();await page.locator('[name=upload_mb]').fill('29');
  await page.locator('[data-settings-section=general]').click();assert.equal(await page.locator('[name=notification_seconds]').inputValue(),'5');
  await page.locator('[data-settings-section=documents]').click();assert.equal(await page.locator('[name=upload_mb]').inputValue(),'29');
  assert.equal(await page.evaluate(()=>window.__fwSameDocument),'keep');
  for(const category of ['general','storage','documents','retention','audit']){
   await page.locator(`[data-settings-section=${category}]`).click();
   assert.ok(await page.locator(`#fw-settings-${category} .fw-settings-description`).isVisible());
  }
  const positions=await page.locator('.fw-settings-nav a').evaluateAll(nodes=>nodes.map(el=>el.getBoundingClientRect().top));
  assert.ok(positions.every((top,i)=>!i||top>positions[i-1]),'Settings categories are no longer vertical');
  await page.locator('[data-settings-section=general]').click();
  await page.waitForFunction(()=>getComputedStyle(document.querySelector('[data-settings-section=general]')).backgroundColor==='rgb(28, 41, 66)');
  assert.equal(await page.locator('[data-settings-section=general]').evaluate(el=>getComputedStyle(el).color),'rgb(255, 255, 255)');
  await page.screenshot({path:'build/screenshots/correction-settings-'+name+'.png',fullPage:true});
  await page.setViewportSize({width:375,height:812});
  assert.ok(await page.evaluate(()=>document.documentElement.scrollWidth<=innerWidth));
  const mobilePositions=await page.locator('.fw-settings-nav a').evaluateAll(nodes=>nodes.map(el=>el.getBoundingClientRect().top));
  assert.ok(mobilePositions.every((top,i)=>!i||top>mobilePositions[i-1]));
  await page.screenshot({path:'build/screenshots/correction-settings-mobile-'+name+'.png',fullPage:true});
  await page.setViewportSize({width:1280,height:900});
  await page.getByRole('button',{name:'Save settings',exact:true}).click();await page.waitForURL(/result=success/);await dismiss();
  await page.reload();assert.equal(await page.locator('[name=notification_seconds]').inputValue(),'5');
  assert.equal(await page.locator('[name=upload_mb]').inputValue(),'29');
  await page.goto(settings+'&fw_settings_fixture=1');
  assert.equal(await page.locator('.fw-settings-nav [role=tab]').count(),6);
  await page.locator('[data-settings-section=acceptance-extra]').click();
  assert.equal(await page.locator('#fw-settings-acceptance-extra .fw-settings-description').textContent(),'Registry acceptance description');
  await page.goto(settings);assert.equal(await page.locator('.fw-settings-nav [role=tab]').count(),5);
  await page.locator('[name=notification_seconds]').selectOption('10');
  await page.locator('[data-settings-section=documents]').click();await page.locator('[name=upload_mb]').fill('50');
  await page.getByRole('button',{name:'Save settings',exact:true}).click();await page.waitForURL(/result=success/);await dismiss();

  // Real empty deletion and a hosting restriction that previously blocked all deletions.
  for(const mode of [undefined,'restricted-metadata']){
   const folder=await leaf(mode||'empty');assert.equal(await remove(folder,mode),'success');
   assert.equal(await page.locator('.fw-tree-folder').filter({hasText:folder.title}).count(),0);
   assert.ok(await page.locator('.fw-notice-success').isVisible());await dismiss();
  }
  const folder=await leaf('with-file');
  const filename='unbroken-'+name+'-'+Date.now()+'-'+('x'.repeat(155))+'-12345678-aaaa-bbbb-cccc-123456789012.txt';
  const bytes=Buffer.from('Protected correction fixture\n');
  await page.goto(docs+'&folder_id='+folder.id+'&tab=add');
  await page.locator('#fw-file').setInputFiles({name:filename,mimeType:'text/plain',buffer:bytes});
  await page.locator('[name=filewarden_save]').click();await page.waitForURL(/result=success/);
  assert.ok(await page.locator('.fw-notice-success').isVisible());await dismiss();
  const originalDownload=await page.locator('tbody tr').filter({hasText:filename}).locator('a[href*="download=1"]').getAttribute('href');
  for(const width of [1280,768,375]){
   await page.setViewportSize({width,height:900});
   const row=page.locator('tbody tr').filter({hasText:filename});
   const bounds=await row.locator('.fw-document-name a').evaluate(el=>{const a=el.getBoundingClientRect(),c=el.closest('td,th').getBoundingClientRect();return {a:{left:a.left,right:a.right},c:{left:c.left,right:c.right},text:el.textContent};});
   assert.ok(bounds.a.right<=bounds.c.right&&bounds.a.left>=bounds.c.left,'Filename overflowed its column');assert.equal(bounds.text.trim(),filename);
   const label=page.locator('.fw-tree-folder[aria-current=location]');
   assert.ok(await label.evaluate(el=>{const a=el.getBoundingClientRect(),s=el.lastElementChild.getBoundingClientRect();return s.right<=a.right&&s.left>=a.left;}),'Long folder label overflowed its navigation column');
  }
  await page.setViewportSize({width:1280,height:900});
  await page.screenshot({path:'build/screenshots/correction-long-name-'+name+'.png',fullPage:true});
  for(const [mode,result]of [['unverified-engines','transaction_metadata'],['transaction-failure','recovery'],['recovery-failure','recovery']]){
   assert.equal(await remove(folder,mode),result);
   const error=page.locator('.fw-notice-error');assert.ok(await error.isVisible());assert.match(await error.locator('.fw-notice-title').textContent(),/Folder not deleted/);
   assert.equal(await error.locator('.fw-notice-track').isVisible(),false);
   assert.ok(await page.locator('.fw-tree-folder').filter({hasText:folder.title}).isVisible());
   const response=await context.request.get(originalDownload);assert.equal(response.status(),200);assert.deepEqual(await response.body(),bytes);
   await dismiss();
  }
  const prior=Number((await page.locator('.fw-recovery-count').textContent()).match(/\d+/)[0]);
  assert.equal(await remove(folder),'success');
  assert.equal(await page.locator('.fw-tree-folder').filter({hasText:folder.title}).count(),0);
  assert.equal(Number((await page.locator('.fw-recovery-count').textContent()).match(/\d+/)[0]),prior+1);
  assert.equal((await context.request.get(originalDownload)).status(),404,'Recovered file stayed published through its former URL');
  const recoveryDownload=await page.locator('tbody tr').filter({hasText:filename}).locator('a[href*="download=1"]').getAttribute('href');
  const recovered=await context.request.get(recoveryDownload);assert.equal(recovered.status(),200);assert.deepEqual(await recovered.body(),bytes);
  assert.ok(await page.locator('.fw-notice-success').isVisible());
  await page.screenshot({path:'build/screenshots/correction-deletion-'+name+'.png',fullPage:true});
  assert.deepEqual(errors,[],'JavaScript errors occurred during real correction paths');
  console.log(name+': six corrective paths, floating/native-dialog feedback, notice-hiding compatibility, vertical settings, long names, successful deletion and transactional refusal/recovery passed');
 }catch(error){await page.screenshot({path:'build/screenshots/correction-failure-'+name+'.png',fullPage:true}).catch(()=>{});throw error;}
 finally{await page.close();}
};
