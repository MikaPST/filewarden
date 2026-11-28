/** Geometry, single-chevron sorting and native links against installed local ZIP only. */
const fs=require('node:fs'),assert=require('node:assert/strict'),{chromium,firefox}=require('playwright');
assert.equal(process.env.FILEWARDEN_TESTING,'1');const base=process.env.FILEWARDEN_URL;
assert.equal(new URL(base).hostname,'127.0.0.1');
const fixture=JSON.parse(fs.readFileSync(process.env.FILEWARDEN_UI_RESULT,'utf8'));
const output=process.env.FILEWARDEN_UI_SCREENSHOTS;assert.ok(output.includes('.tools'));fs.mkdirSync(output,{recursive:true});
(async()=>{for(const[name,engine]of[['chromium',chromium],['firefox',firefox]]){
 const browser=await engine.launch();try{
 const context=await browser.newContext({viewport:{width:1800,height:1000}});
 await context.request.get(base+'/wp-login.php');await context.request.post(base+'/wp-login.php',{form:{log:'fw_admin',pwd:'disposable-test-password',testcookie:'1'}});
 const page=await context.newPage(),errors=[];page.setDefaultTimeout(20000);page.on('pageerror',e=>errors.push(e.message));page.on('dialog',d=>d.accept());
 const folder=base+'/wp-admin/admin.php?page=filewarden&folder_id='+fixture.folder+'&type=pdf';
 await page.goto(folder);const table=page.locator('.filewarden-documents');
 assert.equal(await table.locator('thead th').count(),6);assert.equal(await table.locator('.fw-sort-heading').count(),3);
 assert.equal(await page.locator('a[href*="sort=type_"]').count(),0);assert.equal(await table.locator('tbody tr').count(),3);
 assert.equal(await table.locator('.fw-sort-chevron').count(),3);
 const sort=table.locator('a[href*="sort=name_"]');await sort.click();await page.waitForURL(/sort=name_asc/);
 assert.equal(new URL(page.url()).searchParams.get('type'),'pdf');assert.equal(await table.locator('thead th').first().getAttribute('aria-sort'),'ascending');
 const ascending=await table.locator('thead .fw-sort-chevron').first().evaluate(e=>getComputedStyle(e).transform);
 await table.locator('a[href*="sort=name_desc"]').click();await page.waitForURL(/sort=name_desc/);
 const descending=await table.locator('thead .fw-sort-chevron').first().evaluate(e=>getComputedStyle(e).transform);assert.notEqual(ascending,descending);
 const inactive=await table.locator('thead th:nth-child(2) .fw-sort-chevron').evaluate(e=>({w:e.getBoundingClientRect().width,opacity:getComputedStyle(e).opacity}));assert.ok(inactive.w<12&&Number(inactive.opacity)<1);
 for(const width of[1800,1440,1280,1024]){
  await page.setViewportSize({width,height:1000});
  const geometry=await table.evaluate(t=>[...t.tBodies[0].rows].map(row=>{
   const c=row.cells[4].getBoundingClientRect(),a=row.querySelector('.fw-row-actions').getBoundingClientRect(),buttons=[...row.querySelectorAll('.fw-row-actions>.button,.fw-context>summary')].map(b=>b.getBoundingClientRect());
   return {display:getComputedStyle(row).display,accessRight:c.right,actionsLeft:a.left,firstAction:Math.min(...buttons.map(b=>b.left)),lastAction:Math.max(...buttons.map(b=>b.right)),cellRight:row.cells[5].getBoundingClientRect().right};
  }));
  for(const g of geometry){assert.equal(g.display,'table-row');assert.ok(g.firstAction>=g.accessRight+5,'Actions overlap effective access at '+width);assert.ok(g.lastAction<=g.cellRight-4,'Action overflows its reserved column');}
 }
 await page.setViewportSize({width:1800,height:1000});await page.locator('.fw-content:has(.filewarden-documents)').screenshot({path:output+'/'+name+'-table.png'});
 const row=page.locator('tbody tr').filter({has:page.locator(`[data-document-selection][value="${fixture.documents[1]}"]`)});
 await row.locator('.fw-context>summary').click();await row.locator('a[href*="panel=rights"]').click();const modal=page.locator('dialog.fw-action-dialog');await modal.waitFor();
 await modal.locator('[name=policy_type]').selectOption('public');await modal.locator('.fw-warning:not([hidden])').waitFor();
 for(const viewport of[{width:1280,height:700},{width:1280,height:520},{width:375,height:812}]){
  await page.setViewportSize(viewport);const cancel=modal.locator('.fw-cancel');await cancel.scrollIntoViewIfNeeded();
  const g=await modal.evaluate(d=>{const c=d.querySelector('.fw-cancel').getBoundingClientRect(),b=d.querySelector('[name=save_policy]').getBoundingClientRect(),r=d.getBoundingClientRect();return{cancelCenter:(c.left+c.right)/2,saveCenter:(b.left+b.right)/2,bottom:c.bottom,border:r.bottom,innerOverflow:getComputedStyle(d.querySelector('.fw-inspector')).overflow,below:c.top>=b.bottom};});
  assert.ok(Math.abs(g.cancelCenter-g.saveCenter)<2,'Cancel is not centered');assert.ok(g.bottom<=g.border-12,'Cancel is clipped');assert.ok(g.below);assert.equal(g.innerOverflow,'visible');
 }
 await page.setViewportSize({width:1280,height:850});await modal.locator('.fw-cancel').scrollIntoViewIfNeeded();await page.screenshot({path:output+'/'+name+'-rights-modal.png'});
 const form=await modal.locator('form').evaluate(f=>({url:f.getAttribute('action'),fields:Object.fromEntries(new FormData(f))}));
 const refused=await context.request.post(form.url,{form:{...form.fields,_wpnonce:'invalid',save_policy:'1'}});assert.equal(refused.status(),403,'Rights POST accepted a bad nonce');
 await modal.locator('.fw-cancel').click();await modal.waitFor({state:'detached'});
 for(const width of[375,320]){await page.setViewportSize({width,height:900});await page.goto(folder);assert.equal(await table.evaluate(t=>getComputedStyle(t).display),'table');assert.equal(await table.locator('thead .fw-sort-heading').first().isVisible(),true);assert.ok(await page.evaluate(()=>document.documentElement.scrollWidth<=innerWidth+1));}
 await page.setViewportSize({width:1440,height:1000});await page.goto(base+'/wp-admin/plugins.php');
 const plugin=page.locator('tr[data-plugin="filewarden/filewarden.php"]');const link=plugin.locator('.plugin-version-author-uri a[href="https://filewarden.org"]');assert.equal(await link.textContent(),'filewarden.org');assert.equal(await plugin.locator('a[href="https://michael-pastor.fr"]').textContent(),'Michaël PASTOR');
 await page.goto(base+'/wp-admin/admin.php?page=filewarden-publication&folder_id='+fixture.folder);
 const preview=page.frameLocator('.filewarden-publication-frame');await preview.locator('.fw-subfolders>a>.fw-ui-folder').first().waitFor();
 const icon=await preview.locator('.fw-subfolders>a>.fw-ui-folder').first().evaluate(e=>({mask:getComputedStyle(e).maskImage,color:getComputedStyle(e).backgroundColor,link:getComputedStyle(e.parentElement).color}));assert.match(icon.mask,/folder.svg/);assert.equal(icon.color,icon.link);assert.equal(icon.color,'rgb(159, 228, 222)');
 assert.match(await preview.locator('.file-icon').first().getAttribute('src'),/\/pdf.svg$/);assert.equal(await preview.locator('.file-icon').first().evaluate(e=>getComputedStyle(e).filter),'none');
 await page.locator('[data-publication-tab=appearance]').click();const swatch=await page.locator('.fw-palette-dark').evaluate(e=>{const c=getComputedStyle(e);return ['button','accent','background'].map(k=>c.getPropertyValue('--fw-palette-'+k).trim())});assert.deepEqual(swatch,['#101a2b','#192840','#76d6ce']);
 const custom=page.locator('[data-color=button]');await custom.fill('#80ddd5');await custom.dispatchEvent('input');assert.equal(await page.locator('.fw-palette-custom').evaluate(e=>getComputedStyle(e).getPropertyValue('--fw-palette-button').trim()),'#80ddd5');
 await page.locator('[data-preset=dark]').click();await page.locator('[data-publication-tab=integration]').click();await page.locator('[data-preview-mode=denied]').click();
 for(const device of['desktop','mobile']){
  await page.locator('[data-preview-device='+device+']').click();
  for(const style of['compact','centered','editorial']){
   await page.locator('[name=denied][value='+style+']').check();await preview.locator('.fw-denied-'+style).waitFor();
   const g=await preview.locator('main').evaluate(m=>{const brand=m.querySelector('.fw-public-brand').getBoundingClientRect(),card=m.querySelector('.fw-denied').getBoundingClientRect(),outer=m.getBoundingClientRect();return {left:Math.abs(brand.left-card.left),right:Math.abs(brand.right-card.right),marginLeft:card.left-outer.left,marginRight:outer.right-card.right,overflow:document.documentElement.scrollWidth>innerWidth+1};});
   assert.ok(g.left<1&&g.right<1,'Denied '+style+' edges differ from the separator');assert.ok(g.marginLeft>=15&&g.marginRight>=15,'Denied margins missing');assert.equal(g.overflow,false);
  }
 }
 await page.locator('.fw-preview-box').screenshot({path:output+'/'+name+'-denied.png'});
 assert.deepEqual(errors,[]);console.log('PASS V19 corrections '+name+': single-chevron sorting, list geometry, unclipped centered cancel/CSRF, native site link, palette swatch, folder contrast and all denied styles desktop/mobile');
 await context.close();
 }finally{await browser.close();}
}})().catch(error=>{console.error(error);process.exitCode=1;});
