# Cabinet Templates & Size Tracking System

## Overview
Track common cabinet sizes over time to create reusable templates and optimize quoting workflow.

---

## Size Tracking Features

### 1. Size Range Categories
Every cabinet specification is automatically categorized by linear feet:

```php
Size Ranges:
- Small:       ≤ 1.5 LF  (12-18")
- Medium:      1.5-3 LF  (18-36")
- Large:       3-4 LF    (36-48")
- Extra-Large: > 4 LF    (48"+)
```

**Usage:**
```php
// Get all medium-sized cabinets
$specs = CabinetSpecification::bySizeRange('medium')->get();

// Count by size range for analytics
$distribution = CabinetSpecification::select('size_range')
    ->groupBy('size_range')
    ->selectRaw('size_range, COUNT(*) as count')
    ->get();
```

### 2. Common Size Detection
System automatically detects if cabinet matches a standard/common size:

```php
Common Sizes Tracked:
BASE CABINETS (24" deep × 30" tall):
- 12", 15", 18", 24", 30", 36" widths

WALL CABINETS (12" deep × 30" tall):
- 12", 15", 18", 24", 30", 36" widths

TALL/PANTRY (24" deep):
- 18" × 84"H
- 24" × 84"H
- 30" × 96"H
```

**Example:**
```php
$spec = CabinetSpecification::find(1);
echo $spec->common_size_match;  // "36" Base" or null
```

### 3. Template Generation from Usage Data
System learns from your cabinet specifications to suggest templates:

```php
// Get 10 most commonly built cabinet dimensions
$templates = CabinetSpecification::mostCommon(10)->get();

Result:
┌─────────────────────────────────────────┐
│ Most Common Cabinet Dimensions          │
├─────────────────────────────────────────┤
│ 36" × 24" × 12" × 30"  (24 times)      │
│ 30" × 24" × 12" × 30"  (18 times)      │
│ 24" × 24" × 12" × 30"  (15 times)      │
│ 18" × 12" × 12" × 30"  (12 times)      │
│ ...                                     │
└─────────────────────────────────────────┘
```

---

## UI Features

### Feature 1: Quick Size Presets (Quote Screen)

**Location:** Cabinet dimensions form
**UI Component:** Size preset buttons

```
┌─────────────────────────────────────────┐
│ QUICK SIZES (Base Cabinets)             │
│ ┌────┐ ┌────┐ ┌────┐ ┌────┐ ┌────┐     │
│ │12" │ │18" │ │24" │ │30" │ │36" │     │
│ └────┘ └────┘ └────┘ └────┘ └────┘     │
│                                         │
│ OR CUSTOM DIMENSIONS:                   │
│ Length:  [__] inches                    │
│ Width:   [__] inches                    │
│ Depth:   [24] inches (auto)             │
│ Height:  [30] inches (auto)             │
└─────────────────────────────────────────┘
```

**Behavior:**
- Click "24"" button → Auto-fills: L=24", W=24", D=24", H=30"
- Shows pricing instantly: "24" = 2 LF × $229/LF = $458"
- Most-used sizes appear first (from usage data)

### Feature 2: Template Library

**Location:** New "Templates" tab in product management
**Purpose:** Save common cabinet configurations as reusable templates

```
┌─────────────────────────────────────────┐
│ CABINET TEMPLATES                       │
├─────────────────────────────────────────┤
│                                         │
│ 🔥 POPULAR (Auto-generated)             │
│ ┌─────────────────────────────────────┐ │
│ │ 36" Base - Shaker Maple (24 uses)   │ │
│ │ 36"L × 24"W × 12"D × 30"H          │ │
│ │ Frameless, Shaker, Maple, Clear    │ │
│ │ $229/LF × 3LF = $687               │ │
│ │ [Use Template]                      │ │
│ └─────────────────────────────────────┘ │
│                                         │
│ ┌─────────────────────────────────────┐ │
│ │ 30" Wall - Slab MDF (18 uses)       │ │
│ │ 30"L × 12"W × 12"D × 30"H          │ │
│ │ Face Frame, Slab, MDF, Paint       │ │
│ │ $145/LF × 2.5LF = $362             │ │
│ │ [Use Template]                      │ │
│ └─────────────────────────────────────┘ │
│                                         │
│ 💾 SAVED TEMPLATES                      │
│ - Kitchen Standard Set (6 configs)      │
│ - Bathroom Vanity Set (3 configs)       │
│ - Office Built-ins Set (4 configs)      │
│                                         │
│ [+ Create New Template]                 │
└─────────────────────────────────────────┘
```

