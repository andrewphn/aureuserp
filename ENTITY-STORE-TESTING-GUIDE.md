# Centralized Entity Store - Browser Testing Guide

## Quick Verification (2 minutes)

### Step 1: Verify JavaScript Loaded

1. Open any page in admin panel: `http://aureuserp.test/admin`
2. Open browser DevTools (F12 or Cmd+Option+I)
3. Go to Console tab
4. Type: `Alpine.store('entityStore')`
5. **Expected**: Should show object with methods (getEntity, updateEntity, etc.)
6. **If undefined**: JavaScript not loading, check cache

```javascript
// Should see something like:
{
  getEntity: ƒ,
  updateEntity: ƒ,
  setEntity: ƒ,
  clearEntity: ƒ,
  // ... more methods
}
```

---

## Full Workflow Test (5 minutes)

### Test Case: Project Creation with Session Data

#### Part 1: Create Project (Save to Session)

1. Go to `/admin/project/projects/create`
2. Fill in fields:
   - Company: "TCS Woodwork"
   - Customer: "John Doe"
   - Project Type: "Residential"
   - Street Address: "123 Main St"
   - City: "Boston"
3. **Open Console** and check entity store:

```javascript
// Check what's stored
Alpine.store('entityStore').getEntity('project', null);
// Should show object with your entered data
```

4. **Expected**: All entered data shown in console

#### Part 2: Navigate Away (Preserve Session)

1. Click browser back button OR navigate to `/admin/project/projects`
2. **Data should still be in session**:

```javascript
// In console, check data is still there
Alpine.store('entityStore').getEntity('project', null);
// Should still show your data
```

3. **Expected**: Data persists after navigation

#### Part 3: Return to Form (Auto-Restore)

1. Click "Create Project" again
2. **Expected**: Form should auto-populate with your previous data
   - Company: "TCS Woodwork" (already selected)
   - Customer: "John Doe" (already selected)
   - Street: "123 Main St" (auto-filled)
   - City: "Boston" (auto-filled)

#### Part 4: Manual Update from Console (Simulate Annotation Page)

While on create form, simulate updating data from annotation page:

```javascript
// Update customer phone from "annotation page"
window.updateEntityField('project', null, 'customer_phone', '555-1234');

// Update project location notes
window.updateEntityData('project', null, {
  location_notes: 'Second floor, left unit'
});

// Check updated data
window.getEntityData('project', null);
```

**Expected**: Console shows updated data with new fields merged in

#### Part 5: Save Project (Clear Session)

1. Complete the form and click "Create"
2. After successful save, check console:

```javascript
Alpine.store('entityStore').getEntity('project', null);
// Should be null (data cleared after save)
```

3. **Expected**: Session data cleared, ready for next project

---

## Advanced Testing

### Test Cross-Page Sync (Multi-Tab)

1. **Tab 1**: Open `/admin/project/projects/create`
2. **Tab 2**: Open same page in new tab
3. **Tab 1**: Enter customer name "Jane Doe"
4. **Tab 2**: Refresh page
5. **Expected**: Tab 2 should auto-restore "Jane Doe"

### Test Field-Level Updates

```javascript
// Get specific field
window.getEntityField('project', null, 'customer_phone');
// Returns: '555-1234' or null

// Update specific field
window.updateEntityField('project', null, 'customer_phone', '555-9999');

// Get nested field (if using nested data)
window.getEntityField('project', null, 'address.street');
// Returns: '123 Main St'

// Update nested field
window.updateEntityField('project', null, 'address.street', '456 Oak Ave');
```

### Test Data Expiration

```javascript
// Manually check timestamp
const stored = sessionStorage.getItem('entity_project_new');
const parsed = JSON.parse(stored);
console.log('Data age (ms):', Date.now() - parsed.timestamp);
console.log('Will expire in:', 24*60*60*1000 - (Date.now() - parsed.timestamp), 'ms');
```

### Test Event Listeners

```javascript
// Listen for entity updates
window.addEventListener('entity-updated', (event) => {
  console.log('Entity updated:', event.detail);
});

// Now update something
window.updateEntityData('project', null, { test: 'value' });
// Should trigger console.log above
```

---

## Troubleshooting

### Problem: `Alpine.store('entityStore')` is undefined

