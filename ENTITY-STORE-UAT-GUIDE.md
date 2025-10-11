# Centralized Entity Store - User Acceptance Testing Guide

## For: Bryan Patton (Owner)

## Overview

This guide walks you through testing the new **Centralized Session Data Pool** that allows you to update customer/project data from anywhere in the app and have it sync automatically across all pages.

**Time Required**: 15 minutes
**What You'll Test**: Your actual workflow - creating orders, reviewing PDFs, updating data

---

## Prerequisites

1. **Clear Cache First**:
   ```bash
   ssh hg
   cd /path/to/aureuserp
   DB_CONNECTION=mysql php artisan filament:cache-components
   ```

2. **Open Browser**: Chrome, Firefox, or Safari
3. **Have DevTools Ready**: Press F12 or Cmd+Option+I

---

## Test Scenario 1: Order Creation with PDF Review (5 minutes)

### Your Normal Workflow:
You start creating an order, then need to review a PDF to get customer details, then go back to finish the order.

### Steps:

1. **Go to Create Sales Order**:
   - Navigate to `/admin/sale/orders/create`
   - Select customer: "John Doe" (or any test customer)
   - Enter project type: "Cabinets"
   - **Don't save yet**

2. **Verify Data is in Session**:
   - Press F12 to open DevTools
   - Go to Console tab
   - Type: `Alpine.store('entityStore').getEntity('order', null)`
   - **Expected**: Should show object with your entered data

3. **Navigate Away (Simulate PDF Review)**:
   - Click browser back button
   - Go to Projects or Documents
   - **Your order data is now in session**

4. **Return to Order Form**:
   - Go back to `/admin/sale/orders/create`
   - **Expected**: Form should auto-fill with your previous data
   - Customer: "John Doe" (already selected)
   - Project type: "Cabinets" (already selected)

5. **Complete the Order**:
   - Finish filling out form
   - Click "Save"
   - **Expected**: Order saves successfully, session data clears

---

## Test Scenario 2: Using Entity Editor Sidebar (5 minutes)

### Your Need:
While reviewing a PDF, you discover the customer's phone number and want to update it immediately.

### Steps:

**Note**: For this test, the sidebar component needs to be added to your PDF review page first. If not yet added, skip to Test Scenario 3.

1. **Open Project with PDF**:
   - Go to any project with uploaded PDFs
   - Click "Review PDF and Price" (or annotation page)

2. **Look for Blue Button**:
   - On the right side of screen, look for floating blue button
   - Shows number badge if data in session

3. **Click Blue Button**:
   - Sidebar slides out from right
   - Shows editable fields

4. **Update Customer Phone**:
   - Enter new phone number
   - Click outside field (blur)
   - **Expected**:
     - Field highlights green
     - Toast notification: "Updated phone"

5. **Return to Order/Project Form**:
   - Navigate back to original order or project
   - **Expected**: Phone number already updated in form

---

## Test Scenario 3: Cross-Page Data Sync (3 minutes)

### Your Workflow:
You're creating a project and learning details across multiple pages.

### Steps:

1. **Start Creating Project**:
   - Go to `/admin/project/projects/create`
   - Enter:
     - Customer: "Jane Doe"
     - Project Type: "Residential"
     - Street Address: "123 Main St"
   - **Don't save**

2. **Check Session Data**:
   - Open Console (F12)
   - Type: `window.getEntityData('project', null)`
   - **Expected**: Shows all entered data

3. **Simulate Learning New Info**:
   - While still on form, type in console:
     ```javascript
     window.updateEntityField('project', null, 'notes', 'Customer wants white oak finish');
     ```
   - **Expected**: Console returns updated data

4. **Navigate Away and Back**:
   - Go to project list
   - Return to create form
   - **Expected**: All data restored including the note you added

5. **Save Project**:
   - Complete form
   - Click "Create"
   - Check console: `window.getEntityData('project', null)`
   - **Expected**: Returns `null` (data cleared after save)

---

## Common Issues & Solutions

### Issue 1: `Alpine.store('entityStore')` is undefined

