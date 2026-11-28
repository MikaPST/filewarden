/** Actual AJAX, download and restore controls on a disposable empty destination. */
const assert=require('node:assert/strict'),fs=require('node:fs'),{chromium}=require('playwright');
assert.equal(process.env.FILEWARDEN_TESTING,'1');const base=process.env.FILEWARDEN_URL;assert.equal(new URL(base).hostname,'127.0.0.1');
(async()=>{
 const browser=await chromium.launch();try{
  const context=await browser.newContext({acceptDownloads:true,viewport:{width:1280,height:1000}});await context.request.get(base+'/wp-login.php');await context.request.post(base+'/wp-login.php',{form:{log:'fw_admin',pwd:'disposable-test-password',testcookie:'1'}});
  const page=await context.newPage();page.setDefaultTimeout(30000);const errors=[];page.on('pageerror',error=>errors.push(error.message));
  await page.goto(base+'/wp-admin/admin.php?page=filewarden-settings#fw-settings-backup');await page.locator('.fw-backup').waitFor({state:'visible'});
  const root=page.locator('.fw-backup');await root.locator('[data-backup-generate]').click();const form=root.locator('[data-backup-start=backup]');const phrase=await form.locator('[name=phrase]').inputValue();assert.match(phrase,/^[a-f0-9]{48}$/);assert.equal(await form.locator('[name=confirmation]').inputValue(),phrase);
  const csrf=await context.request.post(base+'/wp-admin/admin-ajax.php',{form:{action:'filewarden_backup',operation:'backup',phrase,confirmation:phrase,acknowledge:'1'}});assert.equal(csrf.status(),403);
  await form.locator('[name=acknowledge]').check();await form.locator('[type=submit]').click();await root.locator('[data-backup-download]').waitFor({state:'visible'});
  assert.equal(await form.locator('[name=phrase]').inputValue(),'');const download=await Promise.all([page.waitForEvent('download'),root.locator('[data-backup-download]').click()]);await download[0].saveAs(process.env.FILEWARDEN_UI_ARCHIVE);assert.ok(fs.statSync(process.env.FILEWARDEN_UI_ARCHIVE).size>56);
  const encrypted=fs.readFileSync(process.env.FILEWARDEN_UI_ARCHIVE);assert.equal(encrypted.subarray(0,8).toString(),'FWBACK01');
  const link=await root.locator('[data-backup-download]').getAttribute('href');assert.ok(!link.includes('&amp;'));const invalid=new URL(link);invalid.searchParams.delete('_wpnonce');assert.equal((await context.request.get(invalid.href)).status(),403);
  const anonymous=await browser.newContext();const denied=await anonymous.request.get(link);assert.ok([400,401,403].includes(denied.status())||denied.url().includes('wp-login'));assert.notEqual((await denied.body()).subarray(0,8).toString(),'FWBACK01');await anonymous.close();
  await root.locator('[data-backup-cancel]').click();await root.locator('.fw-backup-progress').waitFor({state:'hidden'});
  if(process.env.FILEWARDEN_BACKUP_DOWNLOAD_ONLY==='1'){assert.deepEqual(errors,[]);console.log('PASS final encrypted browser download: actual bytes, raw nonce URL, missing nonce/anonymous refusal and cleanup');return;}
  await root.locator('[data-backup-tab=restore]').click();const restore=root.locator('[data-backup-start=restore]');await restore.locator('[name=file]').setInputFiles(process.env.FILEWARDEN_MIGRATION_ARCHIVE);await restore.locator('[name=phrase]').fill('disposable migration test phrase 2026');await restore.locator('[type=submit]').click();
  const mappings=root.locator('.fw-backup-mappings');await mappings.waitFor({state:'visible'});assert.equal(await mappings.locator('[data-mapping=roles]').count(),1);
  assert.equal(await mappings.locator('[data-mapping=roles]').inputValue(),'');await mappings.locator('[data-mapping=roles]').selectOption('editor');
  const users=mappings.locator('[data-mapping=users]');for(let i=0;i<await users.count();i++){assert.equal(await users.nth(i).inputValue(),'');await users.nth(i).selectOption(i?'3':'1');}
  await mappings.locator('[name=acknowledge]').check();await mappings.locator('[type=submit]').click();await mappings.waitFor({state:'hidden'});await page.waitForFunction(()=>document.querySelector('[data-backup-status]').textContent.includes('Library restored'));
  await page.reload();assert.ok((await page.locator('.fw-backup').textContent()).includes('Sauvegarde'));
  assert.deepEqual(errors,[]);await page.screenshot({path:'build/screenshots/v18-backup-real-restored.png',fullPage:true});console.log('PASS v18 browser: nonce refusal, secure phrase, real encrypted download, cleanup, full upload/analysis, explicit mappings and restoration');
 }finally{await browser.close();}
})().catch(error=>{console.error(error);process.exitCode=1;});
