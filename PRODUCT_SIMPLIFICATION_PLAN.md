# Product Name Simplification & Attribute Extraction Plan

**Date**: September 30, 2025
**Goal**: Simplify product names and extract attributes (Brand, Size, Type, Grit, Length, etc.)

---

## Attribute Types to Extract

1. **Brand** - Manufacturer/supplier name
2. **Size** - Physical dimensions (inches, mm)
3. **Length** - For screws, nails, bits
4. **Grit** - For sandpaper/abrasives
5. **Type** - Product variation (Compression, Down-cut, Overlay, etc.)
6. **Gauge** - For nails/fasteners
7. **Model** - Specific model number/variant

---

## RESTRUCTURING PLAN

### 1. CNC Router Bits - Amana Tool

**Current Names:**
- ID 119: "Amana Tool CNC Router Bit" → $109.80
- ID 120: "Amana Tool CNC Router Bit (3/8" Compression)" → $79.82
- ID 121: "Amana Tool CNC Router Bit (3/8" Down-cut)" → $0.00
- ID 122: "Amana Tool CNC Router Bit (VERIFY SPECS - May be discontinued/renamed)" → $56.91
- ID 123: "Amana Tool CNC Router Bit (Up-cut)" → $0.00
- ID 124: "Amana Tool CNC Router Bit (Spiral)" → $133.50

**Simplified Structure:**
- **Parent Name**: "CNC Router Bit"
- **Brand**: Amana Tool
- **Size Attribute**: 3/8"
- **Type Attribute**: Standard, Compression, Down-cut, Up-cut, Spiral

**Proposed Variants:**
1. CNC Router Bit | Brand: Amana Tool | Type: Standard | $109.80
2. CNC Router Bit | Brand: Amana Tool | Size: 3/8" | Type: Compression | $79.82
3. CNC Router Bit | Brand: Amana Tool | Size: 3/8" | Type: Down-cut | $0.00
4. CNC Router Bit | Brand: Amana Tool | Type: TBD | $56.91
5. CNC Router Bit | Brand: Amana Tool | Type: Up-cut | $0.00
6. CNC Router Bit | Brand: Amana Tool | Type: Spiral | $133.50

---

### 2. Brad Nails - RELIABLE

**Current Names:**
- ID 130: "RELIABLE Galvanized Brad Nails - 18 Gauge (1-1/4")" → $54.60
- ID 131: "RELIABLE Galvanized Brad Nails - 18 Gauge (1")" → $49.40
- ID 133: "RELIABLE Galvanized Brad Nails - 18 Gauge (2")" → $59.80

**Simplified Structure:**
- **Parent Name**: "Brad Nails"
- **Brand**: RELIABLE
- **Gauge**: 18
- **Finish**: Galvanized
- **Length Attribute**: 1", 1-1/4", 2"

**Proposed Variants:**
1. Brad Nails | Brand: RELIABLE | Gauge: 18 | Finish: Galvanized | Length: 1" | $49.40
2. Brad Nails | Brand: RELIABLE | Gauge: 18 | Finish: Galvanized | Length: 1-1/4" | $54.60
3. Brad Nails | Brand: RELIABLE | Gauge: 18 | Finish: Galvanized | Length: 2" | $59.80

---

### 3. Hinges - Blum

**Current Names:**
- ID 137: "Blum 1/2 Overlay Hinges (Thick)" → $10.14
- ID 138: "Blum Full Overlay Hinges (Thick)" → $0.00
- ID 139: "Blum Inset Overlay Hinges (Thick)" → $0.00
- ID 136: "CLIP top BLUMOTION Hinge for Blind Corners" → $10.73
- ID 1: "CLIP top BLUMOTION Hinge for Blind Corners" → $8.25

**Simplified Structure:**

**Group A - Overlay Hinges (Thick):**
- **Parent Name**: "Cabinet Hinge (Thick)"
- **Brand**: Blum
- **Type Attribute**: 1/2 Overlay, Full Overlay, Inset Overlay

