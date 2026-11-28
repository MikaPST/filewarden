/** Installed-ZIP library and contrast acceptance on isolated local WordPress. */
const assert=require('node:assert/strict'),fs=require('node:fs');
const {chromium,firefox}=require('playwright');
assert.equal(process.env.FILEWARDEN_TESTING,'1');
const base=process.env.FILEWARDEN_URL, fixture=JSON.parse(fs.readFileSync(process.env.FILEWARDEN_FIXTURES,'utf8'));
assert.ok(['127.0.0.1','localhost'].includes(new URL(base).hostname));
(async()=>{
 for(const[name,engine]of[['chromium',chromium],['firefox',firefox]]){
  const browser=await engine.launch();try{
   const context=await browser.newContext({viewport:{width:1440,height:1000}});
   await context.request.get(base+'/wp-login.php');await context.request.post(base+'/wp-login.php',{form:{log:'fw_admin',pwd:'disposable-test-password',testcookie:'1'}});
   const page=await context.newPage();page.setDefaultTimeout(20000);page.on('dialog',dialog=>dialog.accept());const errors=[];page.on('pageerror',error=>errors.push(error.message));
   const nativeReady=require('./native-admin-ready.cjs')(page,base);
   const navigate=async path=>{await page.goto(base+path);await nativeReady();};
   const folder=id=>'/wp-admin/admin.php?page=filewarden&folder_id='+id;
   await navigate(folder(fixture.public_folder_id));
   const item=page.locator(`[data-rename-id="${fixture.public}"]`);assert.equal(await item.count(),1);assert.equal(await page.locator('.fw-selection select option[value="'+fixture.public_folder_id+'"]').count(),0);
   const original=await item.getAttribute('data-rename-name');const renamed='UI-'+original;
   const rename=async value=>{await page.locator(`[data-rename-id="${fixture.public}"]`).click();const dialog=page.locator('dialog.fw-library-dialog');await dialog.locator('input').fill(value);await dialog.locator('input').focus();assert.equal(await dialog.locator('input').evaluate(el=>getComputedStyle(el).outlineColor),'rgb(28, 41, 66)');await Promise.all([page.waitForNavigation(),dialog.locator('button[type=submit]').click()]);};
   await rename(renamed);assert.equal(await page.locator(`[data-rename-id="${fixture.public}"]`).getAttribute('data-rename-name'),renamed);await rename(original);
   const move=async destination=>{
    await page.locator(`[data-document-selection][value="${fixture.public}"]`).check();await page.locator('.fw-selection select').selectOption(String(destination));await page.locator('.fw-selection button[type=submit]').click();
    const dialog=page.locator('dialog.fw-library-dialog');await dialog.waitFor({state:'visible'});assert.equal(await dialog.locator('li').count(),1);assert.ok((await dialog.textContent()).includes(original));
    await Promise.all([page.waitForNavigation(),dialog.locator('button[type=submit]').click()]);
   };
   await move(fixture.folder_id);assert.equal(await page.locator(`[data-document-selection][value="${fixture.public}"]`).count(),0);
   await navigate(folder(fixture.folder_id));await move(fixture.public_folder_id);
   await navigate(folder(fixture.public_folder_id));
   await page.locator('[name=scope]').selectOption('all');await page.locator('#fw-search').fill(original);await page.locator('[data-filter-toggle]').click();await page.locator('[name=max_size]').fill('0');
   await Promise.all([page.waitForNavigation(),page.locator('.fw-search-main button').click()]);assert.equal(await page.locator('[data-document-selection]').count(),0);
   await navigate(folder(fixture.public_folder_id)+'&scope=all&search='+encodeURIComponent(original));assert.ok(await page.locator('.fw-document-location').count()>0);
   await navigate('/wp-admin/admin.php?page=filewarden-publication&folder_id='+fixture.public_folder_id);
   await page.locator('[data-publication-tab=appearance]').click();
   const report=page.locator('.fw-contrast-report');assert.ok(['aa','aaa'].includes(await report.getAttribute('data-level')));assert.equal(await report.locator('.fw-contrast-segments span').count(),8);assert.ok((await report.locator('.fw-contrast-segments span').first().boundingBox()).height>=5);
   const fill=async(key,value)=>page.locator(`[data-color="${key}"]`).fill(value);
   await fill('text','#777777');assert.equal(await report.getAttribute('data-level'),'improve');assert.ok(await page.locator('.fw-appearance button[type=submit]').isDisabled());
   await fill('text','#zz');assert.equal(await report.getAttribute('data-level'),'incomplete');
   for(const[key,value]of Object.entries({text:'#000000',background:'#ffffff',surface:'#ffffff',button:'#000000',hover:'#000000',button_text:'#ffffff',link:'#000000',muted:'#000000'})){
    const input=page.locator(`[data-color="${key}"]`);await input.evaluate((el,value)=>{el.value=value;el.dispatchEvent(new Event('input',{bubbles:true}));},value);
   }
   assert.equal(await report.getAttribute('data-level'),'aaa');assert.equal(await report.locator('[data-pass=true]').count(),8);
   await report.locator('summary').click();assert.equal(await report.locator('[data-contrast-ratio]').count(),8);assert.ok((await report.textContent()).includes('21'));
   await page.evaluate(()=>window.scrollTo(0,0));await page.screenshot({path:`build/screenshots/v18-contrast-${name}${process.env.FILEWARDEN_BROWSER_SUFFIX||''}.png`,fullPage:true});
   await page.locator('[data-preset=filewarden]').click();
   await navigate('/wp-admin/admin.php?page=filewarden-settings#fw-settings-backup');
   assert.equal(await page.locator('[data-settings-section=backup]').getAttribute('aria-selected'),'true');assert.ok(await page.locator('.fw-backup').isVisible());
   assert.equal(await page.locator('.fw-settings-links [role=tab]').count(),7);
   const invalidNesting=await page.locator('.fw-settings-layout').evaluate(el=>[...el.querySelectorAll('form')].some(form=>form.parentElement.closest('form')));assert.equal(invalidNesting,false);
   for(const width of[375,320]){await page.setViewportSize({width,height:900});const box=await page.locator('.fw-backup').boundingBox();assert.ok(box.width<=width);await page.screenshot({path:`build/screenshots/v18-backup-${name}-${width}${process.env.FILEWARDEN_BROWSER_SUFFIX||''}.png`,fullPage:true});}
   assert.deepEqual(errors,[]);console.log('PASS v18 '+name+(process.env.FILEWARDEN_BROWSER_SUFFIX||'')+': rename/move/confirmation, global filters, AA/AAA/incomplete, settings forms and mobile layouts');
  }finally{await browser.close();}
 }
})().catch(error=>{console.error(error);process.exitCode=1;});
