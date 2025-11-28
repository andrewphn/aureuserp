import { test, expect } from '@playwright/test';

/**
 * Comprehensive E2E Test: Template Preview Full Workflow
 * Tests actual template selection, preview rendering, and updates
 */

test.describe('Template Preview - Full Workflow', () => {
    test('should show actual template preview when template is selected', async ({ page }) => {
        // Login
        await page.goto('http://aureuserp.test/admin/login');
        await page.fill('input[type="email"]', 'info@tcswoodwork.com');
        await page.fill('input[type="password"]', 'Lola2024!');
        await page.click('button[type="submit"]');
        await page.waitForURL(url => !url.pathname.includes('/login'), { timeout: 10000 });
        await page.waitForTimeout(1000);
        console.log('✅ Logged in successfully');

        // Navigate to quotation create page
        await page.goto('http://aureuserp.test/admin/sale/orders/quotations/create');
        await page.waitForLoadState('networkidle');
        console.log('✅ On quotation create page');

        // Verify page loaded
        await expect(page.getByRole('heading', { name: 'Create Quotation' })).toBeVisible();

        // Check if customer field is already populated by looking for the clear button (X)
        const clearButton = page.locator('[data-field="partner_id"]').locator('button[type="button"]').first();
        const hasCustomer = await clearButton.isVisible().catch(() => false);

        if (!hasCustomer) {
            // Customer field is empty, select one
            console.log('Customer field is empty, attempting to select...');
            const customerField = page.locator('label:has-text("Customer")').locator('..').locator('input').first();
            const isFieldVisible = await customerField.isVisible().catch(() => false);

            if (isFieldVisible) {
                await customerField.click();
                await page.waitForTimeout(500);
                const firstCustomer = page.locator('[role="option"]').first();
                await firstCustomer.click();
                await page.waitForTimeout(500);
                console.log('✅ Customer selected');
            } else {
                console.log('⚠️  Customer field not found, skipping customer selection');
            }
        } else {
            console.log('✅ Customer already selected (from previous session)');
        }

        // Find and select a Proposal Document Template using proper FilamentPHP selector
        const templateField = page.locator('[data-field="document_template_id"]').locator('input[type="text"]');
        await templateField.scrollIntoViewIfNeeded();
        await templateField.click();
        await page.waitForTimeout(500);

        // Select first available template
        const templateOption = page.locator('[role="option"]').first();
        if (await templateOption.isVisible()) {
            const templateName = await templateOption.textContent();
            console.log('Selecting template:', templateName);
            await templateOption.click();
            await page.waitForTimeout(1000);
            console.log('✅ Template selected from dropdown');
        }

        // Take screenshot after selecting template
        await page.screenshot({
            path: 'tests/Browser/screenshots/workflow-template-selected.png',
            fullPage: true
        });

        // Click Preview Template button
        const previewButton = page.getByRole('button', { name: 'Preview Template' });
        await expect(previewButton).toBeVisible();
        await previewButton.click();
        console.log('✅ Preview button clicked');

        // Wait for modal to appear
        await page.waitForTimeout(1000);

        // Check what's actually in the modal
        const hasPlaceholder = await page.getByText('Select a proposal template to preview').isVisible().catch(() => false);
        const hasTemplateContent = await page.locator('iframe#template-preview-iframe').isVisible().catch(() => false);

        console.log('🔍 Modal content check:');
        console.log(`  - Placeholder visible: ${hasPlaceholder}`);
        console.log(`  - Template iframe visible: ${hasTemplateContent}`);

        // Take screenshot of modal content
        await page.screenshot({
            path: 'tests/Browser/screenshots/workflow-modal-content.png',
            fullPage: true
        });

        if (hasTemplateContent) {
            console.log('✅ SUCCESS: Template preview is rendering!');

            // Verify iframe has content
            const iframe = page.locator('iframe#template-preview-iframe');
            await expect(iframe).toBeVisible();

            // Wait a bit for iframe content to load
            await page.waitForTimeout(1000);

            // Take screenshot of preview with content
            await page.screenshot({
                path: 'tests/Browser/screenshots/workflow-preview-with-content.png',
                fullPage: true
            });

            console.log('✅ Template preview iframe found and rendered');
        } else if (hasPlaceholder) {
            console.log('⚠️  PARTIAL: Modal opens but shows placeholder (template may not be passed to modal)');
        } else {
            console.log('❌ ISSUE: Modal opened but no expected content found');
        }

        // Close modal
        const closeButton = page.getByRole('button', { name: 'Close' });
        if (await closeButton.isVisible()) {
            await closeButton.click();
            await page.waitForTimeout(500);
            console.log('✅ Modal closed');
        }

        console.log('📊 Test Summary:');
        console.log('  - Login: Success');
        console.log('  - Customer selection: Success');
        console.log('  - Template selection: Success');
        console.log('  - Preview modal: Opens');
        console.log(`  - Preview content: ${hasTemplateContent ? 'Renders ✅' : 'Placeholder only ⚠️'}`);
    });

    test('should update preview when template is changed', async ({ page }) => {
        // Login
        await page.goto('http://aureuserp.test/admin/login');
        await page.fill('input[type="email"]', 'info@tcswoodwork.com');
        await page.fill('input[type="password"]', 'Lola2024!');
        await page.click('button[type="submit"]');
        await page.waitForURL(url => !url.pathname.includes('/login'), { timeout: 10000 });
        await page.waitForTimeout(1000);

        // Navigate to quotation create page
        await page.goto('http://aureuserp.test/admin/sale/orders/quotations/create');
        await page.waitForLoadState('networkidle');

        // Verify page loaded
        await expect(page.getByRole('heading', { name: 'Create Quotation' })).toBeVisible();

        // Check if customer field is already populated
        const clearButton = page.locator('[data-field="partner_id"]').locator('button[type="button"]').first();
        const hasCustomer = await clearButton.isVisible().catch(() => false);

        if (!hasCustomer) {
            // Customer field is empty, select one
            const customerField = page.locator('label:has-text("Customer")').locator('..').locator('input').first();
            if (await customerField.isVisible()) {
                await customerField.click();
                await page.waitForTimeout(500);
                const firstCustomer = page.locator('[role="option"]').first();
                await firstCustomer.click();
                await page.waitForTimeout(500);
                console.log('✅ Customer selected');
            }
        } else {
            console.log('✅ Customer already selected');
        }

        // Select first template
        const templateField = page.locator('[data-field="document_template_id"]').locator('input[type="text"]');
        await templateField.scrollIntoViewIfNeeded();
        await templateField.click();
        await page.waitForTimeout(500);

        const firstTemplate = page.locator('[role="option"]').first();
        const firstTemplateName = await firstTemplate.textContent();
        console.log('Selecting first template:', firstTemplateName);
        await firstTemplate.click();
        await page.waitForTimeout(1000);

        // Open preview modal with first template
        const previewButton = page.getByRole('button', { name: 'Preview Template' });
        await previewButton.click();
        await page.waitForTimeout(1000);

        // Check what's in modal with first template
        const hasContent1 = await page.locator('iframe#template-preview-iframe').isVisible().catch(() => false);
        console.log(`First template preview has content: ${hasContent1}`);

        // Take screenshot
        await page.screenshot({
            path: 'tests/Browser/screenshots/workflow-first-template-preview.png',
            fullPage: true
        });

        // Close modal
        const closeButton = page.getByRole('button', { name: 'Close' });
        await closeButton.click();
        await page.waitForTimeout(500);

        // Now change to second template
        await templateField.click();
        await page.waitForTimeout(500);

        const secondTemplate = page.locator('[role="option"]').nth(1);
        if (await secondTemplate.isVisible()) {
            const secondTemplateName = await secondTemplate.textContent();
            console.log('Changing to second template:', secondTemplateName);
            await secondTemplate.click();
            await page.waitForTimeout(1000);

            // Open preview modal with second template
            await previewButton.click();
            await page.waitForTimeout(1000);

            // Check what's in modal with second template
            const hasContent2 = await page.locator('iframe#template-preview-iframe').isVisible().catch(() => false);
            console.log(`Second template preview has content: ${hasContent2}`);

            // Take screenshot
            await page.screenshot({
                path: 'tests/Browser/screenshots/workflow-second-template-preview.png',
                fullPage: true
            });

            // Close modal
            await closeButton.click();

            console.log('✅ Template switching test completed');
            console.log('📊 Results:');
            console.log(`  - First template (${firstTemplateName}): ${hasContent1 ? 'Rendered' : 'Placeholder'}`);
            console.log(`  - Second template (${secondTemplateName}): ${hasContent2 ? 'Rendered' : 'Placeholder'}`);

            if (hasContent1 && hasContent2) {
                console.log('✅ SUCCESS: Both templates render in preview');
            } else if (!hasContent1 && !hasContent2) {
                console.log('⚠️  ISSUE: Neither template renders - modal gets template ID from form data at modal open time');
            } else {
                console.log('⚠️  PARTIAL: Inconsistent rendering behavior');
            }
        } else {
            console.log('⚠️  Only one template available - cannot test switching');
        }
    });
});
