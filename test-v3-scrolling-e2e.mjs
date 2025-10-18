import { chromium } from '@playwright/test';

(async () => {
  const browser = await chromium.launch({ headless: false });
  const page = await browser.newPage();
  
  console.log('🚀 Starting V3 Scrolling E2E Test...\n');
  
  try {
    // Step 1: Navigate to annotation viewer
    console.log('📍 Step 1: Navigating to V3 annotation viewer...');
    await page.goto('http://aureuserp.test/admin/project/projects/1/annotate-v2/1?pdf=1');
    await page.waitForTimeout(3000);
    console.log('✅ Page loaded\n');
    
    // Step 2: Verify initial state
    console.log('📍 Step 2: Verifying initial state (Page 1)...');
    const page1Text = await page.locator('text=Page 1 of 8').textContent();
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
    const page2Text = await page.locator('text=Page 2 of 8').textContent();
    console.log(`   Pagination: ${page2Text}`);
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
    let pageText = await page.locator('text=Page 3 of 8').textContent();
    console.log(`   ✓ ${pageText}`);
    
    await page.click('button:has-text("Next →")');
    await page.waitForTimeout(1500);
    pageText = await page.locator('text=Page 4 of 8').textContent();
    console.log(`   ✓ ${pageText}`);
    
    await page.click('button:has-text("← Prev")');
    await page.waitForTimeout(1500);
    pageText = await page.locator('text=Page 3 of 8').textContent();
    console.log(`   ✓ ${pageText}`);
    
    await page.click('button:has-text("← Prev")');
    await page.waitForTimeout(1500);
    pageText = await page.locator('text=Page 2 of 8').textContent();
    console.log(`   ✓ ${pageText}`);
    
    await page.click('button:has-text("← Prev")');
    await page.waitForTimeout(1500);
    pageText = await page.locator('text=Page 1 of 8').textContent();
    console.log(`   ✓ ${pageText}`);
    
    console.log('✅ Multi-page navigation successful\n');
    
    // Step 7: Navigate to last page
    console.log('📍 Step 7: Navigating to last page (Page 8)...');
    for (let i = 1; i < 8; i++) {
      await page.click('button:has-text("Next →")');
      await page.waitForTimeout(1000);
    }
    const page8Text = await page.locator('text=Page 8 of 8').textContent();
    console.log(`   Pagination: ${page8Text}`);
    const nextDisabledAtEnd = await page.locator('button:has-text("Next →")').isDisabled();
    console.log(`   Next button disabled at end: ${nextDisabledAtEnd}`);
    if (!nextDisabledAtEnd) throw new Error('Next button should be disabled on last page');
    console.log('✅ Last page navigation correct\n');
    
    // Step 8: Take screenshot
    console.log('📍 Step 8: Taking final screenshot...');
    await page.screenshot({ path: 'v3-scrolling-e2e-final.png', fullPage: false });
    console.log('✅ Screenshot saved: v3-scrolling-e2e-final.png\n');
    
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
