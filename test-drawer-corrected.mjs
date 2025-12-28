import { chromium } from '@playwright/test';
import { join, dirname } from 'path';
import { fileURLToPath } from 'url';
import fs from 'fs';

const __dirname = dirname(fileURLToPath(import.meta.url));
const screenshotDir = join(__dirname, 'test-screenshots-drawer-corrected');

if (!fs.existsSync(screenshotDir)) fs.mkdirSync(screenshotDir, { recursive: true });

const sc = async (page, name) => {
  await page.screenshot({ path: join(screenshotDir, name + '.png'), fullPage: true });
  console.log('Screenshot:', name);
};

const wait = ms => new Promise(r => setTimeout(r, ms));

(async () => {
  console.log('Drawer Hardware Spec Auto-Calculate Test (Corrected URLs)');
  const browser = await chromium.launch({ headless: false, slowMo: 400 });
  const page = await browser.newPage({ viewport: { width: 1920, height: 1080 } });
  
  try {
    await page.goto('http://aureuserp.test/admin/login');
    await page.waitForLoadState('networkidle');
    await sc(page, '01-login');
    
    await page.fill('input[type="email"]', 'info@tcswoodwork.com');
    await page.fill('input[type="password"]', 'Lola2024!');
    await page.click('button[type="submit"]');
    await page.waitForLoadState('networkidle');
    await wait(2000);
    await sc(page, '02-dashboard');
    console.log('Logged in');
    
    await page.goto('http://aureuserp.test/admin/inventory/configurations/product-attributes');
    await page.waitForLoadState('networkidle');
    await wait(1500);
    await sc(page, '03-attributes');
    console.log('Attributes page loaded');
    
    await page.goto('http://aureuserp.test/admin/inventory/products');
    await page.waitForLoadState('networkidle');
    await wait(1500);
    await sc(page, '04-products');
    console.log('Products page loaded');
    
    await page.goto('http://aureuserp.test/admin/projects/projects');
    await page.waitForLoadState('networkidle');
    await wait(1500);
    await sc(page, '05-projects');
    console.log('Projects page loaded');
    
    console.log('Taking periodic screenshots for manual testing...');
    for (let i = 0; i < 20; i++) {
      await wait(15000);
      await sc(page, 'manual-' + String(i + 6).padStart(2, '0'));
      console.log('Screenshot', i + 1, '/20');
    }
    
    console.log('Test complete! Screenshots in:', screenshotDir);
  } catch (e) {
    console.error('Error:', e.message);
    await sc(page, 'ERROR');
  }
})().catch(console.error);
