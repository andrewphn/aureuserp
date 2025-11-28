# Quotation Line Item Description Fix

## Problem
Line item descriptions in quotation previews were showing generic internal text instead of customer-ready descriptions:
- **Before**: "Custom cabinet - configure type, style, material, and finish options to create your perfect cabinet."
- **User Request**: Need customer-facing descriptions that specify the selected attributes

## Root Cause
1. Old quotations (like #300) have `attribute_selections` = NULL
2. Even with attributes selected, the line `name` field was just "Cabinet" (product name only)
3. Template renderer was falling back to generic product description text

## Solution Implemented

### 1. Auto-Generate Customer-Facing Descriptions (QuotationResource.php)
**File**: `plugins/webkul/sales/src/Filament/Clusters/Orders/Resources/QuotationResource.php:1849-1855`

Added logic to generate descriptive line names from selected attributes:

```php
// Generate customer-facing description from product + attributes
$descriptionParts = [$product->name];
foreach ($attributeSelections as $selection) {
    $descriptionParts[] = $selection['option_name'];
}
$customerDescription = implode(' - ', $descriptionParts);
$set('name', $customerDescription);
```

**Result**: New quotations will have line descriptions like:
`"Cabinet - Level 2 Standard - Plywood Box - Paint Grade"`

### 2. Improved Template Description Logic (TemplateRenderer.php)
**File**: `plugins/webkul/sales/src/Services/TemplateRenderer.php:275-279`

Updated to use line name instead of falling back to generic product description:

```php
// For description, use line name (which includes attribute selections)
// Fall back to product name only if line name is empty
// Avoid showing generic product descriptions like "Custom cabinet - configure..."
$description = $line->name ?: ($product?->name ?? '');
$rowHtml = str_replace('{{ITEM_DESCRIPTION}}', $description, $rowHtml);
```

**Result**:
- **New quotations**: Show full attribute description
- **Old quotations**: Show product name only (cleaner than generic description)

## Testing

### Test Script Created
`test-description-generation.php` - Simulates the description generation logic

**Output**:
```
Product Name: Cabinet
Selected Attributes:
  - Pricing Level: Level 2 Standard (+$100)
  - Material Category: Plywood Box (+$50)
  - Finish Option: Paint Grade (+$25)

Generated Customer-Facing Description:
  "Cabinet - Level 2 Standard - Plywood Box - Paint Grade"
```

## Impact

### For New Quotations (Created After This Fix)
✅ **Automatic**: When you select product attributes, the line description is automatically generated
✅ **Customer-Ready**: Shows specific configuration like "Cabinet - Level 2 Standard - Plywood Box - Paint Grade"
✅ **Template Preview**: Preview button will show these customer-facing descriptions

### For Old Quotations (Like #300)
⚠️ **Partial Fix**: Will show "Cabinet" instead of generic long description
🔧 **To Get Full Description**: Edit the quotation and re-select the attributes to trigger description generation

## How to Test

1. **Create New Quotation**:
   ```
   Go to: http://aureuserp.test/admin/sale/orders/quotations/create
   1. Select customer
   2. Add line item with Cabinet product
   3. Select attributes: Pricing Level, Material Category, Finish Option
   4. Save quotation
   5. Click Preview button
   ```

   **Expected**: Preview shows "Cabinet - [Pricing Level] - [Material] - [Finish]"

2. **View Old Quotation #300**:
   ```
   Go to: http://aureuserp.test/admin/sale/orders/quotations/300
   Click Preview button
   ```

   **Expected**: Preview shows "Cabinet" (not the long generic description)

## Files Changed

1. **plugins/webkul/sales/src/Filament/Clusters/Orders/Resources/QuotationResource.php**
   - Lines 1849-1855: Added customer-facing description generation

2. **plugins/webkul/sales/src/Services/TemplateRenderer.php**
   - Lines 275-279: Updated to use line name instead of product description

## Next Steps (Optional Improvements)

1. **Update Old Quotations**: Create a migration script to regenerate descriptions for old quotations if needed
2. **Customize Format**: Adjust the description format (currently "Product - Attr1 - Attr2 - Attr3")
3. **Add Unit Tests**: Create automated tests for description generation logic
