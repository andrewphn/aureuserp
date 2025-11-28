import { test, expect } from '@playwright/test';

test('debug authentication flow', async ({ page }) => {
    console.log('🔍 Testing authentication...');

    // Enable detailed logging
    page.on('console', msg => console.log('BROWSER:', msg.text()));
    page.on('pageerror', err => console.error('PAGE ERROR:', err.message));

    // Go to login page
    await page.goto('http://aureuserp.test/admin/login');
    console.log('📍 At login page');

    // Fill credentials
    await page.fill('input[type="email"]', 'info@tcswoodwork.com');
    await page.fill('input[type="password"]', 'Lola2024!');
    console.log('✅ Filled credentials');

    // Screenshot before submit
    await page.screenshot({ path: 'tests/Browser/auth-before-submit.png' });

    // Click submit and wait for navigation
    await Promise.all([
        page.waitForNavigation({ waitUntil: 'networkidle', timeout: 10000 }).catch(e => console.log('Navigation timeout:', e.message)),
        page.click('button[type="submit"]')
    ]);

    console.log('📍 After submit:', page.url());

    // Screenshot after submit
    await page.screenshot({ path: 'tests/Browser/auth-after-submit.png', fullPage: true });

    // Try navigating to PDF viewer
    await page.goto('http://aureuserp.test/admin/project/projects/9/annotate-v2/2?pdf=1', { waitUntil: 'networkidle' });
    console.log('📍 After navigation attempt:', page.url());

    // Final screenshot
    await page.screenshot({ path: 'tests/Browser/auth-final-location.png', fullPage: true });

    // Check if we're authenticated
    const isAuthenticated = !page.url().includes('/login');
    console.log('✅ Authenticated:', isAuthenticated);

    expect(isAuthenticated).toBe(true);
});
