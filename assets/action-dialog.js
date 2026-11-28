/* Enhance existing server-rendered action forms; their nonce, review and POST handlers stay authoritative. */
(() => {
    'use strict';
    const c = globalThis.filewardenLibrary;
    if (!c || typeof HTMLDialogElement === 'undefined') return;
    document.querySelectorAll('.fw-library-search').forEach(form => {
        const details = form.querySelector('.fw-library-filters'), toggle = form.querySelector('[data-filter-toggle]');
        if (!details || !toggle) return;
        form.classList.add('fw-filters-enhanced'); toggle.hidden = false;
        toggle.addEventListener('click', () => { details.open = !details.open; });
        details.addEventListener('toggle', () => toggle.setAttribute('aria-expanded', String(details.open)));
        details.addEventListener('keydown', event => { if (event.key === 'Escape') { details.open = false; toggle.focus(); event.preventDefault(); } });
    });
    document.addEventListener('input', event => {
        if (!event.target.matches('textarea[data-description]')) return;
        const field = event.target, count = Array.from(field.value).length;
        field.setCustomValidity(count > 400 ? c.descriptionLimit : '');
        const output = field.parentElement.querySelector('.fw-description-count');
        if (output) output.textContent = count + ' / 400';
    });
    document.addEventListener('reset', event => {
        if (!event.target.querySelector('[data-description]')) return;
        setTimeout(() => {
            event.target.querySelectorAll('[data-description]').forEach(field => field.dispatchEvent(new Event('input', {bubbles:true})));
            const details = event.target.closest('.fw-description-editor'); if (details) details.open = false;
        }, 0);
    });
    const actions = new Set(['details', 'trash', 'move', 'copy', 'restore', 'delete_folder', 'rights']);
    const actionURL = href => {
        try { const url = new URL(href, location.href); return url.origin === location.origin && url.pathname === location.pathname && url.searchParams.get('page') === 'filewarden' && actions.has(url.searchParams.get('panel')) ? url : null; }
        catch (_) { return null; }
    };
    let dialog = null, pending = false, posting = false, read = null;
    const report = message => globalThis.FileWardenNotices?.show({type:'error', message});
    const mount = inspector => {
        dialog.replaceChildren(inspector);
        dialog.classList.toggle('fw-document-dialog', !!inspector.querySelector('.fw-document-sheet'));
        inspector.querySelectorAll('[name=policy_type]').forEach(select => {
            const roles = select.closest('form').querySelector('.fw-role-options');
            const boundary = select.closest('[data-public-restricted]');
            const warning = boundary?.querySelector('.fw-public-warning');
            const update = () => {
                if (roles) roles.hidden = select.value !== 'roles';
                if (warning) warning.hidden = !(select.value === 'public' && boundary.dataset.publicRestricted === 'true');
            };
            select.addEventListener('change', update); update();
        });
        const heading = inspector.querySelector('h2');
        if (heading) { heading.id = 'fw-action-dialog-title'; dialog.setAttribute('aria-labelledby', heading.id); }
        dialog.querySelector('.fw-close')?.focus();
    };
    const create = source => {
        posting = false;
        dialog = document.createElement('dialog'); dialog.className = 'filewarden-admin fw-workspace fw-action-dialog';
        const placeholder = document.createElement('section'); placeholder.className = 'fw-inspector';
        const title = document.createElement('h2'); title.id = 'fw-action-dialog-title'; title.textContent = source?.textContent.trim() || c.review;
        const close = document.createElement('button'); close.type = 'button'; close.className = 'button fw-close fw-modal-close'; close.textContent = '×'; close.setAttribute('aria-label', c.close);
        close.addEventListener('click', () => { if (!posting) dialog?.close(); });
        placeholder.append(close,title); dialog.append(placeholder); dialog.setAttribute('aria-labelledby', title.id);
        dialog.addEventListener('cancel', event => { if (posting) event.preventDefault(); else read?.abort(); });
        dialog.addEventListener('close', event => {
            const closed = event.currentTarget;
            if (dialog === closed) { read?.abort(); read = null; dialog = null; pending = false; posting = false; }
            closed.remove();
            if (source?.isConnected) {
                const menu = source.closest('details'); if (menu) menu.open = true;
                source.focus();
            }
        });
        dialog.addEventListener('click', event => {
            const link = event.target.closest('a');
            if (link?.matches('.fw-close,.fw-cancel')) { event.preventDefault(); if (!posting) dialog.close(); }
        });
        dialog.addEventListener('submit', async event => {
            const form = event.target;
            if (pending) { event.preventDefault(); return; }
            if (form.method.toLowerCase() !== 'get') {
                // Native POST follows the existing server redirect and verified feedback.
                pending = true; posting = true; dialog.setAttribute('aria-busy','true');
                setTimeout(() => { if (dialog) dialog.querySelectorAll('button,input[type=submit]').forEach(button => button.disabled = true); }, 0);
                return;
            }
            event.preventDefault();
            const url = new URL(form.getAttribute('action') || location.href, location.href);
            const data = new FormData(form);
            if (event.submitter?.name) data.set(event.submitter.name, event.submitter.value);
            for (const [key,value] of data) url.searchParams.set(key,value);
            if (!actionURL(url)) { report(c.error); return; }
            await load(url);
        });
        document.body.append(dialog); dialog.showModal();
    };
    const load = async url => {
        if (pending) return;
        const active = dialog, controller = new AbortController(); read = controller;
        pending = true; active.setAttribute('aria-busy','true');
        try {
            const response = await fetch(url, {credentials:'same-origin',cache:'no-store',signal:controller.signal});
            if (!response.ok || response.redirected) throw new Error(c.error);
            const page = new DOMParser().parseFromString(await response.text(), 'text/html');
            const inspector = page.querySelector('.fw-inspector');
            if (!inspector) throw new Error(c.error);
            if (dialog !== active) return;
            mount(document.importNode(inspector,true));
        } catch (error) { if (error.name !== 'AbortError' && dialog === active) { dialog.close(); report(c.error); } }
        finally { if (dialog === active) { pending = false; active.removeAttribute('aria-busy'); if (read === controller) read = null; } }
    };
    document.addEventListener('click', event => {
        if (event.defaultPrevented || event.ctrlKey || event.metaKey || event.shiftKey || event.altKey || event.button) return;
        const link = event.target.closest('a[href]');
        if (!link || link.target === '_blank') return;
        const url = actionURL(link.href); if (!url) return;
        if (dialog) {
            if (dialog.contains(link) && !pending) { event.preventDefault(); load(url); }
            return;
        }
        event.preventDefault(); create(link); load(url);
    });
    const initial = actionURL(location.href), inspector = document.querySelector('.fw-inspector');
    if (initial && inspector) {
        create(document.querySelector('.fw-task h2,.fw-heading h1'));
        mount(inspector); document.querySelector('.fw-layout')?.classList.remove('has-panel');
    }
})();
