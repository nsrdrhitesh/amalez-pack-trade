import Alpine from 'alpinejs';
import 'trix';

Alpine.data('contentBlocks', (initial = []) => ({
    sections: initial.map((section, index) => ({ ...section, key: crypto.randomUUID(), enabled: !!Number(section.enabled ?? 1), sort_order: section.sort_order ?? index })),
    add() { this.sections.push({ key: crypto.randomUUID(), type: 'text', title: '', body: '', media_id: '', enabled: true, sort_order: this.sections.length }); },
}));
window.Alpine = Alpine;
Alpine.start();

document.addEventListener('submit', event => {
    const form = event.target;
    if (Number(form.dataset.pendingUploads || 0) > 0) {
        event.preventDefault(); alert('Please wait for image uploads to finish.'); return;
    }
    if (form.dataset.confirm && !confirm(form.dataset.confirm)) { event.preventDefault(); return; }
    if (form.dataset.submitting === 'true') { event.preventDefault(); return; }
    form.dataset.submitting = 'true';
    const submit = event.submitter;
    if (submit) { submit.dataset.originalLabel = submit.textContent; submit.disabled = true; submit.textContent = 'Working…'; }
});
window.addEventListener('pageshow', () => { for (const form of document.querySelectorAll('form')) { form.dataset.submitting = 'false'; for (const button of form.querySelectorAll('[data-original-label]')) { button.disabled = false; button.textContent = button.dataset.originalLabel; } } });
document.addEventListener('input', event => {
    const target = event.target;
    if (target.dataset.filterSelect) for (const option of document.getElementById(target.dataset.filterSelect).options) option.hidden = !!option.value && !option.text.toLowerCase().includes(target.value.toLowerCase());
    if (target.dataset.filterList) for (const label of document.getElementById(target.dataset.filterList).querySelectorAll('label')) label.hidden = !label.textContent.toLowerCase().includes(target.value.toLowerCase());
});
document.addEventListener('click', async event => {
    const button = event.target.closest('[data-create-term]');
    if (!button) return;
    const name = prompt('New term name'); if (!name?.trim()) return;
    const status = button.parentElement.querySelector('.cms-term-status'); button.disabled = true;
    try {
        const response = await fetch(button.dataset.createTerm, { method: 'POST', headers: { 'Content-Type': 'application/json', Accept: 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content }, body: JSON.stringify({ name: name.trim() }) });
        const data = await response.json(); if (!response.ok) throw new Error(Object.values(data.errors || {}).flat()[0] || 'Could not create the term.');
        const label = document.createElement('label'); label.className = 'cms-check';
        const input = document.createElement('input'); input.type = 'checkbox'; input.name = button.dataset.termName + '[]'; input.value = data.id; input.checked = true;
        label.append(input, document.createTextNode(' ' + data.name)); document.getElementById(button.dataset.termList).append(label);
        status.textContent = 'Term created and selected.';
    } catch (error) { status.textContent = error.message; } finally { button.disabled = false; }
});
document.addEventListener('trix-file-accept', event => {
    if (!['image/jpeg', 'image/png', 'image/webp'].includes(event.file.type) || event.file.size > 5 * 1024 * 1024) { event.preventDefault(); alert('Choose a JPEG, PNG or WebP image up to 5 MB.'); }
});
document.addEventListener('trix-attachment-add', async event => {
    if (!event.attachment.file) return;
    const editor = event.target, form = editor.closest('form'), status = editor.parentElement.querySelector('.cms-editor-status');
    const alt = prompt('Describe this image for its ALT text');
    if (!alt?.trim()) { event.attachment.remove(); status.textContent = 'Image cancelled: ALT text is required.'; return; }
    const data = new FormData(); data.set('image', event.attachment.file); data.set('alt', alt.trim());
    form.dataset.pendingUploads = Number(form.dataset.pendingUploads || 0) + 1; status.textContent = 'Uploading image…';
    try {
        const response = await fetch(editor.dataset.uploadUrl, { method: 'POST', body: data, headers: { Accept: 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content } });
        const image = await response.json(); if (!response.ok) throw new Error(Object.values(image.errors || {}).flat()[0] || 'Upload failed.');
        event.attachment.setAttributes({ url: image.url, href: image.url, caption: image.alt }); status.textContent = 'Image uploaded.';
    } catch (error) { event.attachment.remove(); status.textContent = error.message; } finally { form.dataset.pendingUploads = Math.max(0, Number(form.dataset.pendingUploads) - 1); }
});
document.addEventListener('click', event => {
    const button = event.target.closest('[data-insert-image]'); if (!button) return;
    const select = document.getElementById(button.dataset.insertImage + '-library'), option = select.selectedOptions[0]; if (!option?.value) return;
    const image = document.createElement('img'); image.src = option.value; image.alt = option.dataset.alt;
    document.getElementById(button.dataset.insertImage + '-surface').editor.insertHTML(image.outerHTML);
});
