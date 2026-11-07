document.addEventListener('DOMContentLoaded', () => {
	document.querySelectorAll('form[data-filewarden-upload]').forEach((form) => {
		// A named input "action" shadows form.action; read the HTML attribute instead.
		const endpoint = new URL(form.getAttribute('action'), document.baseURI).href;
		const source = () => form.querySelector('input[name="source"]:checked')?.value || 'upload';
		const file = form.querySelector('input[type="file"]');
		const syncSource = () => {
			form.querySelectorAll('[data-filewarden-source]').forEach((panel) => {
				panel.hidden = panel.dataset.filewardenSource !== source();
				panel.disabled = panel.hidden;
			});
			if (file) file.required = source() === 'upload';
		};
		form.querySelectorAll('input[name="source"]').forEach((radio) => radio.addEventListener('change', syncSource));
		syncSource();
		const status = document.createElement('p');
		status.setAttribute('role', 'status');
		status.setAttribute('aria-live', 'polite');
		const progress = document.createElement('progress');
		progress.max = 100;
		progress.hidden = true;
		form.append(progress, status);
		let busy = false;
		form.addEventListener('submit', (event) => {
			event.preventDefault();
			if (busy) return;
			if (source() === 'import' && !(Number(form.querySelector('#fw-media-id')?.value) > 0)) {
				status.textContent = filewardenUpload.media;
				return;
			}
			const data = new FormData(form);
			const controls = [...form.querySelectorAll('input, select, button')];
			const previous = controls.map((control) => control.disabled);
			busy = true;
			controls.forEach((control) => { control.disabled = true; });
			form.setAttribute('aria-busy', 'true');
			progress.hidden = false;
			progress.value = 0;
			status.textContent = filewardenUpload.sending;
			const xhr = new XMLHttpRequest();
			const finish = (message) => {
				busy = false;
				controls.forEach((control, index) => { control.disabled = previous[index]; });
				form.removeAttribute('aria-busy');
				progress.hidden = true;
				status.textContent = message;
			};
			xhr.open('POST', endpoint);
			xhr.upload.addEventListener('progress', (e) => {
				if (e.lengthComputable) progress.value = Math.round(e.loaded / e.total * 100);
			});
			xhr.upload.addEventListener('load', () => {
				progress.removeAttribute('value');
				status.textContent = filewardenUpload.verifying;
			});
			xhr.addEventListener('load', () => {
				const url = new URL(xhr.responseURL || endpoint);
				if (xhr.status === 200 && url.origin === location.origin &&
					url.searchParams.get('page') === 'filewarden' && url.searchParams.has('result')) {
					location.assign(url.href);
					return;
				}
				finish(`${filewardenUpload.failed} ${xhr.status}`);
			});
			xhr.addEventListener('error', () => finish(filewardenUpload.network));
			xhr.addEventListener('abort', () => finish(filewardenUpload.network));
			xhr.send(data);
		});
	});
});