### Feature 3: Size Range Analytics

**Location:** Reports dashboard
**Purpose:** Understand cabinet size distribution and optimize inventory

```
┌─────────────────────────────────────────┐
│ CABINET SIZE DISTRIBUTION               │
├─────────────────────────────────────────┤
│                                         │
│ Small (12-18"):     ████████ 32%        │
│ Medium (18-36"):    ████████████ 48%    │
│ Large (36-48"):     ████ 16%            │
│ Extra-Large (48"+): ██ 4%               │
│                                         │
│ TOP 5 EXACT SIZES THIS MONTH:           │
│ 1. 36" × 24" × 30"  (24 cabinets)      │
│ 2. 30" × 24" × 30"  (18 cabinets)      │
│ 3. 24" × 24" × 30"  (15 cabinets)      │
│ 4. 18" × 12" × 30"  (12 cabinets)      │
│ 5. 12" × 12" × 30"  (8 cabinets)       │
│                                         │
│ RECOMMENDATION:                         │
│ Consider pre-building 36" × 24" × 30"  │
│ base cabinets (most common size)        │
└─────────────────────────────────────────┘
```

---

## Database Queries

### Query 1: Get Most Common Dimensions
```php
// Get top 20 most-used cabinet sizes
$templates = CabinetSpecification::mostCommon(20)->get();

foreach ($templates as $template) {
    echo "{$template->length_inches}\" × {$template->depth_inches}\" × {$template->height_inches}\" ";
    echo "({$template->usage_count} times)\n";
}
```

### Query 2: Size Range Distribution
```php
// Analyze cabinet sizes built this year
$distribution = CabinetSpecification::whereYear('created_at', 2025)
    ->selectRaw('
        CASE
            WHEN linear_feet <= 1.5 THEN "small"
            WHEN linear_feet <= 3.0 THEN "medium"
            WHEN linear_feet <= 4.0 THEN "large"
            ELSE "extra-large"
        END as size_range,
        COUNT(*) as count
    ')
    ->groupBy('size_range')
    ->get();
```

### Query 3: Find Similar Cabinets
```php
// Find cabinets similar to a given spec (±2" tolerance)
$similar = CabinetSpecification::whereBetween('length_inches', [34, 38])
    ->whereBetween('depth_inches', [22, 26])
    ->whereBetween('height_inches', [28, 32])
    ->with('productVariant')
    ->get();
```

---

## Model Methods (Already Implemented)

### Size Range Attribute
```php
$spec = CabinetSpecification::find(1);
echo $spec->size_range;  // "medium"
```

### Common Size Matching
```php
$spec = CabinetSpecification::find(1);
echo $spec->common_size_match;  // "36" Base" or null
```

### Template Generation
```php
// Get template data for UI
$templates = CabinetSpecification::generateTemplates();
// Returns: Collection with name, dimensions, usage_count, size_range
```

### Size Range Filtering
```php
// Get all medium cabinets
$cabinets = CabinetSpecification::bySizeRange('medium')->get();

// Get most common in size range
$commonMedium = CabinetSpecification::bySizeRange('medium')
    ->mostCommon(5)
    ->get();
```

---

## Implementation Roadmap

### Phase 1: Basic Tracking ✅ (Complete)
- [x] Size range auto-categorization
- [x] Common size detection
- [x] Template generation from usage data
- [x] Database queries for analytics

