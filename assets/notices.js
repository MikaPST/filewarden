/* FileWarden-owned feedback only. Server-rendered notices remain readable without JavaScript. */
(() => {
    'use strict';
    const scope = document.querySelector('#wpbody-content');
    if (!scope) return;
    const config = window.filewardenNotices;
    if (!config) return;
    const seconds = [5, 10, 15, 20, 30].includes(Number(config.duration)) ? Number(config.duration) : 10;
    const types = ['success', 'info', 'warning', 'error'];
    const region = document.createElement('section');
    region.className = 'fw-notice-region';
    region.setAttribute('aria-label', config.label);
    const stack = document.createElement('div'); stack.className = 'fw-notice-stack';
    const more = document.createElement('button');
    more.type = 'button'; more.className = 'fw-notice-more'; more.hidden = true;
    region.append(stack, more); region.hidden = true;
    const cards = [];
    let expanded = false, hidden = document.hidden, inactive = false, frame = null;
    const paused = card => hidden || inactive || card.hover || card.focus || card.element.hidden;
    const advance = (card, now) => {
        if (!card.persistent && !paused(card)) card.remaining = Math.max(0, card.remaining - Math.max(0, now - card.last));
        card.last = now;
    };
    const settle = () => { const now = performance.now(); cards.forEach(card => advance(card, now)); };
    const place = () => {
        // Keep the floating layer outside workspace overflow, transforms and notice aggregators.
        const parent = document.querySelector('dialog.filewarden-preview-dialog[open]') || document.body;
        if (region.parentNode !== parent) parent.append(region);
        const bar = document.getElementById('wpadminbar');
        const bottom = bar ? Math.max(0, bar.getBoundingClientRect().bottom) : 0;
        region.style.setProperty('--fw-admin-bar-height', Math.min(bottom, 64) + 'px');
    };
    const refresh = () => {
        settle();
        const excess = Math.max(0, cards.length - 3);
        if (!excess) expanded = false;
        const critical = cards.slice(3).filter(card => card.persistent).length;
        more.hidden = !excess;
        more.textContent = expanded ? config.less : `${config.more} (${excess})${critical ? ` · ${config.critical}: ${critical}` : ''}`;
        more.setAttribute('aria-expanded', String(expanded));
        cards.forEach((card, index) => {
            const hide = !expanded && index >= 3;
            if (hide && card.element.contains(document.activeElement)) more.focus();
            card.element.hidden = hide; card.last = performance.now();
        });
        region.hidden = !cards.length && !stack.childElementCount;
    };
    const dismiss = (card, automatic = false) => {
        const index = cards.indexOf(card);
        if (index < 0) return;
        const focused = card.element.contains(document.activeElement);
        cards.splice(index, 1);
        if (automatic && !window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
            card.element.classList.add('fw-notice-leaving');
            setTimeout(() => { card.element.remove(); refresh(); }, 160);
        } else card.element.remove();
        refresh();
        if (focused || (more.hidden && document.activeElement === more)) {
            const next = cards.find(item => !item.element.hidden);
            if (next) next.close.focus();
            else {
                const heading = document.querySelector('dialog.filewarden-preview-dialog[open] button') || scope.querySelector('.fw-workspace h1, h1');
                if (heading) { heading.tabIndex = -1; heading.focus({ preventScroll: true }); }
            }
        }
    };
    const tick = now => {
        frame = null;
        for (const card of [...cards]) {
            advance(card, now);
            if (card.persistent) continue;
            card.fill.style.transform = `scaleX(${card.remaining / card.total})`;
            if (card.remaining <= 0) dismiss(card, true);
        }
        if (cards.some(card => !card.persistent)) frame = requestAnimationFrame(tick);
    };
    const start = () => { if (frame === null && cards.some(card => !card.persistent)) frame = requestAnimationFrame(tick); };
    const configure = (card, options) => {
        const type = types.includes(options.type) ? options.type : 'info';
        card.element.className = `fw-notice fw-notice-${type}`;
        card.element.setAttribute('role', type === 'error' ? 'alert' : 'status');
        card.element.setAttribute('aria-atomic', 'true');
        card.icon.className = 'dashicons dashicons-' + ({ error: 'dismiss', warning: 'warning', success: 'yes-alt', info: 'info' }[type]);
        card.title.textContent = options.title || config.titles?.[type] || type; card.message.textContent = options.message || '';
        card.persistent = options.persistent === undefined ? ['error', 'warning'].includes(type) : !!options.persistent;
        card.element.dataset.fwPersistent = String(card.persistent); card.track.hidden = card.persistent;
        card.total = seconds * 1000; card.remaining = card.total; card.last = performance.now();
        card.fill.style.transform = 'scaleX(1)';
    };
    const show = (options, existing) => {
        place();
        const element = existing || document.createElement('div'); element.dataset.fwNotice = '1';
        const icon = document.createElement('span'); icon.setAttribute('aria-hidden', 'true');
        const content = document.createElement('div'); content.className = 'fw-notice-content';
        const title = document.createElement('h2'); title.className = 'fw-notice-title';
        const message = document.createElement('p'); content.append(title, message);
        const close = document.createElement('button'); close.type = 'button'; close.className = 'fw-notice-close'; close.textContent = '×'; close.setAttribute('aria-label', config.dismiss);
        const track = document.createElement('span'); track.className = 'fw-notice-track'; track.setAttribute('aria-hidden', 'true');
        const fill = document.createElement('span'); fill.className = 'fw-notice-progress'; track.append(fill);
        element.replaceChildren(icon, content, close, track);
        const card = { element, icon, title, message, close, track, fill, hover: false, focus: false };
        configure(card, options);
        const pause = (key, value) => { advance(card, performance.now()); card[key] = value; };
        element.addEventListener('mouseenter', () => pause('hover', true));
        element.addEventListener('mouseleave', () => pause('hover', false));
        element.addEventListener('focusin', () => pause('focus', true));
        element.addEventListener('focusout', event => pause('focus', element.contains(event.relatedTarget)));
        close.addEventListener('click', () => dismiss(card));
        cards.unshift(card); stack.prepend(element); refresh(); start();
        return element;
    };
    window.FileWardenNotices = Object.freeze({
        show,
        update(element, options) {
            const card = cards.find(item => item.element === element);
            if (!card) return show(options);
            configure(card, options);
            if (card.element.hidden) {
                cards.splice(cards.indexOf(card), 1); cards.unshift(card); stack.prepend(card.element);
            }
            refresh(); start(); return element;
        },
        dismiss(element) { const card = cards.find(item => item.element === element); if (card) dismiss(card); }
    });
    more.addEventListener('click', () => { expanded = !expanded; refresh(); });
    document.addEventListener('visibilitychange', () => { settle(); hidden = document.hidden; });
    window.addEventListener('blur', () => { settle(); inactive = true; });
    window.addEventListener('focus', () => { settle(); inactive = false; });
    window.addEventListener('resize', place);
    window.addEventListener('scroll', place, { passive: true });
    const enhance = () => {
        if (cards.length) place();
        // Restore owned cards if a legacy notice collector moves them.
        [...cards].reverse().forEach(card => {
            if (card.element.isConnected && card.element.parentNode !== stack) stack.prepend(card.element);
        });
        scope.querySelectorAll('.fw-notice:not([data-fw-notice])').forEach(notice => {
            const type = types.find(value => notice.classList.contains('fw-notice-' + value) || notice.classList.contains('notice-' + value)) || 'info';
            const title = notice.querySelector('.fw-notice-title')?.textContent;
            const message = notice.querySelector('p')?.textContent || '';
            show({ type, title, message, persistent: notice.dataset.fwPersistent !== 'false' }, notice);
        });
    };
    enhance();
    new MutationObserver(enhance).observe(document.body, { childList: true, subtree: true, attributes: true, attributeFilter: ['open'] });
})();
