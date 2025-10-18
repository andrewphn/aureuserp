# V3 Annotation System - Database Structure Reference

**Date**: 2025-10-18
**Purpose**: Complete database schema reference for V3 annotation system redesign

---

## Table of Contents

1. [Core PDF Tables](#core-pdf-tables)
2. [Annotation Tables](#annotation-tables)
3. [Project Structure Tables](#project-structure-tables)
4. [Key Relationships](#key-relationships)
5. [Data Flow & Workflows](#data-flow--workflows)
6. [Model References](#model-references)

---

## Core PDF Tables

### `pdf_documents`
Main PDF file storage and metadata.

**Columns:**
- `id` (PK)
- `module_type` (varchar 50) - Polymorphic: 'project', 'quote', etc.
- `module_id` (bigint) - Foreign key to module (e.g., `projects_projects.id`)
- `file_name` (varchar 255)
- `file_path` (varchar 500) - Storage path
- `file_size` (int)
- `mime_type` (varchar 100) - Default: 'application/pdf'
- `page_count` (int) - Total pages in PDF
- `uploaded_by` (FK → `users.id`)
- `tags` (json) - Searchable tags
- `metadata` (json) - Additional PDF metadata
- `timestamps`, `soft_deletes`

**Indexes:**
- `module_type`, `module_id` (polymorphic lookup)
- `uploaded_by`

**Migration:** `2025_09_30_235934_create_pdf_documents_table.php`

---

### `pdf_pages`
Individual PDF pages with dimensions and page types.

**Columns:**
- `id` (PK)
- `document_id` (FK → `pdf_documents.id`) CASCADE
- `page_number` (int) - 1-indexed page number
- `page_type` (varchar) **NEW** - 'cover_page', 'floor_plan', 'elevation', 'section', 'detail', 'schedule', etc.
- `width` (int, nullable) - PDF page width in points
- `height` (int, nullable) - PDF page height in points
- `rotation` (int) - Default: 0 (0, 90, 180, 270)
- `thumbnail_path` (varchar 500, nullable)
- `extracted_text` (longtext, nullable) - OCR/extracted text
- `page_metadata` (json, nullable) - Additional page data
- `timestamps`

**Indexes:**
- `document_id`, `page_number` (composite)

**Migrations:**
- `2025_09_30_235949_create_pdf_pages_table.php` (base table)
- `2025_10_18_145422_add_page_type_to_pdf_pages_table.php` (adds `page_type`)

---

### `pdf_page_metadata`
Extended page classification and room linkage (separate table for detailed metadata).

**Columns:**
- `id` (PK)
- `pdf_document_id` (FK → `pdf_documents.id`) CASCADE
- `page_number` (int)
- `page_type` (varchar, nullable) - Duplicate of `pdf_pages.page_type` for historical reasons
- `room_id` (FK → `projects_rooms.id`, nullable) SET NULL
- `room_name` (varchar, nullable)
- `room_type` (varchar, nullable) - 'kitchen', 'bathroom', etc.
- `detail_number` (varchar, nullable) - Architect callout: 'A-101', 'D-3', etc.
- `notes` (text, nullable)
- `metadata` (json, nullable)
- `creator_id` (FK → `users.id`, nullable) SET NULL
- `timestamps`, `soft_deletes`

**Indexes:**
- `pdf_document_id`, `page_number` (composite)
- `page_type`

**Migration:** `plugins/webkul/projects/database/migrations/2025_10_07_193846_create_pdf_pages_table.php`

**Note:** This table predates the `page_type` column being added to `pdf_pages`. Going forward, use `pdf_pages.page_type` as the primary source.

---

### `pdf_page_rooms`
Junction table linking PDF pages to multiple rooms (one page can show multiple rooms).

**Columns:**
- `id` (PK)
- `pdf_page_id` (FK → `pdf_page_metadata.id`) CASCADE
- `room_id` (FK → `projects_rooms.id`, nullable) SET NULL
- `room_number` (varchar, nullable) - Room identifier on PDF
- `room_type` (varchar, nullable) - 'kitchen', 'bathroom', etc.
- `timestamps`

**Indexes:**
- `pdf_page_id`
- `room_id`

**Migration:** `plugins/webkul/projects/database/migrations/2025_10_07_200202_create_pdf_page_rooms_table.php`

---

## Annotation Tables

### `pdf_page_annotations`
Bounding box annotations drawn on PDF pages with hierarchical relationships.

**Purpose:** Store rectangular annotations for Locations, Cabinet Runs, and Cabinets.

**Workflow:**
1. User draws box for "Location" → Creates annotation with `room_id`
2. Within Location, draws "Cabinet Run" box → Creates annotation with `parent_annotation_id` and `cabinet_run_id`
3. Within Run, draws individual "Cabinet" boxes → Creates annotations with `cabinet_specification_id`

**Columns:**
- `id` (PK)
- `pdf_page_id` (FK → `pdf_pages.id`) CASCADE
- `parent_annotation_id` (FK → `pdf_page_annotations.id`, nullable) CASCADE - Hierarchical nesting
- `annotation_type` (varchar) - 'location', 'cabinet_run', 'cabinet'
- `label` (varchar, nullable) - User label: "Location 1", "Run A", "Cabinet B3"
- **Bounding Box (Normalized 0-1 coordinates):**
  - `x` (decimal 10,4) - Left edge (0 = left, 1 = right)
  - `y` (decimal 10,4) - Top edge (0 = top, 1 = bottom)
  - `width` (decimal 10,4) - Box width (0-1)
  - `height` (decimal 10,4) - Box height (0-1)
- `room_type` (varchar, nullable) - Annotation context type
- `color` (varchar 20, nullable) - Annotation color: '#9333ea', '#3b82f6', etc.
- **Entity Links:**
  - `room_id` (FK → `projects_rooms.id`, nullable) - For Location annotations
  - `cabinet_run_id` (FK → `projects_cabinet_runs.id`, nullable) SET NULL - For Run annotations
  - `cabinet_specification_id` (FK → `projects_cabinet_specifications.id`, nullable) SET NULL - For Cabinet annotations
- **Nutrient SDK Integration:**
  - `visual_properties` (json, nullable) - Stroke color, width, opacity
  - `nutrient_annotation_id` (text, nullable) - Nutrient SDK ID for syncing
  - `nutrient_data` (json, nullable) - Full Nutrient Instant JSON format
- `notes` (text, nullable)
- `metadata` (json, nullable)
- `created_by` (bigint, nullable)
- `creator_id` (FK → `users.id`, nullable) SET NULL
- `timestamps`, `soft_deletes`

**Indexes:**
- `pdf_page_id`
- `parent_annotation_id`
- `annotation_type`
- `cabinet_run_id`
- `cabinet_specification_id`
- `room_type`
- Composite: (`pdf_page_id`, `annotation_type`)

**Migrations:**
- `plugins/webkul/projects/database/migrations/2025_10_08_000001_create_pdf_page_annotations_table.php` (base)
- `plugins/webkul/projects/database/migrations/2025_10_08_173125_add_room_fields_to_pdf_page_annotations_table.php` (adds room fields)

**Model:** `App\Models\PdfPageAnnotation`

**Key Methods:**
- `isTopLevel()` - Returns true if `parent_annotation_id` is null
- `getAllDescendants()` - Recursive child annotations
- `toNutrientAnnotation()` - Export to Nutrient Instant JSON
- `createFromNutrient()` - Import from Nutrient annotation

**Scopes:**
- `cabinetRunAnnotations()` - Top-level run boxes
- `cabinetAnnotations()` - Nested cabinet boxes
- `byPage($pdfPageId)` - Filter by PDF page

---

### `pdf_annotation_history`
Audit trail for annotation changes.

**Columns:**
- `id` (PK)
- `annotation_id` (FK → `pdf_page_annotations.id`) CASCADE
- `change_type` (varchar) - 'created', 'updated', 'deleted'
- `old_values` (json, nullable)
- `new_values` (json, nullable)
- `changed_by` (FK → `users.id`, nullable) SET NULL
- `timestamps`

**Indexes:**
- `annotation_id`
- `changed_by`

**Migrations:**
- `2025_10_09_164508_create_pdf_annotation_history_table.php`
- `2025_10_17_173309_fix_pdf_annotation_history_foreign_key.php` (FK fix)

---

## Project Structure Tables

### `projects_rooms`
Physical spaces/rooms identified in architectural PDFs.

**Examples:** "Kitchen", "Master Bathroom", "Laundry Room", "Office"

**Columns:**
- `id` (PK)
- `project_id` (FK → `projects_projects.id`) CASCADE
- `name` (varchar) - Room name
- `room_type` (varchar, nullable) - 'kitchen', 'bathroom', 'laundry', 'office', etc.
- `floor_number` (varchar, nullable) - '1', '2', 'basement', etc.
- **PDF Reference:**
  - `pdf_page_number` (int, nullable) - Which PDF page shows this room
  - `pdf_room_label` (varchar, nullable) - Label on PDF: "Kitchen", "Detail A"
  - `pdf_detail_number` (varchar, nullable) - Architect callout: "A-3.1", "K-1"
  - `pdf_notes` (text, nullable) - Notes for locating room on PDF
- `notes` (text, nullable)
- `sort_order` (int) - Default: 0
- `creator_id` (FK → `users.id`, nullable) SET NULL
- `timestamps`, `soft_deletes`

**Indexes:**
- `project_id`
- `pdf_page_number`
- Composite: (`project_id`, `sort_order`)

**Migration:** `plugins/webkul/projects/database/migrations/2025_10_07_161656_create_projects_rooms_table.php`

**Model:** `Webkul\Project\Models\Room`

**Relationships:**
- `project()` → `Project`
- `locations()` → `RoomLocation[]`
- `cabinets()` → `CabinetSpecification[]`

**Computed Attributes:**
- `total_linear_feet` - Sum of cabinet linear feet
- `cabinet_count` - Count of cabinets
- `pdf_reference` - Formatted PDF reference string

**Scopes:**
- `ordered()` - Sort by `sort_order`, then `name`
- `byType($type)` - Filter by room type
- `byFloor($floor)` - Filter by floor number
- `onPage($pageNumber)` - Rooms on specific PDF page
- `withCounts()` - With cabinet and location counts

---

### `projects_room_locations`
Specific areas within a room where cabinets are installed.

**Examples:** "North Wall", "Island", "Peninsula", "Corner Pantry", "Vanity Wall"

**Columns:**
- `id` (PK)
- `room_id` (FK → `projects_rooms.id`) CASCADE
- `name` (varchar) - Location name
- `location_type` (varchar, nullable) - 'wall', 'island', 'peninsula', 'standalone', 'corner'
- `sequence` (int) - Default: 0 (left-to-right or clockwise order)
- `elevation_reference` (varchar, nullable) - Architectural elevation reference
- `notes` (text, nullable)
- `sort_order` (int) - Default: 0
- `creator_id` (FK → `users.id`, nullable) SET NULL
- `timestamps`, `soft_deletes`

**Indexes:**
- `room_id`
- Composite: (`room_id`, `sort_order`)

**Migration:** `plugins/webkul/projects/database/migrations/2025_10_07_161931_create_projects_room_locations_table.php`

**Model:** `Webkul\Project\Models\RoomLocation`

---

### `projects_cabinet_runs`
Continuous series of cabinets along a location.

**Examples:** "Base Run 1", "Upper Cabinets A", "Tall Pantry Section"

**Columns:**
- `id` (PK)
- `room_location_id` (FK → `projects_room_locations.id`) CASCADE
- `name` (varchar) - Run name
- `run_type` (varchar, nullable) - 'base', 'wall', 'tall', 'specialty'
- **Measurements:**
  - `total_linear_feet` (decimal 8,2) - Default: 0 (calculated from cabinets)
  - `start_wall_measurement` (decimal 8,2, nullable) - Distance from left reference point (inches)
  - `end_wall_measurement` (decimal 8,2, nullable) - Distance from right reference point (inches)
- `notes` (text, nullable)
- `sort_order` (int) - Default: 0
- `creator_id` (FK → `users.id`, nullable) SET NULL
- `timestamps`, `soft_deletes`

**Indexes:**
- `room_location_id`
- Composite: (`room_location_id`, `sort_order`)

**Migration:** `plugins/webkul/projects/database/migrations/2025_10_07_161947_create_projects_cabinet_runs_table.php`

**Model:** `Webkul\Project\Models\CabinetRun`

---

### `projects_cabinet_specifications`
Individual cabinet dimensions and specifications for fabrication.

**Purpose:** Bridge between product catalog and shop floor production.

**Workflow:**
1. Bryan selects product variant → Gets price per linear foot
2. Enters cabinet dimensions → Stores here
3. System calculates total price
4. Shop floor uses dimensions for cut lists

**Columns:**
- `id` (PK)
- **Order/Project Links:**
  - `order_line_id` (FK → `sales_order_lines.id`, nullable) CASCADE
  - `project_id` (FK → `projects_projects.id`, nullable) CASCADE
  - `room_id` (FK → `projects_rooms.id`, nullable) - Added via migration
  - `cabinet_run_id` (FK → `projects_cabinet_runs.id`, nullable) - Added via migration
- `product_variant_id` (FK → `products_products.id`) RESTRICT - Cabinet product variant
- **Physical Dimensions (Shop Floor Needs):**
  - `length_inches` (decimal 8,2) - Determines linear feet
  - `width_inches` (decimal 8,2, nullable)
  - `depth_inches` (decimal 8,2, nullable) - 12" wall, 24" base standard
  - `height_inches` (decimal 8,2, nullable) - 30" base, 84-96" tall
- **Calculated:**
  - `linear_feet` (decimal 8,2) - `length_inches / 12`
- `quantity` (int) - Default: 1
- **Pricing:**
  - `unit_price_per_lf` (decimal 10,2) - Price per linear foot from variant
  - `total_price` (decimal 10,2) - `unit_price_per_lf × linear_feet × quantity`
- **Custom Specifications:**
  - `hardware_notes` (text, nullable) - Hinges, pulls, slides
  - `custom_modifications` (text, nullable) - Extra shelves, lazy susan
  - `shop_notes` (text, nullable) - Internal production notes
- `creator_id` (FK → `users.id`, nullable) SET NULL
- `timestamps`, `soft_deletes`

**Indexes:**
- `order_line_id`
- `project_id`
- `product_variant_id`

**Migrations:**
- `plugins/webkul/projects/database/migrations/2025_10_04_124625_create_projects_cabinet_specifications_table.php` (base)
- `plugins/webkul/projects/database/migrations/2025_10_07_162003_add_room_and_run_columns_to_projects_cabinet_specifications.php` (adds room/run FKs)

**Model:** `Webkul\Project\Models\CabinetSpecification`

---

## Key Relationships

### Hierarchical Structure

```
projects_projects
    └── pdf_documents (polymorphic via module_type/module_id)
        └── pdf_pages (page_number, page_type)
            ├── pdf_page_metadata (extended classification)
            ├── pdf_page_rooms (junction table)
            └── pdf_page_annotations (hierarchical)
                ├── parent_annotation_id (self-referencing)
                ├── room_id → projects_rooms
                ├── cabinet_run_id → projects_cabinet_runs
                └── cabinet_specification_id → projects_cabinet_specifications

projects_projects
    └── projects_rooms
        ├── projects_room_locations
        │   └── projects_cabinet_runs
        │       └── projects_cabinet_specifications
        └── projects_cabinet_specifications (direct link)
```

### Annotation Entity Mapping

| Annotation Type | Links To | Purpose |
|----------------|----------|---------|
| `location` | `projects_rooms` (via `room_id`) | Define area on PDF for a room |
| `cabinet_run` | `projects_cabinet_runs` (via `cabinet_run_id`) | Define continuous cabinet series |
| `cabinet` | `projects_cabinet_specifications` (via `cabinet_specification_id`) | Define individual cabinet position |

### Page Type Workflows

| Page Type | Annotation Tools | Data Captured |
|-----------|------------------|---------------|
| `cover_page` | ❌ None | Project metadata form only |
| `floor_plan` | ✅ Locations only | Room boundaries and layout |
| `elevation` | ✅ Runs + Cabinets | Cabinet positioning and measurements |
| `section` | ✅ Runs + Cabinets | Detailed cabinet construction |
| `detail` | ✅ Cabinets only | Specific cabinet features |
| `schedule` | ❌ None | Reference data only |

---

## Data Flow & Workflows

### 1. PDF Upload & Page Classification

```mermaid
graph LR
    A[Upload PDF] --> B[Create pdf_documents]
    B --> C[Extract Pages]
    C --> D[Create pdf_pages records]
    D --> E[User classifies page_type]
    E --> F[Update pdf_pages.page_type]
    F --> G[Enable page-specific tools]
```

**Files:**
- Upload: `ReviewPdfAndPrice.php` (FilamentPHP Resource Page)
- Classification: `ReviewPdfAndPrice.php` (Page metadata form)
- Storage: `pdf_pages.page_type` column

---

### 2. V3 Annotation Creation (Floor Plan → Elevation)

**Floor Plan Workflow (Locations):**
1. User opens Floor Plan page (page_type = 'floor_plan')
2. Tools: **Draw Location** button enabled
3. User draws rectangle around "Kitchen" area
4. System creates:
   - `projects_rooms` record (name: "Kitchen")
   - `pdf_page_annotations` record:
     - `annotation_type` = 'location'
     - `room_id` = newly created room ID
     - `x, y, width, height` = normalized coordinates
     - `color` = '#9333ea' (purple)

**Elevation Workflow (Cabinet Runs + Cabinets):**
1. User opens Elevation page (page_type = 'elevation')
2. User selects Room from tree: "Kitchen"
3. Tools: **Draw Location**, **Draw Run**, **Draw Cabinet** enabled
4. User draws "Location 1" (e.g., "North Wall")
5. System creates:
   - `projects_room_locations` record (name: "North Wall")
   - `pdf_page_annotations` record:
     - `annotation_type` = 'location'
     - `room_id` = Kitchen ID
     - Parent annotation for this location area
6. User selects "North Wall" location from tree
7. User draws "Run A" rectangle
8. System creates:
   - `projects_cabinet_runs` record (name: "Run A")
   - `pdf_page_annotations` record:
     - `annotation_type` = 'cabinet_run'
     - `cabinet_run_id` = Run A ID
     - `parent_annotation_id` = Location 1 annotation ID
     - `color` = '#3b82f6' (blue)
9. User draws individual cabinet boxes within Run A
10. System creates:
    - `projects_cabinet_specifications` records (dimensions)
    - `pdf_page_annotations` records:
      - `annotation_type` = 'cabinet'
      - `cabinet_specification_id` = Cabinet ID
      - `parent_annotation_id` = Run A annotation ID
      - `color` = '#10b981' (green)

---

### 3. Per-Page Annotation Loading (V3 System)

**Current Implementation (V3 Alpine Component):**
```javascript
async loadAnnotations() {
    // Loads ALL annotations for pdf_page_id
    const response = await fetch(`/api/pdf/page/${this.pdfPageId}/annotations`);
    // Transforms to screen coordinates
}
```

**Phase 5 Enhancement (Per-Page Loading):**
```javascript
async loadAnnotationsForCurrentPage() {
    // Load only annotations for current page
    const response = await fetch(
        `/api/pdf/page/${this.pdfPageId}/annotations?page=${this.currentPage}`
    );
    // Clear existing annotations
    this.annotations = [];
    // Load fresh annotations for this page
    // Transform to screen coordinates
}
```

---

## Model References

### `App\Models\PdfPage`
**File:** `app/Models/PdfPage.php`
**Table:** `pdf_pages`

**Key Methods:**
- `getPageType(): ?string` - Returns `page_type` value
- `isCoverPage(): bool` - Checks if `page_type === 'cover_page'`
- `isFloorPlan(): bool` - Checks if `page_type === 'floor_plan'`
- `isElevation(): bool` - Checks if `page_type === 'elevation'`

**Relationships:**
- `pdfDocument()` → `PdfDocument`
- `rooms()` → `PdfPageRoom[]` (pivot records)
- `annotations()` → `PdfPageAnnotation[]`

---

### `App\Models\PdfPageAnnotation`
**File:** `app/Models/PdfPageAnnotation.php`
**Table:** `pdf_page_annotations`

**Key Methods:**
- `isTopLevel(): bool` - Returns `parent_annotation_id === null`
- `getAllDescendants()` - Recursive child annotations
- `toNutrientAnnotation(): array` - Export to Nutrient format
- `createFromNutrient(...)` - Import from Nutrient

**Relationships:**
- `pdfPage()` → `PdfPage`
- `parentAnnotation()` → `PdfPageAnnotation`
- `childAnnotations()` → `PdfPageAnnotation[]`
- `cabinetRun()` → `CabinetRun`
- `cabinetSpecification()` → `CabinetSpecification`
- `creator()` → `User`

**Scopes:**
- `cabinetRunAnnotations()` - Top-level runs
- `cabinetAnnotations()` - Nested cabinets
- `byPage($pdfPageId)` - Filter by page

---

### `Webkul\Project\Models\Room`
**File:** `plugins/webkul/projects/src/Models/Room.php`
**Table:** `projects_rooms`

**Relationships:**
- `project()` → `Project`
- `locations()` → `RoomLocation[]`
- `cabinets()` → `CabinetSpecification[]`

**Computed Attributes:**
- `total_linear_feet` - Sum of cabinet LF
- `cabinet_count` - Count of cabinets
- `pdf_reference` - Formatted PDF ref string

**Scopes:**
- `ordered()` - Sort by order/name
- `byType($type)` - Filter by room type
- `onPage($pageNumber)` - Rooms on PDF page

---

## V3 System Configuration

### Component Props (Blade)
```blade
@props([
    'pdfPageId',        // FK to pdf_pages.id
    'pdfUrl',           // Storage URL to PDF file
    'pageNumber',       // Current page number (DEPRECATED)
    'projectId',        // FK to projects_projects.id
])
```

### Alpine.js State
```javascript
{
    // Configuration
    pdfUrl: config.pdfUrl,
    pdfPageId: config.pdfPageId,
    projectId: config.projectId,

    // Pagination (NEW - Phase 2)
    currentPage: config.pageNumber || 1,  // Current page being viewed
    totalPages: config.totalPages || 1,   // Total pages in PDF
    pageType: config.pageType || null,    // Page type from pdf_pages

    // Context State
    activeRoomId: null,           // Selected room ID
    activeLocationId: null,       // Selected location ID
    drawMode: null,               // 'location', 'cabinet_run', 'cabinet'

    // Annotation State
    annotations: [],              // Array of annotations for current page
    pageDimensions: null,         // PDF page dimensions (width, height)
}
```

---

## Key Insights for Phase 2+

### 1. Page Type System is NOW Enabled ✅
- `pdf_pages.page_type` column exists
- `PdfPage` model has helper methods
- `ReviewPdfAndPrice.php` has page type selector uncommented

### 2. Annotation Coordinate System
- Annotations use **normalized coordinates** (0-1 range)
- Stored in `x, y, width, height` columns (decimal 10,4)
- V3 system transforms: Normalized ↔ Screen ↔ PDF coordinates
- Each page has independent coordinate system (no scroll offsets needed)

### 3. Hierarchical Annotation Structure
- `parent_annotation_id` creates tree structure
- Level 1: Location annotations (purple)
- Level 2: Cabinet Run annotations (blue)
- Level 3: Cabinet annotations (green)

### 4. Page-by-Page Navigation Benefits
- Eliminates scroll tracking complexity
- Each page gets fresh coordinate system
- Annotations load per-page (better performance)
- Page type determines available tools

### 5. Database Duplication Note
- `pdf_page_metadata.page_type` (legacy)
- `pdf_pages.page_type` (new, primary)
- Going forward: Use `pdf_pages.page_type` as source of truth

---

**Last Updated:** 2025-10-18
**Author:** Claude Code (V3 Annotation System Redesign)
