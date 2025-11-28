# Database Schema Migration Report
**Project:** TCS AureusERP Cabinet Hierarchy System
**Date:** 2025-11-21
**Status:** Development - Pre-Migration Analysis

---

## Executive Summary

### Current Status: ✅ 70% Complete

Your database structure has **most of the hierarchy foundation** but is missing **critical component-level tables** required by the meeting specifications.

**Key Findings:**
- ✅ **5 of 7 hierarchy levels exist** (Project, Room, Room Location, Cabinet Run, Cabinet)
- ❌ **2 levels missing** (Components, Sections) - **CRITICAL**
- ⚠️ **Components stored as JSON** instead of normalized tables - **MUST FIX**
- ✅ **Task hierarchy support exists** but incomplete
- ⚠️ **Minor field additions needed** across existing tables

---

## Part 1: Existing Schema Inventory

### ✅ Tables That Exist and Are Good

| Table | Status | Notes |
|-------|--------|-------|
| `projects_projects` | ✅ Excellent | Core project table with all fields |
| `projects_rooms` | ✅ Good | Room management complete |
| `projects_room_locations` | ✅ Excellent | Comprehensive with electrical/plumbing |
| `projects_cabinet_runs` | ✅ Excellent | Very detailed production tracking |
| `projects_cabinet_specifications` | ⚠️ Good but needs normalization | Has JSON fields that should be tables |
| `projects_tasks` | ✅ Good | Has hierarchy foreign keys |
| `projects_bom` | ✅ Good | Bill of materials tracking |
| `hardware_requirements` | ✅ Good | Hardware tracking |
| `projects_milestones` | ✅ Good | Milestone tracking |
| `projects_tags` | ✅ Good | Tagging system |

**Total Existing Tables:** 10+ core tables ✅

---

## Part 2: Missing Tables (CRITICAL)

### ❌ Tables That MUST Be Created

| Priority | Table | Purpose | Meeting Requirement |
|----------|-------|---------|---------------------|
| **CRITICAL** | `projects_cabinet_doors` | Individual door records | "It has to be at component level" (01:20:30) |
| **CRITICAL** | `projects_cabinet_drawers` | Individual drawer records | "Each drawer as a component" (01:21:41) |
| **CRITICAL** | `projects_cabinet_shelves` | Individual shelf records | "Adjustable, fixed, or pullout" (01:57:01) |
| **CRITICAL** | `projects_cabinet_pullouts` | Specialty pullouts (Rev-a-Shelf, etc.) | "Has pullout, lazy susan" (01:15:29) |
| **CRITICAL** | `projects_cabinet_sections` | Subdivisions within cabinets | "How do I call those sections? Oh, they call sections" (01:30:40) |

---

## Part 3: Field Additions Needed

### Projects Table
**Status:** ✅ Mostly Complete | **Priority:** Medium

```sql
-- Add access permissions
ALTER TABLE projects_projects
ADD COLUMN access_permissions JSON NULL
    COMMENT 'User/role access control for project';

ALTER TABLE projects_projects
ADD COLUMN is_private BOOLEAN DEFAULT FALSE
    COMMENT 'Private vs accessible to all team';
```

**Meeting Quote (00:26-00:30):** "At the project level, we have customer details. Access."

---

### Rooms Table
**Status:** ✅ Good | **Priority:** Low

```sql
-- Add user access and pricing
ALTER TABLE projects_rooms
ADD COLUMN user_access JSON NULL
    COMMENT 'User access control for this room';

ALTER TABLE projects_rooms
ADD COLUMN estimated_value DECIMAL(10,2) NULL
    COMMENT 'Total estimated value for room';
```

**Meeting Quote (00:34-00:42):** "At the room level, we have access"

---

### Room Locations Table
**Status:** ✅ Very Good | **Priority:** High

```sql
-- Add window and wall type fields
ALTER TABLE projects_room_locations
ADD COLUMN wall_type VARCHAR(100) NULL
    COMMENT 'Wall construction: drywall, plaster, tile, stone, brick',

ADD COLUMN has_windows BOOLEAN DEFAULT FALSE
    COMMENT 'Location has windows',

ADD COLUMN window_fixtures_json TEXT NULL
    COMMENT 'JSON: window details, sizes, trim requirements',

ADD COLUMN window_notes TEXT NULL
    COMMENT 'Window considerations for cabinet installation';
```

**Meeting Quotes:**
- (00:47-00:49): "Location, we have electrical and plumbing and wind fixtures"
- (53:02-53:07): "Wall type would be room location"

---

### Cabinet Runs Table
**Status:** ✅ Excellent | **Priority:** Medium

```sql
-- Expand run_type to support all types mentioned in meeting
-- Current: 'base', 'wall', 'tall', 'specialty'
-- Add: 'trim', 'paneling', 'passage_doors'

ALTER TABLE projects_cabinet_runs
MODIFY COLUMN run_type VARCHAR(50) NULL
    COMMENT 'Type: base, upper, full, trim, paneling, passage_doors';

ALTER TABLE projects_cabinet_runs
ADD COLUMN run_subtype VARCHAR(100) NULL
    COMMENT 'For trim: crown, base_trim, chair_rail. For paneling: wainscot, full_wall';
```

