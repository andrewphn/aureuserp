import { chromium } from '@playwright/test';

(async () => {
    const browser = await chromium.launch({ headless: false });
    const context = await browser.newContext();
    const page = await context.newPage();

    try {
        // Login
        console.log('Logging in...');
        await page.goto('http://aureuserp.test/admin/login');
        await page.fill('input[type="email"]', 'info@tcswoodwork.com');
        await page.fill('input[type="password"]', 'Lola2024!');
        await page.click('button[type="submit"]');
        await page.waitForTimeout(3000);
        
        console.log('✓ Logged in');
        
        // Navigate to project
        console.log('Navigating to project...');
        await page.goto('http://aureuserp.test/admin/project/projects/1');
        await page.waitForTimeout(2000);
        
        console.log('✓ On project page');
        console.log('Current URL:', page.url());
        
        // Look for any annotate buttons
        const buttons = await page.locator('button, a').evaluateAll(elements => 
            elements
                .filter(el => el.textContent.toLowerCase().includes('annotate'))
                .map(el => ({
                    text: el.textContent,
                    tag: el.tagName,
                    href: el.href || null
                }))
        );
        
        console.log('\nFound annotate buttons:', buttons);
        
        // Try to find the V2 annotate link
        const v2Link = await page.locator('text=/annotate/i').first();
        if (await v2Link.isVisible()) {
            console.log('\nClicking annotate link...');
            await v2Link.click();
            await page.waitForTimeout(3000);
            
            console.log('After click URL:', page.url());
            
            // Check what's on the page
            const pageTitle = await page.title();
            console.log('Page title:', pageTitle);
            
            const hasContent = await page.locator('.fi-page-content').count();
            console.log('Has page content container:', hasContent > 0);
            
            const contentText = await page.locator('.fi-page-content').textContent().catch(() => 'N/A');
            console.log('Content text:', contentText);
            
            // Take screenshot
            await page.screenshot({ path: 'navigated-v2.png', fullPage: true });
            console.log('\n✓ Screenshot: navigated-v2.png');
        } else {
            console.log('Could not find annotate link');
        }
        
        await page.waitForTimeout(10000);

    } catch (error) {
        console.error('Error:', error.message);
        await page.screenshot({ path: 'navigation-error.png' });
    } finally {
        await browser.close();
    }
})();
