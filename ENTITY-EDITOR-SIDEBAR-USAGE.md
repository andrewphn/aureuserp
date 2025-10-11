# Entity Editor Sidebar - Usage Guide

## Overview

The Entity Editor Sidebar is a floating, reusable component that allows Bryan to update entity data (customers, projects, etc.) from any page in the app, especially annotation/review pages.

## Features

- **Floating Toggle Button**: Always accessible on right side of page
- **Slide-In Sidebar**: 384px wide panel with editable fields
- **Auto-Save**: Updates entity store on field blur
- **Visual Feedback**: Green highlight on recently updated fields
- **Toast Notifications**: Success messages on updates
- **Session Indicator**: Badge showing number of fields in session
- **Cross-Page Sync**: Updates sync via centralized entity store
- **Dark Mode Support**: Fully styled for dark/light themes

---

## Basic Usage

### On Annotation Page

```blade
{{-- In your annotation blade template --}}
@include('components.entity-editor-sidebar', [
    'entityType' => 'partner',
    'entityId' => $customer->id ?? null,
    'fields' => [
        [
            'name' => 'phone',
            'label' => 'Customer Phone',
            'type' => 'text',
            'placeholder' => '555-1234',
            'helper' => 'Update customer phone number'
        ],
        [
            'name' => 'email',
            'label' => 'Customer Email',
            'type' => 'email',
            'placeholder' => 'customer@example.com'
        ],
        [
            'name' => 'notes',
            'label' => 'Additional Notes',
            'type' => 'textarea',
            'placeholder' => 'Add notes discovered during annotation...'
        ],
    ]
])
```

### On PDF Review Page

```blade
{{-- plugins/webkul/projects/resources/views/filament/resources/project-resource/pages/review-pdf-and-price.blade.php --}}

<x-filament-panels::page>
    <!-- Your existing PDF review content -->
    <div>
        <!-- PDF viewer, pricing calculator, etc. -->
    </div>

    <!-- Add entity editor sidebar -->
    @include('components.entity-editor-sidebar', [
        'entityType' => 'project',
        'entityId' => $record->id ?? null,
        'fields' => [
            [
                'name' => 'location',
                'label' => 'Project Location',
                'type' => 'text',
                'helper' => 'Full address or location details'
            ],
            [
                'name' => 'estimated_linear_feet',
                'label' => 'Linear Feet (Estimated)',
                'type' => 'number',
                'placeholder' => '0'
            ],
            [
                'name' => 'scope_notes',
                'label' => 'Scope Notes',
                'type' => 'textarea',
                'placeholder' => 'Details about project scope discovered during review...'
            ],
            [
                'name' => 'budget_notes',
                'label' => 'Budget Notes',
                'type' => 'textarea'
            ],
        ]
    ])
</x-filament-panels::page>
```

---

## Configuration Options

### Required Parameters

| Parameter | Type | Description |
|-----------|------|-------------|
| `entityType` | string | Entity type: 'partner', 'project', 'order', 'quotation', etc. |
| `entityId` | int\|null | Entity ID (null for new entities) |
| `fields` | array | Array of field definitions (see below) |

### Field Definition

Each field in the `fields` array can have:

| Key | Type | Required | Description |
|-----|------|----------|-------------|
| `name` | string | ✅ | Field name (used as key in entity store) |
| `label` | string | ✅ | Display label for field |
| `type` | string | ❌ | Input type: 'text', 'email', 'tel', 'number', 'textarea' (default: 'text') |
| `placeholder` | string | ❌ | Placeholder text |
| `helper` | string | ❌ | Helper text shown below field |

---

## Complete Examples

### Example 1: Customer Editor on Annotation Page

```blade
{{-- Show customer details while annotating proposal --}}
@include('components.entity-editor-sidebar', [
    'entityType' => 'partner',
    'entityId' => $proposal->partner_id,
    'fields' => [
        [
            'name' => 'phone',
            'label' => 'Phone Number',
            'type' => 'tel',
            'placeholder' => '(555) 123-4567'
        ],
        [
            'name' => 'email',
            'label' => 'Email Address',
            'type' => 'email'
        ],
        [
            'name' => 'contact_person',
            'label' => 'Contact Person',
            'type' => 'text'
        ],
        [
            'name' => 'billing_address',
            'label' => 'Billing Address',
            'type' => 'textarea',
            'helper' => 'Full billing address if different from project location'
        ],
        [
            'name' => 'special_requirements',
            'label' => 'Special Requirements',
            'type' => 'textarea',
            'placeholder' => 'e.g., Access restrictions, parking, delivery notes...'
        ],
    ]
])
```

### Example 2: Project Editor on Multiple Pages

```blade
{{-- Can be included on any project-related page --}}
@include('components.entity-editor-sidebar', [
    'entityType' => 'project',
    'entityId' => $record->id ?? null,
    'fields' => [
        [
            'name' => 'estimated_linear_feet',
            'label' => 'Estimated Linear Feet',
            'type' => 'number',
            'placeholder' => '0',
            'helper' => 'Total LF for this project'
        ],
        [
            'name' => 'estimated_budget',
            'label' => 'Estimated Budget',
            'type' => 'number',
            'placeholder' => '0.00',
            'helper' => 'Budget amount in dollars'
        ],
        [
            'name' => 'timeline_notes',
            'label' => 'Timeline Notes',
            'type' => 'textarea',
            'placeholder' => 'Important dates, deadlines, constraints...'
        ],
        [
            'name' => 'access_details',
            'label' => 'Site Access Details',
            'type' => 'textarea',
            'placeholder' => 'How to access the site, key codes, contact person...'
        ],
    ]
])
```

