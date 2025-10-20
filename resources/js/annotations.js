/**
 * Annotation System Entry Point
 *
 * Bundles PDF.js and annotation system for production use.
 * Registers Alpine component globally for use in Blade templates.
 */

import * as pdfjsLib from 'pdfjs-dist';
import { createAnnotationComponent } from '../../plugins/webkul/projects/resources/js/annotations/alpine-component-factory.js';
import { PDFDocumentManager } from './pdf-document-manager.js';

// Configure PDF.js worker path
pdfjsLib.GlobalWorkerOptions.workerSrc = '/js/pdf.worker.min.js';

// Initialize PDF Document Manager (singleton)
const pdfManager = PDFDocumentManager.getInstance();

// Export for debugging in console
window.pdfjsLib = pdfjsLib;
window.PDFDocumentManager = PDFDocumentManager;
window.pdfManager = pdfManager;
window.createAnnotationComponent = createAnnotationComponent;

console.log('✅ PDF Document Manager initialized (shared instance)');

// Auto-register Alpine component when Alpine initializes
document.addEventListener('alpine:init', () => {
    Alpine.data('pdfThumbnailPdfJs', () => {
        return createAnnotationComponent(pdfjsLib);
    });

    // NOTE: annotationSystemV3 component is defined in the blade file
    // (plugins/webkul/projects/resources/views/filament/components/pdf-annotation-viewer-v3-overlay.blade.php)
    // Do not register it here to avoid overwriting the full component with this minimal version

    console.log('✅ PDF Annotation System loaded - Alpine components registered');
});
