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
    const fitCode = () => {
        code.style.height = 'auto';
        code.style.height = (code.scrollHeight + 2) + 'px';
    };
    window.addEventListener('resize', fitCode);
    requestAnimationFrame(fitCode);
    let pending;
    let requested;
    const failed = () => { clearTimeout(pending); status.textContent = status.dataset.failure; };
    const update = () => {
        const uuid = folder.selectedOptions[0].dataset.uuid;
        const view = form.querySelector('[name="view"]:checked').value;
        const denied = form.querySelector('[name="denied"]:checked').value;
        code.value = `[filewarden folder="${uuid}" view="${view}" denied="${denied}"]`;
        fitCode();
        feedback.textContent = '';
        const url = new URL(form.dataset.endpoint, document.baseURI);
        url.search = new URLSearchParams({ action: 'filewarden_folder', folder_id: uuid, view, denied, return: form.dataset.return });
        if (demo.checked) { url.searchParams.set('demo', '1'); url.searchParams.set('_wpnonce', form.dataset.nonce); }
        requested = url.href;
        status.textContent = status.dataset.loading;
        clearTimeout(pending);
        pending = setTimeout(failed, 20000);
        frame.src = url.href;
    };
    form.querySelector('.fw-publisher-fallback').hidden = true;
    copy.hidden = false;
    form.addEventListener('change', update);
    form.addEventListener('submit', event => { event.preventDefault(); update(); });
    demo.addEventListener('change', update);
    const modes = [...document.querySelectorAll('[data-preview-mode]')];
    const deniedConfig = form.querySelector('[data-preview-config="denied"]');
    document.querySelector('.fw-preview-fallback').hidden = true;
    const changeMode = mode => {
        demo.checked = mode.dataset.previewMode === 'denied';
        modes.forEach(button => { button.setAttribute('aria-selected', String(button === mode)); button.tabIndex = button === mode ? 0 : -1; });
        if (deniedConfig) deniedConfig.hidden = !demo.checked;
        const panel = document.getElementById('fw-preview-live-panel');
        if (panel) panel.setAttribute('aria-labelledby', mode.id);
        update();
    };
    modes.forEach((mode, i) => {
        mode.id = 'fw-preview-mode-' + mode.dataset.previewMode;
        mode.setAttribute('aria-controls', 'fw-preview-live-panel');
        mode.tabIndex = i ? -1 : 0;
        mode.addEventListener('click', () => changeMode(mode));
        mode.addEventListener('keydown', event => {
            if (['ArrowRight', 'ArrowLeft', 'Home', 'End'].includes(event.key)) {
                event.preventDefault(); const next = event.key === 'Home' ? modes[0] : event.key === 'End' ? modes[modes.length - 1] : modes[(i + 1) % modes.length];
                changeMode(next); next.focus();
            }
        });
    });
    if (deniedConfig) deniedConfig.hidden = true;
    frame.addEventListener('load', () => {
        try {
            if (requested && frame.contentWindow.location.href !== requested) return;
            clearTimeout(pending);
            status.textContent = frame.contentDocument?.querySelector('main') ? status.dataset.ready : status.dataset.failure;
        }
        catch (_) { failed(); }
    });
    frame.addEventListener('error', failed);
    copy.addEventListener('click', async () => {
        try { await navigator.clipboard.writeText(code.value); feedback.textContent = feedback.dataset.success; }
        catch (_) { code.focus(); code.select(); feedback.textContent = feedback.dataset.failure; }
    });
})();