**Solution**:
```bash
# Clear cache
DB_CONNECTION=mysql php artisan filament:cache-components

# Hard refresh browser (Cmd+Shift+R or Ctrl+Shift+R)
```

### Issue 2: Data not auto-restoring

**Debug**:
```javascript
// Check if data exists
sessionStorage.getItem('entity_project_new');

// Should show JSON data
```

**If null**: Data was cleared or wasn't saved initially
**If has data**: Livewire component might not be detecting it

**Solution**: Wait 1 second after page load, then check again

### Issue 3: Updates not saving

**Debug**:
```javascript
// Try manual update
window.updateEntityField('project', null, 'test', 'value');

// Check it worked
window.getEntityField('project', null, 'test');
```

**If error**: Check console for JavaScript errors
**If null**: Entity store not initialized correctly

---

## Success Criteria

✅ **You've completed testing when**:

1. **Data Persists**: Entered data survives page navigation
2. **Auto-Restore Works**: Returning to form auto-fills previous data
3. **Manual Updates Work**: Console commands update entity store
4. **Session Clears**: Data clears after successful save
5. **No Errors**: No JavaScript errors in console
6. **Workflow Feels Natural**: System doesn't get in your way

---

## Your Feedback Needed

After testing, please provide feedback on:

### What Worked Well:
- [ ] Data persistence across pages
- [ ] Auto-restore on returning to forms
- [ ] Speed/responsiveness
- [ ] Visual feedback (if testing sidebar)

### What Needs Improvement:
- [ ] Confusing behavior?
- [ ] Missing features?
- [ ] Performance issues?
- [ ] UI/UX concerns?

### Your Workflow Specific:
- [ ] Does this match how you actually work?
- [ ] Are there pages where you'd want this but it's not available?
- [ ] What additional fields would you want to edit from sidebar?

---

## Next Steps After Testing

1. **Report Issues**: Let development team know what didn't work
2. **Request Features**: Any additional capabilities you need
3. **Identify Pages**: Which pages should have the sidebar component?
4. **Train Team**: Once approved, train David and shop team

---

## Emergency: Reset Everything

If something goes wrong and you want to start fresh:

```javascript
// In browser console
Alpine.store('entityStore').clearAll();
sessionStorage.clear();

// Then hard refresh page (Cmd+Shift+R)
```

---

## Quick Reference Commands

### Check What's in Session:
```javascript
// All entity data
Object.keys(sessionStorage)
  .filter(k => k.startsWith('entity_'))
  .forEach(k => console.log(k, JSON.parse(sessionStorage.getItem(k))));
```

### Manual Data Management:
```javascript
// Get data
window.getEntityData('project', null);

// Update field
window.updateEntityField('project', null, 'phone', '555-1234');

// Update multiple fields
window.updateEntityData('project', null, {
  phone: '555-1234',
  email: 'customer@example.com'
});

// Clear specific entity
Alpine.store('entityStore').clearEntity('project', null);

// Clear everything
Alpine.store('entityStore').clearAll();
```

---

## Support

If you encounter issues during testing:

1. **Take Screenshot**: Of error or unexpected behavior
2. **Copy Console Errors**: Press F12 → Console → Copy any red errors
3. **Note Steps**: What you did right before the issue
4. **Contact**: Development team with above info

---

## Final Test Checklist

Before completing UAT, verify:

- [ ] Tested Order creation workflow
- [ ] Tested Project creation workflow
- [ ] Verified data persists across page navigation
- [ ] Verified data auto-restores on return
- [ ] Tested manual updates (if sidebar available)
- [ ] Verified session clears after save
- [ ] No JavaScript errors in console
- [ ] Performance is acceptable (< 2 second delays)
- [ ] Matches your actual workflow
- [ ] Would use this in production

**Overall Assessment**: ⭐⭐⭐⭐⭐ (Rate 1-5 stars)

**Ready for Production?**: ✅ Yes / ❌ No / ⚠️ With Changes

**Comments**:
_____________________________________________________________________
_____________________________________________________________________
_____________________________________________________________________

---

**Thank you for testing! Your feedback helps us build tools that actually work for how you work.**
