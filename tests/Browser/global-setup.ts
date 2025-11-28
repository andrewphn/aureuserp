/**
 * Global Setup for Playwright Tests
 * This runs BEFORE any tests and ensures auth state exists
 */
import { chromium, FullConfig } from '@playwright/test';
import path from 'path';
import { fileURLToPath } from 'url';

const __filename = fileURLToPath(import.meta.url);
const __dirname = path.dirname(__filename);

const authFile = path.join(__dirname, 'auth-state.json');

async function globalSetup(config: FullConfig) {
    console.log('🚀 Running global setup - authenticating...');

    const browser = await chromium.launch();
    const context = await browser.newContext();
    const page = await context.newPage();

    // Navigate to admin dashboard (will redirect to login if not authenticated)
    await page.goto('http://aureuserp.test/admin');

    // Check if redirected to login
    if (page.url().includes('/login')) {
        console.log('🔐 Logging in...');

        // Check for rate limiting error
        const rateLimitError = await page.locator('text=/too many login attempts/i').isVisible().catch(() => false);
        if (rateLimitError) {
            console.error('❌ Rate limited! Please wait before running tests again.');
            await browser.close();
            throw new Error('Rate limited - please wait before running tests');
        }

        // Fill login form
        await page.fill('input[type="email"]', 'info@tcswoodwork.com');
        await page.fill('input[type="password"]', 'Lola2024!');

        // Submit form
        await page.click('button[type="submit"]');

        // Wait for redirect after login (or rate limit error)
        try {
            await page.waitForURL('**/admin/**', { timeout: 10000 });
            // Wait for page to fully load and session to be established
            await page.waitForLoadState('networkidle');
            console.log('✅ Login successful');
        } catch (error) {
            // Check if rate limited after submission
            const rateLimited = await page.locator('text=/too many login attempts/i').isVisible().catch(() => false);
            if (rateLimited) {
                console.error('❌ Rate limited after login attempt!');
                await browser.close();
                throw new Error('Rate limited - please wait before running tests');
            }
            throw error;
        }
    } else {
        console.log('✅ Already authenticated');
    }

    // Save signed-in state
    await context.storageState({ path: authFile });
    console.log('💾 Authentication state saved to:', authFile);

    await browser.close();
}

export default globalSetup;
