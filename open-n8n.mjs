import { chromium } from 'playwright';

(async () => {
    console.log('🚀 Opening n8n in browser...\n');

    const browser = await chromium.launch({
        headless: false,
        slowMo: 300
    });
    
    const context = await browser.newContext({
        viewport: { width: 1400, height: 900 }
    });
    
    const page = await context.newPage();

    try {
        // Navigate to n8n instance
        console.log('📝 Navigating to n8n.tcswoodwork.com...');
        await page.goto('https://n8n.tcswoodwork.com');
        await page.waitForTimeout(3000);
        
        console.log('✅ n8n loaded');
        console.log('\n💡 Please log in when ready.');
        console.log('💡 The browser will stay open for you to work with n8n.');
        
        // Keep the process running - browser stays open
        console.log('\n⏳ Browser will stay open. Press Ctrl+C in terminal to close when done.');
        
        // Keep the process running
        await new Promise(() => {}); // Never resolves, keeps browser open
        
    } catch (error) {
        console.error('❌ Error:', error.message);
        console.error('\nTrying with http:// instead...');
        try {
            await page.goto('http://n8n.tcswoodwork.com');
            await page.waitForTimeout(3000);
            console.log('✅ n8n loaded (HTTP)');
        } catch (e2) {
            console.error('❌ Error with HTTP too:', e2.message);
            await browser.close();
        }
    }
})();
