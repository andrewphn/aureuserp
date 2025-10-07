# Manual PDF Review & Pricing Workflow

## Overview

Implemented a manual page-by-page PDF review system that allows users to manually enter rooms, cabinet runs, and pricing details while viewing architectural drawings.

## What Was Built

### 1. Manual Review Page
**File**: `plugins/webkul/projects/src/Filament/Resources/ProjectResource/Pages/ReviewPdfAndPrice.php`

**Features**:
- Split-screen layout: PDF viewer on left, data entry form on right
- Page-by-page PDF navigation (Previous/Next buttons)
- Nested repeater forms for rooms → cabinet runs
- Cabinet pricing level selector (Levels 1-5)
- Linear feet input with 0.25 step increments
- Additional items repeater for non-cabinet products
- "Try Automatic" button to pre-fill form with automatic PDF parsing
- Sales order creation from manual entries

### 2. View Template
**File**: `plugins/webkul/projects/resources/views/filament/pages/review-pdf-and-price.blade.php`

**Layout**:
- **Left Side**: PDF iframe viewer with page navigation
- **Right Side**: Manual data entry form
- **Bottom**: Action buttons (Try Automatic, Cancel, Create Sales Order)

### 3. Integration
**File**: `plugins/webkul/projects/src/Filament/Resources/ProjectResource/RelationManagers/PdfDocumentsRelationManager.php`

**Changes**:
- Replaced "Create Sales Order" button with "Review & Price" button
- Only visible on architectural drawings (`document_type === 'drawing'`)
- Links to manual review page: `/admin/project/projects/{project}/pdf-review?pdf={pdfId}`

### 4. Route Registration
**File**: `plugins/webkul/projects/src/Filament/Resources/ProjectResource.php`

**Added**:
- Route: `pdf-review` → `ReviewPdfAndPrice::route('/{record}/pdf-review')`
- Import: `use Webkul\Project\Filament\Resources\ProjectResource\Pages\ReviewPdfAndPrice;`

## User Workflow

### Manual Entry Process (Primary):
1. Open project in FilamentPHP
2. Navigate to "PDF Documents" tab
3. Click "Review & Price" on architectural drawing PDF
4. Manual review page opens with split view:
   - PDF on left, form on right
5. Navigate through PDF pages with Previous/Next buttons
6. For each room:
   - Enter room name (e.g., "Kitchen", "Pantry")
   - Add cabinet runs:
     - Run name (e.g., "Sink Wall", "Island")
     - Select cabinet level (1-5)
     - Enter linear feet
     - Optional notes
7. Add additional items (countertops, shelves, etc.)
8. Click "Create Sales Order"
9. System creates sales order with line items
10. Redirects to project view page

### Automatic Pre-Fill (Optional):
1. On manual review page, click "Try Automatic Parsing"
2. System parses PDF and extracts line items
3. Form is pre-filled with extracted data in "Auto-Parsed Items" room
4. User reviews and adjusts as needed
5. Click "Create Sales Order"

## Form Structure

### Rooms Repeater:
```php
Repeater::make('rooms')
  ->schema([
    TextInput::make('room_name'),

    Repeater::make('cabinet_runs')
      ->schema([
        TextInput::make('run_name'),
        Select::make('cabinet_level')
          ->options([
            '1' => 'Level 1 - Basic ($138/LF)',
            '2' => 'Level 2 - Standard ($168/LF)',
            '3' => 'Level 3 - Enhanced ($192/LF)',
            '4' => 'Level 4 - Premium ($210/LF)',
            '5' => 'Level 5 - Custom ($225/LF)',
          ]),
        TextInput::make('linear_feet'),
        TextInput::make('notes'),
      ])
  ])
```

### Additional Items Repeater:
```php
Repeater::make('additional_items')
  ->schema([
    Select::make('product_id')
      ->relationship('product', 'name'),
    TextInput::make('quantity'),
    TextInput::make('notes'),
  ])
```

## Sales Order Creation Logic

### Method: `createSalesOrder()`

**Process**:
1. Validate project has customer assigned
2. Create sales order header:
   - Link to project (`project_id`)
   - Customer (`partner_id`)
   - Draft state
