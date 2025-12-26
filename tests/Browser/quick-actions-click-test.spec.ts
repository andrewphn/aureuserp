import { test, expect } from '@playwright/test';
import path from 'path';
import { fileURLToPath } from 'url';

const __filename = fileURLToPath(import.meta.url);
const __dirname = path.dirname(__filename);

const BASE_URL = 'http://aureuserp.test';

test.use({ storageState: path.join(__dirname, 'auth-state.json') });

test('Click Project Card Opens Quick Actions Modal', async ({ page }) => {
    // Listen for console messages
    page.on('console', msg => {
        if (msg.type() === 'error') {
            console.log('Console error:', msg.text());
        }
    });

    console.log('Navigating to kanban...');
    await page.goto(`${BASE_URL}/admin/project/kanban`);
    await page.waitForLoadState('networkidle');
    await page.waitForTimeout(2000);

    // Verify we're on the kanban page
    const pageTitle = await page.title();
    console.log('Page title:', pageTitle);
    expect(pageTitle).toContain('Kanban');

    // Find project cards
    const projectCards = page.locator('[wire\\:click*="openQuickActions"]');
    const cardCount = await projectCards.count();
    console.log('Found project cards:', cardCount);
    expect(cardCount).toBeGreaterThan(0);

    // Check modal is NOT open before click
    const modal = page.locator('#kanban--quick-actions-modal');
    const modalExists = await modal.count() > 0;
    console.log('Modal element exists:', modalExists);

    if (modalExists) {
        const initialOpenState = await modal.evaluate(el => el.classList.contains('fi-modal-open'));
        console.log('Modal open before click:', initialOpenState);
    }

    // Take screenshot before click
    await page.screenshot({ path: 'tests/Browser/screenshots/before-card-click.png', fullPage: true });

    // Click the first project card
    console.log('Clicking first project card...');
    const firstCard = projectCards.first();
    const cardId = await firstCard.getAttribute('id');
    console.log('Card ID:', cardId);

    await firstCard.click();

    // Wait for Livewire to process and dispatch the event
    console.log('Waiting for Livewire response...');
    await page.waitForTimeout(3000);

    // Take screenshot after click
    await page.screenshot({ path: 'tests/Browser/screenshots/after-card-click.png', fullPage: true });

    // Check if modal opened
    const isModalOpen = await modal.evaluate(el => el.classList.contains('fi-modal-open'));
    console.log('Modal open after click:', isModalOpen);

    // Check Alpine state
    const alpineState = await modal.evaluate(el => {
        // @ts-ignore
        return el._x_dataStack?.[0]?.isOpen ?? 'undefined';
    });
    console.log('Alpine isOpen state:', alpineState);

    // Check if modal content is visible
    const modalContent = page.locator('#kanban--quick-actions-modal .fi-modal-content');
    const contentVisible = await modalContent.isVisible().catch(() => false);
    console.log('Modal content visible:', contentVisible);

    // If modal opened, verify content
    if (isModalOpen || contentVisible) {
        console.log('SUCCESS: Modal opened!');

        // Check for expected elements inside modal
        const stageSelector = page.locator('#kanban--quick-actions-modal select, #kanban--quick-actions-modal [wire\\:model*="stage"]');
        const hasStageSel = await stageSelector.count() > 0;
        console.log('Has stage selector:', hasStageSel);

        const blockedToggle = page.locator('#kanban--quick-actions-modal [wire\\:click*="toggleBlocked"], #kanban--quick-actions-modal input[type="checkbox"]');
        const hasBlocked = await blockedToggle.count() > 0;
        console.log('Has blocked toggle:', hasBlocked);
    } else {
        console.log('FAILED: Modal did not open');
        // Check for any errors in the page
        const errorMessage = await page.locator('.fi-notification').textContent().catch(() => null);
        if (errorMessage) {
            console.log('Notification:', errorMessage);
        }
    }

    // Assert modal opened
    expect(isModalOpen || contentVisible).toBe(true);
});
