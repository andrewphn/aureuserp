import { chromium } from 'playwright';

(async () => {
    const browser = await chromium.launch({ headless: false });
    const context = await browser.newContext();
    const page = await context.newPage();

    // Collect console messages
    page.on('console', msg => console.log(`[BROWSER] ${msg.text()}`));

    console.log('=== Login ===');
    await page.goto('http://aureuserp.test/admin/login');
    await page.waitForLoadState('networkidle');
    await page.fill('input[type="email"]', 'info@tcswoodwork.com');
    await page.fill('input[type="password"]', 'Lola2024!');
    await page.click('button[type="submit"]');
    await page.waitForLoadState('networkidle');
    await page.waitForTimeout(2000);

    console.log('\n=== Navigate to Project Edit Page ===');
    await page.goto('http://aureuserp.test/admin/project/projects/1/edit');
    await page.waitForLoadState('networkidle');
    await page.waitForTimeout(3000);

    console.log('\n=== Check Footer State ===');

    // Get Alpine.js state
    const footerState = await page.evaluate(() => {
        const el = document.querySelector('[x-data="contextFooterGlobal()"]');
        if (!el) return { error: 'Footer element not found' };

        const alpine = Alpine.$data(el);
        return {
            isMinimized: alpine.isMinimized,
            hasActiveContext: alpine.hasActiveContext,
            contextType: alpine.contextType,
            contextId: alpine.contextId,
            hasActiveProject: alpine.hasActiveProject,
            projectData: alpine.projectData,
            preferencesLoaded: alpine.preferencesLoaded
        };
    });

    console.log('Alpine State:', JSON.stringify(footerState, null, 2));

    // Check which divs are visible
    const visibility = await page.evaluate(() => {
        const footer = document.querySelector('[x-data="contextFooterGlobal()"]');
        const noProjectDiv = footer.querySelector('[x-show="!hasActiveProject"]');
        const activeContextDiv = footer.querySelector('[x-show="hasActiveContext && preferencesLoaded"]');
        const contentDiv = footer.querySelector('.fi-section-content');

        return {
            noProjectDiv: {
                exists: !!noProjectDiv,
                displayed: noProjectDiv ? getComputedStyle(noProjectDiv).display !== 'none' : false,
                text: noProjectDiv ? noProjectDiv.textContent.trim().substring(0, 50) : ''
            },
            activeContextDiv: {
                exists: !!activeContextDiv,
                displayed: activeContextDiv ? getComputedStyle(activeContextDiv).display !== 'none' : false,
                text: activeContextDiv ? activeContextDiv.textContent.trim().substring(0, 50) : ''
            },
            contentDiv: {
                exists: !!contentDiv,
                displayed: contentDiv ? getComputedStyle(contentDiv).display !== 'none' : false
            }
        };
    });

    console.log('\nVisibility:', JSON.stringify(visibility, null, 2));

    console.log('\n=== Test Footer Toggle ===');

    // Try clicking the footer toggle
    const footerToggle = page.locator('[x-data="contextFooterGlobal()"]').locator('div.cursor-pointer').first();
    await footerToggle.click();
    await page.waitForTimeout(1000);

    // Check state after click
    const footerStateAfter = await page.evaluate(() => {
        const el = document.querySelector('[x-data="contextFooterGlobal()"]');
        const alpine = Alpine.$data(el);
        return {
            isMinimized: alpine.isMinimized
        };
    });

    console.log('After toggle click - isMinimized:', footerStateAfter.isMinimized);

    console.log('\nKeeping browser open for 30 seconds...');
    await page.waitForTimeout(30000);

    await browser.close();
})();