3. Process rooms and cabinet runs:
   - Get Cabinet product with selected pricing level
   - Calculate: `linear_feet × level_price`
   - Create line with name: `"Cabinet - {room} - {run} (Level {level})"`
4. Process additional items:
   - Get product details
   - Calculate: `quantity × unit_price`
   - Create line with product name
5. Update sales order totals
6. Show success notification
7. Redirect to project view

### Method: `tryAutomatic()`

**Process**:
1. Use `PdfParsingService` to parse PDF
2. Extract line items with pattern matching
3. Group items by product type:
   - Cabinet items → Create cabinet runs in "Auto-Parsed Items" room
   - Other items → Add to additional_items
4. Pre-fill form with extracted data
5. Show notification with match count

### Method: `getCabinetProduct(int $level)`

**Process**:
1. Get Cabinet product (reference: 'CABINET')
2. Get "Pricing Level" attribute
3. Find level option (e.g., "Level 2 - Standard")
4. Calculate: `base_price + extra_price`
5. Return product info with unit price

## Key Implementation Details

### Cabinet Pricing Calculation:
```php
$product = DB::table('products_products')
    ->where('reference', 'CABINET')
    ->first();

$levelOption = DB::table('products_attribute_options')
    ->where('attribute_id', $pricingLevelAttr->id)
    ->where('name', 'LIKE', "Level {$level}%")
    ->first();

$unitPrice = $product->price + $levelOption->extra_price;
```

### Sales Order Line Creation:
```php
DB::table('sales_order_lines')->insert([
    'order_id' => $salesOrderId,
    'product_id' => $cabinetProduct['product_id'],
    'name' => "Cabinet - {$roomName} - {$run['run_name']} (Level {$level})",
    'sort' => $lineNumber++,
    'product_uom_qty' => $linearFeet,
    'price_unit' => $cabinetProduct['unit_price'],
    'price_subtotal' => $linearFeet * $cabinetProduct['unit_price'],
    'qty_delivered' => 0,
    'qty_to_invoice' => $linearFeet,
    'qty_invoiced' => 0,
]);
```

## Database Tables Used

### Primary Tables:
- `sales_orders` - Order header
  - `project_id` (links to project)
  - `partner_id` (customer)
  - `amount_untaxed`, `amount_total`

- `sales_order_lines` - Order line items
  - `order_id` (links to sales_orders)
  - `product_id` (links to products)
  - `product_uom_qty` (quantity)
  - `price_unit` (unit price)
  - `price_subtotal` (line total)
  - `sort` (line order)

- `products_products` - Products
  - Cabinet (reference: 'CABINET')
  - Countertop (reference: 'COUNTERTOP')
  - Floating Shelf (reference: 'FLOAT_SHELF')

- `products_attribute_options` - Pricing levels
  - Level 1 - Basic ($138/LF, extra_price: -30)
  - Level 2 - Standard ($168/LF, extra_price: 0)
  - Level 3 - Enhanced ($192/LF, extra_price: 24)
  - Level 4 - Premium ($210/LF, extra_price: 42)
  - Level 5 - Custom ($225/LF, extra_price: 57)

## Relationship to Automatic Parsing

### Automatic Parsing (Still Available):
**File**: `app/Services/PdfParsingService.php`

**Kept for "Try Automatic" button**:
- `parseArchitecturalDrawing()` - Parses PDF text
- `extractLineItems()` - Pattern matching for quantities
- `mapProductName()` - Maps PDF text to products
- `createSalesOrderFromParsedData()` - NOT USED (manual uses its own method)

**How It's Used**:
- Manual page calls `tryAutomatic()` method
- `tryAutomatic()` uses `PdfParsingService::parseArchitecturalDrawing()`
- Extracted data pre-fills the manual form
- User can review and adjust before creating order

## Validation & Error Handling

### Validations:
1. **Customer Check**: Project must have customer assigned before creating order
2. **Product Validation**: Cabinet product and pricing levels must exist in database
3. **Parsing Errors**: Try/catch around automatic parsing with user-friendly error messages

