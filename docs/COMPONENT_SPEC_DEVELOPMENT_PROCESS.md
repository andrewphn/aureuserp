# Component Specification Development Process

This document outlines the systematic process for building specification systems (like drawers, doors, shelves) that calculate dimensions, assign hardware, and generate manufacturing outputs.

---

## Process Overview

```
┌─────────────────────────────────────────────────────────────────────────────┐
│                        COMPONENT SPEC DEVELOPMENT                            │
├─────────────────────────────────────────────────────────────────────────────┤
│                                                                              │
│  PHASE 1: RESEARCH & DISCOVERY                                              │
│  ├── 1.1 Hardware Manufacturer Specifications                                │
│  ├── 1.2 Shop Practice Documentation                                         │
│  ├── 1.3 Database Gap Analysis                                               │
│  └── 1.4 EAV Attribute Inventory                                             │
│                                                                              │
│  PHASE 2: DATA MODEL DESIGN                                                  │
│  ├── 2.1 Define Database Schema                                              │
│  ├── 2.2 Identify Theoretical vs Shop Values                                 │
│  ├── 2.3 Plan Migrations                                                     │
│  └── 2.4 Update Eloquent Models                                              │
│                                                                              │
│  PHASE 3: SERVICE IMPLEMENTATION                                             │
│  ├── 3.1 Create Configurator Service (calculations)                          │
│  ├── 3.2 Create Hardware Service (product selection)                         │
│  ├── 3.3 Implement Shop Rules                                                │
│  └── 3.4 Add Utility Methods (fractions, rounding)                           │
│                                                                              │
│  PHASE 4: OUTPUT GENERATION                                                  │
│  ├── 4.1 Design Cut List Format                                              │
│  ├── 4.2 Create HTML Spec Templates                                          │
│  ├── 4.3 Generate SVG Diagrams                                               │
│  └── 4.4 Build Comparison Tables                                             │
│                                                                              │
│  PHASE 5: VALIDATION & DOCUMENTATION                                         │
│  ├── 5.1 Test with Real Examples                                             │
│  ├── 5.2 Carpenter Review                                                    │
│  ├── 5.3 Create System Documentation                                         │
│  └── 5.4 Update Related Docs                                                 │
│                                                                              │
└─────────────────────────────────────────────────────────────────────────────┘
```

---

## Phase 1: Research & Discovery

### 1.1 Hardware Manufacturer Specifications

**Goal:** Obtain official specifications from hardware manufacturers.

#### Research Checklist

| Item | Source | Example (Drawer) |
|------|--------|------------------|
| Product data sheets | Manufacturer website | Blum TANDEM 563H PDF |
| Installation guides | Manufacturer docs | Mounting clearances, tolerances |
| Technical dimensions | Spec sheets | Side clearance, top/bottom gaps |
| Minimum requirements | Installation manual | Min cabinet depth per slide length |
| Weight capacities | Product specs | 90 lbs for 563H |

#### Questions to Answer

- [ ] What hardware products are used for this component?
- [ ] What are the official clearance/deduction values?
- [ ] What are the minimum cabinet dimensions required?
- [ ] What installation constraints exist?
- [ ] Are there different variants (sizes, overlay types)?

#### Documentation Template

```markdown
## [Component] Hardware Specifications

### Manufacturer: [Name]
### Product Line: [Model/Series]
### Source Document: [URL or PDF reference]

| Specification | Value | Unit | Notes |
|---------------|-------|------|-------|
| [Name] | [Value] | [Unit] | [Notes] |
```

### 1.2 Shop Practice Documentation

**Goal:** Capture real-world practices that differ from manufacturer specs.

#### Interview Questions for Shop Staff

1. **Safety Margins**
   - "Do you round any dimensions? Up or down? To what increment?"
   - "Do you add any safety clearances beyond manufacturer specs?"

2. **Simplified Rules**
   - "Do you use simpler rules than the manufacturer specifies?"
   - "What's your rule of thumb for [specific calculation]?"

