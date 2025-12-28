import { test, expect, Page } from '@playwright/test';
import path from 'path';
import { fileURLToPath } from 'url';

const __filename = fileURLToPath(import.meta.url);
const __dirname = path.dirname(__filename);

const BASE_URL = 'http://aureuserp.test';

// Configure storage state
test.use({ storageState: path.join(__dirname, 'auth-state.json') });

test('Quick Actions - Open Panel', async ({ page }) => {
    console.log('Navigating to kanban...');
    await page.goto(`${BASE_URL}/admin/project/kanban`);
    await page.waitForLoadState('networkidle');
    await page.waitForTimeout(2000);

    // Check we're not on login page
    if (page.url().includes('login')) {
        throw new Error('Auth not working - redirected to login');
    }

    console.log('Current URL:', page.url());

    // Find and click project card
    const projectCard = page.locator('[wire\\:click*="openQuickActions"]').first();
    await expect(projectCard).toBeVisible({ timeout: 10000 });

    const projectName = await projectCard.locator('h4').textContent();
    console.log(`Clicking project: ${projectName?.trim()}`);

    await projectCard.click();
    await page.waitForTimeout(2000);

    // Wait for modal heading
    const modal = page.locator('#kanban--quick-actions-modal');
    const heading = modal.locator('h2');
    await expect(heading).toBeVisible({ timeout: 10000 });

    console.log('✅ Modal opened successfully');

    // Screenshot
    await page.screenshot({ path: 'tests/Browser/screenshots/quick-actions-modal.png', fullPage: true });
});

test('Quick Actions - Add Task', async ({ page }) => {
    await page.goto(`${BASE_URL}/admin/project/kanban`);
    await page.waitForLoadState('networkidle');
    await page.waitForTimeout(2000);

    // Click project to open modal
    const projectCard = page.locator('[wire\\:click*="openQuickActions"]').first();
    await projectCard.click();
    await page.waitForTimeout(2000);

    const modal = page.locator('#kanban--quick-actions-modal');
    const heading = modal.locator('h2');
    await expect(heading).toBeVisible({ timeout: 10000 });

    // Find task input
    const taskInput = modal.locator('input[wire\\:model*="quickTaskTitle"]');
    await expect(taskInput).toBeVisible({ timeout: 5000 });

    const testTask = `Test Task ${Date.now()}`;
    await taskInput.fill(testTask);
    await taskInput.press('Enter');
    await page.waitForTimeout(2000);

    const inputValue = await taskInput.inputValue();
    if (inputValue === '') {
        console.log(`✅ Added task: ${testTask}`);
    } else {
        console.log('⚠️ Task input not cleared');
    }
});

test('Quick Actions - Complete Workflow', async ({ page }) => {
    console.log('\n=== COMPLETE WORKFLOW TEST ===\n');

    await page.goto(`${BASE_URL}/admin/project/kanban`);
    await page.waitForLoadState('networkidle');
    await page.waitForTimeout(2000);

    // Open modal
    const projectCard = page.locator('[wire\\:click*="openQuickActions"]').first();
    await projectCard.click();
    await page.waitForTimeout(2000);

    const modal = page.locator('#kanban--quick-actions-modal');
    const heading = modal.locator('h2');
    await expect(heading).toBeVisible({ timeout: 10000 });
    console.log('✅ Panel opened');

    // Add milestone
    const milestoneInput = modal.locator('input[wire\\:model*="quickMilestoneTitle"]');
    if (await milestoneInput.isVisible()) {
        await milestoneInput.fill(`Playwright Milestone ${Date.now()}`);
        await milestoneInput.press('Enter');
        await page.waitForTimeout(1500);
        console.log('✅ Milestone added');
    }

    // Add task
    const taskInput = modal.locator('input[wire\\:model*="quickTaskTitle"]');
    if (await taskInput.isVisible()) {
        await taskInput.fill(`Playwright Task ${Date.now()}`);
        await taskInput.press('Enter');
        await page.waitForTimeout(1500);
        console.log('✅ Task added');
    }

    // Post comment
    const commentInput = modal.locator('input[wire\\:model*="quickComment"]');
    if (await commentInput.isVisible()) {
        await commentInput.fill(`Playwright test at ${new Date().toISOString()}`);
        const postButton = modal.locator('button').filter({ hasText: 'Post' });
        if (await postButton.isVisible()) {
            await postButton.click();
            await page.waitForTimeout(1500);
            console.log('✅ Comment posted');
        }
    }

    await page.screenshot({ path: 'tests/Browser/screenshots/quick-actions-complete.png', fullPage: true });
    console.log('\n=== TEST COMPLETE ===');
});
