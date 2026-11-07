const { test } = require('node:test');
const assert = require('node:assert/strict');
const fs = require('node:fs');
const path = require('node:path');
const vm = require('node:vm');

const script = fs.readFileSync(process.env.FW_ADMIN_SCRIPT || path.join(__dirname, '../assets/admin.js'), 'utf8');

// Small DOM/transport doubles exercise the actual shipped script without dependencies.
function setup(initialSource = 'upload') {
    class Element {
        constructor() { this.events = {}; this.disabled = false; this.hidden = false; this.dataset = {}; }
        addEventListener(name, fn) { (this.events[name] ||= []).push(fn); }
        emit(name, event = {}) { for (const fn of this.events[name] || []) fn(event); }
        setAttribute(name, value) { this[name] = value; }
        removeAttribute(name) { delete this[name]; }
    }
    const endpoint = 'https://example.test/wp-admin/admin-post.php';
    const file = new Element();
    const media = Object.assign(new Element(), { value: '0' });
    const panels = ['upload', 'import'].map(value => Object.assign(new Element(), { dataset: { filewardenSource: value } }));
    const radios = ['upload', 'import'].map(value => Object.assign(new Element(), { value, checked: value === initialSource }));
    const source = () => radios.find(radio => radio.checked);
    const controls = [file, media, ...radios, new Element()];
    const form = new Element();
    // This is what a real HTML form exposes when it contains <input name="action">.
    form.action = { value: 'filewarden_admin', toString: () => '[object HTMLInputElement]' };
    form.getAttribute = name => name === 'action' ? endpoint : null;
    form.querySelector = selector => ({
        'input[name="source"]:checked': source(),
        'input[type="file"]': file,
        '#fw-media-id': media
    })[selector] || null;
    form.querySelectorAll = selector => ({
        '[data-filewarden-source]': panels,
        'input[name="source"]': radios,
        'input, select, button': controls
    })[selector] || [];
    form.append = (progress, status) => { form.progress = progress; form.status = status; };
    const document = new Element();
    document.baseURI = 'https://example.test/wp-admin/admin.php?page=filewarden';
    document.querySelectorAll = () => [form];
    document.createElement = () => new Element();
    const requests = [];
    const redirects = [];
    class XHR extends Element {
        constructor() { super(); this.upload = new Element(); requests.push(this); }
        open(method, url) { this.method = method; this.url = url; }
        send(data) { this.data = data; }
    }
    class FormData {
        constructor() {
            assert.ok(controls.every(control => !control.disabled), 'Capture values before locking controls');
            this.source = source().value;
            this.fileIncluded = !panels[0].disabled;
            this.mediaIncluded = !panels[1].disabled;
        }
    }
    vm.runInNewContext(script, {
        document, URL, XMLHttpRequest: XHR, FormData,
        location: { origin: 'https://example.test', assign: url => redirects.push(url) },
        filewardenUpload: { sending: 'sending', verifying: 'verifying', failed: 'unconfirmed', network: 'network', media: 'choose media' }
    });
    document.emit('DOMContentLoaded');
    return { form, panels, file, media, controls, requests, redirects, endpoint,
        submit: () => form.emit('submit', { preventDefault() {} }),
        select: value => {
            radios.forEach(radio => { radio.checked = radio.value === value; });
            source().emit('change');
        }
    };
}

test('routing uses the form URL despite the named action input', () => {
    const ui = setup();
    ui.submit();
    assert.equal(ui.requests[0].url, ui.endpoint);
    assert.equal(ui.requests[0].method, 'POST');
    assert.equal(ui.requests[0].data.source, 'upload');
    assert.equal(ui.requests[0].data.fileIncluded, true);
    assert.equal(ui.requests[0].data.mediaIncluded, false);
    ui.submit();
    assert.equal(ui.requests.length, 1, 'A second click must not send a duplicate');
});

test('both sources use one destination and exclude the inactive file input', () => {
    const ui = setup();
    assert.equal(ui.file.required, true);
    ui.select('import');
    assert.equal(ui.file.required, false);
    assert.equal(ui.panels[0].hidden, true);
    assert.equal(ui.panels[0].disabled, true);
    ui.submit();
    assert.equal(ui.requests.length, 0);
    assert.equal(ui.form.status.textContent, 'choose media');
    ui.media.value = '123';
    ui.submit();
    assert.equal(ui.requests[0].data.fileIncluded, false);
    assert.equal(ui.requests[0].data.mediaIncluded, true);
});

test('a preselected media item opens the Media Library source', () => {
    const ui = setup('import');
    assert.equal(ui.file.required, false);
    assert.equal(ui.panels[1].hidden, false);
    ui.select('upload');
    assert.equal(ui.file.required, true);
    assert.equal(ui.panels[0].disabled, false);
});

test('transfer completion is not treated as server confirmation', () => {
    const ui = setup();
    ui.submit();
    const xhr = ui.requests[0];
    xhr.upload.emit('load');
    assert.equal(ui.form.status.textContent, 'verifying');
    assert.deepEqual(ui.redirects, []);
    xhr.status = 200;
    xhr.responseURL = 'https://example.test/wp-admin/admin.php?page=filewarden&result=success&folder_id=3&created=test';
    xhr.emit('load');
    assert.deepEqual(ui.redirects, [xhr.responseURL]);
});

for (const failure of ['404', 'network', 'login', 'external']) {
    test(`${failure} does not claim success and allows retry`, () => {
        const ui = setup();
        ui.submit();
        const xhr = ui.requests[0];
        xhr.status = failure === '404' ? 404 : 200;
        xhr.responseURL = failure === 'external'
            ? 'https://other.test/?page=filewarden&result=success'
            : 'https://example.test/wp-login.php';
        xhr.emit(failure === 'network' ? 'error' : 'load');
        assert.deepEqual(ui.redirects, []);
        assert.ok(ui.controls.every(control => !control.disabled));
        assert.equal(ui.form.progress.hidden, true);
        ui.submit();
        assert.equal(ui.requests.length, 2);
    });
}
