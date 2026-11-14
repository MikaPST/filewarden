/** Localized repair controls must fit narrow screens and submit their own confirmed form. */
const assert=require('node:assert/strict');
module.exports=async({context,base,name})=>{
 assert.equal(process.env.FILEWARDEN_TESTING,'1');
 assert.ok(['127.0.0.1','localhost','[::1]'].includes(new URL(base).hostname));
 const page=await context.newPage();page.setDefaultTimeout(15000);let nonce;
 try{
  await page.goto(base+'/wp-admin/admin.php?page=filewarden-settings#fw-settings-storage');
  nonce=await page.locator('#fw-database-repair [name=_wpnonce]').inputValue();
  const fixture=await context.request.post(base+'/wp-admin/admin-post.php',{form:{action:'fw_v15_database_fixture',_wpnonce:nonce}});
  assert.equal(fixture.status(),200);assert.equal((await fixture.json()).success,true);
  await page.reload(); // A same-fragment navigation would leave pre-conversion metadata on screen.
  for(const width of [375,320]){
   await page.setViewportSize({width,height:812});
   await page.screenshot({path:`build/screenshots/v15-repair-fr-${name}-${width}.png`,fullPage:true});
   const fit=await page.evaluate(()=>({width:innerWidth,scroll:document.documentElement.scrollWidth,overflow:[...document.querySelectorAll('.fw-workspace *')].filter(el=>el.getBoundingClientRect().right>innerWidth+1).map(el=>({tag:el.tagName,cls:el.className,right:el.getBoundingClientRect().right,text:el.textContent.slice(0,90)})).slice(0,8)}));
   assert.ok(fit.scroll<=fit.width,'French repair layout overflows: '+JSON.stringify(fit));
   assert.ok(await page.locator('.fw-database-alert').isVisible());
   assert.equal(await page.locator('button[form="fw-database-repair"]').textContent(),'Vérifier et réparer les tables de la base');
  }
  await page.locator('[name=backup_confirmed]').check();
  await Promise.all([page.waitForURL(/result=database_repaired/,{timeout:30000}),page.locator('button[form="fw-database-repair"]').click()]);
  assert.equal(await page.locator('.fw-notice-success .fw-notice-title').textContent(),'Base de données vérifiée');
  console.log(name+': French repair confirmation and responsive 375px/320px controls passed');
 }finally{
  if(nonce)await context.request.post(base+'/wp-admin/admin-post.php',{form:{action:'filewarden_admin',operation:'database_repair',_wpnonce:nonce,backup_confirmed:'1'}}).catch(()=>{});
  await page.close();
 }
};
