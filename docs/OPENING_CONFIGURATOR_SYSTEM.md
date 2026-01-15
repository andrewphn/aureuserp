# Opening Configurator System

This document describes the Opening Configurator system for managing component space allocation within cabinet section openings.

---

## Overview

The Opening Configurator enables precise positioning and space management for components (drawers, shelves, doors, pullouts) within cabinet section openings. It tracks consumed vs. remaining space, provides auto-arrangement strategies, validates configurations, and offers a visual builder interface.

---

## Database Hierarchy Integration

```
projects_projects
    └── projects_rooms
        └── projects_room_locations
            └── projects_cabinet_runs
                └── projects_cabinets
                    └── projects_cabinet_sections  ← THE OPENING (this is what we configure)
                        ├── projects_doors
                        ├── projects_drawers
                        ├── projects_shelves
                        └── projects_pullouts
```

**Reference:** [DATABASE_HIERARCHY.md](./DATABASE_HIERARCHY.md)

---

## Shop Standard Gap Constants

| Constant | Value | Description |
|----------|-------|-------------|
| `GAP_TOP_REVEAL_INCHES` | 0.125" (1/8") | Default gap at top of opening |
| `GAP_BOTTOM_REVEAL_INCHES` | 0.125" (1/8") | Default gap at bottom of opening |
| `GAP_BETWEEN_COMPONENTS_INCHES` | 0.125" (1/8") | Default gap between components |
| `GAP_DOOR_SIDE_REVEAL_INCHES` | 0.0625" (1/16") | Side reveal for doors per side |
| `MIN_SHELF_OPENING_HEIGHT_INCHES` | 5.5" | Minimum vertical space for shelf opening |
| `MIN_DRAWER_FRONT_HEIGHT_INCHES` | 4.0" | Minimum drawer front height |

---

## Database Schema

### Table: `projects_cabinet_sections`

**New Layout Fields:**

| Column | Type | Default | Description |
|--------|------|---------|-------------|
| `total_consumed_height_inches` | decimal(8,4) | NULL | Sum of all component heights + gaps |
| `total_consumed_width_inches` | decimal(8,4) | NULL | Sum of widths (horizontal layout) |
| `remaining_height_inches` | decimal(8,4) | NULL | opening_height - consumed_height |
| `remaining_width_inches` | decimal(8,4) | NULL | opening_width - consumed_width |
| `layout_direction` | enum | 'vertical' | 'vertical', 'horizontal', 'grid' |
| `top_reveal_inches` | decimal(8,4) | 0.125 | Gap at top of opening |
| `bottom_reveal_inches` | decimal(8,4) | 0.125 | Gap at bottom of opening |
| `component_gap_inches` | decimal(8,4) | 0.125 | Gap between components |

### Tables: `projects_drawers`, `projects_shelves`, `projects_doors`, `projects_pullouts`

**New Position Fields (all tables):**

| Column | Type | Description |
|--------|------|-------------|
| `position_in_opening_inches` | decimal(8,4) | Distance from bottom of opening |
| `consumed_height_inches` | decimal(8,4) | Total vertical space consumed (height + gap) |
| `position_from_left_inches` | decimal(8,4) | Distance from left edge (horizontal layout) |
| `consumed_width_inches` | decimal(8,4) | Total horizontal space consumed |

---

## Consumed Height Calculation

Each component type has a different source for its consumed height:

