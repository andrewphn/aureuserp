import { test, expect } from '@playwright/test';

/**
 * Capture server error when loading PDF viewer
 */
test('capture server error on PDF viewer page', async ({ page }) => {
  console.log('🔍 Attempting to load PDF viewer and capture errors...');

  // Login
  await page.goto('/admin/login');
  await page.fill('input[type="email"]', 'info@tcswoodwork.com');
  await page.fill('input[type="password"]', 'Lola2024!');
  await page.click('button[type="submit"]');
  await page.waitForURL('**/admin/**', { timeout: 10000 });
  console.log('✅ Logged in');

  // Listen for console errors
  page.on('console', msg => {
    if (msg.type() === 'error') {
      console.log('📄 BROWSER ERROR:', msg.text());
    }
  });

  // Listen for page errors
  page.on('pageerror', error => {
    console.log('📄 PAGE ERROR:', error.message);
  });

  // Try to navigate with no wait
  console.log('📄 Attempting navigation...');
  const response = await page.goto('/admin/project/projects/9/annotate-v2/1?pdf=1', {
    waitUntil: 'commit',
    timeout: 10000
  }).catch(e => {
    console.log('❌ Navigation error:', e.message);
    return null;
  });

  if (response) {
    console.log('📊 Response status:', response.status());
    console.log('📊 Response URL:', response.url());

    if (response.status() === 500) {
      const body = await response.text();
      console.log('📄 Response body (first 500 chars):', body.substring(0, 500));
    }
  }

  console.log('📍 Final URL:', page.url());

  // Take screenshot
  await page.screenshot({ path: 'error-capture.png', fullPage: true });
  console.log('📸 Screenshot saved to error-capture.png');
});
