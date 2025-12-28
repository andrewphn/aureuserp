import { chromium } from '@playwright/test';

const BASE_URL = 'http://aureuserp.test';

async function testSpecBuilder() {
    const browser = await chromium.launch({ headless: false, slowMo: 150 });
    const context = await browser.newContext({
        viewport: { width: 1400, height: 900 }
    });
    const page = await context.newPage();

    console.log('=== SPEC BUILDER AUTO-CALCULATE TEST ===\n');

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
        console.log('✓ Logged in\n');

        // Step 2: Go directly to Create Project URL
        console.log('Step 2: Going to Create Project...');
        await page.goto(`${BASE_URL}/admin/project/projects/create`);
        await page.waitForLoadState('networkidle');
        await page.waitForTimeout(4000);

        await page.screenshot({ path: 'test-screenshots/spec-01-step1.png', fullPage: true });
        console.log('✓ Create Project page loaded\n');

        // Step 3: Click directly on Step 2 tab "Scope & Budget"
        console.log('Step 3: Clicking on "Scope & Budget" tab...');

        // Try multiple selectors to find the Step 2 tab
        const step2Selectors = [
            'text=Scope & Budget',
            'text=2. Scope & Budget',
            'button:has-text("Scope")',
            '[role="tab"]:has-text("Scope")',
            'div:has-text("Scope & Budget")',
        ];

        let clicked = false;
        for (const selector of step2Selectors) {
            const element = page.locator(selector).first();
            if (await element.isVisible({ timeout: 2000 }).catch(() => false)) {
                console.log(`  Found element with: ${selector}`);
                await element.click({ force: true });
                clicked = true;
                break;
            }
        }

        if (!clicked) {
            console.log('  Could not find Step 2 tab, trying JavaScript click...');
            // Use JavaScript to click on the wizard step
            await page.evaluate(() => {
                const steps = document.querySelectorAll('[class*="wizard"] button, [class*="step"] button, [x-on\\:click*="step"]');
                for (const step of steps) {
                    if (step.textContent.includes('Scope')) {
                        step.click();
                        return true;
                    }
                }
                // Try clicking on any element with "Scope & Budget" text
                const scopeElements = document.querySelectorAll('*');
                for (const el of scopeElements) {
                    if (el.textContent && el.textContent.includes('Scope & Budget') && el.onclick) {
                        el.click();
                        return true;
                    }
                }
                return false;
            });
        }

        await page.waitForTimeout(4000);
        await page.screenshot({ path: 'test-screenshots/spec-02-after-click.png', fullPage: true });
        console.log('  Screenshot: spec-02-after-click.png\n');

        // Step 4: Check current page state
        console.log('Step 4: Analyzing page content...');

        const pageContent = await page.locator('body').textContent();

        // Check for Step 2 specific content
        const step2Keywords = ['Linear Feet', 'Estimate', 'Budget', 'Cabinet', 'Spec Builder', 'Opening', 'Room', 'Location'];
        console.log('\n  Step 2 content check:');
        for (const kw of step2Keywords) {
            console.log(`    ${kw}: ${pageContent.includes(kw) ? '✓' : '✗'}`);
        }

        // List all visible text blocks
        console.log('\n  Visible headings/sections:');
        const headings = await page.locator('h1, h2, h3, h4, h5, h6, [class*="title"], [class*="header"]').allTextContents();
        headings.filter(h => h.trim() && h.length < 60).slice(0, 15).forEach(h => {
            console.log(`    - ${h.trim()}`);
        });

        // List all buttons
        console.log('\n  All buttons:');
        const buttons = await page.locator('button').allTextContents();
        buttons.filter(b => b.trim() && b.length < 50).slice(0, 20).forEach(b => {
            console.log(`    - "${b.trim()}"`);
        });

        // Take more screenshots
        await page.evaluate(() => window.scrollTo(0, 0));
        await page.screenshot({ path: 'test-screenshots/spec-03-top.png', fullPage: true });

        await page.evaluate(() => window.scrollTo(0, 600));
        await page.screenshot({ path: 'test-screenshots/spec-04-mid.png', fullPage: true });

        await page.evaluate(() => window.scrollTo(0, 1200));
        await page.screenshot({ path: 'test-screenshots/spec-05-bottom.png', fullPage: true });

        // Step 5: Try to find and expand Spec Builder
        console.log('\nStep 5: Looking for Spec Builder...');

        const specBuilderBtn = page.locator('button:has-text("Spec Builder")').first();
        if (await specBuilderBtn.isVisible({ timeout: 3000 }).catch(() => false)) {
            console.log('  ✓ Found Spec Builder button - clicking...');
            await specBuilderBtn.click({ force: true });
            await page.waitForTimeout(2000);
            await page.screenshot({ path: 'test-screenshots/spec-06-spec-builder.png', fullPage: true });
            console.log('  Screenshot: spec-06-spec-builder.png');
        }

        // Look for Kitchen/Room buttons
        const kitchenBtn = page.locator('button:has-text("Kitchen")').first();
        if (await kitchenBtn.isVisible({ timeout: 2000 }).catch(() => false)) {
            console.log('  ✓ Found Kitchen button - clicking...');
            await kitchenBtn.click({ force: true });
            await page.waitForTimeout(2000);
            await page.screenshot({ path: 'test-screenshots/spec-07-kitchen.png', fullPage: true });
        }

        // Look for Add Opening/Add Room buttons
        const addButtons = ['Add Opening', 'Add Room', 'Add Location', 'Add Section', 'Add Cabinet'];
        for (const btnText of addButtons) {
            const btn = page.locator(`button:has-text("${btnText}")`).first();
            if (await btn.isVisible({ timeout: 1000 }).catch(() => false)) {
                console.log(`  ✓ Found "${btnText}" button`);
            }
        }

        console.log('\n=== EXPLORATION COMPLETE ===');
        console.log('\nBrowser will stay open for 90 seconds.');
        console.log('Manually click on "2. Scope & Budget" tab to see the spec builder.');
        await page.waitForTimeout(90000);

    } catch (error) {
        console.error('\nError:', error.message);
        await page.screenshot({ path: 'test-screenshots/spec-error.png', fullPage: true }).catch(() => {});
        console.log('\nLeaving browser open for 60s to inspect...');
        await page.waitForTimeout(60000);
    } finally {
        await browser.close();
    }
}

import { mkdir } from 'fs/promises';
await mkdir('test-screenshots', { recursive: true });

testSpecBuilder();
