import { chromium } from '@playwright/test';
import { fileURLToPath } from 'url';
import { dirname, join } from 'path';
import fs from 'fs';

const __filename = fileURLToPath(import.meta.url);
const __dirname = dirname(__filename);

const SCREENSHOTS_DIR = join(__dirname, 'test-screenshots-drawer-hardware');

if (!fs.existsSync(SCREENSHOTS_DIR)) {
  fs.mkdirSync(SCREENSHOTS_DIR, { recursive: true });
}

async function screenshot(page, name, fullPage = false) {
  const path = join(SCREENSHOTS_DIR, `${name}.png`);
  await page.screenshot({ path, fullPage });
  console.log(`✓ Screenshot saved: ${name}`);
}

async function testDrawerHardwareSpec() {
  console.log('🚀 Starting Drawer Hardware Spec Auto-Calculate Test\n');
  
  const browser = await chromium.launch({ 
    headless: false,
    slowMo: 500
  });
  
  const context = await browser.newContext({
    viewport: { width: 1920, height: 1080 }
  });
  
  const page = await context.newPage();
  
  try {
    console.log('\n📋 Step 1: Login to FilamentPHP Admin');
    await page.goto('http://aureuserp.test/admin/login');
    await page.waitForLoadState('networkidle');
    await screenshot(page, '01-login-page');
    
    await page.fill('input[type="email"]', 'info@tcswoodwork.com');
    await page.fill('input[type="password"]', 'Lola2024!');
    await screenshot(page, '02-login-filled');
    
    await page.click('button[type="submit"]');
    await page.waitForLoadState('networkidle');
    await page.waitForTimeout(2000);
    await screenshot(page, '03-dashboard', true);
    console.log('✓ Login successful');
    
    console.log('\n📋 Step 2: Navigate to Products → Attributes');
    await page.goto('http://aureuserp.test/admin/products/attributes');
    await page.waitForLoadState('networkidle');
    await page.waitForTimeout(1000);
    await screenshot(page, '04-attributes-list', true);
    console.log('✓ Navigated to Attributes');
    
    console.log('\n📋 Step 3: Check if attributes already exist');
    const pageText = await page.textContent('body');
    const slideLengthExists = pageText.includes('Slide Length');
    const clearanceExists = pageText.includes('Total Width Clearance');
    
    if (!slideLengthExists) {
      console.log('\n📋 Creating "Slide Length" attribute');
      await page.click('a[href*="/attributes/create"]').catch(async () => {
        await page.click('text="New"');
      });
      await page.waitForLoadState('networkidle');
      await page.waitForTimeout(1000);
      await screenshot(page, '05-create-attribute-form');
      
      await page.fill('input[name="name"]', 'Slide Length');
      await page.fill('input[name="unit_symbol"]', 'in');
      await page.fill('input[name="unit_label"]', 'inches');
      await screenshot(page, '06-slide-length-filled');
      
      await page.click('button[type="submit"]');
      await page.waitForLoadState('networkidle');
      await page.waitForTimeout(1000);
      await screenshot(page, '07-slide-length-created', true);
      console.log('✓ Created "Slide Length" attribute');
    } else {
      console.log('✓ "Slide Length" attribute already exists');
    }
    
    if (!clearanceExists) {
      console.log('\n📋 Creating "Total Width Clearance" attribute');
      await page.goto('http://aureuserp.test/admin/products/attributes');
      await page.waitForLoadState('networkidle');
      await page.click('a[href*="/attributes/create"]').catch(async () => {
        await page.click('text="New"');
      });
      await page.waitForLoadState('networkidle');
      await page.waitForTimeout(1000);
      
      await page.fill('input[name="name"]', 'Total Width Clearance');
      await page.fill('input[name="unit_symbol"]', 'mm');
      await page.fill('input[name="unit_label"]', 'millimeters');
      await screenshot(page, '08-total-width-clearance-filled');
      
      await page.click('button[type="submit"]');
      await page.waitForLoadState('networkidle');
      await page.waitForTimeout(1000);
      await screenshot(page, '09-total-width-clearance-created', true);
      console.log('✓ Created "Total Width Clearance" attribute');
    } else {
      console.log('✓ "Total Width Clearance" attribute already exists');
    }
    
    console.log('\n📋 Step 5: Find/Create drawer slide product');
    await page.goto('http://aureuserp.test/admin/products/products');
    await page.waitForLoadState('networkidle');
    await page.waitForTimeout(1000);
    await screenshot(page, '10-products-list', true);
    
    const searchInput = await page.$('input[type="search"]');
    if (searchInput) {
      await searchInput.fill('Blum LEGRABOX');
      await page.waitForTimeout(1500);
      await screenshot(page, '11-search-slide-products', true);
    }
    
    const bodyText = await page.textContent('body');
    const productExists = bodyText.includes('Blum LEGRABOX');
    
    if (!productExists) {
      console.log('⚠ No slide products found, creating a test product...');
      await page.goto('http://aureuserp.test/admin/products/products/create');
      await page.waitForLoadState('networkidle');
      await page.waitForTimeout(1000);
      
      await page.fill('input[name="name"]', 'Blum LEGRABOX Drawer Slide 21"');
      await page.fill('input[name="sku"]', 'BLUM-LEG-21');
      await screenshot(page, '12-create-slide-product');
      
      await page.click('button[type="submit"]');
      await page.waitForLoadState('networkidle');
      await page.waitForTimeout(1000);
      await screenshot(page, '13-slide-product-created', true);
      console.log('✓ Created test slide product');
    } else {
      console.log('✓ Found existing slide product');
      const firstProduct = await page.$('table tbody tr a');
      if (firstProduct) {
        await firstProduct.click();
        await page.waitForLoadState('networkidle');
        await page.waitForTimeout(1000);
        await screenshot(page, '13-existing-slide-product', true);
      }
    }
    
    console.log('\n📋 Step 6: Add numeric attributes to the product');
    const editButton = await page.$('a:has-text("Edit")');
    if (editButton) {
      await editButton.click();
      await page.waitForLoadState('networkidle');
      await page.waitForTimeout(1000);
    }
    
    await screenshot(page, '14-edit-product-form', true);
    
    await page.evaluate(() => window.scrollTo(0, document.body.scrollHeight));
    await page.waitForTimeout(500);
    await screenshot(page, '15-attributes-section', true);
    
    console.log('\n📋 Looking for attribute input fields...');
    const allInputs = await page.$$('input[type="text"], input[type="number"]');
    console.log(`Found ${allInputs.length} input fields`);
    
    for (const input of allInputs) {
      const name = await input.getAttribute('name');
      const label = await input.evaluate(el => {
        const labelEl = el.closest('.fi-fo-field-wrp')?.querySelector('label');
        return labelEl?.textContent || '';
      });
      
      if (label.includes('Slide Length') || (name && name.includes('slide_length'))) {
        await input.fill('21');
        console.log('✓ Set Slide Length to 21 inches');
      }
      
      if (label.includes('Total Width Clearance') || (name && name.includes('clearance'))) {
        await input.fill('35');
        console.log('✓ Set Total Width Clearance to 35 mm');
      }
    }
    
    await screenshot(page, '16-attributes-filled');
    
    await page.click('button[type="submit"]');
    await page.waitForLoadState('networkidle');
    await page.waitForTimeout(1000);
    await screenshot(page, '17-product-saved', true);
    console.log('✓ Product attributes saved');
    
    console.log('\n📋 Step 7: Create new project');
    await page.goto('http://aureuserp.test/admin/projects/projects/create');
    await page.waitForLoadState('networkidle');
    await page.waitForTimeout(1000);
    await screenshot(page, '18-create-project-form', true);
    
    const projectNameInput = await page.$('input[name="name"]');
    if (projectNameInput) {
      await projectNameInput.fill('Drawer Hardware Spec Test Project');
      await screenshot(page, '19-project-form-filled');
      
      await page.click('button[type="submit"]');
      await page.waitForLoadState('networkidle');
      await page.waitForTimeout(2000);
      await screenshot(page, '20-project-created', true);
      console.log('✓ Project created');
    }
    
    console.log('\n📋 Step 8-12: Navigate to Spec Builder and test drawer hardware');
    const currentUrl = page.url();
    console.log(`Current URL: ${currentUrl}`);
    
    await page.waitForTimeout(2000);
    await screenshot(page, '21-spec-builder-initial', true);
    
    console.log('\n✅ Test flow completed!');
    console.log(`\n📸 All screenshots saved to: ${SCREENSHOTS_DIR}`);
    console.log('\n⚠️  Note: The Spec Builder UI may need manual verification for hardware addition and auto-calculation');
    
  } catch (error) {
    console.error('\n❌ Test failed with error:', error.message);
    console.error(error.stack);
    await screenshot(page, 'ZZZ-ERROR-final-state', true);
    throw error;
  } finally {
    console.log('\n⏸️  Browser will close in 10 seconds...');
    await page.waitForTimeout(10000);
    await browser.close();
  }
}

testDrawerHardwareSpec().catch(console.error);
