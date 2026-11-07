const root = document.getElementById('fw-viewer');
const stage = document.getElementById('fw-stage');
const canvas = document.getElementById('fw-canvas');
const image = document.getElementById('fw-image');
const status = document.getElementById('fw-status');
const zoom = document.getElementById('fw-zoom');
const pageInput = document.getElementById('fw-page');
const previous = document.getElementById('fw-previous');
const next = document.getElementById('fw-next');
let pdf, pageNumber = 1, generation = 0, rendering, loadingTask;

function fail() {
    status.textContent = root.dataset.error;
    canvas.hidden = true;
    image.hidden = true;
}

async function render() {
    const current = ++generation;
    status.textContent = root.dataset.loading;
    try {
        if (rendering) {
            rendering.cancel();
            await rendering.promise.catch(() => {});
        }
        if (current !== generation) return;
        const page = await pdf.getPage(pageNumber);
        if (current !== generation) return;
        const natural = page.getViewport({ scale: 1 });
        const scale = zoom.value === 'fit' ? Math.max(0.1, (stage.clientWidth - 40) / natural.width) : Number(zoom.value);
        const viewport = page.getViewport({ scale });
        // Bound canvas memory even for unusually large pages or high-DPI screens.
        const ratio = Math.min(window.devicePixelRatio || 1, 2, Math.sqrt(16000000 / (viewport.width * viewport.height)));
        canvas.width = Math.max(1, Math.floor(viewport.width * ratio));
        canvas.height = Math.max(1, Math.floor(viewport.height * ratio));
        canvas.style.width = `${viewport.width}px`;
        canvas.style.height = `${viewport.height}px`;
        canvas.hidden = false;
        canvas.setAttribute('aria-label', `${root.dataset.pageLabel} ${pageNumber} / ${pdf.numPages}`);
        rendering = page.render({ canvasContext: canvas.getContext('2d'), viewport, transform: [ratio, 0, 0, ratio, 0, 0] });
        await rendering.promise;
        if (current !== generation) return;
        rendering = null;
        pageInput.value = pageNumber;
        previous.disabled = pageNumber <= 1;
        next.disabled = pageNumber >= pdf.numPages;
        status.textContent = `${root.dataset.pageLabel} ${pageNumber} / ${pdf.numPages}`;
    } catch (error) {
        if (current === generation && error.name !== 'RenderingCancelledException') fail();
    }
}

function resizeImage() {
    const width = zoom.value === 'fit' ? Math.min(image.naturalWidth, Math.max(1, stage.clientWidth - 40)) : image.naturalWidth * Number(zoom.value);
    image.style.width = `${width}px`;
    image.style.height = 'auto';
}

try {
    if (root.dataset.kind === 'pdf') {
        const library = await import('./pdfjs/pdf.min.mjs');
        library.GlobalWorkerOptions.workerSrc = new URL('./pdfjs/pdf.worker.min.mjs', import.meta.url).href;
        loadingTask = library.getDocument({
            url: root.dataset.url, withCredentials: true,
            isEvalSupported: false, enableXfa: false, disableFontFace: true,
            cMapUrl: new URL('./pdfjs/cmaps/', import.meta.url).href, cMapPacked: true,
            standardFontDataUrl: new URL('./pdfjs/standard_fonts/', import.meta.url).href,
            wasmUrl: new URL('./pdfjs/wasm/', import.meta.url).href,
            // A single authenticated transfer avoids repeated decryption for range fetches.
            disableRange: true, disableStream: true
        });
        pdf = await loadingTask.promise;
        document.getElementById('fw-total').textContent = pdf.numPages;
        pageInput.max = pdf.numPages;
        pageInput.disabled = false;
        zoom.disabled = false;
        previous.addEventListener('click', () => { pageNumber = Math.max(1, pageNumber - 1); render(); });
        next.addEventListener('click', () => { pageNumber = Math.min(pdf.numPages, pageNumber + 1); render(); });
        pageInput.addEventListener('change', () => {
            const value = Number(pageInput.value);
            pageNumber = Number.isInteger(value) ? Math.min(pdf.numPages, Math.max(1, value)) : pageNumber;
            render();
        });
        zoom.addEventListener('change', render);
        await render();
    } else if (root.dataset.kind === 'image') {
        image.addEventListener('error', fail);
        image.addEventListener('load', () => {
            image.hidden = false;
            zoom.disabled = false;
            status.textContent = '';
            resizeImage();
        });
        image.src = root.dataset.url;
        zoom.addEventListener('change', resizeImage);
    } else {
        fail();
    }
} catch { fail(); }
let resizeTimer;
window.addEventListener('resize', () => {
    clearTimeout(resizeTimer);
    resizeTimer = setTimeout(() => {
        if (zoom.value !== 'fit') return;
        if (pdf) render();
        else if (image.naturalWidth) resizeImage();
    }, 150);
});
window.addEventListener('pagehide', () => { loadingTask?.destroy(); });
window.addEventListener('keydown', (event) => {
    if (event.key === 'Escape' && window.parent !== window) window.parent.postMessage('filewarden:close-preview', location.origin);
});
