#!/usr/bin/env node

import { chromium } from 'playwright';

const BASE_URL = 'http://aureuserp.test';

async function main() {
    const browser = await chromium.launch({
        headless: false,
        slowMo: 1000,
        args: ['--start-maximized']
    });

    const context = await browser.newContext({
        viewport: null
    });

    const page = await context.newPage();

    // Login
    await page.goto(`${BASE_URL}/admin/login`);
    await page.waitForLoadState('networkidle');

    if (page.url().includes('/login')) {
        await page.locator('input[type="email"]').first().fill('info@tcswoodwork.com');
        await page.locator('input[type="password"]').first().fill('Lola2024!');
        await page.click('button:has-text("Sign in")');
        await page.waitForLoadState('networkidle');
        await page.waitForTimeout(2000);
    }

    // Go to annotations page
    console.log('Navigating to annotations page...');
    await page.goto(`${BASE_URL}/admin/project/projects/1/pdf-review?pdf=1`);
    await page.waitForLoadState('networkidle');
    await page.waitForTimeout(5000);

    console.log('Current URL:', page.url());

    // Check browser console for JavaScript errors
    page.on('console', msg => {
        if (msg.type() === 'error') {
            console.log('❌ BROWSER ERROR:', msg.text());
        }
    });

    // Check for JavaScript errors
    const errors = [];
    page.on('pageerror', error => {
        console.log('❌ PAGE ERROR:', error.message);
        errors.push(error.message);
    });

    console.log('\nWaiting 10 seconds to capture any errors...');
    await page.waitForTimeout(10000);

    console.log('\n=== DIAGNOSIS ===');
    console.log('JavaScript Errors Found:', errors.length);
    if (errors.length > 0) {
        errors.forEach((err, i) => console.log(`${i + 1}. ${err}`));
    }

    console.log('\nBrowser staying open - manually test annotation tools');
    console.log('Press Ctrl+C when done\n');

    await page.waitForTimeout(3600000);
}

main().catch(console.error);
