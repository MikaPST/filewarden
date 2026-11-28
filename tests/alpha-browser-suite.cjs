/** Maintained acceptance for current Alpha UI; legacy layout harnesses remain historical. */
const {spawnSync}=require('node:child_process'),path=require('node:path'),fs=require('node:fs'),assert=require('node:assert/strict');
assert.equal(process.env.FILEWARDEN_TESTING,'1');assert.equal(new URL(process.env.FILEWARDEN_URL).hostname,'127.0.0.1');
const qa=process.env.FILEWARDEN_ACCEPTANCE_DIR;assert.ok(qa&&qa.includes('.tools'));
const shots=path.resolve('build/screenshots/.tools/alpha-'+(process.env.FILEWARDEN_BROWSER_SUFFIX||'en'));fs.mkdirSync(shots,{recursive:true});
const env={...process.env,FILEWARDEN_UI_RESULT:path.join(qa,'ui-fixture.json'),FILEWARDEN_UI_SCREENSHOTS:shots,FILEWARDEN_DOCUMENT_RESULT:path.join(qa,'document-result.json'),FILEWARDEN_DOCUMENT_SCREENSHOTS:shots,FILEWARDEN_SAME_NAME_FIXTURE:path.join(qa,'same-name-fixture.json'),FILEWARDEN_SAME_NAME_SCREENSHOTS:shots,FILEWARDEN_V19_RESULT:path.join(qa,'document-result.json'),FILEWARDEN_V19_SCREENSHOTS:shots,FILEWARDEN_TEST_MEDIA:'0'};
for(const script of['v19-browser.cjs','v19-document-browser.cjs','v19-same-name-browser.cjs','v19-ui-corrections-browser.cjs','v19-viewer-browser.cjs']){
 console.log('Alpha installed-ZIP acceptance:',script);
 const current=script==='v19-viewer-browser.cjs'?{...env,FILEWARDEN_FIXTURES:env.FILEWARDEN_UI_RESULT,FILEWARDEN_VIEWER_SEARCH:'LOGOSLH2'}:env;
 const result=spawnSync(process.execPath,[path.join(__dirname,script)],{env:current,stdio:'inherit',timeout:300000});
 if(result.error)throw result.error;assert.equal(result.status,0,script+' failed');
}
