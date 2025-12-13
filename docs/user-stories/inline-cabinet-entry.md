# User Story: Inline Cabinet Entry for Rapid Pricing

## Overview
Replace modal-based cabinet entry with Excel-like inline tables for rapid data entry during pricing workflows.

---

## Current Implementation (Project Creation Wizard)

### Location
- **Step 2: Scope & Budget** → Detailed Cabinet Specification section
- Component: `CabinetSpecBuilder` Livewire component

### How It Works

1. **Navigate to cabinet run** - Expand Room → Location → Run
2. **Click "+ Cabinet"** - New row appears inline (no modal!)
3. **Type cabinet code** - Smart detection auto-fills dimensions
4. **Press Enter** - Save and close
5. **Press Shift+Enter** - Save and add another

### Smart Detection Examples
| Input | Type | Width | Depth | Height |
|-------|------|-------|-------|--------|
| B24 | Base | 24" | 24" | 34.5" |
| SB36 | Sink Base | 36" | 24" | 34.5" |
| W3012 | Wall | 30" | 12" | 30" |
| DB24 | Drawer Base | 24" | 24" | 34.5" |
| T24 | Tall/Pantry | 24" | 24" | 84" |
| V30 | Vanity | 30" | 21" | 34.5" |

### Keyboard Shortcuts
- **Tab** - Move to next field
- **Shift+Tab** - Move to previous field
- **Enter** - Save cabinet
- **Shift+Enter** - Save and add another
- **Escape** - Cancel entry

---

## Expansion Opportunities

### 1. Project Edit Screen
**Location**: `/admin/project/projects/{id}/edit`

**Use Case**: User needs to update cabinet specifications after project creation.

**Implementation**:
- Add "Cabinet Specification" tab or section to project edit form
- Reuse `CabinetSpecBuilder` component with existing spec data
- Same inline entry workflow as creation wizard

**Code Change**:
```php
// In EditProject.php or a dedicated CabinetSpec page
Section::make('Cabinet Specification')
    ->schema([
        ViewField::make('cabinet_spec')
            ->view('webkul-project::filament.components.cabinet-spec-builder-wrapper')
            ->viewData(['specData' => $this->record->cabinet_specifications ?? []])
    ])
```

### 2. Quote/Estimate Builder
**Location**: New dedicated page for quick estimates

**Use Case**: Sales rep at customer site needs to quickly build a rough estimate.

**Workflow**:
1. Create "Quick Estimate" page (no project required)
2. Inline cabinet entry for rapid room-by-room specs
3. Auto-calculate totals with pricing tiers
4. Option to "Convert to Project" when customer approves

**Features**:
- Stripped-down interface (no project metadata)
- Print-friendly estimate output
- Save as draft for later
- Email estimate to customer

### 3. Work Order Detail View
**Location**: `/admin/project/projects/{id}/tasks` or production pages

**Use Case**: Shop floor needs to see cabinet list for a room/run being built.

**Implementation**:
- Read-only inline table view (no editing)
- Highlight current cabinet being worked on
- Show completion status per cabinet
- Link to cut lists and materials

### 4. Template Library
**Location**: New "Cabinet Templates" configuration page

**Use Case**: Save common cabinet configurations for reuse.

**Workflow**:
1. Build a standard kitchen layout once
2. Save as "Standard L-Kitchen" template
3. When creating new project, select template
4. Cabinets auto-populate, user adjusts as needed

**Template Structure**:
```json
{
  "name": "Standard L-Kitchen",
  "rooms": [
    {
      "name": "Kitchen",
      "locations": [
        {
          "name": "Sink Wall",
          "cabinet_level": 2,
          "runs": [
            {
              "name": "Base Run",
              "run_type": "base",
              "cabinets": [
                {"name": "SB36", "length_inches": 36},
                {"name": "B24", "length_inches": 24},
                {"name": "DB24", "length_inches": 24}
              ]
            }
          ]
        }
      ]
    }
  ]
}
```

### 5. Mobile-Friendly Entry
**Use Case**: Field measurements on tablet/phone

**Enhancements**:
- Larger touch targets
- Swipe to delete
- Voice input for cabinet codes ("B twenty-four")
- Camera integration for scanning existing cabinet labels

### 6. Bulk Import from Spreadsheet
**Use Case**: Customer provides cabinet list in Excel format

**Workflow**:
1. Upload CSV/Excel file
2. Map columns to fields (Name, Width, Depth, Height, Qty)
3. Preview parsed data in inline table
4. Confirm import

**CSV Format**:
```csv
Room,Location,Run,Cabinet,Width,Depth,Height,Qty
Kitchen,Sink Wall,Base,SB36,36,24,34.5,1
Kitchen,Sink Wall,Base,B24,24,24,34.5,2
Kitchen,Island,Base,DB36,36,24,34.5,1
```

---

## Technical Architecture

### Reusable Component
The `CabinetSpecBuilder` Livewire component is designed for reuse:

```php
// Mount with existing data
@livewire('cabinet-spec-builder', [
    'specData' => $existingSpecifications,
    'readOnly' => false,  // Future: read-only mode for production views
    'compact' => false,   // Future: compact mode for sidebars
])
```

### Event Communication
Parent forms receive updates via Livewire events:

```javascript
// Parent component listens for updates
Livewire.on('spec-data-updated', (event) => {
    this.$wire.call('handleSpecDataUpdate', event.data);
});
```

### Data Structure
Cabinet specifications are stored as JSON:

```json
{
  "specData": [
    {
      "id": "room_abc123",
      "type": "room",
      "name": "Kitchen",
      "room_type": "kitchen",
      "children": [
        {
          "id": "location_def456",
          "type": "room_location",
          "name": "Sink Wall",
          "cabinet_level": 2,
          "children": [
            {
              "id": "run_ghi789",
              "type": "cabinet_run",
              "name": "Base Run",
              "run_type": "base",
              "children": [
                {
                  "id": "cabinet_jkl012",
                  "type": "cabinet",
                  "name": "SB36",
                  "cabinet_type": "base",
                  "length_inches": 36,
                  "depth_inches": 24,
                  "height_inches": 34.5,
                  "quantity": 1,
                  "linear_feet": 3.0
                }
              ]
            }
          ]
        }
      ]
    }
  ]
}
```

---

## Acceptance Criteria

### For Edit Screen Integration
- [ ] User can access cabinet specifications from project edit page
- [ ] Existing specifications load correctly
- [ ] Inline entry works same as creation wizard
- [ ] Changes auto-save or have explicit save button
- [ ] Totals recalculate on edit

### For Quick Estimate Builder
- [ ] Standalone page accessible from main menu
- [ ] No project required to start
- [ ] Pricing calculates based on cabinet level
- [ ] Export to PDF for customer
- [ ] Convert to project preserves all data

### For Template Library
- [ ] Admin can create/edit templates
- [ ] Templates appear in project creation workflow
- [ ] Selecting template populates cabinet spec
- [ ] User can modify after template applied

---

## Priority Recommendation

| Feature | Priority | Effort | Impact |
|---------|----------|--------|--------|
| Project Edit Screen | P1 | Low | High |
| Quick Estimate Builder | P2 | Medium | High |
| Template Library | P2 | Medium | Medium |
| Bulk Import | P3 | Medium | Medium |
| Mobile Enhancements | P3 | High | Medium |
| Work Order View | P4 | Low | Low |

**Recommendation**: Start with Project Edit Screen integration since the component already exists and it's the most common use case after initial project creation.
