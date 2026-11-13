/* Progressive multi-file selection; each file uses the existing independently authorized upload route. */
document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('form[data-filewarden-upload]').forEach(form => {
        const input = form.querySelector('input[type=file]');
        const zone = form.querySelector('.fw-dropzone');
        const list = form.querySelector('.fw-upload-selection');
        if (!input || !zone || !list) return;
        const messages = window.filewardenQueue;
        input.multiple = true;
        let items = [];
        let busy = false;
        const showFiles = () => {
            items = [...input.files].map(file => ({ file, state: 'ready' }));
            list.replaceChildren();
            items.forEach(item => {
                const row = document.createElement('li');
                row.className = 'fw-upload-item';
                const label = document.createElement('span');
                label.textContent = item.file.name + ' (' + (item.file.size / 1024).toFixed(1) + ' KB)';
                const state = document.createElement('span');
                state.setAttribute('role', 'status');
                item.output = state;
                if (!/\.(pdf|jpe?g|png|txt|csv|zip|docx|xlsx|pptx|odt|ods|odp)$/i.test(item.file.name) || !item.file.size || item.file.size > messages.maxBytes) item.state = 'invalid';
                state.textContent = item.state === 'invalid' ? messages.invalid : messages.ready;
                row.append(label, state); list.append(row);
            });
        };
        input.addEventListener('change', showFiles);
        zone.addEventListener('dragover', event => { event.preventDefault(); if (!busy) zone.classList.add('fw-drop-active'); });
        zone.addEventListener('dragleave', () => zone.classList.remove('fw-drop-active'));
        zone.addEventListener('drop', event => {
            event.preventDefault(); zone.classList.remove('fw-drop-active');
            if (busy || !event.dataTransfer?.files.length) return;
            input.files = event.dataTransfer.files; showFiles();
        });
        form.addEventListener('submit', async event => {
            if (form.querySelector('input[name=source]:checked')?.value !== 'upload' || items.length < 2) return;
            event.preventDefault(); event.stopImmediatePropagation();
            if (busy) return;
            if (items.some(item => item.state === 'invalid')) { list.focus(); return; }
            const fields = [...new FormData(form).entries()].filter(([key]) => key !== 'file');
            const controls = [...form.querySelectorAll('input, select, button')];
            const previous = controls.map(control => control.disabled);
            controls.forEach(control => { control.disabled = true; });
            form.setAttribute('aria-busy', 'true'); busy = true;
            try {
                for (const item of items.filter(item => item.state !== 'saved')) {
                    item.output.textContent = messages.sending;
                    const data = new FormData();
                    fields.forEach(([key, value]) => data.append(key, value));
                    data.set('file', item.file, item.file.name);
                    try {
                        const response = await fetch(form.getAttribute('action'), { method: 'POST', credentials: 'same-origin', body: data });
                        const url = new URL(response.url);
                        if (!response.ok || url.origin !== location.origin || url.searchParams.get('page') !== 'filewarden' || url.searchParams.get('result') !== 'success') throw new Error('Upload refused');
                        item.state = 'saved'; item.output.textContent = messages.saved;
                        const html = new DOMParser().parseFromString(await response.text(), 'text/html');
                        const count = html.querySelector('.fw-recovery-count');
                        const current = document.querySelector('.fw-recovery-count');
                        if (count && current) current.textContent = count.textContent;
                    } catch {
                        item.state = 'failed'; item.output.textContent = messages.failed;
                    }
                }
            } finally {
                controls.forEach((control, index) => { control.disabled = previous[index]; });
                busy = false; form.removeAttribute('aria-busy');
            }
        }, true);
        list.tabIndex = -1;
    });
});
