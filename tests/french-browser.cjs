/** French acceptance on the installed ZIP, after WordPress sets the synthetic admin locale. */
const assert = require('node:assert/strict');
const { chromium, firefox } = require('playwright');
assert.equal(process.env.FILEWARDEN_TESTING, '1');
const base = process.env.FILEWARDEN_URL || 'http://127.0.0.1:8080';
(async () => {
    for (const [name, engine] of [['chromium', chromium], ['firefox', firefox]]) {
        const browser = await engine.launch();
        try {
            const context = await browser.newContext({ viewport: { width: 1280, height: 900 } });
            await context.request.get(base + '/wp-login.php');
            await context.request.post(base + '/wp-login.php', { form: { log: 'fw_admin', pwd: 'disposable-test-password', testcookie: '1' } });
            const page = await context.newPage();
            page.on('dialog', dialog => dialog.accept());
            await page.goto(base + '/wp-admin/admin.php?page=filewarden');
            assert.match(await page.locator('.fw-tree').textContent(), /Documents à classer/);
            assert.match(await page.locator('.fw-recovery-count').textContent(), /^\d+$/);
            assert.equal(await page.locator('.fw-folder-welcome h2').textContent(),'Ouvrez un dossier');
            assert.equal(await page.locator('.fw-folder-welcome p').textContent(),'Sélectionnez un dossier dans le menu latéral pour consulter ses documents ou créer un nouveau dossier.');
            await page.goto(base + '/wp-admin/admin.php?page=filewarden&tab=add');
            assert.match(await page.locator('.fw-dropzone').textContent(), /Glissez vos documents/);
            await page.locator('#fw-file').setInputFiles({ name: 'document-fr.txt', mimeType: 'text/plain', buffer: Buffer.from('Texte français de réception\n') });
            assert.match(await page.locator('.fw-upload-item').textContent(), /Prêt pour la validation du serveur/);
            await page.screenshot({ path: 'build/screenshots/upload-fr-' + name + '.png', fullPage: true });
            await page.goto(base + '/wp-admin/admin.php?page=filewarden&tab=trash');
            assert.equal(await page.locator('.fw-tree').count(), 0);
            assert.match(await page.locator('.fw-global-trash').textContent(), /Emplacement d’origine|Emplacement d'origine/);
            await page.getByRole('link', { name: 'Vider la corbeille', exact: true }).waitFor();
            await page.goto(base + '/wp-admin/admin.php?page=filewarden-settings');
            assert.match(await page.locator('.fw-settings-nav').textContent(), /Stockage et sécurité/);
            assert.match(await page.locator('.fw-settings-nav').textContent(), /Documents et limites/);
            assert.equal(await page.locator('.fw-settings-nav [role=tab]').count(), 7);
            assert.equal(await page.locator('[name=notification_seconds]').inputValue(),'10');
            assert.match(await page.locator('#fw-notification-help').textContent(),/Définissez la durée pendant laquelle les notifications restent visibles/);
            await page.evaluate(()=>window.FileWardenNotices.show({type:'success',message:'Exemple de réception',persistent:false}));
            assert.equal(await page.locator('.fw-notice-success .fw-notice-title').textContent(),'Opération terminée');
            await page.setViewportSize({ width: 375, height: 812 });
            assert.ok(await page.evaluate(() => document.documentElement.scrollWidth <= innerWidth));
            await page.screenshot({ path: 'build/screenshots/settings-fr-mobile-' + name + '.png', fullPage: true });
            await page.setViewportSize({ width: 1280, height: 900 });
            await page.goto(base + '/wp-admin/admin.php?page=filewarden-audit&event=document_purged');
            assert.match(await page.locator('.fw-terminal').textContent(), /Document définitivement supprimé/);
            assert.match(await page.locator('.fw-terminal').textContent(), /fw_admin/);
            assert.ok(await page.locator('.fw-log-resource').first().isVisible());
            assert.match(await page.locator('.fw-log-simple details summary').first().textContent(), /Afficher les détails techniques/);
            await page.screenshot({ path: 'build/screenshots/audit-fr-' + name + '.png', fullPage: true });
            await page.goto(base + '/wp-admin/admin.php?page=filewarden-audit&event=audit_system_fixture');
            assert.match(await page.locator('.fw-terminal').textContent(), /Action système/);
            await page.goto(base + '/wp-admin/admin.php?page=filewarden-audit&event=audit_legacy_unknown');
            assert.match(await page.locator('.fw-terminal').textContent(), /Acteur historique non renseigné/);
            await require('./v15-french-browser.cjs')({context,base,name});
            console.log(name + ': French installed UI, counter, queue, trash, settings and audit passed');
        } finally { await browser.close(); }
    }
})().catch(error => { console.error(error); process.exitCode = 1; });
