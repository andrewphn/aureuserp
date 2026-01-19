# TCS Grasshopper - Step-by-Step Setup Walkthrough

This guide walks you through setting up a complete TCS cabinet definition from scratch, with detailed steps for each component.

---

## Part 1: Initial Setup (10 minutes)

### Step 1.1: Open Rhino and Grasshopper

1. Launch **Rhino 7** or **Rhino 8**
2. Type `Grasshopper` in the command line and press Enter
3. Wait for Grasshopper to load

### Step 1.2: Create New Definition

1. In Grasshopper: **File** → **New Document**
2. **File** → **Save As**
3. Name: `TCS_Cabinet_Workbench.gh`
4. Save to your project folder

### Step 1.3: Set Up Canvas Regions

Organize your canvas into logical regions:

```
┌─────────────────────────────────────────────────────────────┐
│  REGION 1: API CONNECTION (top-left)                        │
│  ┌─────────────────────────────────────────────────┐       │
│  │                                                  │       │
│  └─────────────────────────────────────────────────┘       │
├─────────────────────────────────────────────────────────────┤
│  REGION 2: NAVIGATION (top-right)                           │
│  ┌─────────────────────────────────────────────────┐       │
│  │                                                  │       │
│  └─────────────────────────────────────────────────┘       │
├─────────────────────────────────────────────────────────────┤
│  REGION 3: CALCULATOR (middle-left)                         │
│  ┌─────────────────────────────────────────────────┐       │
│  │                                                  │       │
│  └─────────────────────────────────────────────────┘       │
├─────────────────────────────────────────────────────────────┤
│  REGION 4: GEOMETRY (middle-right)                          │
│  ┌─────────────────────────────────────────────────┐       │
│  │                                                  │       │
│  └─────────────────────────────────────────────────┘       │
├─────────────────────────────────────────────────────────────┤
│  REGION 5: UI / SAVE (bottom)                               │
│  ┌─────────────────────────────────────────────────┐       │
│  │                                                  │       │
│  └─────────────────────────────────────────────────┘       │
└─────────────────────────────────────────────────────────────┘
```

**Tip**: Hold Ctrl and drag to select multiple components, then Group (Ctrl+G) to organize.

---

## Part 2: API Connection Component (15 minutes)

### Step 2.1: Add GHPython Component

1. Double-click the canvas (empty area)
2. Type: `python`
3. Select **GhPython Script** from the list
4. Place in REGION 1

### Step 2.2: Configure Inputs

1. **Zoom in** on the component
2. Right-click the `x` input → **Rename** → type `api_url`
3. Right-click the `y` input → **Rename** → type `api_token`
4. **Add third input**: Right-click component edge → "+" → name it `test_connection`

### Step 2.3: Configure Outputs

1. Right-click the `a` output → **Rename** → type `connected`
2. **Add more outputs** (right-click edge → "+"):
   - `auth_header`
   - `api_base`
   - `status_msg`
   - `error`

### Step 2.4: Paste Code

1. Double-click the GHPython component to open the editor
2. Delete any existing code
3. Open file: `rhino-plugins/tcs-grasshopper/api/tcs_api_connect.py`
4. Copy ALL contents (Ctrl+A, Ctrl+C)
5. Paste into editor (Ctrl+V)
6. Click the **X** to close the editor (changes auto-save)

### Step 2.5: Add Input Panels

1. Double-click canvas → type `panel` → select **Panel**
2. Place panel to the left of the component
3. Double-click panel → type: `http://aureuserp.test`
4. **Draw a wire** from panel output to `api_url` input

5. Add another Panel for the API token:
   - Place below first panel
   - Paste your API token
   - Connect to `api_token` input

6. Add Boolean Toggle:
   - Double-click canvas → type `toggle` → select **Boolean Toggle**
   - Place below panels
   - Connect to `test_connection` input

### Step 2.6: Test Connection

1. Double-click the Boolean Toggle to switch to `True`
2. Look at the `status_msg` output (hover or add Panel)
3. Should show: **"Connected - X projects available"**

**Troubleshooting**:
- If "Connection failed": Check URL is correct, Laravel Herd is running
- If "Authentication failed": Token is invalid or expired
- If error about SSL: This is expected for local dev, should still work

### Step 2.7: Add Output Panels (Optional but Helpful)

1. Add Panels connected to each output for debugging
2. You can collapse them later

**Your API section should now look like:**
```
[Panel: URL]─────┬───▶[GHPython: TCS API Connect]───▶[Panel: Status]
                 │         │
[Panel: Token]───┤         ├───▶ auth_header (wire continues right)
                 │         │
[Toggle: Test]───┘         └───▶ api_base (wire continues right)
```

