# Centralized Entity Store - Usage Guide

## What It Does

A **session-based data pool** that lets you update entity data (customers, projects, orders) from anywhere in the app without saving to the database. Changes sync across all pages automatically.

## Use Case Example

1. Creating a sales order → enter customer details
2. Go to PDF annotation page → learn customer's phone is wrong
3. Update phone number from annotation page
4. Go back to sales order → phone number is updated
5. Save order → data goes to database

---

## Usage from Annotation Page (or anywhere)

### Get Entity Data

```javascript
// Get customer data
const customer = Alpine.store('entityStore').getEntity('partner', 123);
console.log(customer.name, customer.phone);

// Or use global helper
const customer = window.getEntityData('partner', 123);
```

### Update Entity Data (Merge)

```javascript
// Update customer phone from annotation page
Alpine.store('entityStore').updateEntity('partner', 123, {
    phone: '555-1234',
    email: 'newemail@example.com'
});

// Or use global helper
window.updateEntityData('partner', 123, {
    phone: '555-1234',
    email: 'newemail@example.com'
});

// Existing data is preserved, only specified fields are updated
```

### Update Single Field

```javascript
// Update just the phone number
Alpine.store('entityStore').updateEntityField('partner', 123, 'phone', '555-1234');

// Update nested field (e.g., address.street)
Alpine.store('entityStore').updateEntityField('partner', 123, 'address.street', '123 Main St');

// Or use global helpers
window.updateEntityField('partner', 123, 'phone', '555-1234');
```

### Get Single Field

```javascript
// Get customer phone
const phone = Alpine.store('entityStore').getEntityField('partner', 123, 'phone');

// Get nested field
const street = Alpine.store('entityStore').getEntityField('partner', 123, 'address.street');

// Or use global helper
const phone = window.getEntityField('partner', 123, 'phone');
```

---

## Integration in Annotation Page

### Example: Add input field to annotation UI

```html
<!-- In your annotation blade template -->
<div x-data="{
    customerId: {{ $order->partner_id }},
    customerPhone: ''
}" x-init="
    // Load phone from entity store
    customerPhone = Alpine.store('entityStore').getEntityField('partner', customerId, 'phone') || '';
">
    <label>Customer Phone:</label>
    <input
        type="text"
        x-model="customerPhone"
        @blur="
            // Update entity store when field loses focus
            Alpine.store('entityStore').updateEntityField('partner', customerId, 'phone', customerPhone);
        "
    />
</div>
```

### Example: Update from JavaScript

```javascript
// In your annotation JavaScript (e.g., annotation-saver.js)
function updateCustomerFromAnnotation(customerId, fieldName, value) {
    // Update entity store
    Alpine.store('entityStore').updateEntityField('partner', customerId, fieldName, value);

    // Show notification
    console.log(`Updated customer ${customerId}: ${fieldName} = ${value}`);
}

// Usage
updateCustomerFromAnnotation(123, 'phone', '555-1234');
updateCustomerFromAnnotation(123, 'notes', 'Customer prefers email contact');
```

---

## Entity Types

Common entity types you can use:

- `'partner'` - Customers/vendors from `partners_partners`
- `'project'` - Projects from `projects_projects`
- `'order'` - Sales orders
- `'quotation'` - Quotations
- `'product'` - Products
- `'employee'` - Employees

---

## Automatic Features

### Auto-sync from Forms

When you fill out a form (create/edit), data is **automatically saved to entity store** on every change. No manual code needed.

### Auto-restore on Page Load

When you open a form, data from entity store is **automatically restored** if available.

### Cross-page Sync

When data changes in entity store, **all open pages with that entity update automatically**.

### Listen for Changes

```javascript
// React to entity updates from other pages
window.addEventListener('entity-updated', (event) => {
    const { entityType, entityId, data } = event.detail;

    if (entityType === 'partner' && entityId === 123) {
        console.log('Customer updated:', data);
        // Update your annotation UI
    }
});
```

---

## Clear Data After Save

When you successfully save to database, clear the session data:

```php
// In your Livewire component after save
$this->dispatch('entity-saved', [
    'entityType' => 'partner',
    'entityId' => $this->record->id,
]);
```

---

## Example: Full Workflow

### 1. Create Order (Start)

User fills in customer details on order form:
- Name: "John Doe"
- Phone: "555-0000"

**Entity store automatically saves**: `partner:123 = { name: "John Doe", phone: "555-0000" }`

### 2. Navigate to Annotation Page

User goes to PDF annotation page for this order.

**Entity store automatically restores**: Customer data available via `getEntityData('partner', 123)`

### 3. Update from Annotation Page

```javascript
// While annotating, user learns correct phone number
window.updateEntityField('partner', 123, 'phone', '555-1234');
```

**Entity store updates**: `partner:123 = { name: "John Doe", phone: "555-1234" }`

### 4. Return to Order Form

User navigates back to order form.

**Entity store automatically syncs**: Phone field now shows "555-1234"

### 5. Save Order

User clicks "Save" on order form.

**Data saves to database**, then:
```php
$this->dispatch('entity-saved', ['entityType' => 'partner', 'entityId' => 123]);
```

