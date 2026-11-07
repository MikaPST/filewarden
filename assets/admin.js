document.addEventListener('DOMContentLoaded', () => {
	document.querySelectorAll('form[data-filewarden-upload]').forEach((form) => {
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
			xhr.open('POST', form.action);
			xhr.upload.addEventListener('progress', (e) => {
				if (e.lengthComputable) progress.value = Math.round(e.loaded / e.total * 100);
			});
			xhr.upload.addEventListener('load', () => {
				progress.removeAttribute('value');
				status.textContent = filewardenUpload.verifying;
			});
			xhr.addEventListener('load', () => {
				const url = new URL(xhr.responseURL || form.action);
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
