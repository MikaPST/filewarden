/* Progressive enhancement: all navigation and mutations also work without JavaScript. */
(() => {
    'use strict';
    const workspace = document.querySelector('.fw-workspace');
    if (!workspace) return;
    const contexts = [...workspace.querySelectorAll('.fw-context')];
    const closeMenus = except => contexts.forEach(menu => { if (menu !== except) menu.open = false; });
    contexts.forEach(menu => {
        menu.addEventListener('toggle', () => {
            if (!menu.open) return;
            closeMenus(menu);
            const panel = menu.querySelector('.fw-context-menu');
            const rect = menu.querySelector('summary').getBoundingClientRect();
            panel.style.position = 'fixed';
            panel.style.left = Math.max(8, Math.min(rect.right - panel.offsetWidth, innerWidth - panel.offsetWidth - 8)) + 'px';
            panel.style.top = Math.max(8, rect.bottom + panel.offsetHeight < innerHeight - 8 ? rect.bottom + 6 : rect.top - panel.offsetHeight - 6) + 'px';
        });
    });
    document.addEventListener('click', event => {
        const menu = event.target.closest('.fw-context');
        closeMenus(menu);
    });
    window.addEventListener('resize', () => closeMenus());
    window.addEventListener('scroll', event => {
        if (!event.target.closest?.('.fw-context-menu')) closeMenus();
    }, true);
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
    const heading = workspace.querySelector('.fw-inspector h2');
    if (heading) heading.focus({ preventScroll: true });
})();
