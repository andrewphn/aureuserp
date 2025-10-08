# Multi-Pass PDF Annotation System - Product Requirements Document

## Executive Summary

This system enables TCS Woodwork to annotate architectural PDFs with visual boxes that automatically create and link to hierarchical project data (Rooms → Room Locations → Cabinet Runs → Cabinets). The annotation workflow supports multiple passes through the same document, PDF versioning, full editing capabilities, and comprehensive activity tracking via the Chatter system.

## Problem Statement

Architects provide multi-page PDF plans (floor plans, elevations, details) that contain the information needed to price and build custom cabinet projects. Currently, data entry is manual and disconnected from the visual context. Users need to:

1. Draw boxes on PDFs to identify rooms, cabinet runs, and individual cabinets
2. Have those annotations automatically create database records
3. Link annotations hierarchically (Room → Location → Run → Cabinet)
4. Handle document revisions without losing previous work
5. Edit and update annotations as designs change
6. Track all changes in the Chatter activity feed

## User Workflow

### First Pass - Floor Plans (Creating Rooms)

**Page Type**: Floor Plan

**User Actions**:
1. Opens PDF annotation modal for floor plan page
2. Selects "Room" annotation type
3. Chooses room type from palette (Kitchen, Bathroom, Pantry, etc.)
4. Draws box around room area on PDF
5. Optionally adds label (e.g., "Master Kitchen")
6. Clicks Save

**System Behavior**:
- Creates `Room` record in `projects_rooms` table
- Links annotation to room via `room_id` foreign key
- Logs activity in Chatter: "Room created: Master Kitchen (Kitchen)"
- Stores normalized coordinates (0-1 range) for zoom independence

**Optional**: Create Room Locations within rooms (e.g., "North Wall", "Island")

### Second Pass - Elevations (Creating Cabinet Runs)

**Page Type**: Elevation

**User Actions**:
1. Opens PDF annotation modal for elevation page
2. Selects "Cabinet Run" annotation type
3. **Context dropdown**: Selects existing Room (e.g., "Master Kitchen")
4. **Context dropdown**: Selects existing Room Location (e.g., "North Wall")
5. Chooses run type (Base, Wall, Tall, Specialty)
6. Draws box around continuous cabinet run
7. Adds label (e.g., "Base Run 1")
8. Clicks Save

**System Behavior**:
- Creates `CabinetRun` record linked to selected RoomLocation
- Links annotation to cabinet run via `cabinet_run_id`
- Logs activity: "Cabinet Run created: Base Run 1 in Master Kitchen - North Wall"

**Then**: Create individual cabinets within the run

**User Actions** (same page):
1. Selects "Cabinet" annotation type
2. **Context dropdown**: Selects existing Cabinet Run (e.g., "Base Run 1")
3. Draws smaller boxes around individual cabinets
4. Adds cabinet numbers/labels
5. Clicks Save

**System Behavior**:
- Creates `CabinetSpecification` records linked to selected CabinetRun
- Links annotations to cabinets via `cabinet_specification_id`
- Auto-assigns position_in_run based on x-coordinate
- Logs activity for each cabinet created

### Third Pass - Details (Adding Dimensions/Hardware)

**Page Type**: Detail

**User Actions**:
1. Opens PDF annotation modal for detail page
2. Selects "Dimension" or "Hardware" annotation type
3. **Context dropdown**: Selects existing Cabinet from any run
4. Adds dimension values (length, width, depth, height)
5. Adds hardware notes (hinges, pulls, slides)
6. Clicks Save

**System Behavior**:
- Updates existing `CabinetSpecification` record
- Stores dimension data and hardware notes
- Logs activity: "Cabinet dimensions updated: 36\"W x 24\"D x 30\"H"

## Hierarchical Data Model

