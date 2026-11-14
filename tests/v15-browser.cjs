/** Nine approved refinements on the installed ZIP; no production requests. */
const assert=require('node:assert/strict');
module.exports=async({context,base,name})=>{
 assert.equal(process.env.FILEWARDEN_TESTING,'1');
 assert.ok(['127.0.0.1','localhost','[::1]'].includes(new URL(base).hostname),'Database repair fixtures require a loopback test site');
 const page=await context.newPage();const errors=[];page.on('pageerror',e=>errors.push(e.message));
 page.setDefaultTimeout(15000);page.setDefaultNavigationTimeout(30000);
 const admin=base+'/wp-admin/admin.php?page=';const settings=admin+'filewarden-settings';
 const dismiss=()=>page.evaluate(()=>document.querySelectorAll('[data-fw-notice]').forEach(el=>window.FileWardenNotices.dismiss(el)));
 try{
  await page.setViewportSize({width:1280,height:900});
  for(const slug of ['filewarden','filewarden-publication','filewarden-settings','filewarden-audit']){
   await page.goto(admin+slug);const logo=page.locator('.fw-heading .fw-brand-logo');
   assert.equal(await logo.count(),1);assert.equal(await logo.getAttribute('alt'),'');
   assert.ok(await logo.evaluate(el=>el.complete&&el.naturalWidth===512));
   assert.equal((await context.request.get(await logo.getAttribute('src'))).status(),200);
   const menu=page.locator('#toplevel_page_filewarden .wp-menu-image');
   assert.match(await menu.evaluate(el=>getComputedStyle(el,'::before').maskImage),/folder-lock\.svg/);
   assert.equal(await menu.evaluate(el=>getComputedStyle(el,'::before').width),'20px');
  }
  await page.goto(admin+'filewarden');
  if(!await page.locator('body').evaluate(el=>el.classList.contains('folded')))await page.locator('#collapse-button').click();
  await page.waitForFunction(()=>document.body.classList.contains('folded'));
  assert.equal(await page.locator('#toplevel_page_filewarden .wp-menu-image').evaluate(el=>getComputedStyle(el,'::before').height),'20px');
  await page.screenshot({path:`build/screenshots/v15-menu-folded-${name}.png`,fullPage:true});
  await page.locator('#collapse-button').click();
  assert.equal(await page.locator('.fw-tabs a').count(),4);assert.equal(await page.locator('.fw-tabs .fw-action-icon').count(),4);
  assert.equal(await page.locator('.fw-tabs a[aria-current]').evaluate(el=>getComputedStyle(el).backgroundColor),'rgba(0, 0, 0, 0)');
  const artwork=await page.locator('.fw-welcome-icon').evaluate(el=>getComputedStyle(el).backgroundImage);
  assert.match(artwork,/illustrations\/folder-welcome\.png/);
  const imageURL=artwork.match(/url\(["']?(.+?)["']?\)/)[1];
  const crypto=require('node:crypto');
  assert.equal(crypto.createHash('sha256').update(await(await context.request.get(imageURL)).body()).digest('hex'),'1c53db681551b02161927461483fb472484f1d31d42fdd09e76a5133e5af236b');
  await page.screenshot({path:`build/screenshots/v15-welcome-${name}.png`,fullPage:true});
  await page.goto(admin+'filewarden&tab=add');
  for(const [source,icon]of [['upload','laptop-minimal'],['import','library-big']]){
   const label=page.locator(`.filewarden-sources label:has([value=${source}])`);await label.click();
   assert.ok(await label.locator('input').isChecked());assert.equal(await label.locator('.fw-icon-'+icon).count(),1);
   assert.equal(await label.evaluate(el=>getComputedStyle(el).gap),'10px');
   assert.equal(await label.locator('.fw-action-icon').evaluate(el=>getComputedStyle(el).color),'rgb(255, 255, 255)');
  }
  await page.goto(settings);await dismiss();
  const health=page.locator('.fw-health');const details=health.locator('details');
  assert.equal(await details.getAttribute('open'),null);assert.ok((await health.boundingBox()).height<=140,'The healthy banner remains oversized');
  assert.equal(await health.getAttribute('data-storage-status'),'verified');
  assert.equal(await health.locator('.fw-health-summary time').count(),1);
  await details.locator('summary').focus();await details.locator('summary').press('Enter');
  assert.notEqual(await details.getAttribute('open'),null);assert.equal(await details.locator('dt').count(),3);
  await details.locator('summary').press('Enter');
  await page.screenshot({path:`build/screenshots/v15-health-${name}.png`,fullPage:true});
  await page.goto(settings+'&result=database_repaired');assert.equal(await page.locator('.fw-notice-success').count(),0,'Forged repair URL claimed success');
  await page.locator('[data-settings-section=storage]').click();
  const nonce=await page.locator('#fw-database-repair [name=_wpnonce]').inputValue();
  const denied=await context.request.post(base+'/wp-admin/admin-post.php',{form:{action:'filewarden_admin',operation:'database_repair',_wpnonce:'invalid',backup_confirmed:'1'}});
  assert.equal(denied.status(),403);
  const fixture=await context.request.post(base+'/wp-admin/admin-post.php',{form:{action:'fw_v15_database_fixture',_wpnonce:nonce}});assert.equal(fixture.status(),200);
  await page.goto(settings+'#fw-settings-storage');
  assert.ok(await page.locator('.fw-database-alert').isVisible());
  const repair=page.getByRole('button',{name:'Verify and repair database tables',exact:true});
  assert.ok(await repair.isVisible());
  const refused=await context.request.post(base+'/wp-admin/admin-post.php',{form:{action:'filewarden_admin',operation:'database_repair',_wpnonce:await page.locator('#fw-database-repair [name=_wpnonce]').inputValue()}});
  assert.match(refused.url(),/result=database_failed/,'Missing confirmation was accepted');
  assert.match(await page.locator('.fw-database-panel').textContent(),/MyISAM/);
  await page.locator('[name=backup_confirmed]').check();await repair.click();await page.waitForURL(/result=database_repaired/);
  assert.ok(await page.locator('.fw-notice-success').isVisible());await dismiss();
  await page.locator('[data-settings-section=storage]').click();
  assert.equal(await page.getByRole('button',{name:'Verify and repair database tables',exact:true}).count(),0);
  assert.match(await page.locator('.fw-database-panel').textContent(),/InnoDB/);
  await page.goto(admin+'filewarden-audit');
  assert.ok(await page.locator('.fw-log-simple .fw-log-category').count()>0);
  const first=page.locator('.fw-log-simple').first();assert.equal(await first.locator('details summary').count(),1);
  await first.locator('details summary').click();assert.ok(await first.locator('pre').isVisible());
  const purge=page.getByRole('button',{name:'Prepare purge',exact:true});assert.equal(await purge.locator('.fw-icon-trash-2').count(),1);
  assert.equal(await purge.evaluate(el=>getComputedStyle(el).color),'rgb(255, 255, 255)');
  await page.screenshot({path:`build/screenshots/v15-audit-${name}.png`,fullPage:true});
  const technical=page.locator('.fw-audit-views a').filter({hasText:/^Technical$/});
  await Promise.all([page.waitForURL(/view=technical/),technical.click()]);assert.equal(await page.locator('.fw-log-category').count(),0);assert.equal(await page.locator('.fw-log-simple').count(),0);
  for(const slug of ['filewarden','filewarden&tab=add','filewarden-settings','filewarden-audit']){
   await page.setViewportSize({width:375,height:812});await page.goto(admin+slug);
   assert.ok(await page.evaluate(()=>document.documentElement.scrollWidth<=innerWidth),'Mobile overflow on '+slug);
  }
  await page.screenshot({path:`build/screenshots/v15-mobile-${name}.png`,fullPage:true});
  assert.deepEqual(errors,[],'Browser errors during v15 acceptance');
  console.log(name+': 0.1.15 nine refinements, real MyISAM repair POST, nonce/backup refusal, identity/assets, semantic logs, compact health and responsive navigation passed');
 }finally{await page.close();}
};
