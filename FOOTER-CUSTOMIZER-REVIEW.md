# Footer Customizer System - Review & Documentation

## 🎯 Project Overview

Built a **persona-aware, user-customizable footer system** that allows each user to configure which fields appear in their sticky footer based on their role and preferences.

---

## ✅ Completed Components (Phase 1)

### 1. Database Layer ✓

**File:** `database/migrations/2025_10_13_082345_create_footer_preferences_table.php`

**Schema:**
```sql
footer_preferences
├── id (PK)
├── user_id (FK → users, cascade delete)
├── context_type (string: 'project', 'sale', 'inventory', 'production')
├── minimized_fields (JSON array)
├── expanded_fields (JSON array)
├── field_order (JSON array)
├── is_active (boolean, default true)
└── timestamps

UNIQUE INDEX: (user_id, context_type)
INDEX: (user_id, context_type, is_active)
```

**Status:** Migrated successfully ✓

---

### 2. FooterPreference Model ✓

**File:** `app/Models/FooterPreference.php`

**Features:**
- Mass assignable attributes with JSON casting
- Relationship to User model
- Scopes for filtering:
  - `active()` - Only active preferences
  - `forContext($contextType)` - Filter by context
- Static method: `getForUser(User $user, string $contextType)`

**Usage Example:**
```php
// Get user's project footer preferences
$preference = FooterPreference::getForUser($user, 'project');

// Create new preference
$preference = FooterPreference::create([
    'user_id' => $user->id,
    'context_type' => 'project',
    'minimized_fields' => ['project_number', 'customer_name'],
    'expanded_fields' => ['project_number', 'customer_name', 'linear_feet', 'tags'],
    'is_active' => true,
]);
```

---

### 3. FooterFieldRegistry Service ✓

**File:** `app/Services/FooterFieldRegistry.php`

**Purpose:** Central registry of all available footer fields for each context type.

**Defined Fields:**

#### Project Context (12 fields):
- `project_number` - Project identifier
- `customer_name` - Customer/partner name
- `project_type` - Type of project (badge)
- `project_address` - Job site address
- `linear_feet` - Cabinet linear footage
- `estimate_hours` - Production hours estimate (metric)
- `estimate_days` - Production days estimate (metric)
- `estimate_weeks` - Production weeks estimate (metric)
- `estimate_months` - Production months estimate (metric)
- `timeline_alert` - Schedule variance alert
- `completion_date` - Desired completion date
- `tags` - Project tags

#### Sales Context (8 fields):
- `order_number`, `quote_number`, `customer_name`
- `order_total` (currency), `order_status`, `payment_status`
- `order_date`, `expected_delivery`

#### Inventory Context (8 fields):
- `item_name`, `sku`, `quantity`, `unit`
- `location`, `reorder_level`, `supplier`, `unit_cost`

#### Production Context (7 fields):
- `job_number`, `project_name`, `customer_name`
- `production_status`, `assigned_to`, `start_date`, `due_date`

**Field Types:**
- `text` - Plain text display
- `number` - Numeric value with optional suffix
- `badge` - Colored status badge
- `currency` - Formatted currency
- `date` - Formatted date
- `metric` - Visual metric with icon and color
- `alert` - Alert/warning display
- `tags` - Tag collection

**Usage Example:**
```php
$registry = new FooterFieldRegistry();

// Get all project fields
$fields = $registry->getAvailableFields('project');

// Get specific field definition
$fieldDef = $registry->getFieldDefinition('project', 'linear_feet');
// Returns: ['label' => 'Linear Feet', 'type' => 'number', 'data_key' => 'estimated_linear_feet', 'suffix' => ' LF']

// Get all context types
$contexts = $registry->getContextTypes();
// Returns: ['project' => 'Projects', 'sale' => 'Sales Orders', ...]
```

---

### 4. FooterPreferenceService ✓

**File:** `app/Services/FooterPreferenceService.php`

**Purpose:** Business logic for managing user footer preferences.

**Key Methods:**

