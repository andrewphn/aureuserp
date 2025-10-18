import { chromium } from 'playwright';

(async () => {
    const browser = await chromium.connectOverCDP('http://localhost:9222');
    const contexts = browser.contexts();
    const context = contexts[0];
    const pages = context.pages();
    const page = pages[0];
    
    console.log('Clicking New cabinet run button...');
    await page.click('button:has-text("New cabinet run")');
    await page.waitForTimeout(2000);
    
    console.log('Taking screenshot...');
    await page.screenshot({ path: 'after-click-cabinet-run.png' });
    console.log('Screenshot saved!');
    
    await browser.close();
})();
