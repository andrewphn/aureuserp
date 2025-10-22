import { chromium } from '@playwright/test';

const browser = await chromium.launch({ headless: false });
const context = await browser.newContext({ ignoreHTTPSErrors: true });
const page = await context.newPage();

try {
  console.log('Logging in...');
  await page.goto('http://aureuserp.test/admin/login');
  await page.waitForTimeout(1000);
  await page.fill('input[type="email"]', 'info@tcswoodwork.com');
  await page.fill('input[type="password"]', 'Lola2024!');
  await page.click('button[type="submit"]');
  await page.waitForTimeout(3000);

  console.log('Navigating to project edit page...');
  await page.goto('http://aureuserp.test/admin/project/projects/2/edit');
  await page.waitForTimeout(4000);

  console.log('\n=== CHECKING URL ===\n');

  const urlInfo = await page.evaluate(() => {
    return {
      href: window.location.href,
      pathname: window.location.pathname,
      includesEdit: window.location.pathname.includes('/edit'),
      search: window.location.search,
      hash: window.location.hash
    };
  });

  console.log('URL Info:');
  console.log('  href:', urlInfo.href);
  console.log('  pathname:', urlInfo.pathname);
  console.log('  pathname.includes("/edit"):', urlInfo.includesEdit);
  console.log('  search:', urlInfo.search);
  console.log('  hash:', urlInfo.hash);

} catch (error) {
  console.error('ERROR:', error.message);
} finally {
  await browser.close();
}
