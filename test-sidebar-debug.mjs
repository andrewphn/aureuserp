import { chromium } from '@playwright/test';

(async () => {
    console.log('🔍 Debugging sidebar visibility...\\n');

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
        console.log('📝 Logging in...');
        await page.goto('http://aureuserp.test/admin/login');
        await page.fill('input[type="email"]', 'info@tcswoodwork.com');
        await page.fill('input[type="password"]', 'Lola2024!');
        await page.click('button[type="submit"]');
        await page.waitForTimeout(2000);
        console.log('✅ Logged in\\n');

        // Navigate to annotation page
        console.log('📝 Opening annotation page...');
        await page.goto('http://aureuserp.test/admin/project/projects/1/annotate/1?pdf=1');
        await page.waitForTimeout(5000);
        console.log('✅ Page loaded\\n');

        // Check for sidebar
        console.log('📝 Checking sidebar...');

        // Check if sidebar exists
        const sidebar = await page.locator('.w-\\[280px\\]').count();
        console.log(`   Sidebar count: ${sidebar}`);

        // Check Alpine data
        const alpineData = await page.evaluate(() => {
            const el = document.querySelector('[x-data*="annotationSystemV3"]');
            if (el && el._x_dataStack) {
                return el._x_dataStack[0];
            }
            return null;
        });

        if (alpineData) {
            console.log(`   sidebarCollapsed: ${alpineData.sidebarCollapsed}`);
            console.log(`   currentPage: ${alpineData.currentPage}`);
            console.log(`   pageType: ${alpineData.pageType}`);
        } else {
            console.log('   ⚠️ Alpine data not found');
        }

        // Check visibility
        const sidebarVisible = await page.locator('.w-\\[280px\\]').isVisible().catch(() => false);
        console.log(`   Sidebar visible: ${sidebarVisible}`);

        // Get sidebar computed styles
        const sidebarStyles = await page.evaluate(() => {
            const sidebar = document.querySelector('.w-\\[280px\\]');
            if (sidebar) {
                const styles = window.getComputedStyle(sidebar);
                return {
                    display: styles.display,
                    visibility: styles.visibility,
                    opacity: styles.opacity,
                    position: styles.position,
                    zIndex: styles.zIndex,
                    width: styles.width,
                    height: styles.height
                };
            }
            return null;
        });

        if (sidebarStyles) {
            console.log('   Sidebar styles:', sidebarStyles);
        }

        // Take screenshot
        await page.screenshot({ path: 'sidebar-debug.png', fullPage: false });
        console.log('\\n✅ Screenshot: sidebar-debug.png');

        // Check console messages
        const messages = [];
        page.on('console', msg => {
            messages.push(`${msg.type()}: ${msg.text()}`);
        });

        await page.waitForTimeout(2000);

        if (messages.length > 0) {
            console.log('\\nConsole messages:');
            messages.forEach(msg => console.log(`   ${msg}`));
        }

        console.log('\\nBrowser will stay open for inspection...');
        console.log('Press Ctrl+C to close');
        await page.waitForTimeout(60000);

    } catch (error) {
        console.error('\\n❌ Error:', error.message);
        await page.screenshot({ path: 'sidebar-debug-error.png', fullPage: true });
    } finally {
        await browser.close();
        console.log('\\n✅ Browser closed');
    }
})();
