# Database Gap Analysis - Cabinet Hierarchy System
**Comparison:** Current Structure vs. Meeting Requirements
**Date:** 2025-11-21

---

## Summary

Your current database structure has **most of the hierarchy in place** but is **missing key component-level tables** and some fields mentioned in the meeting.

### What Exists ✓
- ✓ Project table (`projects_projects`)
- ✓ Room table (`projects_rooms`)
- ✓ Room Location table (`projects_room_locations`)
- ✓ Cabinet Run table (`projects_cabinet_runs`)
- ✓ Cabinet table (`projects_cabinet_specifications`)
- ✓ Task hierarchy support (`projects_tasks` with foreign keys)
- ✓ BOM table (`projects_bom`)
- ✓ Hardware requirements table (`hardware_requirements`)

### What's Missing ✗
- ✗ Component tables (doors, drawers, shelves, pull-outs as separate entities)
- ✗ Section table (subdivisions within cabinets)
- ✗ Some specific fields at Room and Room Location levels
- ✗ Face frame as separate entity (currently embedded in cabinet specs)

---

## Detailed Gap Analysis by Level

### 1. PROJECT LEVEL
**Status:** ✓ Mostly Complete

**Existing Fields:**
- Customer details via partner relationship ✓
- Project identification ✓
- Stages and milestones ✓
- Linear feet tracking ✓
- Production stages ✓

**Missing Fields:**
- ✗ Access permissions (user/role-based access control per project)
- ✗ Explicit "access" field as mentioned in meeting (00:26-00:42)

**Recommendation:**
```php
// Add to projects_projects table:
$table->json('access_permissions')->nullable()
    ->comment('User/role access control: {user_ids: [], role_ids: []}');
$table->boolean('is_private')->default(false)
    ->comment('Private project vs accessible to all');
```

---

### 2. ROOM LEVEL
**Status:** ✓ Mostly Complete

**Existing Fields:**
- Room name ✓
- Room type ✓
- Floor number ✓
- PDF references ✓
- Notes ✓

**Missing Fields:**
- ✗ Access permissions (mentioned in meeting: "At the room level, we have access")
- ✗ Pricing columns (added to room_locations but not rooms)

**Meeting Quote (00:34-00:42):**
> "At the room level, we have access. We have. What do we agree to access? That's really all we need for a room"

**Recommendation:**
```php
// Add to projects_rooms table:
$table->json('user_access')->nullable()
    ->comment('User access control for this room');
$table->decimal('estimated_value', 10, 2)->nullable()
    ->comment('Total estimated value for room');
```

---

### 3. ROOM LOCATION LEVEL
**Status:** ✓ Good, Minor Additions Needed

**Existing Fields:**
- Electrical requirements ✓ (`requires_electrical`, `electrical_notes`)
- Plumbing requirements ✓ (`requires_plumbing`, `plumbing_notes`)
- Material specifications ✓
- Dimensional constraints ✓
- Hardware standards ✓
- Approval workflow ✓

**Missing Fields:**
- ✗ Window fixtures (mentioned in meeting 00:47-00:49)
- ✗ Wall type (drywall, plaster, tile, etc.) - has `location_type` but that's about position not wall material

**Meeting Quote (00:47-00:49):**
> "Location, we have electrical and plumbing and wind fixtures"

**Meeting Quote (53:02-53:07):**
> "Wall type would be room location. Yeah, that'll be at room location"

**Recommendation:**
```php
// Add to projects_room_locations table:
$table->string('wall_type', 100)->nullable()
    ->comment('Wall construction: drywall, plaster, tile, stone, brick');
$table->text('window_fixtures_json')->nullable()
    ->comment('JSON: window details, sizes, trim requirements');
$table->boolean('has_windows')->default(false)
    ->comment('Location has windows');
$table->text('window_notes')->nullable()
    ->comment('Window considerations for cabinet installation');
```

---

### 4. CABINET RUN LEVEL
**Status:** ✓ Excellent, Very Comprehensive

**Existing Fields:**
- Run type (base, upper, tall) ✓
- Linear feet calculations ✓
- Measurements (start/end wall) ✓
- Material specifications ✓
- CNC programming details ✓
- Production tracking ✓
- Hardware kitting ✓
- Labor tracking ✓
- QC workflow ✓
- Finishing details ✓
- Cost tracking ✓

