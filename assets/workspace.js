/* Progressive enhancement: all navigation and mutations also work without JavaScript. */
(() => {
    'use strict';
    const workspace = document.querySelector('.fw-workspace');
    if (!workspace) return;
    const exportStatus = workspace.querySelector('.fw-export-status');
    workspace.querySelectorAll('.fw-export-button').forEach(button => {
        button.addEventListener('click', async event => {
            event.preventDefault();
            if (button.getAttribute('aria-busy') === 'true') return;
            if (exportStatus) exportStatus.textContent = button.dataset.exportMessage || '';
            const notification = globalThis.FileWardenNotices?.show({ type: 'info', title: button.dataset.exportTitle, message: button.dataset.exportMessage, persistent: true });
            button.setAttribute('aria-busy', 'true');
            button.setAttribute('aria-disabled', 'true');
            try {
                const response = await fetch(button.href, { credentials: 'same-origin', cache: 'no-store' });
                const disposition = response.headers.get('Content-Disposition') || '';
                const filename = /^attachment; filename="(filewarden-audit-\d{4}-\d{2}-\d{2}\.(?:csv|txt|jsonl))"$/.exec(disposition);
                if (!response.ok || response.redirected || !filename) throw new Error('Export refused');
                const blob = await response.blob();
                const url = URL.createObjectURL(blob);
                const link = document.createElement('a');
                link.href = url;
                link.download = filename[1];
                document.body.append(link);
                link.click();
                link.remove();
                setTimeout(() => URL.revokeObjectURL(url), 30000);
                if (exportStatus) exportStatus.textContent = button.dataset.exportSuccess || '';
                globalThis.FileWardenNotices?.update(notification, { type: 'success', title: button.dataset.exportTitle, message: button.dataset.exportSuccess });
            } catch {
                if (exportStatus) exportStatus.textContent = button.dataset.exportError || '';
                globalThis.FileWardenNotices?.update(notification, { type: 'error', title: button.dataset.exportTitle, message: button.dataset.exportError });
            } finally {
                button.removeAttribute('aria-busy');
                button.removeAttribute('aria-disabled');
            }
        });
    });
    const contexts = [...workspace.querySelectorAll('.fw-context')];
    const closeMenus = except => contexts.forEach(menu => { if (menu !== except) menu.open = false; });
    const position = menu => {
        const panel = menu.querySelector('.fw-context-menu');
        const rect = menu.querySelector('summary').getBoundingClientRect();
        panel.style.position = 'fixed';
        panel.style.right = 'auto';
        panel.style.width = 'max-content';
        panel.style.maxWidth = Math.max(1, innerWidth - 16) + 'px';
        panel.style.boxSizing = 'border-box';
        panel.style.left = Math.max(8, Math.min(rect.right - panel.offsetWidth, innerWidth - panel.offsetWidth - 8)) + 'px';
        panel.style.top = Math.max(8, rect.bottom + panel.offsetHeight < innerHeight - 8 ? rect.bottom + 6 : rect.top - panel.offsetHeight - 6) + 'px';
    };
    contexts.forEach(menu => {
        menu.addEventListener('toggle', () => {
            if (!menu.open) return;
            closeMenus(menu);
            position(menu);
        });
    });
    document.addEventListener('click', event => {
        const menu = event.target.closest('.fw-context');
        closeMenus(menu);
    });
    // Scrolling can be caused by focus or browser scroll restoration. Keep the
    // open menu anchored instead of racing the user's next action.
    const reposition = () => contexts.filter(menu => menu.open).forEach(position);
    window.addEventListener('resize', reposition);
    window.addEventListener('scroll', reposition, true);
    document.addEventListener('keydown', event => {
        if (event.key !== 'Escape' || document.querySelector('dialog[open]')) return;
        const open = contexts.find(menu => menu.open);
        if (open) {
            open.open = false;
            open.querySelector('summary').focus();
            event.preventDefault();
        } else {
            const close = workspace.querySelector('.fw-inspector .fw-close');
            if (close) close.click();
        }
    });
    workspace.querySelectorAll('[name="policy_type"]').forEach(select => {
        const form = select.closest('form');
        const roles = form.querySelector('.fw-role-options');
        const boundary = select.closest('[data-public-restricted]');
        const warning = boundary?.querySelector('.fw-public-warning');
        const update = () => {
            if (roles) roles.hidden = select.value !== 'roles';
            if (warning) warning.hidden = !(select.value === 'public' && boundary.dataset.publicRestricted === 'true');
        };
        select.addEventListener('change', update);
        update();
    });
    const parentPicker = workspace.querySelector('#folder-parent');
    if (parentPicker) {
        const policy = parentPicker.closest('form').querySelector('[name="policy_type"]');
        const inherit = policy?.querySelector('option[value="inherit"]');
        if (inherit) {
            const sync = (changed) => {
                inherit.disabled = parentPicker.value === '0';
                if (changed || (inherit.disabled && policy.value === 'inherit')) {
                    policy.value = inherit.disabled ? 'authenticated' : 'inherit';
                    policy.dispatchEvent(new Event('change'));
                }
            };
            parentPicker.addEventListener('change', () => sync(true));
            sync(false);
        }
    }
    const heading = workspace.querySelector('.fw-inspector h2');

    // Rule notes are attached to each relevant row and never change its ACL.
    const ruleNotes = [...workspace.querySelectorAll('.fw-rule-info')];
    let pinnedNote = null;
    const closeRuleNotes = except => ruleNotes.forEach(note => { if (note !== except) note.open = false; });
    const showRuleNote = note => {
        closeRuleNotes(note);
        note.open = true;
        const popup = note.querySelector('.fw-rule-popup');
        const anchor = note.querySelector('summary').getBoundingClientRect();
        popup.style.left = Math.max(12, Math.min(anchor.right - popup.offsetWidth, innerWidth - popup.offsetWidth - 12)) + 'px';
        popup.style.top = Math.max(12, anchor.bottom + popup.offsetHeight + 8 <= innerHeight ? anchor.bottom + 6 : anchor.top - popup.offsetHeight - 6) + 'px';
    };
    ruleNotes.forEach(note => {
        const trigger = note.querySelector('summary');
        note.addEventListener('pointerenter', () => showRuleNote(note));
        note.addEventListener('pointerleave', () => { if (pinnedNote !== note && !note.contains(document.activeElement)) note.open = false; });
        trigger.addEventListener('focus', () => showRuleNote(note));
        note.addEventListener('focusout', event => { if (!note.contains(event.relatedTarget) && pinnedNote !== note) note.open = false; });
        trigger.addEventListener('click', event => {
            event.preventDefault();
            if (pinnedNote === note) { pinnedNote = null; note.open = false; }
            else { pinnedNote = note; showRuleNote(note); }
        });
    });
    document.addEventListener('click', event => { if (!event.target.closest('.fw-rule-info')) { pinnedNote = null; closeRuleNotes(); } });
    document.addEventListener('keydown', event => { if (event.key === 'Escape') { pinnedNote = null; closeRuleNotes(); } });
    window.addEventListener('resize', () => { pinnedNote = null; closeRuleNotes(); });
    window.addEventListener('scroll', () => ruleNotes.filter(note => note.open).forEach(showRuleNote), true);

    // Keep entered fields when a navigation is cancelled, without trapping POSTs.
    let dirty = false;
    workspace.querySelectorAll('.fw-task form').forEach(form => {
        form.addEventListener('input', () => { dirty = true; });
        form.addEventListener('change', () => { dirty = true; });
        form.addEventListener('submit', () => { dirty = false; }, true);
    });
    workspace.addEventListener('click', event => {
        const link = event.target.closest('a[href]');
        if (!dirty || !link || link.target === '_blank' || event.ctrlKey || event.metaKey) return;
        if (!window.confirm(globalThis.filewardenWorkspace?.leave || 'Leave this form without saving?')) event.preventDefault();
        else dirty = false;
    });
    window.addEventListener('beforeunload', event => { if (dirty) { event.preventDefault(); event.returnValue = ''; } });

    if (heading) {
        const focus = () => heading.focus({ preventScroll: true });
        if (document.readyState === 'complete') focus();
        else window.addEventListener('load', focus, { once: true });
    }
})();
