# TCS Rhino Quick Reference Card

Print this and keep at your workstation.

---

## Cabinet ID Format

```
{PROJECT}-{TYPE}-{SEQ}

Examples:
  SANK-B36-001    Sankaty, Base 36", #1
  FSHIP-W3030-002 Friendship, Wall 30x30, #2
  AUST-T2484-001  Austin, Tall 24x84, #1
```

### Cabinet Type Codes

| Code | Type | Dimensions |
|------|------|------------|
| B24 | Base | 24" wide |
| B30 | Base | 30" wide |
| B36 | Base | 36" wide |
| W3030 | Wall | 30"W x 30"H |
| W3618 | Wall | 36"W x 18"H |
| T2484 | Tall | 24"W x 84"H |
| VAN36 | Vanity | 36" wide |

---

## Material Layers

| Layer | Material | Thickness | Use For |
|-------|----------|-----------|---------|
| `3-4_PreFin` | Prefinished Ply | 3/4" | Cabinet box |
| `3-4_Medex` | Medex MDF | 3/4" | Toe kicks, paint grade |
| `3-4_RiftWO` | Rift White Oak | 3/4" | Drawer faces, ends |
| `1-2_Baltic` | Baltic Birch | 1/2" | Drawer boxes |
| `1-4_Plywood` | Plywood | 1/4" | Backs, bottoms |
| `5-4_Hardwood` | Hardwood | 1" | Face frames |

---

## Required TCS Metadata

**Every part MUST have these User Text attributes:**

| Attribute | Example | Required |
|-----------|---------|----------|
| TCS_PART_ID | SANK-B36-001-LeftSide | YES |
| TCS_CABINET_ID | SANK-B36-001 | YES |
| TCS_PROJECT_CODE | SANK | YES |
| TCS_PART_TYPE | cabinet_box | YES |
| TCS_MATERIAL | 3-4_PreFin | YES |
| TCS_THICKNESS | 0.75 | YES |
| TCS_CUT_WIDTH | 28.75 | Recommended |
| TCS_CUT_LENGTH | 20.25 | Recommended |
| TCS_GRAIN | vertical | Optional |
| TCS_EDGEBAND | F | Optional |

---

## Part Types

| Type | Description | Default Material |
|------|-------------|------------------|
| cabinet_box | Sides, bottom, back | 3-4_PreFin |
| toe_kick | Toe kick panel | 3-4_Medex |
| face_frame | Stiles and rails | 5-4_Hardwood |
| drawer_face | Drawer front | 3-4_RiftWO |
| drawer_box | Drawer sides/front/back | 1-2_Baltic |
| drawer_box_bottom | Drawer bottom | 1-4_Plywood |
| finished_end | Exposed end panel | 3-4_RiftWO |
| stretcher | Top stretcher | 3-4_PreFin |
| shelf | Adjustable shelf | 3-4_PreFin |

---

## Edgebanding Codes

| Code | Edge |
|------|------|
| F | Front |
| T | Top |
| B | Bottom |
| L | Left |
| R | Right |

Examples: `F` (front only), `F,T` (front and top)

---

## Grain Direction

| Value | Description |
|-------|-------------|
| vertical | Grain runs up/down |
| horizontal | Grain runs left/right |
| none | No grain (MDF, plywood bottom) |

---

## Python Commands

```python
# Setup layers (run once)
RunPythonScript tcs_layer_setup.py

# Use template for new cabinet
RunPythonScript tcs_template.py

# Verify metadata on all parts
verify_tcs_metadata()

# Export cut list
export_cut_list()

# Clear and rebuild
rebuild()
```

---

## Rhino Commands

```
# Start MCP server (for ERP connection)
mcp_start

# Set User Text on selected object
Properties > Attribute User Text > Add

# Check object layer
Properties > Object > Layer

# Select all on layer
SelLayer
```

---

## File Naming

```
{PROJECT_CODE}_{Description}.3dm

Examples:
  SANK_Kitchen_Cabinets.3dm
  FSHIP_Master_Bath.3dm
  AUST_Mudroom.3dm
```

---

## Pre-CNC Checklist

Before sending to V-Carve:

- [ ] All parts on TCS_Materials::* layers
- [ ] All parts have TCS_PART_ID
- [ ] All parts have TCS_CABINET_ID
- [ ] All parts have TCS_PROJECT_CODE
- [ ] All parts have TCS_MATERIAL
- [ ] Run verify_tcs_metadata() - no warnings
- [ ] File saved with project code in name

---

## Troubleshooting

**"Object not on correct layer"**
→ Select object, change Layer in Properties

**"Missing TCS metadata"**
→ Run verify_tcs_metadata() to find which parts
→ Add User Text manually or re-run build script

**"MCP not connecting"**
→ In Rhino: `mcp_start`
→ Check server shows "started on localhost:9876"

**"Legacy layer names"**
→ Run tcs_migrate_sankaty.py to convert

---

## Support

- Standards Doc: `/docs/rhino/TCS_RHINO_STANDARDS.md`
- Template: `/resources/rhino/tcs_template.py`
- Layer Setup: `/resources/rhino/tcs_layer_setup.py`
- Migration: `/resources/rhino/tcs_migrate_sankaty.py`
