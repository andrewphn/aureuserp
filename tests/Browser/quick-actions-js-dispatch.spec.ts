import { test, expect } from '@playwright/test';
import path from 'path';
import { fileURLToPath } from 'url';

const __filename = fileURLToPath(import.meta.url);
const __dirname = path.dirname(__filename);

const BASE_URL = 'http://aureuserp.test';

test.use({ storageState: path.join(__dirname, 'auth-state.json') });

test('Test Modal Opens with JS Dispatch', async ({ page }) => {
    await page.goto(`${BASE_URL}/admin/project/kanban`);
    await page.waitForLoadState('networkidle');
    await page.waitForTimeout(3000);

    console.log('Page loaded. Trying to open modal via JS...');

    // Try opening the modal directly via JavaScript
    await page.evaluate(() => {
        // Dispatch the open-modal event that Filament modals listen for
        window.dispatchEvent(new CustomEvent('open-modal', {
            detail: { id: 'kanban--quick-actions-modal' }
        }));
    });

    await page.waitForTimeout(2000);

    // Check if modal is visible now
    const modal = page.locator('#kanban--quick-actions-modal');
    const hasOpenClass = await modal.evaluate(el => el.classList.contains('fi-modal-open'));
    console.log('Modal has fi-modal-open class:', hasOpenClass);

    // Check the x-show state
    const isShown = await modal.evaluate(el => {
        // @ts-ignore
        return el._x_dataStack?.[0]?.isOpen ?? 'undefined';
    });
    console.log('Alpine isOpen state:', isShown);

    // Take screenshot
    await page.screenshot({ path: 'tests/Browser/screenshots/js-dispatch-test.png', fullPage: true });

    // Now try clicking the card
    console.log('\nTrying to click project card...');
    const projectCard = page.locator('[wire\\:click*="openQuickActions"]').first();
    await projectCard.click();
    await page.waitForTimeout(3000);

    // Check modal state again
    const hasOpenClass2 = await modal.evaluate(el => el.classList.contains('fi-modal-open'));
    console.log('After click - Modal has fi-modal-open class:', hasOpenClass2);

    // Take another screenshot
    await page.screenshot({ path: 'tests/Browser/screenshots/after-click-test.png', fullPage: true });

    // Check for any console errors
    page.on('console', msg => {
        if (msg.type() === 'error') {
            console.log('Console error:', msg.text());
        }
    });
});
