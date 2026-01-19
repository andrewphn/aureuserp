# TCS Grasshopper - Testing Guide

## Overview

This guide covers how to test each component of the TCS Grasshopper system to ensure proper functionality before production use.

---

## Test Environment Setup

### 1. Prepare Test Data in ERP

Before testing, ensure you have test data in the ERP:

```bash
# SSH to staging or use local dev
cd /path/to/aureuserp

# Create test project via tinker
php artisan tinker
```

```php
// Create test project
$project = \Webkul\Project\Models\Project::create([
    'name' => 'GH Test Project',
    'project_number' => 'GH-TEST-001',
    'status' => 'in_progress',
]);

// Create test room
$room = $project->rooms()->create([
    'name' => 'Test Kitchen',
    'type' => 'kitchen',
]);

// Create test location
$location = $room->locations()->create([
    'name' => 'North Wall',
    'position' => 'north',
]);

// Create test cabinet run
$run = $location->cabinetRuns()->create([
    'name' => 'Base Run A',
    'run_type' => 'base',
]);

// Create test cabinet
$cabinet = $run->cabinets()->create([
    'name' => 'B1',
    'cabinet_number' => 'B1',
    'length_inches' => 36,
    'width_inches' => 36,
    'height_inches' => 34.5,
    'depth_inches' => 24,
    'cabinet_level' => 'base',
    'quantity' => 1,
]);

echo "Created cabinet ID: " . $cabinet->id;
```

### 2. Get API Token

```bash
# Create token via artisan
php artisan tinker
```

```php
$user = \Webkul\Security\Models\User::first();
$token = $user->createToken('GH-Test')->plainTextToken;
echo $token;
```

---

## Component Tests

### Test 1: API Connection

**Component**: `tcs_api_connect.py`

**Setup**:
1. Create GHPython component
2. Paste component code
3. Configure inputs/outputs

**Test Steps**:

| Step | Action | Expected Result |
|------|--------|-----------------|
| 1.1 | Set api_url to `http://aureuserp.test` | URL accepted |
| 1.2 | Set api_token to valid token | Token accepted |
| 1.3 | Toggle test_connection to True | `connected` = True |
| 1.4 | Check status_msg | "Connected - X projects available" |
| 1.5 | Set invalid token | `error` shows "Authentication failed" |
| 1.6 | Set invalid URL | `error` shows "Connection failed" |

**Pass Criteria**:
- ✅ Valid credentials return connected=True
- ✅ Invalid credentials return appropriate error

---

### Test 2: API Fetch (GET Requests)

**Component**: `tcs_api_fetch.py`

**Test Steps**:

| Step | Action | Expected Result |
|------|--------|-----------------|
| 2.1 | Set endpoint to `/api/v1/projects` | Data returned |
| 2.2 | Check count output | Number > 0 |
| 2.3 | Wait 30 seconds, check again | `cached` = True |
| 2.4 | Toggle refresh | `cached` = False, fresh data |
| 2.5 | Set invalid endpoint | `error` shows 404 |
| 2.6 | Set params to `{"per_page": "5"}` | Only 5 items returned |

**Pass Criteria**:
- ✅ Valid endpoints return data
- ✅ Caching works (60 second TTL)
- ✅ Refresh bypasses cache
- ✅ Query params applied correctly

---

### Test 3: API Write (POST/PUT/DELETE)

**Component**: `tcs_api_write.py`

**Test Steps**:

| Step | Action | Expected Result |
|------|--------|-----------------|
| 3.1 | Set method="PUT", endpoint to cabinet | Ready status |
| 3.2 | Set payload to `{"shop_notes": "Test"}` | Payload accepted |
| 3.3 | Toggle execute=False | "Set execute=True to send" |
| 3.4 | Toggle execute=True | `success` = True |
| 3.5 | Verify in ERP | shop_notes updated |
| 3.6 | Check audit log | Operation logged |

**Pass Criteria**:
- ✅ Requires execute toggle for safety
- ✅ Updates ERP successfully
- ✅ Returns updated data
- ✅ Audit log populated

---

### Test 4: Project Selector

**Component**: `tcs_project_selector.py`

**Test Steps**:

| Step | Action | Expected Result |
|------|--------|-----------------|
| 4.1 | Connect API credentials | Projects loaded |
| 4.2 | Check project_names | List of project names |
| 4.3 | Set selected_index=0 | First project selected |
| 4.4 | Check selected_name | Project name displayed |
| 4.5 | Check partner_name | Partner name displayed |
| 4.6 | Adjust slider | Different project selected |

**Pass Criteria**:
- ✅ Projects load from API
- ✅ Selection works via index
- ✅ Partner relationship resolved

---

### Test 5: Room Navigator

**Component**: `tcs_room_navigator.py`

**Test Steps**:

