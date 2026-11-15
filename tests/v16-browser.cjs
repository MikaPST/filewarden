/** Installed UI, full opt-in flow, profile isolation and independent plugin language. */
const assert=require('node:assert/strict');
const crypto=require('node:crypto');
module.exports=async({context,base,name,fixture})=>{
 assert.equal(process.env.FILEWARDEN_TESTING,'1');
 assert.ok(['127.0.0.1','localhost','[::1]'].includes(new URL(base).hostname));
 const page=await context.newPage();page.setDefaultTimeout(15000);page.setDefaultNavigationTimeout(30000);
 const errors=[];page.on('pageerror',e=>errors.push({message:e.message,stack:e.stack,url:page.url()}));
 const admin=base+'/wp-admin/admin.php?page=';
 const settings=admin+'filewarden-settings';
 let nonce;
 const navigate=async(url)=>{await page.goto(url);await page.waitForLoadState('networkidle');};
 const save=async()=>{
  await page.waitForLoadState('networkidle');
  await Promise.all([page.waitForNavigation({waitUntil:'domcontentloaded'}),page.locator('.fw-settings form:has([name=language]) [type=submit]').click()]);
  assert.equal(new URL(page.url()).searchParams.get('result'),'success');
  await page.locator('.fw-notice-success').waitFor();
  await page.waitForLoadState('networkidle');
 };
 const settledSelection=async()=>{
  await page.waitForFunction(()=>{
   const el=document.querySelector('.fw-settings-nav [aria-selected=true]');
   return el&&getComputedStyle(el).backgroundColor==='rgb(28, 41, 66)'&&getComputedStyle(el).color==='rgb(255, 255, 255)';
  });
 };
 const environment=async(site,user,color='fresh')=>{
  const response=await context.request.post(base+'/wp-admin/admin-post.php',{form:{action:'fw_v16_locale_fixture',_wpnonce:nonce,site,user,color}});
  assert.equal(response.status(),200);const result=await response.json();assert.equal(result.success,true);
  assert.equal(result.data.site,site==='en_US'?'':site,'Locale fixture did not change the actual site setting');
 };
 const chooseLanguage=async(value)=>{
  await navigate(settings);await page.locator('#fw-language').selectOption(value);await save();
 };
 try{
  await navigate(settings);nonce=await page.locator('#fw-database-repair [name=_wpnonce]').inputValue();
  await environment('en_US','en_US');await navigate(settings);
  assert.equal(await page.locator('#fw-language').inputValue(),'auto');
  assert.equal(await page.locator('.fw-workspace').getAttribute('lang'),'en-US');
  assert.equal(await page.locator('.fw-database-panel').count(),0);
  await page.locator('.fw-health-details summary').click();
  assert.deepEqual(await page.locator('.fw-health-details dt').allTextContents(),['Storage','Database','Configuration','Last check']);
  assert.match(await page.locator('.fw-database-summary').textContent(),/three tables use InnoDB/);
  const identity=await page.locator('.fw-heading-identity').evaluate(el=>{
   const image=el.querySelector('img').getBoundingClientRect();const brand=el.querySelector('.fw-brand').getBoundingClientRect();const title=el.querySelector('h1').getBoundingClientRect();
   return {logo:image.height,brand:brand.height,logoCenter:image.y+image.height/2,brandCenter:brand.y+brand.height/2,titleTop:title.y,brandBottom:brand.bottom};
  });
  assert.equal(identity.logo,14);assert.ok(Math.abs(identity.logoCenter-identity.brandCenter)<2);assert.ok(identity.titleTop>identity.brandBottom);

  await page.locator('[data-settings-section=storage]').click();
  const optin=page.locator('#fw-allow-all-types');
  assert.equal(await optin.isChecked(),false);
  await optin.click();await page.locator('.fw-import-dialog').waitFor();
  assert.equal(await optin.isChecked(),false);
  await page.locator('[data-fw-import-cancel]').click();await page.locator('.fw-import-dialog').waitFor({state:'hidden'});
  assert.equal(await optin.isChecked(),false);
  await optin.click();await page.keyboard.press('Escape');await page.locator('.fw-import-dialog').waitFor({state:'hidden'});
  assert.equal(await optin.isChecked(),false);
  await optin.click();await page.locator('[data-fw-import-confirm]').click();
  await page.waitForFunction(()=>document.querySelector('#fw-allow-all-types').checked);
  assert.equal(await page.locator('.fw-import-active').count(),0,'Confirmation alone claimed the setting was saved');
  await page.reload();await page.waitForLoadState('networkidle');assert.equal(await optin.isChecked(),false);
  await page.locator('[data-settings-section=storage]').click();
  await optin.click();await page.locator('[data-fw-import-confirm]').click();
  await page.waitForFunction(()=>document.querySelector('#fw-allow-all-types').checked);
  await save();await page.locator('[data-settings-section=storage]').click();
  assert.equal(await optin.isChecked(),true);assert.ok(await page.locator('.fw-import-active').isVisible());
  await settledSelection();
  await page.screenshot({path:`build/screenshots/v16-all-types-${name}.png`,fullPage:true});

  await navigate(admin+'filewarden&tab=add');
  assert.equal(await page.locator('.fw-format-notice').count(),0);assert.equal(await page.locator('#fw-file').getAttribute('accept'),null);
  assert.equal(await page.evaluate(()=>Boolean(window.filewardenMedia.allowAllTypes)),true);
  await page.locator('[name=source][value=import]').check();assert.equal(await page.locator('.fw-format-notice').count(),0);
  await page.locator('[name=source][value=upload]').check();
  await page.locator('#document-parent').selectOption(String(fixture.public_folder_id));
  const suffix=crypto.randomUUID();
  await page.locator('#fw-file').setInputFiles([
   {name:'v16-'+suffix+'.html',mimeType:'text/html',buffer:Buffer.from('<html><body>Inert browser fixture</body></html>')},
   {name:'v16-'+suffix+'.bin',mimeType:'application/octet-stream',buffer:Buffer.from([0,1,2,3])}
  ]);
  assert.equal(await page.locator('.fw-upload-item').count(),2);
  await page.locator('form[data-filewarden-upload] button[type=submit]').click();
  await page.waitForFunction(()=>[...document.querySelectorAll('.fw-upload-item [role=status]')].length===2&&[...document.querySelectorAll('.fw-upload-item [role=status]')].every(el=>el.textContent==='Saved and verified'),{},{timeout:30000});
  await navigate(settings+'#fw-settings-storage');await page.locator('#fw-allow-all-types').uncheck();await save();
  await navigate(admin+'filewarden&tab=add');
  assert.equal(await page.locator('.fw-format-notice details').getAttribute('open'),null);
  assert.equal(await page.locator('.fw-format-tags span').count(),12);
  assert.ok(await page.locator('.fw-format-notice>p').isVisible());
  await page.locator('.fw-format-notice summary').click();assert.ok(await page.locator('.fw-format-tags').isVisible());
  await page.locator('[name=source][value=import]').check();assert.ok(await page.locator('.fw-format-notice').isVisible());
  await page.locator('[name=source][value=upload]').check();
  await page.locator('#fw-file').setInputFiles({name:'strict-again.html',mimeType:'text/html',buffer:Buffer.from('<html>inert</html>')});
  assert.match(await page.locator('.fw-upload-item [role=status]').textContent(),/Invalid/);

  for(const color of ['sunrise','ectoplasm']){
   await environment('en_US','en_US',color);await navigate(admin+'filewarden&tab=add');
   await page.locator('[name=source][value=import]').check();
   assert.ok((await page.locator('body').getAttribute('class')).includes('admin-color-'+color));
   const picker=page.locator('#fw-media-picker');
   assert.equal(await picker.evaluate(el=>getComputedStyle(el).backgroundColor),'rgb(95, 194, 186)');
   assert.equal(await picker.evaluate(el=>getComputedStyle(el).color),'rgb(28, 41, 66)');
   await navigate(settings+'#fw-settings-audit');
   const open=page.getByRole('link',{name:'Open audit log',exact:true});assert.equal(await open.evaluate(el=>getComputedStyle(el).backgroundColor),'rgb(95, 194, 186)');
   const selected=page.locator('.fw-settings-nav [aria-selected=true]');
   await settledSelection();
   assert.equal(await selected.evaluate(el=>getComputedStyle(el).borderTopColor),'rgb(28, 41, 66)');
   await selected.click();assert.equal(await selected.evaluate(el=>getComputedStyle(el).boxShadow),'none');
  }
  await environment('en_US','en_US');await chooseLanguage('fr_FR');
  assert.equal(await page.locator('.fw-settings-panel:not([hidden]) h2').textContent(),'Général');
  assert.equal(await page.locator('.fw-workspace').getAttribute('lang'),'fr-FR');
  assert.match(await page.locator('#menu-dashboard').textContent(),/Dashboard/,'FileWarden translated WordPress itself');
  assert.equal(await page.evaluate(()=>window.filewardenNotices.titles.success),'Opération terminée');
  await navigate(admin+'filewarden');assert.equal(await page.locator('.fw-tabs a').first().textContent(),'Documents');
  const publicURL=base+'/wp-admin/admin-post.php?action=filewarden_folder&folder_id='+fixture.public_folder_uuid;
  const frenchPublic=await context.request.get(publicURL);assert.equal(frenchPublic.status(),200);assert.match(await frenchPublic.text(),/lang="fr-FR"/);assert.match(await frenchPublic.text(),/Rechercher/);
  await navigate(settings+'#fw-settings-storage');await page.locator('#fw-allow-all-types').click();
  await page.locator('.fw-import-dialog').waitFor();assert.equal(await page.locator('#fw-import-dialog-title').textContent(),'Autoriser tous les types de fichiers ?');
  await page.locator('[data-fw-import-cancel]').click();await page.locator('.fw-import-dialog').waitFor({state:'hidden'});
  await chooseLanguage('auto');await environment('fr_FR','en_US');await navigate(settings);
  assert.equal(await page.locator('.fw-workspace').getAttribute('lang'),'fr-FR','Automatic mode followed the personal profile instead of the site');
  await environment('en_US','fr_FR');await navigate(settings);assert.equal(await page.locator('.fw-workspace').getAttribute('lang'),'en-US');
  await environment('de_DE','fr_FR');await navigate(settings);assert.equal(await page.locator('.fw-workspace').getAttribute('lang'),'en-US','Unsupported site language did not fall back to English');
  await environment('fr_CA','en_US');await navigate(settings);assert.equal(await page.locator('.fw-workspace').getAttribute('lang'),'fr-FR');
  await environment('fr_FR','fr_FR');await chooseLanguage('en_US');
  assert.equal(await page.locator('.fw-workspace').getAttribute('lang'),'en-US');
  assert.match(await page.locator('#menu-dashboard').textContent(),/Tableau de bord/);
  assert.equal(await page.evaluate(()=>window.filewardenNotices.titles.success),'Operation completed');
  for(const width of [375,320]){
   await page.setViewportSize({width,height:812});await navigate(settings);
   assert.ok(await page.evaluate(()=>document.documentElement.scrollWidth<=innerWidth),'Language settings overflow '+width);
   await settledSelection();
   await page.screenshot({path:`build/screenshots/v16-language-${name}-${width}.png`,fullPage:true});
   await navigate(settings+'#fw-settings-storage');await page.locator('#fw-allow-all-types').click();await page.locator('.fw-import-dialog').waitFor();
   assert.ok(await page.evaluate(()=>document.documentElement.scrollWidth<=innerWidth),'Confirmation overflows '+width);
   await page.locator('[data-fw-import-cancel]').click();await page.locator('.fw-import-dialog').waitFor({state:'hidden'});
   await navigate(admin+'filewarden&tab=add');assert.equal(await page.locator('.fw-format-notice details').getAttribute('open'),null);
  }
  await page.setViewportSize({width:1280,height:900});
  await environment('en_US','en_US');await chooseLanguage('auto');
  await navigate(admin+'filewarden&collection=unfiled');
  const badge=page.locator('.fw-recovery-count');
  if(await badge.count()){assert.match(await badge.textContent(),/^\d+$/);assert.equal(await badge.evaluate(el=>getComputedStyle(el).backgroundColor),'rgb(128, 84, 0)');}
  const first=page.locator('tbody tr').first();
  if(await first.count()){
   await first.locator('.fw-context summary').click();
   await page.getByRole('link',{name:'Move',exact:true}).first().click();
   await page.locator('#fw-destination').waitFor();
   assert.equal(await page.locator('[data-classify-later]').count(),0);
   assert.equal(await page.locator('#fw-destination option[value="0"]').count(),0);
   assert.equal(await page.getByRole('button',{name:'Review destination',exact:true}).locator('.fw-icon-folder-check').count(),1);
  }
  await require('./v16-controls-browser.cjs')({context,base,name,fixture});
  assert.deepEqual(errors,[]);
  console.log(name+': V16 installed UI, save-confirmation flow, arbitrary multi-upload, strict restoration, profile colors, FR/EN/site/profile/fallback translation isolation, health and narrow layouts passed');
 }finally{
  if(nonce){
   await context.request.post(base+'/wp-admin/admin-post.php',{form:{action:'filewarden_admin',operation:'settings',_wpnonce:nonce,allow_all_types:'0',language:'auto'}}).catch(()=>{});
   await environment('en_US','en_US').catch(()=>{});
  }
  await page.close();
 }
};
