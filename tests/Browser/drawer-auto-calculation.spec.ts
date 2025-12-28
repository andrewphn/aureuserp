/**
 * Drawer Auto-Calculation User Story Test
 *
 * User Story: When adding drawer content and selecting a specific hardware slide,
 * the system should auto-calculate drawer box dimensions based on:
 * - Section opening dimensions (base/max)
 * - Slide specifications (clearance, length constraints)
 *
 * Expected behavior:
 * - Drawer Width = Section Width - Slide Clearance
 * - Drawer Depth = min(Section Depth, Slide Length - Depth Offset)
 */

import { test, expect } from '@playwright/test';

// Test configuration
const BASE_URL = 'http://aureuserp.test';

// Slide product IDs with numeric specs
const SLIDE_21_INCH_ID = 32; // 21" slide with 35mm clearance, 10mm offset
const SLIDE_18_INCH_ID = 33; // 18" slide with 35mm clearance, 10mm offset

// Expected calculation results for 24" x 22" section with 21" slide
const EXPECTED_DRAWER_WIDTH = 22.622; // 24 - (35mm / 25.4)
const EXPECTED_DRAWER_DEPTH = 20.6063; // min(22, 21 - 10mm/25.4)

// Use saved auth state
test.use({ storageState: 'tests/Browser/auth-state.json' });

// Helper: Set Livewire field directly on the CreateProject form
async function setLivewireField(page, field: string, value: any) {
  return await page.evaluate(({ field, value }) => {
    // Find all wire:id elements and look for the form component
    const wireElements = document.querySelectorAll('[wire\\:id]');

    for (const el of wireElements) {
      const wireId = el.getAttribute('wire:id');
      if ((window as any).Livewire) {
        const component = (window as any).Livewire.find(wireId);
        // Check if this is the form component (has data property via get method)
        if (component && component.get && typeof component.get('data') !== 'undefined') {
          component.set(field, value);
          return { success: true, wireId };
        }
      }
    }

    return { success: false, error: 'Form component not found' };
  }, { field, value });
}

// Helper: Select Filament option by label
async function selectFilamentOption(page, label: string, optionIndex: number = 0) {
  // Find and click the select button for this field
  const fieldLabel = page.locator(`text="${label}"`).first();
  const fieldContainer = fieldLabel.locator('xpath=ancestor::div[contains(@class, "fi-fo-field-wrp")]').first();
  const selectButton = fieldContainer.locator('button[type="button"]').first();

  await selectButton.click();
  await page.waitForTimeout(300);

  // Select option by index using keyboard
  for (let i = 0; i <= optionIndex; i++) {
    await page.keyboard.press('ArrowDown');
    await page.waitForTimeout(100);
  }
  await page.keyboard.press('Enter');
  await page.waitForTimeout(300);
}

