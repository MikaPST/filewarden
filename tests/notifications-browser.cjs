/** Real installed component: server outcomes, clocks, combined pauses, overflow and keyboard access. */
const assert = require('node:assert/strict');
module.exports = async ({ context: parentContext, base, name = 'browser' }) => {
    // Playwright's clock is context-scoped: do not freeze WordPress timers in later suites.
    const context = await parentContext.browser().newContext({storageState:await parentContext.storageState(),viewport:{width:1280,height:900}});
    const page = await context.newPage();
    const settingsURL = base + '/wp-admin/admin.php?page=filewarden-settings';
    const reset = () => page.evaluate(() => document.querySelectorAll('.fw-notice[data-fw-notice]').forEach(el => window.FileWardenNotices.dismiss(el)));
    const show = (type, message, persistent) => page.evaluate(({type,message,persistent}) => {
        window.FileWardenNotices.show({type,message,persistent});
    }, {type,message,persistent});
    const progress = locator => locator.locator('.fw-notice-progress').evaluate(el => Number(el.style.transform.match(/scaleX\(([^)]+)\)/)[1]));
    try {
        await page.clock.install({time:new Date(2100, 0, 1)});
        await page.clock.pauseAt(new Date(2100, 0, 1));
        await page.goto(settingsURL + '&result=success');
        assert.equal(await page.locator('.fw-notice-success').count(), 0, 'A forged result URL claimed success');
        assert.equal(await page.locator('[name=notification_seconds]').inputValue(), '10');
        assert.deepEqual(await page.locator('[name=notification_seconds] option').evaluateAll(nodes => nodes.map(n=>n.value)), ['5','10','15','20','30']);
        const settingsForm = page.locator('form').filter({has:page.locator('[name=notification_seconds]')});
        const original = await settingsForm.evaluate(form => Object.fromEntries(new FormData(form)));
        const endpoint = await settingsForm.getAttribute('action');
        const noNonce = await context.request.post(endpoint,{form:{...original,notification_seconds:'5',_wpnonce:'invalid'}});
        assert.equal(noNonce.status(),403,'Notification preference accepted an invalid CSRF nonce');
        const invalid = await context.request.post(endpoint,{form:{...original,notification_seconds:'6'}});
        assert.match(invalid.url(),/result=invalid/);
        for (const duration of ['5','10','15','20','30','10']) {
            await page.goto(settingsURL);
            await page.locator('[name=notification_seconds]').selectOption(duration);
            await page.getByRole('button',{name:'Save settings',exact:true}).click();
            await page.waitForURL(/result=success/);
            const confirmed = page.locator('.fw-notice-success[data-fw-notice]');
            await confirmed.waitFor();
            assert.match(await confirmed.locator('.fw-notice-title').textContent(),/Settings saved/);
            await page.reload();
            assert.equal(await page.locator('[name=notification_seconds]').inputValue(),duration);
            for (const field of ['upload_mb','trash_days','audit_days']) assert.equal(await page.locator(`[name=${field}]`).inputValue(),original[field]);
            await reset(); await show('success','duration-'+duration,false);
            const card = page.locator('.fw-notice-success[data-fw-notice]');
            await page.mouse.move(0,0);
            await page.evaluate(()=>window.dispatchEvent(new Event('focus')));
            await page.clock.runFor(Number(duration)*500);
            assert.ok(Math.abs(await progress(card)-0.5)<0.015,'Progress was not halfway through the configured duration');
            await page.clock.runFor(Number(duration)*500+200);
            assert.equal(await card.count(),0,'Temporary notification did not expire');
        }
        await reset();
        const colors = new Set();
        for (const type of ['success','info','warning','error']) {
            await show(type,'Temporary '+type,false);
            const card = page.locator(`.fw-notice-${type}[data-fw-notice]`);
            assert.ok(await card.locator('.fw-notice-track').isVisible());
            assert.equal(await card.getAttribute('role'),type==='error'?'alert':'status');
            colors.add(await card.locator('.fw-notice-title').evaluate(el=>getComputedStyle(el).color));
            await page.clock.runFor(10200);
            assert.equal(await card.count(),0);
        }
        assert.equal(colors.size,4,'Notification types are not visually distinct');
        await show('success','First independent timer',false);
        await page.clock.runFor(2000);
        await show('info','Second independent timer',false);
        await page.clock.runFor(2000);
        const second = page.locator('.fw-notice-info[data-fw-notice]');
        const remainingSecond = await progress(second);
        await page.locator('.fw-notice-success .fw-notice-close').click();
        await page.clock.runFor(16);
        assert.ok(Math.abs(await progress(second)-remainingSecond)<0.01,'Closing one card reset another countdown');
        await reset(); await show('info','Combined pause',false);
        const paused = page.locator('.fw-notice-info[data-fw-notice]');
        await page.clock.runFor(2000);
        await paused.hover(); const beforeHover = await progress(paused);
        await page.clock.runFor(5000);
        assert.ok(Math.abs(await progress(paused)-beforeHover)<0.005,'Hover did not pause the countdown');
        await paused.locator('button').focus();
        await page.mouse.move(0,0);
        await page.clock.runFor(5000);
        assert.ok(Math.abs(await progress(paused)-beforeHover)<0.005,'Leaving hover resumed a still-focused notification');
        await page.locator('[name=notification_seconds]').focus();
        await page.clock.runFor(2000);
        assert.ok(Math.abs(await progress(paused)-(beforeHover-0.2))<0.015,'Focus pause resumed from zero or failed to resume');
        await page.evaluate(()=>{Object.defineProperty(document,'hidden',{configurable:true,value:true});document.dispatchEvent(new Event('visibilitychange'));});
        const beforeHidden = await progress(paused);
        await page.clock.fastForward(60000);
        await page.evaluate(()=>{Object.defineProperty(document,'hidden',{configurable:true,value:false});document.dispatchEvent(new Event('visibilitychange'));});
        await page.clock.runFor(16);
        assert.ok(Math.abs(await progress(paused)-beforeHidden)<0.01,'Inactive tab consumed the reading time');
        await page.evaluate(()=>window.dispatchEvent(new Event('blur')));
        const beforeBlur = await progress(paused);
        await page.clock.fastForward(60000);
        await page.evaluate(()=>window.dispatchEvent(new Event('focus')));
        await page.clock.runFor(16);
        assert.ok(Math.abs(await progress(paused)-beforeBlur)<0.01,'Inactive window consumed the reading time');
        await reset(); await show('error','Intervention needed',true);
        const error = page.locator('.fw-notice-error[data-fw-notice]');
        assert.equal(await error.getAttribute('role'),'alert');
        assert.equal(await error.locator('.fw-notice-track').isVisible(),false);
        await page.clock.fastForward(120000);
        assert.ok(await error.isVisible(),'Persistent error expired');
        await error.getByRole('button',{name:'Dismiss notification',exact:true}).focus();
        await error.getByRole('button',{name:'Dismiss notification',exact:true}).press('Enter');
        assert.equal(await error.count(),0);
        assert.equal(await page.locator('.fw-workspace h1').evaluate(el=>el===document.activeElement),true);
        // Overflow retains a hidden persistent error and pauses hidden temporary cards.
        await show('error','Old persistent error',true);
        await show('info','Old temporary card',false);
        for (const type of ['success','warning','info']) await show(type,'Visible '+type,true);
        assert.equal(await page.locator('.fw-notice[data-fw-notice]:visible').count(),3);
        assert.match(await page.locator('.fw-notice-more').textContent(),/Persistent notifications waiting: 1/);
        await page.clock.fastForward(60000);
        await page.locator('.fw-notice-more').click();
        const old = page.locator('.fw-notice').filter({hasText:'Old temporary card'});
        assert.ok(await old.isVisible());
        assert.ok(await progress(old)>0.99,'Hidden overflow notification consumed its lifetime');
        await page.locator('.fw-notice-more').click();
        await page.clock.runFor(250);
        await page.screenshot({path:`build/screenshots/notifications-desktop-${name}.png`,fullPage:true});
        // A late server failure must surface even if its pending card was in overflow.
        await page.evaluate(()=>{
            const card=[...document.querySelectorAll('.fw-notice')].find(el=>el.textContent.includes('Old temporary card'));
            window.FileWardenNotices.update(card,{type:'error',message:'Late server refusal',persistent:true});
        });
        assert.ok(await page.locator('.fw-notice-error').filter({hasText:'Late server refusal'}).isVisible(),'A late error remained silently hidden in overflow');
        await page.setViewportSize({width:375,height:812});
        assert.ok(await page.evaluate(()=>document.documentElement.scrollWidth<=innerWidth));
        const boxes = await page.locator('.fw-notice[data-fw-notice]:visible').evaluateAll(nodes=>nodes.map(n=>{const r=n.getBoundingClientRect();return {top:r.top,bottom:r.bottom,left:r.left,right:r.right};}));
        for(let i=1;i<boxes.length;i++) assert.ok(boxes[i].top>=boxes[i-1].bottom,'Notification cards overlap');
        assert.ok(boxes.every(b=>b.left>=0&&b.right<=375),'Mobile notification is clipped');
        await page.screenshot({path:`build/screenshots/notifications-mobile-${name}.png`,fullPage:true});
        await page.setViewportSize({width:1280,height:900});
        await page.emulateMedia({reducedMotion:'reduce'}); await reset();
        await show('success','<img src=x onerror=alert(1)>',false);
        assert.equal(await page.locator('.fw-notice img').count(),0,'Notification rendered user content as HTML');
        assert.equal(await page.locator('.fw-notice').evaluate(el=>getComputedStyle(el).animationName),'none');
        // Foreign WordPress/plugin notices must remain untouched.
        await page.evaluate(()=>{const el=document.createElement('div');el.className='notice notice-info foreign-notice';el.textContent='Other plugin';document.querySelector('#wpbody-content').append(el);});
        await page.clock.runFor(16);
        assert.equal(await page.locator('.foreign-notice [type=button]').count(),0);
        assert.equal(await page.locator('.foreign-notice').getAttribute('data-fw-notice'),null);
        await page.goto(base+'/wp-admin/admin.php?page=filewarden&tab=trash');
        await page.locator('.fw-notice-info[data-fw-notice]').waitFor();
        assert.equal(await page.locator('.fw-notice-info .dashicons').count(),1);
        // Server-rendered notices remain readable and persistent without JavaScript.
        const fallback = await context.browser().newContext({javaScriptEnabled:false});
        try {
            await fallback.addCookies(await context.cookies());
            const noScript = await fallback.newPage();
            await noScript.goto(base+'/wp-admin/admin.php?page=filewarden&result=denied');
            const notice = noScript.locator('.fw-notice.fw-notice-error');
            assert.ok(await notice.isVisible());
            assert.equal(await notice.getAttribute('role'),'alert');
            assert.equal(await notice.getAttribute('data-fw-persistent'),'true');
            assert.equal(await notice.locator('.fw-notice-track').count(),0);
        } finally { await fallback.close(); }
        console.log(name+': notification durations, real settings saves, CSRF refusal, timing, combined pauses, persistent errors, overflow, reduced motion and safe text passed');
    } finally { await context.close(); }
};
