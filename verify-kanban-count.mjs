import { chromium } from '@playwright/test';

(async () => {
  const browser = await chromium.launch({ headless: false });
  const context = await browser.newContext();
  const page = await context.newPage();

  try {
    // Login
    await page.goto('http://aureuserp.test/admin/login');
    await page.fill('input[type="email"]', 'info@tcswoodwork.com');
    await page.fill('input[type="password"]', 'Lola2024!');
    await page.click('button[type="submit"]');
    await page.waitForLoadState('networkidle');
    
    // Navigate to kanban
    await page.goto('http://aureuserp.test/admin/project/kanban');
    await page.waitForLoadState('networkidle');
    await page.waitForTimeout(3000);
    
    // Take screenshot
    await page.screenshot({ 
      path: '/Users/andrewphan/tcsadmin/aureuserp/kanban-verification.png', 
      fullPage: true 
    });
    
    console.log('Screenshot saved to kanban-verification.png');
    console.log('\n');
    console.log('==================================================');
    console.log('KANBAN BOARD COUNT VERIFICATION');
    console.log('==================================================\n');
    
    // Inspect the HTML structure first
    const bodyHTML = await page.content();
    
    // Try to find column headers that contain stage names
    const stageHeaders = await page.$$('div:has-text("Discovery"), div:has-text("Design"), div:has-text("Sourcing"), div:has-text("Production")');
    console.log('Found ' + stageHeaders.length + ' visible stage headers\n');
    
    // Count by looking at the header text which shows "Discovery / 4", etc.
    const headerTexts = await page.$$eval('[class*="flex"][class*="items-center"] > div:first-child', 
      elements => elements.map(el => el.textContent.trim())
    );
    
    console.log('Stage headers found:');
    headerTexts.forEach(text => {
      if (text.includes('/')) {
        console.log('  ' + text);
      }
    });
    
    // Extract counts from headers like "Discovery / 4"
    let totalVisible = 0;
    const stageCounts = {};
    
    for (const text of headerTexts) {
      if (text.includes('/')) {
        const match = text.match(/(.+?)\s*\/\s*(\d+)/);
        if (match) {
          const stageName = match[1].trim();
          const count = parseInt(match[2]);
          stageCounts[stageName] = count;
          
          // Exclude Done and Cancelled
          const isExcluded = stageName.toLowerCase().includes('done') || 
                            stageName.toLowerCase().includes('cancelled') ||
                            stageName.toLowerCase().includes('complete');
          
          if (!isExcluded) {
            totalVisible += count;
            console.log('\n' + stageName + ': ' + count + ' projects');
          } else {
            console.log('\n' + stageName + ': ' + count + ' projects [EXCLUDED]');
          }
        }
      }
    }
    
    console.log('\n--------------------------------------------------');
    console.log('Total visible projects: ' + totalVisible);
    console.log('--------------------------------------------------\n');
    
    // Extract "All" badge count
    const allText = await page.textContent('body');
    const match = allText.match(/All\s+(\d+)/);
    const badgeCount = match ? parseInt(match[1]) : null;
    
    console.log('==================================================');
    console.log('VERIFICATION RESULTS');
    console.log('==================================================\n');
    
    if (badgeCount !== null) {
      console.log('"All" badge count: ' + badgeCount);
      console.log('Visible projects count: ' + totalVisible);
      
      if (badgeCount === totalVisible) {
        console.log('\n✅ PASS: Badge count matches visible projects!');
      } else {
        const diff = badgeCount > totalVisible ? badgeCount - totalVisible : totalVisible - badgeCount;
        console.log('\n❌ FAIL: Counts do not match!');
        console.log('   Badge shows: ' + badgeCount);
        console.log('   Actually visible: ' + totalVisible);
        console.log('   Difference: ' + diff + ' projects');
        
        if (badgeCount > totalVisible) {
          console.log('\n   ⚠️  Badge is counting ' + diff + ' EXTRA projects');
          console.log('   This likely includes Done/Cancelled stages');
        } else {
          console.log('\n   ⚠️  Badge is missing ' + diff + ' projects');
        }
      }
    } else {
      console.log('❌ Could not find "All" badge count');
      console.log('   Visible projects counted: ' + totalVisible);
    }
    
    console.log('\n==================================================\n');
    
  } catch (error) {
    console.error('Error:', error);
    console.error(error.stack);
  } finally {
    await browser.close();
  }
})();
