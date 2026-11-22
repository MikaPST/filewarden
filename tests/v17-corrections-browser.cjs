/** Corrective acceptance against the installed ZIP, real WordPress and both browser engines. */
const assert=require('node:assert/strict');
module.exports=async({context,base,name,fixture})=>{
 assert.equal(process.env.FILEWARDEN_TESTING,'1');
 assert.ok(['127.0.0.1','localhost','[::1]'].includes(new URL(base).hostname));
 const page=await context.newPage();page.setDefaultTimeout(15000);page.setDefaultNavigationTimeout(30000);
 const errors=[];page.on('pageerror',error=>errors.push(error.message));page.on('dialog',dialog=>dialog.accept());
 const ready=require('./native-admin-ready.cjs')(page,base);
 const url=base+'/wp-admin/admin.php?page=filewarden-publication';
 const tab=key=>page.locator('[data-publication-tab='+key+']');
 const frameReady=async()=>page.waitForFunction(()=>{try{return Boolean(document.querySelector('.filewarden-publication-frame').contentDocument.querySelector('#fw-public-appearance'));}catch{return false;}});
 const settled=async()=>{await page.waitForFunction(()=>{const status=document.querySelector('#fw-preview-status');return status.textContent===status.dataset.ready;});await frameReady();};
 const saveLogin=async()=>{await ready();await Promise.all([page.waitForNavigation({waitUntil:'load'}),page.locator('.fw-publication-login button[type=submit]').click({noWaitAfter:true})]);await ready();assert.equal(new URL(page.url()).searchParams.get('result'),'success');await frameReady();await page.locator('[data-preview-mode=denied]').click();await settled();};
 const endpoint=base+'/wp-admin/admin-post.php';
 const guest=await context.browser().newContext();
 const denied=endpoint+'?action=filewarden_folder&folder_id=11111111-1111-4111-8111-111111111111&return='+encodeURIComponent(base+'/documents/');
 try{
  await page.goto(base+'/wp-admin/admin.php?page=filewarden&collection=unfiled&search=v17-');await ready();
  const note=await page.locator('.fw-rule-info').evaluate(el=>{const a=el.querySelector('summary').getBoundingClientRect(),b=el.previousElementSibling.getBoundingClientRect();return {parent:el.parentElement.className,dy:Math.abs(a.top+a.height/2-b.top-b.height/2),dx:a.left-b.right};});
  assert.equal(note.parent,'fw-access-status');assert.ok(note.dy<2&&note.dx>=6&&note.dx<=12);
  await page.locator('.fw-rule-info summary').focus();await page.locator('.fw-rule-popup').waitFor({state:'visible'});await page.keyboard.press('Escape');
  await page.goto(url);await ready();await frameReady();
  const publicStyle=await page.locator('.filewarden-publication-frame').evaluate(frame=>frame.contentDocument.querySelector('link[rel=stylesheet]').href);
  const publicCSS=await(await context.request.get(publicStyle)).body();
  assert.equal(new URL(publicStyle).searchParams.get('ver'),'0.1.17-dev-'+require('node:crypto').createHash('sha256').update(publicCSS).digest('hex').slice(0,12));
  if((process.env.FILEWARDEN_BROWSER_SUFFIX||'').includes('-fr')){
   assert.equal(await page.locator('.fw-publisher').getAttribute('lang'),'fr-FR');
   assert.match(await tab('integration').textContent(),/Intégration du dossier/);
   assert.match(await tab('appearance').textContent(),/Apparence publique/);
   assert.match(await page.locator('[data-preset=custom]').textContent(),/Personnalisé/);
   assert.match(await page.locator('.fw-publication-login h3').textContent(),/Connexion des visiteurs/);
  }
  if(await page.locator('[name="publication_login[mode]"][value=custom]').isChecked()){
   await page.locator('[data-preview-mode=denied]').click();await settled();
   await page.locator('[name="publication_login[mode]"][value=wordpress]').check();await saveLogin();
   await page.locator('[data-preview-mode=authorized]').click();await settled();
  }
  assert.equal(await tab('integration').getAttribute('aria-selected'),'true');
  assert.ok(await page.locator('#fw-publish-folder').isVisible());assert.equal(await page.locator('.fw-appearance').isVisible(),false);
  const frameHandle=await page.locator('.filewarden-publication-frame').elementHandle();
  await page.locator('#fw-publish-folder').selectOption(String(fixture.public_folder_id));await settled();
  await tab('integration').focus();await page.keyboard.press('ArrowRight');assert.equal(await tab('appearance').getAttribute('aria-selected'),'true');
  assert.equal(await page.locator('.fw-appearance-preset').count(),4);assert.equal(await page.locator('[data-color-picker]').count(),10);
  const palettes=await page.locator('.fw-appearance-preset').evaluateAll(cards=>cards.map(card=>({width:card.getBoundingClientRect().width,border:getComputedStyle(card.querySelector('.fw-palette>span')).borderTopWidth})));
  assert.ok(palettes.every(card=>card.width<=170&&parseFloat(card.border)>=1));
  const colors=await page.locator('[data-color]').count();assert.equal(colors,10);
  await page.locator('[data-preset=nature]').click();
  const colorValues=await page.locator('[data-color]').evaluateAll(els=>els.map(el=>el.value));
  await page.locator('[data-preset=custom]').click();assert.deepEqual(await page.locator('[data-color]').evaluateAll(els=>els.map(el=>el.value)),colorValues);
  await page.locator('[data-preset=filewarden]').click();
  const first=page.locator('[data-color]').first();await first.fill('#f7f7f8');await first.dispatchEvent('input');
  assert.equal(await page.locator('[data-preset=custom]').getAttribute('aria-pressed'),'true');
  assert.equal(await page.locator('.fw-palette-custom').evaluate(el=>el.style.getPropertyValue('--fw-palette-background')),'#f7f7f8');
  await page.locator('#fw-public-name').evaluate(el=>el.closest('details').open=true);
  await page.locator('#fw-public-name').fill('Unsaved '+name);
  await tab('integration').click();assert.equal(await page.locator('#fw-publish-folder').inputValue(),String(fixture.public_folder_id));
  await tab('appearance').click();assert.equal(await page.locator('#fw-public-name').inputValue(),'Unsaved '+name);
  assert.ok(await frameHandle.evaluate(el=>el===document.querySelector('.filewarden-publication-frame')),'Tab navigation recreated the preview iframe');
  assert.equal(await page.locator('[data-appearance-logo] .fw-icon-upload').count(),1);
  const checks=await page.locator('.fw-appearance-checks label').evaluateAll(els=>els.map(el=>el.getBoundingClientRect().top));assert.equal(checks.length,2);assert.ok(Math.abs(checks[0]-checks[1])<2);
  await page.locator('[data-appearance-reset]').click();
  await page.locator('[data-preview-device=mobile]').click();assert.ok((await page.locator('.filewarden-publication-frame').boundingBox()).width<=375);
  await page.locator('[data-preview-device=desktop]').focus();await page.locator('[data-preview-device=desktop] .fw-action-tooltip').waitFor({state:'visible'});
  assert.ok((await page.locator('[data-preview-device=desktop]').getAttribute('aria-label')).length>0);
  await page.locator('[data-preview-device=desktop]').click();
  const deviceWidth=await page.locator('[data-preview-device=desktop]').evaluate(el=>el.getBoundingClientRect().width);assert.ok(deviceWidth<=44);
  await page.locator('[data-preview-mode=denied]').click();await settled();
  await tab('integration').click();
  assert.equal(await page.locator('.fw-login-custom').isVisible(),false);
  await page.locator('[name="publication_login[mode]"][value=custom]').check();
  const custom='https://identity.example.test/sign-in?client=filewarden&return=%2Fdocuments#form';
  await page.locator('#fw-login-url').fill('javascript:alert(1)');assert.ok(await page.locator('.fw-publication-login button[type=submit]').isDisabled());assert.equal(await page.locator('.fw-login-check').getAttribute('href'),null);
  await page.locator('#fw-login-url').fill(custom);
  assert.equal(await page.locator('.fw-login-check').getAttribute('href'),custom);
  await page.waitForFunction(expected=>document.querySelector('.filewarden-publication-frame').contentDocument.querySelector('.fw-login')?.href===expected,custom);
  assert.ok(!(await(await guest.request.get(denied)).text()).includes('identity.example.test'),'An unsaved login draft reached the public site');
  await saveLogin();
  assert.equal(await page.locator('#fw-login-url').inputValue(),custom);
  const customHTML=await(await guest.request.get(denied)).text();assert.match(customHTML,/identity\.example\.test\/sign-in\?client=filewarden(?:&amp;|&#038;)return=%2Fdocuments#form/);
  const nonce=await page.locator('.fw-publication-login [name=_wpnonce]').inputValue();
  const badNonce=await context.request.post(endpoint,{form:{action:'filewarden_admin',operation:'publication_login',_wpnonce:'invalid','publication_login[mode]':'wordpress'}});assert.equal(badNonce.status(),403);
  const unsafe=await context.request.post(endpoint,{form:{action:'filewarden_admin',operation:'publication_login',_wpnonce:nonce,'publication_login[mode]':'custom','publication_login[url]':'data:text/html,unsafe'}});assert.equal(new URL(unsafe.url()).searchParams.get('result'),'invalid');
  assert.ok((await(await guest.request.get(denied+'&publication_login%5Burl%5D=https%3A%2F%2Fevil.example.test')).text()).includes('identity.example.test'));
  const subscriber=await context.browser().newContext();
  try{await subscriber.request.get(base+'/wp-login.php');await subscriber.request.post(base+'/wp-login.php',{form:{log:'fw_subscriber',pwd:'disposable-test-password',testcookie:'1'}});const deniedSave=await subscriber.request.post(endpoint,{form:{action:'filewarden_admin',operation:'publication_login',_wpnonce:nonce,'publication_login[mode]':'wordpress'}});assert.equal(deniedSave.status(),403);}finally{await subscriber.close();}
  await page.locator('[name="publication_login[mode]"][value=wordpress]').check();await saveLogin();
  const nativeHTML=await(await guest.request.get(denied)).text();assert.match(nativeHTML,/wp-login\.php\?redirect_to=/);assert.ok(!nativeHTML.includes('identity.example.test'));
  await page.locator('[data-preview-mode=authorized]').click();await settled();
  await page.locator('#fw-publish-folder').selectOption(String(fixture.public_folder_id));await settled();
  await page.locator('#fw-publisher [name=view][value=grid]').check();await settled();
  const frame=page.frames().find(frame=>frame.url().includes('action=filewarden_folder'));
  assert.ok(frame);assert.ok(await frame.locator('.fw-ui-search').isVisible());assert.ok(await frame.locator('.fw-ui-list').isVisible());assert.ok(await frame.locator('.fw-ui-layout-grid').isVisible());
  assert.ok(await frame.locator('.actions .fw-ui-eye').count()>0);assert.ok(await frame.locator('.actions .fw-ui-download').count()>0);
  const grid=await frame.locator('.grid .document').evaluateAll(cards=>cards.map(card=>{const a=card.getBoundingClientRect(),img=card.querySelector('.file-icon').getBoundingClientRect(),b=card.querySelector('.actions').getBoundingClientRect();return {height:a.height,paddingTop:img.top-a.top,center:Math.abs(img.left+img.width/2-a.left-a.width/2),bottom:a.bottom-b.bottom,icon:card.querySelector('.file-icon').src};}));
  assert.ok(grid.length>0&&grid.every(card=>card.paddingTop>=16&&card.paddingTop<=18&&card.center<1&&card.height<300&&card.bottom>=16));
  assert.ok(grid.some(card=>card.icon.endsWith('/pdf.svg')));
  await tab('appearance').click();
  await page.screenshot({path:`build/screenshots/v17-corrections-grid-${name}.png`,fullPage:true});
  await tab('integration').click();
  await page.locator('#fw-publish-folder').selectOption(String(fixture.v17_root_id));await settled();
  const child=frame.locator('.fw-subfolders a').first();assert.ok(await child.isVisible());assert.ok((await child.boundingBox()).width<300);
  await Promise.all([frame.waitForNavigation({waitUntil:'load'}),child.click()]);
  await frame.locator('.fw-breadcrumbs [aria-current=page]').filter({hasText:'V17 nested folder'}).waitFor();
  assert.equal(await frame.locator('.fw-breadcrumbs [aria-current=page]').evaluate(el=>el.tagName),'SPAN');
  assert.ok(await frame.locator('.fw-breadcrumbs ol a').count()>=1);assert.equal(await frame.locator('.fw-breadcrumbs .fw-ui-folder-open').count(),1);
  await page.screenshot({path:`build/screenshots/v17-corrections-publication-${name}.png`,fullPage:true});
  await page.setViewportSize({width:375,height:900});assert.ok(await page.evaluate(()=>document.documentElement.scrollWidth<=innerWidth+1));
  await page.screenshot({path:`build/screenshots/v17-corrections-mobile-${name}.png`,fullPage:true});
  await page.setViewportSize({width:1280,height:900});
  const noJS=await context.browser().newContext({javaScriptEnabled:false,storageState:await context.storageState()});
  try{const fallback=await noJS.newPage();await fallback.goto(url);assert.ok(await fallback.locator('#fw-publish-folder').isVisible());assert.ok(await fallback.locator('.fw-appearance').isVisible());assert.ok(await fallback.locator('.fw-publication-login button[type=submit]').isVisible());}finally{await noJS.close();}
  const touch=await context.browser().newContext({hasTouch:true,viewport:{width:375,height:900},storageState:await context.storageState()});
  try{
   const mobile=await touch.newPage();await mobile.goto(url);await mobile.locator('.fw-appearance-devices:not([hidden])').waitFor();
   assert.ok(await mobile.evaluate(()=>matchMedia('(pointer:coarse)').matches));
   const targets=await mobile.locator('.fw-preview-controls button').evaluateAll(buttons=>buttons.map(button=>({width:button.getBoundingClientRect().width,height:button.getBoundingClientRect().height})));
   assert.ok(targets.every(target=>target.width>=44&&target.height>=44),'Preview touch targets must remain at least 44px');
   assert.ok(await mobile.evaluate(()=>document.documentElement.scrollWidth<=innerWidth+1));
   await mobile.screenshot({path:`build/screenshots/v17-corrections-touch-${name}.png`,fullPage:true});
  }finally{await touch.close();}
  assert.deepEqual(errors,[]);
  console.log(name+': ten V17 corrections passed: inline note, persistent local tabs/drafts, four templates/all colors, upload/checks, login URL persistence/CSRF/roles/unsafe rejection, preview switches, functional icons, natural compact grid, short subfolders, breadcrumb and no-JS/mobile.');
 }catch(error){await page.screenshot({path:`build/screenshots/v17-corrections-failure-${name}.png`,fullPage:true}).catch(()=>{});throw error;}
 finally{await guest.close();await page.close();}
};
