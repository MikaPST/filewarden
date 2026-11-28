/** Real encrypted upload, conditional controls, private restoration and server authorization. */
const assert=require('node:assert/strict'),fs=require('node:fs'),{chromium}=require('playwright');
assert.equal(process.env.FILEWARDEN_TESTING,'1');const base=process.env.FILEWARDEN_URL,archive=process.env.FILEWARDEN_SELECTIVE_ARCHIVE;assert.equal(new URL(base).hostname,'127.0.0.1');
(async()=>{const browser=await chromium.launch();try{
 const context=await browser.newContext({viewport:{width:1440,height:1100}});await context.request.get(base+'/wp-login.php');await context.request.post(base+'/wp-login.php',{form:{log:'fw_admin',pwd:'disposable-test-password',testcookie:'1'}});const page=await context.newPage();page.setDefaultTimeout(30000);const errors=[];page.on('pageerror',e=>errors.push(e.message));
 await page.goto(base+'/wp-admin/admin.php?page=filewarden-settings#fw-settings-backup');await page.waitForLoadState('networkidle');
 const root=page.locator('.fw-backup');if(await root.locator('.fw-backup-progress').isVisible()){await page.waitForFunction(()=>!document.querySelector('[data-backup-cancel]').disabled);await root.locator('[data-backup-cancel]').click();await root.locator('.fw-backup-progress').waitFor({state:'hidden'});}await root.locator('[data-backup-tab=restore]').click();const form=root.locator('[data-backup-start=restore]');await form.locator('[name=file]').setInputFiles(archive);await form.locator('[name=phrase]').fill('disposable selective backup phrase 2026');
 await form.locator('[type=submit]').click();const review=root.locator('.fw-backup-mappings');await review.waitFor({state:'visible'});
 assert.equal(await review.locator('[data-mapping=users]:enabled').count(),0);
 if(process.env.FILEWARDEN_SELECTIVE_EXPECT_FULL==='1')assert.ok(await review.locator('[data-mapping=roles]:enabled').count()>0);
 else{assert.equal(await review.locator('[data-mapping=roles]:enabled').count(),0);assert.ok(await review.locator('[data-backup-private-note]').isVisible());}
 const actions=root.locator('.fw-backup-actions');const boxes=await actions.locator('.button:visible').evaluateAll(els=>els.map(el=>{const b=el.getBoundingClientRect();return {x:b.x,right:b.right,y:b.y,height:b.height}}));assert.ok(boxes.length>=2);assert.ok(Math.abs(boxes[0].height-boxes[1].height)<2);assert.ok(Math.abs(boxes[0].x-boxes[1].right)>=11||Math.abs(boxes[1].x-boxes[0].right)>=11);
 if(process.env.FILEWARDEN_SELECTIVE_EXPECT_FULL==='1'){
  assert.ok(await review.locator('[data-backup-role-mappings]').isVisible());await review.locator('[data-restore-component=permissions]').uncheck();assert.ok(await review.locator('[data-backup-role-mappings]').isHidden());
  await review.locator('[data-restore-creators=map]').check();assert.ok(await review.locator('[data-backup-user-mappings]').isVisible());await review.locator('[data-restore-creators=current]').check();assert.ok(await review.locator('[data-backup-user-mappings]').isHidden());
  for(const key of['settings','appearance','audit'])await review.locator(`[data-restore-component=${key}]`).uncheck();
 }
 for(const width of[1440,375,320]){await page.setViewportSize({width,height:1000});assert.ok(await page.evaluate(()=>document.documentElement.scrollWidth<=innerWidth+1));await page.screenshot({path:`build/screenshots/ui-selective-restore-${width}.png`,fullPage:true});}
 await review.locator('[name=acknowledge]').check();await review.locator('[type=submit]').click();await review.waitFor({state:'hidden'});await page.waitForFunction(()=>document.querySelector('[data-backup-status]').textContent.includes('Library restored'));
 assert.ok(!(await form.locator('[name=phrase]').inputValue()));
 const deniedNonce=await context.request.post(base+'/wp-admin/admin-ajax.php',{form:{action:'filewarden_backup',operation:'apply',_ajax_nonce:'invalid'}});assert.equal(deniedNonce.status(),403);
 assert.deepEqual(errors,[]);console.log('PASS selective browser: actual authenticated upload, conditional mapping controls, private acknowledgment, spaced/mobile actions, server restoration and nonce refusal.');
}finally{await browser.close();}})().catch(e=>{console.error(e);process.exitCode=1});