3. **Common Adjustments**
   - "What adjustments do you commonly make?"
   - "When do you deviate from the spec sheet?"

4. **Material Considerations**
   - "What materials do you typically use?"
   - "Do material choices affect the calculations?"

#### Shop Rules Documentation Template

```markdown
## Shop Practice: [Component Name]

### Rule: [Name]
- **Description:** [What the shop does differently]
- **Formula:** [Mathematical expression]
- **Reason:** [Why this practice exists]
- **Example:** [Concrete example with numbers]

### Comparison: Manufacturer vs Shop

| Dimension | Manufacturer | Shop | Difference |
|-----------|--------------|------|------------|
| [Name] | [Value] | [Value] | [Delta] |
```

### 1.3 Database Gap Analysis

**Goal:** Identify what database fields exist vs what's needed.

#### Analysis Steps

1. **Inventory Existing Tables**
   ```sql
   -- Check if component table exists
   SHOW TABLES LIKE 'projects_%';
   
   -- Get current schema
   DESCRIBE projects_[component];
   ```

2. **Map Required Fields**
   
   | Required Field | Exists? | Table | Column | Type |
   |----------------|---------|-------|--------|------|
   | Opening width | ? | ? | ? | ? |
   | Opening height | ? | ? | ? | ? |
   | Hardware product | ? | ? | ? | ? |
   | Calculated dimensions | ? | ? | ? | ? |

3. **Identify Gaps**
   - Missing columns in existing tables
   - Missing relationships (foreign keys)
   - Missing shop-specific columns
   - Missing calculation metadata

#### Gap Analysis Template

```markdown
## Database Gap Analysis: [Component]

### Existing Table: `projects_[component]`

| Current Column | Purpose | Adequate? |
|----------------|---------|-----------|
| [column] | [purpose] | Yes/No |

### Required Additions

| New Column | Type | Purpose | Migration Needed |
|------------|------|---------|------------------|
| [column] | [type] | [purpose] | Yes |

### Required Relationships

| Relationship | Type | Target Table | Exists? |
|--------------|------|--------------|---------|
| [name] | belongsTo | [table] | Yes/No |
```

### 1.4 EAV Attribute Inventory

**Goal:** Map hardware product attributes needed for calculations.

#### Query Existing Attributes

```php
// Find attributes related to component hardware
DB::table('products_attributes')
    ->where('name', 'LIKE', '%[hardware type]%')
    ->get();

// Check which products have these attributes
DB::table('products_product_attribute_values as pav')
    ->join('products_attributes as a', 'pav.attribute_id', '=', 'a.id')
    ->where('a.name', 'LIKE', '%[keyword]%')
    ->select('pav.product_id', 'a.name', 'pav.text_value', 'pav.numeric_value')
    ->get();
```

#### Attribute Requirements

| Attribute Name | Type | Required For | Exists? | Products With Value |
|----------------|------|--------------|---------|---------------------|
| [Name] | text/numeric | [Calculation] | Yes/No | [Count] |

---

## Phase 2: Data Model Design

### 2.1 Define Database Schema

**Goal:** Design complete schema for storing component data.

#### Schema Categories

