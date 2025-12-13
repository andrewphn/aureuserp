# Layout Optimization Recommendations

## Current State Analysis

### View Page (`/admin/project/projects/{id}`)

**Structure:**
```
┌─────────────────────────────────────────────────────────────────────┐
│ Tabs: View | Edit | Tasks | Milestones                              │
├─────────────────────────────────────────────────────────────────────┤
│ Project Health    │ Total Quoted │ Actual Costs │ Profit Margin     │
│ ⚠️ Needs Attention │ $0           │ $112,869     │ 0.0%              │
├─────────────────────────────────────────────────────────────────────┤
│ Start Date        │ Target Completion │ Production Estimate          │
│ Sep 15, 2025      │ Nov 01, 2025      │ TBD                          │
├─────────────────────────────────────────────────────────────────────┤
│ Project Alerts & Action Items                                       │
│ ⚠️ Project is 40.7 days overdue                                     │
├─────────────────────────────────────────────────────────────────────┤
│ Project Overview (collapsible)     │ Quick Actions                  │
│ • Name, Status, Customer           │ • Export BOM                   │
│                                    │ • Generate Summary              │
│                                    │ • Purchase Requisition          │
├─────────────────────────────────────────────────────────────────────┤
│ Project Breakdown (collapsible)                                     │
│ • Room: Main Kitchen               (description only, no cabinets)  │
│ • Room: Pantry                                                      │
│ • Room: Walk-In Pantry                                              │
├─────────────────────────────────────────────────────────────────────┤
│ Secondary Tabs: Project Data | Assets & Documents | Task Stages     │
└─────────────────────────────────────────────────────────────────────┘
```

**Issues:**
1. ❌ **No cabinet visibility** - Room breakdown shows descriptions but not cabinet details
2. ❌ **No quick pricing view** - Can't see total LF or pricing breakdown on main view
3. ❌ **Too many clicks** - Must click "Project Data" tab then scroll to see cabinets

---

### Edit Page (`/admin/project/projects/{id}/edit`)

**Main Form Structure:**
```
┌─────────────────────────────────────────────────────────────────────┐
│ Project Details                    │ Sidebar                        │
│ • Company, Customer, Project Type  │ • Architectural PDFs           │
│ • Project Number, Name             │ • Project Tags                 │
│ • Description (rich text)          │ • Settings (Visibility, etc.)  │
├─────────────────────────────────────────────────────────────────────┤
│ Project Location                                                    │
│ • Address fields with Google Places                                 │
├─────────────────────────────────────────────────────────────────────┤
│ Timeline & Scope                                                    │
│ • Estimated Linear Feet, Start/End Dates, Project Manager           │
├─────────────────────────────────────────────────────────────────────┤
│ [Save changes] [Cancel]                                             │
└─────────────────────────────────────────────────────────────────────┘
```

**Project Data Tab (Below Main Form):**
```
┌─────────────────────────────────────────────────────────────────────┐
│ Rooms Table (3 rows)                                                │
│ Room Name | Type | Floor | Locations | Cabinets | Actions           │
├─────────────────────────────────────────────────────────────────────┤
│ Room Locations Table (5 rows)                                       │
│ Room | Location Name | Type | Cabinet Runs | Order | Actions        │
├─────────────────────────────────────────────────────────────────────┤
│ Cabinet Runs Table (6 rows)                                         │
│ Room | Location | Run Name | Type | Level | Material | Finish |     │
│ Total LF | Cabinets | Actions                                       │
├─────────────────────────────────────────────────────────────────────┤
│ Cabinets Table (17 rows, paginated)                                 │
│ Cabinet # | Room | Run | Type | Level | Material | Finish |         │
│ Length | Linear Feet | Qty | Total Price | Actions                  │
│                                                                     │
│ Summary: This page Sum $5,106.00 | All cabinets Sum $8,364.00       │
└─────────────────────────────────────────────────────────────────────┘
```

