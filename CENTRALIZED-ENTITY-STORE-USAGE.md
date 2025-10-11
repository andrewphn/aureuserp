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
3. 🔄 **Clear cache**: `php artisan filament:cache-components`
4. 🔄 **Test workflow**: Create order → Annotate → Update data → Return to order
5. 🔄 **Add UI to annotation page** for updating customer/project data