#### User Preferences
```php
// Get preferences for one context
$prefs = $service->getUserPreferences($user, 'project');
// Returns: ['minimized_fields' => [...], 'expanded_fields' => [...], 'field_order' => [...]]

// Get all context preferences
$allPrefs = $service->getAllUserPreferences($user);
// Returns: ['project' => [...], 'sale' => [...], 'inventory' => [...], 'production' => [...]]

// Save preferences
$preference = $service->saveUserPreferences($user, 'project', [
    'minimized_fields' => ['project_number', 'timeline_alert'],
    'expanded_fields' => ['project_number', 'customer_name', 'linear_feet'],
    'field_order' => []
]);

// Reset to defaults
$service->resetToDefaults($user, 'project');

// Delete preferences
$service->deleteUserPreferences($user, 'project');
```

#### Default Preferences
```php
// Get system defaults for a context
$defaults = $service->getDefaultPreferences('project');
```

**System Defaults:**
- **Project:** Shows project number, customer, type, linear feet, all estimates, alerts, tags
- **Sale:** Shows order details, customer, total, status, payment
- **Inventory:** Shows item, SKU, quantity, unit, location, reorder level
- **Production:** Shows job number, project, customer, status, assignment, due date

#### Persona Templates
```php
// Get persona-specific defaults
$ownerPrefs = $service->getPersonaDefaults('owner', 'project');

// Apply entire persona template to user
$appliedContexts = $service->applyPersonaTemplate($user, 'owner');
// Returns: ['project', 'sale'] (contexts that were configured)
```

**Persona Templates Defined:**

**Owner (Bryan)** - ADHD-friendly, high-level KPIs only:
- Project: `project_number`, `timeline_alert` (minimized) | Critical metrics only (expanded)
- Sale: `order_number`, `order_total` (minimized)

**Project Manager (David)** - Detailed tracking:
- Project: `project_number`, `customer_name` (minimized) | Full project details (expanded)

**Sales (Trott)** - Minimal, fast access:
- Sale: `order_number`, `customer_name` (minimized) | Basic order info (expanded)

**Inventory (Ricky)** - Material-focused, simple:
- Inventory: `item_name`, `quantity` (minimized)
- Production: `job_number`, `production_status` (minimized)

---

## 📊 Test Results

**All tests passed ✓**

```
✓ Database schema working (footer_preferences table)
✓ FooterPreference model functional (CRUD operations)
✓ FooterFieldRegistry has 4 contexts, 35+ total fields defined
✓ FooterPreferenceService manages preferences correctly
✓ Persona templates apply successfully
```

**Test Script:** `test-footer-customizer.php`

---

## 🔄 How It Works

### Data Flow

1. **User logs in** → System loads their footer preferences from database
2. **User navigates** → Footer detects context (project/sale/inventory/production)
3. **Footer renders** → Shows fields based on user's saved preferences
4. **User customizes** → Saves to `footer_preferences` table
5. **Next session** → Preferences persist

### Storage Strategy

Each user has **one preference record per context**:
```
User ID: 1 (Bryan)
├── project → {minimized: [project_number, timeline_alert], expanded: [...]}
├── sale → {minimized: [order_number, order_total], expanded: [...]}
├── inventory → {minimized: [item_name, quantity], expanded: [...]}
└── production → {minimized: [job_number, status], expanded: [...]}
```

### Persona Integration

Users can apply pre-configured templates:
- **"Apply Owner Template"** → Sets Bryan's ADHD-friendly, KPI-focused layout
- **"Apply PM Template"** → Sets David's detailed project tracking layout
- **"Apply Sales Template"** → Sets Trott's minimal, fast-access layout
- **"Apply Shop Template"** → Sets Ricky's material-focused layout

---

## 📁 File Structure

```
aureuserp/
├── app/
│   ├── Models/
│   │   └── FooterPreference.php                    ← Model
│   └── Services/
│       ├── FooterFieldRegistry.php                 ← Field definitions
│       └── FooterPreferenceService.php             ← Business logic
├── database/
│   └── migrations/
│       └── 2025_10_13_082345_create_footer_preferences_table.php
├── resources/
│   └── views/
│       └── filament/
│           └── components/
│               └── project-sticky-footer-global.blade.php  ← Footer component
├── test-footer-customizer.php                      ← Test script
└── FOOTER-CUSTOMIZER-REVIEW.md                     ← This file
```

