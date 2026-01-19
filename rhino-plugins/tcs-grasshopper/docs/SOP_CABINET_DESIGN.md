# Standard Operating Procedure: TCS Cabinet Design in Grasshopper

## Document Information
- **Version**: 1.0
- **Last Updated**: January 2025
- **Author**: TCS Woodwork Engineering

---

## 1. Purpose

This SOP defines the standard workflow for designing cabinets using the TCS Grasshopper system, from project selection through geometry generation and ERP synchronization.

---

## 2. Scope

This procedure applies to:
- Cabinet designers using Rhino/Grasshopper
- Project managers reviewing cabinet specifications
- Shop personnel generating cut lists

---

## 3. Prerequisites

### 3.1 Software Requirements
- [ ] Rhino 7 or 8 installed and licensed
- [ ] Grasshopper functional
- [ ] Human UI plugin installed
- [ ] TCS Grasshopper components available

### 3.2 Access Requirements
- [ ] TCS ERP account with API access
- [ ] Valid API token
- [ ] Network access to API server

### 3.3 Project Requirements
- [ ] Project exists in TCS ERP
- [ ] Room structure defined
- [ ] Cabinet runs created with locations

---

## 4. Procedure

### Phase 1: Session Setup

#### 4.1.1 Open Working File
1. Launch Rhino
2. Open project Rhino file or create new
3. Set units to **Inches** (important for TCS calculations)
4. Type `Grasshopper` command

#### 4.1.2 Load TCS Definition
**Option A**: Open existing definition
```
File → Open → TCS_Cabinet_System.gh
```

**Option B**: Create from components
- Follow Quick Start Guide to create definition

#### 4.1.3 Configure API Connection
1. Locate **TCS API Connect** component
2. Verify/update API URL:
   - Development: `http://aureuserp.test`
   - Staging: `https://staging.tcswoodwork.com`
3. Enter API token in token panel
4. Click **Test Connection** toggle
5. Verify "Connected" status

**Checkpoint**: Status shows "Connected - X projects available"

---

### Phase 2: Project Navigation

#### 4.2.1 Select Project
1. Locate **TCS Project Selector** component
2. Click **Refresh** if project list is empty
3. View `project_names` output in connected Panel
4. Adjust `selected_index` slider to choose project
5. Verify `partner_name` shows correct client

**Checkpoint**: Correct project name displayed

#### 4.2.2 Navigate to Room
1. Locate **TCS Room Navigator** component
2. View available rooms in `room_names` output
3. Select room using `selected_room_index` slider
4. View locations in `location_names` output
5. Select location using `selected_location_index`
6. Note available cabinet runs in `cabinet_run_names`

**Checkpoint**: Correct room and location selected

#### 4.2.3 Select Cabinet Run
1. From Room Navigator, identify target `cabinet_run_id`
2. Use Value List or Panel to set the ID
3. Connect to **TCS Cabinet List** component
4. View available cabinets

**Checkpoint**: Cabinet list shows expected cabinets

#### 4.2.4 Select Cabinet
1. Adjust `selected_index` to choose cabinet
2. Verify `selected_name` shows correct cabinet
3. Check `dimensions` output for accuracy
4. Note `cabinet_type` (base, wall, tall)

**Checkpoint**: Correct cabinet selected with accurate dimensions

---

### Phase 3: Cabinet Calculation

#### 4.3.1 Run Calculation
1. Locate **TCS Cabinet Calculator** component
2. Verify `cabinet_id` is connected
3. Click **Calculate** toggle
4. Wait for API response (may take several seconds)

#### 4.3.2 Review Calculation Results
1. Check `width`, `height`, `depth` outputs
2. Review `box_height` (height minus toe kick)
3. Check `linear_feet` value
4. Review `unit_price` and `total_price`

**Checkpoint**: Calculations match expected values

#### 4.3.3 Review Cut List
1. Locate **TCS Cut List** component
2. Connect `cut_list_json` from Calculator
3. View `table_text` in connected Panel
4. Verify all parts are listed with correct dimensions

**Checkpoint**: Cut list contains all expected parts

---

### Phase 4: Apply Overrides (If Needed)

#### 4.4.1 Dimension Overrides
1. Locate **TCS Override Manager** component
2. Connect dimension sliders to override inputs:
   - `override_width`: 0 = use calculated
   - `override_height`: 0 = use calculated
   - `override_depth`: 0 = use calculated
3. Adjust slider to desired value
4. Overridden dimensions flow to geometry

#### 4.4.2 Price Overrides
1. Connect slider to `override_price_lf`
2. Set custom price per linear foot
3. View updated pricing in Calculator outputs

#### 4.4.3 Save Overrides
1. Toggle **Apply** to save to Rhino document
2. Overrides persist with .3dm file
3. Use **Reset** to clear all overrides

**Checkpoint**: Overrides applied and reflected in outputs

---

### Phase 5: Geometry Generation

#### 4.5.1 Generate Cabinet Box
1. Locate **TCS Cabinet Box** component
2. Connect dimensions from Calculator or Override Manager
3. Set position sliders (or connect to layout system)
4. Set `cabinet_type` ("base", "wall", "tall")
5. Toggle **Generate** to create geometry

