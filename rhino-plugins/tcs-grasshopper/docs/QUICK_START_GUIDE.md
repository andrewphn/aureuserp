# TCS Grasshopper - Quick Start Guide

Get up and running in 15 minutes with a basic cabinet visualization.

---

## Prerequisites
- Rhino 7/8 with Grasshopper
- Human UI plugin installed
- API token ready

---

## Step 1: Create New Grasshopper Definition

1. Open Rhino
2. Type `Grasshopper` command
3. `File` → `New Document`
4. Save as `TCS_Cabinet_Test.gh`

---

## Step 2: Set Up API Connection (5 min)

### Create API Connect Component

1. Double-click canvas → type "Python" → select **GhPython Script**
2. Drag component to canvas
3. Double-click component to open editor
4. Open file: `rhino-plugins/tcs-grasshopper/api/tcs_api_connect.py`
5. Copy ALL contents and paste into editor
6. Close editor (click X)

### Configure Inputs/Outputs

Right-click component:
- **Rename**: "TCS API Connect"
- **Zoom** input: Right-click `x` input → Rename to `api_url`
- Repeat for `y` → `api_token`, `z` → `test_connection`
- Right-click output `a` → Rename to `connected`
- Continue: `auth_header`, `api_base`, `status_msg`, `error`

### Add Input Controls

1. **Params** tab → **Input** → **Panel**
   - Drag to canvas, connect to `api_url`
   - Double-click panel, type: `http://aureuserp.test`

2. Add another Panel for `api_token`
   - Paste your API token

3. **Params** tab → **Input** → **Boolean Toggle**
   - Connect to `test_connection`

### Test Connection

1. Set Boolean Toggle to `True`
2. Check `status_msg` output → Should show "Connected - X projects available"
3. If error, check token and URL

---

## Step 3: Add Project Selector (3 min)

### Create Component

1. Add new **GhPython Script**
2. Paste contents of `navigation/tcs_project_selector.py`
3. Rename inputs: `api_base`, `auth_header`, `refresh`, `selected_index`
4. Rename outputs: `project_names`, `project_ids`, `selected_id`, `selected_name`, `partner_name`, `project_data`, `error`

### Connect Wires

1. Connect `api_base` output from API Connect → `api_base` input
2. Connect `auth_header` output → `auth_header` input
3. Add Boolean Toggle → `refresh` input
4. Add **Number Slider** (0 to 10, integers) → `selected_index`

### View Results

1. Add **Panel** connected to `project_names` to see available projects
2. Adjust slider to select different projects
3. `selected_name` shows current selection

---

## Step 4: Add Cabinet List (3 min)

For simplicity, we'll skip room navigation and go directly to cabinets.

### Create Component

1. Add new **GhPython Script**
2. Paste contents of `navigation/tcs_cabinet_list.py`
3. Configure inputs/outputs as documented

### Connect

You'll need a `cabinet_run_id`. For testing, use the API directly:

1. Add **Panel** with a known cabinet_run_id (check ERP or use `1`)
2. Connect to `cabinet_run_id` input
3. Connect `api_base` and `auth_header` from API Connect
4. Add slider for `selected_index`

---

## Step 5: Generate Cabinet Geometry (4 min)

### Create Cabinet Box Component

1. Add new **GhPython Script**
2. Paste contents of `geometry/tcs_cabinet_box.py`
3. Configure inputs: `width`, `height`, `depth`, `cabinet_type`, `position_x`, `position_y`, `position_z`, `include_toe_kick`, `generate`

### Connect Dimensions

**Option A: From Cabinet List**
- Connect `width`, `height`, `depth` outputs from Cabinet List

**Option B: Manual Input (for testing)**
- Add Number Sliders:
  - Width: 12 to 48, default 36
  - Height: 12 to 96, default 34.5
  - Depth: 12 to 36, default 24

### Add Position and Generate

1. Add sliders for `position_x`, `position_y`, `position_z` (0 to 100)
2. Add **Boolean Toggle** → `generate`
3. Add **Panel** with "base" → `cabinet_type`

### View Geometry

1. Set `generate` toggle to `True`
2. Cabinet box should appear in Rhino viewport!
3. Adjust dimension sliders to see changes

---

## Step 6: Preview and Bake (2 min)

### Custom Preview (Optional)

1. **Display** tab → **Preview** → **Custom Preview**
2. Connect `box_geometry` to geometry input
3. Add color swatch for cabinet color

### Bake to Rhino

1. Right-click on `box_geometry` output
2. Select **Bake**
3. Choose layer and click OK
4. Geometry is now permanent in Rhino

---

## Complete Basic Setup Diagram

```
┌─────────────────┐
│  Panel: URL     │──┐
└─────────────────┘  │
┌─────────────────┐  │   ┌──────────────────┐
│  Panel: Token   │──┼──▶│  TCS API Connect │
└─────────────────┘  │   └────────┬─────────┘
┌─────────────────┐  │            │
│  Toggle: Test   │──┘       api_base, auth_header
└─────────────────┘               │
                                  ▼
                         ┌──────────────────┐
                    ┌───▶│ Project Selector │
                    │    └────────┬─────────┘
                    │             │ selected_id
                    │             ▼
                    │    ┌──────────────────┐
                    ├───▶│  Cabinet List    │
                    │    └────────┬─────────┘
                    │             │ width, height, depth
                    │             ▼
                    │    ┌──────────────────┐
                    └───▶│  Cabinet Box     │──▶ [Rhino Geometry]
                         └──────────────────┘
```

---

## What's Next?

### Add Room Navigation
Follow full SOP to add room → location → cabinet run navigation

### Add Cut List Display
Connect Cabinet Calculator for cut list and pricing

### Add Human UI Panel
Create interactive control panel with sliders and buttons

### Save Changes to ERP
Use Save to ERP component to push modifications back

---

## Troubleshooting Quick Start

### No geometry appears
1. Check `generate` toggle is `True`
2. Verify dimensions are positive (>0)
3. Check Rhino viewport is not zoomed out too far
4. Look at component output panel for errors

### "Cabinet ID required" error
1. Need to connect a valid cabinet_run_id
2. Check that the run has cabinets in ERP

### Geometry appears at wrong location
1. Check position sliders (default to 0,0,0)
2. Verify Rhino units match expectations (inches)

### API returns empty data
1. Verify token has correct permissions
2. Check that project/room/cabinet data exists in ERP
3. Try refreshing with the refresh toggle