| Step | Action | Expected Result |
|------|--------|-----------------|
| 5.1 | Connect project_id | Rooms loaded |
| 5.2 | Check room_names | List of rooms |
| 5.3 | Select room | Locations populated |
| 5.4 | Select location | Cabinet runs populated |
| 5.5 | Check cabinet_run_ids | Valid IDs listed |

**Pass Criteria**:
- ✅ Cascading selection works
- ✅ Each level filters next level
- ✅ Cabinet run IDs available for downstream

---

### Test 6: Cabinet List

**Component**: `tcs_cabinet_list.py`

**Test Steps**:

| Step | Action | Expected Result |
|------|--------|-----------------|
| 6.1 | Connect cabinet_run_id | Cabinets loaded |
| 6.2 | Check cabinet_names | List with dimensions |
| 6.3 | Select cabinet | `selected_id` set |
| 6.4 | Check width, height, depth | Correct values |
| 6.5 | Check cabinet_type | Correct type |
| 6.6 | Check linear_feet | Calculated correctly |

**Pass Criteria**:
- ✅ Cabinets load for run
- ✅ Dimensions extracted
- ✅ Type information available

---

### Test 7: Cabinet Calculator

**Component**: `tcs_cabinet_calc.py`

**Test Steps**:

| Step | Action | Expected Result |
|------|--------|-----------------|
| 7.1 | Connect cabinet_id | Ready state |
| 7.2 | Set overrides to 0 | Uses API values |
| 7.3 | Toggle calculate | API called |
| 7.4 | Check dimensions | Match API |
| 7.5 | Check box_height | Height - toe kick |
| 7.6 | Check pricing | Values populated |
| 7.7 | Set override_width=42 | Width changes to 42 |
| 7.8 | Check cut_list_json | Parts listed |

**Pass Criteria**:
- ✅ API calculation works
- ✅ Derived dimensions correct
- ✅ Overrides apply
- ✅ Cut list generated

---

### Test 8: Cut List Display

**Component**: `tcs_cut_list.py`

**Test Steps**:

| Step | Action | Expected Result |
|------|--------|-----------------|
| 8.1 | Connect cut_list_json | Parts parsed |
| 8.2 | Check table_text | Formatted table |
| 8.3 | Check part_names | Part names listed |
| 8.4 | Check board_feet | Calculated total |
| 8.5 | Set filter_type="face_frame" | Only face frame parts |
| 8.6 | Clear filter | All parts shown |

**Pass Criteria**:
- ✅ JSON parsed correctly
- ✅ Table formatted
- ✅ Filtering works
- ✅ Board feet calculated

---

### Test 9: Override Manager

**Component**: `tcs_override_manager.py`

**Test Steps**:

| Step | Action | Expected Result |
|------|--------|-----------------|
| 9.1 | Connect cabinet_id | Ready state |
| 9.2 | Set override_width=40 | Override active |
| 9.3 | Check width_overridden | True |
| 9.4 | Toggle apply | Saved to document |
| 9.5 | Save Rhino file | Overrides persist |
| 9.6 | Reopen file | Overrides restored |
| 9.7 | Toggle reset | Overrides cleared |

**Pass Criteria**:
- ✅ Overrides tracked
- ✅ Persist in Rhino document
- ✅ Reset clears all

---

### Test 10: Geometry - Cabinet Box

**Component**: `tcs_cabinet_box.py`

**Test Steps**:

| Step | Action | Expected Result |
|------|--------|-----------------|
| 10.1 | Set width=36, height=34.5, depth=24 | Dimensions accepted |
| 10.2 | Set cabinet_type="base" | Toe kick enabled |
| 10.3 | Toggle generate | Geometry appears |
| 10.4 | Check box_geometry | Valid Brep |
| 10.5 | Check toe_kick_geometry | 4.5" tall geometry |
| 10.6 | Set cabinet_type="wall" | No toe kick |
| 10.7 | Set position_x=50 | Box moves |

**Pass Criteria**:
- ✅ Box geometry correct size
- ✅ Toe kick correct height
- ✅ Position works
- ✅ Wall cabinets have no toe kick

**Visual Verification**:
```
Expected base cabinet at 36" x 34.5" x 24":
- Box starts at Z=4.5" (above toe kick)
- Box height = 30" (34.5 - 4.5)
- Toe kick setback = 3"
```

---

### Test 11: Geometry - Parts Generator

**Component**: `tcs_parts_generator.py`

**Test Steps**:

| Step | Action | Expected Result |
|------|--------|-----------------|
| 11.1 | Connect cut_list_json | Parts parsed |
| 11.2 | Toggle generate | Parts created |
| 11.3 | Check all_parts | Multiple Breps |
| 11.4 | Check part_names | Names match cut list |
| 11.5 | Set explode_distance=5 | Parts separated |
| 11.6 | Check categorized outputs | Parts in correct lists |

**Pass Criteria**:
- ✅ Parts match cut list
- ✅ Positions correct
- ✅ Explode works
- ✅ Categories populated

