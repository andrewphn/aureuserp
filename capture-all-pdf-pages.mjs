#!/usr/bin/env node

/**
 * Capture all 8 PDF pages from the annotation viewer
 * This gets detailed screenshots of each page so we can analyze the actual content
 */

import { chromium } from 'playwright';
import * as fs from 'fs';
import * as path from 'path';

const BASE_URL = 'http://aureuserp.test';
const PROJECT_ID = 1;
const PDF_ID = 1;
const SCREENSHOT_DIR = 'test-screenshots/pdf-pages-detailed';

// Create screenshot directory
if (!fs.existsSync(SCREENSHOT_DIR)) {
    fs.mkdirSync(SCREENSHOT_DIR, { recursive: true });
}

async function main() {
    console.log('╔════════════════════════════════════════════════════════╗');
    console.log('║  CAPTURING ALL 8 PDF PAGES - DETAILED ANALYSIS        ║');
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

        // Navigate to PDF annotation viewer (NOT the wizard)
        console.log('📄 Opening PDF Annotation Viewer...');
        await page.goto(`${BASE_URL}/admin/project/projects/${PROJECT_ID}/pdf-review?pdf=${PDF_ID}`);
        await page.waitForLoadState('networkidle');
        await page.waitForTimeout(5000); // Wait for PDF to fully load
        console.log('✓ PDF viewer loaded\n');

        // Take initial screenshot to see the interface
        await page.screenshot({
            path: path.join(SCREENSHOT_DIR, '00-viewer-interface.png'),
            fullPage: false
        });
        console.log('📸 Captured initial viewer interface\n');

        // Try to find the current page indicator to confirm we're on the right interface
        const pageIndicator = await page.locator('text=/Page \\d+/').first().textContent().catch(() => null);
        if (pageIndicator) {
            console.log(`✓ Found page indicator: ${pageIndicator}\n`);
        }

        // Find navigation method - look for page number input or next/prev buttons
        let navigationMethod = null;

        // Method 1: Try to find page number input that's visible
        const pageNumberInput = page.locator('input[type="number"]').filter({ hasText: '' });
        if (await pageNumberInput.count() > 0) {
            for (let i = 0; i < await pageNumberInput.count(); i++) {
                const input = pageNumberInput.nth(i);
                if (await input.isVisible().catch(() => false)) {
                    navigationMethod = 'input';
                    console.log('✓ Using page number input for navigation\n');
                    break;
                }
            }
        }

        // Method 2: Try arrow/next buttons
        if (!navigationMethod) {
            const nextButton = page.locator('button[aria-label="Next page"], button:has-text("Next"), [title*="Next"]').first();
            if (await nextButton.isVisible({ timeout: 2000 }).catch(() => false)) {
                navigationMethod = 'buttons';
                console.log('✓ Using next/prev buttons for navigation\n');
            }
        }

        // Method 3: Try keyboard arrows
        if (!navigationMethod) {
            navigationMethod = 'keyboard';
            console.log('✓ Using keyboard arrows for navigation\n');
        }

        // Capture all 8 pages
        for (let pageNum = 1; pageNum <= 8; pageNum++) {
            console.log(`\n━━━ PAGE ${pageNum} ━━━`);

            // If not on page 1, navigate to the page
            if (pageNum > 1) {
                if (navigationMethod === 'input') {
                    // Find visible page input
                    for (let i = 0; i < await pageNumberInput.count(); i++) {
                        const input = pageNumberInput.nth(i);
                        if (await input.isVisible().catch(() => false)) {
                            await input.fill(String(pageNum));
                            await page.keyboard.press('Enter');
                            break;
                        }
                    }
                } else if (navigationMethod === 'buttons') {
                    const nextButton = page.locator('button[aria-label="Next page"], button:has-text("Next")').first();
                    await nextButton.click();
                } else {
                    // Keyboard navigation
                    await page.keyboard.press('ArrowRight');
                }

                console.log(`  → Navigated to page ${pageNum}`);
                await page.waitForTimeout(3000); // Wait for page to render
            }

            // Capture the PDF canvas/viewer area
            const screenshotPath = path.join(SCREENSHOT_DIR, `page-${pageNum}-detail.png`);
            await page.screenshot({
                path: screenshotPath,
                fullPage: false
            });
            console.log(`  📸 Captured: ${screenshotPath}`);

            // Try to capture just the PDF canvas if possible
            const pdfCanvas = page.locator('canvas, .pdf-canvas, [class*="pdf"]').first();
            if (await pdfCanvas.isVisible({ timeout: 1000 }).catch(() => false)) {
                const canvasPath = path.join(SCREENSHOT_DIR, `page-${pageNum}-canvas.png`);
                await pdfCanvas.screenshot({ path: canvasPath }).catch(() => {
                    console.log(`  ℹ Could not capture canvas separately`);
                });
            }
        }

        console.log('\n\n╔════════════════════════════════════════════════════════╗');
        console.log('║  ✅ ALL PAGES CAPTURED SUCCESSFULLY                    ║');
        console.log('╚════════════════════════════════════════════════════════╝\n');
        console.log(`📁 Screenshots saved to: ${SCREENSHOT_DIR}/\n`);
        console.log('You can now review each page to understand the content.\n');

        // Keep browser open for review
        console.log('Browser will stay open for 60 seconds for review...');
        await page.waitForTimeout(60000);

    } catch (error) {
        console.error('\n❌ Error:', error.message);
        await page.screenshot({
            path: path.join(SCREENSHOT_DIR, 'error-state.png')
        });
    } finally {
        await browser.close();
    }
}

main();
