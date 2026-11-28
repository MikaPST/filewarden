/* Only a server-confirmed, settings-authorized report is displayed or downloaded. */
(() => {
    'use strict';
    const c = globalThis.filewardenDiagnostic;
    const root = document.querySelector('.fw-diagnostic');
    if (!c || !root) return;
    const run = root.querySelector('[data-diagnostic-run]');
    const download = root.querySelector('[data-diagnostic-download]');
    const status = root.querySelector('[data-diagnostic-status]');
    const output = root.querySelector('[data-diagnostic-checks]');
    const details = root.querySelector('.fw-diagnostic-report');
    let report = null;
    run.disabled = false;
    const element = (tag, className, text) => {
        const node = document.createElement(tag);
        if (className) node.className = className;
        if (text !== undefined) node.textContent = text;
        return node;
    };
    run.addEventListener('click', async () => {
        if (run.disabled) return;
        run.disabled = true; download.disabled = true; report = null;
        output.replaceChildren(); details.hidden = true; status.className = ''; status.textContent = c.working;
        try {
            const body = new FormData();
            body.set('action', 'filewarden_diagnostic'); body.set('_ajax_nonce', c.nonce);
            const response = await fetch(c.endpoint, { method: 'POST', credentials: 'same-origin', body });
            const payload = await response.json();
            if (!response.ok || !payload.success || !Array.isArray(payload.data?.checks)) throw new Error(c.failed);
            report = payload.data;
            const worst = report.checks.some(row => row.state === 'blocked') ? 'blocked' : report.checks.some(row => row.state !== 'good') ? 'unknown' : 'good';
            status.className = 'fw-diagnostic-summary fw-diagnostic-' + worst;
            status.textContent = c[worst] + ' (' + report.checks.length + ')';
            for (const [id, title] of Object.entries(c.groups)) {
                const checks = report.checks.filter(row => row.group === id);
                if (!checks.length) continue;
                const group = element('details', 'fw-diagnostic-group');
                const state = checks.some(row => row.state === 'blocked') ? 'blocked' : checks.some(row => row.state !== 'good') ? 'unknown' : 'good';
                group.open = state !== 'good';
                const summary = element('summary');
                summary.append(element('span', '', title), element('span', 'fw-diagnostic-badge fw-diagnostic-' + state, c.states[state]));
                group.append(summary);
                for (const row of checks) {
                    const item = element('div', 'fw-diagnostic-check');
                    const rowState = ['good', 'blocked', 'unknown'].includes(row.state) ? row.state : 'unknown';
                    const heading = element('div');
                    heading.append(element('strong', '', row.label), element('span', 'fw-diagnostic-badge fw-diagnostic-' + rowState, c.states[rowState]));
                    item.append(heading, element('p', '', row.message));
                    if (row.reference) item.append(element('code', '', row.reference));
                    if (['wordpress_tables','plugin_tables'].includes(row.action)) {
                        const link = element('a','button',row.action === 'wordpress_tables' ? c.convert : c.storage);
                        const destination = new URL(c.storageURL, location.href);
                        destination.searchParams.set('repair',row.action === 'wordpress_tables' ? 'wordpress' : 'plugin');
                        link.href = destination.href; link.dataset.fwOpenStorage = 'true';
                        link.dataset.fwRepairTarget = row.action === 'wordpress_tables' ? 'fw-wp-table-conversion' : 'fw-database-repair-section';
                        item.append(link);
                    }
                    group.append(item);
                }
                output.append(group);
            }
            details.querySelector('pre').textContent = JSON.stringify(report, null, 2);
            details.hidden = false; download.disabled = false;
        } catch (_) {
            status.className = 'fw-diagnostic-summary fw-diagnostic-unknown'; status.textContent = c.failed;
            globalThis.FileWardenNotices?.show({ type: 'error', message: c.failed });
        } finally { run.disabled = false; }
    });
    download.addEventListener('click', () => {
        if (!report || download.disabled) return;
        const url = URL.createObjectURL(new Blob([JSON.stringify(report, null, 2)], { type: 'application/json' }));
        const anchor = document.createElement('a'); anchor.href = url; anchor.download = 'filewarden-diagnostic.json';
        document.body.append(anchor); anchor.click(); anchor.remove();
        setTimeout(() => URL.revokeObjectURL(url), 1000);
        globalThis.FileWardenNotices?.show({ type: 'info', message: c.downloaded });
    });
})();