```
┌─────────────────────────────────────────────────────────────────┐
│                    COMPONENT TABLE STRUCTURE                     │
├─────────────────────────────────────────────────────────────────┤
│                                                                  │
│  IDENTIFICATION                                                  │
│  ├── id, cabinet_id, section_id                                  │
│  ├── component_number, component_name                            │
│  └── sort_order, full_code                                       │
│                                                                  │
│  OPENING REFERENCE (Input Dimensions)                            │
│  ├── opening_width_inches                                        │
│  ├── opening_height_inches                                       │
│  └── opening_depth_inches                                        │
│                                                                  │
│  CALCULATED DIMENSIONS (Theoretical)                             │
│  ├── calculated_width_inches                                     │
│  ├── calculated_height_inches                                    │
│  └── calculated_depth_inches                                     │
│                                                                  │
│  SHOP DIMENSIONS (Adjusted for Practice)                         │
│  ├── width_shop_inches                                           │
│  ├── height_shop_inches                                          │
│  └── depth_shop_inches                                           │
│                                                                  │
│  HARDWARE REFERENCES                                             │
│  ├── hardware_product_id (FK)                                    │
│  ├── hardware_model                                              │
│  └── hardware_quantity                                           │
│                                                                  │
│  CONSTRUCTION DETAILS                                            │
│  ├── material, thickness                                         │
│  ├── profile_type, fabrication_method                            │
│  └── joinery_method                                              │
│                                                                  │
│  CLEARANCES APPLIED                                              │
│  ├── clearance_left/right                                        │
│  ├── clearance_top/bottom                                        │
│  └── clearance_notes                                             │
│                                                                  │
│  PRODUCTION TRACKING                                             │
│  ├── cnc_cut_at, assembled_at                                    │
│  ├── finished_at, installed_at                                   │
│  └── qc_passed, qc_notes                                         │
│                                                                  │
└─────────────────────────────────────────────────────────────────┘
```

### 2.2 Identify Theoretical vs Shop Values

**Critical Step:** Determine which dimensions need dual values.

#### Decision Framework

| Dimension | Needs Shop Value? | Shop Rule | Reason |
|-----------|-------------------|-----------|--------|
| Height | ? | Round down to 1/2" | Ensures fit |
| Width | ? | [rule] | [reason] |
| Depth | ? | Add 1/4" | Safety clearance |
| Min depth | ? | Slide + 3/4" | Simpler rule |

#### Naming Convention

```
[measurement]_inches           → Theoretical (calculated)
[measurement]_shop_inches      → Shop practice (adjusted)
```

### 2.3 Plan Migrations

**Goal:** Organize migrations logically.

#### Migration Sequence

1. **Base Table** (if new component)
   - Core identification fields
   - Basic dimensions
   - Relationships
   
2. **Extended Fields** (cut list, calculations)
   - Opening reference dimensions
   - Calculated dimensions
   - Material specifications
   
3. **Shop Fields** (practice adjustments)
   - Shop height fields
   - Shop depth fields
   - Shop-specific rules

4. **Hardware Integration**
   - Manufacturer minimums
   - Shop practice minimums

#### Migration Template

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * [Description of what this migration adds]
 * 
 * [Explain the shop rule or calculation if applicable]
 * Example: 5.1875" theoretical → 5.0" shop
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('projects_[component]', function (Blueprint $table) {
            $table->decimal('[field]_inches', 8, 4)->nullable()
                ->after('[previous_field]')
                ->comment('[Clear description of field purpose]');
        });
    }

    public function down(): void
    {
        Schema::table('projects_[component]', function (Blueprint $table) {
            $table->dropColumn(['[field]_inches']);
        });
    }
};
```

### 2.4 Update Eloquent Models

**Goal:** Ensure model reflects all new fields.

#### Model Updates Checklist

- [ ] Add new fields to `$fillable` array
- [ ] Add appropriate `casts()` for decimal/boolean fields
- [ ] Add relationships for hardware products
- [ ] Add accessor methods for formatted dimensions
- [ ] Implement `CabinetComponentInterface` methods

---

## Phase 3: Service Implementation

### 3.1 Create Configurator Service

**Goal:** Build service that calculates all dimensions.

#### Service Structure

```php
<?php

namespace App\Services;

class [Component]ConfiguratorService
{
    // ========================================
    // MANUFACTURER SPECIFICATION CONSTANTS
    // ========================================
    
    // [Document source for each constant]
    public const CLEARANCE_TOP = 0.25;        // 1/4" (6mm)
    public const CLEARANCE_BOTTOM = 0.5625;   // 9/16" (14mm)
    
    // ========================================
    // SHOP PRACTICE CONSTANTS
    // ========================================
    
    public const SHOP_HEIGHT_ROUNDING = 0.5;  // Round to nearest 1/2"
    public const SHOP_DEPTH_ADDITION = 0.25;  // Add 1/4" for safety
    
