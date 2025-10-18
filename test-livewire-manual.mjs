#!/usr/bin/env node

/**
 * Manual Livewire Test - Keep browser open for inspection
 */

import { chromium } from '@playwright/test';

const TEST_URL = 'http://aureuserp.test/admin/project/projects/1/annotate-v2/1?pdf=1';

async function wait(ms) {
    return new Promise(resolve => setTimeout(resolve, ms));
}

async function manualTest() {
    console.log('🔍 Opening browser for manual inspection...\n');

    const browser = await chromium.launch({ headless: false, slowMo: 100 });
    const context = await browser.newContext();
    const page = await context.newPage();

    // Listen for network requests
    page.on('response', async (response) => {
        const url = response.url();
        if (url.includes('livewire') || url.includes('annotation')) {
            const status = response.status();
            console.log(`📡 ${status} ${url}`);

            if (status >= 400) {
                try {
                    const body = await response.text();
                    console.log(`   Error body: ${body.substring(0, 500)}...`);
                } catch (e) {
                    console.log(`   Could not read error body`);
                }
            }
        }
    });

    // Capture console
    page.on('console', msg => {
        const text = msg.text();
        if (text.includes('error') || text.includes('Error') || text.includes('❌') || text.includes('Livewire')) {
            console.log(`   [CONSOLE]: ${text}`);
        }
    });

    try {
        // Login
        console.log('🔐 Logging in...');
        await page.goto(TEST_URL, { waitUntil: 'networkidle' });
        await wait(2000);

        const isLoginPage = await page.locator('input[type="email"]').count() > 0;
        if (isLoginPage) {
            await page.fill('input[type="email"]', 'info@tcswoodwork.com');
            await page.fill('input[type="password"]', 'Lola2024!');
            await page.click('button[type="submit"]');
            await wait(3000);
            await page.goto(TEST_URL, { waitUntil: 'networkidle' });
            await wait(2000);
        }

        console.log('✓ Page loaded\n');
        console.log('🔍 Browser will stay open. Check the Network tab for Livewire requests.');
        console.log('   Try drawing an annotation and see what happens.\n');
        console.log('Press Ctrl+C when done...\n');

        // Keep alive
        await wait(300000); // 5 minutes

    } catch (error) {
        console.error('Error:', error.message);
    } finally {
        await browser.close();
    }
}

manualTest();