---

### Test 12: Geometry - Face Frame

**Component**: `tcs_face_frame_geo.py`

**Test Steps**:

| Step | Action | Expected Result |
|------|--------|-----------------|
| 12.1 | Set width=36, height=30 | Accepted |
| 12.2 | Set stile_width=1.5, rail_width=1.5 | Defaults work |
| 12.3 | Toggle generate | Face frame created |
| 12.4 | Check stiles | 1.5" wide, full height |
| 12.5 | Check rails | Between stiles |
| 12.6 | Set opening_count=2 | Center stile appears |
| 12.7 | Check opening_rects | Opening curves created |

**Pass Criteria**:
- ✅ Stiles and rails correct size
- ✅ Openings calculated correctly
- ✅ Multiple openings supported

---

### Test 13: Geometry - Drawer

**Component**: `tcs_drawer_geo.py`

**Test Steps**:

| Step | Action | Expected Result |
|------|--------|-----------------|
| 13.1 | Set opening_width=33, opening_height=6 | Accepted |
| 13.2 | Set cavity_depth=23 | Accepted |
| 13.3 | Set slide_type="blum_tandem" | Deductions set |
| 13.4 | Toggle generate | Drawer created |
| 13.5 | Check drawer_box | Width = 33 - 1.25 = 31.75 |
| 13.6 | Check drawer_face | Overlay applied |
| 13.7 | Change slide_type | Deductions change |

**Pass Criteria**:
- ✅ Blum deductions correct (0.625" per side)
- ✅ Box dimensions accurate
- ✅ Face overlay correct

---

### Test 14: Save to ERP

**Component**: `tcs_save_to_erp.py`

**Test Steps**:

| Step | Action | Expected Result |
|------|--------|-----------------|
| 14.1 | Connect cabinet_id | Ready state |
| 14.2 | Set width=40 | Change prepared |
| 14.3 | Toggle save=False | "Set save=True to push" |
| 14.4 | Toggle save=True | API called |
| 14.5 | Check success | True |
| 14.6 | Verify in ERP | Width updated to 40 |
| 14.7 | Set invalid value | Validation error |

**Pass Criteria**:
- ✅ Requires explicit save toggle
- ✅ Updates ERP
- ✅ Validates data
- ✅ Clears cache after save

---

## Integration Test

### Full Workflow Test

**Steps**:

1. **Connect API** → Verify connected
2. **Select Project** → "GH Test Project"
3. **Navigate to Room** → "Test Kitchen"
4. **Select Location** → "North Wall"
5. **Select Cabinet Run** → "Base Run A"
6. **Select Cabinet** → "B1"
7. **Run Calculation** → Verify dimensions
8. **Generate Box Geometry** → Verify in Rhino
9. **Generate Parts** → Verify all parts
10. **Apply Override** → Width = 42"
11. **Regenerate** → Geometry updates
12. **Save to ERP** → Verify in ERP
13. **Verify in ERP** → Cabinet width = 42"

**Pass Criteria**: All steps complete without errors

---

## Performance Test

### Caching Performance

| Scenario | Expected Time |
|----------|---------------|
| First API call | 1-3 seconds |
| Cached call | < 100ms |
| Geometry generation | < 500ms |
| Full workflow | < 10 seconds |

### Memory Usage

- Monitor Rhino memory during extended sessions
- Cache should not grow unbounded
- Clear caches periodically if needed

---

## Test Report Template

```markdown
# TCS Grasshopper Test Report

**Date**: ___________
**Tester**: ___________
**Version**: ___________

## Test Results

| Test | Status | Notes |
|------|--------|-------|
| 1. API Connection | ⬜ Pass / ⬜ Fail | |
| 2. API Fetch | ⬜ Pass / ⬜ Fail | |
| 3. API Write | ⬜ Pass / ⬜ Fail | |
| 4. Project Selector | ⬜ Pass / ⬜ Fail | |
| 5. Room Navigator | ⬜ Pass / ⬜ Fail | |
| 6. Cabinet List | ⬜ Pass / ⬜ Fail | |
| 7. Cabinet Calculator | ⬜ Pass / ⬜ Fail | |
| 8. Cut List Display | ⬜ Pass / ⬜ Fail | |
| 9. Override Manager | ⬜ Pass / ⬜ Fail | |
| 10. Cabinet Box | ⬜ Pass / ⬜ Fail | |
| 11. Parts Generator | ⬜ Pass / ⬜ Fail | |
| 12. Face Frame | ⬜ Pass / ⬜ Fail | |
| 13. Drawer | ⬜ Pass / ⬜ Fail | |
| 14. Save to ERP | ⬜ Pass / ⬜ Fail | |
| Integration Test | ⬜ Pass / ⬜ Fail | |

## Issues Found

1. _______________
2. _______________

## Sign-off

Tester Signature: _______________
```
