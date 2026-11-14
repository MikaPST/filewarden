/** End-to-end acceptance of the original ten evolutions on the installed ZIP. */
const assert = require('node:assert/strict');

module.exports = async ({ page, context, base, fixture, name }) => {
    const documents = base + '/wp-admin/admin.php?page=filewarden';
    const filename = 'browser-' + name + '.pdf';
    const folderURL = documents + '&folder_id=' + fixture.public_folder_id;
    const publicFolder = folderURL + '&search=' + encodeURIComponent(filename);
    const go = async link => {
        const href = await link.getAttribute('href');
        await Promise.all([page.waitForURL(href), link.click()]);
    };
    const action = async label => {
        const url = new URL(page.url());
        url.searchParams.set('search', filename);
        url.searchParams.delete('panel');
        await page.goto(url.href);
        const row = page.locator('tbody tr').filter({ hasText: filename });
        await row.locator('.fw-context summary').click();
        await go(row.getByRole('link', { name: label, exact: true }));
    };
    const count = async () => Number((await page.locator('.fw-recovery-count').textContent()).match(/\d+/)[0]);
    await page.setViewportSize({ width: 1280, height: 900 });
    await page.goto(publicFolder);
    const originalCount = await count();
    const originalURL = await page.getByRole('link', { name: filename, exact: true }).getAttribute('href');
    // 01/02: classify via the dedicated shortcut, then reclassify with preserved identity.
    await action('Move');
    assert.ok((await page.locator('#fw-destination').textContent()).includes('Documents to classify'));
    await page.getByRole('button', { name: 'Classify later', exact: true }).click();
    await page.getByRole('heading', { name: 'Remove this document from publication?', exact: true }).waitFor();
    await page.getByRole('button', { name: 'Confirm classification', exact: true }).click();
    await page.waitForURL(/result=success/);
    assert.equal(await count(), originalCount + 1);
    assert.equal((await context.request.get(originalURL)).status(), 404);
    await action('Move');
    await page.locator('#fw-destination').selectOption(String(fixture.public_folder_id));
    await page.getByRole('button', { name: 'Review destination', exact: true }).click();
    await page.getByRole('button', { name: 'Confirm move', exact: true }).click();
    await page.waitForURL(/result=success/);
    assert.equal(await count(), originalCount);
    await page.goto(publicFolder);
    assert.equal(await page.getByRole('link', { name: filename, exact: true }).getAttribute('href'), originalURL);
    // 03: action hierarchy is visible in the real browser.
    assert.equal(await page.locator('.fw-folder-heading a').filter({ hasText: 'Delete folder' }).evaluate(el => getComputedStyle(el).backgroundColor), 'rgb(180, 35, 24)');
    const publish = page.getByRole('link', { name: 'Publish this folder', exact: true });
    assert.equal(await publish.evaluate(el => getComputedStyle(el).backgroundColor), 'rgb(28, 41, 66)');
    assert.equal(await publish.locator('.fw-action-icon,.dashicons').count(), 0);
    // 04: the real queue uploads and validates each document and preserves its destination.
    await page.goto(publicFolder + '&tab=add');
    assert.ok(await page.locator('.fw-dropzone').isVisible());
    await page.locator('#fw-file').setInputFiles([
        { name: 'queued-' + name + '.txt', mimeType: 'text/plain', buffer: Buffer.from('A validated text document\n') },
        { name: 'queued-' + name + '.zip', mimeType: 'application/zip', buffer: Buffer.from(fixture.safe_zip_base64, 'base64') }
    ]);
    assert.equal(await page.locator('.fw-upload-item').count(), 2);
    await page.locator('[name=filewarden_save]').click();
    await page.waitForFunction(() => [...document.querySelectorAll('.fw-upload-item [role=status]')].every(el => el.textContent === 'Saved and verified'));
    await page.waitForFunction(() => document.querySelector('form[data-filewarden-upload]').getAttribute('aria-busy') === null);
    await page.goto(folderURL + '&search=' + encodeURIComponent('queued-' + name + '.zip'));
    assert.equal(await page.getByRole('link', { name: 'queued-' + name + '.zip', exact: true }).count(), 1);
    await page.goto(publicFolder + '&tab=add');
    await page.locator('#fw-file').setInputFiles({ name: 'unsafe-' + name + '.zip', mimeType: 'application/zip', buffer: Buffer.from(fixture.unsafe_zip_base64, 'base64') });
    await page.locator('[name=filewarden_save]').click();
    await page.waitForURL(/result=unsafe_file/);
    assert.ok((await page.locator('.notice-error').textContent()).includes('Scripts, macros'));
    // Drop two files into the real drop target; a rejected member does not prevent a valid upload.
    await page.locator('.fw-dropzone').evaluate((zone, files) => {
        const transfer = new DataTransfer();
        for (const file of files) transfer.items.add(new File([Uint8Array.from(atob(file.bytes), c => c.charCodeAt(0))], file.name));
        zone.dispatchEvent(new DragEvent('drop', { bubbles: true, cancelable: true, dataTransfer: transfer }));
    }, [
        { name: 'dropped-' + name + '.txt', bytes: Buffer.from('Dropped text fixture\n').toString('base64') },
        { name: 'dropped-unsafe-' + name + '.zip', bytes: fixture.unsafe_zip_base64 }
    ]);
    assert.equal(await page.locator('.fw-upload-item').count(), 2);
    await page.locator('[name=filewarden_save]').click();
    await page.waitForFunction(() => document.querySelector('form[data-filewarden-upload]').getAttribute('aria-busy') === null);
    assert.match(await page.locator('.fw-upload-item').nth(0).textContent(), /Saved and verified/);
    assert.match(await page.locator('.fw-upload-item').nth(1).textContent(), /Scripts, macros/);
    await page.screenshot({ path: 'build/screenshots/upload-queue-' + name + '.png', fullPage: true });
    // 05/06: create using the selected parent and inherited policy, then confirm leaf deletion.
    await page.goto(publicFolder + '&tab=folder');
    await page.locator('#fw-name').fill('created-ui-' + name);
    await page.locator('#folder-parent').selectOption(String(fixture.public_folder_id));
    assert.equal(await page.locator('#folder-policy').inputValue(), 'inherit');
    await page.getByRole('button', { name: 'Create folder', exact: true }).click();
    await page.waitForURL(/result=success/);
    await go(page.locator('.fw-tree').getByRole('link', { name: 'created-ui-' + name, exact: true }));
    await go(page.getByRole('link', { name: 'Delete folder', exact: true }));
    await page.getByRole('button', { name: 'Confirm folder deletion', exact: true }).click();
    await page.waitForURL(/result=success/);
    assert.equal(await page.locator('.fw-tree').getByRole('link', { name: 'created-ui-' + name, exact: true }).count(), 0);
    // 07: global trash ignores folder context and permanent deletion enforces confirmation/nonce.
    await page.goto(publicFolder);
    await action('Move to trash');
    await page.getByRole('button', { name: 'Move to trash', exact: true }).click();
    await page.waitForURL(/result=success/);
    await page.goto(documents + '&tab=trash&folder_id=999999999');
    assert.equal(await page.locator('.fw-tree').count(), 0);
    assert.ok((await page.locator('thead').textContent()).includes('Original location'));
    const trashRow = page.locator('tbody tr').filter({ hasText: filename });
    assert.ok(await trashRow.isVisible());
    await go(trashRow.getByRole('link', { name: 'Delete permanently', exact: true }));
    const purgeForm = page.locator('.fw-trash-confirm form');
    const formData = await purgeForm.evaluate(form => Object.fromEntries(new FormData(form)));
    const endpoint = await purgeForm.getAttribute('action');
    assert.equal((await context.request.post(endpoint, { form: { ...formData, confirm: '1', _wpnonce: 'invalid' } })).status(), 403);
    const noConfirmation = await context.request.post(endpoint, { form: formData });
    assert.ok(noConfirmation.url().includes('result=invalid'), 'Permanent disposal accepted absent confirmation');
    assert.ok(await page.locator('.fw-trash-confirm').getByRole('button', { name: 'Confirm permanent deletion' }).isVisible());
    await purgeForm.locator('[name=confirm]').check();
    await purgeForm.getByRole('button', { name: 'Confirm permanent deletion' }).click();
    await page.waitForURL(/result=success/);
    assert.equal(await page.locator('tbody tr').filter({ hasText: filename }).count(), 0);
    assert.equal((await context.request.get(originalURL)).status(), 404);
    await page.getByRole('link', { name: 'Empty trash', exact: true }).click();
    await page.getByRole('heading', { name: 'Permanently delete these documents?', exact: true }).waitFor();
    assert.equal(await page.locator('.fw-trash-confirm [name=confirm]').isChecked(), false);
    await page.goto(documents + '&tab=trash'); // cancel; no irreversible bulk request is sent.
    if (name === 'firefox') {
        await page.goto(folderURL + '&search=' + encodeURIComponent('queued-' + name + '.zip'));
        const queued = page.locator('tbody tr').filter({ hasText: 'queued-' + name + '.zip' });
        await queued.locator('.fw-context summary').click();
        await go(queued.getByRole('link', { name: 'Move to trash', exact: true }));
        await page.getByRole('button', { name: 'Move to trash', exact: true }).click();
        await page.waitForURL(/result=success/);
        await page.goto(documents + '&tab=trash&trash_action=empty');
        await page.locator('.fw-trash-confirm [name=confirm]').check();
        await page.locator('.fw-trash-confirm').getByRole('button', { name: 'Confirm permanent deletion' }).click();
        await page.waitForURL(/result=success/);
        assert.equal(await page.getByRole('link', { name: 'Restore', exact: true }).count(), 0);
    }
    await page.setViewportSize({ width: 375, height: 812 });
    await page.screenshot({ path: 'build/screenshots/global-trash-mobile-' + name + '.png', fullPage: true });
    await page.setViewportSize({ width: 1280, height: 900 });
    // 08: real clipboard success and deliberately refused clipboard access.
    await page.goto(base + '/wp-admin/admin.php?page=filewarden-publication&folder_id=' + fixture.public_folder_id);
    await page.locator('[data-preview-mode=denied]').click();
    assert.equal(await page.locator('[data-preview-mode=denied]').getAttribute('aria-selected'), 'true');
    await page.frameLocator('.filewarden-publication-frame').locator('.fw-denied-centered').waitFor();
    await page.locator('[data-preview-mode=authorized]').click();
    await page.frameLocator('.filewarden-publication-frame').locator('.documents').waitFor();
    assert.ok(await page.locator('.filewarden-publication-frame').evaluate(el => el.getBoundingClientRect().height <= 680));
    await page.getByRole('button', { name: 'Copy', exact: true }).click();
    await page.locator('#fw-copy-status').filter({ hasText: 'Shortcode copied.' }).waitFor();
    await page.evaluate(() => Object.defineProperty(navigator, 'clipboard', { configurable: true, value: { writeText: () => Promise.reject(new DOMException('Test denial', 'NotAllowedError')) } }));
    await page.getByRole('button', { name: 'Copy', exact: true }).click();
    await page.locator('#fw-copy-status').filter({ hasText: 'Copy failed.' }).waitFor();
    // 09: all categories have real content and changing one value preserves the others.
    await page.goto(base + '/wp-admin/admin.php?page=filewarden-settings');
    assert.equal(await page.locator('.fw-settings-nav [role=tab]').count(), 5);
    assert.equal(await page.locator('.fw-health').getAttribute('data-storage-status'), 'verified');
    const trashDays = await page.locator('[name=trash_days]').inputValue();
    const auditDays = await page.locator('[name=audit_days]').inputValue();
    await page.locator('[data-settings-section=documents]').click();
    await page.locator('[name=upload_mb]').fill('26');
    await page.getByRole('button', { name: 'Save settings', exact: true }).click();
    await page.waitForURL(/result=success/);
    assert.equal(await page.locator('[name=upload_mb]').inputValue(), '26');
    assert.equal(await page.locator('[name=trash_days]').inputValue(), trashDays);
    assert.equal(await page.locator('[name=audit_days]').inputValue(), auditDays);
    await page.locator('[data-settings-section=storage]').focus();
    await page.locator('[data-settings-section=storage]').press('ArrowDown');
    assert.equal(await page.locator('[data-settings-section=documents]').getAttribute('aria-selected'), 'true');
    await page.setViewportSize({ width: 375, height: 812 });
    assert.ok(await page.evaluate(() => document.documentElement.scrollWidth <= innerWidth));
    await page.screenshot({ path: 'build/screenshots/settings-categories-mobile-' + name + '.png', fullPage: true });
    // 10 is also exercised by audit-browser.cjs, with the same installed ZIP and session.
    console.log(name + ': original evolutions 01–09 passed; audit exports covered by audit-browser');
};
