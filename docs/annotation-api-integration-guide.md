# Annotation System - API Integration Guide

## Overview

This guide explains how to integrate the frontend annotation modal with the backend API endpoints for the multi-pass hierarchical annotation system.

## API Endpoints

### 1. Get Context Data (Dropdown Population)

**Endpoint**: `GET /api/pdf/page/{pdfPageId}/context`

**Purpose**: Fetch all available entities for dropdown population (Rooms, RoomLocations, CabinetRuns, Cabinets)

**Response**:
```json
{
  "success": true,
  "context": {
    "project_id": 42,
    "project_name": "Smith Residence",
    "rooms": [
      {
        "id": 1,
        "name": "Master Kitchen",
        "room_type": "kitchen",
        "floor_number": "1",
        "display_name": "Master Kitchen (Kitchen)"
      },
      {
        "id": 2,
        "name": "Guest Bathroom",
        "room_type": "bathroom",
        "floor_number": "2",
        "display_name": "Guest Bathroom (Bathroom)"
      }
    ],
    "room_locations": [
      {
        "id": 10,
        "name": "North Wall",
        "room_id": 1,
        "room_name": "Master Kitchen",
        "location_type": "wall",
        "display_name": "Master Kitchen - North Wall"
      },
      {
        "id": 11,
        "name": "Island",
        "room_id": 1,
        "room_name": "Master Kitchen",
        "location_type": "island",
        "display_name": "Master Kitchen - Island"
      }
    ],
    "cabinet_runs": [
      {
        "id": 100,
        "name": "Base Run 1",
        "run_type": "base",
        "room_location_id": 10,
        "room_id": 1,
        "room_name": "Master Kitchen",
        "location_name": "North Wall",
        "display_name": "Master Kitchen - North Wall - Base Run 1"
      }
    ],
    "cabinets": [
      {
        "id": 500,
        "cabinet_number": "BC-1",
        "position_in_run": 1,
        "cabinet_run_id": 100,
        "room_name": "Master Kitchen",
        "run_name": "Base Run 1",
        "display_name": "Master Kitchen - Base Run 1 - BC-1"
      }
    ]
  }
}
```

**Usage**:
```javascript
// Load context when modal opens
async function loadAnnotationContext(pdfPageId) {
  const response = await fetch(`/api/pdf/page/${pdfPageId}/context`, {
    headers: {
      'Accept': 'application/json',
      'Authorization': 'Bearer ' + authToken
    }
  });

  const data = await response.json();

  if (data.success) {
    // Populate dropdowns
    populateRoomsDropdown(data.context.rooms);
    populateRoomLocationsDropdown(data.context.room_locations);
    populateCabinetRunsDropdown(data.context.cabinet_runs);
    populateCabinetsDropdown(data.context.cabinets);
  }
}
```

### 2. Save Annotations with Entity Creation

**Endpoint**: `POST /api/pdf/page/{pdfPageId}/annotations`

**Purpose**: Save annotations AND automatically create linked entities (Room, CabinetRun, Cabinet, etc.)

**Request Body**:
```json
{
  "create_entities": true,
  "annotations": [
    {
      "annotation_type": "room",
      "x": 0.1,
      "y": 0.2,
      "width": 0.3,
      "height": 0.25,
      "text": "Master Kitchen",
      "room_type": "kitchen",
      "color": "#3B82F6",
      "notes": "Main cooking area",
      "context": {}
    },
    {
      "annotation_type": "cabinet_run",
      "x": 0.25,
      "y": 0.30,
      "width": 0.40,
      "height": 0.15,
      "text": "Base Run 1",
      "notes": "North wall cabinets",
      "context": {
        "room_id": 1,
        "room_location_id": 10,
        "run_type": "base"
      }
    },
    {
      "annotation_type": "cabinet",
      "x": 0.26,
      "y": 0.31,
      "width": 0.10,
      "height": 0.14,
      "text": "BC-1",
      "notes": "Corner base cabinet",
      "context": {
        "cabinet_run_id": 100,
        "position_in_run": 1,
        "length_inches": 36,
        "width_inches": 24,
        "depth_inches": 24,
        "height_inches": 30
      }
    }
  ]
}
```

