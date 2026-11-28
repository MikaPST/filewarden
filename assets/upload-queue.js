/* Each upload is independently validated. Duplicate decisions belong to one original file only. */
document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('form[data-filewarden-upload]').forEach(form => {
        const input = form.querySelector('input[type=file]'), zone = form.querySelector('.fw-dropzone');
        const list = form.querySelector('.fw-upload-selection'), messages = window.filewardenQueue;
        if (!input || !zone || !list || !messages) return;
        input.multiple = true;
        // Reviews must stay visible when the upload-source fieldset is hidden for Media imports.
        zone.after(list);
        let items = [], busy = false, mediaItem = null;
        const shared = form.querySelector('.fw-import-description');
        const button = (text, action) => {
            const b = document.createElement('button'); b.type = 'button'; b.className = 'button'; b.textContent = text;
            b.addEventListener('click', action); return b;
        };
        const row = item => {
            const li = document.createElement('li'); li.className = 'fw-upload-item'; item.row = li;
            const label = document.createElement('strong'); label.textContent = item.file?.name || form.querySelector('#fw-media-title')?.textContent || messages.ready;
            item.output = document.createElement('span'); item.output.setAttribute('role','status'); item.output.textContent = messages.ready;
            li.append(label, item.output);
            if (item.file) {
                const details = document.createElement('details'), summary = document.createElement('summary'); summary.textContent = messages.description;
                const field = document.createElement('textarea'); field.rows = 3; field.dataset.description = ''; field.setAttribute('aria-label', messages.description + ': ' + item.file.name);
                const count = document.createElement('output'); count.className = 'fw-description-count'; count.textContent = '0 / 400';
                details.append(summary,field,count); li.append(details); item.description = field;
            }
            li.append(button(messages.remove, () => { if (!busy) { item.state = 'removed'; li.remove(); } }));
            item.review = document.createElement('div'); item.review.hidden = true; item.review.className = 'fw-duplicate-review fw-warning'; li.append(item.review);
            list.append(li);
        };
        const source = () => form.querySelector('input[name=source]:checked')?.value || 'upload';
        const refreshSource = () => {
            if (shared) { shared.hidden = source() === 'upload' && items.length > 0; shared.querySelector('textarea').disabled = shared.hidden; }
        };
        const showFiles = () => {
            if (busy) return;
            items = [...input.files].map(file => ({file,state:'ready',proof:''})); list.replaceChildren(); mediaItem = null;
            items.forEach(row); refreshSource();
        };
        input.addEventListener('change',showFiles);
        form.querySelectorAll('[name=source]').forEach(control => control.addEventListener('change',refreshSource));
        zone.addEventListener('dragover', event => { event.preventDefault(); if (!busy) zone.classList.add('fw-drop-active'); });
        zone.addEventListener('dragleave', () => zone.classList.remove('fw-drop-active'));
        zone.addEventListener('drop', event => { event.preventDefault(); zone.classList.remove('fw-drop-active'); if (!busy && event.dataTransfer?.files.length) { input.files = event.dataTransfer.files; showFiles(); } });
        const review = (item, value) => {
            item.state = 'duplicate'; item.proof = '';
            const box = item.review; box.replaceChildren(); box.hidden = false;
            const title = document.createElement('strong'); title.textContent = messages.duplicate;
            const help = document.createElement('p'); help.textContent = messages.duplicateHelp;
            const name = document.createElement('p'); name.textContent = value.name + ' · ' + value.location;
            const destinationName = document.createElement('p'); destinationName.className = 'fw-copy-filename';
            destinationName.textContent = messages.importAs + ' ' + (value.import_name || item.file?.name || '');
            const link = document.createElement('a'); link.textContent = messages.viewExisting;
            const url = new URL(value.url,location.href);
            if (url.origin !== location.origin) throw new Error('Invalid review URL');
            link.href = url.href; link.target = '_blank'; link.rel = 'noopener noreferrer';
            box.append(title,help,name,destinationName,link,button(messages.keepCopy, () => { if (!busy) { item.proof = value.proof; item.importName = value.import_name || ''; send([item]); } }));
            item.output.textContent = messages.duplicate;
        };
        const send = async selection => {
            if (busy || !selection.length || !form.reportValidity()) return;
            const fields = [...new FormData(form).entries()].filter(([key]) => !['file','description','duplicate_proof','import_name'].includes(key));
            const controls = [...form.querySelectorAll('input,select,button,textarea')], previous = controls.map(control => control.disabled);
            controls.forEach(control => {control.disabled = true;}); busy = true; form.setAttribute('aria-busy','true');
            try {
                for (const item of selection) {
                    item.output.textContent = messages.sending;
                    const data = new FormData(); fields.forEach(([key,value]) => data.append(key,value));
                    if (item.file) data.set('file',item.file,item.file.name);
                    data.set('description',item.file ? item.description.value : (shared?.querySelector('textarea').value || ''));
                    data.set('duplicate_proof',item.proof || form.querySelector('[name=duplicate_proof]')?.value || ''); data.set('fw_queue','1');
                    data.set('import_name',item.importName || form.querySelector('[name=import_name]')?.value || '');
                    try {
                        const response = await fetch(form.getAttribute('action'),{method:'POST',credentials:'same-origin',body:data,cache:'no-store'});
                        if (!response.ok) throw new Error('Upload refused');
                        if (new URL(response.url,location.href).origin !== location.origin) throw new Error('Unexpected response origin');
                        if ((response.headers.get('content-type') || '').includes('application/json')) {
                            if (response.redirected) throw new Error('Unexpected JSON redirect');
                            const result = await response.json();
                            if (result.duplicate) { review(item,result.duplicate); continue; }
                            if (typeof result.error === 'string') {
                                item.state = 'failed'; item.output.textContent = result.error;
                                globalThis.FileWardenNotices?.show({type:'warning',title:messages.failedTitle,message:result.error});
                                continue;
                            }
                            if (!result.saved) throw new Error('Save not confirmed');
                        } else {
                            const url = new URL(response.url);
                            if (url.origin !== location.origin || url.searchParams.get('page') !== 'filewarden' || url.searchParams.get('result') !== 'success') {
                                const page = new DOMParser().parseFromString(await response.text(),'text/html');
                                throw new Error(page.querySelector('.fw-workspace .fw-notice p')?.textContent || messages.failed);
                            }
                        }
                        item.state = 'saved'; item.review.hidden = true; item.output.textContent = messages.saved;
                        globalThis.FileWardenNotices?.show({type:'success',title:messages.savedTitle,message:messages.saved});
                    } catch {
                        item.state = 'failed'; item.output.textContent = messages.failed;
                        globalThis.FileWardenNotices?.show({type:'error',title:messages.failedTitle,message:messages.failed});
                    }
                }
            } finally {
                controls.forEach((control,index) => {control.disabled = previous[index];});
                busy = false; form.removeAttribute('aria-busy');
                items.filter(item => item.state === 'saved').forEach(item => item.row.querySelectorAll('button,textarea').forEach(control => {control.disabled=true;}));
            }
        };
        form.addEventListener('submit', event => {
            if (source() === 'upload' && !items.length) return;
            if (source() === 'import' && !Number(form.querySelector('[name=media_id]')?.value)) return;
            event.preventDefault(); event.stopImmediatePropagation();
            let selected;
            if (source() === 'upload') selected = items.filter(item => ['ready','failed'].includes(item.state));
            else {
                const id = form.querySelector('[name=media_id]').value;
                if (!mediaItem || mediaItem.id !== id) { mediaItem = {id,state:'ready',proof:''}; list.replaceChildren(); row(mediaItem); }
                selected = ['ready','failed'].includes(mediaItem.state) ? [mediaItem] : [];
            }
            send(selected);
        },true);
        list.tabIndex = -1;
    });
});
