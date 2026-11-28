/** Compact viewer acceptance against disposable loopback WordPress only. */
const assert = require('node:assert/strict');
const fs = require('node:fs');
const { chromium, firefox } = require('playwright');
assert.equal(process.env.FILEWARDEN_TESTING, '1');
const base = process.env.FILEWARDEN_URL;
assert.equal(new URL(base).hostname, '127.0.0.1');
const fixture = JSON.parse(fs.readFileSync(process.env.FILEWARDEN_FIXTURES, 'utf8'));
const output = process.env.FILEWARDEN_V19_SCREENSHOTS;
assert.ok(output.includes('.tools'));
(async () => {
    for (const [name, engine] of [['chromium', chromium], ['firefox', firefox]]) {
        const browser = await engine.launch();
        try {
            const context = await browser.newContext({ viewport: { width: 1280, height: 900 } });
            await context.request.get(base + '/wp-login.php');
            await context.request.post(base + '/wp-login.php', { form: { log: 'fw_admin', pwd: 'disposable-test-password', testcookie: '1' } });
            const page = await context.newPage();
            await page.goto(base + '/wp-admin/admin.php?page=filewarden&folder_id=' + fixture.public_folder_id + '&search=' + encodeURIComponent(process.env.FILEWARDEN_VIEWER_SEARCH || 'browser-'));
            const url = await page.locator('.fw-row-actions a[data-filewarden-preview]').first().getAttribute('href');
            await page.goto(url);
            await page.locator('#fw-status.fw-status-complete').waitFor();
            await page.locator('#fw-zoom').selectOption('page');
            await page.waitForFunction(() => document.getElementById('fw-canvas').getBoundingClientRect().height <= document.getElementById('fw-stage').clientHeight);
            await page.locator('#fw-zoom').selectOption('1');
            await page.locator('#fw-zoom-in').click();
            assert.equal(await page.locator('#fw-zoom').inputValue(), '1.25');
            await page.locator('#fw-zoom-out').click();
            assert.equal(await page.locator('#fw-zoom').inputValue(), '1');
            await page.locator('#fw-zoom').selectOption('2');
            await page.waitForFunction(() => document.getElementById('fw-zoom-in').disabled);
            await page.locator('#fw-zoom').selectOption('page');
            assert.ok((await page.locator('header').boundingBox()).height <= 76);
            await page.screenshot({ path: output + '/' + name + '-viewer-desktop.png' });
            await page.setViewportSize({ width: 375, height: 812 });
            await page.locator('#fw-zoom').selectOption('fit');
            await page.locator('#fw-zoom').selectOption('page');
            // PDF.js rerenders asynchronously after resize/zoom; inspect the settled layout.
            await page.waitForFunction(() => document.documentElement.scrollWidth <= innerWidth && document.getElementById('fw-canvas').getBoundingClientRect().width <= innerWidth);
            assert.ok(await page.evaluate(() => document.documentElement.scrollWidth <= innerWidth));
            assert.ok((await page.locator('header').boundingBox()).height <= 150);
            await page.screenshot({ path: output + '/' + name + '-viewer-mobile.png' });
            console.log('PASS V19 viewer ' + name + ': whole-page fit, bounded zoom buttons, compact desktop/mobile toolbar');
        } finally { await browser.close(); }
    }
})().catch(error => { console.error(error); process.exit(1); });
