import { chromium } from '@playwright/test';
import path from 'path';
import { fileURLToPath } from 'url';
import fs from 'fs';

const __filename = fileURLToPath(import.meta.url);
const __dirname = path.dirname(__filename);

const screenshotsDir = path.join(__dirname, 'tests/Browser/screenshots/kanban-user-stories');
fs.mkdirSync(screenshotsDir, { recursive: true });

async function runTests() {
  const browser = await chromium.launch({ headless: false });
  const context = await browser.newContext({
    storageState: 'tests/Browser/auth-state.json'
  });
  const page = await context.newPage();

  try {
    console.log('Testing Project Kanban Board...');
    await page.goto('http://aureuserp.test/admin/project/kanban');
    await page.waitForLoadState('networkidle');
    await page.waitForTimeout(2000);

    console.log('US1: Inbox Visibility');
    await page.screenshot({ path: path.join(screenshotsDir, 'us1-initial.png'), fullPage: true });
    
    const inboxCount = await page.getByText('INBOX').count();
    console.log('INBOX elements:', inboxCount);

    console.log('US2: Checking Add Project buttons');
    const addBtns = await page.getByRole('button', { name: /add project/i }).count();
    console.log('Add project buttons:', addBtns);

    console.log('US3: Column spacing');
    await page.screenshot({ path: path.join(screenshotsDir, 'us3-columns.png'), fullPage: true });

    console.log('US4: Draggable cards');
    const draggable = await page.locator('[draggable]').count();
    console.log('Draggable elements:', draggable);

    console.log('US5: Stage headers');
    await page.screenshot({ path: path.join(screenshotsDir, 'us5-headers.png'), fullPage: true });

    console.log('US6: Project cards');
    await page.screenshot({ path: path.join(screenshotsDir, 'us6-cards.png'), fullPage: true });

    console.log('US7: Customization');
    const customizeBtn = page.getByRole('button', { name: /customize/i });
    if (await customizeBtn.count() > 0) {
      await customizeBtn.click();
      await page.waitForTimeout(1000);
      await page.screenshot({ path: path.join(screenshotsDir, 'us7-customize.png'), fullPage: true });
      await page.keyboard.press('Escape');
    }

    console.log('US8: Filters');
    await page.screenshot({ path: path.join(screenshotsDir, 'us8-filters.png'), fullPage: true });

    console.log('Tests complete. Screenshots saved to:', screenshotsDir);

  } catch (error) {
    console.error('Error:', error);
    await page.screenshot({ path: path.join(screenshotsDir, 'error.png'), fullPage: true });
  } finally {
    await browser.close();
  }
}

runTests();