**Meeting Quote (01:01:53-02:17):** "You could probably put trim in there or wall panel... And we do make doors. Like passage doors."

---

### Cabinet Specifications Table
**Status:** ⚠️ Good but Critical Changes Needed | **Priority:** HIGH

```sql
-- Add missing field
ALTER TABLE projects_cabinet_specifications
ADD COLUMN needs_countertop BOOLEAN DEFAULT TRUE
    COMMENT 'Cabinet needs top (false for stone countertops)';

-- Enhance face frame tracking
ALTER TABLE projects_cabinet_specifications
ADD COLUMN face_frame_overall_width DECIMAL(8,3) NULL
    COMMENT 'Face frame width (different from cabinet box width)',

ADD COLUMN face_frame_overall_height DECIMAL(8,3) NULL
    COMMENT 'Face frame height',

ADD COLUMN has_scribe BOOLEAN DEFAULT FALSE
    COMMENT 'Face frame has scribe strip',

ADD COLUMN scribe_width_inches DECIMAL(5,3) NULL
    COMMENT 'Scribe strip width if applicable',

ADD COLUMN is_corner_cabinet BOOLEAN DEFAULT FALSE
    COMMENT 'Cabinet is at inside/blind corner',

ADD COLUMN corner_type VARCHAR(50) NULL
    COMMENT 'inside_corner, blind_corner';
```

**Meeting Quotes:**
- (01:13:33-01:13:56): "Does it need a top? Sometimes you build a cabinet without a top"
- (01:29:10-01:29:34): "Your face frame doesn't match your cabinet width"

---

### Tasks Table
**Status:** ✅ Good but Incomplete | **Priority:** CRITICAL

```sql
-- Add support for section and component assignment
ALTER TABLE projects_tasks
ADD COLUMN section_id BIGINT UNSIGNED NULL AFTER cabinet_specification_id,
ADD FOREIGN KEY (section_id) REFERENCES projects_cabinet_sections(id) ON DELETE SET NULL;

-- Add polymorphic component support
ALTER TABLE projects_tasks
ADD COLUMN component_type VARCHAR(50) NULL
    COMMENT 'Door, Drawer, Shelf, Pullout',
ADD COLUMN component_id BIGINT UNSIGNED NULL
    COMMENT 'ID of component (polymorphic)';

-- Add index for component queries
CREATE INDEX idx_tasks_component ON projects_tasks(component_type, component_id);
```

**Meeting Quote (02:18:56):** "I can assign room level, cabinet level, cabinet run level, section level, tasks"

---

## Part 4: New Tables to Create

### 1. Cabinet Doors Table (CRITICAL)

```sql
CREATE TABLE projects_cabinet_doors (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,

    -- Relationships
    cabinet_specification_id BIGINT UNSIGNED NOT NULL,
    section_id BIGINT UNSIGNED NULL,

    -- Identification
    door_number INT DEFAULT 1
        COMMENT 'Door position in cabinet (1, 2, 3...)',
    door_name VARCHAR(100) NULL
        COMMENT 'D1, D2, Left Door, Right Door',

    -- Dimensions
    width_inches DECIMAL(8, 3) NOT NULL
        COMMENT 'Door width in inches',
    height_inches DECIMAL(8, 3) NOT NULL
        COMMENT 'Door height in inches',
    rail_width_inches DECIMAL(5, 3) NULL
        COMMENT 'Rail width (horizontal)',
    style_width_inches DECIMAL(5, 3) NULL
        COMMENT 'Style width (vertical)',
    has_check_rail BOOLEAN DEFAULT FALSE
        COMMENT 'Has center horizontal rail',
    check_rail_width_inches DECIMAL(5, 3) NULL
        COMMENT 'Check rail width if applicable',

    -- Construction
    profile_type VARCHAR(100) NULL
        COMMENT 'shaker, flat_panel, beaded, reeded, raised_panel',
    fabrication_method VARCHAR(50) NULL
        COMMENT 'cnc, five_piece_manual, slab',

    -- Finish
    finish_type VARCHAR(100) NULL
        COMMENT 'Inherits from cabinet if NULL: painted, stained, clear_coat',
    paint_color VARCHAR(100) NULL
        COMMENT 'Paint color if different from cabinet',
    stain_color VARCHAR(100) NULL
        COMMENT 'Stain color if different from cabinet',

    -- Hardware
    hinge_type VARCHAR(100) NULL
        COMMENT 'blind_inset, half_overlay, full_overlay, euro_concealed',
    hinge_model VARCHAR(100) NULL
        COMMENT 'Blum 71B9790, etc.',
    hinge_quantity INT DEFAULT 2
        COMMENT 'Number of hinges for this door',
    hinge_side VARCHAR(20) NULL
        COMMENT 'left, right',
    has_decorative_hardware BOOLEAN DEFAULT FALSE
        COMMENT 'Decorative handle/knob',
    decorative_hardware_model VARCHAR(100) NULL
        COMMENT 'Handle/knob model if applicable',

    -- Glass
    has_glass BOOLEAN DEFAULT FALSE
        COMMENT 'Glass panel door',
    glass_type VARCHAR(100) NULL
        COMMENT 'clear, seeded, frosted, mullioned',

    -- Production Tracking
    cnc_cut_at TIMESTAMP NULL
        COMMENT 'When CNC cut this door',
    edge_banded_at TIMESTAMP NULL
        COMMENT 'When edge banding completed',
    assembled_at TIMESTAMP NULL
        COMMENT 'When door assembly completed',
    sanded_at TIMESTAMP NULL
        COMMENT 'When sanding completed',
    finished_at TIMESTAMP NULL
        COMMENT 'When finish applied and cured',
    qc_passed BOOLEAN NULL
        COMMENT 'Passed quality control',
    qc_notes TEXT NULL
        COMMENT 'QC findings',

    -- Metadata
    notes TEXT NULL
        COMMENT 'Special instructions or notes',
    sort_order INT DEFAULT 0
        COMMENT 'Display order in cabinet',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    -- Foreign Keys
    FOREIGN KEY (cabinet_specification_id)
        REFERENCES projects_cabinet_specifications(id)
        ON DELETE CASCADE,
    FOREIGN KEY (section_id)
        REFERENCES projects_cabinet_sections(id)
        ON DELETE SET NULL,

    -- Indexes
    INDEX idx_cabinet_doors_cabinet (cabinet_specification_id),
    INDEX idx_cabinet_doors_section (section_id),
    INDEX idx_cabinet_doors_production (cnc_cut_at, finished_at)

) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT='Individual door components within cabinets';
```