---

## Part 3: Project Selector (10 minutes)

### Step 3.1: Add Component

1. Add new **GhPython Script** in REGION 2
2. Rename component: Right-click → **Name** → `TCS Project Selector`

### Step 3.2: Configure I/O

**Inputs** (add and rename):
- `api_base`
- `auth_header`
- `refresh`
- `selected_index`

**Outputs** (add and rename):
- `project_names`
- `project_ids`
- `selected_id`
- `selected_name`
- `partner_name`
- `project_data`
- `error`

### Step 3.3: Paste Code

1. Double-click component
2. Paste contents of `navigation/tcs_project_selector.py`
3. Close editor

### Step 3.4: Connect Wires

1. Draw wire from API Connect's `api_base` → Project Selector's `api_base`
2. Draw wire from API Connect's `auth_header` → Project Selector's `auth_header`
3. Add Boolean Toggle → `refresh`
4. Add Number Slider (0 to 20, integers) → `selected_index`

**To create integer slider:**
1. Add Number Slider
2. Right-click slider → **Edit**
3. Set: N=0, L=0, U=20, D=0 (D=0 means integers)

### Step 3.5: View Projects

1. Toggle `refresh` to True
2. Add Panel to `project_names` output
3. You should see list of project names
4. Adjust slider to select different projects
5. Check `selected_name` changes

---

## Part 4: Room Navigator (10 minutes)

### Step 4.1: Add and Configure Component

1. Add **GhPython Script**
2. Name: `TCS Room Navigator`

**Inputs:**
- `api_base`
- `auth_header`
- `project_id`
- `selected_room_index`
- `selected_location_index`
- `refresh`

**Outputs:**
- `room_names`
- `room_ids`
- `selected_room_id`
- `selected_room_name`
- `location_names`
- `location_ids`
- `selected_location_id`
- `selected_location_name`
- `cabinet_run_names`
- `cabinet_run_ids`
- `tree_data`
- `error`

### Step 4.2: Paste Code and Connect

1. Paste `navigation/tcs_room_navigator.py`
2. Connect:
   - `api_base` from API Connect
   - `auth_header` from API Connect
   - `project_id` from Project Selector's `selected_id`
   - Add sliders for room and location selection
   - Add toggle for refresh

### Step 4.3: Test Navigation

1. Select a project (Project Selector)
2. Check `room_names` shows rooms
3. Adjust room slider → `location_names` updates
4. Adjust location slider → `cabinet_run_names` updates

---

## Part 5: Cabinet List (10 minutes)

### Step 5.1: Add Component

1. Add **GhPython Script**
2. Name: `TCS Cabinet List`

**Inputs:**
- `api_base`
- `auth_header`
- `cabinet_run_id`
- `selected_index`
- `refresh`

**Outputs:**
- `cabinet_names`
- `cabinet_ids`
- `selected_id`
- `selected_name`
- `cabinet_type`
- `dimensions`
- `width`
- `height`
- `depth`
- `cabinet_data`
- `cabinet_count`
- `error`

### Step 5.2: Connect Cabinet Run

1. Paste `navigation/tcs_cabinet_list.py`
2. Connect `api_base` and `auth_header`
3. **Important**: For `cabinet_run_id`, you need to select from the list

**Option A: Use Value List**
1. Add **Value List** component
2. Right-click → **Edit**
3. Manually add cabinet run IDs (check ERP for IDs)

**Option B: Use List Item**
1. Add **List Item** component
2. Connect `cabinet_run_ids` from Room Navigator to List Item's `L` input
3. Connect slider to `i` input
4. Connect output to Cabinet List's `cabinet_run_id`

### Step 5.3: Test Cabinet Selection

1. Select a cabinet run
2. Check `cabinet_names` shows cabinets
3. Adjust selection slider
4. Verify `width`, `height`, `depth` outputs

---

## Part 6: Cabinet Calculator (10 minutes)

### Step 6.1: Add Component

1. Add **GhPython Script** in REGION 3
2. Name: `TCS Cabinet Calculator`

**Inputs:**
- `api_base`
- `auth_header`
- `cabinet_id`
- `override_width`
- `override_height`
- `override_depth`
- `calculate`

**Outputs:**
- `width`
- `height`
- `depth`
- `linear_feet`
- `box_height`
- `toe_kick_height`
- `unit_price`
- `total_price`
- `complexity_score`
- `cut_list_json`
- `calculation_json`
- `error`

### Step 6.2: Connect and Test

