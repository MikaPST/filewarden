document.addEventListener('DOMContentLoaded', () => {
	const button = document.getElementById('fw-media-picker');
	if (!button || !window.wp?.media) {
		return;
	}
	const picker = wp.media({
		title: button.textContent,
		library: { type: ['application/pdf', 'image/jpeg', 'image/png'] },
		multiple: false
	});
	picker.on('select', () => {
		const attachment = picker.state().get('selection').first();
		if (attachment) {
			document.getElementById('fw-media-id').value = attachment.id;
			document.getElementById('fw-media-title').textContent = attachment.get('filename');
		}
	});
	button.addEventListener('click', () => picker.open());
});