### Example 3: Sales Order Editor

```blade
@include('components.entity-editor-sidebar', [
    'entityType' => 'order',
    'entityId' => $record->id ?? null,
    'fields' => [
        [
            'name' => 'delivery_instructions',
            'label' => 'Delivery Instructions',
            'type' => 'textarea',
            'placeholder' => 'Special delivery requirements...'
        ],
        [
            'name' => 'installation_notes',
            'label' => 'Installation Notes',
            'type' => 'textarea',
            'placeholder' => 'Installation requirements, wall conditions...'
        ],
        [
            'name' => 'customer_preferences',
            'label' => 'Customer Preferences',
            'type' => 'textarea',
            'placeholder' => 'Finish preferences, hardware choices...'
        ],
    ]
])
```

---

## User Workflow

### Bryan's Typical Usage:

1. **Creating an Order**:
   - Bryan starts order form
   - Enters basic customer info
   - Data auto-saves to entity store

2. **Reviews PDF Annotation Page**:
   - Bryan navigates to annotation page
   - Clicks floating blue button on right side
   - Sidebar slides out with editable fields
   - Updates customer phone number (from PDF)
   - Updates project location details
   - Fields highlight green on update

3. **Returns to Order Form**:
   - Bryan navigates back to order
   - Form auto-restores with updated phone number
   - All data preserved from annotation page

4. **Completes & Saves**:
   - Bryan finishes order
   - Clicks "Save"
   - Entity store clears automatically

---

## Customization

### Custom Styling

The component uses Tailwind CSS and supports dark mode. To customize:

```blade
{{-- Override classes by wrapping in custom div --}}
<div class="your-custom-styles">
    @include('components.entity-editor-sidebar', [...])
</div>
```

### Toggle Button Position

Edit `entity-editor-sidebar.blade.php` line ~40:

```html
<!-- Change position -->
<button
    class="absolute left-0 top-1/4 ..." <!-- Change top-1/4 to adjust vertical position -->
>
```

### Sidebar Width

Edit line ~62:

```html
<div class="... w-96 ..."> <!-- Change w-96 (384px) to w-80, w-full, etc. -->
```

---

## Integration Points

### Where to Add This Component

**Primary Locations**:
1. PDF Annotation Pages
2. PDF Review Pages
3. Document Template Preview Pages
4. Quotation Builder Pages
5. Any page where Bryan learns new information about entities

**File Locations**:
```
plugins/webkul/projects/resources/views/filament/resources/project-resource/pages/
├── review-pdf-and-price.blade.php  ← Add here
└── ... other pages

plugins/webkul/documents/resources/views/
└── annotation-page.blade.php        ← Add here

plugins/webkul/sales/resources/views/
└── quote-builder.blade.php          ← Add here
```

---

## Troubleshooting

### Sidebar Not Appearing

**Check**:
1. Component included in blade template
2. Alpine.js loaded on page
3. No JavaScript errors in console
4. Entity store initialized (`Alpine.store('entityStore')`)

### Fields Not Updating

**Check**:
1. Field `name` matches entity store key
2. Entity type and ID correct
3. Browser console for errors
4. SessionStorage has data: `sessionStorage.getItem('entity_partner_123')`

### Visual Feedback Not Working

**Check**:
1. `editedFields` array updating (inspect with Alpine DevTools)
2. CSS classes applied correctly
3. Tailwind CSS loaded on page

---

## Advanced Usage

### Conditional Fields

```blade
@php
    $fields = [
        ['name' => 'phone', 'label' => 'Phone', 'type' => 'tel'],
    ];

    // Add budget field only for commercial projects
    if ($project->type === 'commercial') {
        $fields[] = [
            'name' => 'budget',
            'label' => 'Budget',
            'type' => 'number',
            'helper' => 'Commercial project budget'
        ];
    }
@endphp

@include('components.entity-editor-sidebar', [
    'entityType' => 'project',
    'entityId' => $project->id,
    'fields' => $fields
])
```

### Dynamic Fields from Database

```blade
@php
    $customFields = \App\Models\CustomField::where('entity_type', 'partner')
        ->get()
        ->map(fn($field) => [
            'name' => $field->key,
            'label' => $field->label,
            'type' => $field->input_type,
        ])
        ->toArray();
@endphp

@include('components.entity-editor-sidebar', [
    'entityType' => 'partner',
    'entityId' => $partner->id,
    'fields' => $customFields
])
```

---

## Performance Notes

- Component is lightweight (~5KB HTML)
- No external dependencies beyond Alpine.js
- Session storage typically ~1-5KB per entity
- Updates are debounced via blur event (not real-time typing)

---

## Browser Compatibility

- Chrome/Edge: ✅ Full support
- Firefox: ✅ Full support
- Safari: ✅ Full support
- Mobile browsers: ✅ Supported (full-screen mode on small screens)

---

## Next Steps

1. Test component on annotation page
2. Verify updates sync back to forms
3. Add to all relevant pages
4. Train Bryan on usage
5. Collect feedback for improvements
