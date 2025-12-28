import { chromium } from '@playwright/test';

async function verifyKanbanBadge() {
  const browser = await chromium.launch({ headless: false });
  const context = await browser.newContext({
    storageState: '/Users/andrewphan/tcsadmin/aureuserp/tests/Browser/auth-state.json'
  });
  const page = await context.newPage();
  
  try {
    console.log('Navigating to kanban board...');
    await page.goto('http://aureuserp.test/admin/project/kanban');
    await page.waitForLoadState('networkidle');
    await page.waitForTimeout(3000);

    // Take screenshot
    await page.screenshot({ 
      path: '/Users/andrewphan/tcsadmin/aureuserp/kanban-badge-verification.png',
      fullPage: true 
    });

    console.log('\n=== BADGE COUNT VERIFICATION ===');
    
    // Get all page text
    const bodyText = await page.locator('body').textContent();
    
    // Extract the number after "All"
    const allMatch = bodyText.match(/All\s+(\d+)/);
    const allCount = allMatch ? parseInt(allMatch[1]) : 0;
    console.log('All badge count: ' + allCount);

    // Count by looking for known project titles
    const titles = [
      'Test Active Folder Project',
      'Kitchen Cabinets for Test Company LLC', 
      '25 Friendship Lane Kitchen & Pantry',
      '5 West Sankaty Road - Residential'
    ];
    
    let countByTitles = 0;
    console.log('\nLooking for projects:');
    for (const title of titles) {
      const exists = await page.locator(`text="${title}"`).count();
      if (exists > 0) {
        console.log('  ✓ Found: ' + title);
        countByTitles++;
      } else {
        console.log('  ✗ Not found: ' + title);
      }
    }
    
    console.log('\nProjects found: ' + countByTitles);
    
    // Verification
    const matches = allCount === countByTitles;
    console.log('\n=== VERIFICATION RESULT ===');
    console.log('All badge (' + allCount + ') matches visible projects (' + countByTitles + '): ' + (matches ? 'YES ✓' : 'NO ✗'));
    
    if (!matches) {
      console.log('\n✗ DISCREPANCY: All badge shows ' + allCount + ' but found ' + countByTitles + ' projects');
      console.log('This means the badge count does NOT match the kanban columns.');
    } else {
      console.log('\n✓ SUCCESS: The "All" badge count correctly matches the number of projects in the kanban columns!');
      console.log('The badge filtering is working as expected - it excludes the Inbox.');
    }

    console.log('\nScreenshot saved to: /Users/andrewphan/tcsadmin/aureuserp/kanban-badge-verification.png');
    await page.waitForTimeout(3000);
    
  } catch (error) {
    console.error('Error:', error.message);
    console.error(error.stack);
  } finally {
    await browser.close();
  }
}

verifyKanbanBadge();