1. Paste `calculator/tcs_cabinet_calc.py`
2. Connect:
   - `api_base` and `auth_header` from API Connect
   - `cabinet_id` from Cabinet List's `selected_id`
   - Add sliders for overrides (set to 0 to use API values)
   - Add toggle for `calculate`

3. Test:
   - Toggle `calculate` to True
   - Check dimension outputs
   - Verify pricing outputs

---

## Part 7: Cabinet Box Geometry (10 minutes)

### Step 7.1: Add Component

1. Add **GhPython Script** in REGION 4
2. Name: `TCS Cabinet Box`

**Inputs:**
- `width`
- `height`
- `depth`
- `cabinet_type`
- `position_x`
- `position_y`
- `position_z`
- `include_toe_kick`
- `generate`

**Outputs:**
- `box_geometry`
- `toe_kick_geometry`
- `interior_box`
- `origin_point`
- `bounding_box`
- `dimensions_text`
- `envelope_data`

### Step 7.2: Connect and Generate

1. Paste `geometry/tcs_cabinet_box.py`
2. Connect dimensions from Calculator
3. Add Panel with "base" → `cabinet_type`
4. Add sliders for position (0-100 range)
5. Add toggle for `generate`

### Step 7.3: View in Rhino

1. Toggle `generate` to True
2. **Look at Rhino viewport** - cabinet should appear!
3. Rotate view to inspect
4. Adjust position sliders to move cabinet

**If geometry doesn't appear:**
- Check Rhino is not zoomed out too far
- Try View → Zoom → Zoom Extents in Rhino
- Check component output for errors

---

## Part 8: Bake and Save (5 minutes)

### Step 8.1: Preview Geometry

1. Right-click `box_geometry` output
2. Select **Preview**
3. Geometry shows in Rhino with green color

### Step 8.2: Bake to Rhino

1. Right-click `box_geometry` output
2. Select **Bake**
3. Choose layer (or create new layer "Cabinets")
4. Click OK
5. Geometry is now permanent in Rhino

### Step 8.3: Save Files

1. In Grasshopper: **File** → **Save**
2. In Rhino: **File** → **Save As** → `Cabinet_Project.3dm`

---

## Complete Wiring Diagram

```
┌──────────────────────────────────────────────────────────────────────────┐
│                          TCS CABINET WORKBENCH                            │
├──────────────────────────────────────────────────────────────────────────┤
│                                                                           │
│  [Panel: URL]────┐                                                       │
│                  │                                                       │
│  [Panel: Token]──┼──▶[API Connect]──┬──▶[Project Selector]──▶[selected_id]│
│                  │         │        │           │                        │
│  [Toggle: Test]──┘         │        │           ▼                        │
│                            │        │   [Room Navigator]──▶[run_ids]     │
│                            │        │           │                        │
│                            │        │           ▼                        │
│                            │        └──▶[Cabinet List]──▶[cabinet_id]    │
│                            │                    │                        │
│                            │                    ▼                        │
│                            └───────▶[Calculator]──┬──▶[width,height,depth]│
│                                          │       │                        │
│                                          │       ▼                        │
│                                          │  [Cabinet Box]──▶[Rhino Geo]  │
│                                          │                                │
│                                          └──▶[Cut List Display]          │
│                                                                           │
└──────────────────────────────────────────────────────────────────────────┘
```

---

## Troubleshooting Common Issues

### Issue: "Module has no attribute" error
**Solution**: The code uses Grasshopper-specific syntax. Make sure you're using GHPython, not regular Python.

### Issue: Wires turn red/orange
**Solution**: Data type mismatch. Check that you're connecting correct outputs to inputs.

### Issue: Geometry appears but wrong size
**Solution**: Check Rhino units (should be Inches for TCS system).

### Issue: API returns empty lists
**Solution**: Verify data exists in ERP for the selected project/room/run.

### Issue: Component shows error icon
**Solution**: Hover over component to see error message. Check inputs are connected.

---

## Next Steps

1. **Add Cut List Display** - See Component Reference
2. **Add Human UI Panel** - See SOP document
3. **Add Save to ERP** - For pushing changes back
4. **Create Templates** - Save working definitions as templates

---

## Appendix: Keyboard Shortcuts

| Action | Shortcut |
|--------|----------|
| Pan canvas | Middle mouse drag |
| Zoom | Scroll wheel |
| Select multiple | Ctrl + click |
| Group components | Ctrl + G |
| Ungroup | Ctrl + Shift + G |
| Copy | Ctrl + C |
| Paste | Ctrl + V |
| Undo | Ctrl + Z |
| Toggle preview | Space (on selected) |
| Bake selected | Ctrl + B |
