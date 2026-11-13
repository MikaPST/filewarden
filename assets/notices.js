/* Accessible, manually dismissible notices; only successful transient feedback expires. */
(() => {
    const scope = document.querySelector('#wpbody-content');
    if (!scope) return;
    const enhance = notice => {
        if (notice.dataset.fwNotice) return;
        notice.dataset.fwNotice = '1';
        notice.classList.add('fw-notice');
        const type = ['error', 'warning', 'success', 'info'].find(value => notice.classList.contains('notice-' + value)) || 'info';
        notice.setAttribute('role', type === 'error' ? 'alert' : 'status');
        const icon = document.createElement('span');
        icon.className = 'dashicons dashicons-' + ({ error: 'dismiss', warning: 'warning', success: 'yes-alt', info: 'info' }[type]);
        icon.setAttribute('aria-hidden', 'true');
        notice.prepend(icon);
        const close = document.createElement('button');
        close.type = 'button';
        close.className = 'fw-notice-close';
        close.setAttribute('aria-label', window.filewardenNotices?.dismiss || 'Dismiss notification');
        close.textContent = '×';
        const dismiss = () => {
            if (notice.contains(document.activeElement)) {
                const heading = scope.querySelector('h1');
                if (heading) { heading.tabIndex = -1; heading.focus(); }
            }
            notice.remove();
        };
        close.addEventListener('click', dismiss);
        notice.append(close);
        if (type !== 'success') return;
        const progress = document.createElement('span');
        progress.className = 'fw-notice-progress';
        progress.setAttribute('aria-hidden', 'true');
        notice.append(progress);
        let remaining = 12000;
        let last = performance.now();
        const timer = setInterval(() => {
            if (!notice.isConnected) { clearInterval(timer); return; }
            const now = performance.now();
            if (!document.hidden && !notice.matches(':hover') && !notice.contains(document.activeElement)) remaining -= now - last;
            last = now;
            progress.style.width = Math.max(0, remaining / 120) + '%';
            if (remaining <= 0) { clearInterval(timer); dismiss(); }
        }, 100);
    };
    scope.querySelectorAll('.notice').forEach(enhance);
    new MutationObserver(() => scope.querySelectorAll('.notice:not([data-fw-notice])').forEach(enhance)).observe(scope, { childList: true, subtree: true });
})();
