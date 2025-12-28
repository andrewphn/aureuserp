import { chromium } from '@playwright/test';
import { join, dirname } from 'path';
import { fileURLToPath } from 'url';
import fs from 'fs';

const __dirname = dirname(fileURLToPath(import.meta.url));
const screenshotDir = join(__dirname, 'test-screenshots-drawer-hardware');

if (!fs.existsSync(screenshotDir)) {
  fs.mkdirSync(screenshotDir, { recursive: true });
}

const sc = async (page, name) => {
  const path = join(screenshotDir, name + '.png');
  await page.screenshot({ path, fullPage: true });
  console.log('Screenshot:', name);
};

const wait = ms => new Promise(r => setTimeout(r, ms));

const test = async () => {
  const browser = await chromium.launch({ headless: false, slowMo: 400 });
  const page = await browser.newPage({ viewport: { width: 1920, height: 1080 } });
  
  try {
    console.log('\n=== LOGIN ===');
    await page.goto('http://aureuserp.test/admin/login');
    await page.waitForLoadState('networkidle');
    await sc(page, '01-login');
    
    await page.fill('input[type="email"]', 'info@tcswoodwork.com');
    await page.fill('input[type="password"]', 'Lola2024!');
    await page.click('button[type="submit"]');
    await page.waitForLoadState('networkidle');
    await wait(2000);
    await sc(page, '02-dashboard');
    console.log('Logged in\n');
    
    console.log('=== PRODUCTS ===');
    await page.goto('http://aureuserp.test/admin/products/products');
    await page.waitForLoadState('networkidle');
    await wait(1000);
    await sc(page, '03-products');
    console.log('Products page loaded\n');
    
    console.log('=== PROJECTS ===');
    await page.goto('http://aureuserp.test/admin/projects/projects');
    await page.waitForLoadState('networkidle');
    await wait(1000);
    await sc(page, '04-projects');
    console.log('Projects page loaded\n');
    
    console.log('=== TAKING PERIODIC SCREENSHOTS ===');
    console.log('Please manually test the hardware spec feature.');
    console.log('Screenshots will be taken every 15 seconds.\n');
    
    for (let i = 0; i < 20; i++) {
      await wait(15000);
      const num = String(i + 5).padStart(2, '0');
      await sc(page, num + '-manual-test');
      console.log('Progress:', i + 1, '/ 20');
    }
    
    console.log('\nTest complete!');
    console.log('Screenshots in:', screenshotDir);
    
  } catch (e) {
    console.error('Error:', e.message);
    await sc(page, 'ERROR');
  }
  
  console.log('\nBrowser will stay open. Close when ready.');
};

test().catch(console.error);