**Missing Fields:**
- ✗ Run types mentioned in meeting not in enum:
  - "trim" run type
  - "paneling" run type
  - "doors" (passage doors) run type

**Meeting Quote (01:01:21-01:02:17):**
> Bryan: "Base cabinets, upper cabinets, pantry, cabinets"
> Andrew: "So we just call it full because that could be a closet"
> Bryan: "You could probably put trim in there or wall panel. Because you could have a wall with no cabinets and trim and no cabinets and wallpaper."
> Bryan: "And we do make doors. We could probably doors as a separate. Like passage doors."

**Recommendation:**
```php
// Update run_type field validation/enum to include:
// 'base', 'upper', 'full' (pantry/floor-to-ceiling), 'trim', 'paneling', 'passage_doors'

// Add to projects_cabinet_runs table:
$table->string('run_subtype', 100)->nullable()
    ->comment('For trim: crown, base, chair_rail. For paneling: wainscot, full_wall');
```

---

### 5. CABINET LEVEL
**Status:** ✓ Very Comprehensive, But Components Should Be Separate Tables

**Existing Fields:**
- Cabinet identification ✓
- Dimensions (width, height, depth) ✓
- Toe kick specifications ✓
- Box construction details ✓
- Face frame details ✓
- Door configuration (as JSON) ⚠️ Should be separate table
- Drawer configuration (as JSON) ⚠️ Should be separate table
- Shelving counts ✓
- Hardware specifications ✓
- Special features ✓
- Cutouts ✓
- Pricing/complexity ✓
- Production tracking ✓

**Critical Issue:**
**Components are stored as JSON but meeting requirements specify they should be separate entities**

**Meeting Quote (01:14:06-01:14:32):**
> Andrew: "So then the next part is what sub components exist?"
> Bryan: "Doors, drawers, shelving, pull outs. End panels."

**Meeting Quote (01:20:30-01:20:46):**
> Andrew: "Do you want to say it at the cabinet level?"
> Bryan: "It has to be at the component level. Because in one cabinet you could have a blind door and a regular door. So it has to be at the component level."

**Current Problem:**
- `door_sizes_json` stores all door info as JSON
- `drawer_sizes_json` stores all drawer info as JSON
- Makes it hard to:
  - Query individual components
  - Track production status per component
  - Assign tasks at component level
  - Calculate hardware per component accurately

**Missing Fields:**
- ✗ Does it need a top? (mentioned 01:13:33-01:13:56)

**Recommendation:**
```php
// Add to projects_cabinet_specifications table:
$table->boolean('needs_countertop')->default(true)
    ->comment('Cabinet needs top (false for stone countertops)');

// CREATE NEW TABLES (see Section 6 below)
```

---

### 6. COMPONENT LEVEL (**CRITICAL - MISSING**)
**Status:** ✗ Does Not Exist - Needs to be Created

**What's Missing:**
The meeting specified that doors, drawers, shelves, and pull-outs should be **individual database records**, not JSON fields.

#### 6A. Door Components Table (NEEDED)

**Meeting Requirements (01:15:48-01:17:28):**

**Fields per Door:**
- Profile type ✓
- Finish (inherits from cabinet if not specified) ✓
- Fabrication method (CNC vs. manual/five-piece) ✓
- Hinge type (blind inset, half overlay, full overlay) ✓
- Dimensions (width, height) ✓
- Rail width ✓
- Style width ✓
- Check rail (yes/no) ✓
- Check rail width (if applicable) ✓
- Hardware selection (per component) ✓
- Decorative hardware ✓

**Meeting Quote (01:16:27-01:17:15):**
> Bryan: "I would do fabrication because we don't cut all doors in the cnc. So they need to know, am I making this five piece or am I making this? Or is this getting CNC'd?"
> Andrew: "hinge type will define if it's inset or overlay"
> Bryan: "blind inset, half overlay, full overlay"

**Meeting Quote (01:26:43-01:27:41):**
> Andrew: "What are we measuring? With those?"
> Bryan: "Width, width, height, rail and style. Size."
> Bryan: "You need the width of rail and styles. You have a 14 inch door with a two and a quarter rail and stain overlay."
> Andrew: "Is the rail ever different from the top and the bottom?"
> Bryan: "Yes. What you need on doors. You don't need on doors. What you need on doors is check rail."

