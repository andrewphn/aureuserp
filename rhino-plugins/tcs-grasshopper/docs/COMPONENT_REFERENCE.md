# TCS Grasshopper - Component Reference

Complete reference for all TCS Grasshopper components with inputs, outputs, and usage examples.

---

## API Components

### TCS API Connect
**File**: `api/tcs_api_connect.py`
**Category**: TCS > API

Establishes connection to TCS ERP API and provides authentication for other components.

#### Inputs
| Name | Type | Required | Description |
|------|------|----------|-------------|
| `api_url` | string | Yes | Base API URL (e.g., `http://aureuserp.test`) |
| `api_token` | string | Yes | Bearer token from ERP admin |
| `test_connection` | bool | No | Trigger connection test |

#### Outputs
| Name | Type | Description |
|------|------|-------------|
| `connected` | bool | Connection status |
| `auth_header` | string | Authorization header for other components |
| `api_base` | string | Validated base URL |
| `status_msg` | string | Connection status message |
| `error` | string | Error message if any |

#### Example Connection
```
[Panel: "http://aureuserp.test"] → api_url
[Panel: "1|abc123..."] → api_token
[Toggle] → test_connection
```

---

### TCS API Fetch
**File**: `api/tcs_api_fetch.py`
**Category**: TCS > API

Fetches data from API endpoints with automatic caching.

#### Inputs
| Name | Type | Required | Description |
|------|------|----------|-------------|
| `api_base` | string | Yes | From TCS API Connect |
| `auth_header` | string | Yes | From TCS API Connect |
| `endpoint` | string | Yes | API endpoint (e.g., `/api/v1/projects`) |
| `params` | string | No | JSON query parameters |
| `refresh` | bool | No | Bypass cache |

#### Outputs
| Name | Type | Description |
|------|------|-------------|
| `data` | string | JSON response data |
| `success` | bool | Request success status |
| `count` | int | Items in response |
| `total` | int | Total items available |
| `cached` | bool | Data from cache |
| `error` | string | Error message |

#### Example Usage
```python
# Fetch all projects
endpoint = "/api/v1/projects"

# Fetch with pagination
endpoint = "/api/v1/projects"
params = '{"per_page": 10, "page": 1}'

# Fetch specific cabinet
endpoint = "/api/v1/cabinets/123"
```

---

### TCS API Write
**File**: `api/tcs_api_write.py`
**Category**: TCS > API

Sends write operations (POST/PUT/PATCH/DELETE) to API.

#### Inputs
| Name | Type | Required | Description |
|------|------|----------|-------------|
| `api_base` | string | Yes | From TCS API Connect |
| `auth_header` | string | Yes | From TCS API Connect |
| `endpoint` | string | Yes | API endpoint |
| `method` | string | Yes | HTTP method (POST/PUT/PATCH/DELETE) |
| `payload` | string | Conditional | JSON payload |
| `execute` | bool | Yes | Safety trigger |

#### Outputs
| Name | Type | Description |
|------|------|-------------|
| `response` | string | JSON response |
| `success` | bool | Operation success |
| `status_code` | int | HTTP status code |
| `created_id` | int | ID of created resource |
| `error` | string | Error message |

#### Example Update
```python
endpoint = "/api/v1/cabinets/123"
method = "PUT"
payload = '{"width_inches": 42, "shop_notes": "Modified in GH"}'
execute = True  # Must be True to send
```

---

## Navigation Components

### TCS Project Selector
**File**: `navigation/tcs_project_selector.py`
**Category**: TCS > Navigation

Provides project dropdown and selection.

#### Inputs
| Name | Type | Required | Description |
|------|------|----------|-------------|
| `api_base` | string | Yes | From TCS API Connect |
| `auth_header` | string | Yes | From TCS API Connect |
| `refresh` | bool | No | Force refresh |
| `selected_index` | int | No | Selected project index |

#### Outputs
| Name | Type | Description |
|------|------|-------------|
| `project_names` | list | Project names for dropdown |
| `project_ids` | list | Project IDs |
| `selected_id` | int | Selected project ID |
| `selected_name` | string | Selected project name |
| `partner_name` | string | Partner/client name |
| `project_data` | string | Full project JSON |
| `error` | string | Error message |