    // ========================================
    // CONSTRUCTION CONSTANTS
    // ========================================
    
    public const MATERIAL_THICKNESS = 0.5;    // 1/2" material
    
    // ========================================
    // MAIN CALCULATION METHOD
    // ========================================
    
    public function calculateDimensions(
        float $openingWidth,
        float $openingHeight,
        float $openingDepth,
        // ... component-specific parameters
    ): array {
        // 1. Get hardware specs
        // 2. Calculate theoretical dimensions
        // 3. Apply shop rules
        // 4. Validate constraints
        // 5. Return complete result array
    }
    
    // ========================================
    // SHOP RULE METHODS
    // ========================================
    
    public static function roundDownToHalfInch(float $inches): float
    {
        return floor($inches * 2) / 2;
    }
    
    // ========================================
    // UTILITY METHODS
    // ========================================
    
    public static function toFraction(float $decimal, int $denominator = 32): string
    {
        // Convert decimal to fractional string
    }
}
```

#### Calculation Method Return Structure

```php
return [
    'opening' => [
        'width' => $openingWidth,
        'height' => $openingHeight,
        'depth' => $openingDepth,
    ],
    'calculated' => [
        // Theoretical values
        'width' => $calculatedWidth,
        'height' => $calculatedHeight,
        'depth' => $calculatedDepth,
        // Shop values
        'width_shop' => $widthShop,
        'height_shop' => $heightShop,
        'depth_shop' => $depthShop,
    ],
    'clearances' => [
        'left' => $leftClearance,
        'right' => $rightClearance,
        'top' => $topClearance,
        'bottom' => $bottomClearance,
    ],
    'hardware' => [
        'product_id' => $hardwareProductId,
        'name' => $hardwareName,
        'specifications' => [...],
    ],
    'validation' => [
        'valid' => $isValid,
        'issues' => $issuesList,
    ],
];
```

### 3.2 Create Hardware Service

**Goal:** Build service that selects appropriate hardware from EAV.

#### Hardware Service Structure

```php
<?php

namespace App\Services;

class [Component]HardwareService
{
    // ========================================
    // HARDWARE SELECTION
    // ========================================
    
    /**
     * Select appropriate hardware based on component dimensions.
     */
    public function getHardwareForDimensions(
        float $width,
        float $height,
        // ... other parameters
    ): array {
        // Query EAV system for matching products
        // Return best match with specs
    }
    
    /**
     * Get hardware product model.
     */
    public function getHardwareProduct(float $dimension): ?Product
    {
        $hardwareInfo = $this->getHardwareForDimensions($dimension);
        return Product::find($hardwareInfo['product_id']);
    }
    
    /**
     * Get all specifications for hardware from attributes.
     */
    public function getHardwareSpecs(Product $hardware): array
    {
        return [
            'spec1' => $hardware->getSpecValue('Attribute Name 1'),
            'spec2' => $hardware->getSpecValue('Attribute Name 2'),
            // ...
        ];
    }
    
    // ========================================
    // EAV INTEGRATION
    // ========================================
    
    protected function queryHardwareByAttribute(string $attrName, $value): Collection
    {
        $attrId = DB::table('products_attributes')
            ->where('name', $attrName)
            ->value('id');
            
        return DB::table('products_products as p')
            ->join('products_product_attribute_values as pav', 'p.id', '=', 'pav.product_id')
            ->where('pav.attribute_id', $attrId)
            ->where('pav.numeric_value', '<=', $value)
            ->orderByDesc('pav.numeric_value')
            ->get();
    }
    
    // ========================================
    // FALLBACK DATA
    // ========================================
    
    protected function getHardcodedFallback(): array
    {
        // Return hardcoded values if EAV query fails
        return [
            // ...
        ];
    }
}
```

### 3.3 Implement Shop Rules

**Goal:** Encode all shop practices as methods.

#### Shop Rules Pattern

```php
// ========================================
// SHOP RULES
// ========================================