**Recommended Table:**
```sql
CREATE TABLE projects_cabinet_doors (
    id BIGINT PRIMARY KEY,
    cabinet_specification_id BIGINT NOT NULL,
    door_number INT DEFAULT 1,

    -- Dimensions
    width_inches DECIMAL(8, 3),
    height_inches DECIMAL(8, 3),
    rail_width_inches DECIMAL(5, 3),
    style_width_inches DECIMAL(5, 3),
    has_check_rail BOOLEAN DEFAULT false,
    check_rail_width_inches DECIMAL(5, 3) NULL,

    -- Construction
    profile_type VARCHAR(100),
    fabrication_method VARCHAR(50), -- 'cnc', 'five_piece_manual', 'slab'
    finish_type VARCHAR(100) NULL, -- Inherits from cabinet if NULL
    paint_color VARCHAR(100) NULL,
    stain_color VARCHAR(100) NULL,

    -- Hardware
    hinge_type VARCHAR(100), -- 'blind_inset', 'half_overlay', 'full_overlay'
    hinge_model VARCHAR(100),
    hinge_quantity INT DEFAULT 2,
    has_decorative_hardware BOOLEAN DEFAULT false,
    decorative_hardware_model VARCHAR(100) NULL,

    -- Glass
    has_glass BOOLEAN DEFAULT false,
    glass_type VARCHAR(100) NULL,

    -- Production
    cnc_cut_at TIMESTAMP NULL,
    assembled_at TIMESTAMP NULL,
    finished_at TIMESTAMP NULL,
    qc_passed BOOLEAN NULL,

    -- Metadata
    notes TEXT NULL,
    created_at TIMESTAMP,
    updated_at TIMESTAMP,

    FOREIGN KEY (cabinet_specification_id) REFERENCES projects_cabinet_specifications(id) ON DELETE CASCADE
);
```

#### 6B. Drawer Components Table (NEEDED)

**Meeting Requirements (01:27:30-01:27:41, 01:21:41-01:21:46):**

**Fields per Drawer:**
- Overall width ✓
- Overall height (front height) ✓
- Depth ✓
- Top rail width ✓
- Bottom rail width ✓
- Style width (matches drawer styles) ✓
- Hardware (slide type, slide length) ✓
- Profile type ✓
- Finish ✓
- Fabrication method ✓

**Meeting Quote (01:27:30-01:27:41):**
> Bryan: "What you need on drawers is top rail, bottom rail. And then your styles are probably gonna. Your styles will match your drawers"

**Meeting Quote (01:21:41-01:21:46):**
> Bryan: "Let's say we have a vanity where the top drawer has to be shallow. So each of those as a component. So they would each be defined. So it has to be at the component level"

**Recommended Table:**
```sql
CREATE TABLE projects_cabinet_drawers (
    id BIGINT PRIMARY KEY,
    cabinet_specification_id BIGINT NOT NULL,
    drawer_number INT DEFAULT 1,
    drawer_position VARCHAR(50), -- 'top', 'middle', 'bottom'

    -- Dimensions
    width_inches DECIMAL(8, 3),
    height_inches DECIMAL(8, 3), -- Front face height
    depth_inches DECIMAL(8, 3),
    top_rail_width_inches DECIMAL(5, 3),
    bottom_rail_width_inches DECIMAL(5, 3),
    style_width_inches DECIMAL(5, 3),

    -- Box Construction
    box_material VARCHAR(100),
    box_thickness DECIMAL(5, 3),
    joinery_method VARCHAR(50), -- 'dovetail', 'pocket_screw'

    -- Front
    front_profile VARCHAR(100),
    front_finish VARCHAR(100) NULL,
    fabrication_method VARCHAR(50),

    -- Hardware
    slide_type VARCHAR(100), -- 'blum_tandem', 'blum_undermount', 'full_extension'
    slide_length_inches DECIMAL(5, 2),
    slide_quantity INT DEFAULT 1,
    soft_close BOOLEAN DEFAULT true,
    decorative_hardware_model VARCHAR(100) NULL,

    -- Production
    box_cut_at TIMESTAMP NULL,
    front_cut_at TIMESTAMP NULL,
    assembled_at TIMESTAMP NULL,
    finished_at TIMESTAMP NULL,
    qc_passed BOOLEAN NULL,

    -- Metadata
    notes TEXT NULL,
    created_at TIMESTAMP,
    updated_at TIMESTAMP,

    FOREIGN KEY (cabinet_specification_id) REFERENCES projects_cabinet_specifications(id) ON DELETE CASCADE
);
```

