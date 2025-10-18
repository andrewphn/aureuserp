#!/usr/bin/env node

/**
 * Debug 419 CSRF Errors
 */

import { chromium } from '@playwright/test';

async function debug419() {
    console.log('🔍 Debugging 419 CSRF Errors...\n');

    const browser = await chromium.launch({ headless: false });
    const context = await browser.newContext();
    const page = await context.newPage();

    // Log all network requests
    page.on('request', request => {
        if (request.url().includes('aureuserp.test')) {
            console.log(`→ ${request.method()} ${request.url()}`);
        }
    });

    // Log all responses with status codes
    page.on('response', response => {
        if (response.url().includes('aureuserp.test')) {
            const status = response.status();
            const emoji = status === 419 ? '❌' : status >= 400 ? '⚠️' : '✅';
            console.log(`${emoji} ${status} ${response.url()}`);
        }
    });

    // Log console errors
    page.on('console', msg => {
        if (msg.type() === 'error') {
            console.log(`🐛 Console Error: ${msg.text()}`);
        }
    });

    try {
        console.log('Step 1: Loading login page...\n');
        await page.goto('http://aureuserp.test/admin/login', {
            waitUntil: 'domcontentloaded',
            timeout: 60000
        });

        await page.waitForTimeout(2000);

        console.log('\nStep 2: Filling login form...\n');
        await page.fill('input[type="email"]', 'info@tcswoodwork.com');
        await page.fill('input[type="password"]', 'Lola2024!');

        console.log('Step 3: Submitting login...\n');
        await page.click('button[type="submit"]');

        await page.waitForTimeout(5000);

        console.log('\nStep 4: Navigating to projects...\n');
        await page.goto('http://aureuserp.test/admin/project/projects', {
            waitUntil: 'domcontentloaded',
            timeout: 60000
        });

        await page.waitForTimeout(10000);

        console.log('\n✅ Test complete - check logs above for 419 errors');

    } catch (error) {
        console.error('❌ Error:', error.message);
    } finally {
        await page.waitForTimeout(5000);
        await browser.close();
    }
}

debug419().catch(console.error);
