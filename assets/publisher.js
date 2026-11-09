/* Keep publication configuration and preview in sync without changing any ACL. */
(() => {
    const form = document.getElementById('fw-publisher');
    if (!form) return;
    const folder = form.querySelector('#fw-publish-folder');
    const code = document.getElementById('fw-shortcode');
    const copy = document.getElementById('fw-copy-shortcode');
    const feedback = document.getElementById('fw-copy-status');
    const demo = document.getElementById('fw-preview-denied');
    const frame = document.querySelector('.fw-publisher .filewarden-publication-frame');
    const status = document.getElementById('fw-preview-status');
    const update = () => {
        const uuid = folder.selectedOptions[0].dataset.uuid;
        const view = form.querySelector('[name="view"]:checked').value;
        const denied = form.querySelector('[name="denied"]:checked').value;
        code.value = `[filewarden folder="${uuid}" view="${view}" denied="${denied}"]`;
        feedback.textContent = '';
        const url = new URL(form.dataset.endpoint, document.baseURI);
        url.search = new URLSearchParams({ action: 'filewarden_folder', folder_id: uuid, view, denied, return: form.dataset.return });
        if (demo.checked) { url.searchParams.set('demo', '1'); url.searchParams.set('_wpnonce', form.dataset.nonce); }
        status.textContent = status.dataset.loading;
        frame.src = url.href;
    };
    form.querySelector('.fw-publisher-fallback').hidden = true;
    copy.hidden = false;
    form.addEventListener('change', update);
    form.addEventListener('submit', event => { event.preventDefault(); update(); });
    demo.addEventListener('change', update);
    frame.addEventListener('load', () => { status.textContent = status.dataset.ready; });
    copy.addEventListener('click', async () => {
        try { await navigator.clipboard.writeText(code.value); feedback.textContent = feedback.dataset.success; }
        catch (_) { code.focus(); code.select(); feedback.textContent = feedback.dataset.failure; }
    });
})();
