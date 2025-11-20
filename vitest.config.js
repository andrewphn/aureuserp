import { defineConfig } from 'vitest/config';
import path from 'path';

export default defineConfig({
    test: {
        globals: true,
        environment: 'happy-dom',
        setupFiles: ['./tests/Unit/setup.js'],
        coverage: {
            provider: 'v8',
            reporter: ['text', 'html', 'lcov'],
            exclude: [
                'node_modules/',
                'tests/',
                '**/*.test.js',
                '**/*.spec.js',
                '**/test-*.js',
                'public/',
                'vendor/',
            ],
        },
        include: ['tests/Unit/**/*.test.js'],
    },
    resolve: {
        alias: {
            '@': path.resolve(__dirname, './resources/js'),
            '@pdf-viewer': path.resolve(__dirname, './plugins/webkul/projects/resources/js/components/pdf-viewer'),
        },
    },
});
