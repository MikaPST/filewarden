/** Exercise real notification DOM, focus and timing without slowing the suite with wall-clock sleeps. */
const assert = require('node:assert/strict');
module.exports = async ({ context, base }) => {
    const page = await context.newPage();
    try {
        await page.clock.install();
        await page.goto(base + '/wp-admin/admin.php?page=filewarden&result=success');
        const success = page.locator('.notice-success[data-fw-notice]').first();
        await success.waitFor();
        await page.mouse.move(0,0);
        await page.clock.fastForward(10000);
        assert.ok(await success.isVisible(), 'Success notification disappeared before the 10-second minimum');
        assert.equal(await success.locator('.fw-notice-progress').count(), 1);
        await page.clock.fastForward(3000);
        assert.equal(await page.locator('.notice-success').count(), 0);
        await page.goto(base + '/wp-admin/admin.php?page=filewarden&result=denied');
        const error = page.locator('.notice-error[data-fw-notice]').first();
        await error.waitFor();
        await page.clock.fastForward(60000);
        assert.ok(await error.isVisible(), 'Critical error was automatically hidden');
        assert.equal(await error.getAttribute('role'), 'alert');
        await error.getByRole('button', { name: 'Dismiss notification', exact: true }).focus();
        await error.getByRole('button', { name: 'Dismiss notification', exact: true }).press('Enter');
        assert.equal(await page.locator('.notice-error').count(), 0);
        assert.equal(await page.locator('.fw-workspace h1').evaluate(el=>el === document.activeElement), true);
        await page.goto(base + '/wp-admin/admin.php?page=filewarden&tab=trash');
        await page.locator('.notice-info[data-fw-notice]').waitFor();
        assert.equal(await page.locator('.notice-info .dashicons').count(), 1);
        await page.goto(base + '/wp-admin/admin.php?page=filewarden-settings');
        await page.locator('.fw-settings .notice-warning[data-fw-notice]').waitFor();
        assert.equal(await page.locator('.fw-settings .notice-warning .fw-notice-close').count(), 1);
    } finally { await page.close(); }
};
