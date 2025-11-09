# Entity Metadata Audit - AnnotationEditor vs Database Schema

## Summary
Comparing which entity fields are:
- ✅ Used in AnnotationEditor forms
- 📊 Available in database tables
- ❌ Missing or mismatched

---

## 1. ROOM Entity (projects_rooms)

### Form Fields Used (AnnotationEditor.php:174-226):
- entity.name
- entity.room_type
- entity.floor_number
- entity.pdf_page_number
- entity.pdf_room_label
- entity.pdf_detail_number
- entity.pdf_notes
- entity.notes

### Database Columns Available:
```
id, project_id, name, cabinet_level, material_category, finish_option,
room_type, floor_number, pdf_page_number, pdf_room_label, pdf_detail_number,
pdf_notes, notes, sort_order, creator_id, created_at, updated_at, deleted_at,
total_linear_feet_tier_1-5, floating_shelves_lf, countertop_sqft, trim_millwork_lf,
material_type, material_upgrade_rate, estimated_cabinet_value,
estimated_additional_products, estimated_finish_value, estimated_project_value,
pricing_tier_override, quoted_price, margin_percentage, labor_hours_estimate,
last_pricing_calculation, pricing_notes
```

### Analysis:
✅ **All form fields exist in database**
📊 **Available but unused in form:**
- cabinet_level, material_category, finish_option
- All linear feet and pricing fields (these are calculated/aggregated from child entities)
- material_type, material_upgrade_rate
- All pricing and estimation fields

---

## 2. LOCATION Entity (projects_room_locations)

### Form Fields Used (AnnotationEditor.php:249-284):
- entity.name
- entity.location_type
- entity.sequence
- entity.elevation_reference
- entity.notes

### Database Columns Available:
```
id, room_id, name, cabinet_level, material_category, finish_option,
location_type, sequence, elevation_reference, notes, sort_order,
creator_id, created_at, updated_at, deleted_at,
material_type, wood_species, door_style, finish_type, paint_color, stain_color,
overall_width_inches, overall_height_inches, overall_depth_inches,
soffit_height_inches, toe_kick_height_inches, cabinet_count, total_linear_feet,
cabinet_type_primary, has_face_frame, face_frame_width_inches,
has_beaded_face_frame, inset_doors, overlay_type, hinge_type, slide_type,
soft_close_doors, soft_close_drawers, has_crown_molding, has_light_rail,
has_decorative_posts, has_appliance_panels, special_features_json,
countertop_type, countertop_sqft, backsplash_sqft, countertop_notes,
requires_electrical, requires_plumbing, electrical_notes, plumbing_notes,
approval_status, approved_at, approved_by_user_id, design_notes,
revision_notes, estimated_value, complexity_tier, elevation_view,
pdf_page_reference
```

### Analysis:
✅ **All form fields exist in database**
📊 **Available but unused in form:**
- All material/finish specifications (wood_species, door_style, finish_type, etc.)
- All dimensional fields (overall_width_inches, overall_height_inches, etc.)
- All hardware options (hinge_type, slide_type, soft_close, etc.)
- All countertop fields
- All approval workflow fields
- elevation_view, pdf_page_reference

**NOTE:** Many of these unused fields likely belong at the cabinet_run level, not location level

---

## 3. CABINET RUN Entity (projects_cabinet_runs)

### Form Fields Used (AnnotationEditor.php:307-355):
- entity.name
- entity.run_type
- entity.total_linear_feet
- entity.start_wall_measurement
- entity.end_wall_measurement
- entity.notes

### Database Columns Available:
```
id, room_location_id, name, cabinet_level, material_category, finish_option,
run_type, total_linear_feet, start_wall_measurement, end_wall_measurement,
notes, sort_order, creator_id, created_at, updated_at, deleted_at,
cabinet_count, material_type, wood_species, finish_type,
sheet_goods_required_sqft, solid_wood_required_bf,
cnc_program_file, cnc_program_generated_at, cnc_machine_time_minutes, cnc_notes,
production_status, material_ordered_at, cnc_started_at, cnc_completed_at,
assembly_started_at, assembly_completed_at, finishing_started_at,
finishing_completed_at, estimated_labor_hours, actual_labor_hours,
lead_craftsman_id, labor_notes,
hardware_kit_json, blum_hinges_total, blum_slides_total, shelf_pins_total,
hardware_kitted, hardware_kitted_at,
qc_passed, qc_inspected_at, qc_inspector_id, qc_notes,
primer_type, primer_coats, topcoat_type, topcoat_coats, sheen_level,
finishing_notes, ready_for_delivery, ready_for_delivery_at,
packaging_boxes_needed, delivery_notes,
material_cost_actual, hardware_cost_actual, labor_cost_actual,
finishing_cost_actual, total_production_cost
```

### Analysis:
✅ **All form fields exist in database**
📊 **Available but unused in form:**
- Material specifications (material_type, wood_species, finish_type)
- Production tracking (all status timestamps, lead_craftsman_id)
- CNC fields (program file, machine time, etc.)
- Hardware tracking (hardware_kit_json, all hardware counts)
- QC fields (qc_passed, qc_inspected_at, etc.)
- Finishing specifications (primer_type, topcoat_type, etc.)
- Delivery tracking (ready_for_delivery, packaging, etc.)
- Cost tracking (all _cost_actual fields)