---

## 🚀 Next Steps (Phase 2)

### Still To Build:

1. **FilamentPHP Settings Page** - `ManageFooter.php`
   - Visual field selector with checkboxes
   - Tabbed interface for each context
   - Live preview of footer
   - "Apply Persona Template" buttons

2. **API Endpoints** - `/api/footer/*`
   - `GET /api/footer/preferences` - Load user prefs
   - `POST /api/footer/preferences` - Save user prefs
   - `GET /api/footer/fields/{context}` - Get available fields
   - `POST /api/footer/persona/{persona}` - Apply template

3. **Frontend Integration**
   - Update `project-sticky-footer-global.blade.php`
   - Add `loadUserPreferences()` method
   - Add dynamic field rendering based on prefs
   - Add field type renderers (text, metric, badge, etc.)

4. **Service Registration**
   - Register `FooterFieldRegistry` in AppServiceProvider
   - Register `FooterPreferenceService` in AppServiceProvider

5. **Database Seeders**
   - Seed default preferences for existing users
   - Apply persona templates based on user roles

---

## 🎨 Example: How Fields Are Defined

```php
// From FooterFieldRegistry.php

'linear_feet' => [
    'label' => 'Linear Feet',           // Display label
    'type' => 'number',                 // Field type (affects rendering)
    'description' => 'Estimated linear feet of cabinets',
    'data_key' => 'estimated_linear_feet',  // Path to data in model
    'suffix' => ' LF'                   // Display suffix
],

'estimate_hours' => [
    'label' => 'Est. Hours',
    'type' => 'metric',                 // Rendered as visual metric
    'description' => 'Estimated production hours',
    'data_key' => 'estimate.hours',    // Nested data access
    'icon' => 'clock',                 // Icon to display
    'color' => 'amber'                 // Metric color theme
],
```

---

## 💡 Usage Examples

### For Developers

```php
use App\Services\FooterPreferenceService;
use App\Services\FooterFieldRegistry;

// Get service instances
$service = app(FooterPreferenceService::class);
$registry = app(FooterFieldRegistry::class);

// Load user's project footer preferences
$prefs = $service->getUserPreferences($user, 'project');

// Get field definitions to render
foreach ($prefs['expanded_fields'] as $fieldKey) {
    $fieldDef = $registry->getFieldDefinition('project', $fieldKey);
    // Render field based on $fieldDef['type']
}

// Apply Bryan's ADHD-friendly template
$service->applyPersonaTemplate($user, 'owner');
```

### For Frontend (Alpine.js)

```javascript
// In footer component
async loadUserPreferences() {
    const response = await fetch('/api/footer/preferences');
    this.userPreferences = await response.json();
}

// Render fields dynamically
getFieldsForDisplay() {
    const contextPrefs = this.userPreferences[this.contextType];
    return this.isMinimized
        ? contextPrefs.minimized_fields
        : contextPrefs.expanded_fields;
}
```

---

## 🧪 How to Test

```bash
# Run test script
php test-footer-customizer.php

# Check database
mysql -e "SELECT * FROM footer_preferences"

# Test in tinker
php artisan tinker
>>> $user = User::first();
>>> $service = app(FooterPreferenceService::class);
>>> $service->applyPersonaTemplate($user, 'owner');
>>> FooterPreference::where('user_id', $user->id)->get();
```

---

## 📝 Notes

- **JSON Casting:** All field arrays are automatically cast to/from JSON in database
- **Cascading Deletes:** If user is deleted, their preferences are auto-deleted
- **Unique Constraint:** Each user can only have one preference per context
- **Extensible:** Easy to add new contexts or fields in FooterFieldRegistry
- **Persona System:** Pre-built templates for common user roles

---

## ✨ Key Design Decisions

1. **Separate table vs JSON column:** Used separate table for better querying and indexing
2. **Context-based:** One preference record per context type (not per page)
3. **Field registry:** Centralized field definitions for consistency
4. **Persona templates:** Role-based defaults for fast setup
5. **Service layer:** Business logic separated from models

---

**Status:** Core backend complete ✓ | Frontend integration pending

**Next Command:** Continue with Phase 2 (FilamentPHP settings page + API endpoints)
