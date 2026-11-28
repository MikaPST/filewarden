/* Resumable server-confirmed backup work. Secret phrases never enter storage or URLs. */
(() => {
    'use strict';
    const c = globalThis.filewardenBackup;
    const root = document.querySelector('.fw-backup');
    if (!c || !root) return;
    let job = c.resume;
    let busy = false;
    let failure = '';
    const progress = root.querySelector('.fw-backup-progress');
    const mappings = root.querySelector('.fw-backup-mappings');
    const message = root.querySelector('[data-backup-status]');
    const notify = (message, type = 'error') => globalThis.FileWardenNotices?.show({ type, message });
    const call = async (operation, fields = {}) => {
        const body = fields instanceof FormData ? fields : new FormData();
        if (!(fields instanceof FormData)) Object.entries(fields).forEach(([key, value]) => body.set(key, typeof value === 'object' ? JSON.stringify(value) : value));
        body.set('action', 'filewarden_backup'); body.set('operation', operation); body.set('_ajax_nonce', c.nonce);
        const response = await fetch(c.endpoint, { method: 'POST', credentials: 'same-origin', body });
        const result = await response.json();
        if (!response.ok || !result.success) throw new Error(result.data?.message || c.error);
        return result.data;
    };
    const tab = name => {
        root.querySelectorAll('[data-backup-tab]').forEach(button => button.setAttribute('aria-pressed', String(button.dataset.backupTab === name)));
        root.querySelectorAll('[data-backup-view]').forEach(section => section.hidden = section.dataset.backupView !== name);
    };
    root.querySelectorAll('[data-backup-tab]').forEach(button => button.addEventListener('click', () => tab(button.dataset.backupTab)));
    const mapFields = (node, entries, choices, label, kind) => {
        node.replaceChildren();
        const large = entries.length * Object.keys(choices).length > 100000;
        let suggestions;
        if (large) {
            suggestions = document.createElement('datalist'); suggestions.id = 'fw-backup-choices-' + kind;
            Object.entries(choices).forEach(([id, name]) => { const option = document.createElement('option'); option.value = id; option.label = name; suggestions.append(option); });
            node.append(suggestions);
        }
        entries.forEach(([key, name]) => {
            const field = document.createElement('label'); field.textContent = label + ' : ' + name + ' [' + key + ']';
            const select = document.createElement(large ? 'input' : 'select');
            if (large) select.setAttribute('list', suggestions.id); select.required = true; select.dataset.mapping = kind; select.dataset.source = key;
            const empty = document.createElement('option'); empty.value = ''; empty.textContent = c.choose; if (!large) select.append(empty);
            if (!large) Object.entries(choices).forEach(([id, name]) => { const option = document.createElement('option'); option.value = id; option.textContent = name + ' [' + id + ']'; select.append(option); });
            field.append(select); node.append(field);
        });
    };
    let restoreChoices = null;
    const restoreOptions = () => {
        const included = job.components || Object.fromEntries(Object.keys(c.components).map(key => [key,true]));
        restoreChoices = {permissions:included.permissions,settings:included.settings,appearance:included.appearance,audit:included.audit,creators:'current'};
        const options = mappings.querySelector('[data-backup-restore-options]'); options.replaceChildren();
        const title = document.createElement('h4');title.textContent=c.restoreOptions;options.append(title);
        const roles = mappings.querySelector('[data-backup-role-mappings]'), users = mappings.querySelector('[data-backup-user-mappings]');
        const update = () => {
            roles.hidden=!restoreChoices.permissions;
            const needed = new Set(restoreChoices.audit ? job.roles : (job.content_roles || job.roles));
            roles.querySelectorAll('[data-mapping]').forEach(input=>{
                input.disabled=roles.hidden || !needed.has(input.dataset.source);
                input.closest('label').hidden=input.disabled;
            });
            users.hidden=restoreChoices.creators!=='map';
            users.querySelectorAll('select,input').forEach(input=>input.disabled=users.hidden);
            const note=mappings.querySelector('[data-backup-private-note]');note.hidden=restoreChoices.permissions || job.configuration_only;note.textContent=c.private;
            mappings.elements.acknowledge.checked=false;
        };
        for(const key of ['permissions','settings','appearance','audit']){
            if(!included[key])continue;
            const label=document.createElement('label');label.className='fw-backup-component';
            const checkbox=document.createElement('input');checkbox.type='checkbox';checkbox.checked=true;checkbox.dataset.restoreComponent=key;
            const text=document.createElement('span'),strong=document.createElement('strong'),help=document.createElement('small');strong.textContent=c.components[key][0];help.textContent=c.components[key][1];text.append(strong,help);label.append(checkbox,text);options.append(label);
            checkbox.addEventListener('change',()=>{restoreChoices[key]=checkbox.checked;update();});
        }
        if(included.creators){
            const group=document.createElement('fieldset');group.className='fw-backup-attribution';
            for(const [value,text]of [['current',c.currentCreator],['map',c.mapCreators]]){
                const label=document.createElement('label'),radio=document.createElement('input');radio.type='radio';radio.name='restore_creator_mode';radio.value=value;radio.checked=value==='current';radio.dataset.restoreCreators=value;label.append(radio,document.createTextNode(' '+text));group.append(label);
                radio.addEventListener('change',()=>{if(radio.checked){restoreChoices.creators=value;update();}});
            }
            options.append(group);
        }else if(Object.keys(job.users).length){const help=document.createElement('p');help.textContent=c.currentCreator;options.append(help);}
        update();
    };
    const render = () => {
        progress.hidden = !job;
        mappings.hidden = job?.status !== 'review';
        if (!job) return;
        const done = ['ready', 'review', 'restored'].includes(job.status);
        progress.querySelector('progress').value = done ? 100 : job.total ? job.completed / (job.total + 1) * 100 : 0;
        message.textContent = failure || (done ? c[job.status] : `${c.working} : ${job.completed}/${job.total} · ${job.bytes.toLocaleString()} B`);
        const download = root.querySelector('[data-backup-download]');
        download.hidden = busy || job.status !== 'ready' || !job.download;
        if (job.download) download.href = job.download;
        root.querySelector('[data-backup-cancel]').disabled = busy;
        root.querySelector('[data-backup-resume]').disabled = busy;
        root.querySelectorAll('[data-backup-start] button[type=submit]').forEach(button => button.disabled = busy || ['working', 'review', 'activating'].includes(job.status));
        if (job.status === 'review' && mappings.dataset.job !== job.id) {
            const s = job.summary;
            mappings.querySelector('[data-backup-summary]').textContent = `${s.folders} / ${s.documents} / ${s.trash} / ${s.audit} — ${c.summary}`;
            if (job.configuration_only) mappings.querySelector('[data-backup-summary]').textContent = c.configOnly;
            mapFields(root.querySelector('[data-backup-role-mappings]'), job.roles.map(role => [role, role]), c.roles, c.role, 'roles');
            mapFields(root.querySelector('[data-backup-user-mappings]'), Object.entries(job.users), c.users, c.user, 'users');
            restoreOptions();
            mappings.dataset.job = job.id;
        }
    };
    const run = async () => {
        if (!job || busy) return;
        busy = true; failure = ''; render();
        try {
            // Each request commits one file checkpoint; hidden tabs pause between files.
            do { job = await call('tick', { id: job.id }); render(); }
            while (job.status === 'working' && !document.hidden);
        } catch (error) { failure = error.message || c.error; message.textContent = failure; notify(failure); }
        finally { busy = false; render(); }
    };
    document.addEventListener('visibilitychange', () => { if (!document.hidden && job?.status === 'working') run(); });
    root.querySelector('[data-backup-resume]').addEventListener('click', run);
    root.querySelectorAll('[data-backup-start]').forEach(form => {
        const components = [...form.querySelectorAll('[data-backup-component]')];
        const options = form.querySelector('[data-backup-custom-options]');
        const complete = () => form.querySelector('[name=backup_profile]:checked')?.value === 'complete';
        const updateComponents = () => {
            if (!options) return;
            const byKey = Object.fromEntries(components.map(input => [input.dataset.backupComponent, input]));
            options.hidden = complete();
            form.querySelector('[data-backup-complete-summary]').hidden = !complete();
            const hasDocuments = byKey.documents.checked || byKey.trash.checked;
            if (hasDocuments) byKey.folders.checked = true;
            byKey.folders.disabled = hasDocuments;
            const metadata = byKey.folders.checked || byKey.audit.checked;
            form.querySelector('[data-backup-group=metadata]').hidden = !metadata;
            if (!metadata) { byKey.permissions.checked = false; byKey.creators.checked = false; }
            const labels = components.filter(input => complete() || input.checked).map(input => c.components[input.dataset.backupComponent][0]);
            form.querySelector('[data-backup-contents-summary]').textContent = complete() ? '' : c.contents + ': ' + labels.join(', ');
        };
        form.querySelectorAll('[name=backup_profile]').forEach(preset => preset.addEventListener('change', updateComponents));
        components.forEach(input => input.addEventListener('change', updateComponents));
        updateComponents();
        form.querySelector('[data-backup-show]')?.addEventListener('change', event => form.querySelectorAll('input[name=phrase],input[name=confirmation]').forEach(input => input.type = event.target.checked ? 'text' : 'password'));
        form.querySelector('[data-backup-generate]')?.addEventListener('click', () => {
            const bytes = crypto.getRandomValues(new Uint8Array(24));
            const phrase = [...bytes].map(byte => byte.toString(16).padStart(2, '0')).join('');
            form.elements.phrase.value = phrase; form.elements.confirmation.value = phrase;
        });
        form.addEventListener('submit', async event => {
            event.preventDefault();
            if (busy) return;
            if (form.dataset.backupStart === 'backup' && form.elements.phrase.value !== form.elements.confirmation.value) { notify(c.phraseError); return; }
            busy = true; form.querySelector('button[type=submit]').disabled = true; render();
            try {
                const body=new FormData(form);
                if (form.dataset.backupStart === 'backup') {
                    const flags = Object.fromEntries(components.map(input => [input.dataset.backupComponent, complete() || input.checked]));
                    if (!['documents','folders','trash','settings','appearance','audit'].some(key => flags[key])) throw new Error(c.emptySelection);
                    body.set('components', JSON.stringify(flags));
                }
                job = await call(form.dataset.backupStart, body);
                form.querySelectorAll('input[type=password],input[name=phrase],input[name=confirmation]').forEach(input => input.value = '');
                render(); busy = false; await run();
            } catch (error) { notify(error.message || c.error); }
            finally { busy = false; form.querySelector('button[type=submit]').disabled = false; render(); }
        });
    });
    mappings.addEventListener('submit', async event => {
        event.preventDefault(); if (busy || !job) return;
        const fields = { id: job.id, acknowledge: '1', roles: {}, users: {}, choices: restoreChoices };
        mappings.querySelectorAll('[data-mapping]:not(:disabled)').forEach(select => fields[select.dataset.mapping][select.dataset.source] = select.value);
        busy = true; mappings.querySelector('button').disabled = true; render();
        try { job = await call('apply', fields); render(); notify(c.restored, 'success'); }
        catch (error) { notify(error.message || c.error); }
        finally { busy = false; mappings.querySelector('button').disabled = false; render(); }
    });
    root.querySelector('[data-backup-cancel]').addEventListener('click', async () => {
        if (!job || busy) return;
        busy = true;
        try { await call('cancel', { id: job.id }); job = null; mappings.dataset.job = ''; root.querySelectorAll('[data-backup-start] button[type=submit]').forEach(button => button.disabled = false); render(); notify(c.cancelled, 'info'); }
        catch (error) { notify(error.message || c.error); }
        finally { busy = false; }
    });
    render();
    if (job) { tab(job.type); run(); }
})();
