import { chromium } from 'playwright';

(async () => {
    console.log('🚀 Opening Slack in browser...\n');

    const browser = await chromium.launch({
        headless: false,
        slowMo: 300
    });
    
    const context = await browser.newContext({
        viewport: { width: 1400, height: 900 }
    });
    
    const page = await context.newPage();

    try {
        // Navigate to Slack web app
        console.log('📝 Navigating to Slack...');
        await page.goto('https://app.slack.com');
        await page.waitForTimeout(3000);
        
        console.log('✅ Slack loaded');
        console.log('\n💡 If you need to sign in, please do so now.');
        console.log('💡 Then tell me which channel you want to open (e.g., #erp-discovery)');
        
        // Wait for user interaction - keep browser open
        console.log('\n⏳ Browser will stay open. Press Ctrl+C to close when done.');
        
        // Keep the process running
        await new Promise(() => {}); // Never resolves, keeps browser open
        
    } catch (error) {
        console.error('❌ Error:', error.message);
        await browser.close();
    }
})();
