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
            } catch {
                if (exportStatus) exportStatus.textContent = button.dataset.exportError || '';
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
    if (heading) {
        const focus = () => heading.focus({ preventScroll: true });
        if (document.readyState === 'complete') focus();
        else window.addEventListener('load', focus, { once: true });
    }
})();