#### 6C. Shelf Components Table (NEEDED)

**Meeting Requirements (01:57:01-01:58:29):**

**Fields per Shelf:**
- Type: adjustable, fixed, pull-out ✓
- Width ✓
- Depth ✓
- Material ✓
- Thickness ✓

**Meeting Quote (01:57:01-01:58:29):**
> Andrew: "You're going to have a parallel screen that when you add a cabinet to a cabinet run, it's going to ask you how many sections, how many openings are in it. Whereas is it adjustable? Adjustable or fixed? I would just go adjustable, fixed or pull out"

**Recommended Table:**
```sql
CREATE TABLE projects_cabinet_shelves (
    id BIGINT PRIMARY KEY,
    cabinet_specification_id BIGINT NOT NULL,
    shelf_number INT DEFAULT 1,

    -- Type
    shelf_type VARCHAR(50), -- 'adjustable', 'fixed', 'pullout'

    -- Dimensions
    width_inches DECIMAL(8, 3),
    depth_inches DECIMAL(8, 3),
    thickness_inches DECIMAL(5, 3),

    -- Material
    material VARCHAR(100),
    edge_treatment VARCHAR(100), -- 'edge_banded', 'solid_edge', 'exposed'

    -- For Adjustable
    pin_hole_spacing DECIMAL(5, 3) NULL,
    number_of_positions INT NULL,

    -- For Pull-out
    slide_model VARCHAR(100) NULL,
    weight_capacity_lbs INT NULL,

    -- Production
    cut_at TIMESTAMP NULL,
    finished_at TIMESTAMP NULL,

    -- Metadata
    notes TEXT NULL,
    created_at TIMESTAMP,
    updated_at TIMESTAMP,

    FOREIGN KEY (cabinet_specification_id) REFERENCES projects_cabinet_specifications(id) ON DELETE CASCADE
);
```

#### 6D. Pull-out Components Table (NEEDED)

**For specialized pull-outs like Rev-a-Shelf, trash pullouts, spice racks, etc.**

**Recommended Table:**
```sql
CREATE TABLE projects_cabinet_pullouts (
    id BIGINT PRIMARY KEY,
    cabinet_specification_id BIGINT NOT NULL,

    -- Type
    pullout_type VARCHAR(100), -- 'trash', 'spice_rack', 'tray_divider', 'lazy_susan', 'custom'

    -- Product
    manufacturer VARCHAR(100), -- 'Rev-a-Shelf', 'Lemans', etc.
    model_number VARCHAR(100),
    description TEXT,

    -- Dimensions
    width_inches DECIMAL(8, 3),
    depth_inches DECIMAL(8, 3),
    height_inches DECIMAL(8, 3),

    -- Hardware
    slide_model VARCHAR(100),
    mounting_type VARCHAR(100),

    -- Production
    installed_at TIMESTAMP NULL,
    qc_passed BOOLEAN NULL,

    -- Metadata
    notes TEXT NULL,
    created_at TIMESTAMP,
    updated_at TIMESTAMP,

    FOREIGN KEY (cabinet_specification_id) REFERENCES projects_cabinet_specifications(id) ON DELETE CASCADE
);
```

---

### 7. SECTION LEVEL (**CRITICAL - MISSING**)
**Status:** ✗ Does Not Exist - Needs to be Created

**What's Missing:**
The meeting specified that cabinets can have **sections** (subdivisions) like a drawer section, door section, open shelving section.

**Meeting Quote (01:30:40-01:30:45):**
> Andrew: "Let's say there's a door and a drawer. And then this. There's the another one next to it. That is open shelves. Are you considering that's one cabinet? So then how do I call those sections? Oh, they call sections"

**Meeting Quote (01:57:01):**
> Andrew: "You're going to have a parallel screen that when you add a cabinet to a cabinet run, it's going to ask you how many sections, how many openings are in it"

