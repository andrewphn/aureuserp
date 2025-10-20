import { chromium } from '@playwright/test';

(async () => {
    const browser = await chromium.launch({ headless: false });
    const context = await browser.newContext();
    const page = await context.newPage();

    // Login first
    await page.goto('http://aureuserp.test/admin/login');
    await page.fill('input[type="email"]', 'info@tcswoodwork.com');
    await page.fill('input[type="password"]', 'Lola2024!');
    await page.click('button[type="submit"]');
    await page.waitForLoadState('networkidle');
    
    console.log('✓ Logged in');
    
    // Now navigate to the V2 page
    await page.goto('http://aureuserp.test/admin/project/projects/1/annotate-v2/1?pdf=1');
    await page.waitForLoadState('networkidle');
    
    console.log('✓ Navigated to:', page.url());
    
    // Wait for page to render
    await page.waitForTimeout(3000);
    
    // Check what's on the page
    const pageTitle = await page.title();
    console.log('Page title:', pageTitle);
    
    // Check if Alpine loaded
    const alpineLoaded = await page.evaluate(() => typeof window.Alpine !== 'undefined');
    console.log('Alpine loaded:', alpineLoaded);
    
    // Check for key elements
    const hasPageContent = await page.locator('.fi-page-content').count();
    console.log('Has .fi-page-content:', hasPageContent);
    
    const hasPdfViewer = await page.locator('.pdf-viewer-container').count();
    console.log('Has .pdf-viewer-container:', hasPdfViewer);
    
    const hasXData = await page.locator('[x-data]').count();
    console.log('Elements with x-data:', hasXData);
    
    // Check console errors
    const logs = [];
    page.on('console', msg => {
        if (msg.type() === 'error') {
            logs.push(`ERROR: ${msg.text()}`);
        }
    });
    
    await page.waitForTimeout(2000);
    
    if (logs.length > 0) {
        console.log('\n❌ Console errors:');
        logs.forEach(log => console.log(log));
    }
    
    // Get HTML content size
    const html = await page.content();
    console.log('\nHTML size:', html.length, 'bytes');
    console.log('Contains "No PDF Found":', html.includes('No PDF Found'));
    console.log('Contains "pdf-annotation-viewer":', html.includes('pdf-annotation-viewer'));
    
    // Take screenshot
    await page.screenshot({ path: 'v2-logged-in.png', fullPage: true });
    console.log('\n✓ Screenshot: v2-logged-in.png');
    
    // Save HTML
    const fs = require('fs');
    fs.writeFileSync('v2-page-content.html', html);
    console.log('✓ HTML saved: v2-page-content.html');
    
    await page.waitForTimeout(5000);
    await browser.close();
})();