### Phase 2: UI Integration (Next)
- [ ] Quick size preset buttons on quote form
- [ ] Template library view
- [ ] "Use Template" action
- [ ] Size analytics dashboard

### Phase 3: Smart Suggestions (Future)
- [ ] ML-based template recommendations
- [ ] Auto-suggest based on customer history
- [ ] Material optimization for common sizes
- [ ] Pre-build queue suggestions

### Phase 4: Advanced Features (Later)
- [ ] Template versioning
- [ ] Template sharing between users
- [ ] Cabinet configuration sets (kitchen packages)
- [ ] Integration with inventory for pre-built cabinets

---

## Usage Examples

### Example 1: Bryan's Quick Quote Workflow
```
1. Customer needs kitchen cabinets
2. Bryan opens quote form
3. Clicks "Templates" → sees "Kitchen Standard Set"
4. Set includes:
   - 6× 36" Base Cabinets (most common)
   - 4× 30" Wall Cabinets
   - 2× 18" Wall Cabinets
   - 1× 24" Tall Pantry
5. Clicks "Use Template" → All specs auto-fill
6. Adjusts quantity: 36" Base from 6 to 8
7. Total recalculates instantly
8. Quote complete in 30 seconds
```

### Example 2: Shop Floor Production Planning
```
1. Production manager reviews size distribution report
2. Sees 36" × 24" × 30" base is built 2-3× per week
3. Decides to pre-build 4 units in standard Maple Shaker
4. When quote comes in with matching specs:
   - Pull from pre-built inventory
   - Ship same day
   - Faster delivery, happy customer
```

### Example 3: Material Optimization
```
1. System shows 24" × 12" × 30" wall cabinets are common
2. Purchase manager sees pattern
3. Optimizes plywood cutting for this size:
   - 4×8 sheet yields 8 cabinet boxes with minimal waste
   - Pre-cuts common sizes during slow periods
   - Speeds up production when orders come in
```

---

## API Endpoints (To Create)

### GET /api/cabinet-templates
Returns template data for UI:
```json
{
  "popular": [
    {
      "name": "36\" Base - Shaker Maple",
      "dimensions": { "l": 36, "w": 24, "d": 12, "h": 30 },
      "variant_id": 127,
      "usage_count": 24,
      "price_per_lf": 229,
      "total_price": 687
    }
  ],
  "saved": [ ... ],
  "size_distribution": {
    "small": 32,
    "medium": 48,
    "large": 16,
    "extra-large": 4
  }
}
```

### POST /api/cabinet-specs/from-template
Create spec from template:
```json
{
  "template_id": 5,
  "quantity": 4,
  "modifications": {
    "hardware_notes": "Soft-close hinges",
    "custom_modifications": "Extra shelf at 15\""
  }
}
```

---

## Success Metrics

### For Bryan (Speed)
- Quote creation time with templates: **< 30 seconds** (vs 2 min manual)
- Template usage rate: **> 60%** of quotes
- Accuracy improvement: **100%** (no dimension errors)

### For Shop (Efficiency)
- Pre-build accuracy: **> 80%** match rate
- Material waste reduction: **15%** (optimized cutting)
- Production time savings: **20%** (pre-built common sizes)

### For Business (Profitability)
- Quote-to-order conversion: **+25%** (faster quotes)
- Inventory turnover: **+30%** (pre-built popular sizes)
- Customer satisfaction: **+20%** (faster delivery)

---

## Conclusion

The template and sizing system provides:

✅ **Smart Learning** - System learns from usage patterns
✅ **Quick Quoting** - Templates speed up Bryan's workflow
✅ **Production Planning** - Data-driven pre-build decisions
✅ **Material Optimization** - Cut common sizes efficiently
✅ **Customer Service** - Faster quotes and delivery

**Status:** Model complete, UI integration pending
**Next Step:** Build template UI components
