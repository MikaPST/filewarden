/* Checkbox drafts apply only when the existing GET form is submitted. */
(() => {
    const form = document.getElementById('fw-audit-filters');
    if (!form) return;
    const pickers = [...form.querySelectorAll('[data-audit-picker]')];
    for (const picker of pickers) {
        const inputs = [...picker.querySelectorAll('input[type=checkbox]')];
        const update = () => {
            const count = inputs.filter(input => input.checked).length;
            picker.querySelector('summary span').textContent = count === inputs.length ? picker.dataset.all : picker.dataset.count.replace('%d', String(count));
        };
        inputs.forEach(input => input.addEventListener('change', update));
        picker.addEventListener('toggle', () => {
            if (picker.open) pickers.filter(other => other !== picker).forEach(other => other.open = false);
        });
        picker.addEventListener('keydown', event => {
            if (event.key === 'Escape' && picker.open) { picker.open = false; picker.querySelector('summary').focus(); }
        });
        update();
    }
})();
