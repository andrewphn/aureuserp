/**
 * PDF Viewer Module
 *
 * Provides utility functions for working with Nutrient PDF SDK
 * and managing PDF document interactions.
 */

import PSPDFKit from '@nutrient-sdk/viewer';

/**
 * Initialize Nutrient SDK with default configuration
 *
 * @param {Object} config - Configuration object
 * @param {string} config.container - DOM selector for container element
 * @param {string} config.document - URL to PDF document
 * @param {string} config.licenseKey - Nutrient license key
 * @param {Array} config.toolbarItems - Toolbar configuration
 * @param {boolean} config.enableAnnotations - Enable annotation features
 * @returns {Promise<PSPDFKit.Instance>}
 */
export async function loadPdfViewer(config) {
    const defaultConfig = {
        baseUrl: '/vendor/nutrient/',
        styleSheets: ['/vendor/nutrient/styles.css'],
        enableAnnotations: true,
        autoSaveMode: PSPDFKit.AutoSaveMode.INTELLIGENT,
        ...config
    };

    try {
        const instance = await PSPDFKit.load(defaultConfig);
        console.log('✅ Nutrient PDF viewer loaded successfully');
        return instance;
    } catch (error) {
        console.error('❌ Failed to load Nutrient PDF viewer:', error);
        throw error;
    }
}

/**
 * Export annotations from PDF instance
 *
 * @param {PSPDFKit.Instance} instance - Nutrient instance
 * @returns {Promise<Object>} InstantJSON format
 */
export async function exportAnnotations(instance) {
    if (!instance) {
        throw new Error('No PDF viewer instance provided');
    }

    try {
        const instantJSON = await instance.exportInstantJSON();
        return instantJSON;
    } catch (error) {
        console.error('Failed to export annotations:', error);
        throw error;
    }
}

/**
 * Import annotations into PDF instance
 *
 * @param {PSPDFKit.Instance} instance - Nutrient instance
 * @param {Object} instantJSON - InstantJSON format annotations
 * @returns {Promise<void>}
 */
export async function importAnnotations(instance, instantJSON) {
    if (!instance) {
        throw new Error('No PDF viewer instance provided');
    }

    try {
        await instance.importInstantJSON(instantJSON);
        console.log('✅ Annotations imported successfully');
    } catch (error) {
        console.error('Failed to import annotations:', error);
        throw error;
    }
}

/**
 * Save annotations to backend API
 *
 * @param {number} documentId - Document ID
 * @param {PSPDFKit.Instance} instance - Nutrient instance
 * @param {string} csrfToken - CSRF token for POST request
 * @returns {Promise<Object>} API response
 */
export async function saveAnnotationsToBackend(documentId, instance, csrfToken) {
    const instantJSON = await exportAnnotations(instance);

    const response = await fetch(`/api/pdf/${documentId}/annotations`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': csrfToken,
            'Accept': 'application/json'
        },
        body: JSON.stringify({
            annotations: instantJSON.annotations || []
        })
    });

    if (!response.ok) {
        const errorData = await response.json();
        throw new Error(errorData.message || 'Failed to save annotations');
    }

    return await response.json();
}

/**
 * Load annotations from backend API
 *
 * @param {number} documentId - Document ID
 * @param {PSPDFKit.Instance} instance - Nutrient instance
 * @returns {Promise<void>}
 */
export async function loadAnnotationsFromBackend(documentId, instance) {
    const response = await fetch(`/api/pdf/${documentId}/annotations`);

    if (!response.ok) {
        throw new Error('Failed to load annotations');
    }

    const data = await response.json();

    if (data.annotations && data.annotations.length > 0) {
        const instantJSON = {
            format: 'https://pspdfkit.com/instant-json/v1',
            annotations: data.annotations.map(a => a.annotation_data)
        };

        await importAnnotations(instance, instantJSON);
    }
}

/**
 * Setup autosave for annotations
 *
 * @param {PSPDFKit.Instance} instance - Nutrient instance
 * @param {number} documentId - Document ID
 * @param {Function} onSave - Callback when save occurs
 * @param {number} debounceMs - Debounce delay in milliseconds
 * @returns {Function} Cleanup function
 */
