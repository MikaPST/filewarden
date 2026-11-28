/* Authorized selection reviews and direct rename; no optimistic success. */
(() => {
    'use strict';
    const config = globalThis.filewardenLibrary;
    if (!config) return;
    const announce = (message, type = 'error') => globalThis.FileWardenNotices?.show({ type, message });
    const request = async payload => {
        const response = await fetch(config.endpoint, { method: 'POST', credentials: 'same-origin', headers: { 'Content-Type': 'application/json', 'X-WP-Nonce': config.nonce }, body: JSON.stringify(payload) });
        const value = await response.json();
        if (!response.ok) throw new Error(value.message || config.error);
        return value;
    };
    const dialog = (title, opener) => {
        const node = document.createElement('dialog'); node.className = 'filewarden-admin fw-workspace fw-library-dialog';
        const form = document.createElement('form'); form.method = 'dialog';
        const heading = document.createElement('h2'); heading.textContent = title; heading.id = 'fw-library-dialog-title';
        node.setAttribute('aria-labelledby', heading.id);
        const content = document.createElement('div');
        const cancel = document.createElement('button'); cancel.type = 'button'; cancel.className = 'button'; cancel.textContent = config.cancel;
        cancel.addEventListener('click', () => { if (node.getAttribute('aria-busy') !== 'true') node.close(); });
        node.addEventListener('cancel', event => { if (node.getAttribute('aria-busy') === 'true') event.preventDefault(); });
        const close = document.createElement('button'); close.type = 'button'; close.className = 'button fw-dialog-close fw-modal-close'; close.textContent = '×'; close.setAttribute('aria-label', config.close);
        close.addEventListener('click', () => cancel.click());
        const confirm = document.createElement('button'); confirm.type = 'submit'; confirm.className = 'button button-primary'; confirm.textContent = config.confirm;
        const error = document.createElement('p'); error.setAttribute('role', 'alert');
        form.append(heading, content, error, cancel, confirm); node.append(close, form); document.body.append(node);
        node.addEventListener('close', () => { node.remove(); opener.focus(); });
        node.showModal();
        return { node, form, content, confirm, error };
    };
    document.querySelectorAll('[data-rename-kind]').forEach(button => button.addEventListener('click', () => {
        const ui = dialog(config.rename, button);
        const label = document.createElement('label'); label.textContent = button.dataset.renameKind === 'folder' ? config.folderName : config.name;
        const input = document.createElement('input'); input.value = button.dataset.renameName; input.maxLength = 255; input.required = true;
        label.append(input); ui.content.append(label); input.focus(); input.select();
        ui.form.addEventListener('submit', async event => {
            event.preventDefault(); if (ui.confirm.disabled) return; ui.confirm.disabled = true; ui.node.setAttribute('aria-busy', 'true');
            try {
                await request({ operation: 'rename', kind: button.dataset.renameKind, id: button.dataset.renameId, name: input.value });
                announce(config.reload, 'success'); location.reload();
            } catch (error) { ui.error.textContent = error.message || config.error; ui.confirm.disabled = false; ui.node.removeAttribute('aria-busy'); }
        });
    }));
    document.querySelectorAll('th').forEach(cell => {
        const headings = [...cell.querySelectorAll('.fw-sort-heading[data-sort-state]')];
        const active = headings.find(heading => heading.dataset.sortState !== 'none') || headings[0];
        if (active) cell.setAttribute('aria-sort', active.dataset.sortState);
    });
    const mobile = window.matchMedia('(max-width: 782px)');
    document.querySelectorAll('.fw-row-actions').forEach(actions => {
        const rename = actions.querySelector(':scope > .fw-rename-document');
        const menu = actions.querySelector('.fw-context');
        const destination = menu?.querySelector('.fw-mobile-file-actions');
        if (!rename || !destination) return;
        const arrange = () => { if (mobile.matches) destination.append(rename); else actions.insertBefore(rename, menu); };
        mobile.addEventListener('change', arrange); arrange();
    });
    const bar = document.querySelector('.fw-selection');
    if (!bar) return;
    const boxes = [...document.querySelectorAll('[data-document-selection]')];
    const all = document.querySelector('[data-select-visible]');
    if (!all) return;
    const submit = bar.querySelector('button[type=submit]');
    const trash = bar.querySelector('[data-bulk-trash]');
    const selected = () => boxes.filter(box => box.checked).map(box => box.value);
    const update = () => {
        const count = selected().length;
        bar.hidden = count === 0;
        all.disabled = boxes.length === 0;
        boxes.forEach(box => box.closest('tr').classList.toggle('fw-row-selected', box.checked));
        bar.querySelector('output').textContent = `${count} ${config.selected}`;
        all.checked = !!count && count === boxes.length;
        all.indeterminate = !!count && count < boxes.length;
        if (submit) submit.disabled = !count || count > 50;
        if (trash) trash.disabled = !count || count > 50;
    };
    boxes.forEach(box => box.addEventListener('change', update));
    bar.querySelector('[data-clear-selection]')?.addEventListener('click', () => { boxes.forEach(box => box.checked = false); update(); all.focus(); });
    all.addEventListener('change', () => { boxes.forEach((box, i) => box.checked = all.checked && i < 50); update(); });
    let reviewing = false;
    const reviewSelection = async (operation, opener, destination = 0) => {
        if (reviewing) return;
        reviewing = true; opener.disabled = true;
        const payload = { operation, ids: selected(), destination: operation === 'trash' ? 0 : destination };
        try {
            const review = await request(payload);
            const ui = dialog(operation === 'trash' ? `${config.trash} (${review.rows.length})` : config.review, opener);
            if (operation === 'trash') {
                ui.confirm.textContent = config.trash; ui.confirm.classList.add('fw-button-danger');
                const help = document.createElement('p'); help.textContent = config.trashHelp; ui.content.append(help);
            }
            const list = document.createElement('ul');
            review.rows.forEach(row => { const item = document.createElement('li'); item.textContent = row.name + ' — ' + row.access; list.append(item); });
            ui.content.append(list);
            ui.form.addEventListener('submit', async event => {
                event.preventDefault(); if (ui.confirm.disabled) return; ui.confirm.disabled = true; ui.node.setAttribute('aria-busy', 'true');
                try { await request({ ...payload, confirm: true, proof: review.proof }); announce(config.reload, 'success'); location.reload(); }
                catch (error) { ui.error.textContent = error.message || config.error; ui.confirm.disabled = false; ui.node.removeAttribute('aria-busy'); }
            });
        } catch (error) { announce(error.message || config.error); }
        finally { reviewing = false; update(); }
    };
    bar.addEventListener('submit', event => {
        event.preventDefault();
        const template = bar.querySelector('[data-selection-destination]');
        if (!template || !selected().length) return;
        const ui = dialog(config.destination, submit);
        ui.content.append(template.content.cloneNode(true));
        const destination = ui.content.querySelector('select');
        if (bar.dataset.operation === 'restore') destination.required = false;
        ui.confirm.textContent = config.check;
        destination.focus();
        ui.form.addEventListener('submit', async event => {
            event.preventDefault();
            if (!ui.form.reportValidity()) return;
            const value = destination.value || '0';
            ui.node.close();
            await reviewSelection(bar.dataset.operation, submit, value);
        });
    });
    trash?.addEventListener('click', () => reviewSelection('trash', trash));
    update();
})();
