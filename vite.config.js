import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';

export default defineConfig({
    plugins: [
        laravel({
            input: [
                'resources/css/app.css',
                'resources/js/app.js',
                'resources/js/login-debug.js', // Login debugging console logger
                'resources/js/pdf-viewer.js',
                'resources/js/pdf-thumbnail.js',
                'resources/js/pdf-document-manager.js',
                'resources/js/annotations.js', // PDF annotation system - loaded separately by PDF pages
                'plugins/webkul/projects/resources/js/pdf-viewer.js', // Projects plugin: Modular PDF annotation viewer
                'plugins/webkul/projects/resources/css/pdf-annotation-viewer.css', // Projects plugin: PDF viewer styles
                // Note: centralized-entity-store.js and form-auto-populate.js are imported in app.js
                'resources/css/filament/admin/theme.css',
            ],
            refresh: true,
        }),
    ],
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
