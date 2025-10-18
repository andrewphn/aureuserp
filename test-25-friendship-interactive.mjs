#!/usr/bin/env node

/**
 * Interactive 25 Friendship Lane Workflow
 * Takes screenshots at each step and pauses for analysis
 */

import { chromium } from 'playwright';
import * as fs from 'fs';
import * as path from 'path';

const BASE_URL = 'http://aureuserp.test';
const PROJECT_ID = 1;
const SCREENSHOT_DIR = 'test-screenshots/25-friendship';

// Create screenshot directory
if (!fs.existsSync(SCREENSHOT_DIR)) {
    fs.mkdirSync(SCREENSHOT_DIR, { recursive: true });
}

async function takeScreenshot(page, name, description) {
    const filename = `${name}.png`;
    const filepath = path.join(SCREENSHOT_DIR, filename);
    await page.screenshot({ path: filepath, fullPage: false });
    console.log(`📸 Screenshot: ${description}`);
    console.log(`   Saved to: ${filepath}\n`);
    return filepath;
}

async function main() {
    console.log('=== 25 Friendship Lane - Interactive Workflow ===\n');

    const browser = await chromium.launch({
        headless: false,
        slowMo: 300
    });

    const context = await browser.newContext({
        viewport: { width: 1920, height: 1080 }
    });

    const page = await context.newPage();

    try {
        // Step 1: Login
        console.log('STEP 1: Login\n');
        await page.goto(`${BASE_URL}/admin/login`);
        await page.waitForLoadState('networkidle');

        if (page.url().includes('/login')) {
            await takeScreenshot(page, '01-login-page', 'Login page');

            // Fill login form - use getByLabel for Filament forms
            const emailField = page.locator('input[type="email"]').first();
            const passwordField = page.locator('input[type="password"]').first();

            await emailField.fill('info@tcswoodwork.com');
            await passwordField.fill('Lola2024!');
            await page.click('button:has-text("Sign in")');
            await page.waitForLoadState('networkidle');
            await page.waitForTimeout(2000);

            await takeScreenshot(page, '02-logged-in', 'After login - Dashboard');
        }

        // Step 2: Navigate to project
        console.log('STEP 2: Navigate to Project\n');
        await page.goto(`${BASE_URL}/admin/project/projects/${PROJECT_ID}/edit`);
        await page.waitForLoadState('networkidle');
        await page.waitForTimeout(2000);

        await takeScreenshot(page, '03-project-overview', 'Project edit page');

        // Step 3: Go to Documents tab
        console.log('STEP 3: Open Documents Tab\n');
        const documentsTab = page.locator('button:has-text("Documents"), a:has-text("Documents")').first();
        if (await documentsTab.isVisible()) {
            await documentsTab.click();
            await page.waitForTimeout(1500);
            await takeScreenshot(page, '04-documents-tab', 'Documents tab opened');
        }

        // Step 4: Open PDF Viewer
        console.log('STEP 4: Open PDF Viewer\n');
        const reviewButton = page.locator('text="Review PDF & Price", text="View PDF", a[href*="review"]').first();
        if (await reviewButton.isVisible()) {
            await reviewButton.click();
            await page.waitForLoadState('networkidle');
            await page.waitForTimeout(3000);
            await takeScreenshot(page, '05-pdf-viewer-opened', 'PDF viewer initial state');
        }

        // Step 5: Navigate through all pages and take screenshots
        console.log('STEP 5: Review All PDF Pages\n');

        for (let pageNum = 1; pageNum <= 8; pageNum++) {
            console.log(`   Reviewing page ${pageNum}...`);

            // Try to click thumbnail
            const thumbnail = page.locator(`[data-page-number="${pageNum}"]`).first();
            if (await thumbnail.isVisible()) {
                await thumbnail.scrollIntoViewIfNeeded();
                await thumbnail.click();
                await page.waitForTimeout(2000);
            }

            await takeScreenshot(page, `06-page-${pageNum}`, `PDF page ${pageNum}`);
        }

        // Step 6: Go back to project and check Project Data
        console.log('STEP 6: Review Project Data Tab\n');
        await page.goto(`${BASE_URL}/admin/project/projects/${PROJECT_ID}/edit`);
        await page.waitForLoadState('networkidle');

        const projectDataTab = page.locator('button:has-text("Project Data"), a:has-text("Project Data")').first();
        if (await projectDataTab.isVisible()) {
            await projectDataTab.click();
            await page.waitForTimeout(1500);
            await takeScreenshot(page, '07-project-data-tab', 'Project Data tab');
        }

        // Step 7: Check Rooms section
        console.log('STEP 7: Review Rooms Section\n');
        const roomsHeading = page.locator('text="Rooms"').first();
        if (await roomsHeading.isVisible()) {
            await roomsHeading.scrollIntoViewIfNeeded();
            await page.waitForTimeout(1000);
            await takeScreenshot(page, '08-rooms-section', 'Rooms section');
        }

        // Step 8: Check Cabinet Runs section
        console.log('STEP 8: Review Cabinet Runs Section\n');
        const cabinetRunsHeading = page.locator('text="Cabinet Runs"').first();
        if (await cabinetRunsHeading.isVisible()) {
            await cabinetRunsHeading.scrollIntoViewIfNeeded();
            await page.waitForTimeout(1000);
            await takeScreenshot(page, '09-cabinet-runs-section', 'Cabinet Runs section');
        }

        // Step 9: Check Cabinets section
        console.log('STEP 9: Review Cabinets Section\n');
        const cabinetsHeading = page.locator('text="Cabinets"').first();
        if (await cabinetsHeading.isVisible()) {
            await cabinetsHeading.scrollIntoViewIfNeeded();
            await page.waitForTimeout(1000);
            await takeScreenshot(page, '10-cabinets-section', 'Cabinets section');
        }

        // Final screenshot
        await page.screenshot({
            path: path.join(SCREENSHOT_DIR, '11-final-state.png'),
            fullPage: true
        });

        console.log('\n=== Screenshot Collection Complete ===');
        console.log(`All screenshots saved to: ${SCREENSHOT_DIR}/\n`);

        console.log('Ready for analysis! Press Ctrl+C to close browser.');
        await page.waitForTimeout(30000); // Keep browser open for 30 seconds

    } catch (error) {
        console.error('Error:', error);
        await takeScreenshot(page, 'error', 'Error state');
    } finally {
        await browser.close();
    }
}

main();