| Component | Height Source | Formula |
|-----------|---------------|---------|
| Drawer | `front_height_inches` | front_height + gap |
| Shelf | Opening clearance | MIN_SHELF_OPENING_HEIGHT (5.5") + gap |
| Door | `height_inches` | height + reveal |
| Pullout | `height_inches` | height + gap |

### Example: 4-Drawer Bank in 28" Opening

```
Opening Height: 28"

├── Top Reveal:        0.125"  (1/8")
├── Drawer 4 (top):    6.000"  → consumed: 6.125" (height + gap)
├── Drawer 3:          6.000"  → consumed: 6.125"
├── Drawer 2:          6.000"  → consumed: 6.125"
├── Drawer 1 (bottom): 6.000"  → consumed: 6.000" (no gap after)
└── Bottom Reveal:     0.125"  (1/8")
─────────────────────────────
Total Consumed:        24.625"
Remaining Space:       3.375"  (28" - 24.625")
```

---

## Services

### OpeningConfiguratorService

**Location:** `app/Services/OpeningConfiguratorService.php`

**Responsibilities:**
- Calculate component positions within openings
- Track consumed/remaining space
- Manage gap settings
- Provide fraction formatting

**Key Methods:**

```php
// Calculate all positions and update section
calculateSectionLayout(CabinetSection $section): array

// Collect all components from a section
collectAllComponents(CabinetSection $section): Collection

// Calculate vertical stacked layout
calculateVerticalLayout(Collection $components, ...): array

// Calculate horizontal side-by-side layout
calculateHorizontalLayout(Collection $components, ...): array

// Get remaining space info
getRemainingSpace(CabinetSection $section): array

// Check if component fits
canFitComponent(CabinetSection $section, string $type, float $height): bool

// Convert decimal to fraction
toFraction(float $decimal, int $precision = 16): string
```

### OpeningLayoutEngine

**Location:** `app/Services/OpeningLayoutEngine.php`

**Responsibilities:**
- Auto-arrange components using different strategies
- Support multiple layout algorithms

**Layout Strategies:**

| Strategy | Description | Use Case |
|----------|-------------|----------|
| `stack_from_bottom` | Stack components bottom-up | Drawer banks |
| `stack_from_top` | Stack components top-down | Upper cabinet shelves |
| `equal_distribution` | Equal gaps between components | Decorative layouts |
| `weighted_distribution` | Proportional space by size | Mixed height drawers |

**Usage:**

```php
$engine = app(OpeningLayoutEngine::class);
$result = $engine->autoArrange($section, 'stack_from_bottom');

// Result:
[
    'success' => true,
    'strategy' => 'stack_from_bottom',
    'total_consumed' => 24.625,
    'remaining' => 3.375,
    'overflow' => 0,
    'positions' => [...],
]
```

### OpeningValidator

**Location:** `app/Services/OpeningValidator.php`

**Responsibilities:**
- Validate component configurations
- Check for overflow and overlaps
- Enforce minimum heights
- Provide actionable error/warning messages

**Validation Checks:**

1. **Height Overflow**: Total consumed > opening height
2. **Width Overflow**: Total consumed > opening width (horizontal)
3. **Overlapping Components**: Two components at same position
4. **Minimum Heights**: Drawer/shelf minimum heights met
5. **Component Requirements**: Type-specific validations

**Usage:**

```php
$validator = app(OpeningValidator::class);
$result = $validator->validateSection($section);

// Result is ValidationResult object:
$result->isValid();      // bool
$result->hasWarnings();  // bool
$result->errors;         // array of error messages
$result->warnings;       // array of warning messages
```

---

## HasOpeningPosition Trait

**Location:** `plugins/webkul/projects/src/Traits/HasOpeningPosition.php`

Provides shared position-related methods for all component models:

```php
// Check if positioned
$drawer->isPositioned(): bool

// Get position info
$drawer->getTopEdgePosition(): ?float
$drawer->getRightEdgePosition(): ?float

// Check overlaps
$drawer->overlapsVertically($other): bool
$drawer->overlapsHorizontally($other): bool

// Set positions
$drawer->setVerticalPosition(float $position, float $gapAfter): void
$drawer->setHorizontalPosition(float $position, float $gapAfter): void

// Reset positions
$drawer->resetPosition(): void

// Formatted accessors
$drawer->formatted_position;         // "2-1/8" from bottom"
$drawer->formatted_consumed_height;  // "6-1/8""
```

**Applied to Models:**
- `Webkul\Project\Models\Drawer`
- `Webkul\Project\Models\Shelf`
- `Webkul\Project\Models\Door`
- `Webkul\Project\Models\Pullout`

---

## Livewire Visual Builder

**Component:** `Webkul\Project\Livewire\OpeningConfigurator`
**View:** `resources/views/livewire/opening-configurator.blade.php`

### Features

1. **Visual Opening Representation**
   - Scaled visual of opening dimensions
   - Color-coded components by type
   - Position labels and dimensions

2. **Space Usage Indicator**
   - Progress bar showing usage percentage
   - Color changes: blue (<90%), yellow (90-100%), red (>100%)
   - Remaining space display

3. **Component Management**
   - Add drawer/shelf/door/pullout
   - Move up/down in stack
   - Remove components
   - Height input validation

4. **Auto-Arrange**
   - Strategy selection dropdown
   - One-click auto-arrangement
   - Real-time position updates

5. **Gap Settings**
   - Configurable top/bottom reveal
   - Component gap adjustment
   - Apply and recalculate

6. **Validation Display**
   - Error badges (red)
   - Warning badges (yellow)
   - Success indicator (green)

### Usage

```blade
{{-- In a Filament page or blade view --}}
<livewire:opening-configurator :section-id="$section->id" />

{{-- Or load dynamically --}}
<livewire:opening-configurator />
@push('scripts')
<script>
    Livewire.dispatch('load-section', { sectionId: {{ $sectionId }} });
</script>
@endpush
```

---

## Migrations

### Migration 1: Section Layout Fields

**File:** `database/migrations/2026_01_15_120001_add_opening_layout_fields_to_sections.php`

Adds to `projects_cabinet_sections`:
- Space tracking fields (consumed, remaining)
- Layout configuration (direction, gaps)

### Migration 2: Component Position Fields

**File:** `database/migrations/2026_01_15_120002_add_position_fields_to_components.php`

Adds to all 4 component tables:
- `position_in_opening_inches`
- `consumed_height_inches`
- `position_from_left_inches`
- `consumed_width_inches`

---

## Files Created/Modified

| File | Action |
|------|--------|
| `database/migrations/2026_01_15_120001_add_opening_layout_fields_to_sections.php` | Created |
| `database/migrations/2026_01_15_120002_add_position_fields_to_components.php` | Created |
| `app/Services/OpeningConfiguratorService.php` | Created |
| `app/Services/OpeningLayoutEngine.php` | Created |
| `app/Services/OpeningValidator.php` | Created |
| `plugins/webkul/projects/src/Traits/HasOpeningPosition.php` | Created |
| `plugins/webkul/projects/src/Models/Drawer.php` | Modified (trait + fields) |
| `plugins/webkul/projects/src/Models/Shelf.php` | Modified (trait + fields) |
| `plugins/webkul/projects/src/Models/Door.php` | Modified (trait + fields) |
| `plugins/webkul/projects/src/Models/Pullout.php` | Modified (trait + fields) |
| `plugins/webkul/projects/src/Models/CabinetSection.php` | Modified (fields + const) |
| `plugins/webkul/projects/src/Livewire/OpeningConfigurator.php` | Created |
| `plugins/webkul/projects/resources/views/livewire/opening-configurator.blade.php` | Created |
| `docs/OPENING_CONFIGURATOR_SYSTEM.md` | Created |

---

## Usage Examples

### Calculate Section Layout

```php
use App\Services\OpeningConfiguratorService;

$configurator = app(OpeningConfiguratorService::class);
$result = $configurator->calculateSectionLayout($section);

// Result:
[
    'layout' => 'vertical',
    'consumed_height' => 24.625,
    'consumed_width' => 0,
    'positions' => [
        ['id' => 1, 'type' => 'drawer', 'position' => 0.125, 'height' => 6.0],
        ['id' => 2, 'type' => 'drawer', 'position' => 6.25, 'height' => 6.0],
        // ...
    ],
    'valid' => true,
    'overflow' => 0,
]
```

### Auto-Arrange with Strategy

```php
use App\Services\OpeningLayoutEngine;

$engine = app(OpeningLayoutEngine::class);

// Equal distribution - spreads components evenly
$result = $engine->autoArrange($section, 'equal_distribution');

// Stack from top - for upper cabinet shelves
$result = $engine->autoArrange($section, 'stack_from_top');
```

### Validate Configuration

```php
use App\Services\OpeningValidator;

$validator = app(OpeningValidator::class);
$result = $validator->validateSection($section);

if (!$result->isValid()) {
    foreach ($result->errors as $error) {
        echo "ERROR: $error\n";
    }
}

if ($result->hasWarnings()) {
    foreach ($result->warnings as $warning) {
        echo "WARNING: $warning\n";
    }
}
```

### Check If Component Fits

```php
$configurator = app(OpeningConfiguratorService::class);

// Check before adding
if ($configurator->canFitComponent($section, 'drawer', 6.0)) {
    // Safe to add 6" drawer
    Drawer::create([...]);
} else {
    // Not enough space
    throw new Exception('Drawer does not fit');
}
```

---

## Future Enhancements

1. **Drag-and-Drop Positioning**: Interactive repositioning in visual builder
2. **Horizontal Division Support**: Split sections side-by-side
3. **Grid Layout**: Complex multi-row, multi-column arrangements
4. **Template Presets**: Save and apply common configurations
5. **Spec Generation**: Auto-generate cut lists from opening config

---

## Related Documentation

- [DATABASE_HIERARCHY.md](./DATABASE_HIERARCHY.md) - Full database schema
- [DRAWER_SPEC_SYSTEM.md](./DRAWER_SPEC_SYSTEM.md) - Drawer specifications
- [SHELF_SPEC_SYSTEM.md](./SHELF_SPEC_SYSTEM.md) - Shelf specifications
