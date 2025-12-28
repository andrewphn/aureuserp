import { test, expect } from '@playwright/test';

test.describe('Debug Step 1', () => {
  test.use({ storageState: 'tests/Browser/auth-state.json' });

  test('debug Step 1 form structure', async ({ page }) => {
    await page.goto('http://aureuserp.test/admin/project/projects/create');
    await page.waitForLoadState('domcontentloaded');
    await page.waitForTimeout(3000);

    // Debug: Find all select-related buttons
    const info = await page.evaluate(() => {
      const results: any = { buttons: [], labels: [], selects: [] };

      // Find all buttons with "Select" in text
      const buttons = document.querySelectorAll('button');
      buttons.forEach(b => {
        if (b.textContent?.includes('Select')) {
          results.buttons.push({
            text: b.textContent?.trim().slice(0, 50),
            class: b.className.slice(0, 100),
            visible: b.offsetParent !== null
          });
        }
      });

      // Find all labels
      const labels = document.querySelectorAll('label');
      labels.forEach(l => {
        const text = l.textContent?.trim();
        if (text && ['Customer', 'Lead Source', 'Project Type'].some(t => text.includes(t))) {
          const parent = l.parentElement;
          const button = parent?.querySelector('button');
          results.labels.push({
            text,
            hasButton: !!button,
            buttonClass: button?.className.slice(0, 80)
          });
        }
      });

      // Find .fi-fo-select containers
      const selects = document.querySelectorAll('[class*="fi-fo-select"], [class*="select"]');
      results.selects.push(`Found ${selects.length} select containers`);

      return results;
    });

    console.log('Buttons with Select text:', JSON.stringify(info.buttons, null, 2));
    console.log('Labels:', JSON.stringify(info.labels, null, 2));
    console.log('Selects:', info.selects);

    await page.screenshot({ path: 'test-results/debug-step1.png', fullPage: true });
  });
});
