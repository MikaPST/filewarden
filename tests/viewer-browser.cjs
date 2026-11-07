/** Real browser checks against disposable WordPress only. */
const assert = require('node:assert/strict');
const fs = require('node:fs');
const { chromium, firefox } = require('playwright');
assert.equal(process.env.FILEWARDEN_TESTING, '1');
const base = process.env.FILEWARDEN_URL || 'http://127.0.0.1:8080';

function pdfFixture() {
    const objects = [
        '<< /Type /Catalog /Pages 2 0 R >>',
        '<< /Type /Pages /Count 2 /Kids [3 0 R 4 0 R] >>',
        '<< /Type /Page /Parent 2 0 R /MediaBox [0 0 400 500] /Resources << /Font << /F1 5 0 R >> >> /Contents 6 0 R >>',
        '<< /Type /Page /Parent 2 0 R /MediaBox [0 0 400 500] /Resources << /Font << /F1 5 0 R >> >> /Contents 7 0 R >>',
        '<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica >>',
        ...[1, 2].map(n => {
            const data = `BT /F1 24 Tf 40 400 Td (FileWarden test page ${n}) Tj ET\n`;
            return `<< /Length ${Buffer.byteLength(data)} >>\nstream\n${data}endstream`;
        })
    ];
    let bytes = '%PDF-1.4\n';
    const offsets = [0];
    objects.forEach((object, index) => { offsets.push(Buffer.byteLength(bytes)); bytes += `${index + 1} 0 obj\n${object}\nendobj\n`; });
    const xref = Buffer.byteLength(bytes);
    bytes += `xref\n0 ${objects.length + 1}\n0000000000 65535 f \n`;
    offsets.slice(1).forEach(offset => { bytes += `${String(offset).padStart(10, '0')} 00000 n \n`; });
    bytes += `trailer\n<< /Size ${objects.length + 1} /Root 1 0 R >>\nstartxref\n${xref}\n%%EOF\n`;
    return Buffer.from(bytes);
}

(async () => {
    for (const [name, engine] of [['chromium', chromium], ['firefox', firefox]]) {
        const browser = await engine.launch();
        try {
            const context = await browser.newContext({ viewport: { width: 1280, height: 900 } });
            const page = await context.newPage();
            // Authenticate the disposable context without waiting on dashboard third-party resources.
            await context.request.get(base + '/wp-login.php');
            const login = await context.request.post(base + '/wp-login.php', {
                form: { log: 'fw_admin', pwd: 'disposable-test-password', testcookie: '1' }
            });
            assert.ok(login.url().includes('/wp-admin/'), 'Fixture login failed');
            await page.goto(base + '/wp-admin/admin.php?page=filewarden');
            const filename = `browser-${name}.pdf`;
            await page.locator('#fw-file').setInputFiles({ name: filename, mimeType: 'application/pdf', buffer: pdfFixture() });
            await page.locator('[name="filewarden_save"]').click();
            await page.waitForURL(/result=success/, { timeout: 30000 });
            const link = page.getByRole('link', { name: filename, exact: true });
            const detailURL = await link.getAttribute('href');
            await link.click();
            const modal = page.locator('dialog.filewarden-preview-dialog');
            await modal.waitFor({ state: 'visible' });
            const viewer = page.frameLocator('dialog iframe');
            await viewer.locator('#fw-status').filter({ hasText: 'Page 1 / 2' }).waitFor({ timeout: 30000 });
            assert.equal(await viewer.locator('#fw-canvas').isVisible(), true);
            // Check that the page contains rendered dark pixels, not just a blank canvas.
            assert.ok(await viewer.locator('#fw-canvas').evaluate(canvas => {
                const pixels = canvas.getContext('2d').getImageData(0, 0, canvas.width, canvas.height).data;
                for (let i = 0; i < pixels.length; i += 4) if (pixels[i + 3] && pixels[i] < 100) return true;
                return false;
            }));
            await viewer.locator('#fw-next').click();
            await viewer.locator('#fw-status').filter({ hasText: 'Page 2 / 2' }).waitFor();
            await viewer.locator('#fw-zoom').selectOption('1.5');
            await viewer.locator('#fw-status').filter({ hasText: 'Page 2 / 2' }).waitFor();
            const popupPromise = page.waitForEvent('popup');
            await viewer.getByRole('link', { name: 'Open in new tab' }).click();
            const popup = await popupPromise;
            await popup.locator('#fw-status').filter({ hasText: 'Page 1 / 2' }).waitFor();
            await popup.close();
            await viewer.locator('#fw-page').press('Escape');
            await modal.waitFor({ state: 'hidden' });
            assert.equal(await link.evaluate(element => element === document.activeElement), true);
            const anonymous = await browser.newContext();
            const forbidden = await anonymous.request.get(detailURL + '&preview=1');
            assert.equal(forbidden.status(), 404);
            await anonymous.close();
            // Test a real image through the same direct-upload workflow and viewer.
            const fixture = JSON.parse(fs.readFileSync(process.env.FILEWARDEN_FIXTURES, 'utf8'));
            await page.goto(base + '/wp-admin/admin.php?page=filewarden&folder_id=' + fixture.folder_id);
            await page.getByRole('link', { name: 'filewarden-fixture.png', exact: true }).click();
            await viewer.locator('#fw-image').waitFor({ state: 'visible' });
            assert.ok(await viewer.locator('#fw-image').evaluate(img => img.naturalWidth > 0));
            await viewer.locator('#fw-zoom').selectOption('2');
            await page.getByRole('button', { name: 'Close preview' }).click();
            await modal.waitFor({ state: 'hidden' });
            console.log(`${name}: real upload, rendered PDF, page navigation, zoom, new tab, modal focus, image and denied access passed`);
        } finally { await browser.close(); }
    }
})().catch(error => { console.error(error); process.exitCode = 1; });