---

### TCS Room Navigator
**File**: `navigation/tcs_room_navigator.py`
**Category**: TCS > Navigation

Cascading navigation: Project → Room → Location → Cabinet Run.

#### Inputs
| Name | Type | Required | Description |
|------|------|----------|-------------|
| `api_base` | string | Yes | From TCS API Connect |
| `auth_header` | string | Yes | From TCS API Connect |
| `project_id` | int | Yes | From Project Selector |
| `selected_room_index` | int | No | Room selection |
| `selected_location_index` | int | No | Location selection |
| `refresh` | bool | No | Force refresh |

#### Outputs
| Name | Type | Description |
|------|------|-------------|
| `room_names` | list | Available rooms |
| `room_ids` | list | Room IDs |
| `selected_room_id` | int | Selected room |
| `selected_room_name` | string | Room name |
| `location_names` | list | Locations in room |
| `location_ids` | list | Location IDs |
| `selected_location_id` | int | Selected location |
| `selected_location_name` | string | Location name |
| `cabinet_run_names` | list | Runs at location |
| `cabinet_run_ids` | list | Run IDs |
| `tree_data` | string | Full tree JSON |
| `error` | string | Error message |

---

### TCS Cabinet List
**File**: `navigation/tcs_cabinet_list.py`
**Category**: TCS > Navigation

Lists cabinets in selected run.

#### Inputs
| Name | Type | Required | Description |
|------|------|----------|-------------|
| `api_base` | string | Yes | From TCS API Connect |
| `auth_header` | string | Yes | From TCS API Connect |
| `cabinet_run_id` | int | Yes | From Room Navigator |
| `selected_index` | int | No | Cabinet selection |
| `refresh` | bool | No | Force refresh |

#### Outputs
| Name | Type | Description |
|------|------|-------------|
| `cabinet_names` | list | Cabinet names |
| `cabinet_ids` | list | Cabinet IDs |
| `selected_id` | int | Selected cabinet ID |
| `selected_name` | string | Cabinet name |
| `cabinet_type` | string | Type (base/wall/tall) |
| `dimensions` | string | Formatted dimensions |
| `width` | float | Width in inches |
| `height` | float | Height in inches |
| `depth` | float | Depth in inches |
| `cabinet_data` | string | Full cabinet JSON |
| `cabinet_count` | int | Total cabinets |
| `error` | string | Error message |

---

### TCS Hierarchy Tree
**File**: `navigation/tcs_hierarchy_tree.py`
**Category**: TCS > Navigation

Full project tree visualization.

#### Inputs
| Name | Type | Required | Description |
|------|------|----------|-------------|
| `api_base` | string | Yes | From TCS API Connect |
| `auth_header` | string | Yes | From TCS API Connect |
| `project_id` | int | Yes | Project to visualize |
| `expand_all` | bool | No | Show all cabinet details |
| `refresh` | bool | No | Force refresh |

#### Outputs
| Name | Type | Description |
|------|------|-------------|
| `tree_text` | string | ASCII tree for display |
| `tree_structure` | string | JSON tree structure |
| `room_count` | int | Total rooms |
| `location_count` | int | Total locations |
| `cabinet_run_count` | int | Total runs |
| `cabinet_count` | int | Total cabinets |
| `total_linear_feet` | float | Sum of all LF |
| `error` | string | Error message |

---

## Calculator Components

### TCS Cabinet Calculator
**File**: `calculator/tcs_cabinet_calc.py`
**Category**: TCS > Calculator

Calculates cabinet dimensions and pricing via API.

#### Inputs
| Name | Type | Required | Description |
|------|------|----------|-------------|
| `api_base` | string | Yes | From TCS API Connect |
| `auth_header` | string | Yes | From TCS API Connect |
| `cabinet_id` | int | Yes | From Cabinet List |
| `override_width` | float | No | Override width (0=use API) |
| `override_height` | float | No | Override height |
| `override_depth` | float | No | Override depth |
| `calculate` | bool | No | Trigger API calculation |

