# How the Global Footer V2 Context System Works

**Date:** October 25, 2025

---

## Architecture Overview

The footer context system has **two mechanisms** working together:

### 1. Session-Based Loading (Primary)
- **When:** Page loads
- **How:** Footer widget reads from `session('active_context')`
- **Advantage:** Works immediately on page load

### 2. Livewire Events (Secondary/Updates)
- **When:** Real-time updates needed
- **How:** Livewire dispatches events between components
- **Advantage:** Updates footer without page refresh

---

## The Flow (Step by Step)

### When You Open a Project Edit Page:

```
1. Browser → Navigate to /admin/project/projects/25/edit

2. Laravel Router → Routes to EditProject page

3. EditProject::mount() executes:
   ↓
   a) Sets session variable:
      session(['active_context' => [
          'entityType' => 'project',
          'entityId' => 25
      ]])
   ↓
   b) Dispatches Livewire event to footer:
      $this->dispatch('set-active-context')
          ->to('app.filament.widgets.global-context-footer')

4. GlobalContextFooter Widget mounts:
   ↓
   a) Calls loadActiveContext()
   ↓
   b) Reads from session:
      $activeContext = session('active_context')
   ↓
   c) Sets widget properties:
      $this->contextType = 'project'
      $this->contextId = 25
   ↓
   d) Loads data via ProjectContextProvider:
      $provider = $registry->get('project')
      $this->contextData = $provider->loadContext(25)

5. ProjectContextProvider::loadContext(25):
   ↓
   a) Queries database:
      SELECT * FROM projects_projects WHERE id = 25
   ↓
   b) Loads customer name:
      SELECT name FROM partners_partners WHERE id = {partner_id}
   ↓
   c) Calculates estimates (hours, days, weeks)
   ↓
   d) Loads tags
   ↓
   e) Returns complete data array

6. Footer Widget Renders:
   ↓
   a) getViewData() prepares data for Blade view
   ↓
   b) getFieldSchema() from ProjectContextProvider
      returns array of Filament Infolist fields
   ↓
   c) Blade view renders with Alpine.js component

7. User Sees:
   ✅ Footer bar at bottom
   ✅ Shows: "Project #... • Customer Name"
   ✅ Can expand to see all fields
```

---

## Data Flow Diagram

```
┌─────────────────────┐
│  Project Edit Page  │
│   (EditProject)     │
└──────────┬──────────┘
           │
           │ mount()
           ├──────────────────────┐
           │                      │
           ▼                      ▼
    ┌──────────────┐      ┌─────────────┐
    │ Set Session  │      │ Dispatch    │
    │ active_context│      │ Event       │
    └──────┬───────┘      └─────┬───────┘
           │                    │
           │                    │
           └────────┬───────────┘
                    ▼
        ┌───────────────────────┐
        │ GlobalContextFooter   │
        │      Widget           │
        └───────────┬───────────┘
                    │
                    │ mount() → loadActiveContext()
                    ├───────────────────────┐
                    ▼                       │
            ┌──────────────┐                │
            │ Read Session │                │
            │ active_context│               │
            └──────┬───────┘                │
                   │                        │
                   ▼                        │
        ┌─────────────────────┐             │
        │  ContextRegistry    │             │
        │  get('project')     │             │
        └──────────┬──────────┘             │
                   │                        │
                   ▼                        │
    ┌──────────────────────────┐            │
    │  ProjectContextProvider  │            │
    │  loadContext(25)         │            │
    └──────────┬───────────────┘            │
               │                            │
               ├─ Query projects_projects   │
               ├─ Query partners_partners   │
               ├─ Calculate estimates       │
               └─ Load tags                 │
                   │                        │
                   ▼                        │
           ┌──────────────┐                 │
           │ Return Data  │                 │
           │   Array      │                 │
           └──────┬───────┘                 │
                  │                         │
                  └─────────────────────────┘
                              │
                              ▼
                  ┌───────────────────────┐
                  │  Widget Properties    │
                  │  - contextType        │
                  │  - contextId          │
                  │  - contextData        │
                  └───────────┬───────────┘
                              │
                              ▼
                  ┌───────────────────────┐
                  │   Blade View          │
                  │   + Alpine.js         │
                  └───────────┬───────────┘
                              │
                              ▼
                  ┌───────────────────────┐
                  │   User Sees Footer    │
                  │   with Project Info   │
                  └───────────────────────┘
```