/**
 * Rule: [Description]
 * Purpose: [Why this exists]
 * Applied to: [Which dimensions]
 * Example: [Input] → [Output]
 */
public static function applyShopRule[Name](float $theoretical): float
{
    // Implementation
}

// Common shop rules:

// 1. Round DOWN to increment (for heights that must fit)
public static function roundDownTo(float $value, float $increment): float
{
    return floor($value / $increment) * $increment;
}

// 2. Round UP to increment (for minimums)
public static function roundUpTo(float $value, float $increment): float
{
    return ceil($value / $increment) * $increment;
}

// 3. Add safety margin
public static function addSafetyMargin(float $value, float $margin): float
{
    return $value + $margin;
}

// 4. Simplified rule (replace complex manufacturer spec)
public static function simplifiedMinimum(float $baseValue, float $addition): float
{
    return $baseValue + $addition;
}
```

### 3.4 Add Utility Methods

**Goal:** Common utilities for formatting and conversion.

```php
// ========================================
// UTILITY METHODS
// ========================================

/**
 * Convert decimal inches to fractional string.
 * Example: 11.375 → "11-3/8""
 */
public static function toFraction(float $decimal, int $denominator = 32): string
{
    $whole = floor($decimal);
    $remainder = $decimal - $whole;
    $numerator = round($remainder * $denominator);
    
    if ($numerator == 0) return $whole . '"';
    if ($numerator == $denominator) return ($whole + 1) . '"';
    
    $gcd = self::gcd((int)$numerator, $denominator);
    $num = $numerator / $gcd;
    $den = $denominator / $gcd;
    
    return $whole > 0 ? "{$whole}-{$num}/{$den}\"" : "{$num}/{$den}\"";
}

/**
 * Convert fractional string to decimal.
 * Example: "11-3/8"" → 11.375
 */
public static function fromFraction(string $fraction): float
{
    // Parse and convert
}

/**
 * Greatest common divisor.
 */
private static function gcd(int $a, int $b): int
{
    return $b === 0 ? $a : self::gcd($b, $a % $b);
}

/**
 * Format dimension with both decimal and fraction.
 * Example: 11.375 → "11.375 (11-3/8")"
 */