#### Outputs
| Name | Type | Description |
|------|------|-------------|
| `width` | float | Final width |
| `height` | float | Final height |
| `depth` | float | Final depth |
| `linear_feet` | float | Linear feet |
| `box_height` | float | Interior box height |
| `toe_kick_height` | float | Toe kick height |
| `unit_price` | float | Price per LF |
| `total_price` | float | Total price |
| `complexity_score` | float | Complexity score |
| `cut_list_json` | string | Cut list JSON |
| `calculation_json` | string | Full calculation |
| `error` | string | Error message |

#### TCS Constants Used
```python
TOE_KICK_HEIGHT = 4.5"
STRETCHER_DEPTH = 3.0"
FACE_FRAME_STILE = 1.5"
MATERIAL_THICKNESS = 0.75"
BACK_PANEL_THICKNESS = 0.25"
```

---

### TCS Cut List Display
**File**: `calculator/tcs_cut_list.py`
**Category**: TCS > Calculator

Parses and displays cut list data.

#### Inputs
| Name | Type | Required | Description |
|------|------|----------|-------------|
| `cut_list_json` | string | Conditional | From Calculator |
| `calculation_json` | string | Conditional | Alternative source |
| `filter_type` | string | No | Part type filter |

#### Outputs
| Name | Type | Description |
|------|------|-------------|
| `part_names` | list | Part names |
| `part_widths` | list | Widths |
| `part_heights` | list | Heights |
| `part_quantities` | list | Quantities |
| `part_types` | list | Part types |
| `part_materials` | list | Materials |
| `table_text` | string | Formatted table |
| `total_parts` | int | Part count |
| `board_feet` | float | Estimated BF |
| `cut_list_data` | string | Parsed JSON |
| `error` | string | Error message |

#### Part Types
- `cabinet_box`: Sides, bottom, back
- `face_frame`: Stiles and rails
- `stretcher`: Front/back stretchers
- `drawer_box`: Drawer components
- `drawer_face`: Drawer fronts
- `door`: Door panels
- `shelf`: Adjustable shelves

---

### TCS Override Manager
**File**: `calculator/tcs_override_manager.py`
**Category**: TCS > Calculator

Manages dimension and pricing overrides with document persistence.

#### Inputs
| Name | Type | Required | Description |
|------|------|----------|-------------|
| `cabinet_id` | int | Yes | Cabinet to manage |
| `override_width` | float | No | Width override |
| `override_height` | float | No | Height override |
| `override_depth` | float | No | Depth override |
| `override_price_lf` | float | No | Price override |
| `shelf_qty_override` | int | No | Shelf count |
| `drawer_qty_override` | int | No | Drawer count |
| `apply` | bool | No | Save to document |
| `reset` | bool | No | Clear overrides |

#### Outputs
| Name | Type | Description |
|------|------|-------------|
| `active_overrides` | string | Current overrides JSON |
| `override_count` | int | Number of overrides |
| `width_overridden` | bool | Width has override |
| `height_overridden` | bool | Height has override |
| `depth_overridden` | bool | Depth has override |
| `price_overridden` | bool | Price has override |
| `effective_width` | float | Width to use |
| `effective_height` | float | Height to use |
| `effective_depth` | float | Depth to use |
| `effective_price_lf` | float | Price to use |
| `status_msg` | string | Status message |

#### Storage
Overrides stored in Rhino document user text:
```
Key: TCS_OVERRIDES_{cabinet_id}
Value: JSON {"width": 42, "timestamp": "..."}
```

---

### TCS Pricing Calculator
**File**: `calculator/tcs_pricing.py`
**Category**: TCS > Calculator

Calculates cabinet pricing with TCS tiers.

#### Inputs
| Name | Type | Required | Description |
|------|------|----------|-------------|
| `width` | float | Yes | Cabinet width |
| `height` | float | No | Cabinet height |
| `depth` | float | No | Cabinet depth |
| `cabinet_type` | string | No | Type for pricing tier |
| `complexity_score` | float | No | 0-10 complexity |
| `price_per_lf_override` | float | No | Override price |
| `material_multiplier` | float | No | Material factor |
| `api_pricing_json` | string | No | API pricing data |