**Recommended Table:**
```sql
CREATE TABLE projects_cabinet_sections (
    id BIGINT PRIMARY KEY,
    cabinet_specification_id BIGINT NOT NULL,
    section_number INT DEFAULT 1,

    -- Identification
    name VARCHAR(255), -- 'Top Drawer Section', 'Door Opening', 'Open Shelving'
    section_type VARCHAR(50), -- 'drawer_stack', 'door_opening', 'open_shelving', 'pullout_area'

    -- Dimensions (within cabinet)
    width_inches DECIMAL(8, 3),
    height_inches DECIMAL(8, 3),
    position_from_left_inches DECIMAL(8, 3),
    position_from_bottom_inches DECIMAL(8, 3),

    -- Configuration
    component_count INT DEFAULT 0, -- How many doors/drawers/shelves in this section

    -- Face Frame Opening (for this section)
    opening_width_inches DECIMAL(8, 3),
    opening_height_inches DECIMAL(8, 3),

    -- Metadata
    notes TEXT NULL,
    sort_order INT DEFAULT 0,
    created_at TIMESTAMP,
    updated_at TIMESTAMP,

    FOREIGN KEY (cabinet_specification_id) REFERENCES projects_cabinet_specifications(id) ON DELETE CASCADE
);
```

**Then update component tables to reference sections:**
```sql
-- Add to projects_cabinet_doors:
ALTER TABLE projects_cabinet_doors
ADD COLUMN section_id BIGINT NULL,
ADD FOREIGN KEY (section_id) REFERENCES projects_cabinet_sections(id) ON DELETE SET NULL;

-- Add to projects_cabinet_drawers:
ALTER TABLE projects_cabinet_drawers
ADD COLUMN section_id BIGINT NULL,
ADD FOREIGN KEY (section_id) REFERENCES projects_cabinet_sections(id) ON DELETE SET NULL;

-- Add to projects_cabinet_shelves:
ALTER TABLE projects_cabinet_shelves
ADD COLUMN section_id BIGINT NULL,
ADD FOREIGN KEY (section_id) REFERENCES projects_cabinet_sections(id) ON DELETE SET NULL;
```

---

### 8. TASK HIERARCHY SUPPORT
**Status:** ✓ Excellent

**Existing Support:**
- Tasks can be assigned to project ✓
- Tasks can be assigned to room ✓
- Tasks can be assigned to room_location ✓
- Tasks can be assigned to cabinet_run ✓
- Tasks can be assigned to cabinet_specification ✓

**Missing Support:**
- ✗ Tasks cannot be assigned to component level (door, drawer)
- ✗ Tasks cannot be assigned to section level

**Meeting Quote (02:18:56):**
> Andrew: "I can assign room level, cabinet level, cabinet run level, section level, tasks which you can then go, I'm making a task for so and so, and I'm assigning it to this section"

**Recommendation:**
```php
// Add to projects_tasks table:
$table->foreignId('section_id')->nullable()->after('cabinet_specification_id')
    ->constrained('projects_cabinet_sections')->nullOnDelete();
$table->morphs('component'); // For polymorphic relation to doors/drawers/shelves/pullouts
```

---

### 9. FACE FRAME AS SEPARATE ENTITY
**Status:** ⚠️ Partial - Currently embedded in cabinet specs

**Current Implementation:**
Face frame details are fields in `projects_cabinet_specifications` table

**Meeting Discussion:**
Face frames are at the **cabinet run level** according to the meeting, but later corrected to **cabinet level**.

**Meeting Quote (01:30:10-01:30:27):**
> Andrew: "So is a face frame connected to a cabinet run or a cabinet run?"
> Andrew: "Okay, so face frames are on the cabinet run level."
> Bryan: "No, I'm sorry. It's on the cabinet level."

**Current Issue:**
- Face frame fields exist but don't capture all dimensional requirements mentioned in meeting
- Meeting specified: "You need to know Your face frame doesn't match your cabinet width" (01:29:10-01:29:34)
- Need face frame opening dimensions, scribe considerations, corner considerations

**Recommendation:**
Current implementation is **acceptable** but could be enhanced:

```php
// Add to projects_cabinet_specifications table:
$table->decimal('face_frame_overall_width', 8, 3)->nullable()
    ->comment('Face frame width (different from cabinet box width)');
$table->decimal('face_frame_overall_height', 8, 3)->nullable()
    ->comment('Face frame height');
$table->boolean('has_scribe')->default(false)
    ->comment('Face frame has scribe strip');
$table->decimal('scribe_width_inches', 5, 3)->nullable()
    ->comment('Scribe strip width if applicable');
$table->boolean('is_corner_cabinet')->default(false)
    ->comment('Cabinet is at inside/blind corner');
$table->string('corner_type', 50)->nullable()
    ->comment('inside_corner, blind_corner');
```

