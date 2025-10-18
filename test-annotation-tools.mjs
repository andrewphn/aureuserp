#!/usr/bin/env node

/**
 * Test and debug annotation viewer tools
 */

import { chromium } from 'playwright';

const BASE_URL = 'http://aureuserp.test';
const PROJECT_ID = 1;
const PDF_ID = 1;

async function main() {
    console.log('╔══════════════════════════════════════════════════════╗');
    console.log('║  TESTING ANNOTATION VIEWER TOOLS                      ║');
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
        console.log('📄 Opening annotation viewer...');
        await page.goto(`${BASE_URL}/admin/project/projects/${PROJECT_ID}/pdf-review?pdf=${PDF_ID}`);
        await page.waitForLoadState('networkidle');
        await page.waitForTimeout(5000);

        console.log('✓ Viewer opened');
        console.log('Current URL:', page.url());
        console.log();

        // Check what interface loaded
        console.log('🔍 Checking interface type...\n');

        // Check for annotation viewer toolbar
        const hasAnnotationToolbar = await page.locator('button[title*="Rectangle"], button[aria-label*="Rectangle"]').count() > 0;
        console.log('Annotation toolbar found:', hasAnnotationToolbar);

        // Check for wizard interface
        const hasWizardSteps = await page.locator('text=/Step \\d+/').count() > 0;
        console.log('Wizard steps found:', hasWizardSteps);

        // Check page title
        const title = await page.title();
        console.log('Page title:', title);

        // Check for FilamentPHP form elements
        const hasFilamentForm = await page.locator('.fi-form').count() > 0;
        console.log('Filament form found:', hasFilamentForm);

        console.log('\n' + '='.repeat(60));
        console.log('DIAGNOSIS');
        console.log('='.repeat(60) + '\n');

        if (page.url().includes('review-pdf-and-price')) {
            console.log('❌ ISSUE: Loaded wizard interface instead of annotation viewer');
            console.log('   URL redirected to: review-pdf-and-price');
            console.log('   Expected: pdf-review\n');
            console.log('SOLUTION:');
            console.log('   The annotation viewer might not be accessible directly.');
            console.log('   Try using the wizard interface instead:\n');
            console.log('   1. Use wizard for data entry (form-based)');
            console.log('   2. Or check if there\'s a button to switch to annotation view\n');
        } else if (hasAnnotationToolbar) {
            console.log('✅ Annotation viewer loaded correctly\n');
            console.log('Available annotation tools:');
            const buttons = await page.locator('button[title], button[aria-label]').all();
            for (const btn of buttons.slice(0, 15)) {
                const title = await btn.getAttribute('title') || await btn.getAttribute('aria-label') || '';
                if (title) console.log('   -', title);
            }
        } else {
            console.log('⚠️  Unknown interface loaded\n');
            console.log('Taking screenshot for analysis...');
            await page.screenshot({ path: 'annotation-viewer-debug.png', fullPage: true });
            console.log('Screenshot saved: annotation-viewer-debug.png\n');
        }

        console.log('='.repeat(60) + '\n');
        console.log('Browser will stay open for inspection. Press Ctrl+C when done.\n');

        // Keep browser open
        await page.waitForTimeout(3600000);

    } catch (error) {
        console.error('\n❌ Error:', error.message);
        await page.screenshot({ path: 'error-debug.png', fullPage: true });
    } finally {
        await browser.close();
    }
}

main();