#### 4.5.2 Generate Detailed Parts
1. Locate **TCS Parts Generator** component
2. Connect `cut_list_json` or `calculation_json`
3. Set same position as Cabinet Box
4. Optionally set `explode_distance` to separate parts
5. Toggle **Generate**

#### 4.5.3 Generate Face Frame
1. Locate **TCS Face Frame** component
2. Connect width and height
3. Set stile/rail widths (default 1.5")
4. Set `opening_count` (1 for single door, 2 for double)
5. Toggle **Generate**

#### 4.5.4 Generate Drawers
1. Locate **TCS Drawer** component
2. Connect opening dimensions from Face Frame
3. Connect `cavity_depth` from Calculator
4. Set `drawer_height` (typically 4" to 8")
5. Select `slide_type` (blum_tandem, side_mount, undermount)
6. Toggle **Generate**

**Checkpoint**: All geometry visible in Rhino viewport

---

### Phase 6: Review and Validation

#### 4.6.1 Visual Inspection
1. Rotate Rhino view to inspect all sides
2. Check component alignment
3. Verify toe kick height and setback
4. Check face frame opening sizes
5. Verify drawer clearances

#### 4.6.2 Dimension Verification
1. Use Rhino dimension tools to measure key dimensions
2. Compare to cut list values
3. Verify linear feet calculation
4. Check pricing accuracy

#### 4.6.3 Interference Check
1. Look for overlapping parts
2. Check drawer slide clearances (0.625" per side for Blum)
3. Verify face frame doesn't obstruct drawers/doors

**Checkpoint**: All geometry validates correctly

---

### Phase 7: Output and Documentation

#### 4.7.1 Export Cut List
1. View `table_text` from Cut List component
2. Copy to clipboard or export CSV
3. Use for shop production sheet

#### 4.7.2 Bake Geometry
1. Select output you want to keep:
   - `box_geometry` for envelope only
   - `all_parts` for detailed parts
2. Right-click output → **Bake**
3. Select target layer
4. Geometry is now permanent in Rhino

#### 4.7.3 Save to ERP
1. Locate **TCS Save to ERP** component
2. Connect any modified dimensions
3. Toggle **Save**
4. Verify "Saved successfully" message
5. Check ERP to confirm updates

**Checkpoint**: Changes saved to ERP, geometry baked to Rhino

---

### Phase 8: Session Cleanup

#### 4.8.1 Save Grasshopper Definition
```
File → Save
```

#### 4.8.2 Save Rhino File
- File contains baked geometry
- Overrides stored in document user text
- Project link preserved

#### 4.8.3 Document Changes
- Note any overrides applied
- Record custom pricing
- Log any issues encountered

---

## 5. Quality Checkpoints Summary

| Phase | Checkpoint | Pass Criteria |
|-------|------------|---------------|
| 1 | API Connected | Status shows "Connected" |
| 2 | Project Selected | Correct project/room/cabinet |
| 3 | Calculation Complete | Dimensions and pricing correct |
| 4 | Overrides Applied | Changes reflected in outputs |
| 5 | Geometry Generated | All parts visible |
| 6 | Validation Passed | No dimension errors |
| 7 | Outputs Created | Cut list and geometry exported |
| 8 | Session Saved | Files and ERP updated |

---

## 6. Troubleshooting

### API Connection Failed
1. Check network connectivity
2. Verify API URL is correct
3. Test token in browser/curl
4. Check Laravel Herd is running (dev)

### Calculation Returns Empty
1. Verify cabinet has sections defined in ERP
2. Check cabinet has valid dimensions
3. Try refreshing cabinet list

### Geometry Not Visible
1. Check generate toggle is True
2. Verify dimensions are positive
3. Check Rhino viewport zoom level
4. Look at component output panel for errors

### Overrides Not Saving
1. Verify Rhino document is saved (.3dm)
2. Check Apply toggle was set to True
3. Look for error messages

### ERP Save Failed
1. Check API token has write permissions
2. Verify cabinet_id is valid
3. Check dimension values are in valid range
4. Look at error output for details

---

## 7. Reference

### TCS Construction Constants
```
TOE_KICK_HEIGHT = 4.5"
TOE_KICK_SETBACK = 3.0"
FACE_FRAME_STILE = 1.5"
FACE_FRAME_RAIL = 1.5"
MATERIAL_THICKNESS = 0.75"
BACK_PANEL_THICKNESS = 0.25"
BLUM_SIDE_DEDUCTION = 0.625"
BLUM_HEIGHT_DEDUCTION = 0.8125"
```

### Default Pricing Tiers
```
Base: $125/LF
Wall: $95/LF
Tall: $150/LF
Vanity: $110/LF
Drawer Base: $140/LF
Corner: $175/LF
```

### API Endpoints
```
Projects: /api/v1/projects
Rooms: /api/v1/rooms
Cabinet Runs: /api/v1/cabinet-runs
Cabinets: /api/v1/cabinets
Calculate: POST /api/v1/cabinets/{id}/calculate
Cut List: GET /api/v1/cabinets/{id}/cut-list
```

---

## 8. Revision History

| Version | Date | Author | Changes |
|---------|------|--------|---------|
| 1.0 | 2025-01-19 | TCS Engineering | Initial release |