```
Project
└── PDF Document (architectural plans)
    ├── Version 1 (original upload)
    ├── Version 2 (Rev A - architect update)
    └── Version 3 (Rev B - latest)
        └── PDF Pages (1-15)
            └── Page Annotations (visual boxes)

Created Entities:

Room (Floor Plan)
  ├── name: "Master Kitchen"
  ├── room_type: "kitchen"
  └── RoomLocation (Floor Plan - optional)
      ├── name: "North Wall"
      ├── location_type: "wall"
      └── CabinetRun (Elevation)
          ├── name: "Base Run 1"
          ├── run_type: "base"
          └── CabinetSpecification (Elevation/Detail)
              ├── cabinet_number: "BC-1"
              ├── length_inches: 36
              ├── width_inches: 24
              ├── depth_inches: 24
              ├── height_inches: 30
              ├── linear_feet: 3.0
              └── hardware_notes: "Soft-close hinges, satin nickel pulls"
```

## Database Schema Changes

### ✅ Completed: PDF Document Versioning

**Migration**: `2025_10_08_180053_add_versioning_to_pdf_documents_table.php`

**New fields in `pdf_documents`**:
- `version_number` (integer, default 1) - Incremental version number
- `parent_document_id` (foreign key) - Links to previous version
- `is_current_version` (boolean, default true) - Flag for latest version
- `version_created_at` (timestamp) - When version was created
- `version_notes` (varchar 500) - Architect's revision notes

**Indexes**:
- `idx_current_version` on (module_type, module_id, is_current_version)
- `parent_document_id` index

### Existing: Annotation Storage

**Table**: `pdf_page_annotations`

**Key fields**:
- `annotation_type` - room, room_location, cabinet_run, cabinet, dimension, hardware
- `room_id` - Links to `projects_rooms.id`
- `cabinet_run_id` - Links to `projects_cabinet_runs.id`
- `cabinet_specification_id` - Links to `projects_cabinet_specifications.id`
- `x, y, width, height` - Normalized coordinates (0-1)
- `room_type` - Kitchen, Bathroom, etc.
- `color` - Hex color for visual differentiation
- `metadata` - JSON for flexible data storage

## Chatter Integration

### ✅ Completed: Model Trait Implementation

All models now use `HasChatter` and `HasLogActivity` traits:
- `PdfPageAnnotation`
- `Room`
- `RoomLocation`
- `CabinetRun`
- `CabinetSpecification`

### Activity Logging

**Automatically logged events**:
- Annotation created/updated/deleted/moved
- Room created from annotation
- RoomLocation created from annotation
- CabinetRun created from annotation
- Cabinet created from annotation
- Cabinet specs updated (dimensions, hardware)
- Entity reassigned to different parent

**Log attributes configured** for each model to track meaningful changes:
- Room: name, room_type, floor_number, pdf_page_number
- CabinetRun: name, run_type, total_linear_feet, measurements
- Cabinet: dimensions, quantities, pricing, notes

**Messages & Comments**:
- Users can comment on any annotation
- @mention team members for collaboration
- Attach photos or spec sheets to annotations
- Reply to comments creating threaded discussions

**Followers**:
- Follow specific Rooms or CabinetRuns
- Receive notifications when annotations updated
- Automatically follow entities you create

## Context-Aware Annotation Modal

### Modal States

**1. Floor Plan Mode**
- **Annotation Type**: Room or Room Location
- **Dropdowns**: None (creating new entities)
- **Fields**: Room type palette, label input, notes textarea
- **Action**: Creates Room record + annotation

**2. Elevation Mode - Cabinet Run**
- **Annotation Type**: Cabinet Run
- **Dropdowns**:
  - Select Room (required) → Filters from `projects_rooms` where project_id = current_project
  - Select Room Location (required) → Filters by selected Room
- **Fields**: Run type (Base/Wall/Tall), label input, notes
- **Action**: Creates CabinetRun record + annotation

**3. Elevation Mode - Cabinet**
- **Annotation Type**: Cabinet
- **Dropdowns**:
  - Select Cabinet Run (required) → Shows all runs in current project
- **Fields**: Cabinet number, dimensions (optional at this stage), notes
- **Action**: Creates CabinetSpecification record + annotation

