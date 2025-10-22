import { chromium } from '@playwright/test';

(async () => {
    const browser = await chromium.launch({ headless: false, slowMo: 500 });
    const context = await browser.newContext({
        viewport: { width: 1440, height: 900 }
    });
    const page = await context.newPage();

    try {
        console.log('🧪 Testing: Room create option in annotation slideover');

        // Login
        console.log('1️⃣ Logging in...');
        await page.goto('http://aureuserp.test/admin/login');
        await page.fill('input[type="email"]', 'info@tcswoodwork.com');
        await page.fill('input[type="password"]', 'Lola2024!');
        await page.click('button[type="submit"]');
        await page.waitForTimeout(3000);
        console.log('✓ Logged in');

        // Navigate directly to projects
        console.log('2️⃣ Navigating to projects...');
        await page.goto('http://aureuserp.test/admin/projects/projects');
        await page.waitForTimeout(2000);
        
        // Click first project
        const firstRow = page.locator('table tbody tr').first();
        await firstRow.click();
        await page.waitForTimeout(2000);
        console.log('✓ Opened project');

        // Click Annotate PDF tab
        console.log('3️⃣ Opening Annotate PDF...');
        await page.click('button:has-text("Annotate PDF"), a:has-text("Annotate PDF")');
        await page.waitForTimeout(3000);
        console.log('✓ Annotation viewer loaded');

        // Take screenshot
        await page.screenshot({ path: 'annotation-page.png', fullPage: true });

        // Click Draw Room Boundary button
        console.log('4️⃣ Clicking Draw Room Boundary...');
        const roomBtn = page.locator('button').filter({ has: page.locator('svg') }).filter({ hasText: /Draw Room/i }).or(
            page.locator('button[title*="Room"]')
        );
        
        const buttonCount = await roomBtn.count();
        console.log(`Found ${buttonCount} room boundary buttons`);
        
        if (buttonCount > 0) {
            await roomBtn.first().click();
            await page.waitForTimeout(1000);
            console.log('✓ Draw mode activated');
        }

        // Draw rectangle
        console.log('5️⃣ Drawing rectangle...');
        const canvas = page.locator('canvas').first();
        const box = await canvas.boundingBox();
        
        if (box) {
            await page.mouse.move(box.x + 100, box.y + 100);
            await page.mouse.down();
            await page.mouse.move(box.x + 300, box.y + 250);
            await page.mouse.up();
            await page.waitForTimeout(2000);
            console.log('✓ Rectangle drawn');
        }

        // Wait for slideover
        console.log('6️⃣ Waiting for slideover...');
        const slideover = page.locator('h2:has-text("Edit Annotation")');
        await slideover.waitFor({ state: 'visible', timeout: 5000 });
        console.log('✓ Slideover opened');

        // Take screenshot of slideover
        await page.screenshot({ path: 'slideover-opened.png', fullPage: true });

        // Look for Room field
        console.log('7️⃣ Checking Room field...');
        const roomLabel = page.locator('label:has-text("Room")').first();
        await roomLabel.waitFor({ state: 'visible', timeout: 3000 });
        console.log('✓ Room field visible');

        // Click on room select to open dropdown
        console.log('8️⃣ Opening room dropdown...');
        
        // Try different selectors for the room select field
        const roomInput = page.locator('input[id*="room"], select[id*="room"]').first();
        await roomInput.click();
        await page.waitForTimeout(1500);
        
        await page.screenshot({ path: 'room-dropdown-opened.png', fullPage: true });
        
        // Look for create option
        console.log('9️⃣ Looking for create option...');
        const createBtn = page.locator('button:has-text("Create"), li:has-text("Create"), a:has-text("Create")').first();
        const hasCreate = await createBtn.isVisible({ timeout: 2000 }).catch(() => false);
        
        if (hasCreate) {
            console.log('✅ CREATE OPTION FOUND!');
            
            // Click it
            await createBtn.click();
            await page.waitForTimeout(2000);
            
            await page.screenshot({ path: 'create-room-modal.png', fullPage: true });
            console.log('✓ Create modal opened');
        } else {
            console.log('❌ CREATE OPTION NOT FOUND');
            
            // Log what we see
            const dropdownText = await page.locator('ul, div[role="listbox"]').first().textContent().catch(() => 'No dropdown found');
            console.log('Dropdown content:', dropdownText);
        }

        console.log('\n📊 Test complete - check screenshots');

    } catch (error) {
        console.error('\n❌ TEST FAILED:', error.message);
        await page.screenshot({ path: 'test-error.png', fullPage: true });
    } finally {
        await page.waitForTimeout(3000);
        await browser.close();
    }
})();
