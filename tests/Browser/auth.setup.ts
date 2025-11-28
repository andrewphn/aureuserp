/**
 * Authentication Setup Project
 * Runs once before all tests to log in and save authentication state
 */
import { test as setup, expect } from '@playwright/test';
import path from 'path';
import { fileURLToPath } from 'url';

const __filename = fileURLToPath(import.meta.url);
const __dirname = path.dirname(__filename);

const authFile = path.join(__dirname, 'auth-state.json');

setup('authenticate', async ({ page }) => {
    // Navigate to the actual PDF viewer page
    await page.goto('http://aureuserp.test/admin/project/projects/9/annotate-v2/1?pdf=1');

    // Check if redirected to login
    if (page.url().includes('/login')) {
        console.log('🔐 Logging in...');

        // Fill login form
        await page.fill('input[type="email"]', 'info@tcswoodwork.com');
        await page.fill('input[type="password"]', 'Lola2024!');

        // Submit form
        await page.click('button[type="submit"]');

        // Wait for redirect after login
        await page.waitForURL('**/admin/**', { timeout: 10000 });

        console.log('✅ Login successful');
    } else {
        console.log('✅ Already authenticated');
    }

    // Save signed-in state
    await page.context().storageState({ path: authFile });
    console.log('💾 Authentication state saved');
});
