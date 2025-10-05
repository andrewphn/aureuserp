# TCS Woodwork Pricing System - Implementation Plan

## Overview
Based on the TCS Wholesale Pricing Sheets (3 pages), this document outlines how pricing data should be structured, tagged, and integrated into the AureusERP system.

## 1. Pricing Data Structure

### Cabinet Pricing (Page 1)

**Base Levels** (5 complexity tiers):
- **Level 1**: $138/LF - Paint grade, open boxes only, no doors/drawers
- **Level 2**: $168/LF - Paint grade, semi-European, flat/shaker doors
- **Level 3**: $192/LF - Stain grade, semi-complicated paint grade
- **Level 4**: $210/LF - Beaded frames, specialty doors, moldings
- **Level 5**: $225/LF - Unique custom work, paneling, reeded, rattan

**Material Category Upgrades** (added to base):
- **Paint Grade**: +$138/LF (Hard Maple, Poplar)
- **Stain Grade**: +$156/LF (Oak, Maple)
- **Premium**: +$185/LF (Rifted White Oak, Black Walnut)
- **Custom/Exotic**: TBD (Rare/specialty woods)

### Closet Systems (Page 1)

**Base Labor**: $92/LF (hardware/materials not included)

**Material Costs**:
- Paint Grade: $75.44 materials = **$167.44/LF total**
- Stain Grade: $96.38 materials = **$188.38/LF total**

**Closet Shelf & Rod**:
- $28/LF (paint grade only, wood only - no hardware)

### Floating Shelves (Page 1)

**Standard**: 1.75" thick × 10" deep × up to 120" long

**Pricing**:
- Paint Grade: **$18/LF** (Hard Maple/Poplar)
- Premium: **$24/LF** (White Oak/Walnut)

**Customizations**:
- Custom depths: +$3/LF
- Over 120" length: +$2/LF

*Note: Wood only - mounting brackets not included*

### Trim & Millwork (Page 2)
*To be documented based on page 2 content*

### Custom/Specialty Work (Page 3)
*To be documented based on page 3 content*

---

## 2. Database Schema Recommendations

### Pricing Models Needed

```php
// Product Price Lists (existing table)
products_product_price_lists
- id
- product_id
- price_list_id
- price
- effective_date
- expiration_date

// New: Base Pricing Levels
products_base_pricing_levels
- id
- level (1-5)
- base_price_per_lf
- name (Level 1, Level 2, etc.)
- description
- features (JSON or text)
- effective_date
- expiration_date

// New: Material Category Upgrades
products_material_categories
- id
- category_name (Paint Grade, Stain Grade, Premium, Custom/Exotic)
- upgrade_price_per_lf
- wood_species (JSON array)
- effective_date

// New: Linear Foot Pricing Rules
products_lf_pricing_rules
- id
- product_category_id
- base_price_per_lf
- calculation_method (base_plus_material, flat_rate, custom)
- labor_rate_per_lf (nullable)
- material_rate_per_lf (nullable)
- modifiers (JSON - for custom depths, lengths, etc.)
```

### Tag Integration

**Product Tags** (leverage existing projects_tags pattern):
```
products_tags table
- id
- name
- type (pricing_level, material_category, product_type, customization)
- color
- description
```

**Pricing Tag Types**:

1. **pricing_level tags**:
   - Level 1 (Open Boxes)
   - Level 2 (Semi-European)
   - Level 3 (Stain Grade)
   - Level 4 (Enhanced Details)
   - Level 5 (Custom Work)

2. **material_category tags**:
   - Paint Grade
   - Stain Grade
   - Premium Hardwood
   - Custom/Exotic

3. **product_type tags**:
   - Cabinets
   - Closet Systems
   - Floating Shelves
   - Trim & Millwork
   - Custom Furniture

