#!/usr/bin/env node

/**
 * Capture all 8 PDF pages from the CORRECT annotation viewer interface
 * Uses the page number input field in the toolbar to navigate
 */

import { chromium } from 'playwright';
import * as fs from 'fs';
import * as path from 'path';

const BASE_URL = 'http://aureuserp.test';
const PROJECT_ID = 1;
const PDF_ID = 1;
const SCREENSHOT_DIR = 'test-screenshots/pdf-pages-correct';

// Create screenshot directory
if (!fs.existsSync(SCREENSHOT_DIR)) {
    fs.mkdirSync(SCREENSHOT_DIR, { recursive: true });
}

async function main() {
    console.log('╔════════════════════════════════════════════════════════╗');
    console.log('║  CAPTURING PDF PAGES - CORRECT ANNOTATION INTERFACE   ║');
    console.log('╚════════════════════════════════════════════════════════╝\n');

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
            const emailField = page.locator('input[type="email"]').first();
            const passwordField = page.locator('input[type="password"]').first();
            await emailField.fill('info@tcswoodwork.com');
            await passwordField.fill('Lola2024!');
            await page.click('button:has-text("Sign in")');
            await page.waitForLoadState('networkidle');
            await page.waitForTimeout(2000);
        }
        console.log('✓ Logged in\n');

        // Navigate directly to the project edit page first, then click PDF review link
        console.log('📄 Navigating to project...');
        await page.goto(`${BASE_URL}/admin/project/projects/${PROJECT_ID}`);
        await page.waitForLoadState('networkidle');
        await page.waitForTimeout(2000);

        // Look for a link or button to open the PDF annotation viewer
        // Try multiple possible selectors
        console.log('🔍 Looking for PDF annotation viewer link...\n');

        const possibleSelectors = [
            `a[href*="pdf-review"]`,
            `a[href*="pdf=${PDF_ID}"]`,
            'button:has-text("Review PDF")',
            'a:has-text("Annotate")',
            'a:has-text("PDF")'
        ];

        let foundLink = false;
        for (const selector of possibleSelectors) {
            const link = page.locator(selector).first();
            if (await link.isVisible({ timeout: 2000 }).catch(() => false)) {
                console.log(`✓ Found link with selector: ${selector}`);
                await link.click();
                await page.waitForLoadState('networkidle');
                await page.waitForTimeout(3000);
                foundLink = true;
                break;
            }
        }

        if (!foundLink) {
            console.log('⚠️  Could not find PDF review link, trying direct URL...\n');
            await page.goto(`${BASE_URL}/admin/project/projects/${PROJECT_ID}/pdf-review?pdf=${PDF_ID}`);
            await page.waitForLoadState('networkidle');
            await page.waitForTimeout(5000);
        }

        console.log(`📍 Current URL: ${page.url()}\n`);

        // Check for the annotation viewer toolbar (black background with tools)
        const hasToolbar = await page.locator('input[type="number"]').first().isVisible({ timeout: 5000 }).catch(() => false);

        if (!hasToolbar) {
            console.log('❌ ERROR: Not on the annotation viewer interface!');
            console.log('   Expected: Black toolbar with page number input');
            console.log('   Current URL:', page.url());

            await page.screenshot({
                path: path.join(SCREENSHOT_DIR, 'wrong-interface.png'),
                fullPage: true
            });

            throw new Error('Wrong interface loaded - check wrong-interface.png');
        }

        console.log('✓ Confirmed: Annotation viewer with toolbar detected\n');

        // Take initial screenshot
        const initialPath = path.join(SCREENSHOT_DIR, '00-annotation-viewer.png');
        await page.screenshot({
            path: initialPath,
            fullPage: false
        });
        console.log(`📸 Initial interface: ${initialPath}\n`);

        // Find the page number input field in the toolbar
        const pageInput = page.locator('input[type="number"]').first();
        console.log('✓ Found page number input field\n');

        // Capture all 8 pages
        for (let pageNum = 1; pageNum <= 8; pageNum++) {
            console.log(`\n━━━ CAPTURING PAGE ${pageNum}/8 ━━━`);

            // Navigate to page using the input field
            if (pageNum > 1) {
                console.log(`  📝 Setting page input to ${pageNum}...`);
                await pageInput.fill(String(pageNum));
                await page.keyboard.press('Enter');
                console.log(`  ⏳ Waiting for page to load...`);
                await page.waitForTimeout(4000); // Wait for PDF to render
            }

            // Verify we're on the correct page by reading the input value
            const currentPage = await pageInput.inputValue();
            console.log(`  ✓ Current page: ${currentPage}`);

            // Capture full viewport
            const screenshotPath = path.join(SCREENSHOT_DIR, `page-${pageNum}-full.png`);
            await page.screenshot({
                path: screenshotPath,
                fullPage: false
            });
            console.log(`  📸 Full viewport: ${screenshotPath}`);

            // Capture just the PDF canvas (higher quality)
            try {
                const pdfCanvas = page.locator('canvas').first();
                if (await pdfCanvas.isVisible({ timeout: 2000 })) {
                    const canvasPath = path.join(SCREENSHOT_DIR, `page-${pageNum}-canvas.png`);
                    await pdfCanvas.screenshot({ path: canvasPath });
                    console.log(`  📸 PDF canvas: ${canvasPath}`);
                }
            } catch (err) {
                console.log(`  ⚠️  Could not capture canvas for page ${pageNum}`);
            }

            // Capture the right sidebar
            try {
                const sidebar = page.locator('.fi-aside, [class*="sidebar"]').first();
                if (await sidebar.isVisible({ timeout: 1000 })) {
                    const sidebarPath = path.join(SCREENSHOT_DIR, `page-${pageNum}-sidebar.png`);
                    await sidebar.screenshot({ path: sidebarPath });
                    console.log(`  📸 Sidebar: ${sidebarPath}`);
                }
            } catch (err) {
                console.log(`  ⚠️  Could not capture sidebar for page ${pageNum}`);
            }
        }

        console.log('\n\n╔════════════════════════════════════════════════════════╗');
        console.log('║  ✅ ALL 8 PAGES CAPTURED FROM ANNOTATION VIEWER        ║');
        console.log('╚════════════════════════════════════════════════════════╝\n');
        console.log(`📁 Screenshots: ${SCREENSHOT_DIR}/\n`);
        console.log('Now we can analyze the REAL page content!\n');

        // Keep browser open for review
        console.log('Browser will stay open for 30 seconds...');
        await page.waitForTimeout(30000);

    } catch (error) {
        console.error('\n❌ Error:', error.message);
        console.error('Stack:', error.stack);

        const errorPath = path.join(SCREENSHOT_DIR, 'error-state.png');
        await page.screenshot({ path: errorPath, fullPage: true }).catch(() => {});
        console.log(`\n📸 Error screenshot: ${errorPath}`);
    } finally {
        await browser.close();
        console.log('\n👋 Browser closed.');
    }
}

main();
