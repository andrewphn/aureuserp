import { chromium } from '@playwright/test';

(async () => {
  const browser = await chromium.launch({ headless: false });
  const context = await browser.newContext();
  const page = await context.newPage();

  console.log('🚀 Starting Per-Page Annotations Test...\n');

  try {
    // Step 0: Login
    console.log('📍 Step 0: Logging in...');
    await page.goto('http://aureuserp.test/admin/login');
    await page.fill('input[type="email"]', 'info@tcswoodwork.com');
    await page.fill('input[type="password"]', 'Lola2024!');
    await page.click('button:has-text("Sign in")');
    await page.waitForURL('**/admin/**', { timeout: 10000 });
    console.log('✅ Logged in\n');

    // Step 1: Navigate to annotation viewer
    console.log('📍 Step 1: Navigating to V3 annotation viewer...');
    await page.goto('http://aureuserp.test/admin/project/projects/1/annotate-v2/1?pdf=1');
    await page.waitForTimeout(4000);
    console.log('✅ Page loaded\n');

    // Step 2: Check console logs for page map
    console.log('📍 Step 2: Checking page map in console...');
    const consoleLogs = [];
    page.on('console', msg => {
      const text = msg.text();
      consoleLogs.push(text);
      if (text.includes('pdfPageId') || text.includes('pageMap') || text.includes('annotations')) {
        console.log(`   📋 Console: ${text}`);
      }
    });
    await page.waitForTimeout(2000);
    console.log('✅ Console monitoring active\n');

    // Step 3: Count annotations on page 1
    console.log('📍 Step 3: Counting annotations on Page 1...');
    const page1AnnotationCount = await page.evaluate(() => {
      const annotations = document.querySelectorAll('.annotation-marker');
      return annotations.length;
    });
    console.log(`   Found ${page1AnnotationCount} annotations on Page 1`);
    await page.screenshot({ path: 'page-1-annotations.png', fullPage: false });
    console.log('✅ Screenshot saved: page-1-annotations.png\n');

    // Step 4: Navigate to Page 2
    console.log('📍 Step 4: Navigating to Page 2...');
    await page.click('button:has-text("Next →")');
    await page.waitForTimeout(3000); // Wait for annotations to load

    // Step 5: Count annotations on page 2
    console.log('📍 Step 5: Counting annotations on Page 2...');
    const page2AnnotationCount = await page.evaluate(() => {
      const annotations = document.querySelectorAll('.annotation-marker');
      return annotations.length;
    });
    console.log(`   Found ${page2AnnotationCount} annotations on Page 2`);
    await page.screenshot({ path: 'page-2-annotations.png', fullPage: false });
    console.log('✅ Screenshot saved: page-2-annotations.png\n');

    // Step 6: Navigate to Page 3
    console.log('📍 Step 6: Navigating to Page 3...');
    await page.click('button:has-text("Next →")');
    await page.waitForTimeout(3000);

    // Step 7: Count annotations on page 3
    console.log('📍 Step 7: Counting annotations on Page 3...');
    const page3AnnotationCount = await page.evaluate(() => {
      const annotations = document.querySelectorAll('.annotation-marker');
      return annotations.length;
    });
    console.log(`   Found ${page3AnnotationCount} annotations on Page 3`);
    await page.screenshot({ path: 'page-3-annotations.png', fullPage: false });
    console.log('✅ Screenshot saved: page-3-annotations.png\n');

    // Step 8: Navigate back to Page 1
    console.log('📍 Step 8: Navigating back to Page 1...');
    await page.click('button:has-text("← Prev")');
    await page.waitForTimeout(2000);
    await page.click('button:has-text("← Prev")');
    await page.waitForTimeout(3000);

    // Step 9: Verify Page 1 annotations again
    console.log('📍 Step 9: Verifying Page 1 annotations again...');
    const page1AnnotationCountAgain = await page.evaluate(() => {
      const annotations = document.querySelectorAll('.annotation-marker');
      return annotations.length;
    });
    console.log(`   Found ${page1AnnotationCountAgain} annotations on Page 1 (second visit)`);
    console.log('✅ Verified Page 1\n');

    // Step 10: Analysis
    console.log('📊 ANALYSIS:\n');
    console.log(`   Page 1 annotations: ${page1AnnotationCount} (first visit), ${page1AnnotationCountAgain} (second visit)`);
    console.log(`   Page 2 annotations: ${page2AnnotationCount}`);
    console.log(`   Page 3 annotations: ${page3AnnotationCount}`);

    // Check if per-page loading is working
    const allSame = page1AnnotationCount === page2AnnotationCount && page2AnnotationCount === page3AnnotationCount;
    const consistent = page1AnnotationCount === page1AnnotationCountAgain;

    if (allSame) {
      console.log('\n⚠️  WARNING: All pages have the same annotation count!');
      console.log('   This suggests per-page filtering is NOT working correctly.');
      console.log('   Expected: Different counts per page (unless they actually have the same annotations)');
    } else {
      console.log('\n✅ SUCCESS: Different annotation counts per page!');
      console.log('   Per-page annotation filtering appears to be working.');
    }

    if (!consistent) {
      console.log('\n⚠️  WARNING: Page 1 annotation count changed!');
      console.log('   This suggests annotations are not being loaded consistently.');
    } else {
      console.log('\n✅ Consistency check passed: Page 1 has same annotations on both visits');
    }

    // Check console logs for pdfPageId updates
    const pdfPageIdLogs = consoleLogs.filter(log => log.includes('pdfPageId'));
    if (pdfPageIdLogs.length > 0) {
      console.log('\n📋 pdfPageId Update Logs:');
      pdfPageIdLogs.forEach(log => console.log(`   ${log}`));
    }

    console.log('\n🎉 Test completed!\n');

  } catch (error) {
    console.error('❌ Test Failed:', error.message);
    await page.screenshot({ path: 'per-page-annotations-error.png', fullPage: true });
    console.log('Error screenshot saved: per-page-annotations-error.png');
  } finally {
    await browser.close();
  }
})();