**Response**:
```json
{
  "success": true,
  "message": "Annotations saved successfully",
  "count": 3,
  "annotations": [
    {
      "id": 201,
      "pdf_page_id": 5,
      "annotation_type": "room",
      "room_id": 15,
      "label": "Master Kitchen",
      "x": 0.1,
      "y": 0.2,
      "width": 0.3,
      "height": 0.25
    },
    {
      "id": 202,
      "pdf_page_id": 5,
      "annotation_type": "cabinet_run",
      "cabinet_run_id": 101,
      "label": "Base Run 1",
      "x": 0.25,
      "y": 0.30
    },
    {
      "id": 203,
      "pdf_page_id": 5,
      "annotation_type": "cabinet",
      "cabinet_specification_id": 505,
      "label": "BC-1"
    }
  ],
  "created_entities": [
    {
      "annotation_id": 201,
      "entity_type": "room",
      "entity_id": 15,
      "entity": {
        "id": 15,
        "name": "Master Kitchen",
        "room_type": "kitchen",
        "project_id": 42
      }
    },
    {
      "annotation_id": 202,
      "entity_type": "cabinet_run",
      "entity_id": 101,
      "entity": {
        "id": 101,
        "name": "Base Run 1",
        "run_type": "base",
        "room_location_id": 10
      }
    },
    {
      "annotation_id": 203,
      "entity_type": "cabinet",
      "entity_id": 505,
      "entity": {
        "id": 505,
        "cabinet_number": "BC-1",
        "length_inches": 36,
        "cabinet_run_id": 100
      }
    }
  ],
  "entities_created_count": 3
}
```

**Usage**:
```javascript
async function saveAnnotationsWithEntities(pdfPageId, annotations) {
  const response = await fetch(`/api/pdf/page/${pdfPageId}/annotations`, {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json',
      'Authorization': 'Bearer ' + authToken
    },
    body: JSON.stringify({
      create_entities: true,
      annotations: annotations
    })
  });

  const data = await response.json();

  if (data.success) {
    console.log(`Saved ${data.count} annotations`);
    console.log(`Created ${data.entities_created_count} entities`);

    // Show success message with created entities
    data.created_entities.forEach(entity => {
      console.log(`Created ${entity.entity_type}: ${entity.entity.name}`);
    });
  }
}
```

### 3. Load Existing Annotations

**Endpoint**: `GET /api/pdf/page/{pdfPageId}/annotations`

**Purpose**: Load saved annotations for a specific page

**Response**:
```json
{
  "success": true,
  "annotations": [
    {
      "id": 201,
      "x": 0.1,
      "y": 0.2,
      "width": 0.3,
      "height": 0.25,
      "text": "Master Kitchen",
      "room_type": "kitchen",
      "color": "#3B82F6",
      "room_id": 15,
      "annotation_type": "room"
    }
  ],
  "count": 1
}
```

## Frontend Integration Workflow

### Floor Plan Page - Creating Room

```javascript
// 1. User opens modal on floor plan page
const modal = openAnnotationModal(pdfPageId);

// 2. Load context (though floor plan doesn't need dropdowns)
await loadAnnotationContext(pdfPageId);

// 3. User selects annotation type = "room"
modal.annotationType = 'room';

// 4. User selects room type from palette
modal.selectedRoomType = 'kitchen';

// 5. User draws box on canvas
const annotation = {
  annotation_type: 'room',
  x: normalizedX,
  y: normalizedY,
  width: normalizedWidth,
  height: normalizedHeight,
  text: modal.inputLabel || modal.selectedRoomType,
  room_type: modal.selectedRoomType,
  color: getRoomTypeColor(modal.selectedRoomType),
  notes: modal.inputNotes,
  context: {} // Empty context - will be created fresh
};

// 6. Save annotation
const result = await saveAnnotationsWithEntities(pdfPageId, [annotation]);

// 7. Result contains created Room entity
console.log('Created Room ID:', result.created_entities[0].entity_id);
```

### Elevation Page - Creating Cabinet Run

```javascript
// 1. User opens modal on elevation page
const modal = openAnnotationModal(pdfPageId);

// 2. Load context data
const contextData = await loadAnnotationContext(pdfPageId);

// 3. Populate dropdowns
modal.rooms = contextData.context.rooms;
modal.roomLocations = contextData.context.room_locations;

// 4. User selects annotation type = "cabinet_run"
modal.annotationType = 'cabinet_run';

// 5. User selects Room from dropdown
modal.selectedRoomId = 1; // Master Kitchen

// 6. Filter and display Room Locations for selected Room
modal.filteredLocations = modal.roomLocations.filter(
  loc => loc.room_id === modal.selectedRoomId
);

// 7. User selects Room Location
modal.selectedRoomLocationId = 10; // North Wall

// 8. User selects run type
modal.selectedRunType = 'base';

// 9. User draws box on canvas
const annotation = {
  annotation_type: 'cabinet_run',
  x: normalizedX,
  y: normalizedY,
  width: normalizedWidth,
  height: normalizedHeight,
  text: modal.inputLabel || 'Base Run 1',
  notes: modal.inputNotes,
  context: {
    room_id: modal.selectedRoomId,
    room_location_id: modal.selectedRoomLocationId,
    run_type: modal.selectedRunType
  }
};

// 10. Save annotation
const result = await saveAnnotationsWithEntities(pdfPageId, [annotation]);

// 11. Result contains created CabinetRun entity
console.log('Created CabinetRun ID:', result.created_entities[0].entity_id);
```

