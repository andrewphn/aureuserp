import { chromium } from 'playwright';

/**
 * Playwright script to navigate to n8n using MCP-style approach
 * This mimics the behavior of Playwright MCP tools
 */

(async () => {
    console.log('🚀 Starting Playwright browser session (MCP-style)...\n');

    const browser = await chromium.launch({
        headless: false,
        slowMo: 300
    });
    
    const context = await browser.newContext({
        viewport: { width: 1400, height: 900 }
    });
    
    const page = await context.newPage();

    try {
        // Step 1: Navigate to n8n (equivalent to browser_navigate MCP tool)
        console.log('📍 Step 1: Navigating to n8n.tcswoodwork.com...');
        await page.goto('https://n8n.tcswoodwork.com', {
            waitUntil: 'networkidle',
            timeout: 30000
        });
        await page.waitForTimeout(2000);
        console.log('✅ Navigation complete\n');

        // Step 2: Take snapshot (equivalent to browser_snapshot MCP tool)
        console.log('📸 Step 2: Taking page snapshot...');
        const snapshot = await page.accessibility.snapshot();
        console.log('✅ Page snapshot captured\n');

        // Step 3: Take screenshot (equivalent to browser_take_screenshot MCP tool)
        console.log('📷 Step 3: Taking screenshot...');
        await page.screenshot({
            path: 'n8n-page.png',
            fullPage: true
        });
        console.log('✅ Screenshot saved: n8n-page.png\n');

        console.log('💡 Browser is ready for interaction.');
        console.log('💡 You can now log in to n8n.');
        console.log('\n⏳ Browser will stay open. Press Ctrl+C to close when done.');

        // Keep browser open
        await new Promise(() => {}); // Never resolves
        
    } catch (error) {
        console.error('❌ Error:', error.message);
        console.error('\nTrying HTTP instead...');
        try {
            await page.goto('http://n8n.tcswoodwork.com');
            await page.waitForTimeout(2000);
            console.log('✅ Loaded via HTTP');
        } catch (e2) {
            console.error('❌ Both HTTPS and HTTP failed');
            await browser.close();
        }
    }
})();