**Solutions**:
1. Clear cache: `php artisan filament:cache-components`
2. Check JavaScript registered: Look in page source for `centralized-entity-store.js`
3. Check browser console for JavaScript errors
4. Refresh page hard (Cmd+Shift+R)

### Problem: Data not auto-restoring on page load

**Debug**:
```javascript
// Check if data exists
sessionStorage.getItem('entity_project_new');

// Check Livewire component
Livewire.all()[0]?.$wire?.data;

// Check component name
Livewire.all()[0]?.name;
// Should be something like "Webkul\Project\...\CreateProject"
```

**Common Causes**:
- Form component name doesn't contain "Create" or "Edit"
- Livewire not mounted yet (wait 500ms after page load)
- Field names don't match between store and form

### Problem: Updates not syncing between pages

**Debug**:
```javascript
// Check event firing
window.addEventListener('entity-updated', (e) => {
  console.log('Event fired:', e.detail);
});

// Manually dispatch test event
window.dispatchEvent(new CustomEvent('entity-updated', {
  detail: { entityType: 'project', entityId: null, data: {test: 1} }
}));
```

### Problem: Data persisting after save

**Check**:
```javascript
// Verify clear event was dispatched
Livewire.on('entity-saved', (event) => {
  console.log('Clear event:', event);
});
```

**Manual Clear**:
```javascript
// Clear specific entity
Alpine.store('entityStore').clearEntity('project', null);

// Nuclear option: clear ALL entities
Alpine.store('entityStore').clearAll();
```

---

## Performance Monitoring

### Check Storage Usage

```javascript
// Get all entity keys
Object.keys(sessionStorage)
  .filter(k => k.startsWith('entity_'))
  .forEach(k => {
    const size = new Blob([sessionStorage.getItem(k)]).size;
    console.log(k, ':', size, 'bytes');
  });

// Total size
const total = Object.keys(sessionStorage)
  .filter(k => k.startsWith('entity_'))
  .reduce((sum, k) => {
    return sum + new Blob([sessionStorage.getItem(k)]).size;
  }, 0);

console.log('Total entity storage:', total, 'bytes');
console.log('Estimated limit: ~5-10MB');
```

### Monitor Update Frequency

```javascript
let updateCount = 0;
window.addEventListener('entity-updated', () => {
  updateCount++;
  console.log('Total updates:', updateCount);
});
```

---

## Success Criteria Checklist

- [ ] `Alpine.store('entityStore')` returns object (not undefined)
- [ ] Entering data in form saves to sessionStorage automatically
- [ ] Data persists after navigating away
- [ ] Data auto-restores when returning to form
- [ ] Manual updates via console work correctly
- [ ] Data clears after successful save
- [ ] No JavaScript errors in console
- [ ] sessionStorage keys start with `entity_`
- [ ] Cross-tab sync works (optional enhancement)
- [ ] Update events fire correctly

---

## Next Steps After Verification

Once core functionality is verified:

1. **Phase 3**: Add annotation sidebar UI for manual entity updates
2. **Phase 4**: Test full Bryan workflow: Create → Annotate → Update → Return
3. **Phase 5**: Add visual feedback (toast notifications, field highlighting)
4. **Phase 6**: User acceptance testing with real projects

---

## Quick Test Script

Copy-paste this into browser console for automated testing:

```javascript
// Automated Entity Store Test
console.log('=== Entity Store Test Suite ===\n');

// Test 1: Store exists
const storeExists = typeof Alpine.store('entityStore') !== 'undefined';
console.log('✓ Store exists:', storeExists);

// Test 2: Create test data
window.updateEntityData('project', null, {
  name: 'Test Project',
  customer: 'Test Customer',
  phone: '555-1234'
});
console.log('✓ Data created');

// Test 3: Retrieve data
const data = window.getEntityData('project', null);
console.log('✓ Data retrieved:', data);

// Test 4: Update field
window.updateEntityField('project', null, 'phone', '555-9999');
const updatedPhone = window.getEntityField('project', null, 'phone');
console.log('✓ Field updated:', updatedPhone === '555-9999');

// Test 5: Check storage
const stored = sessionStorage.getItem('entity_project_new');
console.log('✓ SessionStorage key exists:', !!stored);

// Test 6: Clear data
Alpine.store('entityStore').clearEntity('project', null);
const cleared = window.getEntityData('project', null);
console.log('✓ Data cleared:', cleared === null);

console.log('\n=== All Tests Completed ===');