**4. Detail Mode - Dimensions/Hardware**
- **Annotation Type**: Dimension or Hardware
- **Dropdowns**:
  - Select Cabinet (required) → Shows all cabinets across all runs
- **Fields**:
  - Dimensions: Length, Width, Depth, Height (inches)
  - Hardware: Hardware notes textarea
  - Modifications: Custom modifications textarea
  - Shop Notes: Shop notes textarea
- **Action**: Updates existing CabinetSpecification record

### Create vs. Link Modes

**All dropdown selectors include**:
- "Create New" option at top
- Inline creation form when "Create New" selected
- List of existing entities to link to

**Example - Cabinet Run Dropdown**:
```
┌─────────────────────────────────────┐
│ Select Cabinet Run                  │
├─────────────────────────────────────┤
│ + Create New Cabinet Run           │  ← Click to show inline form
├─────────────────────────────────────┤
│ Base Run 1 (Master Kitchen)        │
│ Wall Run 1 (Master Kitchen)        │
│ Base Run 1 (Guest Bathroom)        │
└─────────────────────────────────────┘
```

**Inline Creation Form** (when "+ Create New" clicked):
```
┌─────────────────────────────────────┐
│ New Cabinet Run                     │
├─────────────────────────────────────┤
│ Room:          [Master Kitchen ▼]   │
│ Location:      [North Wall ▼]       │
│ Run Name:      [____________]       │
│ Run Type:      [Base ▼]             │
│ Notes:         [____________]       │
│                                     │
│      [Cancel]  [Create & Link]      │
└─────────────────────────────────────┘
```

## AnnotationEntityService

### ✅ Completed: Service Layer Implementation

**File**: `/app/Services/AnnotationEntityService.php`

**Key Methods**:

1. **createOrLinkEntityFromAnnotation($annotation, $context)**
   - Routes to correct method based on annotation_type
   - Handles transactions and error logging

2. **createRoom($annotation, $context)**
   - Creates Room record
   - Links annotation via room_id
   - Logs activity in Chatter

3. **createRoomLocation($annotation, $context)**
   - Requires room_id in context
   - Creates RoomLocation record
   - Links annotation

4. **createCabinetRun($annotation, $context)**
   - Requires room_location_id in context
   - Creates CabinetRun record
   - Links annotation via cabinet_run_id

5. **createCabinet($annotation, $context)**
   - Requires cabinet_run_id and project_id in context
   - Creates CabinetSpecification record
   - Auto-calculates linear_feet from length_inches
   - Links annotation via cabinet_specification_id

6. **updateCabinetSpecs($cabinetId, $data)**
   - Updates existing cabinet with dimensions/hardware
   - Supports partial updates
   - Logs changes via HasLogActivity trait

**Usage Example**:
```php
$service = new AnnotationEntityService();

// Floor Plan: Create Room
$result = $service->createOrLinkEntityFromAnnotation($annotation, [
    'project_id' => 42,
    'page_number' => 3,
]);

// Elevation: Create Cabinet Run
$result = $service->createOrLinkEntityFromAnnotation($annotation, [
    'room_location_id' => 15,
    'run_type' => 'base',
]);

// Detail: Update Cabinet Dimensions
$result = $service->updateCabinetSpecs(123, [
    'length_inches' => 36,
    'width_inches' => 24,
    'depth_inches' => 24,
    'height_inches' => 30,
    'hardware_notes' => 'Soft-close hinges, satin nickel pulls',
]);
```

## PDF Versioning Workflow

### Scenario: Architect Sends Revision

1. **User uploads new PDF** (Rev B) for existing project
2. **System detects** previous version exists via (module_type, module_id)
3. **Versioning prompt appears**:
   ```
   ┌────────────────────────────────────────────────┐
   │ New Revision Detected                           │
   ├────────────────────────────────────────────────┤
   │ Current version: Rev A (uploaded 2025-09-15)    │
   │                                                  │
   │ Revision notes:                                  │
   │ [____________________________________]          │
   │                                                  │
   │ ☑ Copy annotations from previous version        │
   │ ☐ Start with blank annotations                  │
   │                                                  │
   │      [Cancel]  [Upload as Rev B]                │
   └────────────────────────────────────────────────┘
   ```