**Group B - BLUMOTION Hinges (Standalone):**
- **Name**: "Cabinet Hinge - CLIP top BLUMOTION"
- **Brand**: Blum
- **Type**: Blind Corner

**Proposed Variants (Group A):**
1. Cabinet Hinge (Thick) | Brand: Blum | Type: 1/2 Overlay | $10.14
2. Cabinet Hinge (Thick) | Brand: Blum | Type: Full Overlay | $0.00
3. Cabinet Hinge (Thick) | Brand: Blum | Type: Inset Overlay | $0.00

---

### 4. Wood Screws - Plain (Quadrex Drive)

**Current Names:**
- ID 97: "Plain Wood Screw, Flat Head, Quadrex Drive (1-1/4" #6)" → $117
- ID 98: "Plain Wood Screw, Flat Head, Quadrex Drive (1-1/2" #6)" → $45
- ID 134: "Plain Wood Screw, Flat Head, Quadrex Drive (2" #8)" → $110.50
- ID 135: "Plain Wood Screw, Flat Head, Quadrex Drive (3" #8)" → $84.50

**Simplified Structure:**

**Group A - #6 Screws:**
- **Parent Name**: "Wood Screw - Flat Head"
- **Brand**: Generic/TCS
- **Drive**: Quadrex
- **Gauge**: #6
- **Length Attribute**: 1-1/4", 1-1/2"

**Group B - #8 Screws:**
- **Parent Name**: "Wood Screw - Flat Head"
- **Brand**: Generic/TCS
- **Drive**: Quadrex
- **Gauge**: #8
- **Length Attribute**: 2", 3"

**Proposed Variants:**
1. Wood Screw - Flat Head | Drive: Quadrex | Gauge: #6 | Length: 1-1/4" | $117
2. Wood Screw - Flat Head | Drive: Quadrex | Gauge: #6 | Length: 1-1/2" | $45
3. Wood Screw - Flat Head | Drive: Quadrex | Gauge: #8 | Length: 2" | $110.50
4. Wood Screw - Flat Head | Drive: Quadrex | Gauge: #8 | Length: 3" | $84.50

---

### 5. Sanding Discs - Serious Grit

**Current Names:**
- ID 105: "Serious Grit 6-Inch 120 Grit" → $0
- ID 106: "Serious Grit 6-Inch 120 Grit" → $37.99
- ID 107: "Serious Grit 6-Inch 80 Grit" → $0
- ID 23: "Serious Grit 6-Inch 120 Grit Ceramic Multi-Hole Hook & Loop..." → $37.99
- ID 24: "Serious Grit 6-Inch 80 Grit Ceramic Multi-Hole Hook & Loop..." → $37.99

**Simplified Structure:**
- **Parent Name**: "Sanding Disc"
- **Brand**: Serious Grit
- **Size**: 6"
- **Material**: Ceramic
- **Grit Attribute**: 80, 120

**Proposed Variants:**
1. Sanding Disc | Brand: Serious Grit | Size: 6" | Grit: 80 | $0
2. Sanding Disc | Brand: Serious Grit | Size: 6" | Grit: 120 | $37.99

---

### 6. Sandpaper - Generic

**Current Names:**
- ID 109: "6" 100 Grit Sandpaper" → $0
- ID 110: "6" 150 Grit Sandpaper" → $0
- ID 111: "6" 180 Grit Sandpaper" → $0
- ID 112: "6" 220 Grit Sandpaper" → $2

**Simplified Structure:**
- **Parent Name**: "Sandpaper"
- **Brand**: Generic/TCS
- **Size**: 6"
- **Grit Attribute**: 100, 150, 180, 220

**Proposed Variants:**
1. Sandpaper | Size: 6" | Grit: 100 | $0
2. Sandpaper | Size: 6" | Grit: 150 | $0
3. Sandpaper | Size: 6" | Grit: 180 | $0
4. Sandpaper | Size: 6" | Grit: 220 | $2

---

### 7. Drawer Slides

