import { chromium } from '@playwright/test';
import path from 'path';
import { fileURLToPath } from 'url';
import fs from 'fs';

const __filename = fileURLToPath(import.meta.url);
const __dirname = path.dirname(__filename);
const screenshotsDir = path.join(__dirname, 'tests/Browser/screenshots/kanban-user-stories');
fs.mkdirSync(screenshotsDir, { recursive: true });

async function detailedTests() {
  const browser = await chromium.launch({ headless: false });
  const context = await browser.newContext({ storageState: 'tests/Browser/auth-state.json' });
  const page = await context.newPage();

  const results = {
    us1: { title: 'Inbox Visibility', status: 'UNKNOWN', findings: [], screenshots: [] },
    us2: { title: 'Inbox Expansion/Collapse', status: 'UNKNOWN', findings: [], screenshots: [] },
    us3: { title: 'Column Spacing Consistency', status: 'UNKNOWN', findings: [], screenshots: [] },
    us4: { title: 'Drag and Drop', status: 'UNKNOWN', findings: [], screenshots: [] },
    us5: { title: 'Stage Headers', status: 'UNKNOWN', findings: [], screenshots: [] },
    us6: { title: 'Project Cards', status: 'UNKNOWN', findings: [], screenshots: [] },
    us7: { title: 'Customization', status: 'UNKNOWN', findings: [], screenshots: [] },
    us8: { title: 'Filtering', status: 'UNKNOWN', findings: [], screenshots: [] }
  };

  try {
    console.log('=== DETAILED PROJECT KANBAN BOARD USER STORY TESTING ===
');
    
    await page.goto('http://aureuserp.test/admin/project/kanban');
    await page.waitForLoadState('networkidle');
    await page.waitForTimeout(2000);

    // US1: Inbox Visibility
    console.log('
--- US1: Inbox Visibility ---');
    const inboxTextCount = await page.getByText('INBOX').count();
    const hasCollapsedInbox = await page.locator('.inbox-collapsed, [data-inbox-collapsed]').count();
    const hasInboxIcon = await page.locator('[class*="inbox"] i, [class*="inbox"] svg').count();
    
    results.us1.findings.push(`INBOX text found: ${inboxTextCount} occurrences`);
    results.us1.findings.push(`Collapsed inbox elements: ${hasCollapsedInbox}`);
    results.us1.findings.push(`Icons in inbox area: ${hasInboxIcon}`);
    
    if (inboxTextCount === 0) {
      results.us1.status = 'FAIL';
      results.us1.findings.push('ISSUE: No INBOX element found on page');
    } else if (inboxTextCount === 3) {
      results.us1.status = 'PARTIAL';
      results.us1.findings.push('NOTE: Found 3 INBOX occurrences - may indicate issue with rendering');
    } else {
      results.us1.status = 'PASS';
    }
    
    await page.screenshot({ path: path.join(screenshotsDir, 'detailed-us1.png'), fullPage: true });
    results.us1.screenshots.push('detailed-us1.png');

    // US2: Inbox Expansion
    console.log('
--- US2: Inbox Expansion/Collapse ---');
    let expandable = false;
    
    if (inboxTextCount > 0) {
      const inboxEl = page.getByText('INBOX').first();
      const isClickable = await inboxEl.evaluate(el => {
        const style = window.getComputedStyle(el);
        return style.cursor === 'pointer' || el.onclick !== null;
      }).catch(() => false);
      
      results.us2.findings.push(`Inbox appears clickable: ${isClickable}`);
      
      if (isClickable) {
        await inboxEl.click();
        await page.waitForTimeout(1000);
        await page.screenshot({ path: path.join(screenshotsDir, 'detailed-us2-clicked.png'), fullPage: true });
        results.us2.screenshots.push('detailed-us2-clicked.png');
        expandable = true;
      }
    }
    
    results.us2.status = expandable ? 'PASS' : 'FAIL';
    if (!expandable) {
      results.us2.findings.push('ISSUE: Inbox does not appear expandable/clickable');
    }

    // US3: Column Spacing
    console.log('
--- US3: Column Spacing ---');
    const columnHeaders = page.locator('div').filter({ hasText: /^(Discovery|Design|Sourcing|Production)/ });
    const colCount = await columnHeaders.count();
    
    results.us3.findings.push(`Columns found: ${colCount}`);
    
    if (colCount >= 4) {
      const boxes = [];
      for (let i = 0; i < 4; i++) {
        const box = await columnHeaders.nth(i).boundingBox();
        if (box) boxes.push(box);
      }
      
      const gaps = [];
      for (let i = 0; i < boxes.length - 1; i++) {
        const gap = boxes[i + 1].x - (boxes[i].x + boxes[i].width);
        gaps.push(gap);
        results.us3.findings.push(`Gap between column ${i} and ${i+1}: ${gap.toFixed(2)}px`);
      }
      
      if (gaps.length > 0) {
        const avgGap = gaps.reduce((a, b) => a + b) / gaps.length;
        const maxDiff = Math.max(...gaps.map(g => Math.abs(g - avgGap)));
        results.us3.findings.push(`Average gap: ${avgGap.toFixed(2)}px`);
        results.us3.findings.push(`Max deviation: ${maxDiff.toFixed(2)}px`);
        results.us3.status = maxDiff < 5 ? 'PASS' : 'PARTIAL';
      }
    } else {
      results.us3.status = 'FAIL';
      results.us3.findings.push('ISSUE: Could not find expected columns');
    }
    
    await page.screenshot({ path: path.join(screenshotsDir, 'detailed-us3.png'), fullPage: true });
    results.us3.screenshots.push('detailed-us3.png');

    // US4: Drag and Drop
    console.log('
--- US4: Drag and Drop ---');
    const draggableCount = await page.locator('[draggable="true"]').count();
    results.us4.findings.push(`Draggable elements: ${draggableCount}`);
    results.us4.status = draggableCount === 0 ? 'FAIL' : 'PASS';
    
    if (draggableCount === 0) {
      results.us4.findings.push('ISSUE: No draggable project cards found');
    }

    // US5: Stage Headers
    console.log('
--- US5: Stage Headers ---');
    const stageHeaders = await page.locator('div').filter({ hasText: // d+/ }).all();
    results.us5.findings.push(`Stage headers with count format: ${stageHeaders.length}`);
    
    for (let i = 0; i < Math.min(4, stageHeaders.length); i++) {
      const text = await stageHeaders[i].textContent();
      const bgColor = await stageHeaders[i].evaluate(el => window.getComputedStyle(el).backgroundColor);
      results.us5.findings.push(`Header ${i}: ${text.trim()} - BG: ${bgColor}`);
    }
    
    const addProjectBtns = await page.getByRole('button', { name: /add project/i }).count();
    results.us5.findings.push(`Add project buttons: ${addProjectBtns}`);
    results.us5.status = (stageHeaders.length >= 4 && addProjectBtns >= 4) ? 'PASS' : 'PARTIAL';

    // US6: Project Cards
    console.log('
--- US6: Project Cards ---');
    const cards = await page.locator('.bg-white.border').count();
    results.us6.findings.push(`Project cards found: ${cards}`);
    
    if (cards > 0) {
      const firstCard = page.locator('.bg-white.border').first();
      const cardText = await firstCard.textContent();
      const hasMetrics = cardText.includes('LF') || cardText.includes('days') || cardText.includes('milestones');
      results.us6.findings.push(`Card has metrics: ${hasMetrics}`);
      
      await firstCard.hover();
      await page.waitForTimeout(500);
      await page.screenshot({ path: path.join(screenshotsDir, 'detailed-us6-hover.png'), fullPage: true });
      results.us6.screenshots.push('detailed-us6-hover.png');
      
      results.us6.status = hasMetrics ? 'PASS' : 'PARTIAL';
    } else {
      results.us6.status = 'FAIL';
      results.us6.findings.push('ISSUE: No project cards found');
    }

    // US7: Customization
    console.log('
--- US7: Customization ---');
    const customizeBtn = page.getByRole('button', { name: /customize/i });
    const hasCustomize = await customizeBtn.count() > 0;
    results.us7.findings.push(`Customize button exists: ${hasCustomize}`);
    
    if (hasCustomize) {
      await customizeBtn.click();
      await page.waitForTimeout(1000);
      
      const slideOver = await page.locator('[role="dialog"]').count();
      const toggles = await page.locator('input[type="checkbox"]').count();
      
      results.us7.findings.push(`Slide-over panel opened: ${slideOver > 0}`);
      results.us7.findings.push(`Toggle options: ${toggles}`);
      
      await page.screenshot({ path: path.join(screenshotsDir, 'detailed-us7-panel.png'), fullPage: true });
      results.us7.screenshots.push('detailed-us7-panel.png');
      
      results.us7.status = (slideOver > 0 && toggles > 0) ? 'PASS' : 'PARTIAL';
      
      await page.keyboard.press('Escape');
      await page.waitForTimeout(500);
    } else {
      results.us7.status = 'FAIL';
    }

    // US8: Filtering
    console.log('
--- US8: Filtering ---');
    const personBtn = await page.getByRole('button', { name: /person/i }).count();
    const filterBtn = await page.getByRole('button', { name: /filter/i }).count();
    const sortBtn = await page.getByRole('button', { name: /sort/i }).count();
    
    results.us8.findings.push(`Person filter button: ${personBtn > 0 ? 'FOUND' : 'NOT FOUND'}`);
    results.us8.findings.push(`Filter button: ${filterBtn > 0 ? 'FOUND' : 'NOT FOUND'}`);
    results.us8.findings.push(`Sort button: ${sortBtn > 0 ? 'FOUND' : 'NOT FOUND'}`);
    
    const allFound = personBtn > 0 && filterBtn > 0 && sortBtn > 0;
    results.us8.status = allFound ? 'PASS' : 'PARTIAL';

    // Generate Report
    console.log('

========================================');
    console.log('USER STORY TEST RESULTS SUMMARY');
    console.log('========================================
');
    
    Object.entries(results).forEach(([key, value]) => {
      console.log(`[${value.status}] ${key.toUpperCase()}: ${value.title}`);
      value.findings.forEach(f => console.log(`  - ${f}`));
      console.log('');
    });

    // Write JSON report
    fs.writeFileSync(
      path.join(screenshotsDir, 'test-report.json'),
      JSON.stringify(results, null, 2)
    );

    console.log(`Full report saved to: ${path.join(screenshotsDir, 'test-report.json')}`);
    console.log(`Screenshots saved to: ${screenshotsDir}`);

  } catch (error) {
    console.error('Test error:', error);
    await page.screenshot({ path: path.join(screenshotsDir, 'error-detailed.png'), fullPage: true });
  } finally {
    await browser.close();
  }
}

detailedTests();