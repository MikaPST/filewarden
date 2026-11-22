/** Run new installed-ZIP acceptance before historical scenarios purge synthetic trash. */
const assert=require('node:assert/strict');
const fs=require('node:fs');
const {chromium,firefox}=require('playwright');
assert.equal(process.env.FILEWARDEN_TESTING,'1');
const base=process.env.FILEWARDEN_URL||'http://127.0.0.1:8080';
assert.ok(['127.0.0.1','localhost','[::1]'].includes(new URL(base).hostname));
(async()=>{
 for(const[name,engine]of[['chromium',chromium],['firefox',firefox]]){
  if(process.env.FILEWARDEN_BROWSER&&process.env.FILEWARDEN_BROWSER!==name)continue;
  const browser=await engine.launch();
  try{
   const context=await browser.newContext({viewport:{width:1280,height:900}});
   await context.request.get(base+'/wp-login.php');
   const login=await context.request.post(base+'/wp-login.php',{form:{log:'fw_admin',pwd:'disposable-test-password',testcookie:'1'}});
   assert.ok(login.url().includes('/wp-admin/'));
   const fixture=JSON.parse(fs.readFileSync(process.env.FILEWARDEN_FIXTURES,'utf8'));
   if(process.env.FILEWARDEN_FINAL_ONLY!=='1'){
    if(process.env.FILEWARDEN_CORRECTIONS_ONLY!=='1')await require('./v17-browser.cjs')({context,base,name:name+(process.env.FILEWARDEN_BROWSER_SUFFIX||''),fixture});
    await require('./v17-corrections-browser.cjs')({context,base,name:name+(process.env.FILEWARDEN_BROWSER_SUFFIX||''),fixture});
   }
   await require('./v17-final-corrections-browser.cjs')({context,base,name:name+(process.env.FILEWARDEN_BROWSER_SUFFIX||''),fixture});
  }finally{await browser.close();}
 }
})().catch(error=>{console.error(error);process.exitCode=1;});
