# TCS Cabinet Formulas Reference

> **Source**: Bryan Patton, TCS Woodwork
> **Last Updated**: January 2026
> **Purpose**: Complete reference for all cabinet dimension calculations

---

## 1. Cabinet Box Dimensions

### Box Height
```
Box Height = Cabinet Height - Toe Kick Height
```
**Example**: 34.75" - 4.5" = **30.25"**

### Inside Width
```
Inside Width = Cabinet Width - (2 × Side Panel Thickness)
```
**Example**: 36" - (2 × 0.75") = **34.5"**

### Inside Depth
```
Inside Depth = Cabinet Depth - Back Panel Thickness - Back Wall Gap
```
**Example**: 24" - 0.75" - 0.25" = **23"**

### Side Panel Height

**Standard Cabinet (with stretchers)**:
```
Side Height = Box Height - Stretcher Thickness
```
**Example**: 30.25" - 0.75" = **29.5"**

**Sink Cabinet (no stretchers)**:
```
Side Height = Box Height + Sink Side Extension
```
**Example**: 30.25" + 0.75" = **31"**

### Back Panel Height
```
Back Height = Box Height (full height)
```
**Note**: TCS uses full 3/4" backs to square the box.

---

## 2. Face Frame Dimensions

### Face Frame Opening Width
```
Opening Width = Cabinet Width - (2 × Stile Width)
```
**Example**: 36" - (2 × 1.5") = **33"**

### Face Frame Opening Height
```
Opening Height = Box Height - (2 × Rail Width)
```
**Example**: 30.25" - (2 × 1.5") = **27.25"**

### Stile Length
```
Stile Length = Box Height
```
**Example**: **30.25"**

### Rail Length
```
Rail Length = Opening Width = Cabinet Width - (2 × Stile Width)
```
**Example**: 36" - (2 × 1.5") = **33"**

---

## 3. Drawer Dimensions (Blum TANDEM 563H)

### Drawer Front (Face)
```
Front Width = Opening Width - (2 × Door Gap)
Front Height = Face Height - (2 × Door Gap)
```
**Example**: 33" - 0.25" = **32.75"** wide

### Drawer Box Width
```
Box Width = Opening Width - Blum Side Deduction (0.625")
```
**Example**: 33" - 0.625" = **32.375"**

### Drawer Box Height
```
Box Height (exact) = Face Height - Blum Height Deduction (0.8125")
Box Height (shop) = Round down to nearest 1/2"
```
**Example**: 6" - 0.8125" = 5.1875" → **5"**

### Drawer Box Depth
```
Box Depth = Slide Length + 0.25" (shop practice)
```
**Example**: 18" + 0.25" = **18.25"**

### Drawer Piece Breakdown (Dovetail Construction)
| Piece | Width | Length | Thickness |
|-------|-------|--------|-----------|
| Sides (×2) | Box Height | Box Depth | 1/2" |
| Front/Back (×2) | Box Height | Box Width - 1" | 1/2" |
| Bottom (×1) | Box Width - 0.4375" | Box Depth - 0.75" | 1/4" |

---

## 4. False Front Dimensions

### False Front Panel
```
Panel Width = Opening Width - (2 × Door Gap)
Panel Height = Face Height - (2 × Door Gap)
```

### False Front Backing (Stretcher)
```
Backing Width = Opening Width - (2 × Side Thickness)
Backing Height = Face Height + Overhang (1")
```
**TCS Rule**: False front backing serves dual purpose as stretcher.

---

## 5. Stretcher Calculations

### Stretcher Count
```
Count = 2 (front + back) + Drawer Supports - False Front Backings
```
**Where**:
- Drawer Supports = max(0, Drawer Count - 1)
- FF backings reduce count (they serve as stretchers)

**Example** (2 drawers, 1 false front):
```
Count = 2 + (2-1) - 1 = 2
```

### Stretcher Dimensions
```
Width = Inside Width
Depth = 3" (TCS standard)
Thickness = 0.75"
```

### Sink Cabinet Exception
```
Stretcher Count = 0 (open top for sink/plumbing access)
```

---

## 6. Door Dimensions

### Single Door
```
Door Width = Opening Width - (2 × Door Gap)
Door Height = Face Height - (2 × Door Gap)
```

