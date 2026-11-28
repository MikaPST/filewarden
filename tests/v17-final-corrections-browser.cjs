/** Final owner-approved publication structure/context and recovery tone, on the installed ZIP. */
const assert=require('node:assert/strict');
module.exports=async({context,base,name,fixture})=>{
 assert.equal(process.env.FILEWARDEN_TESTING,'1');assert.ok(['127.0.0.1','localhost','[::1]'].includes(new URL(base).hostname));
 const page=await context.newPage();page.setDefaultTimeout(15000);page.setDefaultNavigationTimeout(30000);
 const errors=[],failedResponses=[];page.on('pageerror',error=>errors.push({message:error.message,stack:error.stack}));page.on('dialog',dialog=>dialog.accept());
 page.on('response',response=>{if(response.status()>=400)failedResponses.push({status:response.status(),url:response.url()});});
 const ready=require('./native-admin-ready.cjs')(page,base),fr=name.endsWith('-fr');
 const tab=key=>page.locator('[data-publication-tab='+key+']');
 const mode=key=>page.locator('[data-preview-mode='+key+']');
 const settled=()=>page.waitForFunction(()=>{const status=document.querySelector('#fw-preview-status');return status.textContent===status.dataset.ready;});
 try{
  await page.goto(base+'/wp-admin/admin.php?page=filewarden&collection=unfiled&search=v17-');await ready();
  const tones=await page.locator('.fw-rule-info>summary').evaluate(el=>({tone:getComputedStyle(el).color,surface:getComputedStyle(el).backgroundColor,recovery:getComputedStyle(document.querySelector('.fw-unfiled')).color}));
  assert.equal(tones.tone,'rgb(128, 84, 0)');assert.equal(tones.surface,'rgb(255, 246, 232)');assert.equal(tones.tone,tones.recovery);
  const luminance=rgb=>rgb.map(c=>c/255).map(c=>c<=.04045?c/12.92:((c+.055)/1.055)**2.4).reduce((n,c,i)=>n+c*[.2126,.7152,.0722][i],0);
  assert.ok((luminance([255,246,232])+.05)/(luminance([128,84,0])+.05)>=4.5);
  await page.goto(base+'/wp-admin/admin.php?page=filewarden-publication&folder_id='+fixture.public_folder_id);await ready();
  await page.waitForFunction(()=>document.querySelector('.fw-appearance-devices')?.hidden===false);
  const geometry=await page.evaluate(()=>{const nav=document.querySelector('.fw-publication-tabs'),left=document.querySelector('.fw-publication-settings'),code=document.querySelector('.fw-shortcode-card'),preview=document.querySelector('.fw-preview-box');return {navOutside:!nav.closest('.fw-content'),navBottom:nav.getBoundingClientRect().bottom,leftTop:left.getBoundingClientRect().top,codeTop:code.getBoundingClientRect().top,codeBottom:code.getBoundingClientRect().bottom,previewTop:preview.getBoundingClientRect().top,codeX:code.getBoundingClientRect().x,previewX:preview.getBoundingClientRect().x,navWidth:nav.getBoundingClientRect().width,workspaceWidth:document.querySelector('.fw-publisher').getBoundingClientRect().width};});
  assert.ok(geometry.navOutside&&geometry.navBottom<geometry.leftTop&&geometry.navWidth>=geometry.workspaceWidth*.95);
  assert.ok(geometry.codeBottom<geometry.leftTop&&Math.abs(geometry.leftTop-geometry.previewTop)<2);
  assert.ok(geometry.codeBottom-geometry.codeTop<260,'The shortcode card reserves excessive empty height');
  assert.equal(await page.locator('#fw-shortcode-title').textContent(),fr?'Code court':'Shortcode');
  assert.equal(await page.locator('#fw-shortcode-help').textContent(),fr?'Copiez ce code dans le bloc « Code court » de WordPress ou dans le widget « Code court » d’Elementor.':'Copy this code into the WordPress Shortcode block or the Elementor Shortcode widget.');
  assert.equal(await page.locator('.fw-appearance-section h3').first().textContent(),fr?'Palette de couleurs':'Color palette');
  assert.equal(await page.locator('.fw-appearance-presets').locator('..').locator('p.description').textContent(),fr?'Choisissez une palette prédéfinie ou personnalisez vos couleurs, tout en conservant votre nom et votre logo personnalisés.':'Choose a preset palette or customize your colors while keeping your custom name and logo.');
  assert.equal(await page.locator('#fw-shortcode').getAttribute('readonly'),'');assert.equal(await page.locator('#fw-copy-shortcode .fw-icon-files').count(),1);
  assert.ok(await page.locator('[data-preview-config=authorized]').isVisible());assert.equal(await page.locator('[data-preview-config=denied]').first().isVisible(),false);assert.equal(await page.locator('.fw-publication-login').isVisible(),false);
  await page.locator('[name=view][value=grid]').check();await settled();
  const field=await page.locator('#fw-shortcode').elementHandle();
  const gridCode=await page.locator('#fw-shortcode').inputValue();assert.ok(gridCode.includes('view="grid"'));
  await mode('denied').click();await settled();
  assert.equal(await page.locator('[data-preview-config=authorized]').isVisible(),false);assert.ok(await page.locator('[data-preview-config=denied]').first().isVisible());assert.ok(await page.locator('.fw-publication-login').isVisible());
  await page.locator('[name=denied][value=editorial]').check();await settled();
  assert.match(await page.locator('#fw-shortcode').inputValue(),/view="grid" denied="editorial"/);
  await page.locator('[name="publication_login[mode]"][value=custom]').check();await page.locator('#fw-login-url').fill('https://identity.example.test/unsaved');
  await tab('appearance').click();assert.ok(await page.locator('.fw-shortcode-card').isVisible());
  await page.locator('#fw-public-name').evaluate(el=>el.closest('details').open=true);await page.locator('#fw-public-name').fill('Draft '+name);
  await mode('authorized').click();await settled();await tab('integration').click();
  assert.ok(await page.locator('[name=view][value=grid]').isChecked());assert.ok(await page.locator('[data-preview-config=authorized]').isVisible());assert.equal(await page.locator('.fw-publication-login').isVisible(),false);
  await mode('denied').click();await settled();assert.ok(await page.locator('[name=denied][value=editorial]').isChecked());assert.equal(await page.locator('#fw-login-url').inputValue(),'https://identity.example.test/unsaved');
  await tab('integration').focus();await page.keyboard.press('ArrowRight');assert.equal(await tab('appearance').getAttribute('aria-selected'),'true');assert.equal(await page.locator('#fw-public-name').inputValue(),'Draft '+name);
  assert.ok(await field.evaluate(el=>el===document.querySelector('#fw-shortcode')),'Output card was recreated while changing tabs');
  await page.bringToFront();if(name.startsWith('chromium'))await context.grantPermissions(['clipboard-read','clipboard-write'],{origin:base});
  await page.locator('#fw-copy-shortcode').click();await page.waitForFunction(()=>{const p=document.querySelector('#fw-copy-status');return p.textContent===p.dataset.success;});
  if(name.startsWith('chromium'))assert.equal(await page.evaluate(()=>navigator.clipboard.readText()),await page.locator('#fw-shortcode').inputValue());
  await page.evaluate(()=>Object.defineProperty(navigator,'clipboard',{configurable:true,value:{writeText:()=>Promise.reject(new DOMException('Test denial','NotAllowedError'))}}));
  await page.locator('#fw-copy-shortcode').click();await page.waitForFunction(()=>{const p=document.querySelector('#fw-copy-status');return p.textContent===p.dataset.failure;});assert.equal(await page.locator('#fw-shortcode').evaluate(el=>el===document.activeElement),true);
  await page.screenshot({path:`build/screenshots/v17-final-publication-${name}.png`,fullPage:true});
  for(const width of [375,320]){
   await page.setViewportSize({width,height:900});assert.ok(await page.evaluate(()=>document.documentElement.scrollWidth<=innerWidth+1));
   const order=await page.evaluate(()=>{const a=document.querySelector('.fw-publication-settings').getBoundingClientRect(),b=document.querySelector('.fw-shortcode-card').getBoundingClientRect(),c=document.querySelector('.fw-preview-box').getBoundingClientRect();return b.bottom<=a.top&&a.bottom<c.top;});assert.ok(order,'Mobile output order differs from shortcode → settings → preview');
   assert.ok(await page.locator('.fw-shortcode-card').evaluate(card=>card.getBoundingClientRect().bottom-card.querySelector('#fw-copy-status').getBoundingClientRect().bottom<40),'Shortcode card has excessive lower whitespace');
  }
  await page.screenshot({path:`build/screenshots/v17-final-mobile-${name}.png`,fullPage:true});
  const noJS=await context.browser().newContext({javaScriptEnabled:false,storageState:await context.storageState()});
  try{const fallback=await noJS.newPage();await fallback.goto(base+'/wp-admin/admin.php?page=filewarden-publication');assert.ok(await fallback.locator('[name=view][value=list]').isVisible());assert.ok(await fallback.locator('[name=denied][value=compact]').isVisible());assert.ok(await fallback.locator('.fw-publication-login').isVisible());assert.ok(await fallback.locator('#fw-shortcode').isVisible());}finally{await noJS.close();}
  assert.deepEqual(errors,[]);
  console.log(name+': final V17 corrections passed: external full-width tabs, contextual choices/drafts preserved, palette FR/EN, separate shortcode card/copy success/refusal, amber contrast and 375/320px/no-JS layouts.');
 }catch(error){require('node:fs').writeFileSync(`build/screenshots/v17-final-diagnostics-${name}.json`,JSON.stringify({errors,failedResponses},null,2));await page.screenshot({path:`build/screenshots/v17-final-failure-${name}.png`,fullPage:true}).catch(()=>{});throw error;}
 finally{await page.close();}
};