### Error Messages:
- "No Customer Assigned" - Customer required for sales order
- "Automatic Parsing Failed" - PDF parsing error with exception message
- "Sales Order Created" - Success notification with line count and total

## Testing Checklist

### Manual Entry:
- [ ] Upload PDF to project
- [ ] Click "Review & Price" button
- [ ] Navigate PDF pages (Previous/Next)
- [ ] Add multiple rooms
- [ ] Add multiple cabinet runs per room
- [ ] Select different cabinet levels (1-5)
- [ ] Enter linear feet values
- [ ] Add additional items (countertops, shelves)
- [ ] Create sales order
- [ ] Verify sales order has correct line items
- [ ] Verify totals are calculated correctly

### Automatic Pre-Fill:
- [ ] Click "Try Automatic Parsing"
- [ ] Verify form is pre-filled with extracted data
- [ ] Adjust pre-filled values
- [ ] Create sales order
- [ ] Verify line items match adjusted values

### Edge Cases:
- [ ] Test with project that has no customer (should show error)
- [ ] Test with PDF that has no matching products
- [ ] Test with very long room/run names
- [ ] Test with decimal linear feet (0.25, 0.5, etc.)
- [ ] Test navigation on multi-page PDFs
- [ ] Test with missing cabinet pricing levels

## Future Enhancements

### Planned Features:
1. **Live Pricing Calculator** - Show running total as user enters data
2. **Material Selection** - Auto-detect materials from PDF text (Paint Grade, White Oak, etc.)
3. **Save Draft** - Save partially entered data without creating order
4. **Copy Previous Room** - Duplicate room configuration
5. **PDF Markup** - Allow annotations on PDF while reviewing
6. **Compare Automatic vs Manual** - Side-by-side view of automatic vs manual entries
7. **Batch PDF Processing** - Upload multiple PDFs and review in sequence

### Known Limitations:
1. No live pricing calculation (must create order to see total)
2. Cannot save partial entries (all-or-nothing)
3. Automatic parsing may miss complex line items
4. No material/finish detection in automatic mode
5. PDF viewer doesn't support zoom/pan controls

## Files Modified/Created

### Created Files:
- `plugins/webkul/projects/src/Filament/Resources/ProjectResource/Pages/ReviewPdfAndPrice.php`
- `plugins/webkul/projects/resources/views/filament/pages/review-pdf-and-price.blade.php`
- `docs/manual-pdf-review-workflow.md` (this file)

### Modified Files:
- `plugins/webkul/projects/src/Filament/Resources/ProjectResource.php`
  - Added ReviewPdfAndPrice import
  - Added 'pdf-review' route to getPages()

- `plugins/webkul/projects/src/Filament/Resources/ProjectResource/RelationManagers/PdfDocumentsRelationManager.php`
  - Replaced "Create Sales Order" with "Review & Price" button
  - Changed from immediate order creation to manual review workflow

## Deployment

### Requirements:
- Existing PDF upload functionality
- Cabinet product with pricing level attributes (Levels 1-5)
- Sales order system with project_id foreign key
- PdfParsingService for automatic parsing (optional feature)

### No Database Changes Required:
- All necessary migrations were completed in previous work
- project_id already exists in sales_orders table
- Cabinet pricing levels already configured

### Route Access:
**URL Format**: `/admin/project/projects/{project_id}/pdf-review?pdf={pdf_id}`

**Example**: `/admin/project/projects/15/pdf-review?pdf=42`

## Success Metrics

**Completed**:
✅ Manual page-by-page PDF review workflow
✅ Room and cabinet run data entry form
✅ Cabinet pricing level selection (1-5)
✅ Linear feet input with decimal precision
✅ Additional items support (countertops, shelves)
✅ "Try Automatic" button for optional pre-fill
✅ Sales order creation from manual entries
✅ Project linking for sales orders
✅ User-friendly error messages
✅ Navigation controls for multi-page PDFs

**Next Steps**:
- Test with real architectural PDFs
- Gather user feedback on manual entry experience
- Enhance automatic parsing accuracy
- Add live pricing calculation
- Consider material/finish detection
