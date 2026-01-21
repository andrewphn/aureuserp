import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';

export default defineConfig({
    plugins: [
        laravel({
            input: [
                'resources/css/app.css',
                'resources/js/app.js',
                'resources/js/pdf-viewer.js',
                'resources/js/pdf-thumbnail.js',
                'resources/js/pdf-document-manager.js',
                'resources/js/annotations.js', // PDF annotation system - loaded separately by PDF pages
                'resources/js/dwg-parser.js', // DWG/DXF CAD file parser
                'plugins/webkul/projects/resources/js/pdf-viewer.js', // Projects plugin: Modular PDF annotation viewer
                'plugins/webkul/projects/resources/css/pdf-annotation-viewer.css', // Projects plugin: PDF viewer styles
                // Note: centralized-entity-store.js and form-auto-populate.js are imported in app.js
                'resources/css/filament/admin/theme.css',
            ],
            refresh: true,
        }),
    ],
    build: {
        rollupOptions: {
            output: {
                manualChunks: (id) => {
                    // Split PDF.js into its own chunk (likely the large 5.3MB bundle)
                    if (id.includes('pdfjs-dist') || id.includes('pdf.worker')) {
                        return 'pdfjs';
                    }
                    // Split node_modules into vendor chunk
                    if (id.includes('node_modules')) {
                        // Keep Alpine.js and other small libs together
                        if (id.includes('alpinejs') || id.includes('@alpinejs')) {
                            return 'vendor-alpine';
                        }
                        return 'vendor';
                    }
                    // Split PDF viewer managers into separate chunk for better code splitting
                    if (id.includes('pdf-viewer/managers/')) {
                        return 'pdf-managers';
                    }
                },
                chunkSizeWarningLimit: 1000, // Increase limit to 1MB (5MB chunk is likely PDF.js which is acceptable)
            },
        },
    },
    server: {
        host: '0.0.0.0',
        port: 5173,
        strictPort: true,
        hmr: {
            host: 'localhost',
            port: 5173,
        },
        cors: true,
        origin: 'http://localhost:5173',
    },
});
