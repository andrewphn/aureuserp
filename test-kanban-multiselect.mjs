import { chromium } from 'playwright';

(async () => {
  const browser = await chromium.launch({ headless: false });
  const context = await browser.newContext({
    viewport: { width: 1920, height: 1080 }
  });
  const page = await context.newPage();

  try {
    // Navigate to kanban board
    console.log('Navigating to kanban board...');
    await page.goto('http://aureuserp.test/admin/project/kanban');
    
    // Check if login is needed
    if (page.url().includes('login')) {
      console.log('Logging in...');
      await page.fill('input[type="email"]', 'info@tcswoodwork.com');
      await page.fill('input[type="password"]', 'Lola2024!');
      await page.click('button[type="submit"]');
      await page.waitForLoadState('networkidle');
      await page.waitForTimeout(2000);
      
      // Navigate to kanban after login
      console.log('Navigating to kanban board after login...');
      await page.goto('http://aureuserp.test/admin/project/kanban', { waitUntil: 'networkidle' });
    }
    
    // Wait for page to be ready
    await page.waitForTimeout(3000);
    console.log('Current URL:', page.url());
    
    // Take initial screenshot
    await page.screenshot({ path: '/tmp/kanban-before-selection.png', fullPage: true });
    console.log('Screenshot 1: Initial kanban board (before selection)');
    
    // Find the Discovery column - it has 4 projects
    console.log('Looking for cards in Discovery column...');
    
    // The cards appear to be in a structure - let me find any clickable project cards
    const firstCard = page.locator('text=Test Active Folder Project').first();
    const secondCard = page.locator('text=Kitchen Cabinets for Test Company LLC').first();
    
    const hasFirst = await firstCard.count() > 0;
    const hasSecond = await secondCard.count() > 0;
    console.log(`Found first card: ${hasFirst}, second card: ${hasSecond}`);
    
    if (hasFirst && hasSecond) {
      // Cmd+Click first card
      console.log('Cmd+Click on first card (Test Active Folder Project)...');
      await firstCard.click({ modifiers: ['Meta'] });
      await page.waitForTimeout(800);
      
      // Take screenshot after first selection
      await page.screenshot({ path: '/tmp/kanban-one-selected.png', fullPage: true });
      console.log('Screenshot 2: One card selected (with blue ring)');
      
      // Cmd+Click second card
      console.log('Cmd+Click on second card (Kitchen Cabinets)...');
      await secondCard.click({ modifiers: ['Meta'] });
      await page.waitForTimeout(800);
      
      // Take screenshot after second selection
      await page.screenshot({ path: '/tmp/kanban-two-selected.png', fullPage: true });
      console.log('Screenshot 3: Two cards selected (with blue rings + bulk actions bar)');
      
      // Try to find bulk actions bar
      const bulkBar = page.locator('.bulk-actions-bar, [class*="bulk"], .fixed.bottom-0').first();
      const hasBulkBar = await bulkBar.count() > 0;
      console.log(`Bulk actions bar visible: ${hasBulkBar}`);
      
      // Wait a bit for observation
      console.log('Waiting 3 seconds for observation...');
      await page.waitForTimeout(3000);
      
    } else {
      console.log('Could not find expected cards for testing');
    }
    
  } catch (error) {
    console.error('Error:', error.message);
    await page.screenshot({ path: '/tmp/kanban-error.png', fullPage: true });
  } finally {
    await browser.close();
  }
})();
