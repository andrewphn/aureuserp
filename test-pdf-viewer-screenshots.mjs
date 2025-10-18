#!/usr/bin/env node

/**
 * Open PDF viewer and capture actual PDF pages for analysis
 */

import { chromium } from 'playwright';
import * as fs from 'fs';
import * as path from 'path';

const BASE_URL = 'http://aureuserp.test';
const PROJECT_ID = 1;
const SCREENSHOT_DIR = 'test-screenshots/25-friendship-pdf';

// Create screenshot directory
if (!fs.existsSync(SCREENSHOT_DIR)) {
    fs.mkdirSync(SCREENSHOT_DIR, { recursive: true });
}

async function takeScreenshot(page, name, description) {
    const filename = `${name}.png`;
    const filepath = path.join(SCREENSHOT_DIR, filename);
    await page.screenshot({ path: filepath, fullPage: false });
    console.log(`📸 ${description}`);
    console.log(`   Saved: ${filepath}\n`);
    return filepath;
}

async function main() {
    console.log('=== PDF Viewer - Capturing Actual PDF Pages ===\n');

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
        console.log('Logging in...');
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

        // Navigate to project edit page
        console.log('Opening project...');
        await page.goto(`${BASE_URL}/admin/project/projects/${PROJECT_ID}/edit`);
        await page.waitForLoadState('networkidle');
        await page.waitForTimeout(2000);
        console.log('✓ Project loaded\n');

        // Click Documents tab
        console.log('Opening Documents tab...');
        const documentsTab = page.locator('button:has-text("Documents"), a:has-text("Documents")').first();
        if (await documentsTab.isVisible()) {
            await documentsTab.click();
            await page.waitForTimeout(2000);
        }
        console.log('✓ Documents tab opened\n');

        // Navigate directly to the PDF review page with PDF ID
        console.log('Opening PDF viewer...');
        const pdfId = 1; // From our earlier query
        await page.goto(`${BASE_URL}/admin/project/projects/${PROJECT_ID}/pdf-review?pdf=${pdfId}`);
        await page.waitForLoadState('networkidle');
        await page.waitForTimeout(5000); // Wait for PDF to load

        await takeScreenshot(page, '00-pdf-viewer-opened', 'PDF viewer initial state');
        console.log('✓ PDF viewer opened\n');

        // Wait for PDF to load
        console.log('Waiting for PDF to load...');
        await page.waitForTimeout(5000);

        // Take screenshot of initial view
        await takeScreenshot(page, '01-pdf-initial-view', 'PDF initial view');

        // Look for page thumbnails or navigation
        console.log('Looking for page navigation...');

        // Try to find thumbnail container
        const thumbnailSelectors = [
            '[data-page-number]',
            '.pdf-thumbnail',
            '.thumbnail',
            '[class*="thumbnail"]',
            'canvas[data-page]',
            '.page-thumbnail'
        ];

        let thumbnailsFound = false;
        for (const selector of thumbnailSelectors) {
            const thumbnails = page.locator(selector);
            const count = await thumbnails.count();
            if (count > 0) {
                console.log(`   Found ${count} thumbnails using: ${selector}\n`);
                thumbnailsFound = true;

                // Click through each page
                for (let i = 0; i < Math.min(count, 8); i++) {
                    console.log(`   Viewing page ${i + 1}...`);
                    await thumbnails.nth(i).click();
                    await page.waitForTimeout(2000);
                    await takeScreenshot(page, `page-${i + 1}`, `PDF page ${i + 1}`);
                }
                break;
            }
        }

        if (!thumbnailsFound) {
            console.log('⚠ No thumbnails found, looking for other navigation...');

            // Look for next/previous buttons
            const nextButton = page.locator('button:has-text("Next"), [aria-label*="Next"]').first();
            if (await nextButton.isVisible({ timeout: 2000 }).catch(() => false)) {
                console.log('   Found Next button, navigating pages...\n');

                for (let i = 1; i <= 8; i++) {
                    await takeScreenshot(page, `page-${i}`, `PDF page ${i}`);
                    if (await nextButton.isEnabled()) {
                        await nextButton.click();
                        await page.waitForTimeout(2000);
                    } else {
                        break;
                    }
                }
            } else {
                console.log('⚠ No navigation found, taking full page screenshot...');
                await page.screenshot({
                    path: path.join(SCREENSHOT_DIR, 'full-pdf-view.png'),
                    fullPage: true
                });
            }
        }

        console.log('\n=== PDF Screenshot Capture Complete ===');
        console.log(`Screenshots saved to: ${SCREENSHOT_DIR}/\n`);

        // Keep browser open
        console.log('Browser will stay open for 60 seconds...');
        await page.waitForTimeout(60000);

    } catch (error) {
        console.error('Error:', error);
        await takeScreenshot(page, 'error', 'Error state');
    } finally {
        await browser.close();
    }
}

main();
