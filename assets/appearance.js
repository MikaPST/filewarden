/* Global public appearance: draft preview only; saving remains a privileged POST. */
(() => {
    'use strict';
    const messages = globalThis.filewardenAppearance;
    const section = document.querySelector('.fw-appearance');
    const form = section?.querySelector('form');
    const frame = document.querySelector('.fw-publisher .filewarden-publication-frame');
    if (!form || !frame || !messages) return;
    const colorInputs = [...form.querySelectorAll('[data-color]')];
    const fields = key => form.querySelector(`[name="appearance[${key}]"]`);
    const checkbox = key => form.querySelector(`input[type=checkbox][name="appearance[${key}]"]`);
    const validHex = value => /^#[0-9a-f]{6}$/i.test(value);
    const currentLogo = section.querySelector('.fw-appearance-logo img');
    let logo = { id: Number(fields('logo_id').value), url: currentLogo.src, title: section.querySelector('.fw-logo-name').textContent };
    let saved = '';
    let submitting = false;
    let picker;
    const draft = () => ({
        preset: fields('preset').value,
        colors: Object.fromEntries(colorInputs.map(input => [input.dataset.color, input.value.toLowerCase()])),
        name: fields('name').value,
        logo_id: Number(fields('logo_id').value),
        show_logo: checkbox('show_logo').checked,
        show_name: checkbox('show_name').checked
    });
    const serialize = () => JSON.stringify(draft());
    const luminance = hex => {
        const rgb = [1, 3, 5].map(offset => parseInt(hex.slice(offset, offset + 2), 16) / 255)
            .map(value => value <= 0.04045 ? value / 12.92 : ((value + 0.055) / 1.055) ** 2.4);
        return .2126 * rgb[0] + .7152 * rgb[1] + .0722 * rgb[2];
    };
    const contrastReport = colors => messages.contrast.pairs.map(([a, b]) =>
        validHex(colors[a]) && validHex(colors[b])
            ? (Math.max(luminance(colors[a]), luminance(colors[b])) + .05) / (Math.min(luminance(colors[a]), luminance(colors[b])) + .05) : null);
    const renderContrast = colors => {
        const ratios = contrastReport(colors);
        const valid = Object.values(colors).every(validHex);
        const count = ratios.filter(ratio => ratio !== null && ratio >= 4.5).length;
        const level = !valid ? 'incomplete' : count < ratios.length ? 'improve' : Math.min(...ratios) >= 7 ? 'aaa' : 'aa';
        const report = form.querySelector('.fw-contrast-report');
        report.dataset.level = level;
        const summary = messages.contrast[level] + (!valid ? '' : ` · ${count}/${ratios.length} ${messages.contrast.compliant}`);
        const node = report.querySelector('[data-contrast-summary]');
        if (node.textContent !== summary) node.textContent = summary;
        report.querySelectorAll('.fw-contrast-segments span').forEach((segment, i) => segment.dataset.pass = String(ratios[i] !== null && ratios[i] >= 4.5));
        report.querySelectorAll('[data-contrast-ratio]').forEach((node, i) => {
            const ratio = ratios[i];
            node.textContent = ratio === null ? messages.contrast.incomplete : ratio.toLocaleString(document.documentElement.lang, { minimumFractionDigits: 2, maximumFractionDigits: 2 }) + ':1 · ' + (ratio >= 7 ? 'AAA' : ratio >= 4.5 ? 'AA' : messages.contrast.improve);
        });
        return valid && count === ratios.length;
    };
    const preview = value => {
        try {
            const url = new URL(frame.contentWindow.location.href);
            if (url.origin !== location.origin || url.searchParams.get('action') !== 'filewarden_folder') return;
            const doc = frame.contentDocument;
            const style = doc?.getElementById('fw-public-appearance');
            if (!style || !doc.body.classList.contains('fw-public-theme')) return;
            // Existing nonced style, finite property names and validated hex values only.
            style.textContent = 'body.fw-public-theme{' + Object.entries(value.colors).map(([key, color]) => '--fw-public-' + key.replaceAll('_', '-') + ':' + color + ';').join('') + '}';
            const brand = doc.querySelector('.fw-public-brand');
            if (!brand) return;
            const children = [];
            if (value.show_logo) {
                const image = doc.createElement('img');
                image.className = 'fw-public-logo'; image.src = logo.url; image.alt = ''; image.width = 38; image.height = 38;
                children.push(image);
            }
            if (value.show_name) {
                const name = doc.createElement('span');
                name.className = 'eyebrow fw-public-name'; name.textContent = value.name;
                children.push(name);
            }
            brand.replaceChildren(...children);
        } catch { /* An unavailable frame must not expose or change another origin. */ }
    };
    const render = () => {
        const value = draft();
        const valid = renderContrast(value.colors);
        const status = form.querySelector('.fw-appearance-contrast');
        status.textContent = valid ? messages.contrastOK : messages.contrastError;
        status.dataset.valid = String(valid);
        form.querySelector('button[type=submit]').disabled = !valid;
        colorInputs.forEach(input => {
            input.setAttribute('aria-invalid', String(!validHex(input.value)));
            if (validHex(input.value)) form.querySelector(`[data-color-picker="${input.dataset.color}"]`).value = input.value;
        });
        form.querySelectorAll('[data-preset]').forEach(button => button.setAttribute('aria-pressed', String(button.dataset.preset === value.preset)));
        const swatch = form.querySelector('.fw-palette-custom');
        if (swatch) for (const key of ['button', 'accent', 'background']) {
            if (validHex(value.colors[key])) swatch.style.setProperty('--fw-palette-' + key, value.colors[key]);
        }
        form.querySelector('.fw-appearance-dirty').textContent = saved === serialize() ? messages.clean : messages.dirty;
        if (Object.values(value.colors).every(validHex)) preview(value);
    };
    const applyColors = colors => colorInputs.forEach(input => { if (validHex(colors[input.dataset.color])) input.value = colors[input.dataset.color]; });
    colorInputs.forEach(input => input.addEventListener('input', () => { fields('preset').value = 'custom'; render(); }));
    form.querySelectorAll('[data-color-picker]').forEach(input => input.addEventListener('input', () => {
        form.querySelector(`[data-color="${input.dataset.colorPicker}"]`).value = input.value;
        fields('preset').value = 'custom'; render();
    }));
    form.querySelectorAll('[data-preset]').forEach(button => button.addEventListener('click', () => {
        fields('preset').value = button.dataset.preset;
        if (button.dataset.preset === 'custom') form.querySelector('[data-color]')?.closest('details')?.setAttribute('open', '');
        else applyColors(messages.presets[button.dataset.preset]);
        render();
    }));
    fields('name').addEventListener('input', render);
    checkbox('show_logo').addEventListener('change', render);
    checkbox('show_name').addEventListener('change', render);
    const setLogo = next => {
        logo = next; fields('logo_id').value = String(next.id); currentLogo.src = next.url;
        section.querySelector('.fw-logo-name').textContent = next.title; render();
    };
    section.querySelector('[data-appearance-logo-reset]').addEventListener('click', () => setLogo({ id: 0, url: messages.officialLogo, title: messages.officialLabel }));
    section.querySelector('[data-appearance-logo]')?.addEventListener('click', () => {
        if (!globalThis.wp?.media) return;
        picker ||= wp.media({ title: messages.logoTitle, button: { text: messages.logoChoose }, multiple: false, library: { type: ['image/png', 'image/jpeg'] } });
        picker.off('select');
        picker.on('select', () => {
            const item = picker.state().get('selection').first()?.toJSON();
            let url;
            try { url = new URL(item?.url); } catch { return; }
            if (!item || !['image/png', 'image/jpeg'].includes(item.mime) || url.origin !== location.origin) {
                globalThis.FileWardenNotices?.show({ type: 'error', message: messages.logoError }); return;
            }
            setLogo({ id: item.id, url: url.href, title: item.title || item.filename });
        });
        picker.open();
    });
    section.querySelector('[data-appearance-reset]').addEventListener('click', () => {
        if (!window.confirm(messages.resetConfirm)) return;
        applyColors(messages.defaults.colors); fields('preset').value = 'filewarden'; fields('name').value = messages.defaults.name;
        checkbox('show_logo').checked = true; checkbox('show_name').checked = true;
        setLogo({ id: 0, url: messages.officialLogo, title: messages.officialLabel });
    });
    frame.addEventListener('load', render);
    form.addEventListener('submit', event => {
        if (!renderContrast(draft().colors)) { event.preventDefault(); return; }
        submitting = true;
    });
    window.addEventListener('beforeunload', event => {
        if (!submitting && saved !== serialize()) { event.preventDefault(); event.returnValue = ''; }
    });
    saved = serialize(); render();
})();
