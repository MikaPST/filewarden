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
