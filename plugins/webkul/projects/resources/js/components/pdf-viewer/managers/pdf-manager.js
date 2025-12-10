/**
 * PDF Manager
 * Handles PDF loading, rendering, and dimension extraction
 * Uses PDFObject.js for display and PDF.js for dimension extraction
 */

import { syncOverlayToCanvas } from './coordinate-transform.js';

/**
 * WeakMap to store PDF documents outside Alpine's reactivity system
 * This prevents PDF.js private field access errors caused by Alpine's Proxy wrapping
 */
const pdfDocuments = new WeakMap();

/**
 * Preload PDF document using PDF.js (for dimension extraction)
 * Stores document in WeakMap to avoid Alpine.js reactivity issues
 * @param {Object} state - Component state
 * @returns {Promise<void>}
 */
export async function preloadPdf(state) {
    console.log('📄 Preloading PDF document...');

    try {
        // PDF.js is loaded via Vite bundle
        if (typeof window.pdfjsLib === 'undefined') {
            throw new Error('PDF.js library not loaded');
        }

        const loadingTask = window.pdfjsLib.getDocument(state.pdfUrl);
        const pdfDocument = await loadingTask.promise;

        // Store in WeakMap instead of state to avoid Alpine.js Proxy wrapping
        // This prevents "Cannot read from private field" errors
        pdfDocuments.set(state, pdfDocument);

        console.log('✓ PDF document preloaded', {
            numPages: pdfDocument.numPages,
            fingerprint: pdfDocument.fingerprint
        });
    } catch (error) {
        console.error('❌ Failed to preload PDF:', error);
        state.error = `Failed to load PDF: ${error.message}`;
        throw error;
    }
}

/**
 * Extract PDF page dimensions using PDF.js
 * Retrieves document from WeakMap to avoid reactivity issues
 * @param {Object} state - Component state
 * @returns {Promise<void>}
 */
export async function extractPdfDimensions(state) {
    console.log(`📐 Extracting dimensions for page ${state.currentPage}...`);

    const pdfDocument = pdfDocuments.get(state);

    if (!pdfDocument) {
        console.error('❌ PDF document not preloaded');
        return;
    }

    try {
        // Get page directly from PDF.js (no longer wrapped in Alpine Proxy)
        const page = await pdfDocument.getPage(state.currentPage);
        const viewport = page.getViewport({ scale: 1.0 });

        // Store natural dimensions (in points)
        state.pageDimensions = {
            width: viewport.width,
            height: viewport.height
        };

        console.log(`✓ Page dimensions: ${viewport.width} × ${viewport.height} pts`);
    } catch (error) {
        console.warn('⚠️ PDF.js dimension extraction failed, using fallback method:', error.message);

        // Fallback - Use estimated standard letter size dimensions
        // This allows the system to work even if PDF.js has other issues
        state.pageDimensions = {
            width: 612,  // 8.5 inches * 72 pts/inch
            height: 792  // 11 inches * 72 pts/inch
        };

        console.log('✓ Using fallback dimensions: 612 × 792 pts (letter size)');

        // Clear error - this is not a critical failure
        state.error = null;
    }
}

/**
 * Display PDF using canvas-based rendering (strictly one page at a time)
 * @param {Object} state - Component state
 * @param {Object} refs - Alpine.js $refs
 * @returns {Promise<void>}
 */
