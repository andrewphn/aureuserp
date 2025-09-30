// Advanced File Upload Alpine.js Component
// This follows FilamentPHP's AlpineComponent pattern for proper x-load integration

function advancedFileUpload(config = {}) {
    console.log('[AdvancedFileUpload] Initializing with config:', config);

    return {
        state: {
            acceptedFileTypes: config.acceptedFileTypes || ['application/pdf'],
            maxFiles: config.maxFiles || 1,
            maxFileSize: config.maxFileSize || '10MB',
            pdfPreviewHeight: config.pdfPreviewHeight || 400,
            allowPdfPreview: config.allowPdfPreview !== false,
            ...config
        },

        init() {
            console.log('[AdvancedFileUpload] Component initialized');

            // Wait for FilePond to be available
            if (typeof window.FilePond !== 'undefined') {
                this.initializeFilePond();
            } else {
                // Wait for FilePond to load
                document.addEventListener('FilePond:loaded', () => {
                    this.initializeFilePond();
                });
            }
        },

        initializeFilePond() {
            console.log('[AdvancedFileUpload] Initializing FilePond with state:', this.state);
            // Let FilePond handle the file upload functionality
            // The actual FilePond initialization will be handled by the FilamentPHP integration
        }
    };
}

// Import the component loader
import { registerComponent } from '../livewire-component-loader.js';

// Register component with Livewire-aware loader
registerComponent('advancedFileUpload', advancedFileUpload);

// Also make immediately available
window.advancedFileUpload = advancedFileUpload;

// Export as default for ES6 module compatibility
export default advancedFileUpload;