#### Outputs
| Name | Type | Description |
|------|------|-------------|
| `linear_feet` | float | Linear feet |
| `base_price_lf` | float | Base price/LF |
| `effective_price_lf` | float | Adjusted price/LF |
| `subtotal` | float | Before adjustments |
| `complexity_adjustment` | float | Complexity add-on |
| `material_adjustment` | float | Material add-on |
| `total_price` | float | Final price |
| `pricing_breakdown` | string | Formatted breakdown |
| `pricing_json` | string | Full pricing JSON |

#### Default Pricing Tiers
| Type | $/LF |
|------|------|
| Base | $125 |
| Wall | $95 |
| Tall | $150 |
| Vanity | $110 |
| Drawer Base | $140 |
| Corner | $175 |

---

## Geometry Components

### TCS Cabinet Box
**File**: `geometry/tcs_cabinet_box.py`
**Category**: TCS > Geometry

Generates cabinet box envelope geometry.

#### Inputs
| Name | Type | Required | Description |
|------|------|----------|-------------|
| `width` | float | Yes | Width in inches |
| `height` | float | Yes | Height in inches |
| `depth` | float | Yes | Depth in inches |
| `cabinet_type` | string | No | base/wall/tall |
| `position_x` | float | No | X position |
| `position_y` | float | No | Y position |
| `position_z` | float | No | Z position |
| `include_toe_kick` | bool | No | Include toe kick |
| `generate` | bool | Yes | Trigger generation |

#### Outputs
| Name | Type | Description |
|------|------|-------------|
| `box_geometry` | Brep | Cabinet box |
| `toe_kick_geometry` | Brep | Toe kick |
| `interior_box` | Brep | Interior cavity |
| `origin_point` | Point3d | Origin |
| `bounding_box` | Box | Bounding box |
| `dimensions_text` | string | Formatted dims |
| `envelope_data` | string | Envelope JSON |

#### Coordinate System
```
Origin: Bottom-left-front corner
X: Width (left to right)
Y: Depth (front to back)
Z: Height (bottom to top)
```

---

### TCS Parts Generator
**File**: `geometry/tcs_parts_generator.py`
**Category**: TCS > Geometry

Generates individual cabinet parts from cut list.

#### Inputs
| Name | Type | Required | Description |
|------|------|----------|-------------|
| `cut_list_json` | string | Conditional | Cut list JSON |
| `calculation_json` | string | Conditional | Calculation JSON |
| `position_x` | float | No | X position |
| `position_y` | float | No | Y position |
| `position_z` | float | No | Z position |
| `cabinet_type` | string | No | Cabinet type |
| `explode_distance` | float | No | Explode parts |
| `filter_type` | string | No | Part filter |
| `generate` | bool | Yes | Trigger generation |

#### Outputs
| Name | Type | Description |
|------|------|-------------|
| `all_parts` | list[Brep] | All parts |
| `part_names` | list | Part names |
| `part_colors` | list | RGB colors |
| `cabinet_box_parts` | list | Box parts |
| `face_frame_parts` | list | Face frame |
| `drawer_parts` | list | Drawer parts |
| `parts_data` | string | Parts JSON |
| `total_parts` | int | Part count |

#### Part Colors
| Type | RGB |
|------|-----|
| Cabinet Box | (139, 90, 43) |
| Face Frame | (210, 180, 140) |
| Stretcher | (160, 82, 45) |
| Drawer Box | (255, 228, 181) |
| Drawer Face | (245, 222, 179) |

---

### TCS Face Frame Geometry
**File**: `geometry/tcs_face_frame_geo.py`
**Category**: TCS > Geometry

Generates face frame geometry.

