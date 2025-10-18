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
                'resources/js/annotations.js',
                'resources/js/centralized-entity-store.js',
                'resources/js/form-auto-populate.js',
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
