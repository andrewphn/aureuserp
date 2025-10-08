import * as pdfjsLib from 'pdfjs-dist';

// Set worker path
pdfjsLib.GlobalWorkerOptions.workerSrc = '/js/pdf.worker.min.js';

/**
 * Render a PDF page thumbnail using PDF.js
 *
 * @param {string} pdfUrl - URL to the PDF file
 * @param {number} pageNumber - Page number (1-indexed)
 * @param {HTMLCanvasElement} canvas - Canvas element to render into
 * @param {number} width - Desired width in pixels (default: 800)
 * @returns {Promise<void>}
 */
export async function renderPdfThumbnail(pdfUrl, pageNumber, canvas, width = 800) {
    try {
        // Load the PDF document
        const loadingTask = pdfjsLib.getDocument(pdfUrl);
        const pdf = await loadingTask.promise;

        // Get the specific page
        const page = await pdf.getPage(pageNumber);

        // Calculate scale based on desired width
        const viewport = page.getViewport({ scale: 1.0 });
        const scale = width / viewport.width;
        const scaledViewport = page.getViewport({ scale });

        // Set canvas dimensions
        canvas.width = scaledViewport.width;
        canvas.height = scaledViewport.height;

        // Render the page
        const renderContext = {
            canvasContext: canvas.getContext('2d'),
            viewport: scaledViewport
        };

        await page.render(renderContext).promise;

        return true;
    } catch (error) {
        console.error('Error rendering PDF thumbnail:', error);
        return false;
    }
}

/**
 * Initialize all PDF thumbnails on the page
 * Looks for elements with data-pdf-thumbnail attribute
 */
export function initPdfThumbnails() {
    document.querySelectorAll('[data-pdf-thumbnail]').forEach(element => {
        const pdfUrl = element.dataset.pdfUrl;
        const pageNumber = parseInt(element.dataset.pageNumber);
        const width = parseInt(element.dataset.width || '800');

        // Create canvas if it doesn't exist
        let canvas = element.querySelector('canvas');
        if (!canvas) {
            canvas = document.createElement('canvas');
            canvas.className = 'pdf-thumbnail-canvas';
            element.appendChild(canvas);
        }

        // Show loading state
        element.classList.add('loading');

        // Render thumbnail
        renderPdfThumbnail(pdfUrl, pageNumber, canvas, width)
            .then(success => {
                element.classList.remove('loading');
                if (success) {
                    element.classList.add('loaded');
                } else {
                    element.classList.add('error');
                    element.textContent = 'Failed to load preview';
                }
            });
    });
}

// Auto-initialize on DOM ready
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initPdfThumbnails);
} else {
    initPdfThumbnails();
}

// Export for Livewire compatibility
window.initPdfThumbnails = initPdfThumbnails;