#### Inputs
| Name | Type | Required | Description |
|------|------|----------|-------------|
| `width` | float | Yes | Cabinet width |
| `height` | float | Yes | Box height (minus toe kick) |
| `cabinet_type` | string | No | Cabinet type |
| `stile_width` | float | No | Stile width (default 1.5") |
| `rail_width` | float | No | Rail width (default 1.5") |
| `opening_count` | int | No | Number of openings |
| `position_x/y/z` | float | No | Position |
| `generate` | bool | Yes | Trigger generation |

#### Outputs
| Name | Type | Description |
|------|------|-------------|
| `face_frame` | Brep | Combined frame |
| `left_stile` | Brep | Left stile |
| `right_stile` | Brep | Right stile |
| `top_rail` | Brep | Top rail |
| `bottom_rail` | Brep | Bottom rail |
| `center_stile` | Brep | Center (if double) |
| `mullion_rails` | list | Any mullions |
| `opening_rects` | list | Opening curves |
| `face_frame_data` | string | Frame JSON |

---

### TCS Drawer Geometry
**File**: `geometry/tcs_drawer_geo.py`
**Category**: TCS > Geometry

Generates drawer box and face geometry.

#### Inputs
| Name | Type | Required | Description |
|------|------|----------|-------------|
| `opening_width` | float | Yes | Face frame opening width |
| `opening_height` | float | Yes | Opening height |
| `cavity_depth` | float | Yes | Interior depth |
| `drawer_height` | float | No | Box height (default 4") |
| `slide_type` | string | No | Slide specification |
| `overlay` | float | No | Face overlay (default 0.5") |
| `reveal` | float | No | Gap between faces (default 0.125") |
| `position_x/y/z` | float | No | Position |
| `generate` | bool | Yes | Trigger generation |

#### Outputs
| Name | Type | Description |
|------|------|-------------|
| `drawer_box` | Brep | Drawer box |
| `drawer_face` | Brep | Drawer front |
| `drawer_assembly` | list | All parts |
| `box_parts` | list | Box components |
| `drawer_dimensions` | string | Formatted dims |
| `drawer_data` | string | Drawer JSON |

#### Slide Specifications
| Type | Side Deduction | Height Deduction |
|------|---------------|------------------|
| blum_tandem | 0.625" | 0.8125" |
| side_mount | 0.5" | 0.5" |
| undermount | 0.5" | 0.75" |

---

## UI Components

### TCS Cabinet Panel
**File**: `ui/tcs_cabinet_panel.py`
**Category**: TCS > UI

Generates data for Human UI panel.

#### Inputs
| Name | Type | Required | Description |
|------|------|----------|-------------|
| `cabinet_data` | string | No | Cabinet JSON |
| `calculation_json` | string | No | Calculation JSON |
| `cut_list_json` | string | No | Cut list JSON |
| `pricing_json` | string | No | Pricing JSON |
| `override_json` | string | No | Override JSON |
| `show_advanced` | bool | No | Show advanced options |

#### Outputs
| Name | Type | Description |
|------|------|-------------|
| `panel_title` | string | Panel title |
| `dimension_sliders` | list | Slider configs |
| `dimension_values` | list | Current values |
| `dimension_overridden` | list | Override flags |
| `cut_list_table` | list | Table rows |
| `pricing_text` | string | Pricing display |
| `status_text` | string | Status message |
| `button_labels` | list | Button labels |
| `panel_data` | string | Full panel JSON |

---

### TCS Save to ERP
**File**: `ui/tcs_save_to_erp.py`
**Category**: TCS > UI

Saves cabinet changes to ERP.

#### Inputs
| Name | Type | Required | Description |
|------|------|----------|-------------|
| `api_base` | string | Yes | From API Connect |
| `auth_header` | string | Yes | From API Connect |
| `cabinet_id` | int | Yes | Cabinet to update |
| `width` | float | No | Width to save |
| `height` | float | No | Height to save |
| `depth` | float | No | Depth to save |
| `price_per_lf` | float | No | Price to save |
| `notes` | string | No | Shop notes |
| `overrides_json` | string | No | Full overrides |
| `save` | bool | Yes | Trigger save |

#### Outputs
| Name | Type | Description |
|------|------|-------------|
| `success` | bool | Save success |
| `response` | string | API response |
| `saved_fields` | list | Fields saved |
| `status_msg` | string | Status message |
| `error` | string | Error message |