**Entity store clears**: Session data removed (database is now source of truth)

---

## Technical Details

- **Storage**: `sessionStorage` (clears when browser closes)
- **Expiration**: 24 hours
- **Sync**: Real-time via browser events
- **Merge Strategy**: Deep merge (preserves existing fields)
- **Global Access**: Available via `Alpine.store('entityStore')` or `window.*` helpers

---

## Debugging

```javascript
// Check what's stored for a customer
console.log(Alpine.store('entityStore').getEntity('partner', 123));

// Check all stored entities
Object.keys(sessionStorage)
    .filter(k => k.startsWith('entity_'))
    .forEach(k => console.log(k, sessionStorage.getItem(k)));

// Clear all entity data (reset)
Alpine.store('entityStore').clearAll();
```

---

## Integration Points

### Where This Works

✅ **Order forms** - Auto-syncs customer/project data
✅ **Annotation pages** - Can read/write entity data
✅ **Project views** - Can update project details
✅ **Any custom page** - Use global helpers

### How to Add to Annotation Page

1. **Get order's customer ID** (already available in `$order->partner_id`)
2. **Add input fields** for data you want to update
3. **Use `updateEntityField()`** when user changes data
4. **Data automatically syncs** back to order form

---

## Next Steps

1. ✅ JavaScript created: `resources/js/centralized-entity-store.js`
2. ✅ Registered in `AppServiceProvider`
3. ✅ **Blacklist implementation** for filtering framework state
4. 🔄 **Test workflow**: Create order → Annotate → Update data → Return to order
5. 🔄 **Add UI to annotation page** for updating customer/project data

---

## State Management & Blacklist Implementation

### The Problem (Fixed: 2025-10-12)

The EntityStore was initially persisting **ALL** Livewire component data, including:
- Filament internal state (stage_id, mountedActions, etc.)
- Livewire framework state (_instance, _memo, etc.)
- Temporary UI state (table filters, search, etc.)

When the system tried to restore this data on page load, it caused **hundreds of 500 errors**:
```
Unable to set component data. Public property [$stage_id] not found
```

### The Solution: Blacklist Pattern

Based on industry best practices research, we implemented a **comprehensive blacklist** to filter what gets persisted.

#### ✅ What SHOULD Be Persisted
- **User-entered data**: title, description, notes
- **Business data**: amounts, prices, dates
- **Business relationships**: project_id, customer_id, partner_id
- **Form field values**: data.* fields

#### ❌ What SHOULD NOT Be Persisted (Blacklist)

```javascript
// Filament internal state
'stage_id', 'mountedActions', 'mountedActionsArguments', 'mountedActionsData',
'mountedFormComponentActions', 'mountedInfolistActions', 'defaultTableAction', 'activeTab',

// Table state (temporary UI)
'tableFilters', 'tableSearch', 'tableSortColumn', 'tableSortDirection',
'tableGrouping', 'tableRecordsPerPage',

// Livewire framework state
'_instance', '_syncData', '_memo',

// Relationship UI state
'users',
```

### Troubleshooting

#### 500 Error: "Public property [$field] not found"

**Cause**: A framework internal field is being persisted and restored.

**Solution**: Add the field to the blacklist in `resources/js/centralized-entity-store.js`

Update in **four locations**:
1. `getEntity()` method (line ~24)
2. `updateEntity()` method (line ~69)
3. `livewire:init` BLACKLISTED_FIELDS (line ~295)
4. `DOMContentLoaded` RESTORE_BLACKLIST (line ~482)

Then rebuild assets:
```bash
php artisan filament:assets
php artisan cache:clear
```

#### Data Not Persisting

**Possible causes**:
1. Field is in the blacklist (check if it's framework state)
2. sessionStorage is full (~5MB limit)
3. Data is older than 24 hours (auto-expired)

**Solution**:
```javascript
// Clear old data
Alpine.store('entityStore').clearAll();

// Check if field is blacklisted
// Review the BLACKLISTED_FIELDS array in centralized-entity-store.js
```

### Implementation Details

**Filtering Points** - Data is filtered at four critical stages:
1. **getEntity()**: When retrieving from storage
2. **updateEntity()**: Before saving to storage
3. **Livewire commit hook**: On every component update
4. **DOMContentLoaded**: When restoring on page load

**Storage Lifetime**: 24 hours (automatically cleared)

**Events Dispatched**:
- `entity-updated`: When entity data changes
- `entity-cleared`: When entity is cleared
- `active-context-changed`: When active context changes

### Testing the Fix

After the blacklist implementation:
- ✅ Page loads successfully (no timeout)
- ✅ All network requests return 200 OK (no 500 errors)
- ✅ No console errors
- ✅ EntityStore only persists user business data
- ✅ Framework state is excluded from persistence

### References
- [State Management Best Practices](https://web.dev/storage-for-the-web/)
- [Livewire Lifecycle Hooks](https://livewire.laravel.com/docs/lifecycle-hooks)
- [FilamentPHP Component Architecture](https://filamentphp.com/docs)