### Double Doors
```
Door Width = (Opening Width - Center Gap) / 2 - Door Gap
Door Height = Face Height - (2 × Door Gap)
```

---

## 7. Finished End Panel (Edge Cabinet)

### Panel Dimensions
```
Panel Height = Box Height
Panel Depth = Cabinet Depth + Wall Extension (0.5")
```

### Gap from Cabinet
```
Gap = 0.25" (1/4")
```

---

## 8. Constants Reference

### Heights
| Constant | Value | Description |
|----------|-------|-------------|
| Base Cabinet Height | 34.75" | Standard (countertop makes 36") |
| Wall Cabinet 30 | 30" | Standard wall |
| Wall Cabinet 36 | 36" | Tall wall |
| Wall Cabinet 42 | 42" | Extra tall wall |
| Tall Cabinet 84 | 84" | Standard pantry |
| Tall Cabinet 96 | 96" | Floor to ceiling |

### Toe Kick
| Constant | Value |
|----------|-------|
| Height | 4.5" |
| Recess | 3.0" |

### Face Frame
| Constant | Value |
|----------|-------|
| Stile Width | 1.5" |
| Rail Width | 1.5" |
| Door Gap | 0.125" (1/8") |
| Thickness | 0.75" |

### Materials
| Constant | Value |
|----------|-------|
| Box Material | 0.75" (3/4") |
| Back Panel | 0.75" (3/4") |
| Side Panel | 0.75" (3/4") |

### Blum TANDEM 563H
| Constant | Value |
|----------|-------|
| Side Deduction | 0.625" (5/8") |
| Height Deduction | 0.8125" (13/16") |
| Drawer Side Thickness | 0.5" (1/2") |
| Drawer Bottom Thickness | 0.25" (1/4") |

### Gaps & Extensions
| Constant | Value |
|----------|-------|
| Back Wall Gap | 0.25" (1/4") |
| Sink Side Extension | 0.75" |
| Finished End Gap | 0.25" |
| Finished End Wall Extension | 0.5" |
| Component Gap | 0.125" |

---

## 9. Quick Reference Card

### Base Cabinet (36"W × 34.75"H × 24"D)
```
Box Height:      30.25"  (34.75 - 4.5)
Inside Width:    34.5"   (36 - 1.5)
Inside Depth:    23"     (24 - 0.75 - 0.25)
Side Height:     29.5"   (30.25 - 0.75)
Opening Width:   33"     (36 - 3)
Opening Height:  27.25"  (30.25 - 3)
```

### Drawer Box (6" face, 18" slides)
```
Box Width:       32.375" (33 - 0.625)
Box Height:      5"      (6 - 0.8125 → 5)
Box Depth:       18.25"  (18 + 0.25)
```

---

## 10. Database Field Reference

### Cabinet Model Fields
| Field | Type | Formula Source |
|-------|------|----------------|
| `width_inches` | decimal | Input |
| `height_inches` | decimal | Input |
| `depth_inches` | decimal | Input |
| `box_height_inches` | decimal | height - toe_kick |
| `inside_width_inches` | decimal | width - (2 × side) |
| `inside_depth_inches` | decimal | depth - back - gap |
| `toe_kick_height_inches` | decimal | Template or 4.5 |
| `top_construction_type` | string | stretchers/none |

### Drawer Model Fields
| Field | Type | Formula Source |
|-------|------|----------------|
| `face_width_inches` | decimal | opening - gaps |
| `face_height_inches` | decimal | Input |
| `box_width_inches` | decimal | opening - Blum |
| `box_height_inches` | decimal | face - 0.8125 |
| `box_depth_inches` | decimal | slide + 0.25 |
| `slide_length_inches` | int | Input (18/21/24) |

### Face Frame Model Fields
| Field | Type | Formula Source |
|-------|------|----------------|
| `opening_width_inches` | decimal | cabinet - stiles |
| `opening_height_inches` | decimal | box - rails |
| `stile_width_inches` | decimal | Template or 1.5 |
| `rail_width_inches` | decimal | Template or 1.5 |

### Stretcher Model Fields
| Field | Type | Formula Source |
|-------|------|----------------|
| `width_inches` | decimal | inside_width |
| `depth_inches` | decimal | Template or 3.0 |
| `thickness_inches` | decimal | Template or 0.75 |
| `position_type` | string | front/back/support |
| `position_from_front_inches` | decimal | Calculated |
