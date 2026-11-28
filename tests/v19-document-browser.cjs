/** Installed ZIP only: desktop/mobile, private sheet, exact duplicate review and per-file text. */
const fs=require('node:fs'),assert=require('node:assert/strict'),{chromium,firefox}=require('playwright');
assert.equal(process.env.FILEWARDEN_TESTING,'1');const base=process.env.FILEWARDEN_URL;assert.equal(new URL(base).hostname,'127.0.0.1');
const fixture=JSON.parse(fs.readFileSync(process.env.FILEWARDEN_DOCUMENT_RESULT,'utf8'));
const out=process.env.FILEWARDEN_DOCUMENT_SCREENSHOTS;assert.ok(out?.includes('.tools'));fs.mkdirSync(out,{recursive:true});
(async()=>{for(const[name,engine]of[['chromium',chromium],['firefox',firefox]]){
 const browser=await engine.launch();try{
 const context=await browser.newContext({viewport:{width:1440,height:950}});await context.request.get(base+'/wp-login.php');await context.request.post(base+'/wp-login.php',{form:{log:'fw_admin',pwd:'disposable-test-password',testcookie:'1'}});
 const page=await context.newPage(),errors=[];page.on('pageerror',e=>errors.push(e.message));page.setDefaultTimeout(20000);page.on('dialog',d=>d.accept());
 const folder=base+'/wp-admin/admin.php?page=filewarden&folder_id='+fixture.folder;
 await page.goto(folder+'&search=Independent');const row=page.locator('tr').filter({has:page.locator(`[data-document-selection][value="${fixture.document}"]`)});
 const eye=row.locator('a[aria-label="Preview"],a[aria-label="Aperçu"]').first();const previewURL=await eye.getAttribute('href');
 await row.locator('.fw-document-details').click();let modal=page.locator('dialog.fw-action-dialog');await modal.waitFor();
 await modal.locator('.fw-document-sheet').waitFor();assert.equal(await modal.locator('.fw-sheet-metadata dt').count(),5);
 await modal.locator('.fw-access-explanation summary').click();assert.equal(await modal.locator('.fw-access-rules').isVisible(),true);
 await modal.screenshot({path:out+'/'+name+'-sheet-desktop.png'});
 const editor=modal.locator('.fw-description-editor');await editor.locator('summary').click();const text=editor.locator('textarea');await text.fill('🌿'.repeat(400));
 assert.equal(await editor.locator('output').textContent(),'400 / 400');assert.equal(await text.evaluate(e=>e.validity.valid),true);
 await text.fill('🌿'.repeat(401));assert.equal(await text.evaluate(e=>e.validity.valid),false);
 await editor.locator('[data-description-cancel]').click();await page.waitForFunction(()=>!document.querySelector('dialog .fw-description-editor').open);
 await editor.locator('summary').click();assert.equal(await text.evaluate(e=>e.validity.valid),true);
 await text.fill('Description navigateur '+name);await editor.locator('button[type=submit],button:not([type])').click();await page.waitForURL(/result=success/);
 await page.goto(folder+'&search='+encodeURIComponent('Description navigateur '+name));assert.equal(await page.locator('.fw-description-match').count(),1);
 const config=await page.evaluate(()=>({nonce:filewardenLibrary.nonce,endpoint:filewardenLibrary.endpoint}));
 const deniedWrite=await context.request.post(config.endpoint,{headers:{'X-WP-Nonce':'invalid'},data:{operation:'description',id:fixture.document,description:'forbidden'}});assert.equal(deniedWrite.status(),403);
 const tooLong=await context.request.post(config.endpoint,{headers:{'X-WP-Nonce':config.nonce},data:{operation:'description',id:fixture.document,description:'x'.repeat(401)}});assert.equal(tooLong.status(),400);
 await page.locator('.fw-document-details').click();await modal.locator('.fw-document-sheet').waitFor();assert.ok((await modal.locator('.fw-description-text').textContent()).includes('Description navigateur '+name));
 await modal.locator('a[href*="panel=rights"]').click();await modal.locator('form [name=policy_type]').waitFor();assert.equal(await modal.locator('.fw-document-sheet').count(),0);assert.equal(await modal.locator('.fw-access-explanation').count(),1);await page.keyboard.press('Escape');await modal.waitFor({state:'detached'});
 await page.goto(folder+'&search=Visible');const visibleRow=page.locator('tr').filter({has:page.locator(`[data-document-selection][value="${fixture.visible}"]`)});
 const downloadURL=await visibleRow.locator('a[aria-label="Download"],a[aria-label="Télécharger"]').first().getAttribute('href');const response=await context.request.get(downloadURL);assert.equal(response.status(),200);const bytes=await response.body();
 await page.goto(folder+'&tab=add');const form=page.locator('form[data-filewarden-upload]');const suffix=name+'-'+Date.now();
 await form.locator('input[type=file]').setInputFiles([{name:'duplicate-'+suffix+'.txt',mimeType:'text/plain',buffer:bytes},{name:'unique-'+suffix+'.txt',mimeType:'text/plain',buffer:Buffer.from('Unique '+suffix)}]);
 const queue=page.locator('.fw-upload-item');assert.equal(await queue.count(),2);
 await queue.nth(0).locator('summary').click();await queue.nth(0).locator('textarea').fill('Copie indépendante '+suffix);
 await queue.nth(1).locator('summary').click();await queue.nth(1).locator('textarea').fill('Autre description '+name);
 await form.locator('[name=filewarden_save]').click();await queue.nth(0).locator('.fw-duplicate-review').waitFor({state:'visible'});
 await page.waitForFunction(()=>!document.querySelector('form[data-filewarden-upload]').hasAttribute('aria-busy'));
 assert.ok(/Saved|Enregistré/.test(await queue.nth(1).locator('[role=status]').textContent()),'Other file did not finish while duplicate waits');
 assert.ok((await queue.nth(0).locator('.fw-duplicate-review').textContent()).includes('.txt'));
 await page.screenshot({path:out+'/'+name+'-import-review.png',fullPage:true});
 await queue.nth(0).locator('.fw-duplicate-review button').click();await queue.nth(0).locator('.fw-duplicate-review').waitFor({state:'hidden'});await page.waitForFunction(()=>!document.querySelector('form[data-filewarden-upload]').hasAttribute('aria-busy'));
 assert.ok(/Saved|Enregistré/.test(await queue.nth(0).locator('[role=status]').textContent()));
 if(fixture.media&&name==='chromium'&&process.env.FILEWARDEN_TEST_MEDIA==='1'){
  await page.goto(folder+'&tab=add&media_id='+fixture.media);const mediaForm=page.locator('form[data-filewarden-upload]');
  assert.equal(await mediaForm.locator('[name=source][value=import]').isChecked(),true);
  await mediaForm.locator('.fw-import-description summary').click();await mediaForm.locator('.fw-import-description textarea').fill('Média protégé '+suffix);
  await mediaForm.locator('[name=filewarden_save]').click();const mediaRow=page.locator('.fw-upload-item');await mediaRow.locator('.fw-duplicate-review').waitFor({state:'visible'});
  await page.waitForFunction(()=>!document.querySelector('form[data-filewarden-upload]').hasAttribute('aria-busy'));
  await mediaRow.locator('.fw-duplicate-review button').click();await mediaRow.locator('.fw-duplicate-review').waitFor({state:'hidden'});
  await page.waitForFunction(()=>!document.querySelector('form[data-filewarden-upload]').hasAttribute('aria-busy'));assert.ok(/Saved|Enregistré/.test(await mediaRow.locator('[role=status]').textContent()));
 }
 await page.goto(folder+'&search='+encodeURIComponent('Copie indépendante '+suffix));assert.equal(await page.locator('.fw-description-match').count(),1);
 await page.setViewportSize({width:390,height:844});await page.locator('.fw-document-details').click();await modal.locator('.fw-document-sheet').waitFor();
 assert.equal(await page.evaluate(()=>document.documentElement.scrollWidth>innerWidth+1),false);const bounds=await modal.boundingBox();assert.ok(bounds.x>=0&&bounds.x+bounds.width<=391);
 await modal.screenshot({path:out+'/'+name+'-sheet-mobile.png'});await page.keyboard.press('Escape');
 await page.goto(previewURL);assert.equal((await page.locator('body').textContent()).includes('Description navigateur'),false,'Private description leaked to public viewer');
 assert.deepEqual(errors,[]);console.log('PASS '+name+': native sheet/read chain/edit/cancel/Unicode counter, per-file duplicate review, other-file completion, independent copy, description search, desktop/mobile, no public description');
 const limited=await browser.newContext();await limited.request.get(base+'/wp-login.php');await limited.request.post(base+'/wp-login.php',{form:{log:fixture.limited_user,pwd:'disposable-test-password',testcookie:'1'}});
 const allowedLibrary=await limited.request.get(folder);assert.ok((await allowedLibrary.text()).includes('fw-document-details'),'Limited account did not reach its authorized library');
 const denied=await limited.request.get(base+'/wp-admin/admin.php?page=filewarden&panel=details&document='+fixture.hidden);const deniedHTML=await denied.text();assert.equal(deniedHTML.includes('fw-document-sheet'),false);assert.equal(deniedHTML.includes('Hidden '),false);
 await limited.close();await context.close();
 }finally{await browser.close();}
}})().catch(e=>{console.error(e);process.exitCode=1;});
