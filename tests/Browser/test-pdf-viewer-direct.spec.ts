import { test, expect } from '@playwright/test';

/**
 * Test using SAVED auth state from global setup
 * Should already be authenticated when test starts
 */
test('should access admin using saved auth state', async ({ page }) => {
  console.log('🧪 Testing with saved auth state (no manual login)...');

  // Check cookies from saved state
  const cookies = await page.context().cookies();
  console.log('🍪 Cookies from saved state:', cookies.map(c => c.name).join(', '));

  // Step 1: Go directly to admin (should NOT redirect to login)
  console.log('📊 Navigating to admin dashboard...');
  await page.goto('/admin');
  await page.waitForLoadState('domcontentloaded');
  console.log(`📍 URL after navigation: ${page.url()}`);

  if (page.url().includes('/login')) {
    console.log('❌ Redirected to login - saved auth state not working');

    // Debug: check the saved auth state
    const authState = require('./auth-state.json');
    console.log('📋 Saved cookies:', authState.cookies?.map(c => c.name) || 'none');

    throw new Error('Saved auth state not being used - redirected to login');
  }

  console.log('✅ Successfully accessed admin with saved auth state!');

  // Step 2: Check debug endpoint to verify auth persists
  console.log('📡 Checking debug endpoint...');
  try {
    const debugResponse = await page.goto('/test-auth-debug');
    const debugData = await debugResponse.json();
    console.log('📊 Debug auth status:', JSON.stringify(debugData, null, 2));

    if (!debugData.authenticated) {
      console.log('⚠️  Auth not persisting on web routes!');
    } else {
      console.log(`✅ Auth persists: User ${debugData.user_id} (${debugData.user_email})`);
    }
  } catch (e) {
    console.log(`❌ Debug endpoint error: ${e.message}`);
  }

  // Step 3: Navigate to dashboard first (normal user flow)
  console.log('📊 Navigating to dashboard...');
  await page.goto('/admin');
  await page.waitForLoadState('domcontentloaded');
  console.log(`✅ Dashboard loaded: ${page.url()}`);

  // Step 3: Now try PDF viewer
  console.log('📄 Navigating to PDF viewer...');
  const pdfViewerUrl = '/admin/project/projects/9/annotate-v2/1?pdf=1';

  // Listen for responses to see what happens
  page.on('response', response => {
    if (response.url().includes('annotate-v2')) {
      console.log(`📡 Response: ${response.status()} - ${response.url()}`);
    }
  });

  try {
    await page.goto(pdfViewerUrl, {
      waitUntil: 'domcontentloaded',
      timeout: 15000
    });

    console.log(`✅ Navigation completed: ${page.url()}`);

    // Check if we got redirected
    if (page.url().includes('/login')) {
      console.log('⚠️  Got redirected to login page despite being authenticated');
      throw new Error('Redirected to login despite authentication');
    }

    if (page.url().includes('annotate-v2')) {
      console.log('✅ Successfully loaded PDF viewer page');

      // Wait for Alpine.js to initialize
      const alpineLoaded = await page.evaluate(() => typeof window.Alpine !== 'undefined');
      console.log(`Alpine.js loaded: ${alpineLoaded}`);

      // Take screenshot
      await page.screenshot({ path: 'pdf-viewer-direct-success.png', fullPage: true });
      console.log('📸 Screenshot saved');
    }
  } catch (error) {
    console.log(`❌ Navigation failed: ${error.message}`);
    console.log(`Final URL: ${page.url()}`);

    // Take screenshot of error state
    await page.screenshot({ path: 'pdf-viewer-direct-error.png', fullPage: true });

    throw error;
  }
});