**NOTE:** These production/manufacturing fields are intentionally not in the annotation editor - they're managed elsewhere in the ERP system

---

## 4. CABINET Entity (projects_cabinet_specifications)

### Form Fields Used (AnnotationEditor.php:378-493):
- entity.cabinet_number
- entity.position_in_run
- entity.length_inches
- entity.width_inches
- entity.depth_inches
- entity.height_inches
- entity.linear_feet
- entity.cabinet_level
- entity.material_category
- entity.finish_option
- entity.unit_price_per_lf

### Database Columns Available:
```
id, order_line_id, project_id, room_id, cabinet_run_id, cabinet_number,
cabinet_level, material_category, finish_option, position_in_run,
wall_position_start_inches, product_variant_id,
length_inches, width_inches, depth_inches, height_inches, linear_feet,
quantity, unit_price_per_lf, total_price, hardware_notes,
custom_modifications, shop_notes, creator_id, created_at, updated_at, deleted_at,
cabinet_code, sequence_in_run, cabinet_type, toe_kick_height, toe_kick_depth,
box_material, box_thickness, joinery_method, has_back_panel,
back_panel_thickness, has_face_frame, face_frame_stile_width,
face_frame_rail_width, face_frame_material, beaded_face_frame,
door_count, door_style, door_mounting, door_sizes_json, has_glass_doors,
glass_type, drawer_count, drawer_sizes_json, dovetail_drawers,
drawer_box_material, drawer_box_thickness, drawer_soft_close,
adjustable_shelf_count, fixed_shelf_count, shelf_thickness, shelf_material,
shelf_pin_holes, hinge_model, hinge_quantity, slide_model, slide_quantity,
specialty_hardware_json, has_pullout, pullout_model, has_lazy_susan,
lazy_susan_model, has_tray_dividers, has_spice_rack,
interior_accessories_json, appliance_panel, appliance_type,
has_microwave_shelf, has_trash_pullout, has_hamper, has_wine_rack,
special_features_json, has_sink_cutout, sink_dimensions_json,
has_cooktop_cutout, has_electrical_cutouts, cutout_notes, complexity_tier,
base_price_per_lf, material_upgrade_per_lf, cabinet_linear_feet,
estimated_cabinet_price, assembly_notes, installation_notes,
requires_scribing, hardware_installation_notes, plywood_sqft,
solid_wood_bf, edge_banding_lf, cnc_cut_at, assembled_at, sanded_at,
finished_at, assembled_by_user_id, qc_passed, qc_issues, qc_inspected_at
```

### Analysis:
✅ **All form fields exist in database**
📊 **Available but unused in form:**
- Construction details (box_material, box_thickness, joinery_method, etc.)
- Face frame specifications (face_frame_stile_width, etc.)
- Door/drawer specifications (door_count, door_style, drawer_count, etc.)
- Shelf specifications (adjustable_shelf_count, fixed_shelf_count, etc.)
- Hardware specifications (hinge_model, slide_model, etc.)
- Accessories (has_pullout, has_lazy_susan, etc.)
- Cutouts (has_sink_cutout, has_cooktop_cutout, etc.)
- Production tracking (cnc_cut_at, assembled_at, etc.)
- Material calculations (plywood_sqft, solid_wood_bf, etc.)

**NOTE:** These are detailed specification fields managed in other parts of the ERP system

---

## 5. PDF PAGE ANNOTATIONS (pdf_page_annotations)

### ✅ ALL FIXED - Full Sync Achieved

**Fields Previously Missing from Response (NOW FIXED):**
- ✅ viewType
- ✅ viewOrientation
- ✅ viewScale
- ✅ inferredPosition
- ✅ verticalZone
- ✅ color
- ✅ roomType
- ✅ measurementWidth (JUST ADDED)
- ✅ measurementHeight (JUST ADDED)

**Current Status:** All editable annotation fields are now properly synced between:
- Form inputs
- Database saves (AnnotationSaveService)
- Response to Alpine.js (AnnotationEditor)

---

## CONCLUSIONS:

### ✅ GOOD NEWS:
1. All form fields have corresponding database columns
2. No orphaned form fields trying to save to non-existent columns
3. All annotation fields now properly synced after recent fixes

### 📊 EXPECTED BEHAVIOR:
1. Entity tables have many more fields than the annotation editor uses
2. This is by design - annotation editor is for quick capture
3. Detailed specifications managed in dedicated ERP modules

### 🎯 RECOMMENDATIONS:
1. Consider adding material/finish fields to cabinet form if users need them during annotation
2. Document which fields are "annotation-time" vs "specification-time"
3. Consider adding measurement_width/height to cabinet_run entity forms as well

### 🔧 RECENT FIXES APPLIED:
1. Added measurement_width and measurement_height to database ✅
2. Updated AnnotationSaveService to save these fields ✅
3. Updated AnnotationEditor response to return these fields ✅
4. Migration applied successfully ✅
