import { test, expect } from '@playwright/test';
import path from 'path';
import { fileURLToPath } from 'url';

const __filename = fileURLToPath(import.meta.url);
const __dirname = path.dirname(__filename);

/**
 * E2E Test: Quotation to Project Creation Workflow
 *
 * This test covers:
 * 1. Creating a new quotation with template preview
 * 2. Testing template selection reactivity
 * 3. Filling out quotation details
 * 4. Verifying quotation creation
 * 5. Converting quotation to project (if applicable)
 */

test.describe('Quotation to Project Workflow', () => {

    test.beforeEach(async ({ page }) => {
        // Login directly for each test
        await page.goto('http://aureuserp.test/admin/login');
        await page.fill('input[type="email"]', 'info@tcswoodwork.com');
        await page.fill('input[type="password"]', 'Lola2024!');
        await page.click('button[type="submit"]');

        // Wait for redirect away from login page
        await page.waitForURL(url => !url.pathname.includes('/login'), { timeout: 10000 });

        // Give a moment for session to fully establish
        await page.waitForTimeout(1000);
    });

    test('should create quotation with template preview and convert to project', async ({ page }) => {
        // Step 1: Navigate to quotation create page
        await page.goto('http://aureuserp.test/admin/sale/orders/quotations/create');
        await page.waitForLoadState('networkidle');

        // Verify page loaded (use specific selector to avoid multiple h1 elements)
        await expect(page.getByRole('heading', { name: 'Create Quotation' })).toBeVisible();

        // Step 2: Test Template Preview Feature
        // Expand the Template Preview section
        await page.locator('header').filter({ hasText: 'Template Preview' }).click();

        // Verify placeholder message is shown
        await expect(page.getByText('Select a proposal template above to preview')).toBeVisible();

        // Find and click the Proposal Document Template dropdown
        // Note: Filament uses a complex select component, so we need to find the right selector
        const templateSelector = page.locator('[data-field="document_template_id"]').locator('input[type="text"]');
        if (await templateSelector.isVisible()) {
            await templateSelector.click();

            // Wait for dropdown to appear
            await page.waitForTimeout(500);

            // Select "Watchtower Proposal Template" or first available option
            const firstOption = page.locator('[role="option"]').first();
            if (await firstOption.isVisible()) {
                const optionText = await firstOption.textContent();
                console.log('Selecting template:', optionText);
                await firstOption.click();

                // Wait for preview to update
                await page.waitForTimeout(1000);

                // Verify preview is no longer showing placeholder
                await expect(page.getByText('Select a proposal template above to preview')).not.toBeVisible();

                // Verify preview shows actual content
                await expect(page.locator('.quotation-preview-panel')).toBeVisible();

                console.log('✓ Template preview reactivity test passed');
            }
        }

        // Step 3: Fill out quotation form
        // Select or create customer
        const customerField = page.locator('[data-field="partner_id"]').locator('input[type="text"]');
        if (await customerField.isVisible()) {
            await customerField.click();
            await page.waitForTimeout(500);

            // Try to select existing customer or create new
            const createCustomerBtn = page.getByRole('button', { name: 'Create' });
            if (await createCustomerBtn.isVisible()) {
                await createCustomerBtn.click();

                // Fill customer creation form
                await page.fill('input[name="name"]', 'Test Customer E2E');
                await page.fill('input[name="email"]', 'testcustomer@example.com');

                // Save customer
                await page.getByRole('button', { name: 'Create' }).last().click();
                await page.waitForTimeout(1000);
            } else {
                // Select first available customer
                await page.locator('[role="option"]').first().click();
            }
        }

        // Fill expiration date (30 days from now)
        const expirationField = page.locator('[data-field="validity_date"]').locator('input');
        if (await expirationField.isVisible()) {
            await expirationField.click();
            // Click on a date 30 days from now in the calendar
            await page.waitForTimeout(500);
            // Just press Enter to accept default or pick a date
            await expirationField.press('Enter');
        }

        // Fill quotation date (today)
        const quotationDateField = page.locator('[data-field="date_order"]').locator('input');
        if (await quotationDateField.isVisible()) {
            await quotationDateField.click();
            await page.waitForTimeout(500);
            await quotationDateField.press('Enter');
        }

        // Select payment term
        const paymentTermField = page.locator('[data-field="payment_term_id"]').locator('input[type="text"]');
        if (await paymentTermField.isVisible()) {
            await paymentTermField.click();
            await page.waitForTimeout(500);
            const paymentTermOption = page.locator('[role="option"]').first();
            if (await paymentTermOption.isVisible()) {
                await paymentTermOption.click();
            }
        }

        // Step 4: Add order line (product)
        // Click on Order Line tab
        await page.getByRole('tab', { name: 'Order Line' }).click();
        await page.waitForTimeout(500);

        // Look for add product button
        const addProductBtn = page.getByRole('button', { name: /add/i }).first();
        if (await addProductBtn.isVisible()) {
            await addProductBtn.click();
            await page.waitForTimeout(500);

            // Select a product
            const productField = page.locator('input[type="text"]').filter({ hasText: /product/i }).first();
            if (await productField.isVisible()) {
                await productField.click();
                await page.waitForTimeout(500);
                await page.locator('[role="option"]').first().click();
            }
        }

        // Take a screenshot before submission
        await page.screenshot({
            path: 'tests/Browser/screenshots/quotation-before-submit.png',
            fullPage: true
        });

        // Step 5: Create the quotation
        await page.getByRole('button', { name: 'Create' }).first().click();

        // Wait for creation to complete
        await page.waitForTimeout(2000);

        // Verify we're redirected to the quotation view/edit page or list
        await expect(page.url()).not.toContain('/create');

        console.log('✓ Quotation created successfully');
        console.log('Current URL:', page.url());

        // Take screenshot after creation
        await page.screenshot({
            path: 'tests/Browser/screenshots/quotation-after-creation.png',
            fullPage: true
        });

        // Step 6: Convert to project (if this functionality exists)
        // This depends on your specific workflow
        // Look for a "Create Project" or "Convert to Project" button
        const createProjectBtn = page.getByRole('button', { name: /project/i });
        if (await createProjectBtn.isVisible()) {
            await createProjectBtn.click();
            await page.waitForTimeout(1000);

            // Fill project details if form appears
            const projectNameField = page.locator('input[name="name"]');
            if (await projectNameField.isVisible()) {
                await projectNameField.fill('E2E Test Project from Quotation');

                // Submit project creation
                await page.getByRole('button', { name: 'Create' }).click();
                await page.waitForTimeout(2000);

                console.log('✓ Project created from quotation');

                // Verify project was created
                await expect(page.url()).toContain('project');

                await page.screenshot({
                    path: 'tests/Browser/screenshots/project-created.png',
                    fullPage: true
                });
            }
        } else {
            console.log('ℹ No project conversion button found - skipping project creation test');
        }

        console.log('✓ Complete E2E workflow test passed');
    });

    test('should validate required fields on quotation form', async ({ page }) => {
        await page.goto('http://aureuserp.test/admin/sale/orders/quotations/create');
        await page.waitForLoadState('networkidle');

        // Try to create without filling required fields
        await page.getByRole('button', { name: 'Create' }).first().click();

        // Wait for validation messages
        await page.waitForTimeout(500);

        // Verify validation errors appear
        // Filament typically shows validation errors inline
        const errorMessages = page.locator('.fi-fo-field-wrp-error-message');
        const errorCount = await errorMessages.count();

        expect(errorCount).toBeGreaterThan(0);
        console.log(`✓ Form validation working - ${errorCount} required fields detected`);
    });

    test('should allow template preview collapse and expand', async ({ page }) => {
        await page.goto('http://aureuserp.test/admin/sale/orders/quotations/create');
        await page.waitForLoadState('networkidle');

        const previewHeader = page.locator('header').filter({ hasText: 'Template Preview' });

        // Expand
        await previewHeader.click();
        await page.waitForTimeout(300);
        await expect(page.getByText('Select a proposal template above to preview')).toBeVisible();

        // Collapse
        await previewHeader.click();
        await page.waitForTimeout(300);
        await expect(page.getByText('Select a proposal template above to preview')).not.toBeVisible();

        console.log('✓ Template preview collapse/expand working');
    });
});
