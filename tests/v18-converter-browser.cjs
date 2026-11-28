/** Diagnostic links and explicit SQL maintenance through real nonce-protected AJAX. */
const assert=require('node:assert/strict'),{chromium}=require('playwright');
assert.equal(process.env.FILEWARDEN_TESTING,'1');const base=process.env.FILEWARDEN_URL;assert.equal(new URL(base).hostname,'127.0.0.1');
(async()=>{const browser=await chromium.launch();try{
 const context=await browser.newContext({viewport:{width:1440,height:1100}});await context.request.get(base+'/wp-login.php');await context.request.post(base+'/wp-login.php',{form:{log:'fw_admin',pwd:'disposable-test-password',testcookie:'1'}});const page=await context.newPage();page.setDefaultTimeout(30000);const errors=[];page.on('pageerror',e=>errors.push(e.message));
 await page.goto(base+'/wp-admin/admin.php?page=filewarden-settings#fw-settings-diagnostic');await page.waitForLoadState('networkidle');
 await page.locator('[data-diagnostic-run]').click();await page.waitForFunction(()=>!document.querySelector('[data-diagnostic-run]').disabled);
 const report=await page.locator('.fw-diagnostic-report pre').textContent();const initial=JSON.parse(report);assert.equal(initial.checks.find(row=>row.id==='wp_tables').action,'wordpress_tables');
 const link=page.locator('a[data-fw-repair-target=fw-wp-table-conversion]');assert.equal(await link.count(),1);
 // Re-render on navigation if an earlier page did not yet contain the repair helper.
 await page.locator('#fw-wp-table-conversion').evaluate(el=>el.remove());await Promise.all([page.waitForNavigation({waitUntil:'networkidle'}),link.click()]);
 const root=page.locator('#fw-wp-table-conversion');assert.ok(await root.isVisible());assert.ok(await root.evaluate(el=>el.classList.contains('fw-helper-highlight')));
 assert.ok(await root.locator('[data-wp-table-convert]').isDisabled());assert.ok((await root.textContent()).includes('myisam'));
 const config=await page.evaluate(()=>({endpoint:filewardenWordPressTables.endpoint,nonce:filewardenWordPressTables.nonce}));
 const bad=await context.request.post(config.endpoint,{form:{action:'filewarden_wp_tables',operation:'preflight',_ajax_nonce:'invalid'}});assert.equal(bad.status(),403);
 await root.locator('[data-wp-table-check]').click();await page.waitForFunction(()=>!document.querySelector('[data-wp-table-check]').disabled);await root.locator('[data-wp-table-review]').waitFor({state:'visible'});
 assert.ok(await root.locator('[data-wp-table-convert]').isDisabled());await root.locator('[data-wp-table-backup]').check();assert.ok(await root.locator('[data-wp-table-convert]').isDisabled());await root.locator('[data-wp-table-maintenance]').check();
 if(await root.locator('[data-wp-table-disk]').isVisible())await root.locator('[data-wp-table-disk]').check();
 for(const width of[1440,375,320]){await page.setViewportSize({width,height:1000});assert.ok(await page.evaluate(()=>document.documentElement.scrollWidth<=innerWidth+1));await page.screenshot({path:`build/screenshots/ui-converter-${width}.png`,fullPage:true});}
 assert.ok(await root.locator('[data-wp-table-convert]').isEnabled());await root.locator('[data-wp-table-convert]').click();await root.locator('[data-wp-table-recheck]').waitFor({state:'visible'});
 await root.locator('[data-wp-table-recheck]').click();await page.waitForFunction(()=>document.querySelector('[data-settings-section=diagnostic]').getAttribute('aria-selected')==='true');await page.waitForFunction(()=>!document.querySelector('[data-diagnostic-run]').disabled);
 const final=JSON.parse(await page.locator('.fw-diagnostic-report pre').textContent());assert.equal(final.checks.find(row=>row.id==='wp_tables').state,'good');assert.equal(final.checks.find(row=>row.id==='jobs').state,'good');
 assert.deepEqual(errors,[]);console.log('PASS converter browser: actionable diagnostic reload/link, no automatic conversion, nonce/confirmation gates, actual three-table conversion, 320px layout and diagnostic recheck.');
}finally{await browser.close();}})().catch(e=>{console.error(e);process.exitCode=1});
