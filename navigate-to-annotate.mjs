import { chromium } from '@playwright/test';

(async () => {
    console.log('🧭 Navigating through UI to annotation page...\n');

    const browser = await chromium.launch({
        headless: false,
        slowMo: 1000
    });
    const context = await browser.newContext({
        viewport: { width: 1920, height: 1080 }
    });
    const page = await context.newPage();

    try {
        // Login
        console.log('📝 Step 1: Logging in...');
        await page.goto('http://aureuserp.test/admin/login');
        await page.fill('input[type="email"]', 'info@tcswoodwork.com');
        await page.fill('input[type="password"]', 'Lola2024!');
        await page.click('button[type="submit"]');
        await page.waitForTimeout(2000);
        console.log('✅ Logged in\n');

        // Navigate to Projects
        console.log('📝 Step 2: Clicking Projects menu...');
        await page.click('a:has-text("Projects")');
        await page.waitForTimeout(1500);
        await page.screenshot({ path: 'nav-step-1-projects.png', fullPage: false });
        console.log('✅ On Projects list\n');

        // Click on first project
        console.log('📝 Step 3: Clicking first project...');
        const firstProjectRow = page.locator('table tbody tr').first();
        await firstProjectRow.click();
        await page.waitForTimeout(2000);
        await page.screenshot({ path: 'nav-step-2-project-view.png', fullPage: false });
        console.log('✅ On Project view page\n');

        // Look for Tasks tab or annotation link
        console.log('📝 Step 4: Looking for annotation access...');

        // Try clicking Tasks tab if it exists
        const tasksTab = page.locator('button:has-text("Tasks"), a:has-text("Tasks")');
        if (await tasksTab.count() > 0) {
            await tasksTab.first().click();
            await page.waitForTimeout(1500);
            await page.screenshot({ path: 'nav-step-3-tasks.png', fullPage: false });
            console.log('✅ On Tasks tab\n');
        }

        // Try to find any annotation button/link
        const annotateLink = page.locator('a:has-text("Annotate"), button:has-text("Annotate")');
        if (await annotateLink.count() > 0) {
            console.log('📝 Step 5: Clicking Annotate link...');
            await annotateLink.first().click();
            await page.waitForTimeout(3000);
            await page.screenshot({ path: 'nav-step-4-annotate.png', fullPage: false });
            console.log('✅ On Annotation page\n');
        } else {
            console.log('⚠️ No Annotate link found. Taking screenshot of current page...');
            await page.screenshot({ path: 'nav-no-annotate-link.png', fullPage: true });
        }

        // Check current URL
        console.log(`\nCurrent URL: ${page.url()}`);

        console.log('\nBrowser will stay open for inspection...');
        console.log('Press Ctrl+C to close');
        await page.waitForTimeout(60000);

    } catch (error) {
        console.error('\n❌ Error:', error.message);
        await page.screenshot({ path: 'nav-error.png', fullPage: true });
    } finally {
        await browser.close();
        console.log('\n✅ Browser closed');
    }
})();