4. **customization tags**:
   - Custom Depth
   - Extended Length (>120")
   - Beaded Frames
   - Specialty Doors
   - Hardware Included/Not Included

---

## 3. Usage in Project Estimation

### Workflow Integration

**Step 1: Project Type Selection**
- User selects project type (Cabinet, Closet, Shelving, etc.)
- System filters relevant pricing options

**Step 2: Linear Feet Input**
- User enters estimated linear feet
- System stores in `projects.estimated_linear_feet`

**Step 3: Complexity/Level Selection**
- User selects pricing level (1-5) via tag selector
- System calculates base price: `linear_feet × base_level_price`

**Step 4: Material Category**
- User selects material category tag
- System adds upgrade: `+ (linear_feet × material_upgrade_price)`

**Step 5: Customizations**
- User adds customization tags (custom depth, extended length)
- System applies modifiers to price

**Step 6: Final Calculation**
```php
$basePrice = $linearFeet * $baseLevelPrice;
$materialUpgrade = $linearFeet * $materialCategoryPrice;
$customizations = $this->calculateCustomizations($tags, $linearFeet);

$totalEstimate = $basePrice + $materialUpgrade + $customizations;
```

### Production Estimates Table Enhancement

**Current**: `projects_production_estimates`
```sql
ALTER TABLE projects_production_estimates ADD COLUMN pricing_data JSON AFTER estimated_completion_date;
```

**JSON Structure**:
```json
{
  "linear_feet": 100,
  "base_level": {
    "id": 3,
    "name": "Level 3",
    "price_per_lf": 192,
    "total": 19200
  },
  "material_category": {
    "id": 2,
    "name": "Stain Grade",
    "upgrade_per_lf": 156,
    "total": 15600
  },
  "customizations": [
    {
      "name": "Custom Depth",
      "modifier": 3,
      "total": 300
    }
  ],
  "total_estimate": 35100,
  "effective_date": "2025-01-01"
}
```

---

## 4. Tag Selector Enhancement for Pricing

### UI Components Needed

**Pricing Calculator Card** (new Filament custom component):
```php
app/Forms/Components/PricingCalculatorCard.php
```

Features:
- Linear feet input
- Base level selector (visual cards showing Level 1-5)
- Material category dropdown (with pricing preview)
- Customization checkboxes (auto-calculate modifiers)
- Real-time pricing display

**Integration with Existing Tag Selector**:
- Pricing-related tags auto-populate calculator
- Tag selection updates pricing estimate
- Pricing estimate updates selected tags

### Tooltip Enhancement

Add pricing info to tag descriptions:
```php
// In seed migration
'description' => 'Level 3: Stain grade construction, semi-complicated paint grade. Base price: $192/LF'
```

---

## 5. Implementation Phases

### Phase 1: Database Schema (Week 1)
- [ ] Create `products_base_pricing_levels` table
- [ ] Create `products_material_categories` table
- [ ] Create `products_lf_pricing_rules` table
- [ ] Add `pricing_data` JSON column to `projects_production_estimates`
- [ ] Seed initial pricing data from TCS sheets

### Phase 2: Pricing Tags (Week 1-2)
- [ ] Create pricing tag types (pricing_level, material_category, product_type, customization)
- [ ] Seed pricing tags with descriptions
- [ ] Add pricing info to tag tooltips
- [ ] Update TagSelectorPanel to support pricing tags

### Phase 3: Calculator Component (Week 2-3)
- [ ] Build PricingCalculatorCard Filament component
- [ ] Integrate linear feet input with real-time calculation
- [ ] Add base level visual selector
- [ ] Add material category dropdown
- [ ] Add customization modifiers

### Phase 4: Project Integration (Week 3-4)
- [ ] Add pricing calculator to CreateProject form
- [ ] Auto-populate production estimates from pricing
- [ ] Store detailed pricing breakdown in JSON
- [ ] Generate pricing reports

### Phase 5: Pricing Management (Week 4+)
- [ ] Admin interface for pricing updates
- [ ] Price history tracking
- [ ] Material market condition adjustments
- [ ] Automatic expiration handling

---

## 6. Business Rules

### Pricing Validity
- **Effective Date**: January 2025 (from price sheet)
- **Valid For**: 30 days
- **Subject To**: Material market conditions
- **Auto-Expiration**: System should flag expired pricing

### Calculation Rules

1. **Cabinet Pricing** = Base Level + Material Upgrade
   - Example: Level 1 ($138) + Paint Grade ($138) = $306/LF
   - Example: Level 2 ($168) + Stain Grade ($156) = $348/LF

2. **Closet Systems** = Base Labor ($92) + Materials
   - Paint Grade: $92 + $75.44 = $167.44/LF
   - Stain Grade: $92 + $96.38 = $188.38/LF

3. **Floating Shelves** = Base Price + Customizations
   - Standard: $18/LF (paint) or $24/LF (premium)
   - Custom depth: +$3/LF
   - Extended length (>120"): +$2/LF

### Hardware Exclusions
- Closet systems: hardware/materials listed separately
- Floating shelves: wood only, no mounting brackets
- Closet shelf & rod: wood only, no hardware

---

## 7. User Personas & Use Cases

### Bryan (Owner/Operator)
**Needs**:
- Quick pricing estimates for client quotes
- Ability to adjust for material market changes
- Historical pricing comparisons

**Use Case**:
1. Opens new project
2. Enters 100 LF cabinet job
3. Selects Level 3 + Stain Grade
4. System calculates: 100 × ($192 + $156) = $34,800
5. Adds 10% contingency
6. Generates client quote

### David (Project Manager)
**Needs**:
- Accurate cost estimates for scheduling
- Material cost breakdowns
- Labor vs. material split

**Use Case**:
1. Reviews project estimate
2. Validates linear feet calculation
3. Confirms material category with client drawings
4. Adjusts for custom work (Level 5)
5. Creates production schedule based on pricing tier

### Miguel (Production Lead)
**Needs**:
- Understanding of complexity level
- Material requirements
- Custom details affecting shop time

**Use Case**:
1. Receives project with Level 4 tag
2. Reviews pricing breakdown
3. Identifies "Beaded Frames" and "Specialty Doors"
4. Plans shop capacity accordingly
5. Orders premium materials if needed

---

## 8. API Endpoints Needed

### Pricing Calculation API
```php
POST /api/pricing/calculate
{
  "product_type": "cabinet",
  "linear_feet": 100,
  "base_level": 3,
  "material_category": "stain_grade",
  "customizations": ["custom_depth", "extended_length"]
}

Response:
{
  "base_total": 19200,
  "material_upgrade": 15600,
  "customizations": 500,
  "total_estimate": 35300,
  "price_per_lf": 353,
  "breakdown": {...}
}
```

### Price List Management
```php
GET /api/pricing/levels - Get all base levels
GET /api/pricing/materials - Get material categories
GET /api/pricing/rules/{product_type} - Get pricing rules
POST /api/pricing/update - Update pricing (admin)
```

---

## 9. Reporting & Analytics

### Pricing Reports Needed

1. **Estimate Accuracy Report**
   - Compare estimated vs. actual costs
   - Track pricing tier vs. actual complexity
   - Material cost variance

2. **Pricing History**
   - Track price changes over time
   - Material market condition impacts
   - Margin analysis by tier

3. **Product Mix Analysis**
   - Most common pricing levels
   - Material category distribution
   - Customization frequency

---

## 10. Migration Path

### Existing Data
- Review existing `products_product_price_lists` table
- Identify any current linear foot pricing
- Map to new pricing structure

### Data Import
1. Export TCS pricing sheets to structured JSON
2. Create seeder for base_pricing_levels
3. Create seeder for material_categories
4. Import pricing_rules for each product type
5. Generate pricing tags

### Testing
1. Verify calculations match price sheet examples
2. Test edge cases (custom work, exotic materials)
3. Validate expiration logic
4. Test API endpoints

---

## Questions for Personas (To Document)

### For Bryan:
1. How do you currently handle pricing updates when material costs change?
2. What's your typical markup percentage over base pricing?
3. How do you track competitive pricing in the market?
4. Should the system auto-adjust for material cost inflation?

### For David:
1. How do you validate linear feet estimates from client drawings?
2. Do you need to track pricing by client/partner for volume discounts?
3. How should the system handle custom quote requests outside standard tiers?

### For Miguel:
1. How does complexity level affect shop capacity planning?
2. Should Level 4/5 work automatically flag for your review?
3. What material details do you need to see upfront for ordering?

---

## Appendix: Current Table Structure

### Existing Pricing Tables
```sql
-- From migration 2025_02_18_112837
products_product_price_lists
- id
- product_id (FK)
- price_list_id (FK)
- price (decimal)
- min_qty (nullable)
- max_qty (nullable)
- priority (nullable)
- date_start (nullable date)
- date_end (nullable date)
- timestamps
```

### Integration Points
- `projects.estimated_linear_feet` (existing)
- `projects_production_estimates` (existing)
- `projects_tags` (existing - can add pricing tags)
- `products_products` (existing - link pricing to products)

---

**Status**: Draft - Awaiting persona feedback
**Next Steps**: Review with Bryan, David, Miguel to validate approach
**Timeline**: 4-week implementation with weekly milestones
