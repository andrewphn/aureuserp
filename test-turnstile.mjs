/**
 * Turnstile CAPTCHA Test for tcswoodwork.com
 */

import { chromium } from '@playwright/test';

const BASE_URL = 'https://tcswoodwork.com';

async function testTurnstile() {
    console.log('🧪 Testing Turnstile CAPTCHA on tcswoodwork.com\n');

    const browser = await chromium.launch({ headless: false });
    const page = await browser.newPage({ viewport: { width: 1400, height: 900 } });

    await page.goto(`${BASE_URL}/contact`);
    await page.waitForTimeout(4000);

    // Fill all required fields
    console.log('📝 Filling form fields...');
    await page.fill('input[name="firstname"]', 'Test');
    await page.fill('input[name="lastname"]', 'User');
    await page.fill('input[name="email"]', 'test@example.com');
    await page.fill('input[name="phone"]', '5551234567');
    await page.check('input[name="processing_consent"]');

    await page.waitForTimeout(1000);

    // Force navigate to step 3 via Alpine with proper reactivity
    console.log('➡️ Force navigating to step 3 (Review)...');

    await page.evaluate(() => {
        // Find all x-data elements
        const formEl = document.querySelector('.contact-form-container');
        if (formEl && formEl._x_dataStack) {
            const data = formEl._x_dataStack[0];

            // Set required data to bypass validation
            data.formData.firstname = 'Test';
            data.formData.lastname = 'User';
            data.formData.email = 'test@example.com';
            data.formData.phone = '5551234567';
            data.formData.processing_consent = true;
            data.formData.project_type = ['Kitchen Cabinets'];
            data.formData.project_description = 'Test project';

            // Use Alpine's reactive setter
            data.currentStep = 3;

            // Force Alpine to re-evaluate
            formEl.dispatchEvent(new CustomEvent('x-refresh'));
        }

        // Also try direct DOM manipulation to show step 3
        const step3 = document.querySelector('[x-show="currentStep === 3"]');
        if (step3) {
            step3.style.display = 'block';
        }

        // Hide steps 1 and 2
        const step1 = document.querySelector('[x-show="currentStep === 1"]');
        const step2 = document.querySelector('[x-show="currentStep === 2"]');
        if (step1) step1.style.display = 'none';
        if (step2) step2.style.display = 'none';
    });

    await page.waitForTimeout(1000);

    // Trigger handleTurnstile after step is visible
    await page.evaluate(() => {
        const formEl = document.querySelector('.contact-form-container');
        if (formEl && formEl._x_dataStack) {
            const data = formEl._x_dataStack[0];
            if (typeof data.handleTurnstile === 'function') {
                data.handleTurnstile();
            }
        }
    });

    await page.waitForTimeout(3000);
    await page.screenshot({ path: 'test-screenshots/turnstile-step3.png', fullPage: true });
    console.log('📸 Screenshot: turnstile-step3.png');

    // Scroll to Turnstile area
    await page.evaluate(() => window.scrollTo(0, 800));
    await page.waitForTimeout(2000);

    await page.screenshot({ path: 'test-screenshots/turnstile-widget-area.png', fullPage: true });
    console.log('📸 Screenshot: turnstile-widget-area.png');

    // Check Turnstile status
    console.log('\n🔍 Checking Turnstile widget...');

    // The Turnstile widget is inside an iframe, we need to look for the widget container
    const turnstileContainer = page.locator('#turnstile-widget-container');
    const containerVisible = await turnstileContainer.isVisible().catch(() => false);
    console.log('   Turnstile container visible:', containerVisible);

    // Look for any div inside the container (Turnstile renders its own elements)
    const widgetInner = await turnstileContainer.locator('div').first().isVisible().catch(() => false);
    console.log('   Widget inner element:', widgetInner);

    // Check for iframe (Turnstile uses iframe)
    const turnstileIframe = page.frameLocator('iframe[src*="challenges.cloudflare"]');
    const iframeExists = await page.locator('iframe').count() > 1;
    console.log('   Turnstile iframe exists:', iframeExists);

    // Try to click the container area
    const checkboxVisible = containerVisible;

    if (checkboxVisible) {
        console.log('✅ Turnstile widget is rendering!');

        // Click the Turnstile widget container
        console.log('\n🖱️ Clicking Turnstile widget...');
        await turnstileContainer.click();
        await page.waitForTimeout(3000);

        await page.screenshot({ path: 'test-screenshots/turnstile-after-click.png', fullPage: true });

        // Check if verification passed
        const tokenValue = await page.evaluate(() => {
            const input = document.querySelector('input[name="cf-turnstile-response"]');
            return input ? input.value : '';
        });
        console.log('   Turnstile token:', tokenValue ? 'Generated (' + tokenValue.substring(0, 20) + '...)' : 'Empty');

        if (tokenValue) {
            console.log('\n📤 Submitting form...');
            await page.click('button:has-text("Submit")');
            await page.waitForTimeout(5000);

            await page.screenshot({ path: 'test-screenshots/turnstile-after-submit.png', fullPage: true });

            // Check result
            const currentUrl = page.url();
            const pageContent = await page.content();

            console.log('\n📋 Result:');
            console.log('   URL:', currentUrl);

            if (pageContent.includes('Thank you') || pageContent.includes('success') || pageContent.includes('received')) {
                console.log('✅ PASS: Form submitted successfully!');
            } else if (currentUrl.includes('contact')) {
                console.log('⚠️ Still on contact page - checking for errors...');
                const errors = await page.locator('.text-red-500, .text-red-600, [class*="error"]').allTextContents();
                if (errors.length > 0) {
                    console.log('   Errors found:', errors.join(', '));
                }
            }
        }
    } else {
        console.log('⚠️ Turnstile checkbox not visible');
    }

    console.log('\n📸 Screenshots saved to test-screenshots/');
    await page.waitForTimeout(3000);
    await browser.close();
}

testTurnstile().catch(console.error);