---

## Migration Priority

### CRITICAL (Must Have for Meeting Requirements):
1. **Create Component Tables** (doors, drawers, shelves, pullouts)
   - Migrate existing JSON data to normalized tables
   - Update FilamentPHP resources to manage components
   - Impact: Enables component-level task assignment, hardware tracking, production status

2. **Create Section Table**
   - Support subdivisions within cabinets
   - Link components to sections
   - Impact: Enables section-level task assignment

3. **Update Task Hierarchy**
   - Add section_id and component polymorphic support
   - Impact: Full hierarchy task assignment as specified in meeting

### HIGH (Important for Full Functionality):
4. **Add Missing Room Location Fields**
   - Window fixtures
   - Wall type
   - Impact: Complete location specification

5. **Expand Cabinet Run Types**
   - Add trim, paneling, passage_doors types
   - Impact: Support all run types mentioned in meeting

6. **Enhance Face Frame Specifications**
   - Add overall dimensions, scribe, corner details
   - Impact: Accurate face frame production specs

### MEDIUM (Nice to Have):
7. **Add Project/Room Access Permissions**
   - User/role-based access control
   - Impact: Security and team collaboration

8. **Add Cabinet "Needs Top" Flag**
   - Simple boolean flag
   - Impact: Production clarity for stone countertops

---

## Data Migration Considerations

### Migrating JSON to Component Tables:

**Current JSON structure in `door_sizes_json`:**
```json
[{
  "width": 17.75,
  "height": 30,
  "qty": 2,
  "hinge_side": "left"
}]
```

**Migration Strategy:**
```php
// Migration script needed to:
// 1. Read all cabinet_specifications with door_sizes_json
// 2. Parse JSON
// 3. Create individual door records in projects_cabinet_doors
// 4. Preserve production tracking data where possible
```

**Same for drawers, shelves, etc.**

---

## Summary Table

| Level | Table | Status | Missing Items | Priority |
|-------|-------|--------|--------------|----------|
| 1. Project | `projects_projects` | ✓ Good | Access permissions | Medium |
| 2. Room | `projects_rooms` | ✓ Good | Access field | Medium |
| 3. Room Location | `projects_room_locations` | ✓ Good | Window fixtures, Wall type | High |
| 4. Cabinet Run | `projects_cabinet_runs` | ✓ Excellent | Trim/Paneling/Door types | High |
| 5. Cabinet | `projects_cabinet_specifications` | ✓ Very Good | "Needs top" field, Face frame enhancements | Medium/High |
| 6. Components | **MISSING** | ✗ Critical | Entire tables needed | **CRITICAL** |
| 7. Sections | **MISSING** | ✗ Critical | Entire table needed | **CRITICAL** |
| 8. Task Hierarchy | `projects_tasks` | ✓ Good | Section & Component support | Critical |

---

## Recommended Migration Order

### Phase 1: Component Tables (Week 1)
1. Create `projects_cabinet_doors` table
2. Create `projects_cabinet_drawers` table
3. Create `projects_cabinet_shelves` table
4. Create `projects_cabinet_pullouts` table
5. Migrate existing JSON data to normalized tables
6. Create FilamentPHP resources for component management

### Phase 2: Section Table (Week 2)
1. Create `projects_cabinet_sections` table
2. Add section_id foreign keys to component tables
3. Create FilamentPHP resource for section management
4. Update task table with section support

### Phase 3: Field Additions (Week 3)
1. Add missing room_location fields (windows, wall type)
2. Add cabinet run types (trim, paneling, doors)
3. Enhance face frame specifications
4. Add "needs_top" to cabinets
5. Add access permissions to projects/rooms

### Phase 4: Task Hierarchy Enhancement (Week 4)
1. Add component polymorphic support to tasks
2. Update task assignment UI
3. Test full hierarchy task assignment

---

## Next Steps

**Immediate Action Required:**
1. Approve component table structure
2. Decide on migration strategy for existing JSON data
3. Create database migrations for new tables
4. Update FilamentPHP resources
5. Test data integrity after migration

**Questions to Answer:**
1. Do you want to migrate existing JSON data or start fresh for components?
2. Should we keep JSON fields as backup during transition?
3. What's the timeline for implementing these changes?
4. Are there existing projects with data that need careful migration?

