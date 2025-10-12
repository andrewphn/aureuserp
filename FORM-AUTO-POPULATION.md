# Form Auto-Population System

## Overview
**Single Point of Entry** for Bryan's workflow - select a project once, all forms auto-populate across ALL systems.

## Problem Solved
Bryan (Owner) was repeatedly entering the same information:
- Creating task → manually select project
- Creating PO → manually select project again
- Creating design task → manually select project AGAIN

This violated ADHD-friendly design principles from his persona documentation.

## Solution
When Bryan selects a project (via footer or dashboard), the system:
1. Stores it as **active context** in `sessionStorage`
2. Automatically populates ALL forms across **all Filament panels** (Admin, Customer)
3. Persists context as he navigates between pages and systems

## How It Works

### 1. Project Selection (Footer)
```javascript
// When Bryan clicks "Edit Project" in the footer
window.setActiveProjectAndPopulate(projectId)
```

### 2. Form Auto-Population
The `form-auto-populate.js` listens for context changes and:
- Fetches full project details from `/api/projects/{id}`
- Auto-fills forms with:
  - `project_id` → Project ID
  - `customer_id` / `partner_id` → Customer from project
  - `location` → Project location
  - Other project-related fields

### 3. Cross-Page Persistence
Uses `EntityStore` (sessionStorage):
```javascript
Alpine.store('entityStore').setActiveContext('project', projectId)
// Persists across page navigation for 24 hours
```

## Usage

### For Bryan (User)
1. Click "Edit Project" button in global footer
2. OR select project from dashboard
3. Navigate to any form (Tasks, POs, Designs)
4. Forms automatically pre-filled with project context

### For Developers

**Manually trigger population:**
```javascript
window.populateFormFromActiveProject()
```

**Set project and populate:**
```javascript
window.setActiveProjectAndPopulate(projectId)
```

**Check active context:**
```javascript
const context = window.getActiveContext()
// Returns: { entityType: 'project', entityId: 1, timestamp: ... }
```

## Files Modified

### JavaScript
- `resources/js/form-auto-populate.js` - New auto-population logic
- `resources/js/centralized-entity-store.js` - Existing state management

### PHP/Laravel
- `app/Http/Controllers/Api/FooterApiController.php` - Added `getProject()` method
- `routes/api.php` - Added `GET /api/projects/{id}` route
- `app/Providers/Filament/AdminPanelProvider.php` - Load form-auto-populate.js in admin panel
- `app/Providers/Filament/CustomerPanelProvider.php` - Load form-auto-populate.js in customer panel
- `app/Providers/AppServiceProvider.php` - Register JS asset
- `vite.config.js` - Build form-auto-populate.js

### Cross-System Support
The system now works across:
- **Admin Panel** (`/admin`) - Full functionality with project footer
- **Customer Panel** (`/`) - Form auto-population for customer-facing features
- All future Filament panels added to the application

## API Endpoint

**GET `/api/projects/{projectId}`**

Response:
```json
{
  "id": 1,
  "name": "25 Friendship Lane - Residential",
  "customer_id": 1,
  "customer_name": "Trottier Fine Woodworking",
  "location": "25 Friendship Lane, Nantucket",
  "status": "active",
  "tags": [...]
}
```

## Field Mappings

Forms are populated with these mappings:
```javascript
{
  'data.project_id': project.id,
  'project_id': project.id,
  'data.customer_id': project.customer_id,
  'customer_id': project.customer_id,
  'data.partner_id': project.customer_id,
  'partner_id': project.customer_id,
  'data.location': project.location,
  'location': project.location,
  'data.project_name': project.name,
  'project_name': project.name,
}
```

## ADHD-Friendly Design

Aligns with Bryan's persona requirements:
- ✅ **Reduces cognitive load** - Don't repeat data entry
- ✅ **Context persistence** - Survives page navigation
- ✅ **24-hour expiration** - Clears stale data automatically
- ✅ **Single point of entry** - Select project once
- ✅ **Automatic** - No manual intervention needed

## Future Enhancements

1. **Dashboard Widget** - Show active project prominently
2. **Quick Switch** - Dropdown to change active project
3. **Recent Projects** - Quick access to last 5 projects
4. **Auto-clear on Save** - Clear context after successful submission
5. **More Field Mappings** - Add budget, deadlines, etc.

## Testing

```bash
# Build assets
npm run build

# Clear caches
php artisan view:clear
php artisan route:clear

# Test in browser
1. Login as Bryan (info@tcswoodwork.com)
2. Click "Edit Project" in footer → selects "25 Friendship Lane"
3. Navigate to Tasks → Create Task
4. Verify project_id is pre-filled
5. Navigate to another form
6. Verify project context persists
```

## Troubleshooting

**Forms not auto-populating?**
- Check browser console for `[FormAutoPopulate]` logs
- Verify active context: `window.getActiveContext()`
- Ensure JavaScript assets loaded: Check Network tab

**Wrong project selected?**
- Clear context: `window.clearActiveContext()`
- Select correct project again

**Data not persisting?**
- Check sessionStorage: `sessionStorage.getItem('active_context')`
- Verify 24-hour expiration hasn't passed