**Meeting References:**
- Dimensions (01:26:43-01:27:41): "Width, width, height, rail and style size"
- Fabrication (01:16:27-01:16:32): "CNC vs five piece manual"
- Hinge types (01:17:08-01:17:15): "blind inset, half overlay, full overlay"
- Check rail (01:27:22-01:27:28): "What you need on doors is check rail"

---

### 2. Cabinet Drawers Table (CRITICAL)

```sql
CREATE TABLE projects_cabinet_drawers (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,

    -- Relationships
    cabinet_specification_id BIGINT UNSIGNED NOT NULL,
    section_id BIGINT UNSIGNED NULL,

    -- Identification
    drawer_number INT DEFAULT 1
        COMMENT 'Drawer position (1=top, 2, 3...)',
    drawer_name VARCHAR(100) NULL
        COMMENT 'DR1, Top Drawer, etc.',
    drawer_position VARCHAR(50) NULL
        COMMENT 'top, middle, bottom',

    -- Dimensions
    width_inches DECIMAL(8, 3) NOT NULL
        COMMENT 'Drawer front width',
    height_inches DECIMAL(8, 3) NOT NULL
        COMMENT 'Drawer front face height',
    depth_inches DECIMAL(8, 3) NOT NULL
        COMMENT 'Drawer box depth',
    top_rail_width_inches DECIMAL(5, 3) NULL
        COMMENT 'Top rail width',
    bottom_rail_width_inches DECIMAL(5, 3) NULL
        COMMENT 'Bottom rail width',
    style_width_inches DECIMAL(5, 3) NULL
        COMMENT 'Vertical style width',

    -- Box Construction
    box_material VARCHAR(100) NULL
        COMMENT 'Drawer box: maple, birch, baltic_birch',
    box_thickness DECIMAL(5, 3) NULL
        COMMENT 'Drawer side thickness (0.5" or 0.75")',
    joinery_method VARCHAR(50) NULL
        COMMENT 'dovetail, pocket_screw, dado',

    -- Front
    front_profile VARCHAR(100) NULL
        COMMENT 'shaker, flat, raised_panel',
    front_finish VARCHAR(100) NULL
        COMMENT 'Inherits from cabinet if NULL',
    fabrication_method VARCHAR(50) NULL
        COMMENT 'cnc, manual',

    -- Hardware
    slide_type VARCHAR(100) NULL
        COMMENT 'blum_tandem, blum_undermount, full_extension',
    slide_model VARCHAR(100) NULL
        COMMENT 'Specific slide model number',
    slide_length_inches DECIMAL(5, 2) NULL
        COMMENT '18", 21", 24" typical',
    slide_quantity INT DEFAULT 1
        COMMENT 'Pairs of slides (usually 1)',
    soft_close BOOLEAN DEFAULT TRUE
        COMMENT 'Soft close slides',
    decorative_hardware_model VARCHAR(100) NULL
        COMMENT 'Handle/knob model',

    -- Production Tracking
    box_cut_at TIMESTAMP NULL
        COMMENT 'When box parts cut',
    front_cut_at TIMESTAMP NULL
        COMMENT 'When front cut',
    box_assembled_at TIMESTAMP NULL
        COMMENT 'When box assembled',
    front_attached_at TIMESTAMP NULL
        COMMENT 'When front attached to box',
    sanded_at TIMESTAMP NULL,
    finished_at TIMESTAMP NULL,
    hardware_installed_at TIMESTAMP NULL
        COMMENT 'When slides installed',
    qc_passed BOOLEAN NULL,
    qc_notes TEXT NULL,

    -- Metadata
    notes TEXT NULL,
    sort_order INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    -- Foreign Keys
    FOREIGN KEY (cabinet_specification_id)
        REFERENCES projects_cabinet_specifications(id)
        ON DELETE CASCADE,
    FOREIGN KEY (section_id)
        REFERENCES projects_cabinet_sections(id)
        ON DELETE SET NULL,

    -- Indexes
    INDEX idx_cabinet_drawers_cabinet (cabinet_specification_id),
    INDEX idx_cabinet_drawers_section (section_id),
    INDEX idx_cabinet_drawers_position (drawer_position, drawer_number)

) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT='Individual drawer components within cabinets';
```