export function setupAnnotationAutosave(instance, documentId, onSave, debounceMs = 2000) {
    let saveTimeout = null;
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content || '';

    const debouncedSave = () => {
        if (saveTimeout) {
            clearTimeout(saveTimeout);
        }

        saveTimeout = setTimeout(async () => {
            try {
                await saveAnnotationsToBackend(documentId, instance, csrfToken);
                if (onSave) onSave(null);
            } catch (error) {
                console.error('Autosave failed:', error);
                if (onSave) onSave(error);
            }
        }, debounceMs);
    };

    // Listen to annotation events
    const handlers = {
        create: () => debouncedSave(),
        update: () => debouncedSave(),
        delete: () => debouncedSave()
    };

    instance.addEventListener('annotations.create', handlers.create);
    instance.addEventListener('annotations.update', handlers.update);
    instance.addEventListener('annotations.delete', handlers.delete);

    // Return cleanup function
    return () => {
        instance.removeEventListener('annotations.create', handlers.create);
        instance.removeEventListener('annotations.update', handlers.update);
        instance.removeEventListener('annotations.delete', handlers.delete);
        if (saveTimeout) clearTimeout(saveTimeout);
    };
}

/**
 * Get annotation count from instance
 *
 * @param {PSPDFKit.Instance} instance - Nutrient instance
 * @param {number} pageIndex - Page index (optional, counts all if not provided)
 * @returns {Promise<number>}
 */
export async function getAnnotationCount(instance, pageIndex = null) {
    if (!instance) return 0;

    try {
        if (pageIndex !== null) {
            const annotations = await instance.getAnnotations(pageIndex);
            return annotations.size;
        } else {
            // Count all annotations across all pages
            let totalCount = 0;
            const pageCount = instance.totalPageCount;

            for (let i = 0; i < pageCount; i++) {
                const annotations = await instance.getAnnotations(i);
                totalCount += annotations.size;
            }

            return totalCount;
        }
    } catch (error) {
        console.error('Failed to get annotation count:', error);
        return 0;
    }
}

/**
 * Navigate to specific page
 *
 * @param {PSPDFKit.Instance} instance - Nutrient instance
 * @param {number} pageIndex - Zero-based page index
 * @returns {Promise<void>}
 */
export async function goToPage(instance, pageIndex) {
    if (!instance) {
        throw new Error('No PDF viewer instance provided');
    }

    try {
        await instance.setViewState(viewState =>
            viewState.set('currentPageIndex', pageIndex)
        );
    } catch (error) {
        console.error('Failed to navigate to page:', error);
        throw error;
    }
}

/**
 * Download PDF with annotations
 *
 * @param {PSPDFKit.Instance} instance - Nutrient instance
 * @param {string} filename - Download filename
 * @returns {Promise<void>}
 */
export async function downloadPdfWithAnnotations(instance, filename = 'document.pdf') {
    if (!instance) {
        throw new Error('No PDF viewer instance provided');
    }

    try {
        const buffer = await instance.exportPDF();
        const blob = new Blob([buffer], { type: 'application/pdf' });
        const url = URL.createObjectURL(blob);

        const link = document.createElement('a');
        link.href = url;
        link.download = filename;
        link.click();

        URL.revokeObjectURL(url);
    } catch (error) {
        console.error('Failed to download PDF:', error);
        throw error;
    }
}

/**
 * Print PDF
 *
 * @param {PSPDFKit.Instance} instance - Nutrient instance
 * @returns {Promise<void>}
 */
export async function printPdf(instance) {
    if (!instance) {
        throw new Error('No PDF viewer instance provided');
    }

    try {
        await instance.print();
    } catch (error) {
        console.error('Failed to print PDF:', error);
        throw error;
    }
}

// Export PSPDFKit for direct access
export { PSPDFKit };

// Make available globally for non-module scripts
if (typeof window !== 'undefined') {
    window.PdfViewerModule = {
        loadPdfViewer,
        exportAnnotations,
        importAnnotations,
        saveAnnotationsToBackend,
        loadAnnotationsFromBackend,
        setupAnnotationAutosave,
        getAnnotationCount,
        goToPage,
        downloadPdfWithAnnotations,
        printPdf,
        PSPDFKit
    };
}
