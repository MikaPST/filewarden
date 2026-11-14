/** Approved Documents welcome and independently operable guided hierarchy. */
const assert = require('node:assert/strict');
module.exports = async ({page,base,fixture,name}) => {
    await page.setViewportSize({width:1280,height:900});
    await page.goto(base+'/wp-admin/admin.php?page=filewarden');
    assert.equal(await page.locator('.fw-folder-cards').count(),0,'A second folder tree remains in the central pane');
    assert.equal(await page.locator('.fw-content .fw-folder-heading').count(),0,'The overview still claims a current folder');
    assert.equal(await page.locator('.fw-folder-welcome h2').textContent(),'Open a folder');
    assert.equal(await page.locator('.fw-folder-welcome p').textContent(),'Select a folder in the sidebar to view its documents or create a new folder.');
    assert.equal(await page.locator('.fw-welcome-icon').evaluate(el=>getComputedStyle(el).width),'80px');
    assert.match(await page.locator('.fw-welcome-icon').evaluate(el=>getComputedStyle(el).backgroundImage),/folder-welcome\.png/);
    assert.equal(await page.locator('.fw-tree-create').evaluate(el=>getComputedStyle(el).gap),'10px');
    assert.equal(await page.locator('.fw-tree-folder').filter({hasText:'Public fixtures'}).evaluate(el=>el.getBoundingClientRect().height),40,'The compact single-line folder row did not retain its approved minimum target');
    // Select by exact URL parameter to avoid confusing a parent ID with an unrelated longer ID.
    const target = await page.locator('.fw-tree-folder').evaluateAll((nodes,id)=>nodes.find(n=>new URL(n.href).searchParams.get('folder_id')===String(id))?.href,fixture.public_folder_id);
    assert.ok(target,'Authorized public folder is missing from the tree');
    await page.goto(target);
    const selected = page.locator('.fw-tree-folder[aria-current=location]');
    assert.equal(await selected.evaluate(el=>getComputedStyle(el).backgroundColor),'rgb(28, 41, 66)');
    assert.equal(await selected.evaluate(el=>getComputedStyle(el).color),'rgb(255, 255, 255)');
    const branch = page.locator('.fw-tree-node').filter({has:page.locator(':scope > details > ul > li')}).first();
    if(await branch.count()) {
        const toggle = branch.locator(':scope > details > summary');
        const href = page.url();
        await toggle.click();
        assert.equal(page.url(),href,'Expanding/collapsing a branch navigated to a folder');
        assert.equal(await branch.locator(':scope > details').getAttribute('open'),null);
        await toggle.focus(); await toggle.press('Enter');
        assert.notEqual(await branch.locator(':scope > details').getAttribute('open'),null);
    }
    await page.goto(base+'/wp-admin/admin.php?page=filewarden');
    await page.screenshot({path:`build/screenshots/welcome-guided-${name}.png`,fullPage:true});
    await page.setViewportSize({width:375,height:812});
    assert.ok(await page.evaluate(()=>document.documentElement.scrollWidth<=innerWidth));
    await page.screenshot({path:`build/screenshots/welcome-mobile-${name}.png`,fullPage:true});
    await page.setViewportSize({width:1280,height:900});
    console.log(name+': essential Documents welcome and guided folder hierarchy passed');
};
