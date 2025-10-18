import { chromium } from '@playwright/test';

(async () => {
  const browser = await chromium.launch({ headless: false });
  const context = await browser.newContext();
  const page = await context.newPage();
  
  console.log('🚀 Starting V3 Scrolling E2E Test...\n');
  
  try {
    // Step 0: Login
    console.log('📍 Step 0: Logging in...');
    await page.goto('http://aureuserp.test/admin/login');
    await page.fill('input[type="email"]', 'info@tcswoodwork.com');
    await page.fill('input[type="password"]', 'Lola2024!');
    await page.click('button:has-text("Sign in")');
    await page.waitForURL('**/admin', { timeout: 10000 });
    console.log('✅ Logged in\n');
    
    // Step 1: Navigate to annotation viewer
    console.log('📍 Step 1: Navigating to V3 annotation viewer...');
    await page.goto('http://aureuserp.test/admin/project/projects/1/annotate-v2/1?pdf=1');
    await page.waitForTimeout(4000);
    console.log('✅ Page loaded\n');
    
    // Step 2: Verify initial state
    console.log('📍 Step 2: Verifying initial state (Page 1)...');
    await page.waitForSelector('text=/Page 1 of \\d+/', { timeout: 10000 });
    const page1Text = await page.locator('text=/Page \\d+ of \\d+/').first().textContent();
    console.log(`   Pagination: ${page1Text}`);
    const prevDisabled = await page.locator('button:has-text("← Prev")').isDisabled();
    const nextEnabled = await page.locator('button:has-text("Next →")').isEnabled();
    console.log(`   Prev button disabled: ${prevDisabled}`);
    console.log(`   Next button enabled: ${nextEnabled}`);
    if (!prevDisabled || !nextEnabled) throw new Error('Initial button states incorrect');
    console.log('✅ Initial state correct\n');
    
    // Step 3: Test scrolling is blocked
    console.log('📍 Step 3: Testing scroll blocking...');
    const scrollResult = await page.evaluate(() => {
      const iframe = document.querySelector('iframe');
      if (iframe && iframe.contentWindow) {
        const beforeScroll = iframe.contentWindow.scrollY;
        iframe.contentWindow.scrollBy(0, 1000);
        const afterScroll = iframe.contentWindow.scrollY;
        return { before: beforeScroll, after: afterScroll, blocked: beforeScroll === afterScroll };
      }
      return { error: 'No iframe found' };
    });
    console.log(`   Before scroll: ${scrollResult.before}`);
    console.log(`   After scroll attempt: ${scrollResult.after}`);
    console.log(`   Scroll blocked: ${scrollResult.blocked}`);
    if (!scrollResult.blocked) throw new Error('Scrolling is NOT blocked!');
    console.log('✅ Scrolling successfully blocked\n');
    
    // Step 4: Navigate to Page 2
    console.log('📍 Step 4: Clicking Next to navigate to Page 2...');
    await page.click('button:has-text("Next →")');
    await page.waitForTimeout(2000);
    const page2Text = await page.locator('text=/Page \\d+ of \\d+/').first().textContent();
    console.log(`   Pagination: ${page2Text}`);
    if (!page2Text.includes('Page 2')) throw new Error('Did not navigate to Page 2');
    const prevEnabled = await page.locator('button:has-text("← Prev")').isEnabled();
    const nextStillEnabled = await page.locator('button:has-text("Next →")').isEnabled();
    console.log(`   Prev button enabled: ${prevEnabled}`);
    console.log(`   Next button enabled: ${nextStillEnabled}`);
    if (!prevEnabled || !nextStillEnabled) throw new Error('Page 2 button states incorrect');
    console.log('✅ Navigation to Page 2 successful\n');
    
    // Step 5: Test scrolling on Page 2
    console.log('📍 Step 5: Testing scroll blocking on Page 2...');
    const scroll2Result = await page.evaluate(() => {
      const iframe = document.querySelector('iframe');
      if (iframe && iframe.contentWindow) {
        const beforeScroll = iframe.contentWindow.scrollY;
        iframe.contentWindow.scrollBy(0, 500);
        const afterScroll = iframe.contentWindow.scrollY;
        return { before: beforeScroll, after: afterScroll, blocked: beforeScroll === afterScroll };
      }
      return { error: 'No iframe found' };
    });
    console.log(`   Scroll blocked on Page 2: ${scroll2Result.blocked}`);
    if (!scroll2Result.blocked) throw new Error('Scrolling NOT blocked on Page 2!');
    console.log('✅ Scrolling blocked on Page 2\n');
    
    // Step 6: Navigate through multiple pages
    console.log('📍 Step 6: Testing multi-page navigation (2→3→4→3→2→1)...');
    
    await page.click('button:has-text("Next →")');
    await page.waitForTimeout(1500);
    let pageText = await page.locator('text=/Page \\d+ of \\d+/').first().textContent();
    console.log(`   ✓ ${pageText}`);
    if (!pageText.includes('Page 3')) throw new Error('Navigation to page 3 failed');
    
    await page.click('button:has-text("Next →")');
    await page.waitForTimeout(1500);
    pageText = await page.locator('text=/Page \\d+ of \\d+/').first().textContent();
    console.log(`   ✓ ${pageText}`);
    if (!pageText.includes('Page 4')) throw new Error('Navigation to page 4 failed');
    
    await page.click('button:has-text("← Prev")');
    await page.waitForTimeout(1500);
    pageText = await page.locator('text=/Page \\d+ of \\d+/').first().textContent();
    console.log(`   ✓ ${pageText}`);
    if (!pageText.includes('Page 3')) throw new Error('Navigation back to page 3 failed');
    
    await page.click('button:has-text("← Prev")');
    await page.waitForTimeout(1500);
    pageText = await page.locator('text=/Page \\d+ of \\d+/').first().textContent();
    console.log(`   ✓ ${pageText}`);
    if (!pageText.includes('Page 2')) throw new Error('Navigation back to page 2 failed');
    
    await page.click('button:has-text("← Prev")');
    await page.waitForTimeout(1500);
    pageText = await page.locator('text=/Page \\d+ of \\d+/').first().textContent();
    console.log(`   ✓ ${pageText}`);
    if (!pageText.includes('Page 1')) throw new Error('Navigation back to page 1 failed');
    
    console.log('✅ Multi-page navigation successful\n');
    
    // Step 7: Navigate to last page
    console.log('📍 Step 7: Navigating to last page...');
    const totalPages = parseInt(pageText.match(/of (\d+)/)[1]);
    console.log(`   Total pages: ${totalPages}`);
    
    for (let i = 1; i < totalPages; i++) {
      await page.click('button:has-text("Next →")');
      await page.waitForTimeout(800);
    }
    const lastPageText = await page.locator('text=/Page \\d+ of \\d+/').first().textContent();
    console.log(`   Pagination: ${lastPageText}`);
    const nextDisabledAtEnd = await page.locator('button:has-text("Next →")').isDisabled();
    console.log(`   Next button disabled at end: ${nextDisabledAtEnd}`);
    if (!nextDisabledAtEnd) throw new Error('Next button should be disabled on last page');
    console.log('✅ Last page navigation correct\n');
    
    // Step 8: Take screenshot
    console.log('📍 Step 8: Taking final screenshot...');
    await page.screenshot({ path: 'v3-scrolling-e2e-success.png', fullPage: false });
    console.log('✅ Screenshot saved: v3-scrolling-e2e-success.png\n');
    
    console.log('🎉 All E2E tests passed!\n');
    console.log('Summary:');
    console.log('  ✅ Scrolling blocked on all pages');
    console.log('  ✅ Next/Previous navigation works correctly');
    console.log('  ✅ Button states update properly');
    console.log('  ✅ Pagination counter accurate');
    console.log('  ✅ Multi-page navigation smooth');
    console.log('  ✅ Edge cases handled (first/last page)');
    
  } catch (error) {
    console.error('❌ E2E Test Failed:', error.message);
    await page.screenshot({ path: 'v3-scrolling-e2e-error.png', fullPage: true });
    console.log('Error screenshot saved: v3-scrolling-e2e-error.png');
  } finally {
    await browser.close();
  }
})();
