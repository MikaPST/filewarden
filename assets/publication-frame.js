/* Resize only FileWarden's same-origin frames, without sending document metadata. */
(() => {
    const attach = frame => {
        if (frame.dataset.fwResize) return;
        frame.dataset.fwResize = '1';
        frame.style.border = '0';
        frame.style.display = 'block';
        frame.style.width = '100%';
        let observer;
        const resize = () => {
            if (observer) observer.disconnect();
            try {
                const main = frame.contentDocument.querySelector('main');
                if (!main) return;
                const source = new URL(frame.contentWindow.location.href);
                if (source.origin === location.origin && source.searchParams.get('action') === 'filewarden_folder' && !main.dataset.fwPopupBound) {
                    main.dataset.fwPopupBound = '1';
                    main.addEventListener('click', event => {
                        const link = event.target.closest?.('a.fw-login[data-fw-popup]');
                        if (!link || event.defaultPrevented || event.button !== 0 || event.metaKey || event.ctrlKey || event.shiftKey || event.altKey) return;
                        const value = link.dataset.fwPopup || '';
                        const id = Number(value);
                        const popup = globalThis.elementorProFrontend?.modules?.popup;
                        if (!/^[1-9][0-9]{0,9}$/.test(value) || !Number.isSafeInteger(id) || id > 2147483647 || typeof popup?.showPopup !== 'function') return;
                        try {
                            // Only a user's click in our same-origin frame may open a numeric popup.
                            popup.showPopup({ id });
                            event.preventDefault();
                        } catch (_) { /* Keep the native WordPress sign-in link as fallback. */ }
                    });
                }
                const update = () => { frame.height = String(Math.ceil(main.getBoundingClientRect().height) + 4); };
                update();
                if (typeof ResizeObserver !== 'undefined') { observer = new ResizeObserver(update); observer.observe(main); }
            } catch (_) { /* A redirected login page may be cross-origin. Keep the fallback height. */ }
        };
        frame.addEventListener('load', resize);
        resize();
    };
    const scan = () => document.querySelectorAll('iframe.filewarden-publication-frame').forEach(attach);
    scan();
    // Elementor can insert shortcode frames after the initial page load.
    new MutationObserver(scan).observe(document.documentElement, { childList: true, subtree: true });
})();