**Meeting References:**
- Dimensions (01:27:30-01:27:41): "Top rail, bottom rail, styles match drawers"
- Variable depth (01:21:41-01:21:46): "Vanity where top drawer has to be shallow"
- Component level (01:20:46): "It has to be at component level"

---

### 3. Cabinet Shelves Table (CRITICAL)

```sql
CREATE TABLE projects_cabinet_shelves (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,

    -- Relationships
    cabinet_specification_id BIGINT UNSIGNED NOT NULL,
    section_id BIGINT UNSIGNED NULL,

    -- Identification
    shelf_number INT DEFAULT 1,
    shelf_name VARCHAR(100) NULL
        COMMENT 'S1, Top Shelf, etc.',

    -- Type
    shelf_type VARCHAR(50) NOT NULL
        COMMENT 'adjustable, fixed, pullout',

    -- Dimensions
    width_inches DECIMAL(8, 3) NOT NULL,
    depth_inches DECIMAL(8, 3) NOT NULL,
    thickness_inches DECIMAL(5, 3) NULL
        COMMENT 'Typically 0.75"',

    -- Material
    material VARCHAR(100) NULL
        COMMENT 'plywood, solid_edge, melamine',
    edge_treatment VARCHAR(100) NULL
        COMMENT 'edge_banded, solid_edge, exposed',

    -- For Adjustable Shelves
    pin_hole_spacing DECIMAL(5, 3) NULL
        COMMENT '1.25" or 32mm typical',
    number_of_positions INT NULL
        COMMENT 'How many positions for adjustment',

    -- For Pull-out Shelves
    slide_model VARCHAR(100) NULL
        COMMENT 'Rev-a-Shelf or other slide system',
    weight_capacity_lbs INT NULL
        COMMENT 'Weight rating for pullout',

    -- Production Tracking
    cut_at TIMESTAMP NULL,
    edge_banded_at TIMESTAMP NULL,
    finished_at TIMESTAMP NULL,
    installed_at TIMESTAMP NULL,
    qc_passed BOOLEAN NULL,

    -- Metadata
    notes TEXT NULL,
    sort_order INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    -- Foreign Keys
    FOREIGN KEY (cabinet_specification_id)
        REFERENCES projects_cabinet_specifications(id)
        ON DELETE CASCADE,
    FOREIGN KEY (section_id)
        REFERENCES projects_cabinet_sections(id)
        ON DELETE SET NULL,

    -- Indexes
    INDEX idx_cabinet_shelves_cabinet (cabinet_specification_id),
    INDEX idx_cabinet_shelves_type (shelf_type)

) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT='Individual shelf components within cabinets';
```

**Meeting Reference (01:57:01-01:58:29):** "Adjustable, fixed or pull out"

---

### 4. Cabinet Pullouts Table (CRITICAL)

```sql
CREATE TABLE projects_cabinet_pullouts (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,

    -- Relationships
    cabinet_specification_id BIGINT UNSIGNED NOT NULL,
    section_id BIGINT UNSIGNED NULL,

    -- Identification
    pullout_name VARCHAR(100) NULL,

    -- Type
    pullout_type VARCHAR(100) NOT NULL
        COMMENT 'trash, spice_rack, tray_divider, lazy_susan, hamper, wine_rack, custom',

    -- Product Information
    manufacturer VARCHAR(100) NULL
        COMMENT 'Rev-a-Shelf, Lemans, etc.',
    model_number VARCHAR(100) NULL
        COMMENT 'Manufacturer model/part number',
    description TEXT NULL
        COMMENT 'Detailed description',

    -- Dimensions
    width_inches DECIMAL(8, 3) NULL,
    depth_inches DECIMAL(8, 3) NULL,
    height_inches DECIMAL(8, 3) NULL,

    -- Hardware
    slide_model VARCHAR(100) NULL
        COMMENT 'Slide system if applicable',
    mounting_type VARCHAR(100) NULL
        COMMENT 'bottom_mount, side_mount, door_mount',

    -- Cost
    unit_cost DECIMAL(10, 2) NULL
        COMMENT 'Cost per unit',
    quantity INT DEFAULT 1
        COMMENT 'Number of units',

    -- Production Tracking
    ordered_at TIMESTAMP NULL,
    received_at TIMESTAMP NULL,
    installed_at TIMESTAMP NULL,
    qc_passed BOOLEAN NULL,

    -- Metadata
    notes TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    -- Foreign Keys
    FOREIGN KEY (cabinet_specification_id)
        REFERENCES projects_cabinet_specifications(id)
        ON DELETE CASCADE,
    FOREIGN KEY (section_id)
        REFERENCES projects_cabinet_sections(id)
        ON DELETE SET NULL,

    -- Indexes
    INDEX idx_cabinet_pullouts_cabinet (cabinet_specification_id),
    INDEX idx_cabinet_pullouts_type (pullout_type)

) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT='Specialty pullout components and accessories';
```

**Meeting Reference (01:15:29-01:15:31):** "Has pullout, lazy susan, tray dividers, spice rack"

