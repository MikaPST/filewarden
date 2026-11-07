document.addEventListener('DOMContentLoaded', () => {
    if (typeof HTMLDialogElement === 'undefined') return;
    const dialog = document.createElement('dialog');
    dialog.className = 'filewarden-preview-dialog';
    dialog.setAttribute('aria-label', filewardenPreview.title);
    const close = document.createElement('button');
    close.type = 'button';
    close.className = 'button';
    close.textContent = filewardenPreview.close;
    const frame = document.createElement('iframe');
    frame.title = filewardenPreview.title;
    dialog.append(close, frame);
    document.body.append(dialog);
    let trigger;
    close.addEventListener('click', () => dialog.close());
    dialog.addEventListener('close', () => {
        frame.removeAttribute('src');
        trigger?.focus();
    });
    document.addEventListener('click', (event) => {
        const link = event.target.closest('a[data-filewarden-preview]');
        if (!link || event.button !== 0 || event.ctrlKey || event.metaKey || event.shiftKey || event.altKey) return;
        const url = new URL(link.href);
        if (url.origin !== location.origin || url.searchParams.get('action') !== 'filewarden_content') return;
        event.preventDefault();
        trigger = link;
        frame.src = url.href;
        dialog.showModal();
        close.focus();
    });
    window.addEventListener('message', (event) => {
        if (dialog.open && event.origin === location.origin && event.source === frame.contentWindow && event.data === 'filewarden:close-preview') dialog.close();
    });
});
