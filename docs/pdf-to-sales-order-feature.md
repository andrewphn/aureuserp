# PDF to Sales Order Feature - Implementation Summary

## Overview

Implemented a complete workflow that allows users to upload architectural drawing PDFs to projects and automatically generate sales orders with extracted line items.

## What Was Built

### 1. Product Setup
- **Cabinet Pricing Levels** (5 tiers)
  - Level 1 - Basic ($138/LF)
  - Level 2 - Standard ($168/LF) [base]
  - Level 3 - Enhanced ($192/LF)
  - Level 4 - Premium ($210/LF)
  - Level 5 - Custom ($225/LF)
  - Migration: `2025_10_06_000001_add_cabinet_pricing_level_attribute.php`

- **Countertop Product**
  - Base price: $75/SF
  - UOM: Square Foot
  - Attributes: Material, Finish, Edge Profile
  - Migration: `2025_10_06_000002_create_countertop_product.php`

- **Floating Shelf Product**
  - Base price: $18/LF
  - UOM: Linear Foot
  - Manual creation via tinker

### 2. Database Schema Updates
- **Added `project_id` to sales_orders table**
  - Migration: `2025_10_06_000003_add_project_id_to_sales_orders.php`
  - Foreign key constraint to `projects_projects`
  - ON DELETE SET NULL behavior

- **Fixed Missing Settings**
  - Added `quotation_prefix` = "Q"
  - Added `sales_order_prefix` = "SO"
  - Settings group: `sales_quotation_and_orders`

### 3. PDF Parsing Service
**File**: `app/Services/PdfParsingService.php`

**Features**:
- Uses `smalot/pdfparser` package
- Extracts line items from architectural drawings
- Pattern matching for quantities and units (LF, SF, EA)
- Product mapping:
  - "Tier 2 Cabinetry" → Cabinet (Level 2)
  - "Tier 4 Cabinetry" → Cabinet (Level 4)
  - "Floating Shelves" → Floating Shelf
  - "Millwork Countertops" → Countertop

**Key Methods**:
```php
parseArchitecturalDrawing(PdfDocument $pdfDocument): array
extractLineItems(string $text): array
mapProductName(string $productName): ?array
getCabinetProduct(int $level = 2): ?array
createSalesOrderFromParsedData(array $parsedData, int $projectId, int $partnerId): int
```

### 4. UI Integration
**File**: `plugins/webkul/projects/src/Filament/Resources/ProjectResource/RelationManagers/PdfDocumentsRelationManager.php`

**Added "Create Sales Order" Action**:
- Visible only on architectural drawings (`document_type === 'drawing'`)
- Requires project to have a customer assigned
- Parses PDF and creates sales order with line items
- Shows success notification with link to created sales order
- Reports matched vs unmatched line items

### 5. Testing Scripts
- **test-pdf-parsing.php** - Tests PDF parsing and line item extraction
- **test-sales-order-creation.php** - Tests complete workflow (parsing + sales order creation)

## Test Results

**Sample PDF**: `9.28.25_25FriendshipRevision4.pdf` (Friendship Lane Kitchen)

**Extracted Line Items**:
1. ✅ Tier 2 Cabinetry: 11.5 LF @ $168/LF = $1,932.00
2. ✅ Tier 4 Cabinetry: 35.25 LF @ $210/LF = $7,402.50
3. ✅ Floating Shelves: 4 LF @ $18/LF = $72.00
4. ✅ Millwork Countertops: 11 SF @ $75/SF = $825.00

**Total Estimate**: $10,231.50
**Match Rate**: 100% (4/4 items)

## Usage Workflow

### For End Users:
1. Open a project in FilamentPHP
2. Go to "PDF Documents" tab
3. Click "Upload" and select architectural drawing PDF
4. Set document type to "Architectural Drawing"
5. After upload, click "Create Sales Order" button on the PDF row
6. System automatically:
   - Parses the PDF
   - Extracts line items with quantities
   - Maps products from database
   - Creates sales order with all line items
   - Links sales order to project

### System Architecture

```
┌─────────────────────┐
│  Project (FilamentPHP) │
└──────────┬──────────┘
           │
           │ 1. Upload PDF
           ↓
    ┌──────────────┐
    │ PdfDocument   │
    │  - file_path  │
    │  - module_type│
    │  - module_id  │
    └──────┬───────┘
           │
           │ 2. Click "Create Sales Order"
           ↓
   ┌──────────────────────┐
   │ PdfParsingService     │
   │  - parseArchitectural │
   │  - extractLineItems   │
   │  - mapProductName     │
   └─────────┬────────────┘
             │
             │ 3. Extract & Map
             ↓
      ┌──────────────┐
      │  Line Items   │
      │  [{product_id,│
      │    quantity,  │
      │    unit_price}]│
      └──────┬────────┘
             │
             │ 4. Create Order
             ↓
       ┌─────────────┐
       │ Sales Order  │
       │  - project_id │
       │  - partner_id │
       │  - lines[]   │
       └──────────────┘
```