---

### 5. Cabinet Sections Table (CRITICAL)

```sql
CREATE TABLE projects_cabinet_sections (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,

    -- Relationships
    cabinet_specification_id BIGINT UNSIGNED NOT NULL,

    -- Identification
    section_number INT DEFAULT 1,
    name VARCHAR(255) NOT NULL
        COMMENT 'Top Drawer Section, Door Opening, Open Shelving',
    section_type VARCHAR(50) NOT NULL
        COMMENT 'drawer_stack, door_opening, open_shelving, pullout_area, appliance',

    -- Dimensions (within cabinet)
    width_inches DECIMAL(8, 3) NULL
        COMMENT 'Section width',
    height_inches DECIMAL(8, 3) NULL
        COMMENT 'Section height',
    position_from_left_inches DECIMAL(8, 3) NULL
        COMMENT 'Position from left edge of cabinet',
    position_from_bottom_inches DECIMAL(8, 3) NULL
        COMMENT 'Position from bottom of cabinet',

    -- Configuration
    component_count INT DEFAULT 0
        COMMENT 'How many doors/drawers/shelves in section',

    -- Face Frame Opening (for this section)
    opening_width_inches DECIMAL(8, 3) NULL
        COMMENT 'Face frame opening width',
    opening_height_inches DECIMAL(8, 3) NULL
        COMMENT 'Face frame opening height',

    -- Metadata
    notes TEXT NULL
        COMMENT 'Section-specific notes',
    sort_order INT DEFAULT 0
        COMMENT 'Top to bottom or left to right',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    -- Foreign Keys
    FOREIGN KEY (cabinet_specification_id)
        REFERENCES projects_cabinet_specifications(id)
        ON DELETE CASCADE,

    -- Indexes
    INDEX idx_cabinet_sections_cabinet (cabinet_specification_id),
    INDEX idx_cabinet_sections_type (section_type),
    INDEX idx_cabinet_sections_sort (cabinet_specification_id, sort_order)

) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT='Subdivisions within cabinets for organizing components';
```

**Meeting Reference (01:30:40-01:30:45):** "How do I call those sections? Oh, they call sections"

---

## Part 5: Data Migration Strategy

### Phase 1: Create New Tables (Week 1)
**Priority:** CRITICAL
**Risk:** Low (new tables, no data loss)

1. Run all 5 CREATE TABLE statements above
2. Verify table creation with: `SHOW TABLES LIKE 'projects_cabinet_%';`
3. Verify foreign key constraints work

### Phase 2: Migrate JSON to Normalized Data (Week 2)
**Priority:** CRITICAL
**Risk:** Medium (data transformation)

**Current JSON Fields to Migrate:**
- `projects_cabinet_specifications.door_sizes_json` → `projects_cabinet_doors`
- `projects_cabinet_specifications.drawer_sizes_json` → `projects_cabinet_drawers`
- `projects_cabinet_specifications.specialty_hardware_json` → `projects_cabinet_pullouts`

**Migration Script Needed:**
```php
// Pseudo-code for migration
foreach (CabinetSpecification::whereNotNull('door_sizes_json') as $cabinet) {
    $doors = json_decode($cabinet->door_sizes_json, true);

    foreach ($doors as $index => $doorData) {
        CabinetDoor::create([
            'cabinet_specification_id' => $cabinet->id,
            'door_number' => $index + 1,
            'width_inches' => $doorData['width'] ?? null,
            'height_inches' => $doorData['height'] ?? null,
            'hinge_side' => $doorData['hinge_side'] ?? null,
            // ... map other fields
        ]);
    }
}
```

**Recommendation:** Keep JSON fields for 1 migration cycle as backup, then remove.

### Phase 3: Add Missing Fields (Week 3)
**Priority:** HIGH
**Risk:** Low (simple ALTER TABLE)

1. Add window/wall fields to room_locations
2. Add access fields to projects/rooms
3. Add needs_countertop to cabinets
4. Add face frame enhancements
5. Update run_type enum for cabinet_runs

### Phase 4: Update Task Hierarchy (Week 3)
**Priority:** CRITICAL
**Risk:** Low (new columns)

1. Add section_id to tasks
2. Add component polymorphic fields
3. Update task assignment UI

### Phase 5: FilamentPHP Resources (Week 4)
**Priority:** HIGH
**Risk:** Low (UI layer only)

1. Create DoorResource
2. Create DrawerResource
3. Create ShelfResource
4. Create PulloutResource
5. Create SectionResource
6. Update CabinetSpecificationResource to manage components

---

## Part 6: Migration File Templates

### Migration 1: Create Component Tables

