#!/usr/bin/env node

/**
 * 25 Friendship Lane - Create 7 Kitchens Workflow
 * This script will guide through creating all 7 kitchen units
 */

import { chromium } from 'playwright';

const BASE_URL = 'http://aureuserp.test';
const PROJECT_ID = 1;
const PDF_ID = 1;

async function main() {
    console.log('╔══════════════════════════════════════════════════════╗');
    console.log('║  25 FRIENDSHIP LANE - CREATE 7 KITCHENS WORKFLOW     ║');
    console.log('╚══════════════════════════════════════════════════════╝\n');

    const browser = await chromium.launch({
        headless: false,
        slowMo: 800
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

        // Navigate to wizard
        console.log('📄 Opening wizard interface...');
        const wizardUrl = `${BASE_URL}/admin/project/projects/${PROJECT_ID}/review-pdf-and-price?pdf=${PDF_ID}`;
        await page.goto(wizardUrl);
        await page.waitForLoadState('networkidle');
        await page.waitForTimeout(3000);
        console.log('✓ Wizard loaded\n');

        console.log('Current URL:', page.url());
        console.log('\n' + '='.repeat(60));
        console.log('MANUAL WORKFLOW GUIDE');
        console.log('='.repeat(60) + '\n');

        console.log('The wizard is now open. Follow these steps:\n');

        console.log('📋 PAGE 1 - COVER PAGE');
        console.log('  1. Scroll to Page 1 section');
        console.log('  2. Set Page Type: Cover');
        console.log('  3. Expand "Cover Page Information"');
        console.log('  4. Verify project details');
        console.log('  5. Click Save/Next\n');

        console.log('📋 PAGE 2 - FLOOR PLAN (Multiple Kitchen Units)');
        console.log('  1. Examine the floor plan closely');
        console.log('  2. Count how many kitchen units are highlighted');
        console.log('  3. For EACH kitchen unit on this page:');
        console.log('     a. Click "New Room"');
        console.log('     b. Room Type: Kitchen');
        console.log('     c. Room Name: Kitchen 1, Kitchen 2, etc.');
        console.log('     d. Measure dimensions from PDF');
        console.log('     e. Add drawing number (if visible)');
        console.log('     f. Click "Add Another Room" for next unit\n');

        console.log('📋 PAGE 3 - FLOOR PLAN (More Kitchen Units)');
        console.log('  1. Repeat the same process as Page 2');
        console.log('  2. Continue numbering (Kitchen 4, Kitchen 5, etc.)');
        console.log('  3. Ensure total count is tracking toward 7 kitchens\n');

        console.log('📋 PAGES 4-7 - ELEVATIONS');
        console.log('  1. Set Page Type: Elevation');
        console.log('  2. Note which kitchen each elevation belongs to');
        console.log('  3. Save for Step 2 (Cabinet Run Entry)\n');

        console.log('📋 PAGE 8 - FINAL PAGE');
        console.log('  1. Check if floor plan or elevation');
        console.log('  2. Add any remaining kitchen units');
        console.log('  3. Ensure total = 7 kitchens\n');

        console.log('='.repeat(60));
        console.log('VERIFICATION CHECKLIST');
        console.log('='.repeat(60) + '\n');

        console.log('Before moving to Step 2 (Enter Pricing Details):');
        console.log('  ☐ Total of 7 kitchen rooms created');
        console.log('  ☐ All kitchens have dimensions (L × W × H)');
        console.log('  ☐ Drawing numbers added to notes');
        console.log('  ☐ Each kitchen linked to correct floor plan page\n');

        console.log('='.repeat(60) + '\n');
        console.log('Browser will stay open. Press Ctrl+C when done.\n');

        // Keep browser open indefinitely
        await page.waitForTimeout(3600000); // 1 hour

    } catch (error) {
        console.error('\n❌ Error:', error.message);
    } finally {
        await browser.close();
    }
}

main();