4. **User selects** "Copy annotations from previous version"

5. **System creates**:
   - New PdfDocument record with version_number = 2
   - parent_document_id = previous version's ID
   - is_current_version = true
   - Sets previous version's is_current_version = false

6. **System copies annotations**:
   - Duplicates all annotations from parent version
   - Preserves entity links (room_id, cabinet_run_id, etc.)
   - User can then adjust positions if layout changed

### Annotation Migration Logic

```php
// When new version uploaded with "copy annotations" option
public function migrateAnnotationsToNewVersion($oldDocumentId, $newDocumentId)
{
    $oldPages = PdfPage::where('pdf_document_id', $oldDocumentId)->get();
    $newPages = PdfPage::where('pdf_document_id', $newDocumentId)->get();

    foreach ($oldPages as $index => $oldPage) {
        $newPage = $newPages[$index] ?? null;
        if (!$newPage) continue;

        $annotations = $oldPage->annotations()->get();

        foreach ($annotations as $annotation) {
            PdfPageAnnotation::create([
                'pdf_page_id' => $newPage->id,
                'annotation_type' => $annotation->annotation_type,
                'label' => $annotation->label,
                'x' => $annotation->x,
                'y' => $annotation->y,
                'width' => $annotation->width,
                'height' => $annotation->height,
                'room_type' => $annotation->room_type,
                'color' => $annotation->color,
                // Preserve entity links
                'room_id' => $annotation->room_id,
                'cabinet_run_id' => $annotation->cabinet_run_id,
                'cabinet_specification_id' => $annotation->cabinet_specification_id,
                'notes' => '[Migrated from Rev A] ' . $annotation->notes,
                'created_by' => Auth::id(),
            ]);
        }
    }
}
```

## Annotation Editing Features

### Edit Mode UI

**Selection**:
- Click annotation box → Shows blue selection border
- Resize handles appear at corners and edges
- Context menu shows: Edit Details | Delete | Cancel

**Resize**:
- Drag corner handles → Proportional resize
- Drag edge handles → Single dimension resize
- Updates annotation x, y, width, height in database
- Logs activity: "Annotation resized on page 5"

**Move**:
- Drag annotation box to new position
- Snap to grid (optional setting)
- Updates x, y coordinates
- Logs activity: "Annotation moved on page 5"

**Edit Details**:
- Opens inline form with current annotation data
- Can change linked entity (e.g., switch from Room A to Room B)
- Can update label, notes, room type
- Logs activity: "Annotation updated: room type changed from Kitchen to Pantry"

**Delete**:
- Shows confirmation: "Delete this annotation? The linked Room will remain."
- Soft deletes annotation (deleted_at timestamp)
- Does NOT delete linked entity (Room/Cabinet/etc. stays)
- Logs activity: "Annotation deleted from page 5"

**Undo/Redo**:
- Browser maintains state stack
- Ctrl+Z / Cmd+Z to undo
- Ctrl+Shift+Z / Cmd+Shift+Z to redo
- Clears on page reload or manual save

## API Enhancements

### Enhanced Save Endpoint

**POST** `/api/pdf/page/{pdfPageId}/annotations`

**Request Body**:
```json
{
  "annotations": [
    {
      "annotation_type": "cabinet_run",
      "x": 0.25,
      "y": 0.30,
      "width": 0.40,
      "height": 0.15,
      "text": "Base Run 1",
      "context": {
        "room_id": 42,
        "room_location_id": 15,
        "run_type": "base"
      }
    }
  ],
  "create_entities": true  // Flag to auto-create entities
}
```

**Response**:
```json
{
  "success": true,
  "message": "Annotations saved successfully",
  "count": 1,
  "annotations": [
    {
      "id": 123,
      "annotation_type": "cabinet_run",
      "cabinet_run_id": 456,  // Newly created entity ID
      "created_entity": {
        "type": "cabinet_run",
        "id": 456,
        "name": "Base Run 1",
        "room": "Master Kitchen",
        "location": "North Wall"
      }
    }
  ]
}
```

