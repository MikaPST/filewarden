/** Verify the installed shared controls, including narrow-column badge alignment. */
const assert=require('node:assert/strict');
module.exports=async({context,base,name,fixture})=>{
 const page=await context.newPage();page.setDefaultTimeout(15000);
 const navigate=async(url)=>{await page.goto(url);await page.waitForLoadState('networkidle');};
 try{
  await navigate(base+'/wp-admin/admin.php?page=filewarden&collection=unfiled');
  const badge=page.locator('.fw-recovery-count');assert.equal(await badge.count(),1);
  const position=await badge.evaluate(el=>{
   const b=el.getBoundingClientRect(),p=el.parentElement.getBoundingClientRect();
   return {center:b.y+b.height/2,parentCenter:p.y+p.height/2,right:b.right,parentRight:p.right};
  });
  assert.ok(Math.abs(position.center-position.parentCenter)<1,'Badge dropped below its label');
  assert.ok(position.right<=position.parentRight+1);
  assert.equal(await page.locator('.fw-search button').evaluate(el=>getComputedStyle(el).backgroundColor),'rgb(95, 194, 186)');
  const active=page.locator('.fw-tabs a[aria-current]');
  assert.equal(await active.evaluate(el=>getComputedStyle(el).borderBottomColor),'rgb(28, 41, 66)');
  await Promise.all([page.waitForNavigation(),active.click()]);await page.waitForLoadState('networkidle');assert.equal(await active.evaluate(el=>getComputedStyle(el).boxShadow),'none');
  await page.keyboard.press('Tab');await active.focus();
  assert.equal(await active.evaluate(el=>el.matches(':focus-visible')),true);
  assert.equal(await active.evaluate(el=>getComputedStyle(el).outlineColor),'rgb(28, 41, 66)');
  assert.equal(await active.evaluate(el=>getComputedStyle(el).outlineStyle),'solid');
  await navigate(base+'/wp-admin/admin.php?page=filewarden&folder_id='+fixture.public_folder_id);
  const preview=page.locator('.fw-row-actions [data-filewarden-preview]').first();
  assert.equal(await preview.locator('.fw-icon-eye').count(),1);
  await preview.hover();assert.ok(await preview.locator('.fw-action-tooltip').isVisible());
  assert.equal(await preview.evaluate(el=>getComputedStyle(el).color),'rgb(28, 41, 66)');
  const download=page.locator('.fw-row-actions a:has(.fw-icon-download)').first();
  assert.ok((await download.getAttribute('href')).includes('download=1'));
  await page.keyboard.press('Tab');await download.focus();assert.ok(await download.locator('.fw-action-tooltip').isVisible());
  await page.screenshot({path:`build/screenshots/v16-controls-${name}.png`,fullPage:true});
  await navigate(base+'/wp-admin/admin.php?page=filewarden-audit');
  await page.mouse.move(0,0);
  await page.waitForFunction(()=>[...document.querySelectorAll('.fw-export-button')].every(el=>getComputedStyle(el).backgroundColor==='rgb(241, 245, 249)'));
  assert.equal(await page.locator('.fw-export-button').count(),3);
  for(const button of await page.locator('.fw-export-button').all()){
   assert.equal(await button.locator('.fw-icon-download').count(),1);
   assert.equal(await button.evaluate(el=>getComputedStyle(el).color),'rgb(28, 41, 66)');
   assert.equal(await button.evaluate(el=>getComputedStyle(el).backgroundColor),'rgb(241, 245, 249)');
  }
  console.log(name+': badge alignment, turquoise search, Navy active/keyboard states, refined actions/tooltips and export controls passed');
 }finally{await page.close();}
};