## Database Tables Involved

### `pdf_documents`
- `id` - Document ID
- `module_type` - "Webkul\Project\Models\Project"
- `module_id` - Project ID
- `file_path` - Path in storage
- `document_type` - "drawing" for architectural drawings
- `uploaded_by` - User ID

### `sales_orders`
- `id` - Order ID
- `project_id` - **NEW** Links to projects
- `partner_id` - Customer ID
- `partner_invoice_id` - Invoice address (same as partner)
- `partner_shipping_id` - Shipping address (same as partner)
- `state` - 'draft', 'sent', 'sale', 'cancel'
- `amount_untaxed` - Subtotal
- `amount_total` - Total

### `sales_order_lines`
- `id` - Line ID
- `order_id` - Sales order ID
- `product_id` - Product ID
- `name` - Product name
- `sort` - Line order
- `product_uom_qty` - Quantity
- `price_unit` - Unit price
- `price_subtotal` - Line total

### `products_products`
- Cabinet (reference: CABINET)
- Millwork Countertop (reference: COUNTERTOP)
- Floating Shelf (reference: FLOAT_SHELF)

## Configuration Requirements

### Settings (in `settings` table)
```json
{
  "group": "sales_quotation_and_orders",
  "quotation_prefix": "Q",
  "sales_order_prefix": "SO",
  "default_quotation_validity": 30
}
```

### Dependencies
```json
{
  "smalot/pdfparser": "^2.12"
}
```

## Future Enhancements

### Planned Features:
1. **Material Detection** - Parse PDF text for material specifications (Paint Grade, White Oak, etc.)
2. **Attribute Auto-Selection** - Map "Paint Grade Maple" → Select correct material attributes
3. **Custom Pricing Modifiers** - Apply custom depth, length modifiers from PDF text
4. **Multi-Page Support** - Extract line items from multiple PDF pages
5. **AI-Enhanced Parsing** - Use LLM to better understand architectural drawings
6. **PDF Annotations** - Allow users to mark/correct extracted data before creating order

### Known Limitations:
1. Currently matches basic patterns only ("Product: Quantity UNIT")
2. No material/finish auto-detection (manual selection needed after order creation)
3. Unmatched products are skipped (not added to order)
4. No error correction UI (parse errors require re-upload)

## Files Modified/Created

### Created Files:
- `database/migrations/2025_10_06_000001_add_cabinet_pricing_level_attribute.php`
- `database/migrations/2025_10_06_000002_create_countertop_product.php`
- `database/migrations/2025_10_06_000003_add_project_id_to_sales_orders.php`
- `app/Services/PdfParsingService.php`
- `test-pdf-parsing.php`
- `test-sales-order-creation.php`
- `docs/pdf-to-sales-order-feature.md` (this file)

### Modified Files:
- `plugins/webkul/projects/src/Filament/Resources/ProjectResource/RelationManagers/PdfDocumentsRelationManager.php`
  - Added "Create Sales Order" table action
  - Integrated PdfParsingService
  - Added success/error notifications

- `composer.json`
  - Added `smalot/pdfparser` dependency

- `settings` table (database)
  - Added `quotation_prefix` and `sales_order_prefix` settings

## Deployment Checklist

### Local/Staging:
- [x] Run migrations
- [x] Install composer dependencies (`composer require smalot/pdfparser`)
- [x] Verify settings in database
- [x] Test with sample PDF
- [x] Verify sales order creation

### Production (when ready):
- [ ] Run migrations on production database
- [ ] Update composer dependencies
- [ ] Add missing settings if not present
- [ ] Test with real architectural drawings
- [ ] Train users on workflow
- [ ] Monitor for parsing errors

## Support & Maintenance

**For Developers**:
- PDF parsing logic is in `app/Services/PdfParsingService.php`
- Product mapping can be extended in `mapProductName()` method
- Regex patterns in `extractLineItems()` can be adjusted for different PDF formats

**For Users**:
- PDF must be text-based (not scanned images)
- Line items should follow pattern: "Product Name: Quantity UNIT"
- Supported units: LF (Linear Feet), SF (Square Feet), EA (Each)
- Products must exist in database with matching names/references

## Success Metrics

**Implemented**:
✅ 100% match rate on test PDF (4/4 items)
✅ Automatic pricing calculation with levels
✅ Project-linked sales orders
✅ User-friendly error messages
✅ Test coverage with validation scripts

**Next Steps**:
- Deploy to staging for user testing
- Collect feedback on parsing accuracy
- Enhance pattern matching based on real PDFs
- Consider AI/LLM integration for complex documents
