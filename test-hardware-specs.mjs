import { chromium } from '@playwright/test';

const BASE_URL = 'http://aureuserp.test';

async function testHardwareSpecs() {
    const browser = await chromium.launch({ headless: false, slowMo: 300 });
    const context = await browser.newContext();
    const page = await context.newPage();

    console.log('=== HARDWARE SPECS USER STORY TEST ===\n');

    try {
        // Step 1: Login
        console.log('Step 1: Logging in...');
        await page.goto(`${BASE_URL}/admin/login`);
        await page.waitForLoadState('networkidle');

        const emailInput = page.locator('input[type="email"]').first();
        if (await emailInput.isVisible({ timeout: 3000 }).catch(() => false)) {
            await emailInput.fill('info@tcswoodwork.com');
            await page.locator('input[type="password"]').first().fill('Lola2024!');
            await page.locator('button[type="submit"]').first().click();
            await page.waitForTimeout(3000);
        }
        console.log('✓ Logged in - URL:', page.url());
        await page.screenshot({ path: 'test-screenshots/01-dashboard.png', fullPage: true });

        // Step 2: Go to Products page
        console.log('\nStep 2: Navigating to Products...');
        await page.goto(`${BASE_URL}/admin/inventory/products/products`);
        await page.waitForLoadState('networkidle');
        await page.waitForTimeout(2000);

        console.log('  Products page URL:', page.url());
        console.log('  Page title:', await page.title());
        await page.screenshot({ path: 'test-screenshots/02-products-page.png', fullPage: true });
        console.log('  Screenshot: 02-products-page.png');

        // Step 3: Search for slide products
        console.log('\nStep 3: Searching for drawer slides...');
        const searchInput = page.locator('input[type="search"]').first();
        if (await searchInput.isVisible({ timeout: 3000 }).catch(() => false)) {
            await searchInput.fill('slide');
            await page.waitForTimeout(2000);
            console.log('  Searched for "slide"');
        } else {
            // Try table filter
            const filterInput = page.locator('input[placeholder*="Search"], input[wire\\:model*="search"]').first();
            if (await filterInput.isVisible({ timeout: 2000 }).catch(() => false)) {
                await filterInput.fill('slide');
                await page.waitForTimeout(2000);
            }
        }
        await page.screenshot({ path: 'test-screenshots/03-search-slides.png', fullPage: true });
        console.log('  Screenshot: 03-search-slides.png');

        // Step 4: Click on a product to view/edit
        console.log('\nStep 4: Opening a product...');
        // Look for any product link in the table
        const productLinks = await page.locator('table tbody tr a, .fi-ta-text a').all();
        console.log(`  Found ${productLinks.length} product links`);

        if (productLinks.length > 0) {
            await productLinks[0].click();
            await page.waitForLoadState('networkidle');
            await page.waitForTimeout(2000);
            console.log('  Product page URL:', page.url());
            await page.screenshot({ path: 'test-screenshots/04-product-detail.png', fullPage: true });
            console.log('  Screenshot: 04-product-detail.png');

            // Step 5: Look for Attributes tab
            console.log('\nStep 5: Looking for Attributes tab...');
            const attributesTab = page.locator('a:has-text("Attributes"), button:has-text("Attributes"), [role="tab"]:has-text("Attributes")').first();
            if (await attributesTab.isVisible({ timeout: 3000 }).catch(() => false)) {
                await attributesTab.click();
                await page.waitForTimeout(2000);
                console.log('✓ Found and clicked Attributes tab');
                await page.screenshot({ path: 'test-screenshots/05-attributes-tab.png', fullPage: true });
                console.log('  Screenshot: 05-attributes-tab.png');

                // Step 6: Look for Add Attribute button
                console.log('\nStep 6: Looking for Add Attribute button...');
                const addBtn = page.locator('button:has-text("Add"), button:has-text("Create"), button:has-text("New")').first();
                if (await addBtn.isVisible({ timeout: 3000 }).catch(() => false)) {
                    await addBtn.click();
                    await page.waitForTimeout(2000);
                    console.log('✓ Clicked Add button');
                    await page.screenshot({ path: 'test-screenshots/06-add-attribute-modal.png', fullPage: true });
                    console.log('  Screenshot: 06-add-attribute-modal.png');

                    // Step 7: Try to create a new attribute
                    console.log('\nStep 7: Looking for attribute selector...');
                    const attrSelect = page.locator('select[name*="attribute"], [wire\\:model*="attribute"]').first();
                    if (await attrSelect.isVisible({ timeout: 2000 }).catch(() => false)) {
                        console.log('  Found attribute selector');
                    }

                    // Look for create option in select
                    const createOption = page.locator('button:has-text("Create"), a:has-text("Create new")').first();
                    if (await createOption.isVisible({ timeout: 2000 }).catch(() => false)) {
                        await createOption.click();
                        await page.waitForTimeout(2000);
                        await page.screenshot({ path: 'test-screenshots/07-create-attribute-form.png', fullPage: true });
                        console.log('  Screenshot: 07-create-attribute-form.png');
                    }
                }
            } else {
                console.log('  Attributes tab not visible. Available navigation:');
                const navItems = await page.locator('nav a, [role="tablist"] button').allTextContents();
                console.log('  ', navItems.slice(0, 10).join(', '));
            }
        } else {
            console.log('  No products found. Let me create one...');
            // Go to create product
            await page.goto(`${BASE_URL}/admin/inventory/products/products/create`);
            await page.waitForTimeout(2000);
            await page.screenshot({ path: 'test-screenshots/04-create-product.png', fullPage: true });
        }

        // Step 8: Test Project Spec Builder
        console.log('\nStep 8: Testing Spec Builder...');
        await page.goto(`${BASE_URL}/admin/project/projects/create`);
        await page.waitForLoadState('networkidle');
        await page.waitForTimeout(3000);
        console.log('  Spec Builder URL:', page.url());
        await page.screenshot({ path: 'test-screenshots/08-spec-builder.png', fullPage: true });
        console.log('  Screenshot: 08-spec-builder.png');

        // Look for Cabinets section
        const cabinetsSection = page.locator('text=Cabinets, :has-text("Add Cabinet")').first();
        if (await cabinetsSection.isVisible({ timeout: 3000 }).catch(() => false)) {
            console.log('✓ Found Cabinets section');
        }

        console.log('\n=== TEST COMPLETE ===');
        console.log('\nScreenshots saved in test-screenshots/');
        console.log('Browser will stay open for 60 seconds for manual inspection...');
        await page.waitForTimeout(60000);

    } catch (error) {
        console.error('\nError during test:', error.message);
        await page.screenshot({ path: 'test-screenshots/error.png', fullPage: true });
        console.log('Error screenshot saved: test-screenshots/error.png');
        await page.waitForTimeout(30000);
    } finally {
        await browser.close();
    }
}

// Create screenshots directory
import { mkdir } from 'fs/promises';
await mkdir('test-screenshots', { recursive: true });

testHardwareSpecs();
