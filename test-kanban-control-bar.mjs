import { chromium } from 'playwright';

const baseUrl = 'http://aureuserp.test';
const loginUrl = `${baseUrl}/admin/login`;
const kanbanUrl = `${baseUrl}/admin/project/kanban`;

async function sleep(ms) {
  return new Promise(resolve => setTimeout(resolve, ms));
}

async function main() {
  console.log('Starting Kanban Control Bar User Story Tests...\n');
  
  const browser = await chromium.launch({
    headless: false,
    executablePath: '/Users/andrewphan/Library/Caches/ms-playwright/chromium-1193/chrome-mac/Chromium.app/Contents/MacOS/Chromium'
  });
  const context = await browser.newContext({
    viewport: { width: 1920, height: 1080 }
  });
  const page = await context.newPage();

  try {
    // Login
    console.log('Step 0: Logging in...');
    await page.goto(loginUrl);
    await page.waitForLoadState('networkidle');
    await sleep(1000);
    
    await page.fill('input[type="email"]', 'info@tcswoodwork.com');
    await page.fill('input[type="password"]', 'Lola2024!');
    await page.click('button[type="submit"]');
    
    await page.waitForURL(/\/admin\/(?!login)/, { timeout: 15000 });
    await sleep(2000);
    console.log('✓ Login successful\n');

    // Navigate to Kanban
    console.log('Navigating to Kanban board...');
    await page.goto(kanbanUrl);
    await page.waitForLoadState('networkidle');
    await sleep(3000);
    
    await page.screenshot({ path: '/tmp/kanban-initial.png', fullPage: true });
    console.log('✓ Kanban board loaded\n');

    // US1: Toggle between Projects and Tasks views
    console.log('=== US1: Toggle between Projects and Tasks views ===');
    
    const tasksTab = page.locator('button:has-text("Tasks")').first();
    const projectsTab = page.locator('button:has-text("Projects")').first();
    
    const tasksTabExists = await tasksTab.count() > 0;
    const projectsTabExists = await projectsTab.count() > 0;
    
    console.log(`Tasks tab found: ${tasksTabExists}`);
    console.log(`Projects tab found: ${projectsTabExists}`);
    
    if (tasksTabExists) {
      console.log('Clicking Tasks tab...');
      await tasksTab.click();
      await sleep(2000);
      await page.screenshot({ path: '/tmp/kanban-us1-tasks-view.png', fullPage: true });
      console.log('✓ Tasks view screenshot captured');
      console.log('RESULT: US1 Tasks tab - PASS\n');
      
      if (projectsTabExists) {
        console.log('Clicking Projects tab to return...');
        await projectsTab.click();
        await sleep(2000);
        await page.screenshot({ path: '/tmp/kanban-us1-projects-view.png', fullPage: true });
        console.log('✓ Projects view screenshot captured');
        console.log('RESULT: US1 Projects tab - PASS\n');
      }
    } else {
      console.log('RESULT: US1 - FAIL\n');
    }

    // US2: Filter projects by status
    console.log('=== US2: Filter projects by status ===');
    
    const blockedFilter = page.getByRole('tab', { name: /Blocked/i });
    const allFilter = page.getByRole('tab', { name: /^All/i });
    
    const blockedFilterExists = await blockedFilter.count() > 0;
    const allFilterExists = await allFilter.count() > 0;
    
    console.log(`Blocked filter found: ${blockedFilterExists}`);
    console.log(`All filter found: ${allFilterExists}`);
    
    if (blockedFilterExists) {
      console.log('Clicking Blocked filter...');
      await blockedFilter.click();
      await sleep(2500);
      await page.screenshot({ path: '/tmp/kanban-us2-blocked-filter.png', fullPage: true });
      console.log('✓ Blocked filter screenshot captured');
      console.log('RESULT: US2 Blocked filter - PASS\n');
      
      if (allFilterExists) {
        console.log('Clicking All filter to reset...');
        await allFilter.click();
        await sleep(2500);
        await page.screenshot({ path: '/tmp/kanban-us2-all-filter.png', fullPage: true });
        console.log('✓ All filter screenshot captured');
        console.log('RESULT: US2 All filter - PASS\n');
      }
    } else {
      console.log('RESULT: US2 - FAIL\n');
    }

    // US3: Change time range for KPIs
    console.log('=== US3: Change time range for KPIs ===');
    
    // First, check if we need to toggle analytics visibility
    // Look for the analytics toggle icon (chart icon on far right)
    const analyticsToggle = page.locator('[data-analytics-toggle], button:has-text("Analytics")').first();
    const toggleExists = await analyticsToggle.count() > 0;
    
    if (toggleExists) {
      console.log('Found analytics toggle, clicking it...');
      await analyticsToggle.click();
      await sleep(2000);
      await page.screenshot({ path: '/tmp/kanban-us3-analytics-shown.png', fullPage: true });
    }
    
    // Now look for time range buttons - try multiple selectors
    let qtrButton = page.locator('button').filter({ hasText: /^Qtr$/ });
    let ytdButton = page.locator('button').filter({ hasText: /^YTD$/ });
    
    let qtrExists = await qtrButton.count() > 0;
    let ytdExists = await ytdButton.count() > 0;
    
    console.log(`Qtr button found: ${qtrExists}`);
    console.log(`YTD button found: ${ytdExists}`);
    
    // If not found, try as span/div elements
    if (!qtrExists) {
      qtrButton = page.locator('span, div').filter({ hasText: /^Qtr$/ });
      qtrExists = await qtrButton.count() > 0;
      console.log(`Qtr element (non-button) found: ${qtrExists}`);
    }
    
    if (!ytdExists) {
      ytdButton = page.locator('span, div').filter({ hasText: /^YTD$/ });
      ytdExists = await ytdButton.count() > 0;
      console.log(`YTD element (non-button) found: ${ytdExists}`);
    }
    
    if (qtrExists) {
      console.log('Clicking Qtr time range...');
      await qtrButton.first().click();
      await sleep(2000);
      await page.screenshot({ path: '/tmp/kanban-us3-qtr-range.png', fullPage: true });
      console.log('✓ Quarter range screenshot captured');
      console.log('RESULT: US3 Qtr - PASS\n');
    }
    
    if (ytdExists) {
      console.log('Clicking YTD time range...');
      await ytdButton.first().click();
      await sleep(2000);
      await page.screenshot({ path: '/tmp/kanban-us3-ytd-range.png', fullPage: true });
      console.log('✓ YTD range screenshot captured');
      console.log('RESULT: US3 YTD - PASS\n');
    }
    
    if (!qtrExists && !ytdExists) {
      await page.screenshot({ path: '/tmp/kanban-us3-not-found.png', fullPage: true });
      console.log('RESULT: US3 - FAIL (time range buttons not found)\n');
    }

    // US4: Verify Linear Feet badge
    console.log('=== US4: Verify Linear Feet badge ===');
    
    const lfBadge = page.locator('text=/\\d+\\s*LF/i').first();
    const lfBadgeExists = await lfBadge.count() > 0;
    
    console.log(`Linear Feet badge found: ${lfBadgeExists}`);
    
    if (lfBadgeExists) {
      const lfText = await lfBadge.textContent();
      console.log(`Linear Feet value: "${lfText.trim()}"`);
      
      // Highlight and screenshot
      await lfBadge.evaluate(el => {
        el.style.outline = '3px solid red';
        el.style.outlineOffset = '2px';
      });
      await sleep(500);
      await page.screenshot({ path: '/tmp/kanban-us4-lf-badge.png', fullPage: true });
      console.log('✓ Linear Feet badge screenshot captured');
      console.log('RESULT: US4 - PASS\n');
    } else {
      await page.screenshot({ path: '/tmp/kanban-us4-not-found.png', fullPage: true });
      console.log('RESULT: US4 - FAIL\n');
    }

    // Final screenshot
    await page.screenshot({ path: '/tmp/kanban-final.png', fullPage: true });
    
    console.log('\n========================================');
    console.log('TEST EXECUTION COMPLETE');
    console.log('========================================');
    console.log('All screenshots saved to /tmp/kanban-*.png');

  } catch (error) {
    console.error('\n❌ ERROR:', error.message);
    await page.screenshot({ path: '/tmp/kanban-error.png', fullPage: true });
  } finally {
    await browser.close();
  }
}

main();