**Current Names:**
- ID 100: "21 inch Drawer Slides" → $0
- ID 101: "18 inch Drawer Slides" → $0
- ID 102: "15 inch Drawer Slides" → $0
- ID 103: "12 inch Drawer Slides" → $0

**Simplified Structure:**
- **Parent Name**: "Drawer Slide"
- **Brand**: Generic/TCS
- **Length Attribute**: 12", 15", 18", 21"

**Proposed Variants:**
1. Drawer Slide | Length: 12" | $0
2. Drawer Slide | Length: 15" | $0
3. Drawer Slide | Length: 18" | $0
4. Drawer Slide | Length: 21" | $0

---

### 8. Saw Blades

**Current Names:**
- ID 117: "12 inch Chop Saw Blades" → $0
- ID 118: "10 inch Table Saw Blades" → $0

**Simplified Structure:**
- **Parent Name**: "Saw Blade"
- **Brand**: Generic/TCS
- **Size Attribute**: 10", 12"
- **Type Attribute**: Chop Saw, Table Saw

**Proposed Variants:**
1. Saw Blade | Type: Chop Saw | Size: 12" | $0
2. Saw Blade | Type: Table Saw | Size: 10" | $0

---

### 9. Edge Banding - Maple

**Current Names:**
- ID 126: "Edgebanding - Maple (250 ft)" → $75.40
- ID 127: "Pre-glued 7/8" width (22mm) maple veneer (Finished)" → $123.50

**Simplified Structure:**
- **Parent Name**: "Edge Banding - Maple"
- **Brand**: Generic/TCS
- **Type Attribute**: Roll (250 ft), Pre-glued 7/8"

**Proposed Variants:**
1. Edge Banding - Maple | Type: Roll | Length: 250 ft | $75.40
2. Edge Banding - Maple | Type: Pre-glued | Width: 7/8" | $123.50

---

### 10. Dust Collection Bags

**Current Names:**
- ID 150: "YUEERIO 709563 Upgraded Dust Collection Bag for JET" → $35.09
- ID 151: "Felder AF22 Dust Collection Bags" → $0
- ID 152: "Floor Unit Dust Collection Bags" → $0

**Simplified Structure:**
- **Parent Name**: "Dust Collection Bag"
- **Brand Attribute**: YUEERIO, Felder, Generic
- **Model Attribute**: JET, AF22, Floor Unit

**Proposed Variants:**
1. Dust Collection Bag | Brand: YUEERIO | Model: JET 709563 | $35.09
2. Dust Collection Bag | Brand: Felder | Model: AF22 | $0
3. Dust Collection Bag | Model: Floor Unit | $0

---

### 11. Bungee Cords

**Current Names:**
- ID 20: "80'' Real Heavy Duty Carabiner Bungee Cords..." → $17.09
- ID 21: "96'' Real Heavy Duty Carabiner Bungee Cords..." → $16.19

**Simplified Structure:**
- **Parent Name**: "Bungee Cord - Heavy Duty"
- **Brand**: Real
- **Length Attribute**: 80", 96"
- **Pack Size**: 4-pack, 2-pack

**Proposed Variants:**
1. Bungee Cord - Heavy Duty | Brand: Real | Length: 80" | Pack: 4 | $17.09
2. Bungee Cord - Heavy Duty | Brand: Real | Length: 96" | Pack: 2 | $16.19

---

### 12. Wood Glue - Titebond

**Current Names:**
- ID 114: "Titebond II Premium Wood Glue" → $24.05
- ID 115: "Titebond Speed Set Wood Glue - 4366" → $28.60

**Simplified Structure:**
- **Parent Name**: "Wood Glue"
- **Brand**: Titebond
- **Type Attribute**: Premium II, Speed Set

**Proposed Variants:**
1. Wood Glue | Brand: Titebond | Type: Premium II | $24.05
2. Wood Glue | Brand: Titebond | Type: Speed Set | Model: 4366 | $28.60

---

## STANDALONE PRODUCTS (Keep Simple Names with Brand Attribute)

