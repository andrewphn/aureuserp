import { test, expect } from '@playwright/test';

test('Verify All badge matches kanban columns excluding Inbox', async ({ page }) => {
  // Navigate to kanban board
  await page.goto('/admin/project/kanban');
  await page.waitForLoadState('networkidle');
  await page.waitForTimeout(2000);

  // Expand Inbox if collapsed
  const inboxColumn = page.locator('.kanban-column').filter({ hasText: 'Inbox' }).first();
  const expandButton = inboxColumn.locator('button').first();
  const isCollapsed = await expandButton.getAttribute('aria-expanded');
  
  if (isCollapsed === 'false') {
    await expandButton.click();
    await page.waitForTimeout(500);
  }

  // Take screenshot of full page
  await page.screenshot({ 
    path: '/Users/andrewphan/tcsadmin/aureuserp/kanban-badge-verification.png',
    fullPage: true 
  });

  // Get "All" badge count
  const allBadge = page.locator('.control-bar').getByText('All').locator('..').locator('.badge');
  const allBadgeText = await allBadge.textContent();
  const allCount = parseInt(allBadgeText?.trim() || '0');
  
  console.log('\n=== BADGE COUNT VERIFICATION ===');
  console.log('All badge count: ' + allCount);

  // Count Inbox projects
  const inboxCards = await inboxColumn.locator('.kanban-card').count();
  console.log('\nInbox projects: ' + inboxCards);

  // Count projects in each kanban column (excluding Inbox)
  const columns = ['On Hold', 'Planning', 'In Progress', 'Review', 'Complete'];
  let totalKanbanProjects = 0;
  
  console.log('\nKanban column projects (excluding Inbox):');
  for (const columnName of columns) {
    const column = page.locator('.kanban-column').filter({ hasText: new RegExp('^' + columnName) });
    const count = await column.locator('.kanban-card').count();
    console.log('  ' + columnName + ': ' + count);
    totalKanbanProjects += count;
  }

  console.log('\nTotal kanban projects (excluding Inbox): ' + totalKanbanProjects);
  console.log('Total all projects (including Inbox): ' + (totalKanbanProjects + inboxCards));
  
  // Verification
  const matches = allCount === totalKanbanProjects;
  console.log('\n=== VERIFICATION RESULT ===');
  console.log('All badge (' + allCount + ') matches kanban columns excluding Inbox (' + totalKanbanProjects + '): ' + (matches ? 'YES' : 'NO'));
  
  if (!matches) {
    console.log('\nDISCREPANCY: All badge shows ' + allCount + ' but kanban has ' + totalKanbanProjects + ' projects');
  }

  await page.waitForTimeout(3000);
});
