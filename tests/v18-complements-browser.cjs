/** Installed WordPress acceptance for the owner-approved corrective batch. */
const assert=require('node:assert/strict'),fs=require('node:fs');
const {chromium,firefox}=require('playwright');
assert.equal(process.env.FILEWARDEN_TESTING,'1');
const base=process.env.FILEWARDEN_URL;
assert.equal(new URL(base).hostname,'127.0.0.1');
const fixture=JSON.parse(fs.readFileSync(process.env.FILEWARDEN_FIXTURES,'utf8'));
(async()=>{
 for(const[name,engine]of[['chromium',chromium],['firefox',firefox]]){
  const browser=await engine.launch();
  try{
   const context=await browser.newContext({viewport:{width:1440,height:1000},acceptDownloads:true});
   await context.request.get(base+'/wp-login.php');
   const login=await context.request.post(base+'/wp-login.php',{form:{log:'fw_admin',pwd:'disposable-test-password',testcookie:'1'}});
   assert.ok(login.url().includes('/wp-admin/'));
   const page=await context.newPage();page.setDefaultTimeout(20000);
   const errors=[];page.on('pageerror',e=>errors.push(e.message));page.on('dialog',d=>d.accept());
   const nativeReady=require('./native-admin-ready.cjs')(page,base);
   const goto=async path=>{await page.goto(base+path);await nativeReady()};
   const settings='/wp-admin/admin.php?page=filewarden-settings';
   await goto(settings+'#fw-settings-diagnostic');
   assert.equal(await page.locator('.fw-settings-links a').count(),7);
   assert.equal(await page.locator('.fw-settings-links a').last().getAttribute('data-settings-section'),'diagnostic');
   assert.equal(await page.locator('.fw-diagnostic form').count(),0);
   assert.equal(await page.locator('form .submit').first().isVisible(),false);
   await page.locator('[data-diagnostic-run]').click();await page.waitForFunction(()=>!document.querySelector('[data-diagnostic-run]').disabled);
   assert.equal(await page.locator('.fw-diagnostic-check').count(),11);
   const reportDownload=page.waitForEvent('download');await page.locator('[data-diagnostic-download]').click();
   const report=JSON.parse(fs.readFileSync(await(await reportDownload).path(),'utf8'));
   assert.equal(report.plugin.schema,'7');assert.equal(report.checks.length,11);
   assert.ok(report.checks.every(row=>['good','blocked','unknown'].includes(row.state)));
   for(const word of ['actor_ip','password','storage_key','source_key','ABSPATH','filewarden_managed_key'])assert.ok(!JSON.stringify(report).includes('"'+word+'"'));
   const config=await page.evaluate(()=>({endpoint:filewardenDiagnostic.endpoint,nonce:filewardenDiagnostic.nonce}));
   const denied=await context.request.post(config.endpoint,{form:{action:'filewarden_diagnostic',_ajax_nonce:'invalid'}});
   assert.equal(denied.status(),403);
   const anonymous=await browser.newContext();
   const anonDenied=await anonymous.request.post(config.endpoint,{form:{action:'filewarden_diagnostic',_ajax_nonce:config.nonce}});
   assert.ok([400,401,403].includes(anonDenied.status()));
   await page.screenshot({path:'build/screenshots/complements-diagnostic-'+name+(process.env.FILEWARDEN_BROWSER_SUFFIX||'')+'.png',fullPage:true});
   await page.locator('[data-settings-section=audit]').click();
   const ipBox=page.locator('[name=audit_collect_ip][type=checkbox]');
   await ipBox.check();await page.locator('#fw-audit-ip-days').fill('30');
   const saveSettings=page.locator('form:has(input[name=operation][value=settings])');
   await Promise.all([page.waitForNavigation(),saveSettings.locator('button[type=submit], input[type=submit]').click()]);
   const deniedFolder=await anonymous.request.get(base+'/wp-admin/admin-post.php?action=filewarden_folder&folder_id='+fixture.folder_uuid,{headers:{'X-Forwarded-For':'198.51.100.123'}});
   assert.ok([200,403,404].includes(deniedFolder.status()));
   assert.ok((await deniedFolder.text()).includes('fw-denied'));
   await goto('/wp-admin/admin.php?page=filewarden-audit&event=access_denied');
   const detail=page.locator('.fw-log-simple details').first();await detail.locator('summary').click();
   const audit=JSON.parse(await detail.locator('code').innerText());
   assert.equal(audit.schema_version,3);assert.equal(audit.actor_ip,'127.0.0.1');assert.equal(audit.outcome,'denied');
   await goto('/wp-admin/admin.php?page=filewarden-audit');
   const action=page.locator('[data-audit-picker]').nth(0),status=page.locator('[data-audit-picker]').nth(1);
   const a=await action.boundingBox(),s=await status.boundingBox();assert.ok(s.y>a.y+a.height);
   await action.locator('summary').click();
   const actionInputs=await action.locator('input[type=checkbox]').all();
   for(const input of actionInputs)await input.uncheck();
   await action.locator('input[value=copy]').check();
   await status.locator('summary').click();
   for(const input of await status.locator('input[type=checkbox]').all())await input.uncheck();
   await status.locator('input[value=success]').check();
   await Promise.all([page.waitForNavigation(),page.locator('#fw-audit-filters .button-primary').click()]);
   for(const item of await page.locator('.fw-log-simple details').all()){
    await item.locator('summary').click();
    const data=JSON.parse(await item.locator('code').innerText());assert.equal(data.event,'document_copy_completed');assert.equal(data.outcome,'success');
   }
   const exportLink=page.locator('.fw-export-jsonl');
   const exportResponse=await context.request.get(await exportLink.getAttribute('href'));assert.equal(exportResponse.status(),200);
   for(const line of (await exportResponse.text()).trim().split('\n').filter(Boolean)){
    const data=JSON.parse(line);assert.equal(data.event,'document_copy_completed');assert.equal(data.schema_version,3);assert.ok(Object.hasOwn(data,'actor_ip'));
   }
   await goto('/wp-admin/admin.php?page=filewarden-publication');
   const card=await page.locator('.fw-shortcode-card').boundingBox(),left=await page.locator('.fw-publication-settings').boundingBox(),right=await page.locator('.fw-preview-box').boundingBox();
   assert.ok(card.y+card.height<=left.y+1);assert.ok(Math.abs(left.y-right.y)<=2);assert.ok(card.width>left.width+right.width);
   assert.equal(await page.locator('.fw-shortcode-card').count(),1);
   const code=await page.locator('#fw-shortcode').inputValue();
   await page.locator('[data-publication-tab=appearance]').click();assert.equal(await page.locator('.fw-shortcode-card').isVisible(),true);assert.equal(await page.locator('#fw-shortcode').inputValue(),code);
   await page.locator('[data-publication-tab=integration]').click();await page.locator('[data-preview-mode=denied]').click();
   await page.locator('input[name="publication_login[mode]"][value=elementor]').check();
   await page.locator('#fw-login-popup').fill('0');assert.equal(await page.locator('.fw-publication-login button[type=submit]').isDisabled(),true);
   await page.locator('#fw-login-popup').fill('1252');assert.equal(await page.locator('.fw-publication-login button[type=submit]').isEnabled(),true);
   await page.locator('.fw-login-help summary').click();assert.equal(await page.locator('.fw-login-help a').getAttribute('target'),'_blank');
   await Promise.all([page.waitForNavigation(),page.locator('.fw-publication-login button[type=submit]').click()]);
   assert.equal(await page.locator('input[name="publication_login[mode]"][value=elementor]').isChecked(),true);
   const popupContext=await browser.newContext();
   await popupContext.addInitScript(()=>{window.__fwOpened=[];window.elementorProFrontend={modules:{popup:{showPopup:({id})=>window.__fwOpened.push(id)}}}});
   const publicPage=await popupContext.newPage();await publicPage.goto(base+'/?page_id='+fixture.complements_page_id);
   const popupLink=publicPage.frameLocator('iframe.filewarden-publication-frame').locator('.fw-login');
   await popupLink.waitFor();await popupLink.click();assert.deepEqual(await publicPage.evaluate(()=>window.__fwOpened),[1252]);
   await popupLink.press('Enter');assert.deepEqual(await publicPage.evaluate(()=>window.__fwOpened),[1252,1252]);
   await publicPage.evaluate(()=>window.postMessage({action:'popup',id:999},'*'));assert.deepEqual(await publicPage.evaluate(()=>window.__fwOpened),[1252,1252]);
   const fallbackPage=await anonymous.newPage();await fallbackPage.goto(base+'/?page_id='+fixture.complements_page_id);
   await Promise.all([fallbackPage.waitForURL(url=>url.pathname.endsWith('/wp-login.php')),fallbackPage.frameLocator('iframe.filewarden-publication-frame').locator('.fw-login').click()]);
   await page.locator('[data-preview-mode=denied]').click();await page.locator('input[name="publication_login[mode]"][value=wordpress]').check();
   await Promise.all([page.waitForNavigation(),page.locator('.fw-publication-login button[type=submit]').click()]);
   for(const width of [375,320]){
    await page.setViewportSize({width,height:1000});await goto(settings+'#fw-settings-diagnostic');
    await page.locator('[data-diagnostic-run]').click();await page.waitForFunction(()=>!document.querySelector('[data-diagnostic-run]').disabled);
    const overflow=await page.evaluate(()=>document.documentElement.scrollWidth>innerWidth);assert.equal(overflow,false,'diagnostic mobile overflow');
    await goto('/wp-admin/admin.php?page=filewarden-publication');
    assert.equal(await page.evaluate(()=>document.documentElement.scrollWidth>innerWidth),false,'publication mobile overflow');
   }
   await page.setViewportSize({width:1440,height:1000});await goto(settings+'#fw-settings-audit');
   await page.locator('[name=audit_collect_ip][type=checkbox]').uncheck();
   await Promise.all([page.waitForNavigation(),page.locator('form:has(input[name=operation][value=settings])').locator('button[type=submit], input[type=submit]').click()]);
   assert.deepEqual(errors,[]);
   console.log(name+': installed diagnostic/report/security, actual opt-in IP and spoof refusal, stacked filters/export, full-width shortcode alignment, numeric popup bridge/keyboard/fallback and mobile passed.');
  }finally{await browser.close()}
 }
})().catch(error=>{console.error(error);process.exitCode=1});