### Adhesives
- ID 113: **Epoxy** | Brand: West Systems | $0
- ID 116: **Hot Melt Adhesive** | Brand: Jowatherm | Model: 280.3 | $84.50

### Edge Banding
- ID 125: **Edge Banding - Unfinished** | Brand: Auto Tape | $0

### Fasteners
- ID 128: **Wood Screw - Flat Head with Nibs** | Length: 1-1/2" | Gauge: #6 | $110.50
- ID 129: **Wood Screw - Black Phosphate** | Length: 1-1/2" | Gauge: #8 | $110.50
- ID 132: **Headless Pin** | Gauge: 23 | Length: 1-3/8" | $84.50

### Hardware
- ID 140: **LeMans II System** | Brand: Rev-A-Shelf | $455
- ID 141: **Drawer Paddle (Left)** | Brand: Generic | $0
- ID 142: **Drawer Paddle (Right)** | Brand: Generic | $0
- ID 143: **Inserta Plate** | Brand: Generic | $0
- ID 144: **Trash Pull Out - 35qt** | Brand: Rev-A-Shelf | $162.50
- ID 145: **Recycling Center** | Brand: Rev-A-Shelf | $188.50

### Sanding
- ID 148: **Sandpaper Roll - PSA** | Brand: Serious Grit | Grit: 120 | Size: 2.75" x 20yd | $38.99
- ID 149: **Sandpaper - Rectangular** | Size: 2x4/2x5 | $0

### Maintenance
- ID 146: **White Lithium Grease** | Brand: Generic | $0
- ID 147: **Machine Oil** | Brand: Generic | $0

### Tools
- ID 153: **Drill Bit - Kreg Jig** | Brand: Kreg | $0
- ID 154: **Caliper** | Brand: Generic | $0
- ID 155: **Collet** | Brand: Generic | $0
- ID 156: **Router Bit - Rabbit** | Brand: Generic | $0

---

## ATTRIBUTE STRUCTURE

### Attributes to Create:
1. **Brand** (text)
   - Amana Tool, RELIABLE, Blum, Serious Grit, Titebond, etc.

2. **Length** (dropdown)
   - 1", 1-1/4", 1-1/2", 2", 3", 12", 15", 18", 21", 80", 96"

3. **Size** (dropdown)
   - 3/8", 6", 10", 12", 2.75"

4. **Grit** (dropdown)
   - 80, 100, 120, 150, 180, 220

5. **Type** (dropdown)
   - Standard, Compression, Down-cut, Up-cut, Spiral
   - 1/2 Overlay, Full Overlay, Inset Overlay
   - Chop Saw, Table Saw
   - Premium II, Speed Set
   - etc.

6. **Gauge** (dropdown)
   - #6, #8, 18, 23

7. **Model** (text)
   - 4366, 280.3, 709563, AF22, etc.

8. **Finish** (dropdown)
   - Galvanized, Black Phosphate, Plain

9. **Drive** (dropdown)
   - Quadrex, Phillips, etc.

10. **Pack Size** (number)
    - 2, 4, etc.

---

## IMPLEMENTATION STEPS

1. **Create Brand attribute** in `products_attributes` table
2. **Create attribute values** for all brands
3. **Update existing parent products** with simplified names
4. **Update variants** with simplified names
5. **Link products to Brand attribute values**
6. **Create additional attributes** (Length, Size, Grit, Type, etc.)
7. **Test in admin UI** to ensure proper display
8. **Update inventory** with correct attribute assignments

---

## BENEFITS

✅ **Cleaner product names** - Remove redundant brand/spec info from name
✅ **Better filtering** - Filter by Brand, Size, Type in admin UI
✅ **Easier searching** - Find "CNC Router Bit" + filter by "Amana Tool"
✅ **Consistent structure** - All products follow same naming pattern
✅ **Scalable** - Easy to add new brands/variants
✅ **Professional** - Clean, organized product catalog

---

**Report Generated**: September 30, 2025
**Status**: PLAN READY - Awaiting Approval
