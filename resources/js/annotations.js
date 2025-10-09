/**
 * Annotation System Entry Point
 *
 * Bundles PDF.js and annotation system for production use.
 * Registers Alpine component globally for use in Blade templates.
 */

import * as pdfjsLib from 'pdfjs-dist';
import { createAnnotationComponent } from '../../plugins/webkul/projects/resources/js/annotations/alpine-component-factory.js';

// Configure PDF.js worker path
pdfjsLib.GlobalWorkerOptions.workerSrc = '/js/pdf.worker.min.js';

// Export for debugging in console
window.pdfjsLib = pdfjsLib;
window.createAnnotationComponent = createAnnotationComponent;

// Auto-register Alpine component when Alpine initializes
document.addEventListener('alpine:init', () => {
    Alpine.data('pdfThumbnailPdfJs', () => {
        return createAnnotationComponent(pdfjsLib);
    });

    console.log('✅ PDF Annotation System loaded - Alpine component registered');
});