---

## Troubleshooting

### Footer Shows "No Project Selected"

**Possible causes:**

1. **Session not set**
   - Check: EditProject::mount() is executing
   - Fix: Already updated to set session directly

2. **Widget not loading from session**
   - Check: GlobalContextFooter::loadActiveContext() is called
   - Fix: Already calls in mount()

3. **Provider not found**
   - Check: ProjectContextProvider registered
   - Fix: Already registered in FooterServiceProvider

4. **Database query failing**
   - Check: Project ID 25 exists in projects_projects table
   - Check Laravel logs for SQL errors

5. **Widget not rendering**
   - Check: Livewire component registered
   - Fix: Already registered as 'app.filament.widgets.global-context-footer'

---

## Testing Steps

### Test 1: Check Session

In browser console after navigating to edit page:
```javascript
// This won't work in browser, need PHP
// Check in Tinker instead:
php artisan tinker
> session('active_context')
```

### Test 2: Check Database

```sql
SELECT * FROM projects_projects WHERE id = 25;
SELECT * FROM partners_partners WHERE id = (
    SELECT partner_id FROM projects_projects WHERE id = 25
);
```

### Test 3: Check Livewire Component

Browser console:
```javascript
// Check if widget component exists
Livewire.all()
// Should show GlobalContextFooter in the list
```

### Test 4: Check Laravel Logs

```bash
tail -f storage/logs/laravel.log
# Navigate to edit page
# Look for any errors
```

---

## Recent Fix Applied

**File:** `plugins/webkul/projects/src/Filament/Resources/ProjectResource/Pages/EditProject.php`

**Changes:**
```php
// BEFORE (didn't work reliably):
$this->dispatch('set-active-context', [...]);

// AFTER (works reliably):
session(['active_context' => [...]]);  // Direct session set
$this->dispatch('set-active-context')
    ->to('app.filament.widgets.global-context-footer');  // Targeted event
```

**Why:**
- Session is read on widget mount (reliable)
- Event is sent directly to footer widget (not broadcast)
- Backup mechanism ensures context loads even if event timing fails

---

## Next Steps to Debug

1. **Hard refresh the page**: `Cmd+Shift+R` or `Ctrl+Shift+R`
2. **Check if project #25 exists** in database
3. **Open browser DevTools → Network tab** → Look for Livewire AJAX calls
4. **Check Laravel logs** for any errors
5. **Verify session is set** using tinker

---

## What You Should See

### Minimized Footer (Bottom of Page):
```
[📁] Project #25-001 • Friendship Lane Kitchen
```

### Expanded Footer (Click to expand):
```
┌────────────────────────────────────────────────────────┐
│ 📁 Project Context                                     │
├────────────────────────────────────────────────────────┤
│ Project #: 25-001 [📋 Copy]                           │
│ Customer: Friendship Lane Kitchen                      │
│ Type: Residential                                      │
│ Linear Feet: 45.5 ft                                  │
│ Estimated: 32 hours • 4 days • 0.8 weeks • 0.2 months │
│ Due Date: 2025-11-15                                  │
│ Tags: Kitchen (3 more)                                │
│                                                        │
│ [💾 Save] [🔄 Switch] [✖ Clear]                      │
└────────────────────────────────────────────────────────┘
```

---

**Status:** System is configured correctly. If footer still shows "No Project Selected", check:
1. Project exists in database
2. Session is being set
3. No JavaScript errors in console
4. No PHP errors in Laravel logs