export async function displayPdf(state, refs) {
    console.log(`🖼️ Rendering PDF page ${state.currentPage} to canvas at ${Math.round(state.zoomLevel * 100)}% zoom...`);

    const embedContainer = refs.pdfEmbed;

    // Guard: Ensure container element exists before rendering
    if (!embedContainer) {
        console.warn('⚠️ PDF embed container not found in DOM, skipping render');
        return;
    }

    // Guard: Ensure container has valid dimensions
    if (!embedContainer.clientWidth || embedContainer.clientWidth < 10) {
        console.warn('⚠️ PDF embed container has no width, waiting for layout...');
        // Wait a bit and retry
        await new Promise(resolve => setTimeout(resolve, 100));
        if (!embedContainer.clientWidth || embedContainer.clientWidth < 10) {
            console.error('❌ PDF embed container still has no width after waiting');
            state.error = 'PDF viewer container not ready';
            return;
        }
    }

    try {
        // Get PDF document from WeakMap, or preload if not available
        let pdfDocument = pdfDocuments.get(state);

        if (!pdfDocument) {
            console.log('📄 PDF not in WeakMap, preloading...');
            await preloadPdf(state);
            pdfDocument = pdfDocuments.get(state);

            if (!pdfDocument) {
                throw new Error('PDF document failed to preload');
            }
        }

        // Get the specific page
        const page = await pdfDocument.getPage(state.currentPage);

        // Get unscaled viewport for dimension reference
        const unscaledViewport = page.getViewport({ scale: 1.0 });

        // Calculate base scale to fit container width, then apply zoom
        const containerWidth = embedContainer.clientWidth;
        const baseScale = containerWidth / unscaledViewport.width;
        const scale = baseScale * state.zoomLevel; // Apply zoom multiplier
        const scaledViewport = page.getViewport({ scale });

        // Create canvas with scaled dimensions
        const canvas = document.createElement('canvas');
        const context = canvas.getContext('2d');
        canvas.width = scaledViewport.width;
        canvas.height = scaledViewport.height;

        // At 100% zoom, fit to container width
        // At higher zoom, allow overflow for scrolling
        if (state.zoomLevel === 1.0) {
            canvas.style.width = '100%';
            canvas.style.height = 'auto';
        } else {
            canvas.style.width = `${scaledViewport.width}px`;
            canvas.style.height = `${scaledViewport.height}px`;
        }
        canvas.style.display = 'block';

        // Render PDF page to canvas
        const renderContext = {
            canvasContext: context,
            viewport: scaledViewport
        };

        await page.render(renderContext).promise;

        // Clear container and add canvas
        embedContainer.innerHTML = '';
        embedContainer.appendChild(canvas);

        // Store canvas scale factor for coordinate transformations
        state.canvasScale = scale;

        console.log('✓ PDF page rendered to canvas');
        console.log(`✓ Canvas dimensions: ${canvas.width} × ${canvas.height}`);
        console.log(`✓ Canvas scale factor: ${scale.toFixed(3)}`);

        state.pdfReady = true;

        console.log(`✓ PDF page ${state.currentPage} displayed successfully`);
    } catch (error) {
        console.error('❌ Failed to render PDF:', error);
        state.error = `Failed to render PDF: ${error.message}`;
        throw error;
    }
}

/**
 * Setup page observer for lazy loading annotations
 * @param {Object} state - Component state
 * @param {Object} refs - Alpine.js $refs
 * @param {Function} loadAnnotationsCallback - Callback to load annotations
 */
export function setupPageObserver(state, refs, loadAnnotationsCallback) {
    if (!refs.pdfEmbed) return;

    // Disconnect existing observer
    if (state.pageObserver) {
        state.pageObserver.disconnect();
    }

    // Create intersection observer for PDF pages
    state.pageObserver = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                const pageNum = parseInt(entry.target.dataset.pageNumber);
                if (!state.visiblePages.includes(pageNum)) {
                    state.visiblePages.push(pageNum);
                    console.log('📄 Page visible:', pageNum);

                    // Load annotations for this page
                    if (loadAnnotationsCallback) {
                        loadAnnotationsCallback(pageNum);
                    }
                }
            } else {
                // Remove from visible pages
                const pageNum = parseInt(entry.target.dataset.pageNumber);
                const index = state.visiblePages.indexOf(pageNum);
                if (index > -1) {
                    state.visiblePages.splice(index, 1);
                    console.log('📄 Page hidden:', pageNum);
                }
            }
        });
    }, {
        root: refs.pdfEmbed,
        rootMargin: '100px', // Load annotations 100px before page is visible
        threshold: 0.1
    });

    // Observe all page elements (if PDF viewer exposes them)
    const pages = refs.pdfEmbed.querySelectorAll('.page');
    pages.forEach(page => {
        state.pageObserver.observe(page);
    });
}

/**
 * Initialize PDF viewer system
 * @param {Object} state - Component state
 * @param {Object} refs - Alpine.js $refs
 * @param {Object} callbacks - Callback functions
 * @returns {Promise<void>}
 */
