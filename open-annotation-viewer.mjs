#!/usr/bin/env node

/**
 * Open the PDF annotation viewer for 25 Friendship Lane
 * URL: /pdf-review?pdf=1
 */

import { chromium } from 'playwright';

const BASE_URL = 'http://aureuserp.test';
const PROJECT_ID = 1;
const PDF_ID = 1;

async function main() {
    console.log('╔══════════════════════════════════════════════════════╗');
    console.log('║  OPENING PDF ANNOTATION VIEWER                        ║');
    console.log('╚══════════════════════════════════════════════════════╝\n');

    const browser = await chromium.launch({
        headless: false,
        slowMo: 500
    });

    const context = await browser.newContext({
        viewport: { width: 1920, height: 1080 }
    });

    const page = await context.newPage();

    try {
        // Login
        console.log('🔐 Logging in...');
        await page.goto(`${BASE_URL}/admin/login`);
        await page.waitForLoadState('networkidle');

        if (page.url().includes('/login')) {
            await page.locator('input[type="email"]').first().fill('info@tcswoodwork.com');
            await page.locator('input[type="password"]').first().fill('Lola2024!');
            await page.click('button:has-text("Sign in")');
            await page.waitForLoadState('networkidle');
            await page.waitForTimeout(2000);
        }
        console.log('✓ Logged in\n');

        // Navigate to annotation viewer
        console.log('📄 Opening PDF annotation viewer...');
        const annotationUrl = `${BASE_URL}/admin/project/projects/${PROJECT_ID}/pdf-review?pdf=${PDF_ID}`;
        console.log(`   URL: ${annotationUrl}\n`);

        await page.goto(annotationUrl);
        await page.waitForLoadState('networkidle');
        await page.waitForTimeout(5000);

        console.log('✓ Annotation viewer opened\n');
        console.log('Current URL:', page.url());

        console.log('\n' + '='.repeat(60));
        console.log('ANNOTATION VIEWER - WORKFLOW GUIDE');
        console.log('='.repeat(60) + '\n');

        console.log('You are now in the annotation viewer interface.\n');

        console.log('📋 WORKFLOW STEPS:\n');

        console.log('1. PAGE 1 - COVER PAGE');
        console.log('   - Current page should be page 1');
        console.log('   - Review cover page information');
        console.log('   - Use right sidebar to set page type if needed\n');

        console.log('2. NAVIGATE TO PAGE 2 (Floor Plan)');
        console.log('   - Use page input field in toolbar (top left)');
        console.log('   - OR use keyboard arrow keys');
        console.log('   - OR click next page button\n');

        console.log('3. FOR EACH FLOOR PLAN PAGE (2, 3, possibly 8):');
        console.log('   - Look for highlighted kitchen units');
        console.log('   - Use annotation tools to mark each kitchen');
        console.log('   - Create room annotations for each unit');
        console.log('   - Total goal: 7 kitchens across all pages\n');

        console.log('4. PAGES 4-7 (Elevations):');
        console.log('   - Review elevation drawings');
        console.log('   - Prepare for cabinet run entry');
        console.log('   - Note which kitchen each elevation belongs to\n');

        console.log('='.repeat(60) + '\n');
        console.log('Browser will stay open. Press Ctrl+C when done.\n');

        // Keep browser open
        await page.waitForTimeout(3600000); // 1 hour

    } catch (error) {
        console.error('\n❌ Error:', error.message);
        console.error(error.stack);
    } finally {
        await browser.close();
    }
}

main();
