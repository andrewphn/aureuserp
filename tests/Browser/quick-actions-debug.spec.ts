import { test, expect } from '@playwright/test';
import path from 'path';
import { fileURLToPath } from 'url';

const __filename = fileURLToPath(import.meta.url);
const __dirname = path.dirname(__filename);

const BASE_URL = 'http://aureuserp.test';

// Explicitly use auth state for this test
test.use({ storageState: path.join(__dirname, 'auth-state.json') });

test('Debug - Check Kanban page loads', async ({ page }) => {
    console.log('Navigating to kanban...');
    await page.goto(`${BASE_URL}/admin/project/kanban`);
    await page.waitForLoadState('networkidle');
    await page.waitForTimeout(3000);

    // Take screenshot
    await page.screenshot({ path: 'tests/Browser/screenshots/kanban-debug.png', fullPage: true });
    console.log('Screenshot saved: tests/Browser/screenshots/kanban-debug.png');

    // Log page URL
    console.log('Current URL:', page.url());

    // Check if we're redirected to login
    if (page.url().includes('login')) {
        console.log('ERROR: Redirected to login page - auth not working');
        throw new Error('Auth not working');
    }

    // Check page title
    const title = await page.title();
    console.log('Page title:', title);

    // Look for any project cards with different selectors
    const selectors = [
        '[wire\\:click*="openQuickActions"]',
        '.group.cursor-pointer',
        '[id]:not([id=""])[wire\\:click]',
        '.fi-section',
        '[class*="kanban"]',
    ];

    for (const selector of selectors) {
        const count = await page.locator(selector).count();
        console.log(`Selector "${selector}": ${count} elements`);
    }

    // Get all wire:click elements
    const wireClicks = await page.locator('[wire\\:click]').all();
    console.log(`Total wire:click elements: ${wireClicks.length}`);

    // Log first few wire:click attributes
    for (let i = 0; i < Math.min(5, wireClicks.length); i++) {
        const attr = await wireClicks[i].getAttribute('wire:click');
        console.log(`  ${i}: ${attr}`);
    }

    // Look for the kanban board container
    const kanbanBoard = page.locator('.kanban-board, [class*="kanban"]');
    const kanbanCount = await kanbanBoard.count();
    console.log(`Kanban containers: ${kanbanCount}`);

    // Get page HTML structure
    const bodyHtml = await page.locator('body').innerHTML();
    console.log('Body HTML length:', bodyHtml.length);

    // Check for project cards
    const projectCards = page.locator('.group.cursor-pointer');
    const cardCount = await projectCards.count();
    console.log(`Project cards (group cursor-pointer): ${cardCount}`);

    if (cardCount > 0) {
        const firstCard = projectCards.first();
        const firstCardHtml = await firstCard.innerHTML();
        console.log('First card HTML:', firstCardHtml.substring(0, 500));
    }
});