**File:** `2025_11_21_create_cabinet_component_tables.php`

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Create Doors Table
        Schema::create('projects_cabinet_doors', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cabinet_specification_id')
                ->constrained('projects_cabinet_specifications')
                ->onDelete('cascade');
            $table->foreignId('section_id')
                ->nullable()
                ->constrained('projects_cabinet_sections')
                ->onDelete('set null');

            // Identification
            $table->integer('door_number')->default(1);
            $table->string('door_name', 100)->nullable();

            // Dimensions
            $table->decimal('width_inches', 8, 3);
            $table->decimal('height_inches', 8, 3);
            $table->decimal('rail_width_inches', 5, 3)->nullable();
            $table->decimal('style_width_inches', 5, 3)->nullable();
            $table->boolean('has_check_rail')->default(false);
            $table->decimal('check_rail_width_inches', 5, 3)->nullable();

            // Construction
            $table->string('profile_type', 100)->nullable();
            $table->string('fabrication_method', 50)->nullable();
            $table->string('finish_type', 100)->nullable();
            $table->string('paint_color', 100)->nullable();
            $table->string('stain_color', 100)->nullable();

            // Hardware
            $table->string('hinge_type', 100)->nullable();
            $table->string('hinge_model', 100)->nullable();
            $table->integer('hinge_quantity')->default(2);
            $table->string('hinge_side', 20)->nullable();
            $table->boolean('has_decorative_hardware')->default(false);
            $table->string('decorative_hardware_model', 100)->nullable();

            // Glass
            $table->boolean('has_glass')->default(false);
            $table->string('glass_type', 100)->nullable();

            // Production
            $table->timestamp('cnc_cut_at')->nullable();
            $table->timestamp('edge_banded_at')->nullable();
            $table->timestamp('assembled_at')->nullable();
            $table->timestamp('sanded_at')->nullable();
            $table->timestamp('finished_at')->nullable();
            $table->boolean('qc_passed')->nullable();
            $table->text('qc_notes')->nullable();

            // Metadata
            $table->text('notes')->nullable();
            $table->integer('sort_order')->default(0);
            $table->timestamps();

            // Indexes
            $table->index('cabinet_specification_id', 'idx_doors_cabinet');
            $table->index('section_id', 'idx_doors_section');
        });

        // Create Drawers Table
        Schema::create('projects_cabinet_drawers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cabinet_specification_id')
                ->constrained('projects_cabinet_specifications')
                ->onDelete('cascade');
            $table->foreignId('section_id')
                ->nullable()
                ->constrained('projects_cabinet_sections')
                ->onDelete('set null');

            // Identification
            $table->integer('drawer_number')->default(1);
            $table->string('drawer_name', 100)->nullable();
            $table->string('drawer_position', 50)->nullable();

            // Dimensions
            $table->decimal('width_inches', 8, 3);
            $table->decimal('height_inches', 8, 3);
            $table->decimal('depth_inches', 8, 3);
            $table->decimal('top_rail_width_inches', 5, 3)->nullable();
            $table->decimal('bottom_rail_width_inches', 5, 3)->nullable();
            $table->decimal('style_width_inches', 5, 3)->nullable();

            // Box Construction
            $table->string('box_material', 100)->nullable();
            $table->decimal('box_thickness', 5, 3)->nullable();
            $table->string('joinery_method', 50)->nullable();

            // Front
            $table->string('front_profile', 100)->nullable();
            $table->string('front_finish', 100)->nullable();
            $table->string('fabrication_method', 50)->nullable();

            // Hardware
            $table->string('slide_type', 100)->nullable();
            $table->string('slide_model', 100)->nullable();
            $table->decimal('slide_length_inches', 5, 2)->nullable();
            $table->integer('slide_quantity')->default(1);
            $table->boolean('soft_close')->default(true);
            $table->string('decorative_hardware_model', 100)->nullable();

            // Production
            $table->timestamp('box_cut_at')->nullable();
            $table->timestamp('front_cut_at')->nullable();
            $table->timestamp('box_assembled_at')->nullable();
            $table->timestamp('front_attached_at')->nullable();
            $table->timestamp('sanded_at')->nullable();
            $table->timestamp('finished_at')->nullable();
            $table->timestamp('hardware_installed_at')->nullable();
            $table->boolean('qc_passed')->nullable();
            $table->text('qc_notes')->nullable();

            // Metadata
            $table->text('notes')->nullable();
            $table->integer('sort_order')->default(0);
            $table->timestamps();

            // Indexes
            $table->index('cabinet_specification_id', 'idx_drawers_cabinet');
            $table->index('section_id', 'idx_drawers_section');
        });

        // Create Shelves Table
        Schema::create('projects_cabinet_shelves', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cabinet_specification_id')
                ->constrained('projects_cabinet_specifications')
                ->onDelete('cascade');
            $table->foreignId('section_id')
                ->nullable()
                ->constrained('projects_cabinet_sections')
                ->onDelete('set null');

            // Identification
            $table->integer('shelf_number')->default(1);
            $table->string('shelf_name', 100)->nullable();
            $table->string('shelf_type', 50);

            // Dimensions
            $table->decimal('width_inches', 8, 3);
            $table->decimal('depth_inches', 8, 3);
            $table->decimal('thickness_inches', 5, 3)->nullable();

            // Material
            $table->string('material', 100)->nullable();
            $table->string('edge_treatment', 100)->nullable();

            // For Adjustable
            $table->decimal('pin_hole_spacing', 5, 3)->nullable();
            $table->integer('number_of_positions')->nullable();

            // For Pull-out
            $table->string('slide_model', 100)->nullable();
            $table->integer('weight_capacity_lbs')->nullable();

            // Production
            $table->timestamp('cut_at')->nullable();
            $table->timestamp('edge_banded_at')->nullable();
            $table->timestamp('finished_at')->nullable();
            $table->timestamp('installed_at')->nullable();
            $table->boolean('qc_passed')->nullable();

            // Metadata
            $table->text('notes')->nullable();
            $table->integer('sort_order')->default(0);
            $table->timestamps();

            // Indexes
            $table->index('cabinet_specification_id', 'idx_shelves_cabinet');
            $table->index('shelf_type', 'idx_shelves_type');
        });

        // Create Pullouts Table
        Schema::create('projects_cabinet_pullouts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cabinet_specification_id')
                ->constrained('projects_cabinet_specifications')
                ->onDelete('cascade');
            $table->foreignId('section_id')
                ->nullable()
                ->constrained('projects_cabinet_sections')
                ->onDelete('set null');

            // Identification
            $table->string('pullout_name', 100)->nullable();
            $table->string('pullout_type', 100);

            // Product
            $table->string('manufacturer', 100)->nullable();
            $table->string('model_number', 100)->nullable();
            $table->text('description')->nullable();

            // Dimensions
            $table->decimal('width_inches', 8, 3)->nullable();
            $table->decimal('depth_inches', 8, 3)->nullable();
            $table->decimal('height_inches', 8, 3)->nullable();

            // Hardware
            $table->string('slide_model', 100)->nullable();
            $table->string('mounting_type', 100)->nullable();

            // Cost
            $table->decimal('unit_cost', 10, 2)->nullable();
            $table->integer('quantity')->default(1);

            // Production
            $table->timestamp('ordered_at')->nullable();
            $table->timestamp('received_at')->nullable();
            $table->timestamp('installed_at')->nullable();
            $table->boolean('qc_passed')->nullable();

            // Metadata
            $table->text('notes')->nullable();
            $table->timestamps();

            // Indexes
            $table->index('cabinet_specification_id', 'idx_pullouts_cabinet');
            $table->index('pullout_type', 'idx_pullouts_type');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('projects_cabinet_pullouts');
        Schema::dropIfExists('projects_cabinet_shelves');
        Schema::dropIfExists('projects_cabinet_drawers');
        Schema::dropIfExists('projects_cabinet_doors');
    }
};
```

---

### Migration 2: Create Sections Table

**File:** `2025_11_21_create_cabinet_sections_table.php`

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('projects_cabinet_sections', function (Blueprint $table) {
            $table->id();

            // Relationships
            $table->foreignId('cabinet_specification_id')
                ->constrained('projects_cabinet_specifications')
                ->onDelete('cascade');

            // Identification
            $table->integer('section_number')->default(1);
            $table->string('name');
            $table->string('section_type', 50);

            // Dimensions
            $table->decimal('width_inches', 8, 3)->nullable();
            $table->decimal('height_inches', 8, 3)->nullable();
            $table->decimal('position_from_left_inches', 8, 3)->nullable();
            $table->decimal('position_from_bottom_inches', 8, 3)->nullable();

            // Configuration
            $table->integer('component_count')->default(0);
            $table->decimal('opening_width_inches', 8, 3)->nullable();
            $table->decimal('opening_height_inches', 8, 3)->nullable();

            // Metadata
            $table->text('notes')->nullable();
            $table->integer('sort_order')->default(0);
            $table->timestamps();

            // Indexes
            $table->index('cabinet_specification_id', 'idx_sections_cabinet');
            $table->index('section_type', 'idx_sections_type');
            $table->index(['cabinet_specification_id', 'sort_order'], 'idx_sections_sort');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('projects_cabinet_sections');
    }
};
```

