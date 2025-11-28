import { test, expect } from '@playwright/test';

const PROJECT_ID = 9;
const PDF_PAGE_ID = 1;
const PDF_ID = 1;
const PDF_VIEWER_URL = `/admin/project/projects/${PROJECT_ID}/annotate-v2/${PDF_PAGE_ID}?pdf=${PDF_ID}`;

test.describe('Component Load Diagnostics', () => {
    test('diagnose why component not loading', async ({ page }) => {
        console.log('🔍 Starting diagnostics...');

        // Enable console logging
        page.on('console', msg => console.log('📄 CONSOLE:', msg.type(), msg.text()));
        page.on('pageerror', err => console.error('❌ PAGE ERROR:', err.message));

        await page.goto(PDF_VIEWER_URL, { waitUntil: 'networkidle' });

        // Check if redirected to login
        if (page.url().includes('/login')) {
            console.log('⚠️ Redirected to login, authenticating...');
            await page.fill('input[type="email"]', 'info@tcswoodwork.com');
            await page.fill('input[type="password"]', 'Lola2024!');
            await page.click('button[type="submit"]');
            await page.waitForTimeout(2000);
            await page.goto(PDF_VIEWER_URL, { waitUntil: 'networkidle' });
        }

        console.log('📍 Current URL:', page.url());

        // Wait a bit for any async loading
        await page.waitForTimeout(3000);

        // Check for Alpine.js
        const alpineLoaded = await page.evaluate(() => {
            return typeof window.Alpine !== 'undefined';
        });
        console.log('✓ Alpine.js loaded:', alpineLoaded);

        // Check for component element
        const componentExists = await page.evaluate(() => {
            const el = document.querySelector('[x-data*="annotationSystemV3"]');
            return {
                exists: !!el,
                html: el ? el.outerHTML.substring(0, 200) : null
            };
        });
        console.log('✓ Component exists:', componentExists.exists);
        if (componentExists.exists) {
            console.log('  HTML preview:', componentExists.html);
        }

        // Check Alpine data if component exists
        if (alpineLoaded && componentExists.exists) {
            const componentData = await page.evaluate(() => {
                const el = document.querySelector('[x-data*="annotationSystemV3"]');
                if (!el || !window.Alpine) return null;
                const data = Alpine.$data(el);
                return {
                    systemReady: data?.systemReady,
                    annotationsCount: data?.annotations?.length,
                    currentPage: data?.currentPage,
                    hasError: !!data?.error
                };
            });
            console.log('📊 Component data:', JSON.stringify(componentData, null, 2));
        }

        // Check for any JavaScript errors in console
        const consoleErrors = await page.evaluate(() => {
            // Check if there are any error messages
            return {
                bodyText: document.body.innerText.substring(0, 500),
                hasErrorMessage: document.body.innerText.includes('error') ||
                                document.body.innerText.includes('Error')
            };
        });
        console.log('📄 Page preview:', consoleErrors.bodyText);
        console.log('⚠️ Has error messages:', consoleErrors.hasErrorMessage);

        // Take screenshot
        await page.screenshot({
            path: 'tests/Browser/diagnose-component-load.png',
            fullPage: true
        });

        console.log('✅ Diagnostics complete - see screenshot');
    });
});
