import { chromium } from 'playwright';

const SKU = '563H5330B';

async function testAiProduct() {
  const browser = await chromium.launch({ headless: false });
  const context = await browser.newContext();
  const page = await context.newPage();

  try {
    console.log('Navigating to Create Product page...');
    await page.goto('http://aureuserp.test/admin/inventory/products/products/create');
    await page.waitForLoadState('networkidle');
    
    if (page.url().includes('login')) {
      console.log('Logging in...');
      await page.fill('input[name="email"]', 'info@tcswoodwork.com');
      await page.fill('input[name="password"]', 'Lola2024!');
      await page.click('button[type="submit"]');
      await page.waitForLoadState('networkidle');
      await page.goto('http://aureuserp.test/admin/inventory/products/products/create');
      await page.waitForLoadState('networkidle');
    }
    
    console.log('On Create Product page');
    console.log(`Entering SKU: ${SKU}`);
    
    const skuInput = page.locator('input[id*="supplier_sku"], input[name*="supplier_sku"]').first();
    await skuInput.waitFor({ timeout: 10000 });
    await skuInput.fill(SKU);
    
    console.log('Looking for AI Populate button...');
    await page.waitForTimeout(1000);
    
    const aiButton = page.locator('button:has-text("AI Populate"), button:has-text("Populate")').first();
    await aiButton.waitFor({ timeout: 10000 });
    await aiButton.click();
    
    console.log('Clicked AI Populate, waiting for modal...');
    await page.waitForTimeout(2000);
    
    const generateButton = page.locator('button:has-text("Generate")').first();
    if (await generateButton.isVisible()) {
      await generateButton.click();
      console.log('Clicked Generate button');
    }
    
    console.log('Waiting for AI to process (60 seconds)...');
    await page.waitForTimeout(60000);
    
    await page.screenshot({ path: 'ai-product-test.png', fullPage: true });
    console.log('Screenshot saved to ai-product-test.png');
    
    const nameInput = page.locator('input[id*="name"]').first();
    const productName = await nameInput.inputValue();
    console.log(`Product name: ${productName}`);
    
    console.log('Keeping browser open for 60 seconds...');
    await page.waitForTimeout(60000);
    
  } catch (error) {
    console.error('Error:', error.message);
    await page.screenshot({ path: 'ai-product-error.png', fullPage: true });
  } finally {
    await browser.close();
  }
}

testAiProduct();