---

### Migration 3: Add Missing Fields

**File:** `2025_11_21_add_missing_fields_to_existing_tables.php`

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Projects table additions
        Schema::table('projects_projects', function (Blueprint $table) {
            $table->json('access_permissions')->nullable()
                ->after('id')
                ->comment('User/role access control');
            $table->boolean('is_private')->default(false)
                ->after('access_permissions')
                ->comment('Private vs accessible to all');
        });

        // Rooms table additions
        Schema::table('projects_rooms', function (Blueprint $table) {
            $table->json('user_access')->nullable()
                ->comment('User access control for room');
            $table->decimal('estimated_value', 10, 2)->nullable()
                ->comment('Total estimated value for room');
        });

        // Room Locations table additions
        Schema::table('projects_room_locations', function (Blueprint $table) {
            $table->string('wall_type', 100)->nullable()
                ->comment('Wall construction type');
            $table->boolean('has_windows')->default(false);
            $table->text('window_fixtures_json')->nullable()
                ->comment('Window details JSON');
            $table->text('window_notes')->nullable();
        });

        // Cabinet Runs table additions
        Schema::table('projects_cabinet_runs', function (Blueprint $table) {
            $table->string('run_subtype', 100)->nullable()
                ->after('run_type')
                ->comment('Subtype: crown, base_trim for trim runs');
        });

        // Modify run_type to support new types
        DB::statement("ALTER TABLE projects_cabinet_runs
            MODIFY COLUMN run_type VARCHAR(50) NULL
            COMMENT 'Type: base, upper, full, trim, paneling, passage_doors'");

        // Cabinet Specifications table additions
        Schema::table('projects_cabinet_specifications', function (Blueprint $table) {
            $table->boolean('needs_countertop')->default(true)
                ->comment('Cabinet needs top');
            $table->decimal('face_frame_overall_width', 8, 3)->nullable();
            $table->decimal('face_frame_overall_height', 8, 3)->nullable();
            $table->boolean('has_scribe')->default(false);
            $table->decimal('scribe_width_inches', 5, 3)->nullable();
            $table->boolean('is_corner_cabinet')->default(false);
            $table->string('corner_type', 50)->nullable();
        });

        // Tasks table additions
        Schema::table('projects_tasks', function (Blueprint $table) {
            $table->foreignId('section_id')->nullable()
                ->after('cabinet_specification_id')
                ->constrained('projects_cabinet_sections')
                ->onDelete('set null');
            $table->string('component_type', 50)->nullable()
                ->after('section_id');
            $table->unsignedBigInteger('component_id')->nullable()
                ->after('component_type');

            $table->index(['component_type', 'component_id'], 'idx_tasks_component');
        });
    }

    public function down(): void
    {
        Schema::table('projects_tasks', function (Blueprint $table) {
            $table->dropForeign(['section_id']);
            $table->dropIndex('idx_tasks_component');
            $table->dropColumn(['section_id', 'component_type', 'component_id']);
        });

        Schema::table('projects_cabinet_specifications', function (Blueprint $table) {
            $table->dropColumn([
                'needs_countertop',
                'face_frame_overall_width',
                'face_frame_overall_height',
                'has_scribe',
                'scribe_width_inches',
                'is_corner_cabinet',
                'corner_type'
            ]);
        });

        Schema::table('projects_cabinet_runs', function (Blueprint $table) {
            $table->dropColumn('run_subtype');
        });

        Schema::table('projects_room_locations', function (Blueprint $table) {
            $table->dropColumn([
                'wall_type',
                'has_windows',
                'window_fixtures_json',
                'window_notes'
            ]);
        });

        Schema::table('projects_rooms', function (Blueprint $table) {
            $table->dropColumn(['user_access', 'estimated_value']);
        });

        Schema::table('projects_projects', function (Blueprint $table) {
            $table->dropColumn(['access_permissions', 'is_private']);
        });
    }
};
```

---

## Part 7: Verification Checklist

After running migrations, verify with these queries:

```sql
-- 1. Check all component tables exist
SHOW TABLES LIKE 'projects_cabinet_%';
-- Should show: doors, drawers, shelves, pullouts, sections