**Issues:**
1. ❌ **Flat tables lose hierarchy** - Cabinets are listed without visual context of their run/location
2. ❌ **Redundant pricing dropdowns** - Level/Material/Finish on BOTH Cabinet Runs AND Cabinets
3. ❌ **Modal-based entry** - "New cabinet" button opens modal (slow workflow)
4. ❌ **Excessive scrolling** - 4 separate tables require scrolling to see full picture
5. ❌ **No inline editing** - Must click "Edit" to modify any cabinet

---

## Optimization Recommendations

### Priority 1: Integrate CabinetSpecBuilder on Edit Page

**What:** Add the tree-based CabinetSpecBuilder component as a new section or tab.

**Why:**
- Hierarchical view (Room → Location → Run → Cabinets)
- Inline cabinet entry we just built
- Smart detection auto-fills dimensions
- Keyboard-first workflow (Tab/Enter/Shift+Enter)

**Implementation:**
```php
// Option A: Add as new section in edit form
Section::make('Cabinet Specification')
    ->schema([
        ViewField::make('cabinet_spec')
            ->view('webkul-project::filament.components.cabinet-spec-builder-wrapper')
            ->viewData(['specData' => $this->record->cabinet_specifications ?? []])
    ])
    ->collapsible()

// Option B: Add as new tab alongside "Project Data"
// "Project Data" | "Cabinet Spec (Tree)" | "Assets & Documents"
```

**Layout After:**
```
┌─────────────────────────────────────────────────────────────────────┐
│ Project Data | Cabinet Spec | Assets & Documents | Task Stages      │
├─────────────────────────────────────────────────────────────────────┤
│ 🏠 Kitchen                                          Total: 18.0 LF  │
│   📍 Sink Wall                                               10.5 LF │
│     🔲 Base Run                                              10.5 LF │
│       ┌─────────────────────────────────────────────────────────┐  │
│       │ Name  │ Width │ Depth │ Height │ Qty │ LF    │ Actions │  │
│       │ SB36  │ 36"   │ 24"   │ 34.5"  │ 1   │ 3.0   │ ✏️ 🗑️   │  │
│       │ B24   │ 24"   │ 24"   │ 34.5"  │ 1   │ 2.0   │ ✏️ 🗑️   │  │
│       │ [B30] │ [30]  │ (24)  │ (34.5) │ [1] │ 2.5   │ ✓ ✗     │  │
│       └─────────────────────────────────────────────────────────┘  │
│                                                    [+ Add Cabinet]  │
│   📍 Island                                                 7.5 LF │
│     ...                                                            │
└─────────────────────────────────────────────────────────────────────┘
```

---

### Priority 2: Add Summary Dashboard to View Page

**What:** Add a cabinet summary section to the View page's "Project Breakdown" area.

**Why:** Users want to see key metrics without navigating to Edit or Project Data.

**Layout:**
```
┌─────────────────────────────────────────────────────────────────────┐
│ Project Breakdown                               Total: 51.25 LF     │
│                                                 Est: $8,364         │
├─────────────────────────────────────────────────────────────────────┤
│ 🏠 Main Kitchen                                 18.0 LF | $3,015    │
│    ├─ Sink Wall: 15.5 LF (4 base, 2 wall)                          │
│    ├─ Fridge Wall: 12.25 LF (0 cabinets TBD)                       │
│    └─ Island: 7.5 LF (3 base)                                      │
├─────────────────────────────────────────────────────────────────────┤
│ 🏠 Pantry                                       11.5 LF | $1,242    │
│    └─ Pantry Wall: 11.5 LF (4 tall)                                │
├─────────────────────────────────────────────────────────────────────┤
│ 🏠 Walk-In Pantry                               11.5 LF | $1,242    │
│    └─ Pantry Wall: 11.5 LF (4 tall)                                │
└─────────────────────────────────────────────────────────────────────┘
```

---

### Priority 3: Remove Redundant Dropdowns

**What:** Move Level/Material/Finish to Cabinet Run level ONLY (not individual cabinets).

**Why:**
- TCS pricing is based on runs, not individual cabinets
- Reduces confusion about where to set pricing
- Simplifies cabinet table

