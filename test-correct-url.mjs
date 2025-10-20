import { chromium } from '@playwright/test';

(async () => {
    const browser = await chromium.launch({ headless: false });
    const page = await browser.newContext().then(c => c.newPage());

    // Navigate to the CORRECT URL (annotate-v2, not annotate-pdf-v2)
    await page.goto('http://aureuserp.test/admin/project/projects/1/annotate-v2/1?pdf=1');
    await page.waitForLoadState('networkidle');
    
    console.log('✓ Navigated to:', page.url());
    
    await page.waitForTimeout(3000);
    
    // Check Alpine
    const alpineLoaded = await page.evaluate(() => typeof window.Alpine !== 'undefined');
    console.log('Alpine loaded:', alpineLoaded);
    
    // Check for PDF viewer
    const hasViewer = await page.locator('.pdf-viewer-container').count();
    console.log('PDF viewer found:', hasViewer > 0);
    
    // Take screenshot
    await page.screenshot({ path: 'correct-url-test.png', fullPage: true });
    console.log('✓ Screenshot: correct-url-test.png');
    
    await page.waitForTimeout(10000); // Keep open for inspection

    await browser.close();
})();
