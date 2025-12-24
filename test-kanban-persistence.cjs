const { chromium } = require('playwright');
const { execSync } = require('child_process');

async function runDbQuery(query) {
  const result = execSync(`DB_CONNECTION=mysql php artisan tinker --execute="${query}"`, {
    cwd: '/Users/andrewphan/tcsadmin/aureuserp',
    encoding: 'utf8'
  });
  return result.trim();
}

async function testKanbanPersistence() {
  const browser = await chromium.launch({ headless: false });
  const context = await browser.newContext({
    viewport: { width: 1920, height: 1080 }
  });
  const page = await context.newPage();
  
  console.log('\n=== KANBAN BOARD PERSISTENCE TEST ===\n');
  
  try {
    // Login
    console.log('Step 1: Logging in...');
    await page.goto('http://aureuserp.test/admin/login');
    await page.fill('input[type="email"]', 'info@tcswoodwork.com');
    await page.fill('input[type="password"]', 'Lola2024!');
    await page.click('button[type="submit"]');
    await page.waitForLoadState('networkidle');
    await page.screenshot({ path: '/Users/andrewphan/tcsadmin/aureuserp/screenshots/01-login.png', fullPage: true });
    console.log('✓ Logged in successfully\n');
    
    // Navigate to Kanban board
    console.log('Step 2: Navigating to Kanban board...');
    await page.goto('http://aureuserp.test/admin/project/kanban');
    await page.waitForTimeout(2000);
    await page.screenshot({ path: '/Users/andrewphan/tcsadmin/aureuserp/screenshots/02-kanban-initial.png', fullPage: true });
    console.log('✓ Kanban board loaded\n');
    
    // Get initial state from database
    console.log('Step 3: Checking initial database state...');
    const initialState = await runDbQuery(
      "\\\\Webkul\\\\Project\\\\Models\\\\Project::with('stage')->get(['id','name','stage_id'])->each(fn(\\$p) => print(\\$p->id.': '.\\$p->name.' - Stage: '.(\\$p->stage?->name ?? 'None').' (ID: '.\\$p->stage_id.\")\\n\"));"
    );
    console.log('Initial DB State:\n', initialState);
    
    // TEST 1: Drag and Drop Test
    console.log('\n=== TEST 1: DRAG AND DROP ===');
    console.log('Looking for project cards...');
    
    await page.waitForTimeout(2000);
    await page.screenshot({ path: '/Users/andrewphan/tcsadmin/aureuserp/screenshots/03-before-drag.png', fullPage: true });
    
    // Try to find any draggable elements
    const allCards = await page.locator('[draggable="true"], .kanban-card, [data-project-id]').all();
    console.log(`Found ${allCards.length} potential draggable cards`);
    
    if (allCards.length > 0) {
      console.log('Attempting drag and drop...');
      const sourceCard = allCards[0];
      
      // Get bounding box for source
      const sourceBox = await sourceCard.boundingBox();
      if (sourceBox) {
        console.log('Source card position:', sourceBox);
        
        // Try to find target column (Design stage)
        const targetColumn = await page.locator('[data-stage-id="70"]').first();
        const targetBox = await targetColumn.boundingBox();
        
        if (targetBox) {
          console.log('Target column position:', targetBox);
          
          // Perform drag
          await page.mouse.move(sourceBox.x + sourceBox.width / 2, sourceBox.y + sourceBox.height / 2);
          await page.mouse.down();
          await page.mouse.move(targetBox.x + targetBox.width / 2, targetBox.y + 100, { steps: 10 });
          await page.mouse.up();
          
          await page.waitForTimeout(2000);
          await page.screenshot({ path: '/Users/andrewphan/tcsadmin/aureuserp/screenshots/04-after-drag.png', fullPage: true });
          
          console.log('Drag operation completed');
        }
      }
    }
    
    // Check database after drag
    console.log('\nChecking database after drag...');
    const afterDrag = await runDbQuery(
      "\\\\Webkul\\\\Project\\\\Models\\\\Project::with('stage')->get(['id','name','stage_id'])->each(fn(\\$p) => print(\\$p->id.': '.\\$p->name.' - Stage: '.(\\$p->stage?->name ?? 'None').' (ID: '.\\$p->stage_id.\")\\n\"));"
    );
    console.log('After drag DB State:\n', afterDrag);
    
    // TEST 2: Click on card
    console.log('\n=== TEST 2: CARD INTERACTION ===');
    await page.waitForTimeout(1000);
    
    if (allCards.length > 0) {
      console.log('Clicking on first card...');
      await allCards[0].click();
      await page.waitForTimeout(2000);
      await page.screenshot({ path: '/Users/andrewphan/tcsadmin/aureuserp/screenshots/05-card-click.png', fullPage: true });
      console.log('Card clicked');
      
      // Check if modal or panel opened
      const modal = await page.locator('[role="dialog"], .fi-modal, .fi-slideover').first();
      if (await modal.count() > 0) {
        console.log('✓ Modal/slideover opened');
        await page.screenshot({ path: '/Users/andrewphan/tcsadmin/aureuserp/screenshots/06-modal-open.png', fullPage: true });
        
        // Try to close
        const closeBtn = await page.locator('button[aria-label*="Close"], button:has-text("Cancel")').first();
        if (await closeBtn.count() > 0) {
          await closeBtn.click();
          await page.waitForTimeout(1000);
        }
      }
    }
    
    // Final screenshot
    await page.screenshot({ path: '/Users/andrewphan/tcsadmin/aureuserp/screenshots/07-final.png', fullPage: true });
    
    console.log('\n=== FINAL DATABASE STATE ===');
    const finalState = await runDbQuery(
      "\\\\Webkul\\\\Project\\\\Models\\\\Project::with('stage')->get(['id','name','stage_id'])->each(fn(\\$p) => print(\\$p->id.': '.\\$p->name.' - Stage: '.(\\$p->stage?->name ?? 'None').' (ID: '.\\$p->stage_id.\")\\n\"));"
    );
    console.log(finalState);
    
    console.log('\n=== TEST COMPLETE ===\n');
    
  } catch (error) {
    console.error('Error during testing:', error);
    await page.screenshot({ path: '/Users/andrewphan/tcsadmin/aureuserp/screenshots/error.png', fullPage: true });
  } finally {
    await browser.close();
  }
}

testKanbanPersistence();