**Before (Cabinets Table):**
```
Cabinet # | Room | Run | Type | Level | Material | Finish | Length | LF | Qty | Price
B1        | Main | Base| Base | L2    | Paint    | Prime  | 36"    | 3  | 1   | $504
```

**After (Cabinets Table):**
```
Cabinet # | Room | Run | Type | Length | Depth | Height | Qty | LF | Actions
B1        | Main | Base| Base | 36"    | 24"   | 34.5"  | 1   | 3  | ✏️ 🗑️
```

---

### Priority 4: Add Quick Entry Button

**What:** Add "Quick Add Cabinets" button that expands inline entry without navigating.

**Location:** On Cabinet Runs table, add expandable inline entry.

**Interaction:**
1. Click row or expand icon on Cabinet Run
2. Inline table appears below that row
3. User can add cabinets directly (no modal)

```
┌─────────────────────────────────────────────────────────────────────┐
│ Cabinet Runs                                                        │
├─────────────────────────────────────────────────────────────────────┤
│ ▼ Main Kitchen | Sink Wall | Base Run | Base | L2 | 10.5 LF | 4    │
│   ┌───────────────────────────────────────────────────────────────┐│
│   │ B1 | 36" | 24" | 34.5" | 1 | 3.0 LF                      ✏️ 🗑️ ││
│   │ B2 | 30" | 24" | 34.5" | 1 | 2.5 LF                      ✏️ 🗑️ ││
│   │ [B3] [24] (24) (34.5) [1] 2.0 LF                         ✓ ✗ ││
│   │ Tab: Next | Enter: Save | Shift+Enter: Save & Add              ││
│   └───────────────────────────────────────────────────────────────┘│
│ ► Main Kitchen | Island | Kitchen Island | Base | -- | 7.5 LF | 3  │
│ ► Pantry | Pantry Wall | Tall Storage | Tall | -- | 11.5 LF | 4    │
└─────────────────────────────────────────────────────────────────────┘
```

---

## Implementation Roadmap

| Phase | Feature | Effort | Impact |
|-------|---------|--------|--------|
| 1 | Add CabinetSpecBuilder tab to Edit page | Low | High |
| 2 | Remove redundant Level/Material/Finish from Cabinets table | Low | Medium |
| 3 | Add expandable cabinet rows to Cabinet Runs table | Medium | High |
| 4 | Add summary dashboard to View page | Medium | Medium |
| 5 | Sync CabinetSpecBuilder data with relation manager tables | Medium | High |

---

## Technical Notes

### Data Synchronization

The project currently has TWO data structures:
1. **Relation Manager Tables** - `project_rooms`, `project_room_locations`, `project_cabinet_runs`, `project_cabinets`
2. **JSON Spec Data** - `cabinet_specifications` JSON column (used by CabinetSpecBuilder)

**Challenge:** Keep both in sync when editing from either interface.

**Solutions:**
1. **Option A: Single Source of Truth (Database)**
   - CabinetSpecBuilder reads from relation tables
   - Saves directly to relation tables
   - No JSON column needed

2. **Option B: Single Source of Truth (JSON)**
   - Use JSON spec as canonical data
   - Sync to relation tables on save for reporting/queries
   - Better for flexible tree structure

3. **Option C: Hybrid (Current)**
   - Both exist independently
   - Add sync methods to keep in parity
   - More complex but allows gradual migration

**Recommendation:** Option A (Database as source) for new projects, but support JSON import for wizard-created projects.

---

## Files to Modify

| File | Change |
|------|--------|
| `EditProject.php` | Add CabinetSpecBuilder section or tab |
| `ViewProject.php` | Add cabinet summary dashboard |
| `CabinetRelationManager.php` | Remove Level/Material/Finish columns |
| `CabinetRunRelationManager.php` | Add expandable cabinet inline entry |
| `CabinetSpecBuilder.php` | Support loading from database relations |

---

## Success Metrics

- **Cabinets added per minute**: Target 10+ (vs current 3-4 with modals)
- **Clicks to add a cabinet**: Target 2 (expand run, type, enter) vs current 5+
- **Time to view full project scope**: Target <5 seconds on View page