### Elevation Page - Creating Cabinet

```javascript
// 1. User already has modal open on elevation page
// 2. Context already loaded

// 3. User changes annotation type = "cabinet"
modal.annotationType = 'cabinet';

// 4. Display cabinet runs dropdown
modal.cabinetRuns = contextData.context.cabinet_runs;

// 5. User selects Cabinet Run
modal.selectedCabinetRunId = 100; // Base Run 1

// 6. User draws smaller box within the run
const annotation = {
  annotation_type: 'cabinet',
  x: normalizedX,
  y: normalizedY,
  width: normalizedWidth,
  height: normalizedHeight,
  text: 'BC-1',
  notes: modal.inputNotes,
  context: {
    cabinet_run_id: modal.selectedCabinetRunId,
    position_in_run: calculatePosition(normalizedX), // Auto-calc from x position
    length_inches: 36,
    width_inches: 24,
    depth_inches: 24,
    height_inches: 30
  }
};

// 7. Save annotation
const result = await saveAnnotationsWithEntities(pdfPageId, [annotation]);
```

## Cascade Filtering Logic

```javascript
// Room selected → Filter Room Locations
function onRoomSelected(roomId) {
  const filteredLocations = allRoomLocations.filter(
    loc => loc.room_id === roomId
  );

  populateDropdown('room-location-select', filteredLocations);

  // Reset dependent selections
  selectedRoomLocationId = null;
  selectedCabinetRunId = null;
}

// Room Location selected → Filter Cabinet Runs
function onRoomLocationSelected(roomLocationId) {
  const filteredRuns = allCabinetRuns.filter(
    run => run.room_location_id === roomLocationId
  );

  populateDropdown('cabinet-run-select', filteredRuns);

  // Reset dependent selections
  selectedCabinetRunId = null;
}

// Cabinet Run selected → Filter Cabinets
function onCabinetRunSelected(cabinetRunId) {
  const filteredCabinets = allCabinets.filter(
    cab => cab.cabinet_run_id === cabinetRunId
  );

  populateDropdown('cabinet-select', filteredCabinets);
}
```

## Error Handling

```javascript
async function saveAnnotationsWithErrorHandling(pdfPageId, annotations) {
  try {
    const response = await fetch(`/api/pdf/page/${pdfPageId}/annotations`, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'Authorization': 'Bearer ' + authToken
      },
      body: JSON.stringify({
        create_entities: true,
        annotations: annotations
      })
    });

    const data = await response.json();

    if (!response.ok) {
      throw new Error(data.error || 'Failed to save annotations');
    }

    if (!data.success) {
      throw new Error(data.error || 'Unknown error');
    }

    return data;

  } catch (error) {
    console.error('Annotation save error:', error);
    showErrorNotification(error.message);
    throw error;
  }
}
```

## Chatter Activity Logging

All entity creation and updates are automatically logged to Chatter via the `HasLogActivity` trait. No additional frontend code needed.

**Example Chatter entries created**:
- "Room created: Master Kitchen (Kitchen)"
- "Room Location created: North Wall in Master Kitchen"
- "Cabinet Run created: Base Run 1 in Master Kitchen - North Wall"
- "Cabinet created: BC-1 in Base Run 1"
- "Cabinet dimensions updated: 36\"W x 24\"D x 30\"H"

Users can view these in the Chatter panel on the Project detail page.

## Summary

✅ **Backend Complete**:
- Context endpoint provides all dropdown data
- Save endpoint creates entities automatically
- Chatter logging tracks all changes
- Service layer handles hierarchical relationships

⏸️ **Frontend TODO**:
- Integrate context API call on modal open
- Build cascading dropdown UI components
- Wire up annotation save with context data
- Handle create vs. link entity modes
- Display created entity feedback

---

**Last Updated**: 2025-10-08
**API Version**: 1.0
