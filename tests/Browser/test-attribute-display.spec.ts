import { test, expect } from '@playwright/test';

test('Check attribute display', async ({ page }) => {
  // Navigate to quotation create page
  await page.goto('/admin/sale/orders/quotations/create');
  await page.waitForLoadState('networkidle');
  await page.waitForTimeout(2000);

  console.log('✅ On quotation create page');

  // Add a product row
  await page.getByRole('button', { name: 'Add Product' }).click();
  await page.waitForTimeout(1000);
  console.log('✅ Added product row');

  // Click the product select dropdown (first select in first table row)
  const productSelect = page.locator('tr').last().locator('[data-field="product_id"]').locator('button').first();
  await productSelect.scrollIntoViewIfNeeded();
  await productSelect.click();
  await page.waitForTimeout(500);
  console.log('✅ Opened product dropdown');

  // Select Cabinet
  await page.getByRole('option', { name: 'Cabinet' }).click();
  await page.waitForTimeout(3000);
  console.log('✅ Selected Cabinet');

  // Take screenshot to see what fields are visible
  await page.screenshot({ path: '/tmp/cabinet-attributes-display.png', fullPage: true });
  console.log('📸 Screenshot saved');

  // Look for attribute fields
  const allFields = await page.locator('[data-field]').evaluateAll(elements =>
    elements
      .filter(el => el.offsetParent !== null) // Only visible elements
      .map(el => ({
        field: el.getAttribute('data-field'),
        tag: el.tagName,
        type: el.getAttribute('type'),
        value: (el as HTMLInputElement).value || (el as any).innerText?.substring(0, 50)
      }))
  );

  console.log('\n📋 All visible fields:');
  allFields.forEach(f => {
    if (f.field?.includes('attribute') || f.field?.includes('pricing') || f.field?.includes('material') || f.field?.includes('finish')) {
      console.log(`  - ${f.field}: ${f.tag} (type: ${f.type}) = "${f.value}"`);
    }
  });

  // Keep browser open for manual inspection
  await page.waitForTimeout(20000);
});
