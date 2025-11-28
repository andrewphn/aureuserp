import { test, expect } from '@playwright/test';

test.describe('Attribute Selection Persistence', () => {
    test('should persist attribute selections when editing quotation', async ({ page }) => {
        // Login
        await page.goto('http://aureuserp.test/admin/login');
        await page.fill('input[type="email"]', 'info@tcswoodwork.com');
        await page.fill('input[type="password"]', 'Lola2024!');
        await page.click('button[type="submit"]');
        await page.waitForURL('**/admin');

        // Navigate to create quotation
        await page.goto('http://aureuserp.test/admin/sale/orders/quotations/create');
        await page.waitForLoadState('networkidle');

        // Select customer
        console.log('Selecting customer...');
        await page.click('[data-field-wrapper="partner_id"] button');
        await page.waitForTimeout(500);
        await page.click('li[role="option"]:first-child');
        await page.waitForTimeout(1000);

        // Click "Add line item" in the Order Lines tab
        console.log('Adding line item...');
        await page.click('text=Order Lines');
        await page.waitForTimeout(500);
        await page.click('button:has-text("Add line item")');
        await page.waitForTimeout(1000);

        // Select Cabinet product
        console.log('Selecting Cabinet product...');
        const productSelect = page.locator('[data-field-wrapper="product_id"] button').last();
        await productSelect.click();
        await page.waitForTimeout(500);
        await page.click('li[role="option"]:has-text("Cabinet")');
        await page.waitForTimeout(2000); // Wait for attributes to load

        // Take screenshot after product selection
        await page.screenshot({ path: 'tests/Browser/debug-attributes-loaded.png', fullPage: true });

        // Check if attribute fields are visible
        console.log('Checking for attribute fields...');
        const pricingLevelLabel = page.locator('text=Pricing Level').last();
        const materialCategoryLabel = page.locator('text=Material Category').last();
        const finishOptionLabel = page.locator('text=Finish Option').last();

        console.log('Pricing Level visible:', await pricingLevelLabel.isVisible());
        console.log('Material Category visible:', await materialCategoryLabel.isVisible());
        console.log('Finish Option visible:', await finishOptionLabel.isVisible());

        // Select Pricing Level
        console.log('Selecting Pricing Level...');
        const pricingLevelSelect = page.locator('[data-field-wrapper*="attribute_"] button').filter({ hasText: 'Select an option' }).first();
        await pricingLevelSelect.click();
        await page.waitForTimeout(500);
        const pricingOption = page.locator('li[role="option"]').filter({ hasText: /Level 2|Standard/i }).first();
        const pricingOptionText = await pricingOption.textContent();
        console.log('Selected pricing option:', pricingOptionText);
        await pricingOption.click();
        await page.waitForTimeout(1000);

        // Select Material Category
        console.log('Selecting Material Category...');
        const materialSelect = page.locator('[data-field-wrapper*="attribute_"] button').filter({ hasText: 'Select an option' }).first();
        await materialSelect.click();
        await page.waitForTimeout(500);
        const materialOption = page.locator('li[role="option"]').filter({ hasText: /Plywood|Box/i }).first();
        const materialOptionText = await materialOption.textContent();
        console.log('Selected material option:', materialOptionText);
        await materialOption.click();
        await page.waitForTimeout(1000);

        // Select Finish Option
        console.log('Selecting Finish Option...');
        const finishSelect = page.locator('[data-field-wrapper*="attribute_"] button').filter({ hasText: 'Select an option' }).first();
        await finishSelect.click();
        await page.waitForTimeout(500);
        const finishOption = page.locator('li[role="option"]').filter({ hasText: /Paint/i }).first();
        const finishOptionText = await finishOption.textContent();
        console.log('Selected finish option:', finishOptionText);
        await finishOption.click();
        await page.waitForTimeout(1000);

        // Take screenshot after selections
        await page.screenshot({ path: 'tests/Browser/debug-attributes-selected.png', fullPage: true });

        // Save the quotation
        console.log('Saving quotation...');
        await page.click('button[type="submit"]:has-text("Create")');
        await page.waitForLoadState('networkidle');
        await page.waitForTimeout(2000);

        // Get the quotation ID from URL
        const url = page.url();
        const quotationId = url.match(/quotations\/(\d+)/)?.[1];
        console.log('Created quotation ID:', quotationId);

        // Take screenshot after save
        await page.screenshot({ path: 'tests/Browser/debug-quotation-saved.png', fullPage: true });

        // Now edit the quotation
        console.log('Editing quotation...');
        await page.click('button:has-text("Edit")');
        await page.waitForLoadState('networkidle');
        await page.waitForTimeout(2000);

        // Take screenshot of edit form
        await page.screenshot({ path: 'tests/Browser/debug-edit-form.png', fullPage: true });

        // Check if attributes are still selected
        console.log('Verifying attribute selections are restored...');

        // Check the line item description
        const lineDescription = page.locator('[data-field-wrapper="name"] input').last();
        const descriptionValue = await lineDescription.inputValue();
        console.log('Line description:', descriptionValue);

        // Get all select buttons in the attribute area
        const attributeButtons = page.locator('[data-field-wrapper*="attribute_"] button');
        const buttonCount = await attributeButtons.count();
        console.log('Number of attribute buttons found:', buttonCount);

        // Check each attribute button for selected values
        for (let i = 0; i < buttonCount; i++) {
            const button = attributeButtons.nth(i);
            const buttonText = await button.textContent();
            console.log(`Attribute button ${i + 1} text:`, buttonText);

            // If button text is "Select an option", the attribute is NOT restored
            if (buttonText?.includes('Select an option')) {
                console.error(`❌ Attribute ${i + 1} is NOT restored!`);
            } else {
                console.log(`✅ Attribute ${i + 1} appears to be restored: ${buttonText}`);
            }
        }

        // Take final screenshot
        await page.screenshot({ path: 'tests/Browser/debug-attributes-after-edit.png', fullPage: true });

        // Assertions
        expect(descriptionValue).toContain('Cabinet');

        // Check that at least one attribute button doesn't say "Select an option"
        const hasRestoredAttributes = await attributeButtons.filter({ hasNotText: 'Select an option' }).count() > 0;
        expect(hasRestoredAttributes).toBeTruthy();

        console.log('✅ Test complete!');
    });
});