export async function initializePdfSystem(state, refs, callbacks) {
    try {
        console.log('🚀 Initializing PDF viewer system...');

        // Step 1: Preload PDF document (PDF.js)
        await preloadPdf(state);

        // Step 2: Extract page dimensions (PDF.js)
        await extractPdfDimensions(state);

        // Step 3: Display PDF (PDFObject)
        await displayPdf(state, refs);

        // Step 4: Wait for DOM to update
        if (callbacks.$nextTick) {
            await callbacks.$nextTick();
        }
        await new Promise(resolve => setTimeout(resolve, 100));

        // Step 5: Calculate canvas scale
        state.canvasScale = calculateCanvasScale(refs, state);
        console.log(`📐 Canvas scale: ${state.canvasScale.toFixed(3)}`);

        // Step 6: Sync overlay dimensions
        syncOverlayToCanvas(refs, state);

        console.log('✓ PDF system initialized successfully');
    } catch (error) {
        console.error('❌ PDF system initialization failed:', error);
        state.error = error.message;
        throw error;
    }
}

/**
 * Calculate canvas scale (internal helper)
 * @param {Object} refs - Alpine.js $refs
 * @param {Object} state - Component state
 * @returns {Number} Scale factor
 */
function calculateCanvasScale(refs, state) {
    if (!refs.pdfEmbed || !state.pageDimensions) {
        console.warn('⚠️ calculateCanvasScale: Missing refs.pdfEmbed or pageDimensions');
        return 1.0;
    }

    // Try to find the actual PDF canvas or iframe
    const iframe = refs.pdfEmbed.querySelector('iframe');
    const canvas = refs.pdfEmbed.querySelector('canvas');

    let actualWidth;

    if (canvas && canvas.clientWidth > 0) {
        // Canvas element exists with valid width
        actualWidth = canvas.clientWidth;
    } else if (iframe && iframe.clientWidth > 0) {
        // Use iframe dimensions
        actualWidth = iframe.clientWidth;
    } else if (refs.pdfEmbed.clientWidth > 0) {
        // Fallback to container
        actualWidth = refs.pdfEmbed.clientWidth;
    } else {
        // No valid width found - return default scale
        console.warn('⚠️ calculateCanvasScale: No element has valid clientWidth, using default scale');
        return 1.0;
    }

    const naturalWidth = state.pageDimensions.width;

    const scale = actualWidth / naturalWidth;
    console.log(`📐 Scale calculation: actual=${actualWidth}px, natural=${naturalWidth}pts, scale=${scale.toFixed(3)}`);

    return scale;
}

/**
 * Reload PDF at current page
 * @param {Object} state - Component state
 * @param {Object} refs - Alpine.js $refs
 * @returns {Promise<void>}
 */
export async function reloadPdf(state, refs) {
    console.log('🔄 Reloading PDF...');

    try {
        await extractPdfDimensions(state);
        await displayPdf(state, refs);

        // Recalculate scale
        state.canvasScale = calculateCanvasScale(refs, state);
        syncOverlayToCanvas(refs, state);

        console.log('✓ PDF reloaded');
    } catch (error) {
        console.error('❌ PDF reload failed:', error);
        state.error = error.message;
    }
}

/**
 * Navigate to specific page
 * @param {Number} pageNum - Page number (1-indexed)
 * @param {Object} state - Component state
 * @param {Object} refs - Alpine.js $refs
 * @param {Function} loadAnnotationsCallback - Callback to load annotations
 * @returns {Promise<void>}
 */
export async function goToPage(pageNum, state, refs, loadAnnotationsCallback) {
    if (pageNum < 1 || pageNum > state.totalPages) {
        console.warn(`⚠️ Invalid page number: ${pageNum}`);
        return;
    }

    if (state.navigating) {
        console.log('⏸️ Navigation already in progress');
        return;
    }

    state.navigating = true;

    try {
        console.log(`📄 Navigating to page ${pageNum}`);

        // Update current page
        state.currentPage = pageNum;

        // Update pdfPageId from pageMap
        if (state.pageMap[pageNum]) {
            state.pdfPageId = state.pageMap[pageNum];
            console.log(`✓ Updated pdfPageId to ${state.pdfPageId}`);
        }

        // Clear current annotations
        state.annotations = [];

        // Reload PDF at new page
        await extractPdfDimensions(state);
        await displayPdf(state, refs);

        // Recalculate scale and sync overlay
        state.canvasScale = calculateCanvasScale(refs, state);
        syncOverlayToCanvas(refs, state);

        // Load annotations for new page
        if (loadAnnotationsCallback) {
            await loadAnnotationsCallback();
        }

        console.log(`✓ Navigated to page ${pageNum}`);
    } catch (error) {
        console.error('❌ Page navigation failed:', error);
        state.error = error.message;
    } finally {
        state.navigating = false;
    }
}