-- 2. Verify foreign keys are created
SELECT
    TABLE_NAME,
    CONSTRAINT_NAME,
    REFERENCED_TABLE_NAME
FROM information_schema.KEY_COLUMN_USAGE
WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME LIKE 'projects_cabinet_%'
    AND REFERENCED_TABLE_NAME IS NOT NULL;

-- 3. Check new fields exist
DESCRIBE projects_room_locations;
DESCRIBE projects_cabinet_specifications;
DESCRIBE projects_tasks;

-- 4. Verify indexes
SHOW INDEX FROM projects_cabinet_doors;
SHOW INDEX FROM projects_cabinet_drawers;
SHOW INDEX FROM projects_cabinet_shelves;
SHOW INDEX FROM projects_cabinet_sections;

-- 5. Test foreign key constraints
-- (Try inserting a door for non-existent cabinet - should fail)
```

---

## Part 8: FilamentPHP Resource Checklist

After database migrations, create/update these FilamentPHP resources:

### New Resources Needed:
- ☐ `CabinetDoorResource.php`
- ☐ `CabinetDrawerResource.php`
- ☐ `CabinetShelfResource.php`
- ☐ `CabinetPulloutResource.php`
- ☐ `CabinetSectionResource.php`

### Resources to Update:
- ☐ `CabinetSpecificationResource.php` - Add component management
- ☐ `TaskResource.php` - Add section/component assignment
- ☐ `RoomLocationResource.php` - Add window/wall fields
- ☐ `CabinetRunResource.php` - Add new run types

---

## Part 9: Next Steps

### Immediate Actions (This Week):
1. ✅ Review this migration report with team
2. ☐ Backup production database before any changes
3. ☐ Test migrations on development database first
4. ☐ Create migration files from templates above
5. ☐ Run Phase 1 migrations (create tables)

### Week 1-2 Actions:
1. ☐ Create data migration script for JSON → normalized tables
2. ☐ Test data migration on copy of production data
3. ☐ Run Phase 2 migrations (data transformation)
4. ☐ Verify data integrity after migration

### Week 3-4 Actions:
1. ☐ Run Phase 3 migrations (add fields)
2. ☐ Create FilamentPHP resources
3. ☐ Update existing resources
4. ☐ Test UI for component management
5. ☐ Train team on new structure

---

## Conclusion

Your database is **70% complete** for the meeting requirements. The foundation is solid with the hierarchy in place. The critical gap is **component-level normalization** - moving from JSON storage to proper relational tables.

**Estimated Implementation Time:** 3-4 weeks for complete migration

**Critical Path:**
1. Create component tables (1 week)
2. Migrate data from JSON (1 week)
3. Add missing fields (3 days)
4. Create FilamentPHP UI (1 week)

**Risk Assessment:** Medium - Requires careful data migration but structure is sound

---

**Report Generated:** 2025-11-21
**Next Review:** After Phase 1 completion