### New Endpoints Needed

**GET** `/api/pdf/page/{pdfPageId}/context`
- Returns available entities for dropdowns
- Returns: rooms, room_locations, cabinet_runs, cabinets
- Filtered by project_id

**PUT** `/api/pdf/annotation/{annotationId}/reassign`
- Reassign annotation to different entity
- Body: `{ "entity_type": "cabinet_run", "entity_id": 789 }`
- Updates foreign key and logs activity

**POST** `/api/pdf/document/{documentId}/version`
- Upload new version of PDF
- Body: `{ "file": blob, "version_notes": "Rev B - kitchen layout changed", "copy_annotations": true }`
- Creates new document version and optionally migrates annotations

## Success Metrics

### Phase 1 (Current Implementation)
- ✅ Chatter activity logging for all entity changes
- ✅ PDF document versioning schema in place
- ✅ AnnotationEntityService for creating entities from annotations
- ⏸️ Context-aware annotation modal (pending frontend work)

### Phase 2 (Next Implementation)
- ⏸️ Annotation editing (select/resize/move/delete)
- ⏸️ Version migration workflow
- ⏸️ Enhanced API endpoints with entity creation
- ⏸️ Frontend dropdown components for entity selection

### User Acceptance Criteria
1. User can draw box on floor plan → Room record created
2. User can draw box on elevation → CabinetRun created (after selecting Room + Location)
3. User can draw box on elevation → Cabinet created (after selecting CabinetRun)
4. User can add dimensions on detail page → Cabinet specs updated
5. All activities appear in Chatter feed with @mention support
6. User can upload Rev B → Annotations copy from Rev A
7. User can adjust annotation positions after migration
8. User can edit/delete annotations without losing entity data

## Technical Implementation Notes

### Frontend State Management

**Annotation Modal State**:
```javascript
const annotationState = {
  mode: 'create', // 'create' | 'edit'
  annotationType: 'room', // 'room' | 'room_location' | 'cabinet_run' | 'cabinet' | 'dimension'
  selectedAnnotationId: null,
  context: {
    projectId: 42,
    pageNumber: 5,
    selectedRoomId: null,
    selectedRoomLocationId: null,
    selectedCabinetRunId: null,
    selectedCabinetId: null,
  },
  availableEntities: {
    rooms: [],
    roomLocations: [],
    cabinetRuns: [],
    cabinets: [],
  },
  currentAnnotations: [], // Loaded from API
  pendingChanges: [], // Not yet saved
}
```

### Canvas Drawing Logic

```javascript
// Store coordinates as percentages for zoom independence
function normalizeCoordinates(canvas, rect) {
  return {
    x: rect.x / canvas.width,
    y: rect.y / canvas.height,
    width: rect.width / canvas.width,
    height: rect.height / canvas.height,
  };
}

// Restore to absolute pixels for rendering
function denormalizeCoordinates(canvas, annotation) {
  return {
    x: annotation.x * canvas.width,
    y: annotation.y * canvas.height,
    width: annotation.width * canvas.width,
    height: annotation.height * canvas.height,
  };
}
```

## Future Enhancements

### AI-Assisted Annotation
- OCR text extraction from PDF labels
- Auto-detect room boundaries
- Suggest cabinet positions based on elevation lines

### Bulk Operations
- Select multiple annotations → Batch reassign to different run
- Copy annotations from one page to another
- Template-based annotation (common room layouts)

### Integration
- Export annotated PDF with entity data overlay
- Sync with production scheduling system
- Generate shop drawings from cabinet specs

### Reporting
- Annotation coverage report (% of pages annotated)
- Entity creation timeline
- Chatter activity summary per project

---

**Document Version**: 1.0
**Last Updated**: 2025-10-08
**Status**: ✅ Phase 1 Backend Complete | ⏸️ Phase 2 Frontend Pending
