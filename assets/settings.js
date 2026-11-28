/* Accessible local category navigation; all form values remain submitted together. */
(() => {
    const nav = document.querySelector('.fw-settings-nav');
    if (!nav) return;
    const links = [...nav.querySelectorAll('a[data-settings-section]')];
    const panels = [...document.querySelectorAll('.fw-settings-panel')];
    const select = (link, focus = false) => {
        for (const item of links) {
            item.setAttribute('aria-selected', String(item === link));
            item.tabIndex = item === link ? 0 : -1;
        }
        for (const panel of panels) panel.hidden = '#' + panel.id !== link.getAttribute('href');
        const save = nav.closest('.fw-settings-layout').querySelector('form .submit');
        if (save) save.hidden = ['backup', 'diagnostic'].includes(link.dataset.settingsSection);
        if (focus) document.querySelector(link.getAttribute('href')).querySelector('h2').focus();
    };
    const tablist = nav.querySelector('.fw-settings-links') || nav;
    tablist.setAttribute('role', 'tablist');
    tablist.setAttribute('aria-orientation', 'vertical');
    tablist.setAttribute('aria-label', nav.getAttribute('aria-label'));
    links.forEach((link, i) => {
        link.id = 'fw-settings-tab-' + link.dataset.settingsSection;
        link.setAttribute('role', 'tab');
        link.setAttribute('aria-controls', link.hash.substring(1));
        const panel = document.querySelector(link.hash);
        panel.setAttribute('role', 'tabpanel');
        panel.setAttribute('aria-labelledby', link.id);
        link.addEventListener('click', event => { event.preventDefault(); select(link); history.replaceState(null, '', link.hash); });
        link.addEventListener('keydown', event => {
            let index = i;
            if (event.key === 'ArrowDown') index = (i + 1) % links.length;
            else if (event.key === 'ArrowUp') index = (i + links.length - 1) % links.length;
            else if (event.key === 'Home') index = 0;
            else if (event.key === 'End') index = links.length - 1;
            else return;
            event.preventDefault(); select(links[index]); links[index].focus();
        });
    });
    select(links.find(link => link.hash === location.hash) || links[0]);
    window.addEventListener('hashchange', () => {
        const target = links.find(link => link.hash === location.hash);
        if (target) select(target);
    });
    document.addEventListener('click', event => {
        const link = event.target.closest('[data-fw-open-storage]'); if (!link) return;
        const target = links.find(item => item.dataset.settingsSection === 'storage');
        if (target) {
            const helper = document.getElementById(link.dataset.fwRepairTarget || 'fw-wp-table-conversion');
            // A fresh diagnostic can discover an anomaly absent when the page was rendered.
            if (!helper) return;
            event.preventDefault(); target.click(); select(target, true);
            if (helper) { helper.classList.add('fw-helper-highlight'); helper.scrollIntoView({block:'center'}); helper.focus({preventScroll:true}); }
        }
    });
    const repair = new URL(location.href).searchParams.get('repair');
    if (['wordpress','plugin'].includes(repair)) {
        const helper = document.getElementById(repair === 'wordpress' ? 'fw-wp-table-conversion' : 'fw-database-repair-section');
        if (helper) { helper.classList.add('fw-helper-highlight'); helper.scrollIntoView({block:'center'}); }
    }
    nav.closest('.fw-settings-layout').querySelector('form').addEventListener('invalid', event => {
        const panel = event.target.closest('.fw-settings-panel');
        const link = links.find(link => link.hash === '#' + panel?.id);
        if (link) select(link);
    }, true);
    nav.dataset.fwSettingsReady = 'true';
})();

/* A confirmation prepares the opt-in; only the nonce-protected save persists it. */
(() => {
    const checkbox = document.getElementById('fw-allow-all-types');
    const dialog = document.querySelector('.fw-import-dialog');
    if (!checkbox || !dialog) return;
    const form = checkbox.form;
    const confirmation = document.createElement('input');
    confirmation.type = 'hidden'; confirmation.name = 'all_types_confirmed'; confirmation.value = '0';
    form.append(confirmation);
    const ask = () => {
        checkbox.checked = false;
        if (typeof dialog.showModal === 'function') {
            dialog.returnValue = '';
            dialog.showModal();
        } else {
            checkbox.checked = window.confirm(dialog.querySelector('#fw-import-dialog-description').textContent);
            confirmation.value = checkbox.checked ? '1' : '0';
        }
    };
    checkbox.addEventListener('change', () => {
        if (checkbox.checked && checkbox.dataset.fwEnabled !== 'true' && confirmation.value !== '1') ask();
        else if (!checkbox.checked) confirmation.value = '0';
    });
    dialog.querySelector('[data-fw-import-cancel]').addEventListener('click', () => dialog.close('cancel'));
    dialog.querySelector('[data-fw-import-confirm]').addEventListener('click', () => dialog.close('confirm'));
    dialog.addEventListener('close', () => {
        checkbox.checked = dialog.returnValue === 'confirm';
        confirmation.value = checkbox.checked ? '1' : '0';
    });
    form.addEventListener('submit', event => {
        if (checkbox.checked && checkbox.dataset.fwEnabled !== 'true' && confirmation.value !== '1') {
            event.preventDefault(); ask();
        }
    });
})();
