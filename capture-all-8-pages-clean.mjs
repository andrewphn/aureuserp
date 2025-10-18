#!/usr/bin/env node

/**
 * Capture all 8 PDF pages from ANNOTATION VIEWER (not wizard)
 * Uses keyboard navigation to avoid footer overlay issues
 */

import { chromium } from 'playwright';
import * as fs from 'fs';
import * as path from 'path';

const BASE_URL = 'http://aureuserp.test';
const PROJECT_ID = 1;
const PDF_ID = 1;
const SCREENSHOT_DIR = 'test-screenshots/pdf-pages-annotation-viewer';

// Create screenshot directory
if (!fs.existsSync(SCREENSHOT_DIR)) {
    fs.mkdirSync(SCREENSHOT_DIR, { recursive: true });
}

async function main() {
    console.log('╔════════════════════════════════════════════════════════╗');
    console.log('║  CAPTURING ALL 8 PDF PAGES - ANNOTATION VIEWER        ║');
    console.log('╚════════════════════════════════════════════════════════╝\n');

    const browser = await chromium.launch({
        headless: false,
        slowMo: 300
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

        // Navigate to ANNOTATION VIEWER (not wizard)
        console.log('📄 Opening PDF Annotation Viewer...');
        const annotationUrl = `${BASE_URL}/admin/project/projects/${PROJECT_ID}/pdf-review?pdf=${PDF_ID}`;
        console.log(`   URL: ${annotationUrl}\n`);

        await page.goto(annotationUrl);
        await page.waitForLoadState('networkidle');
        await page.waitForTimeout(5000); // Wait for PDF to fully load
        console.log('✓ Annotation viewer loaded\n');

        // Take initial screenshot to verify correct interface
        const initialPath = path.join(SCREENSHOT_DIR, '00-viewer-interface.png');
        await page.screenshot({
            path: initialPath,
            fullPage: false
        });
        console.log(`📸 Captured initial interface: ${initialPath}\n`);

        // Check if we're on the annotation viewer by looking for the toolbar
        const hasToolbar = await page.locator('[class*="annotation"], [class*="toolbar"], button[title*="Rectangle"]').count() > 0;
        if (hasToolbar) {
            console.log('✓ Confirmed: Annotation viewer interface detected\n');
        } else {
            console.log('⚠️  Warning: May not be on annotation viewer interface\n');
        }

        // Find the page indicator to confirm current page
        const pageIndicator = await page.locator('text=/Page \\d+/').first().textContent().catch(() => null);
        if (pageIndicator) {
            console.log(`📄 Current page indicator: ${pageIndicator}\n`);
        }

        // Capture all 8 pages using KEYBOARD NAVIGATION (avoids footer overlay)
        for (let pageNum = 1; pageNum <= 8; pageNum++) {
            console.log(`\n━━━ CAPTURING PAGE ${pageNum} ━━━`);

            // Navigate to page if not on page 1
            if (pageNum > 1) {
                console.log(`  ⌨️  Pressing ArrowRight to navigate...`);
                await page.keyboard.press('ArrowRight');
                await page.waitForTimeout(3000); // Wait for page to render
                console.log(`  → Navigated to page ${pageNum}`);
            }

            // Capture full viewport
            const screenshotPath = path.join(SCREENSHOT_DIR, `page-${pageNum}-full.png`);
            await page.screenshot({
                path: screenshotPath,
                fullPage: false
            });
            console.log(`  📸 Captured: ${screenshotPath}`);

            // Try to capture just the PDF canvas area (higher quality)
            try {
                const pdfCanvas = page.locator('canvas').first();
                if (await pdfCanvas.isVisible({ timeout: 2000 })) {
                    const canvasPath = path.join(SCREENSHOT_DIR, `page-${pageNum}-canvas.png`);
                    await pdfCanvas.screenshot({ path: canvasPath });
                    console.log(`  📸 Captured canvas: ${canvasPath}`);
                }
            } catch (err) {
                console.log(`  ℹ️  Could not capture canvas separately for page ${pageNum}`);
            }

            // Try to capture the right sidebar with metadata
            try {
                const sidebar = page.locator('[class*="sidebar"], .fi-aside').first();
                if (await sidebar.isVisible({ timeout: 1000 })) {
                    const sidebarPath = path.join(SCREENSHOT_DIR, `page-${pageNum}-sidebar.png`);
                    await sidebar.screenshot({ path: sidebarPath });
                    console.log(`  📸 Captured sidebar: ${sidebarPath}`);
                }
            } catch (err) {
                console.log(`  ℹ️  Could not capture sidebar for page ${pageNum}`);
            }
        }

        console.log('\n\n╔════════════════════════════════════════════════════════╗');
        console.log('║  ✅ ALL 8 PAGES CAPTURED SUCCESSFULLY                  ║');
        console.log('╚════════════════════════════════════════════════════════╝\n');
        console.log(`📁 Screenshots saved to: ${SCREENSHOT_DIR}/\n`);
        console.log('Files captured per page:');
        console.log('  - page-N-full.png (entire viewport)');
        console.log('  - page-N-canvas.png (PDF canvas only)');
        console.log('  - page-N-sidebar.png (metadata panel)\n');
        console.log('Now you can analyze each page to understand the content!\n');

        // Keep browser open for review
        console.log('Browser will stay open for 30 seconds for review...');
        await page.waitForTimeout(30000);

    } catch (error) {
        console.error('\n❌ Error:', error.message);
        console.error('Stack:', error.stack);

        const errorPath = path.join(SCREENSHOT_DIR, 'error-state.png');
        await page.screenshot({ path: errorPath }).catch(() => {});
        console.log(`\n📸 Error screenshot saved to: ${errorPath}`);
    } finally {
        await browser.close();
        console.log('\n👋 Browser closed. Check screenshots to analyze page content.');
    }
}

main();
