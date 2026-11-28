/* Explicit review and confirmation; one real server-verified DDL unit per request. */
(() => {
    const c = globalThis.filewardenWordPressTables, root = document.querySelector('.fw-wp-conversion');
    if (!c || !root) return;
    const check = root.querySelector('[data-wp-table-check]'), convert = root.querySelector('[data-wp-table-convert]');
    if (!check || !convert) return;
    const status = root.querySelector('[data-wp-table-status]'), backup = root.querySelector('[data-wp-table-backup]'), maintenance = root.querySelector('[data-wp-table-maintenance]'), disk = root.querySelector('[data-wp-table-disk]');
    let review = null, busy = false;
    const allowed = () => convert.disabled = busy || !review || !backup.checked || !maintenance.checked || (!review.disk_measured && !disk.checked);
    [backup,maintenance,disk].forEach(input => input.addEventListener('change',allowed));
    const request = async operation => {
        const body = new FormData(); body.set('action','filewarden_wp_tables'); body.set('_ajax_nonce',c.nonce); body.set('operation',operation);
        if (operation === 'convert') { body.set('proof',review.proof); body.set('reviewed_at',review.reviewed_at); body.set('confirmed',backup.checked && maintenance.checked ? '1' : '0'); body.set('disk_confirmed',disk.checked ? '1' : '0'); }
        const response = await fetch(c.endpoint,{method:'POST',credentials:'same-origin',body}); const payload = await response.json();
        if (!response.ok || !payload.success) throw new Error(payload.data?.message || c.error);
        return payload.data;
    };
    check.addEventListener('click',async()=>{
        if(busy)return; busy=true; check.disabled=true; allowed(); status.textContent=c.working;
        try { review=await request('preflight'); root.querySelector('[data-wp-table-review]').hidden=false; root.querySelector('[data-wp-table-capacity]').textContent=review.disk_measured?c.measured:c.unknown; root.querySelector('[data-wp-table-disk-label]').hidden=review.disk_measured; status.textContent=c.progress.replace('%d',String(review.completed)); }
        catch(error){review=null;status.textContent=error.message||c.error;}
        finally{busy=false;check.disabled=false;allowed();}
    });
    convert.addEventListener('click',async()=>{
        if(convert.disabled)return; busy=true;check.disabled=true;allowed();status.textContent=c.working;
        try{
            let result;
            do{result=await request('convert');status.textContent=c.progress.replace('%d',String(result.completed));}while(!result.done);
            status.textContent=c.done; review=null;root.querySelector('[data-wp-table-review]').hidden=true;root.querySelector('[data-wp-table-recheck]').hidden=false;
        }catch(error){status.textContent=error.message||c.error;}
        finally{busy=false;check.disabled=false;allowed();}
    });
    root.querySelector('[data-wp-table-recheck]').addEventListener('click',()=>setTimeout(()=>document.querySelector('[data-diagnostic-run]')?.click(),0));
})();
