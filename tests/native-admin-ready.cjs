/** Await native profile hydration without waiting for unrelated WordPress traffic. */
const assert=require('node:assert/strict');
module.exports=(page,base)=>{
 const origin=new URL(base).origin;
 const pending=new Map();
 const nativeProfile=request=>{
  const url=new URL(request.url());
  const route=url.searchParams.get('rest_route')||url.pathname.replace(/^.*\/wp-json/,'');
  return request.method()==='GET'&&url.origin===origin&&route==='/wp/v2/users/me';
 };
 page.on('request',request=>{
  if(nativeProfile(request)){
   let finish;const promise=new Promise(resolve=>{finish=resolve;});
   pending.set(request,{promise,finish});
  }
 });
 const finish=request=>{
  const item=pending.get(request);
  if(item){pending.delete(request);item.finish(request.failure());}
 };
 page.on('requestfinished',finish);page.on('requestfailed',finish);
 return async()=>{
  await page.waitForLoadState('load');
  let timer;
  try{
   await Promise.race([
    (async()=>{
     while(pending.size){
      const results=await Promise.all([...pending.values()].map(item=>item.promise));
      for(const failure of results)assert.equal(failure,null,'Native WordPress profile request failed: '+JSON.stringify(failure));
     }
     // Let the browser process the completed JSON response before another navigation.
     await page.evaluate(()=>true);
    })(),
    new Promise((_,reject)=>{timer=setTimeout(()=>reject(new Error('Native WordPress profile hydration did not finish: '+[...pending.keys()].map(r=>r.url()).join(', '))),30000);})
   ]);
  }finally{clearTimeout(timer);}
 };
};
