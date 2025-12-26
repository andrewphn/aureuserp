import { test, expect } from '@playwright/test';
import path from 'path';
import { fileURLToPath } from 'url';

const __filename = fileURLToPath(import.meta.url);
const __dirname = path.dirname(__filename);

const BASE_URL = 'http://aureuserp.test';

test.use({ storageState: path.join(__dirname, 'auth-state.json') });

test('Right-click context menu on Kanban card', async ({ page }) => {
    console.log('Navigating to kanban...');
    await page.goto(`${BASE_URL}/admin/project/kanban`);
    await page.waitForLoadState('networkidle');
    await page.waitForTimeout(2000);

    // Verify we're on the kanban page
    const pageTitle = await page.title();
    expect(pageTitle).toContain('Kanban');

    // Find project cards
    const projectCards = page.locator('[wire\\:click*="openQuickActions"]');
    const cardCount = await projectCards.count();
    console.log('Found project cards:', cardCount);
    expect(cardCount).toBeGreaterThan(0);

    // Get the first card ID
    const firstCard = projectCards.first();
    const cardId = await firstCard.getAttribute('id');
    console.log('First card ID:', cardId);

    // Right-click on first card
    console.log('Right-clicking first project card...');
    await firstCard.click({ button: 'right' });
    await page.waitForTimeout(1000);

    // Take screenshot
    await page.screenshot({ path: 'tests/Browser/screenshots/context-menu-open.png', fullPage: false });

    // Debug: Check Alpine data on the card
    const alpineData = await firstCard.evaluate((el) => {
        // @ts-ignore
        return el._x_dataStack ? JSON.stringify(el._x_dataStack[0]) : 'no alpine data';
    });
    console.log('Alpine data on card:', alpineData);

    // Check if context menu appeared using the card's x-data showMenu
    const showMenuState = await firstCard.evaluate((el) => {
        // @ts-ignore
        return el._x_dataStack?.[0]?.showMenu;
    });
    console.log('showMenu state:', showMenuState);
    expect(showMenuState).toBe(true);

    // Scope selectors to the first card
    const quickActionsBtn = firstCard.locator('button:has-text("Quick Actions")');
    const editLink = firstCard.locator('a:has-text("Edit Project")');
    const messagesBtn = firstCard.locator('button:has-text("Messages")');
    const viewLink = firstCard.locator('a:has-text("View Full Page")');
    const blockedBtn = firstCard.locator('button:has-text("Blocked"), button:has-text("Unblock")');

    // Wait for menu to be visible
    await quickActionsBtn.waitFor({ state: 'visible', timeout: 5000 });

    expect(await quickActionsBtn.isVisible()).toBe(true);
    console.log('Quick Actions button visible');

    expect(await editLink.isVisible()).toBe(true);
    console.log('Edit Project link visible');

    expect(await messagesBtn.isVisible()).toBe(true);
    console.log('Messages button visible');

    expect(await viewLink.isVisible()).toBe(true);
    console.log('View Full Page link visible');

    expect(await blockedBtn.isVisible()).toBe(true);
    console.log('Blocked toggle button visible');

    // Take screenshot with menu visible
    await page.screenshot({ path: 'tests/Browser/screenshots/context-menu-visible.png', fullPage: false });

    // Test clicking Quick Actions from context menu
    console.log('Clicking Quick Actions from context menu...');
    await quickActionsBtn.click();
    await page.waitForTimeout(1500);

    // Verify modal opened
    const modal = page.locator('#kanban--quick-actions-modal');
    const modalOpen = await modal.evaluate(el => el.classList.contains('fi-modal-open'));
    console.log('Quick Actions modal opened:', modalOpen);
    expect(modalOpen).toBe(true);

    // Take final screenshot
    await page.screenshot({ path: 'tests/Browser/screenshots/context-menu-quick-actions.png', fullPage: false });

    console.log('Context menu test passed!');
});

test('Context menu closes on click away', async ({ page }) => {
    await page.goto(`${BASE_URL}/admin/project/kanban`);
    await page.waitForLoadState('networkidle');
    await page.waitForTimeout(2000);

    // Right-click on first card
    const firstCard = page.locator('[wire\\:click*="openQuickActions"]').first();
    await firstCard.click({ button: 'right' });
    await page.waitForTimeout(500);

    // Verify menu is visible (scoped to first card)
    const quickActionsBtn = firstCard.locator('button:has-text("Quick Actions")');
    await quickActionsBtn.waitFor({ state: 'visible', timeout: 5000 });
    expect(await quickActionsBtn.isVisible()).toBe(true);
    console.log('Menu visible after right-click');

    // Click somewhere else (on the header)
    await page.locator('h1:has-text("Project Kanban Board")').click();
    await page.waitForTimeout(500);

    // Verify menu is no longer visible
    const showMenuState = await firstCard.evaluate((el) => {
        // @ts-ignore
        return el._x_dataStack?.[0]?.showMenu;
    });
    console.log('showMenu state after click away:', showMenuState);
    expect(showMenuState).toBe(false);

    console.log('Click away test passed!');
});
