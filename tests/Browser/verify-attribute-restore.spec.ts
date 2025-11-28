import { test, expect } from '@playwright/test';

test.describe('Verify Attribute Restoration Fix', () => {
    test('should show restored attribute selections when editing quotation 300', async ({ page }) => {
        // Login first
        await page.goto('http://aureuserp.test/admin/login');
        await page.fill('input[type="email"]', 'info@tcswoodwork.com');
        await page.fill('input[type="password"]', 'Lola2024!');
        await page.click('button[type="submit"]');

        // Wait for login to complete - be flexible about where we land
        await page.waitForLoadState('networkidle');
        await page.waitForTimeout(2000);

        console.log('✅ Logged in successfully');
        console.log('Current URL:', page.url());

        // Navigate directly to edit quotation 300
        console.log('📝 Navigating to edit quotation 300...');
        await page.goto('http://aureuserp.test/admin/sale/orders/quotations/300/edit');
        await page.waitForLoadState('networkidle');
        await page.waitForTimeout(2000);

        console.log('Current URL:', page.url());

        // Take screenshot of the page
        await page.screenshot({ path: 'tests/Browser/verify-edit-page.png', fullPage: true });

        // Click on Order Lines tab if needed
        const orderLinesTab = page.locator('text=Order Lines');
        if (await orderLinesTab.isVisible()) {
            console.log('📋 Clicking Order Lines tab...');
            await orderLinesTab.click();
            await page.waitForTimeout(1000);
        }

        // Take screenshot after showing order lines
        await page.screenshot({ path: 'tests/Browser/verify-order-lines.png', fullPage: true });

        // Look for attribute fields - they should have values, not "Select an option"
        console.log('🔍 Checking for attribute field values...');

        // Get all select buttons that might be attribute fields
        const attributeButtons = page.locator('[data-field-wrapper*="attribute_"] button');
        const buttonCount = await attributeButtons.count();

        console.log(`Found ${buttonCount} attribute buttons`);

        if (buttonCount === 0) {
            console.log('⚠️  No attribute buttons found - might need to scroll or expand the line item');

            // Try to find and expand the repeater item
            const repeaterItems = page.locator('[data-sortable-handle]');
            const itemCount = await repeaterItems.count();
            console.log(`Found ${itemCount} repeater items`);

            if (itemCount > 0) {
                console.log('Clicking first repeater item to expand...');
                await repeaterItems.first().click();
                await page.waitForTimeout(1000);
                await page.screenshot({ path: 'tests/Browser/verify-expanded-item.png', fullPage: true });
            }
        }

        // Check attribute buttons again
        const attributeButtonsAfter = page.locator('[data-field-wrapper*="attribute_"] button');
        const finalButtonCount = await attributeButtonsAfter.count();
        console.log(`After expansion: ${finalButtonCount} attribute buttons`);

        let restoredCount = 0;
        for (let i = 0; i < finalButtonCount; i++) {
            const button = attributeButtonsAfter.nth(i);
            const buttonText = await button.textContent();
            console.log(`Attribute button ${i + 1}: "${buttonText}"`);

            if (buttonText && !buttonText.includes('Select an option')) {
                restoredCount++;
                console.log(`  ✅ Attribute ${i + 1} is RESTORED: ${buttonText}`);
            } else {
                console.log(`  ❌ Attribute ${i + 1} is NOT restored (shows "Select an option")`);
            }
        }

        // Final screenshot
        await page.screenshot({ path: 'tests/Browser/verify-final-state.png', fullPage: true });

        // Assertions
        expect(finalButtonCount).toBeGreaterThan(0); // Should have at least one attribute field
        expect(restoredCount).toBeGreaterThan(0); // At least one should be restored

        console.log(`\n✅ TEST PASSED: ${restoredCount} out of ${finalButtonCount} attributes are restored!`);
    });
});
