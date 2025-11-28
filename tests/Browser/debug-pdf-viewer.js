/**
 * Debug script to check PDF viewer initialization
 */
import { chromium } from '@playwright/test';

(async () => {
    const browser = await chromium.launch({ headless: false });
    const context = await browser.newContext({
        storageState: 'tests/Browser/auth-state.json'
    });
    const page = await context.newPage();

    // Collect console messages
    page.on('console', msg => console.log('BROWSER:', msg.type(), msg.text()));
    page.on('pageerror', err => console.error('PAGE ERROR:', err.message));

    console.log('🚀 Navigating to PDF viewer...');
    await page.goto('http://aureuserp.test/admin/project/projects/9/annotate-v2/1?pdf=1', {
        waitUntil: 'networkidle'
    });

    console.log('📍 Current URL:', page.url());
    console.log('📄 Page title:', await page.title());

    // Wait a bit for JavaScript to initialize
    await page.waitForTimeout(5000);

    // Check Alpine.js
    const hasAlpine = await page.evaluate(() => window.hasOwnProperty('Alpine'));
    console.log('✅ Alpine.js exists:', hasAlpine);

    // Check component
    const componentCheck = await page.evaluate(() => {
        const el = document.querySelector('[x-data*="annotationSystemV3"]');
        if (!el) return { found: false, reason: 'Element not found' };

        try {
            const data = Alpine.$data(el);
            return {
                found: true,
                hasData: !!data,
                hasPdfDoc: !!data?.pdfDoc,
                totalPages: data?.totalPages || 0,
                systemReady: data?.systemReady || false,
                error: data?.error || null,
                initialized: data?._initialized || false
            };
        } catch (e) {
            return { found: true, error: e.message };
        }
    });

    console.log('🔍 Component check:', JSON.stringify(componentCheck, null, 2));

    // Take screenshot
    await page.screenshot({ path: 'tests/Browser/debug-screenshot.png', fullPage: true });
    console.log('📸 Screenshot saved');

    // Keep browser open for manual inspection
    console.log('\n⏸️  Browser will stay open for 30 seconds for manual inspection...');
    await page.waitForTimeout(30000);

    await browser.close();
})();
