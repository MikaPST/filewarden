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
    // An administration preview must load even while its controls hold scroll/focus.
    frame.loading = 'eager';
    const fitCode = () => {
        code.style.height = 'auto';
        code.style.height = (code.scrollHeight + 2) + 'px';
    };
    window.addEventListener('resize', fitCode);
    requestAnimationFrame(fitCode);
    // Local tabs preserve the same forms and iframe; no draft is reset or saved.
    const settings = document.querySelector('.fw-publication-settings');
    const tabs = [...document.querySelectorAll('[data-publication-tab]')];
    const selectTab = tab => {
        tabs.forEach(button => {
            const active = button === tab;
            button.setAttribute('aria-selected', String(active));
            button.tabIndex = active ? 0 : -1;
            document.getElementById(button.getAttribute('aria-controls')).hidden = !active;
        });
        requestAnimationFrame(fitCode);
    };
    if (tabs.length) {
        document.querySelector('.fw-publication-tabs').hidden = false;
        tabs.forEach((tab, index) => {
            tab.addEventListener('click', () => selectTab(tab));
            tab.addEventListener('keydown', event => {
                if (!['ArrowLeft', 'ArrowRight', 'Home', 'End'].includes(event.key)) return;
                event.preventDefault();
                const next = event.key === 'Home' ? 0 : event.key === 'End' ? tabs.length - 1 : (index + (event.key === 'ArrowRight' ? 1 : -1) + tabs.length) % tabs.length;
                selectTab(tabs[next]); tabs[next].focus();
            });
        });
        selectTab(tabs.find(tab => tab.dataset.publicationTab === settings.dataset.initialTab) || tabs[0]);
    }
    const devices = document.querySelector('.fw-appearance-devices');
    if (devices) {
        devices.hidden = false;
        devices.querySelectorAll('[data-preview-device]').forEach(button => button.addEventListener('click', () => {
            frame.classList.toggle('fw-preview-mobile', button.dataset.previewDevice === 'mobile');
            devices.querySelectorAll('button').forEach(other => other.setAttribute('aria-pressed', String(other === button)));
        }));
    }
    const login = document.querySelector('.fw-publication-login');
    const loginForm = login?.querySelector('form');
    if (loginForm) {
        const address = loginForm.querySelector('#fw-login-url');
        const popupId = loginForm.querySelector('#fw-login-popup');
        const choice = () => loginForm.querySelector('[name="publication_login[mode]"]:checked').value;
        const snapshot = () => JSON.stringify({ mode: choice(), url: choice() === 'custom' ? address.value : '', popup: choice() === 'elementor' ? popupId.value : '' });
        popupId.addEventListener('change', () => {
            const match = /^\[elementor-template\s+id\s*=\s*(["'])([1-9][0-9]{0,9})\1\s*\]$/.exec(popupId.value.trim());
            if (match && Number(match[2]) <= 2147483647) popupId.value = match[2];
        });
        const original = snapshot();
        let saving = false;
        const validURL = value => {
            if (!value || value.length > 2048 || /[\u0000-\u0020\u007f\\]|%0[ad0]/i.test(value) || !/^https?:\/\//i.test(value)) return false;
            try { const url = new URL(value); return ['http:', 'https:'].includes(url.protocol) && Boolean(url.hostname) && !url.username && !url.password; } catch { return false; }
        };
        const updateLogin = () => {
            const custom = choice() === 'custom';
            const elementor = choice() === 'elementor';
            const rawPopup = popupId.value.trim();
            const matchPopup = /^\[elementor-template\s+id\s*=\s*(["'])([1-9][0-9]{0,9})\1\s*\]$/.exec(rawPopup);
            const numericPopup = matchPopup ? matchPopup[2] : rawPopup;
            const validPopup = /^[1-9][0-9]{0,9}$/.test(numericPopup) && Number(numericPopup) <= 2147483647;
            const valid = (!custom || validURL(address.value)) && (!elementor || validPopup);
            popupId.disabled = !elementor; popupId.required = elementor;
            popupId.setAttribute('aria-invalid', String(elementor && !validPopup));
            loginForm.querySelector('.fw-login-elementor').hidden = !elementor;
            address.disabled = !custom; address.required = custom;
            address.setAttribute('aria-invalid', String(!valid));
            loginForm.querySelector('.fw-login-custom').hidden = !custom;
            loginForm.querySelector('button[type=submit]').disabled = !valid;
            const feedback = loginForm.querySelector('.fw-login-validation');
            feedback.textContent = valid ? '' : elementor ? feedback.dataset.invalidPopup : feedback.dataset.invalid;
            const destination = custom ? address.value : login.dataset.wordpressUrl;
            const check = loginForm.querySelector('.fw-login-check');
            if (valid) { check.href = destination; check.removeAttribute('aria-disabled'); }
            else { check.removeAttribute('href'); check.setAttribute('aria-disabled', 'true'); }
            // Draft link affects only the same-origin FileWarden preview, never a public setting.
            try {
                const url = new URL(frame.contentWindow.location.href);
                if (url.origin !== location.origin || url.searchParams.get('action') !== 'filewarden_folder') return;
                const link = frame.contentDocument?.querySelector('body.fw-public-theme .fw-login');
                if (link) {
                    if (elementor && validPopup) link.dataset.fwPopup = popupId.value;
                    else delete link.dataset.fwPopup;
                    if (valid) { link.href = destination; link.removeAttribute('aria-disabled'); }
                    else { link.removeAttribute('href'); link.setAttribute('aria-disabled', 'true'); }
                }
            } catch { /* A foreign or unavailable frame is never modified. */ }
        };
        loginForm.addEventListener('input', updateLogin);
        loginForm.addEventListener('change', updateLogin);
        loginForm.addEventListener('submit', event => {
            if (loginForm.querySelector('button[type=submit]').disabled) { event.preventDefault(); return; }
            saving = true;
        });
        frame.addEventListener('load', updateLogin);
        window.addEventListener('beforeunload', event => {
            if (!saving && snapshot() !== original) { event.preventDefault(); event.returnValue = ''; }
        });
        updateLogin();
    }
    let pending;
    let requested;
    let navigation;
    const failed = () => {
        clearTimeout(pending); status.textContent = status.dataset.failure;
        globalThis.FileWardenNotices?.show({ type: 'error', message: status.dataset.failure });
    };
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
        clearTimeout(navigation);
        // Keep the shortcode immediate; coalesce rapid form changes into one final frame navigation.
        navigation = setTimeout(() => {
            pending = setTimeout(failed, 20000);
            frame.src = url.href;
        }, 120);
    };
    form.querySelector('.fw-publisher-fallback').hidden = true;
    copy.hidden = false;
    form.addEventListener('change', update);
    form.addEventListener('submit', event => { event.preventDefault(); update(); });
    demo.addEventListener('change', update);
    const modes = [...document.querySelectorAll('[data-preview-mode]')];
    const contextual = [...document.querySelectorAll('.fw-publisher [data-preview-config]')];
    const syncContext = () => contextual.forEach(section => {
        section.hidden = section.dataset.previewConfig !== (demo.checked ? 'denied' : 'authorized');
    });
    document.querySelector('.fw-preview-fallback').hidden = true;
    const changeMode = mode => {
        demo.checked = mode.dataset.previewMode === 'denied';
        modes.forEach(button => { button.setAttribute('aria-selected', String(button === mode)); button.tabIndex = button === mode ? 0 : -1; });
        syncContext();
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
    syncContext();
    frame.addEventListener('load', () => {
        try {
            if (requested && frame.contentWindow.location.href !== requested) return;
            clearTimeout(pending);
            if (frame.contentDocument?.querySelector('main')) status.textContent = status.dataset.ready;
            else failed();
        }
        catch (_) { failed(); }
    });
    frame.addEventListener('error', failed);
    copy.addEventListener('click', async () => {
        try {
            await navigator.clipboard.writeText(code.value); feedback.textContent = feedback.dataset.success;
            globalThis.FileWardenNotices?.show({ type: 'success', message: feedback.dataset.success });
        } catch (_) {
            code.focus(); code.select(); feedback.textContent = feedback.dataset.failure;
            globalThis.FileWardenNotices?.show({ type: 'error', message: feedback.dataset.failure });
        }
    });
})();
