/**
 * Canvas Renderer
 * Handles PDF.js rendering, zoom, rotation, and view management
 */

export function createCanvasRenderer() {
    return {
        /**
         * Render the PDF page onto both PDF and annotation canvases
         * @param {Object} pdfPageCache - Cached PDF.js page object
         * @param {HTMLCanvasElement} pdfCanvas - Canvas for PDF rendering
         * @param {HTMLCanvasElement} annotationCanvas - Canvas for annotations
         * @param {number} baseScale - Base scale for responsive sizing
         * @param {number} zoomLevel - Current zoom level (1.0 = 100%)
         * @param {number} rotation - Rotation in degrees (0, 90, 180, 270)
         */
        async renderCanvas(pdfPageCache, pdfCanvas, annotationCanvas, baseScale, zoomLevel, rotation) {
            if (!pdfPageCache) return;

            const currentScale = baseScale * zoomLevel;
            const scaledViewport = pdfPageCache.getViewport({
                scale: currentScale,
                rotation: rotation
            });

            // Set PDF canvas dimensions
            pdfCanvas.width = scaledViewport.width;
            pdfCanvas.height = scaledViewport.height;

            // Set annotation canvas dimensions (same as PDF canvas)
            annotationCanvas.width = scaledViewport.width;
            annotationCanvas.height = scaledViewport.height;

            // Render the PDF page
            const renderContext = {
                canvasContext: pdfCanvas.getContext('2d'),
                viewport: scaledViewport
            };

            await pdfPageCache.render(renderContext).promise;
        },

        /**
         * Calculate zoom level to fit entire page in container
         * @param {Object} pdfPageCache - Cached PDF.js page object
         * @param {HTMLElement} container - Container element
         * @param {number} baseScale - Base scale
         * @param {number} rotation - Current rotation
         * @returns {number} Optimal zoom level
         */
        calculateFitToPage(pdfPageCache, container, baseScale, rotation) {
            if (!pdfPageCache) return 1.0;

            const viewport = pdfPageCache.getViewport({ scale: 1.0, rotation });
            const containerHeight = container.clientHeight - 100; // padding
            const containerWidth = container.clientWidth - 100;

            // Calculate scale to fit both dimensions
            const scaleX = containerWidth / viewport.width;
            const scaleY = containerHeight / viewport.height;
            const optimalScale = Math.min(scaleX, scaleY);

            return optimalScale / baseScale;
        },

        /**
         * Calculate zoom level to fit page width in container
         * @param {Object} pdfPageCache - Cached PDF.js page object
         * @param {HTMLElement} container - Container element
         * @param {number} baseScale - Base scale
         * @param {number} rotation - Current rotation
         * @returns {number} Optimal zoom level
         */
        calculateFitToWidth(pdfPageCache, container, baseScale, rotation) {
            if (!pdfPageCache) return 1.0;

            const viewport = pdfPageCache.getViewport({ scale: 1.0, rotation });
            const containerWidth = container.clientWidth - 100; // padding

            const optimalScale = containerWidth / viewport.width;
            return optimalScale / baseScale;
        },

        /**
         * Calculate zoom level to fit page height in container
         * @param {Object} pdfPageCache - Cached PDF.js page object
         * @param {HTMLElement} container - Container element
         * @param {number} baseScale - Base scale
         * @param {number} rotation - Current rotation
         * @returns {number} Optimal zoom level
         */
        calculateFitToHeight(pdfPageCache, container, baseScale, rotation) {
            if (!pdfPageCache) return 1.0;

            const viewport = pdfPageCache.getViewport({ scale: 1.0, rotation });
            const containerHeight = container.clientHeight - 100; // padding

            const optimalScale = containerHeight / viewport.height;
            return optimalScale / baseScale;
        },

        /**
         * Increment zoom level
         * @param {number} currentZoom - Current zoom level
         * @param {number} step - Zoom step (default 0.25)
         * @param {number} max - Maximum zoom (default 3.0)
         * @returns {number} New zoom level
         */
        zoomIn(currentZoom, step = 0.25, max = 3.0) {
            return Math.min(currentZoom + step, max);
        },

        /**
         * Decrement zoom level
         * @param {number} currentZoom - Current zoom level
         * @param {number} step - Zoom step (default 0.25)
         * @param {number} min - Minimum zoom (default 0.5)
         * @returns {number} New zoom level
         */
        zoomOut(currentZoom, step = 0.25, min = 0.5) {
            return Math.max(currentZoom - step, min);
        },

        /**
         * Reset zoom to 100%
         * @returns {number} Zoom level of 1.0
         */
        resetZoom() {
            return 1.0;
        },

        /**
         * Rotate clockwise by 90 degrees
         * @param {number} currentRotation - Current rotation in degrees
         * @returns {number} New rotation in degrees
         */
        rotateClockwise(currentRotation) {
            return (currentRotation + 90) % 360;
        },

        /**
         * Rotate counter-clockwise by 90 degrees
         * @param {number} currentRotation - Current rotation in degrees
         * @returns {number} New rotation in degrees
         */
        rotateCounterClockwise(currentRotation) {
            return (currentRotation - 90 + 360) % 360;
        },

        /**
         * Reset view to defaults
         * @returns {Object} Default view state
         */
        resetView() {
            return {
                zoomLevel: 1.0,
                rotation: 0
            };
        },

        /**
         * Save current view state
         * @param {number} zoomLevel - Current zoom level
         * @param {number} rotation - Current rotation
         * @param {number} pageNum - Current page number
         * @returns {Object} Saved view state
         */
        saveView(zoomLevel, rotation, pageNum) {
            return {
                zoomLevel,
                rotation,
                pageNum
            };
        },

        /**
         * Load PDF document and page for annotation viewer
         * @param {Object} pdfjsLib - PDF.js library
         * @param {string} pdfUrl - URL to PDF
         * @param {number} pageNum - Page number to load
         * @returns {Promise<Object>} { pdfDocument, pdfPage, totalPages }
         */
        async loadPdfPage(pdfjsLib, pdfUrl, pageNum) {
            const loadingTask = pdfjsLib.getDocument(pdfUrl);
            const pdfDocument = await loadingTask.promise;
            const totalPages = pdfDocument.numPages;
            const pdfPage = await pdfDocument.getPage(pageNum);

            return {
                pdfDocument,
                pdfPage,
                totalPages
            };
        },

        /**
         * Calculate base scale for responsive canvas
         * @param {Object} viewport - PDF.js viewport
         * @param {number} metadataPanelWidth - Width of metadata panel in pixels
         * @param {number} padding - Additional padding in pixels
         * @returns {number} Base scale for responsive sizing
         */
        calculateBaseScale(viewport, metadataPanelWidth = 384, padding = 100) {
            const maxCanvasWidth = window.innerWidth - metadataPanelWidth - padding;
            return maxCanvasWidth / viewport.width;
        }
    };
}
