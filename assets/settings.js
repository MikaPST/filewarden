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
        if (focus) document.querySelector(link.getAttribute('href')).querySelector('h2').focus();
    };
    nav.setAttribute('role', 'tablist');
    nav.setAttribute('aria-orientation', 'vertical');
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
    nav.closest('.fw-settings-layout').querySelector('form').addEventListener('invalid', event => {
        const panel = event.target.closest('.fw-settings-panel');
        const link = links.find(link => link.hash === '#' + panel?.id);
        if (link) select(link);
    }, true);
})();