test.describe('Drawer Auto-Calculation', () => {

  test('should auto-calculate drawer dimensions when slide is selected', async ({ page }) => {
    // Navigate to Create Project
    await page.goto(`${BASE_URL}/admin/project/projects/create`);
    await page.waitForLoadState('networkidle');
    await page.waitForTimeout(1000); // Wait for Livewire to initialize

    console.log('=== Step 1: Quick Capture ===');

    // Set all Step 1 fields via Livewire (bypasses UI interaction issues)
    const customerResult = await setLivewireField(page, 'data.partner_id', 1);
    console.log('Customer set:', customerResult);
    await page.waitForTimeout(300);

    // Lead Source - string value
    const leadSourceResult = await setLivewireField(page, 'data.lead_source', 'website');
    console.log('Lead Source set:', leadSourceResult);
    await page.waitForTimeout(300);

    // Project Type - string value: residential, commercial, furniture, millwork, other
    const projectTypeResult = await setLivewireField(page, 'data.project_type', 'residential');
    console.log('Project Type set:', projectTypeResult);
    await page.waitForTimeout(500);

    console.log('Step 1 fields set via Livewire');

    // Click Next to go to Step 2
    const nextButton = page.locator('button').filter({ hasText: 'Next' }).first();
    await nextButton.scrollIntoViewIfNeeded();
    await nextButton.click({ force: true });
    await page.waitForTimeout(2000);

    console.log('=== Step 2: Scope & Budget ===');

    // Select "Detailed Spec" pricing mode to show Cabinet Spec Builder
    // The full text is "Detailed Spec (Room → Location → Run → Cabinet)"
    const detailedSpecOption = page.locator('label').filter({ hasText: 'Detailed Spec' }).first();
    await detailedSpecOption.scrollIntoViewIfNeeded();
    await detailedSpecOption.click();
    await page.waitForTimeout(1000);
    console.log('Selected Detailed Spec pricing mode');

    // Wait for Cabinet Spec Builder
    await page.waitForSelector('.cabinet-spec-builder', { timeout: 10000 });
    console.log('Cabinet Spec Builder loaded');

    // Create full hierarchy via direct method calls (same approach as test 2)
    console.log('Creating Room...');
    await page.evaluate(() => {
      const builder = document.querySelector('.cabinet-spec-builder');
      const wireEl = builder?.closest('[wire\\:id]');
      if (wireEl) {
        const wireId = wireEl.getAttribute('wire:id');
        const component = (window as any).Livewire.find(wireId);
        component.call('handleAiCreateRoom', { name: 'Test Kitchen', room_type: 'kitchen', source: 'test' });
      }
    });
    await page.waitForTimeout(500);
    console.log('✓ Room created');

    console.log('Creating Location...');
    await page.evaluate(() => {
      const builder = document.querySelector('.cabinet-spec-builder');
      const wireEl = builder?.closest('[wire\\:id]');
      if (wireEl) {
        const wireId = wireEl.getAttribute('wire:id');
        const component = (window as any).Livewire.find(wireId);
        component.call('handleAiCreateLocation', { room_path: '0', name: 'Sink Wall', source: 'test' });
      }
    });
    await page.waitForTimeout(500);
    console.log('✓ Location created');

    console.log('Creating Run...');
    await page.evaluate(() => {
      const builder = document.querySelector('.cabinet-spec-builder');
      const wireEl = builder?.closest('[wire\\:id]');
      if (wireEl) {
        const wireId = wireEl.getAttribute('wire:id');
        const component = (window as any).Livewire.find(wireId);
        component.call('handleAiCreateRun', { location_path: '0.children.0', name: 'Base Cabinets', run_type: 'base', source: 'test' });
      }
    });
    await page.waitForTimeout(500);
    console.log('✓ Run created');

    console.log('Adding Cabinet...');
    await page.evaluate(() => {
      const builder = document.querySelector('.cabinet-spec-builder');
      const wireEl = builder?.closest('[wire\\:id]');
      if (wireEl) {
        const wireId = wireEl.getAttribute('wire:id');
        const component = (window as any).Livewire.find(wireId);
        component.call('handleAiAddCabinet', {
          cabinets: [{
            run_path: '0.children.0.children.0',
            name: 'DB24',
            cabinet_type: 'base',
            length_inches: 24,
            depth_inches: 24,
            height_inches: 34.5,
            quantity: 1,
          }],
          source: 'test',
        });
      }
    });
    await page.waitForTimeout(500);
    console.log('✓ Cabinet added');

    // Add Section to Cabinet
    console.log('Adding Section with 24" W x 22" D...');
    await page.evaluate(() => {
      const builder = document.querySelector('.cabinet-spec-builder');
      const wireEl = builder?.closest('[wire\\:id]');
      if (wireEl) {
        const wireId = wireEl.getAttribute('wire:id');
        const component = (window as any).Livewire.find(wireId);
        component.call('addSection', '0.children.0.children.0.children.0');
      }
    });
    await page.waitForTimeout(300);

    // Update section dimensions to 24" W x 22" D
    await page.evaluate(() => {
      const builder = document.querySelector('.cabinet-spec-builder');
      const wireEl = builder?.closest('[wire\\:id]');
      if (wireEl) {
        const wireId = wireEl.getAttribute('wire:id');
        const component = (window as any).Livewire.find(wireId);
        component.call('updateSectionField', '0.children.0.children.0.children.0.children.0', 'width_inches', 24);
        component.call('updateSectionField', '0.children.0.children.0.children.0.children.0', 'depth_inches', 22);
      }
    });
    await page.waitForTimeout(300);
    console.log('✓ Section added');

    // Add Drawer Content
    console.log('Adding Drawer Content...');
    await page.evaluate(() => {
      const builder = document.querySelector('.cabinet-spec-builder');
      const wireEl = builder?.closest('[wire\\:id]');
      if (wireEl) {
        const wireId = wireEl.getAttribute('wire:id');
        const component = (window as any).Livewire.find(wireId);
        component.call('addContent', '0.children.0.children.0.children.0.children.0', 'drawer');
      }
    });
    await page.waitForTimeout(300);
    console.log('✓ Drawer content added');

    // Add Slide Hardware
    console.log('Adding Slide Hardware...');
    await page.evaluate(() => {
      const builder = document.querySelector('.cabinet-spec-builder');
      const wireEl = builder?.closest('[wire\\:id]');
      if (wireEl) {
        const wireId = wireEl.getAttribute('wire:id');
        const component = (window as any).Livewire.find(wireId);
        component.call('addHardware', '0.children.0.children.0.children.0.children.0.children.0', 'slide');
      }
    });
    await page.waitForTimeout(300);
    console.log('✓ Slide hardware added');

    // SELECT THE 21" SLIDE - THIS TRIGGERS AUTO-CALCULATION
    console.log(`Selecting 21" slide product (ID: ${SLIDE_21_INCH_ID})...`);
    await page.evaluate((slideId) => {
      const builder = document.querySelector('.cabinet-spec-builder');
      const wireEl = builder?.closest('[wire\\:id]');
      if (wireEl) {
        const wireId = wireEl.getAttribute('wire:id');
        const component = (window as any).Livewire.find(wireId);
        component.call('updateHardwareProduct',
          '0.children.0.children.0.children.0.children.0.children.0.children.0',
          slideId
        );
      }
    }, SLIDE_21_INCH_ID);
    await page.waitForTimeout(1500);
    console.log('✓ Slide selected - auto-calculation triggered');

    // VERIFY DRAWER DIMENSIONS
    console.log('\n=== Verifying Auto-Calculated Dimensions ===');
    const specData = await page.evaluate(() => {
      const builder = document.querySelector('.cabinet-spec-builder');
      const alpineEl = builder?.closest('[x-data]') || builder?.querySelector('[x-data]');
      if (alpineEl) {
        return (alpineEl as any)._x_dataStack?.[0]?.specData;
      }
      return null;
    });

    // Navigate to drawer
    const room = specData?.[0];
    const location = room?.children?.[0];
    const run = location?.children?.[0];
    const cabinet = run?.children?.[0];
    const section = cabinet?.children?.[0];
    const drawer = section?.children?.[0];
    const hardware = drawer?.children?.[0];

    console.log('Section:', { width: section?.width_inches, depth: section?.depth_inches });
    console.log('Drawer:', { width: drawer?.width_inches, depth: drawer?.depth_inches });
    console.log('Hardware:', { name: hardware?.name, product_id: hardware?.product_id });

    // Assertions
    expect(drawer, 'Drawer should exist').toBeDefined();
    expect(drawer?.width_inches, 'Drawer width should be set').toBeDefined();
    expect(drawer?.depth_inches, 'Drawer depth should be set').toBeDefined();

    // Verify drawer width: section width - clearance (24 - 1.378 = 22.622)
    const drawerWidth = parseFloat(drawer?.width_inches || '0');
    expect(drawerWidth).toBeCloseTo(EXPECTED_DRAWER_WIDTH, 1);
    console.log(`✓ Drawer Width: ${drawerWidth.toFixed(4)}" (expected ~${EXPECTED_DRAWER_WIDTH}")`);

    // Verify drawer depth: min(section depth 22, slide-based 20.6063) = 20.6063
    const drawerDepth = parseFloat(drawer?.depth_inches || '0');
    expect(drawerDepth).toBeCloseTo(EXPECTED_DRAWER_DEPTH, 1);
    console.log(`✓ Drawer Depth: ${drawerDepth.toFixed(4)}" (expected ~${EXPECTED_DRAWER_DEPTH}")`);

    // Verify hardware was linked
    expect(hardware?.product_id).toBe(SLIDE_21_INCH_ID);
    console.log(`✓ Hardware Product ID: ${hardware?.product_id}`);

    console.log('\n=== TEST PASSED ===');
    console.log('Drawer dimensions auto-calculated correctly!');
    console.log('- Width uses: Section Width - Slide Clearance');
    console.log('- Depth uses: min(Section Depth, Slide Length - Offset)');
  });

  test('should use section depth when shorter than slide-based depth', async ({ page }) => {
    // Test: section depth (18") < slide-based depth (20.6")
    // Expected: drawer depth = 18" (section depth wins)

    await page.goto(`${BASE_URL}/admin/project/projects/create`);
    await page.waitForLoadState('networkidle');
    await page.waitForTimeout(1000); // Wait for Livewire to initialize

    // Quick Step 1 setup - all via Livewire
    await setLivewireField(page, 'data.partner_id', 1);
    await page.waitForTimeout(300);
    await setLivewireField(page, 'data.lead_source', 'website');
    await page.waitForTimeout(300);
    await setLivewireField(page, 'data.project_type', 'residential');
    await page.waitForTimeout(300);

    // Go to Step 2
    const nextButton = page.locator('button').filter({ hasText: 'Next' }).first();
    await nextButton.click({ force: true });
    await page.waitForTimeout(2000);

    // Select "Detailed Spec" pricing mode
    const detailedSpecOption = page.locator('label').filter({ hasText: 'Detailed Spec' }).first();
    await detailedSpecOption.click();
    await page.waitForTimeout(1000);

    await page.waitForSelector('.cabinet-spec-builder', { timeout: 10000 });

    // Create full hierarchy via direct method calls (Livewire 3)
    await page.evaluate(() => {
      const builder = document.querySelector('.cabinet-spec-builder');
      const wireEl = builder?.closest('[wire\\:id]');
      if (wireEl) {
        const wireId = wireEl.getAttribute('wire:id');
        const component = (window as any).Livewire.find(wireId);
        // Call method directly instead of dispatching event
        component.call('handleAiCreateRoom', { name: 'Kitchen', room_type: 'kitchen', source: 'test' });
      }
    });
    await page.waitForTimeout(500);

    await page.evaluate(() => {
      const builder = document.querySelector('.cabinet-spec-builder');
      const wireEl = builder?.closest('[wire\\:id]');
      if (wireEl) {
        const wireId = wireEl.getAttribute('wire:id');
        const component = (window as any).Livewire.find(wireId);
        component.call('handleAiCreateLocation', { room_path: '0', name: 'Wall A', source: 'test' });
      }
    });
    await page.waitForTimeout(500);

    await page.evaluate(() => {
      const builder = document.querySelector('.cabinet-spec-builder');
      const wireEl = builder?.closest('[wire\\:id]');
      if (wireEl) {
        const wireId = wireEl.getAttribute('wire:id');
        const component = (window as any).Livewire.find(wireId);
        component.call('handleAiCreateRun', { location_path: '0.children.0', name: 'Bases', run_type: 'base', source: 'test' });
      }
    });
    await page.waitForTimeout(500);

    await page.evaluate(() => {
      const builder = document.querySelector('.cabinet-spec-builder');
      const wireEl = builder?.closest('[wire\\:id]');
      if (wireEl) {
        const wireId = wireEl.getAttribute('wire:id');
        const component = (window as any).Livewire.find(wireId);
        component.call('handleAiAddCabinet', {
          cabinets: [{ run_path: '0.children.0.children.0', name: 'B18', length_inches: 18, depth_inches: 18, height_inches: 34.5 }],
          source: 'test'
        });
      }
    });
    await page.waitForTimeout(500);

    // Add section with SHALLOW depth (18")
    await page.evaluate(() => {
      const builder = document.querySelector('.cabinet-spec-builder');
      const wireEl = builder?.closest('[wire\\:id]');
      if (wireEl) {
        const wireId = wireEl.getAttribute('wire:id');
        const component = (window as any).Livewire.find(wireId);
        component.call('addSection', '0.children.0.children.0.children.0');
      }
    });
    await page.waitForTimeout(300);

    // Set section to 18" W x 18" D
    await page.evaluate(() => {
      const builder = document.querySelector('.cabinet-spec-builder');
      const wireEl = builder?.closest('[wire\\:id]');
      if (wireEl) {
        const wireId = wireEl.getAttribute('wire:id');
        const component = (window as any).Livewire.find(wireId);
        component.call('updateSectionField', '0.children.0.children.0.children.0.children.0', 'width_inches', 18);
        component.call('updateSectionField', '0.children.0.children.0.children.0.children.0', 'depth_inches', 18);
      }
    });
    await page.waitForTimeout(300);

    // Add drawer and slide
    await page.evaluate(() => {
      const builder = document.querySelector('.cabinet-spec-builder');
      const wireEl = builder?.closest('[wire\\:id]');
      if (wireEl) {
        const wireId = wireEl.getAttribute('wire:id');
        const component = (window as any).Livewire.find(wireId);
        component.call('addContent', '0.children.0.children.0.children.0.children.0', 'drawer');
      }
    });
    await page.waitForTimeout(300);

    await page.evaluate(() => {
      const builder = document.querySelector('.cabinet-spec-builder');
      const wireEl = builder?.closest('[wire\\:id]');
      if (wireEl) {
        const wireId = wireEl.getAttribute('wire:id');
        const component = (window as any).Livewire.find(wireId);
        component.call('addHardware', '0.children.0.children.0.children.0.children.0.children.0', 'slide');
      }
    });
    await page.waitForTimeout(300);

    // Select 21" slide - section depth (18") < slide-based depth (20.6")
    await page.evaluate((slideId) => {
      const builder = document.querySelector('.cabinet-spec-builder');
      const wireEl = builder?.closest('[wire\\:id]');
      if (wireEl) {
        const wireId = wireEl.getAttribute('wire:id');
        const component = (window as any).Livewire.find(wireId);
        component.call('updateHardwareProduct',
          '0.children.0.children.0.children.0.children.0.children.0.children.0',
          slideId
        );
      }
    }, SLIDE_21_INCH_ID);
    await page.waitForTimeout(1500);

    // Verify
    const specData = await page.evaluate(() => {
      const builder = document.querySelector('.cabinet-spec-builder');
      const alpineEl = builder?.closest('[x-data]') || builder?.querySelector('[x-data]');
      if (alpineEl) {
        return (alpineEl as any)._x_dataStack?.[0]?.specData;
      }
      return null;
    });

    const drawer = specData?.[0]?.children?.[0]?.children?.[0]?.children?.[0]?.children?.[0]?.children?.[0];

    console.log('Shallow section test:');
    console.log('Section: 18" W x 18" D');
    console.log('Slide: 21" (slide-based depth = 20.6")');
    console.log('Drawer:', { width: drawer?.width_inches, depth: drawer?.depth_inches });

    // When section depth (18") < slide-based depth (20.6"), use section depth
    const drawerDepth = parseFloat(drawer?.depth_inches || '0');
    expect(drawerDepth).toBeCloseTo(18, 1);
    console.log(`✓ Drawer Depth: ${drawerDepth.toFixed(4)}" = Section depth (18" < slide 20.6")`);

    // Width = 18 - 1.378 = 16.622
    const expectedWidth = 18 - (35 / 25.4);
    const drawerWidth = parseFloat(drawer?.width_inches || '0');
    expect(drawerWidth).toBeCloseTo(expectedWidth, 1);
    console.log(`✓ Drawer Width: ${drawerWidth.toFixed(4)}" (expected ~${expectedWidth.toFixed(3)}")`);

    console.log('\n=== SHALLOW DEPTH TEST PASSED ===');
  });
});