public static function formatDimension(float $value): string
{
    return sprintf('%.4f (%s)', $value, self::toFraction($value));
}
```

---

## Phase 4: Output Generation

### 4.1 Design Cut List Format

**Goal:** Structure cut list data for CNC and carpenters.

#### Cut List Structure

```php
public function getCutList(...): array
{
    return [
        'summary' => [
            'component_type' => '[type]',
            'opening' => '[W] × [H] × [D]',
            'material' => '[material spec]',
        ],
        'pieces' => [
            'piece_1' => [
                'name' => '[Piece name]',
                'quantity' => 1,
                'material' => '[Material]',
                'dimensions' => [
                    'width' => ['theoretical' => X, 'shop' => Y],
                    'length' => ['theoretical' => X, 'shop' => Y],
                ],
                'operations' => [
                    // Dado, rabbet, drill, etc.
                ],
                'notes' => '[Special instructions]',
            ],
            // ... more pieces
        ],
        'hardware' => [
            // Hardware to include
        ],
        'assembly_notes' => [
            // Assembly instructions
        ],
    ];
}
```

### 4.2 Create HTML Spec Templates

**Goal:** Generate human-readable specification sheets.

#### HTML Template Structure

```html
<!DOCTYPE html>
<html>
<head>
    <title>[Component] Specification - [Dimensions]</title>
    <style>
        /* Color coding */
        .theoretical { color: #c0392b; }  /* Red */
        .shop { color: #27ae60; }         /* Green */
        
        /* Tables */
        table { border-collapse: collapse; }
        th, td { border: 1px solid #ddd; padding: 8px; }
        
        /* Sections */
        .section { margin: 20px 0; }
    </style>
</head>
<body>
    <!-- Header with opening dimensions -->
    <h1>[Component] Specification</h1>
    <h2>Opening: [W]" × [H]" × [D]"</h2>
    
    <!-- Shop values explanation -->
    <div class="info-box">
        <h3>Shop Values Explained</h3>
        <ul>
            <li><span class="theoretical">Theoretical</span>: Calculated from manufacturer specs</li>
            <li><span class="shop">Shop</span>: Adjusted for shop practice</li>
        </ul>
    </div>
    
    <!-- Summary table -->
    <section class="section">
        <h3>Calculated Dimensions</h3>
        <table>
            <tr><th>Dimension</th><th>Theoretical</th><th>Shop</th></tr>
            <!-- Rows -->
        </table>
    </section>
    
    <!-- Cut list -->
    <section class="section">
        <h3>Cut List</h3>
        <!-- Per-piece tables with SVG diagrams -->
    </section>
    
    <!-- Hardware -->
    <section class="section">
        <h3>Hardware Required</h3>
        <table>
            <tr><th>Item</th><th>Quantity</th><th>Notes</th></tr>
        </table>
    </section>
    
    <!-- Diagrams -->
    <section class="section">
        <h3>Visual Reference</h3>
        <!-- SVG diagrams -->
    </section>
</body>
</html>
```

### 4.3 Generate SVG Diagrams

**Goal:** Create scalable vector diagrams of cut pieces.

#### SVG Generation Pattern

```php
public function generatePieceSvg(array $piece, float $scale = 10): string
{
    $width = $piece['width'] * $scale;
    $height = $piece['height'] * $scale;
    
    $svg = <<<SVG
    <svg width="{$width}" height="{$height}" viewBox="0 0 {$width} {$height}">
        <!-- Border -->
        <rect x="0" y="0" width="{$width}" height="{$height}" 
              fill="none" stroke="#333" stroke-width="2"/>
        
        <!-- Dado groove (if applicable) -->
        <rect x="0" y="{$dadoY}" width="{$width}" height="{$dadoHeight}"
              fill="#e0e0e0" stroke="#666"/>
        
        <!-- Dimension labels -->
        <text x="{$midX}" y="-5" text-anchor="middle" class="dimension">
            {$widthLabel}
        </text>
        <text x="-5" y="{$midY}" text-anchor="end" class="dimension">
            {$heightLabel}
        </text>
        
        <!-- Title -->
        <text x="{$midX}" y="{$midY}" text-anchor="middle" class="title">
            {$pieceName}
        </text>
    </svg>
    SVG;
    
    return $svg;
}
```

### 4.4 Build Comparison Tables

**Goal:** Show manufacturer vs shop values clearly.

```html
<table class="comparison">
    <thead>
        <tr>
            <th>[Parameter]</th>
            <th>Manufacturer Spec</th>
            <th>Shop Practice</th>
            <th>Notes</th>
        </tr>
    </thead>
    <tbody>
        <tr>
            <td>[Name]</td>
            <td class="theoretical">[Value]</td>
            <td class="shop">[Value]</td>
            <td>[Explanation]</td>
        </tr>
    </tbody>
</table>
```

---

## Phase 5: Validation & Documentation

### 5.1 Test with Real Examples

**Goal:** Verify calculations match expected results.

#### Test Case Template

```php
/**
 * Test: [Component] with [specific dimensions]
 * Source: [Where these numbers came from]
 */
public function test[Component]Calculation(): void
{
    $service = new [Component]ConfiguratorService(...);
    
    $result = $service->calculateDimensions(
        openingWidth: 20.0,
        openingHeight: 6.0,
        openingDepth: 14.0,
    );
    
    // Verify theoretical values
    $this->assertEquals(19.375, $result['calculated']['width']);
    
    // Verify shop values
    $this->assertEquals(5.0, $result['calculated']['height_shop']);
    
    // Verify hardware selection
    $this->assertEquals(12, $result['hardware']['length']);
}
```

### 5.2 Carpenter Review

**Goal:** Get sign-off from shop staff.

#### Review Checklist

- [ ] Dimensions match what they would calculate manually
- [ ] Shop rules correctly encoded
- [ ] Cut list format is usable
- [ ] Hardware assignments are correct
- [ ] Nothing is missing from the output

#### Feedback Template

```markdown
## Carpenter Review: [Component] Spec System

### Reviewer: [Name]
### Date: [Date]

### Test Case: [Opening dimensions]

| Item | Expected | System Output | Correct? |
|------|----------|---------------|----------|
| [Dimension] | [Value] | [Value] | ✅/❌ |

### Feedback:
- [Issue or approval]

### Sign-off: [Name] [Date]
```

### 5.3 Create System Documentation

**Goal:** Document everything for future reference.

#### Documentation Template

See `DRAWER_SPEC_SYSTEM.md` for the complete format:

1. Overview
2. Architecture diagram
3. Constants tables (manufacturer + shop)
4. Database schema (all fields)
5. Service methods
6. Calculation flow with example
7. Shop rules summary
8. EAV integration
9. Usage examples
10. Entity hierarchy
11. Migration status

### 5.4 Update Related Docs

**Goal:** Keep all documentation synchronized.

- [ ] Update `DATABASE_HIERARCHY.md` with new tables/fields
- [ ] Update `docs/` index if applicable
- [ ] Add to `CHANGELOG.md`
- [ ] Update any API documentation

---

## Component-Specific Considerations

### Drawers (Complete)

- **Hardware:** Slides (Blum TANDEM 563H)
- **Key Specs:** Side clearance, height deduction, slide length
- **Shop Rules:** Height round down 1/2", depth +1/4", min depth +3/4"
- **Cut List:** Sides, front, back, bottom with dado specs

### Doors (Next)

- **Hardware:** Hinges (Blum CLIP top, etc.)
- **Key Specs:** Overlay type, mounting plate, hinge count
- **Shop Rules:** TBD - research needed
- **Cut List:** Door slab or 5-piece (rails, stiles, panel)

### Shelves

- **Hardware:** Shelf pins, adjustable standards
- **Key Specs:** Span limits, weight capacity, edge banding
- **Shop Rules:** TBD
- **Cut List:** Shelf panel, edge treatment

### Face Frames

- **Hardware:** N/A (structural)
- **Key Specs:** Rail/stile dimensions, overlay calculations
- **Shop Rules:** TBD
- **Cut List:** Rails, stiles, mullions

### Rollouts

- **Hardware:** Slides (similar to drawers)
- **Key Specs:** Similar to drawers but different construction
- **Shop Rules:** Similar to drawers
- **Cut List:** Similar to drawers

---

## Quick Reference: Starting a New Component

```bash
# 1. Research Phase
- [ ] Get manufacturer spec sheets
- [ ] Interview shop staff
- [ ] Query existing database
- [ ] Inventory EAV attributes

# 2. Design Phase
- [ ] Draft database schema
- [ ] Identify theoretical vs shop fields
- [ ] Plan migrations

# 3. Implementation Phase
- [ ] Create [Component]ConfiguratorService
- [ ] Create [Component]HardwareService
- [ ] Implement shop rules
- [ ] Add utility methods

# 4. Output Phase
- [ ] Define cut list structure
- [ ] Create HTML template
- [ ] Generate SVG diagrams

# 5. Validation Phase
- [ ] Test with real examples
- [ ] Carpenter review
- [ ] Create documentation
```

---

## Files to Create for Each Component

```
app/
└── Services/
    ├── [Component]ConfiguratorService.php
    └── [Component]HardwareService.php

database/migrations/
├── XXXX_XX_XX_XXXXXX_create_projects_[component]_table.php
├── XXXX_XX_XX_XXXXXX_add_cut_list_fields_to_[component].php
├── XXXX_XX_XX_XXXXXX_add_shop_height_fields_to_[component].php
└── XXXX_XX_XX_XXXXXX_add_shop_depth_fields_to_[component].php

plugins/webkul/projects/src/Models/
└── [Component].php

docs/
└── [COMPONENT]_SPEC_SYSTEM.md

[component]-spec.html  (sample output)
```
