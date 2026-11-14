/** Audit acceptance against the disposable WordPress fixture only. */
const assert = require('node:assert/strict');
const fs = require('node:fs/promises');

module.exports = async ({ page, base, name }) => {
    const navigate = async link => {
        const href = await link.getAttribute('href');
        await Promise.all([page.waitForURL(href), link.click()]);
        await page.locator('.fw-terminal').waitFor();
    };
    await page.goto(base + '/wp-admin/admin.php?page=filewarden-audit&event=audit_export_fixture');
    assert.equal(await page.locator('.fw-log-line').count(), 50);
    assert.ok((await page.locator('.fw-terminal').textContent()).includes('fw_admin'));
    const simpleIds = await page.locator('.fw-log-line').evaluateAll(rows=>rows.map(row=>row.dataset.eventId));
    await navigate(page.getByRole('link', { name: 'Technical', exact: true }));
    assert.deepEqual(await page.locator('.fw-log-line').evaluateAll(rows=>rows.map(row=>row.dataset.eventId)), simpleIds);
    await page.locator('.fw-log-line summary').first().click();
    assert.ok((await page.locator('.fw-log-line pre').first().textContent()).includes('actor_login'));
    await page.getByRole('button', { name: 'Apply filters', exact: true }).click();
    assert.ok(page.url().includes('view=technical'));
    for (const format of ['csv', 'txt', 'jsonl']) {
        const downloadEvent = page.waitForEvent('download');
        await page.locator('.fw-export-' + format).click();
        const download = await downloadEvent;
        assert.match(download.suggestedFilename(), new RegExp('^filewarden-audit-\\d{4}-\\d{2}-\\d{2}\\.' + format + '$'));
        assert.equal(await download.failure(), null);
        const data = await fs.readFile(await download.path(), 'utf8');
        const lines = data.trim().split(/\r?\n/);
        assert.equal(lines.length, format === 'jsonl' ? 1005 : 1006);
        if (format === 'jsonl') {
            const records = lines.map(JSON.parse);
            assert.ok(records.every(row => row.actor_login === 'fw_admin' && row.actor_type === 'user' && row.event === 'audit_export_fixture'));
            assert.ok(records.every(row => row.schema_version === 2 && /^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}Z$/.test(row.created_at_UTC)));
            assert.equal(new Set(records.map(row => row.id)).size, 1005);
        }
        else assert.ok(data.includes('actor_login') && data.includes('fw_admin'));
        // Download events can arrive before Firefox finishes the UI callback.
        await page.waitForFunction(format => document.querySelector('.fw-export-status').textContent.includes('File received') && document.querySelector('.fw-export-' + format).getAttribute('aria-busy') === null, format);
        assert.ok((await page.locator('.fw-export-status').textContent()).includes('File received'));
        assert.equal(await page.locator('.fw-export-' + format).getAttribute('aria-busy'), null);
    }
    await navigate(page.getByRole('link', { name: 'Older events', exact: true }));
    assert.equal(await page.locator('.fw-log-line').count(), 50);
    const route = /\/wp-admin\/admin-post\.php\?.*action=filewarden_audit/;
    await page.route(route, handler => handler.fulfill({ status: 403, contentType: 'text/html', body: 'Denied' }));
    await page.locator('.fw-export-jsonl').click();
    await page.waitForFunction(() => document.querySelector('.fw-export-status').textContent.includes('Download failed'));
    assert.equal(await page.locator('.fw-export-jsonl').getAttribute('aria-busy'), null);
    await page.unroute(route);
    await navigate(page.getByRole('link', { name: 'Reset', exact: true }));
    assert.equal(await page.locator('#fw-event').inputValue(), '');
    assert.ok(page.url().includes('view=technical'));
    await page.setViewportSize({ width: 375, height: 812 });
    assert.ok(await page.evaluate(() => document.documentElement.scrollWidth <= innerWidth));
    await page.screenshot({ path: 'build/screenshots/audit-mobile-' + name + '.png', fullPage: true });
    await page.setViewportSize({ width: 1280, height: 900 });
};
