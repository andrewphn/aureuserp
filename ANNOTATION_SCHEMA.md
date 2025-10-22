# PDF Annotation System - Comprehensive Schema

> **Last Updated:** 2025-10-20
> **System:** TCS Woodwork ERP - PDF Annotation & Cabinet Pricing System

## Table of Contents
- [Database Schema](#database-schema)
- [Entity Relationships](#entity-relationships)
- [API Endpoints](#api-endpoints)
- [Project Tree Structure](#project-tree-structure)
- [Workflows](#workflows)
- [Annotation Types](#annotation-types)
- [Data Flow](#data-flow)
- [Page Types & Metadata](#page-types--metadata)
- [Performance Considerations](#performance-considerations)
- [Complete System Interconnection](#complete-system-interconnection)

---

## Database Schema

### Core Tables

```mermaid
erDiagram
    projects_projects ||--o{ pdf_documents : "has"
    pdf_documents ||--o{ pdf_pages : "contains"
    pdf_pages ||--o{ pdf_page_annotations : "has"
    pdf_pages ||--o{ pdf_page_rooms : "references"

    projects_projects ||--o{ projects_rooms : "contains"
    projects_rooms ||--o{ pdf_page_rooms : "mapped_to"
    projects_rooms ||--o{ projects_room_locations : "has"
    projects_room_locations ||--o{ projects_cabinet_runs : "contains"
    projects_cabinet_runs ||--o{ projects_cabinet_specifications : "includes"

    pdf_page_annotations }o--|| projects_rooms : "links_to"
    pdf_page_annotations }o--|| projects_cabinet_runs : "links_to"
    pdf_page_annotations }o--|| projects_cabinet_specifications : "links_to"
    pdf_page_annotations ||--o{ pdf_page_annotations : "parent_child"

    users ||--o{ pdf_page_annotations : "created"
    users ||--o{ pdf_annotation_history : "performed"

    pdf_pages ||--o{ pdf_annotation_history : "tracks_changes"
    pdf_page_annotations }o--|| pdf_annotation_history : "logged"

    projects_projects {
        bigint id PK
        string project_number
        string name
        bigint partner_id FK
        bigint company_id FK
        timestamps created_at_updated_at
    }

    pdf_documents {
        bigint id PK
        string file_path
        string file_name
        bigint module_id FK "polymorphic:project"
        string module_type "polymorphic"
        timestamps created_at_updated_at
    }

    pdf_pages {
        bigint id PK
        bigint document_id FK
        int page_number
        string page_type "cover,floor_plan,elevation,detail,other"
        json page_metadata "cover fields, detail numbers"
        text notes
        timestamps created_at_updated_at
    }

    pdf_page_annotations {
        bigint id PK
        bigint pdf_page_id FK
        bigint parent_annotation_id FK "nullable,self-ref"
        string annotation_type "room,location,cabinet_run,cabinet"
        string label "nullable"
        decimal x "0-1 normalized"
        decimal y "0-1 normalized"
        decimal width "0-1 normalized"
        decimal height "0-1 normalized"
        bigint room_id FK "nullable"
        string room_type "nullable"
        string color "nullable"
        bigint cabinet_run_id FK "nullable"
        bigint cabinet_specification_id FK "nullable"
        text notes "nullable"
        bigint created_by FK
        timestamps created_at_updated_at
        soft_delete deleted_at
    }

    pdf_page_rooms {
        bigint id PK
        bigint pdf_page_id FK
        bigint room_id FK
        string room_type
        int room_number
        timestamps created_at_updated_at
    }

    projects_rooms {
        bigint id PK
        bigint project_id FK
        string name
        string room_type "kitchen,bathroom,laundry,etc"
        string floor_number "nullable"
        int pdf_page_number "nullable"
        string pdf_room_label "nullable"
        string pdf_detail_number "nullable"
        text notes "nullable"
        int sort_order
        bigint creator_id FK
        timestamps created_at_updated_at
        soft_delete deleted_at
    }

    projects_room_locations {
        bigint id PK
        bigint room_id FK
        string name "North Wall, Island, etc"
        string location_type "wall,island,peninsula,corner"
        int sequence "left-to-right order"
        string elevation_reference "nullable"
        text notes "nullable"
        int sort_order
        bigint creator_id FK
        timestamps created_at_updated_at
        soft_delete deleted_at
    }

    projects_cabinet_runs {
        bigint id PK
        bigint room_location_id FK
        string name "Base Run 1, Upper Cabinets A"
        string run_type "base,wall,tall,specialty"
        decimal total_linear_feet "calculated from cabinets"
        decimal start_wall_measurement "nullable,inches"
        decimal end_wall_measurement "nullable,inches"
        text notes "nullable"
        int sort_order
        bigint creator_id FK
        timestamps created_at_updated_at
        soft_delete deleted_at
    }

    projects_cabinet_specifications {
        bigint id PK
        bigint project_id FK
        bigint cabinet_run_id FK "nullable"
        string cabinet_number
        int position_in_run
        string cabinet_type "base,wall,tall"
        decimal width_inches
        decimal height_inches
        decimal depth_inches
        text notes "nullable"
        timestamps created_at_updated_at
        soft_delete deleted_at
    }

    pdf_annotation_history {
        bigint id PK
        bigint pdf_page_id FK
        bigint annotation_id FK "nullable,can be null if deleted"
        bigint user_id FK
        string action "created,updated,deleted"
        json before_data "nullable"
        json after_data "nullable"
        json metadata "nullable"
        string ip_address "nullable"
        timestamp created_at
    }

    users {
        bigint id PK
        string name
        string email
        timestamps created_at_updated_at
    }
```

---

## Entity Relationships

### Hierarchical Structure

```mermaid
graph TD
    A[Project] --> B[PDF Document]
    B --> C[PDF Pages]
    C --> D[Page Annotations]

    A --> E[Rooms]
    E --> F[Room Locations]
    F --> G[Cabinet Runs]
    G --> H[Cabinet Specifications]

    D -.links to.-> E
    D -.links to.-> G
    D -.links to.-> H

    C -.metadata.-> I[Page Type]
    I --> J[Cover]
    I --> K[Floor Plan]
    I --> L[Elevation]
    I --> M[Detail]
    I --> N[Other]

    D --> O[Annotation Hierarchy]
    O --> P[Room Annotation]
    O --> Q[Location Annotation]
    O --> R[Run Annotation]
    O --> S[Cabinet Annotation]

    P -.parent.-> Q
    Q -.parent.-> R
    R -.parent.-> S

    style A fill:#e1f5ff
    style B fill:#fff3cd
    style C fill:#d4edda
    style D fill:#f8d7da
    style E fill:#e1f5ff
    style F fill:#e1f5ff
    style G fill:#e1f5ff
    style H fill:#e1f5ff
```

### Annotation Types & Hierarchy

```mermaid
graph LR
    A[Annotation Types] --> B[room]
    A --> C[location]
    A --> D[cabinet_run]
    A --> E[cabinet]

    B -.links.-> F[projects_rooms]
    D -.links.-> G[projects_cabinet_runs]
    E -.links.-> H[projects_cabinet_specifications]

    B --> I[Parent: null]
    C --> J[Parent: room]
    D --> K[Parent: location]
    E --> L[Parent: cabinet_run]

    style B fill:#d4edda
    style C fill:#fff3cd
    style D fill:#f8d7da
    style E fill:#e1f5ff
```

---

## API Endpoints

### Endpoint Structure

| Method | Endpoint | Description | Auth |
|--------|----------|-------------|------|
| `GET` | `/api/pdf/page/{pdfPageId}/annotations` | Load all annotations for a page | ✓ |
| `POST` | `/api/pdf/page/{pdfPageId}/annotations` | Save/replace annotations for a page | ✓ |
| `DELETE` | `/api/pdf/page/annotations/{annotationId}` | Delete single annotation | ✓ |
| `GET` | `/api/pdf/page/{pdfPageId}/context` | Get available rooms, runs, cabinets for dropdowns | ✓ |
| `GET` | `/api/pdf/page/{pdfPageId}/project-context` | Get project info for cover page auto-populate | ✓ |
| `GET` | `/api/pdf/page/{pdfPageId}/project-number` | Get project number | ✓ |
| `GET` | `/api/pdf/page/{pdfPageId}/annotations/history` | Get annotation change history | ✓ |
| `GET` | `/api/pdf/page/{pdfPageId}/metadata` | Get page type and metadata | ✓ |
| `POST` | `/api/pdf/page/{pdfPageId}/metadata` | Save page type and metadata | ✓ |
| `POST` | `/api/pdf/page/{pdfPageId}/page-type` | Save page type only | ✓ |
| `GET` | `/api/pdf/annotations/page/{pdfPageId}/cabinet-runs` | Get cabinet runs for project | ✓ |

### API Request/Response Formats

#### Load Annotations (`GET /api/pdf/page/{pdfPageId}/annotations`)

**Response:**
```json
{
  "success": true,
  "annotations": [
    {
      "id": 1,
      "x": 0.25,
      "y": 0.30,
      "width": 0.40,
      "height": 0.25,
      "text": "Kitchen",
      "room_type": "kitchen",
      "color": "#3B82F6",
      "annotation_type": "room",
      "cabinet_run_id": null,
      "room_id": 5,
      "notes": "Main kitchen area"
    }
  ],
  "last_modified": "2025-10-20T14:32:00Z",
  "count": 1
}
```

#### Save Annotations (`POST /api/pdf/page/{pdfPageId}/annotations`)

**Request:**
```json
{
  "annotations": [
    {
      "annotation_type": "room",
      "x": 0.25,
      "y": 0.30,
      "width": 0.40,
      "height": 0.25,
      "text": "Kitchen",
      "room_type": "kitchen",
      "color": "#3B82F6",
      "notes": "Main kitchen area",
      "context": {
        "create_room": true,
        "room_name": "Kitchen",
        "room_type": "kitchen",
        "floor_number": "1"
      }
    }
  ],
  "create_entities": true
}
```

**Response:**
```json
{
  "success": true,
  "message": "Annotations saved successfully",
  "count": 1,
  "annotations": [...],
  "created_entities": [
    {
      "annotation_id": 1,
      "entity_type": "room",
      "entity_id": 5,
      "entity": { "id": 5, "name": "Kitchen", "room_type": "kitchen" }
    }
  ],
  "entities_created_count": 1
}
```

#### Get Context (`GET /api/pdf/page/{pdfPageId}/context`)

**Response:**
```json
{
  "success": true,
  "context": {
    "project_id": 1,
    "project_name": "25 Friendship Lane - Residential",
    "rooms": [
      {
        "id": 5,
        "name": "Kitchen",
        "room_type": "kitchen",
        "floor_number": "1",
        "display_name": "Kitchen (Kitchen)"
      }
    ],
    "room_locations": [
      {
        "id": 12,
        "name": "North Wall",
        "room_id": 5,
        "room_name": "Kitchen",
        "location_type": "wall",
        "display_name": "Kitchen - North Wall"
      }
    ],
    "cabinet_runs": [
      {
        "id": 8,
        "name": "Base Run 1",
        "run_type": "base",
        "room_location_id": 12,
        "room_id": 5,
        "room_name": "Kitchen",
        "location_name": "North Wall",
        "display_name": "Kitchen - North Wall - Base Run 1"
      }
    ],
    "cabinets": [...]
  }
}
```

---

## Project Tree Structure

### Tree API Endpoint

**Endpoint:** `GET /api/projects/{projectId}/tree`

**Purpose:** Returns hierarchical structure of all rooms, locations, and cabinet runs for the annotation sidebar

**Response Format:**
```json
[
  {
    "id": 5,
    "name": "Kitchen",
    "type": "room",
    "annotation_count": 12,
    "children": [
      {
        "id": 8,
        "name": "North Wall",
        "type": "room_location",
        "annotation_count": 5,
        "children": [
          {
            "id": 3,
            "name": "Base Run 1",
            "type": "cabinet_run",
            "annotation_count": 3
          },
          {
            "id": 4,
            "name": "Upper Cabinets A",
            "type": "cabinet_run",
            "annotation_count": 2
          }
        ]
      },
      {
        "id": 9,
        "name": "Island",
        "type": "room_location",
        "annotation_count": 4,
        "children": [...]
      }
    ]
  },
  {
    "id": 6,
    "name": "Master Bathroom",
    "type": "room",
    "annotation_count": 8,
    "children": [...]
  }
]
```

### Tree View Modes

The annotation system supports **two tree view modes** controlled by the sidebar toggle:

```mermaid
graph LR
    A[Tree View Mode] --> B[Group by Room]
    A --> C[Group by Page]

    B --> D[Room → Location → Run]
    C --> E[Page → Room → Location → Run]

    style B fill:#d4edda
    style C fill:#fff3cd
```

#### 1. Group by Room (Default)

**Hierarchy:**
```
📂 Kitchen (Room)
  ├─ 📍 North Wall (Location)
  │   ├─ 📦 Base Run 1 (Cabinet Run)
  │   └─ 📦 Upper Cabinets A (Cabinet Run)
  ├─ 📍 Island (Location)
  │   └─ 📦 Base Run 2 (Cabinet Run)
  └─ 📍 Peninsula (Location)
```

**Use Case:** Best for understanding room organization and creating annotations based on physical room structure

**Data Source:** `/api/projects/{projectId}/tree`

#### 2. Group by Page

**Hierarchy:**
```
📄 Page 2 - Floor Plan
  ├─ 📂 Kitchen (Room)
  │   ├─ 📍 North Wall (Location)
  │   └─ 📦 Base Run 1 (Cabinet Run)
  └─ 📂 Breakfast Nook (Room)
📄 Page 3 - Kitchen Elevation
  ├─ 📂 Kitchen (Room)
  │   ├─ 📍 North Wall (Location)
  │   └─ 📦 Upper Cabinets A (Cabinet Run)
```

**Use Case:** Best for navigating PDF pages and understanding which rooms appear on each page

**Data Source:** Derived from `pdf_pages` table + `pdf_page_annotations` linking

### Tree-to-Annotation Mapping

```mermaid
sequenceDiagram
    participant User
    participant Tree as Tree Sidebar
    participant State as Alpine State
    participant Canvas as Annotation Canvas

    User->>Tree: Click "Kitchen" node
    Tree->>State: Set selectedContext.room = "Kitchen"
    State->>Canvas: Filter annotations<br/>where room_id = 5
    Canvas->>Canvas: Highlight Kitchen annotations

    User->>Tree: Click "North Wall" node
    Tree->>State: Set selectedContext.location = "North Wall"
    State->>Canvas: Filter annotations<br/>where room_id = 5<br/>AND parent matches location
    Canvas->>Canvas: Highlight North Wall annotations

    User->>Tree: Click "Base Run 1" node
    Tree->>State: Set selectedContext.run = "Base Run 1"
    State->>Canvas: Filter annotations<br/>where cabinet_run_id = 3
    Canvas->>Canvas: Highlight Base Run 1 annotations

    Note over User,Canvas: Context selection enables<br/>drawing new annotations<br/>linked to selected entities
```

### Page Type Integration

Page types stored in `pdf_pages.page_metadata` control tree behavior:

```mermaid
graph TD
    A[PDF Page] --> B{Page Type?}

    B -->|cover| C[📋 Cover Page]
    B -->|floor_plan| D[🏗️ Floor Plan]
    B -->|elevation| E[📐 Elevation]
    B -->|detail| F[🔍 Detail]
    B -->|other| G[📄 Other]

    C --> H[No tree nodes<br/>Cover metadata only]
    D --> I[Shows all rooms<br/>Multiple rooms per page]
    E --> J[Shows single room<br/>Multiple locations/runs]
    F --> K[Shows specific detail<br/>Single location/run focus]
    G --> L[Shows related rooms<br/>if annotated]

    I --> M[Tree filtered by page]
    J --> M
    K --> M
    L --> M

    style C fill:#f8d7da
    style D fill:#d4edda
    style E fill:#fff3cd
    style F fill:#e1f5ff
    style G fill:#e2e3e5
```

### Tree Refresh Triggers

The tree automatically refreshes when:

```mermaid
graph LR
    A[User Action] --> B{Trigger?}

    B -->|Create Room| C[POST to rooms API]
    B -->|Create Location| D[POST to locations API]
    B -->|Create Run| E[POST to runs API]
    B -->|Save Annotations| F[POST /page/annotations<br/>with create_entities=true]
    B -->|Delete Entity| G[DELETE to entity API]

    C --> H[loadProjectTree]
    D --> H
    E --> H
    F --> H
    G --> H

    H --> I[Update annotation_count]
    H --> J[Expand new nodes]
    H --> K[Maintain scroll position]

    style F fill:#ffc107
```

### Tree Node Actions

Each tree node supports context actions:

| Node Type | Actions | Description |
|-----------|---------|-------------|
| **Room** | Select, Delete, Add Location | Selecting sets room context for drawing |
| **Location** | Select, Delete, Add Run | Selecting sets location context |
| **Run** | Select, Delete | Selecting sets run context for cabinet annotations |

**Action Flow:**
```
User clicks "Delete" on tree node
  ↓
Modal confirms deletion
  ↓
DELETE /api/rooms/{id} (or locations/runs)
  ↓
Backend cascades: Room → Locations → Runs → Cabinets
  ↓
Backend updates: pdf_page_annotations (set FK to null)
  ↓
Tree refreshes: loadProjectTree()
  ↓
UI updates: Node removed, counts recalculated
```

### Annotation Count Calculation

```sql
-- Room annotation count
SELECT COUNT(*)
FROM pdf_page_annotations
WHERE room_id = {room_id}

-- Location annotation count
SELECT COUNT(*)
FROM pdf_page_annotations
WHERE parent_annotation_id IN (
  SELECT id FROM pdf_page_annotations WHERE room_id = {room_id}
)

-- Run annotation count
SELECT COUNT(*)
FROM pdf_page_annotations
WHERE cabinet_run_id = {run_id}
```

**Note:** ✅ Annotation counts are fully implemented and tested! The tree API now shows real-time annotation counts for all entities (rooms, locations, cabinet runs). See `ANNOTATION_SYSTEM_VERIFICATION.md` for full test coverage.

---

## Workflows

### 1. Annotation Creation Workflow

```mermaid
sequenceDiagram
    actor User
    participant UI as Frontend<br/>(Alpine.js)
    participant API as Laravel API<br/>/api/pdf/page/
    participant DB as Database
    participant Service as Annotation<br/>Entity Service

    User->>UI: Open PDF Page
    UI->>API: GET /page/{id}/annotations
    API->>DB: Query pdf_page_annotations
    DB-->>API: Return annotations
    API-->>UI: JSON annotations
    UI->>UI: Render annotations<br/>on PDF canvas

    User->>UI: Draw box annotation<br/>(room/location/run)
    UI->>UI: Track coordinates<br/>(normalized x,y,w,h)

    User->>UI: Select context<br/>(room type, etc)
    UI->>API: GET /page/{id}/context
    API->>DB: Get available rooms,<br/>locations, runs
    DB-->>API: Return entities
    API-->>UI: Context data

    User->>UI: Click "Save"
    UI->>API: POST /page/{id}/annotations<br/>+ create_entities flag

    API->>DB: BEGIN TRANSACTION
    API->>DB: DELETE existing annotations<br/>for page

    loop For each annotation
        API->>DB: INSERT pdf_page_annotation

        alt create_entities = true
            API->>Service: createOrLinkEntity()
            Service->>DB: Check if room/run exists

            alt Entity doesn't exist
                Service->>DB: CREATE new room/location/run
                Service->>DB: UPDATE annotation<br/>with entity FK
            else Entity exists
                Service->>DB: LINK annotation to entity
            end

            Service-->>API: Return created entity
        end

        API->>DB: INSERT pdf_annotation_history<br/>(action: created)
    end

    API->>DB: COMMIT TRANSACTION
    API-->>UI: Success + created entities
    UI->>UI: Refresh annotations
    UI-->>User: Show success message
```

### 2. Page Type Selection Workflow

```mermaid
sequenceDiagram
    actor User
    participant UI as Frontend
    participant API as API Controller
    participant DB as pdf_pages table

    User->>UI: Navigate to PDF page
    UI->>API: GET /page/{id}/metadata
    API->>DB: SELECT page_metadata
    DB-->>API: Return metadata JSON
    API-->>UI: page_type, cover_metadata
    UI->>UI: Set dropdown to page_type

    User->>UI: Select page type<br/>(Cover/Floor/Elev/Detail/Other)
    UI->>API: POST /page/{id}/page-type<br/>{ page_type: "floor_plan" }

    API->>DB: UPDATE page_metadata JSON<br/>SET page_type = "floor_plan"
    DB-->>API: Success

    API-->>UI: Success response
    UI-->>User: Show saved indicator

    alt Page type = "cover"
        UI->>UI: Show cover page fields
        User->>UI: Fill customer, address, etc
        UI->>API: POST /page/{id}/metadata<br/>{ cover_metadata: {...} }
        API->>DB: UPDATE page_metadata JSON
        DB-->>API: Success
        API-->>UI: Success
    end
```

### 3. Entity Linking Workflow

```mermaid
graph TD
    A[User draws box] --> B{Annotation Type}

    B -->|room| C[Room Annotation]
    B -->|location| D[Location Annotation]
    B -->|cabinet_run| E[Run Annotation]
    B -->|cabinet| F[Cabinet Annotation]

    C --> G{Room exists?}
    G -->|No| H[Create projects_rooms]
    G -->|Yes| I[Link to existing room]
    H --> J[Set annotation.room_id]
    I --> J

    D --> K{Location exists?}
    K -->|No| L[Create projects_room_locations]
    K -->|Yes| M[Link to existing location]
    L --> N[Set parent_annotation_id<br/>to room annotation]
    M --> N

    E --> O{Run exists?}
    O -->|No| P[Create projects_cabinet_runs]
    O -->|Yes| Q[Link to existing run]
    P --> R[Set annotation.cabinet_run_id<br/>Set parent to location]
    Q --> R

    F --> S{Cabinet exists?}
    S -->|No| T[Create projects_cabinet_specifications]
    S -->|Yes| U[Link to existing cabinet]
    T --> V[Set annotation.cabinet_specification_id<br/>Set parent to run]
    U --> V

    J --> W[Save to DB]
    N --> W
    R --> W
    V --> W

    style C fill:#d4edda
    style D fill:#fff3cd
    style E fill:#f8d7da
    style F fill:#e1f5ff
```

### 4. Annotation History Tracking

```mermaid
sequenceDiagram
    participant User
    participant API
    participant Annotations as pdf_page_annotations
    participant History as pdf_annotation_history

    User->>API: Save annotations

    Note over API,History: BEFORE deletion
    API->>Annotations: SELECT existing annotations
    Annotations-->>API: Old data

    loop For each existing
        API->>History: INSERT<br/>(action: 'deleted',<br/>before_data: old,<br/>after_data: null)
    end

    API->>Annotations: DELETE WHERE pdf_page_id

    Note over API,History: AFTER creation
    loop For each new annotation
        API->>Annotations: INSERT new annotation
        Annotations-->>API: New ID
        API->>History: INSERT<br/>(action: 'created',<br/>before_data: null,<br/>after_data: new,<br/>annotation_id: new ID)
    end

    Note over History: History log shows:<br/>- Who made changes<br/>- When changes occurred<br/>- Before/after data<br/>- IP address
```

---

## Annotation Types

### Type Hierarchy & Properties

| Type | Parent | Links To | Normalized Coords | Color | Notes |
|------|--------|----------|-------------------|-------|-------|
| **room** | null | `projects_rooms.id` | ✓ (x,y,w,h: 0-1) | ✓ | Top-level box around entire room area |
| **location** | room | - | ✓ | ✓ | Box within room for specific wall/area |
| **cabinet_run** | location | `projects_cabinet_runs.id` | ✓ | ✓ | Box for continuous cabinet series |
| **cabinet** | cabinet_run | `projects_cabinet_specifications.id` | ✓ | ✓ | Individual cabinet within run |

### Coordinate System

All annotations use **normalized coordinates** (0.0 to 1.0):

```
(0, 0) = Top-left corner of PDF page
(1, 1) = Bottom-right corner of PDF page

x = horizontal position (0 = left edge, 1 = right edge)
y = vertical position (0 = top edge, 1 = bottom edge)
width = horizontal span (0 = 0%, 1 = 100% of page width)
height = vertical span (0 = 0%, 1 = 100% of page height)
```

**Example:**
```json
{
  "x": 0.25,      // 25% from left edge
  "y": 0.30,      // 30% from top edge
  "width": 0.40,  // Box spans 40% of page width
  "height": 0.25  // Box spans 25% of page height
}
```

This allows annotations to scale correctly when:
- PDF is zoomed in/out
- Page is rendered at different resolutions
- Viewing on different screen sizes

---

## Data Flow

### Complete Annotation Lifecycle

```mermaid
graph TB
    A[User opens PDF page] --> B[Load existing annotations]
    B --> C[Render on canvas overlay]

    C --> D{User action}

    D -->|Draw| E[Create new annotation]
    D -->|Edit| F[Modify coordinates/properties]
    D -->|Delete| G[Remove annotation]
    D -->|Navigate| H[Change PDF page]

    E --> I[Set type & properties]
    I --> J[Select context<br/>room, location, run]
    J --> K[Link to entities<br/>or create new]

    F --> K
    G --> K

    K --> L[Click Save]
    L --> M[API: POST /annotations]

    M --> N[Delete old annotations<br/>Log to history]
    N --> O[Insert new annotations<br/>Log to history]
    O --> P[Create/link entities<br/>if flag set]
    P --> Q[Update entity notes<br/>from annotation]

    Q --> R[Commit transaction]
    R --> S[Return success +<br/>created entities]

    S --> T[Refresh UI]
    T --> C

    H --> B

    style A fill:#e1f5ff
    style L fill:#ffc107
    style R fill:#28a745
    style S fill:#28a745
```

### Annotation Save Strategy

**Replace Strategy** (Current Implementation):
1. Delete all existing annotations for page
2. Log deletions to history
3. Insert all new annotations
4. Log creations to history
5. Link to entities or create new ones

**Benefits:**
- Simplifies conflict resolution
- Complete audit trail via history
- No need for complex diff logic
- Transaction ensures atomicity

**Trade-offs:**
- All annotation IDs change on each save
- Cannot preserve client-side annotation IDs
- History table grows with each save

---

## Page Types & Metadata

### Page Type Enum

| Value | Label | Use Case |
|-------|-------|----------|
| `cover` | 📋 Cover | Title page with project info, customer details |
| `floor_plan` | 🏗️ Floor | Floor plan view showing room layout |
| `elevation` | 📐 Elevation | Wall elevation showing cabinet heights/details |
| `detail` | 🔍 Detail | Detail callout for specific area/feature |
| `other` | 📄 Other | Miscellaneous pages (specs, notes, etc) |

### Cover Page Metadata Schema

Stored in `pdf_pages.page_metadata` JSON column:

```json
{
  "page_type": "cover",
  "cover_metadata": {
    "customer_id": 42,
    "company_id": 1,
    "branch_id": 3,
    "address_street1": "25 Friendship Lane",
    "address_street2": "Unit 2B",
    "address_city": "Springfield",
    "address_state_id": 12,
    "address_zip": "12345",
    "address_country_id": 1
  }
}
```

---

## Notes Synchronization

Annotation notes **automatically sync** to linked entities:

```
pdf_page_annotation.notes
    ↓ (if room_id set)
    ├─→ projects_rooms.notes
    ↓ (if cabinet_run_id set)
    ├─→ projects_cabinet_runs.notes
    ↓ (if cabinet_specification_id set)
    └─→ projects_cabinet_specifications.notes
```

This ensures notes are available in both:
- The visual annotation system
- The entity management tables

---

## Security & Authorization

### Middleware

All API endpoints use `auth:web` middleware:
- User must be authenticated
- Authorization checks via Laravel policies
- User context tracked in history logs

### Audit Trail

Every annotation change is logged to `pdf_annotation_history`:

```json
{
  "pdf_page_id": 1,
  "annotation_id": 5,
  "user_id": 12,
  "action": "created",
  "before_data": null,
  "after_data": { ... },
  "metadata": {},
  "ip_address": "192.168.1.100",
  "created_at": "2025-10-20T14:32:00Z"
}
```

---

## Example Use Cases

### Use Case 1: Creating Kitchen with Cabinet Runs

```
1. User opens Floor Plan PDF (Page 2)
2. Sets page type to "floor_plan"
3. Draws room box around kitchen area
   - Type: room
   - Label: "Kitchen"
   - Links to: new projects_rooms record
4. Within kitchen, draws location box for "North Wall"
   - Type: location
   - Parent: kitchen annotation
   - Creates: projects_room_locations record
5. Within North Wall, draws cabinet run box
   - Type: cabinet_run
   - Label: "Base Run 1"
   - Parent: North Wall annotation
   - Links to: new projects_cabinet_runs record
6. Draws individual cabinet boxes within run
   - Type: cabinet
   - Parent: Base Run 1 annotation
   - Links to: new projects_cabinet_specifications records
7. Clicks Save
   - All annotations saved to pdf_page_annotations
   - All entities created in projects_* tables
   - Complete audit trail in pdf_annotation_history
```

### Use Case 2: Cover Page Auto-Population

```
1. User opens Cover Page PDF (Page 1)
2. Sets page type to "cover"
3. Clicks "Auto-Populate from Project"
4. API returns project context:
   - Customer: "John Doe"
   - Address: "25 Friendship Lane, Springfield"
   - Company: "Trottier Fine Woodworking"
5. Form fields auto-fill
6. User reviews and saves
7. Data stored in page_metadata JSON
```

---

## Performance Considerations

### Database Indexes

Critical indexes for query performance:

```sql
-- Annotation lookups
INDEX (pdf_page_id)
INDEX (pdf_page_id, annotation_type)
INDEX (parent_annotation_id)

-- Entity lookups
INDEX (room_id)
INDEX (cabinet_run_id)
INDEX (cabinet_specification_id)

-- History queries
INDEX (pdf_page_id, created_at)
INDEX (user_id)
```

### Caching Strategy

- **Annotations**: Not cached (require real-time updates)
- **Context data**: Cached for 5 minutes (rooms, locations, runs don't change often)
- **Project context**: Cached for 15 minutes (customer info rarely changes)

---

## Future Enhancements

### Potential Improvements

1. **Versioning**: Track annotation versions instead of replace strategy
2. **Conflict Resolution**: Detect concurrent edits, show merge UI
3. **Undo/Redo**: Use history table to implement undo functionality
4. **Templates**: Save annotation patterns for reuse
5. **AI Extraction**: Auto-detect rooms/cabinets from PDF using OCR/ML
6. **Collaboration**: Real-time multi-user editing with WebSockets
7. **Export**: Generate annotated PDFs with flattened boxes

---

## API Error Handling

### Error Response Format

```json
{
  "success": false,
  "error": "Human-readable error message",
  "details": {
    "field_name": ["Validation error"]
  }
}
```

### HTTP Status Codes

| Code | Meaning | Use Case |
|------|---------|----------|
| 200 | OK | Successful GET request |
| 201 | Created | Successful POST (created resources) |
| 400 | Bad Request | Invalid request data |
| 403 | Forbidden | Not authorized to access resource |
| 404 | Not Found | PDF page/annotation not found |
| 422 | Unprocessable Entity | Validation failed |
| 500 | Internal Server Error | Server-side exception |

---

## Complete System Interconnection

### How Everything Connects

```mermaid
graph TB
    subgraph "UI Layer - Frontend"
        A[PDF Viewer] --> B[Annotation Canvas]
        A --> C[Tree Sidebar]
        A --> D[Page Type Selector]
        A --> E[Context Selectors]
    end

    subgraph "State Management - Alpine.js"
        F[annotations array] --> B
        G[tree array] --> C
        H[selectedContext] --> E
        I[pageType] --> D
    end

    subgraph "API Layer"
        J[GET /page/annotations] --> F
        K[POST /page/annotations] --> F
        L[GET /projects/tree] --> G
        M[POST /page/page-type] --> I
        N[GET /page/context] --> H
    end

    subgraph "Database Layer"
        O[pdf_pages]
        P[pdf_page_annotations]
        Q[projects_rooms]
        R[projects_room_locations]
        S[projects_cabinet_runs]
        T[projects_cabinet_specifications]
        U[pdf_annotation_history]
    end

    J --> P
    K --> P
    K --> Q
    K --> R
    K --> S
    K --> T
    K --> U

    L --> Q
    L --> R
    L --> S

    M --> O
    N --> Q
    N --> R
    N --> S
    N --> T

    P -.room_id.-> Q
    P -.cabinet_run_id.-> S
    P -.cabinet_specification_id.-> T
    P -.parent_annotation_id.-> P

    Q --> R
    R --> S
    S --> T

    O -.page_metadata.-> I

    style A fill:#e1f5ff
    style B fill:#f8d7da
    style C fill:#d4edda
    style D fill:#fff3cd
    style E fill:#e2e3e5
```

### Data Flow Example: Creating Room with Annotation

```mermaid
sequenceDiagram
    actor User
    box Frontend
        participant UI as PDF Viewer
        participant State as Alpine State
    end
    box Backend
        participant API as Laravel API
        participant DB as Database
    end

    Note over User,DB: Step 1: Load Page
    User->>UI: Navigate to Page 2
    UI->>API: GET /page/2/annotations
    API->>DB: Query pdf_page_annotations
    DB-->>API: [] (empty)
    API-->>UI: No annotations

    UI->>API: GET /projects/1/tree
    API->>DB: Query rooms → locations → runs
    DB-->>API: Tree structure
    API-->>State: Update tree array

    Note over User,DB: Step 2: Set Page Type
    User->>UI: Select "Floor Plan"
    UI->>API: POST /page/2/page-type<br/>{page_type: "floor_plan"}
    API->>DB: UPDATE pdf_pages<br/>SET page_metadata = '{"page_type":"floor_plan"}'
    DB-->>API: Success
    API-->>UI: Confirmed

    Note over User,DB: Step 3: Draw Annotation
    User->>UI: Draw box on PDF
    State->>State: Track coordinates (x,y,w,h)
    User->>UI: Label: "Kitchen"
    User->>UI: Type: "kitchen"
    State->>State: Store annotation data

    Note over User,DB: Step 4: Save & Create Entity
    User->>UI: Click "Save"
    UI->>API: POST /page/2/annotations<br/>{<br/>  annotations: [{...}],<br/>  create_entities: true<br/>}

    API->>DB: BEGIN TRANSACTION
    API->>DB: DELETE FROM pdf_page_annotations<br/>WHERE pdf_page_id = 2
    API->>DB: Log deletion to history

    API->>DB: Check if Kitchen exists
    DB-->>API: Not found

    API->>DB: INSERT INTO projects_rooms<br/>(name: "Kitchen", project_id: 1)
    DB-->>API: room_id = 5

    API->>DB: INSERT INTO pdf_page_annotations<br/>(room_id: 5, x, y, w, h, ...)
    DB-->>API: annotation_id = 10

    API->>DB: Log creation to history
    API->>DB: COMMIT TRANSACTION
    API-->>UI: Success + created entities

    Note over User,DB: Step 5: Refresh UI
    UI->>API: GET /projects/1/tree
    API->>DB: Query updated structure
    DB-->>API: Tree with Kitchen
    API-->>State: Update tree array
    State-->>UI: Render Kitchen in tree

    UI-->>User: Show Kitchen annotation<br/>+ Kitchen in tree sidebar
```

### Complete Feature Map

| Feature | Database Tables | API Endpoints | UI Components |
|---------|-----------------|---------------|---------------|
| **PDF Pages** | `pdf_documents`, `pdf_pages` | GET /page/{id}/metadata | Page selector, Page type dropdown |
| **Annotations** | `pdf_page_annotations` | GET/POST /page/{id}/annotations | Canvas overlay, Drawing tools |
| **Rooms** | `projects_rooms`, `pdf_page_rooms` | Implicit via tree/annotations | Tree nodes (📂), Context selector |
| **Locations** | `projects_room_locations` | Implicit via tree | Tree nodes (📍), Context selector |
| **Cabinet Runs** | `projects_cabinet_runs` | GET /cabinet-runs | Tree nodes (📦), Context selector |
| **Cabinets** | `projects_cabinet_specifications` | Implicit via context | Annotation targets |
| **Project Tree** | All room tables | GET /projects/{id}/tree | Tree sidebar, Group by buttons |
| **Page Types** | `pdf_pages.page_metadata` | POST /page/{id}/page-type | Page type selector (Cover/Floor/Elev/etc) |
| **Context Data** | All entity tables | GET /page/{id}/context | Dropdown options for linking |
| **History** | `pdf_annotation_history` | GET /page/{id}/annotations/history | Audit trail (future UI) |

---

## Conclusion

This annotation system provides:

✅ **Hierarchical Structure**: Room → Location → Run → Cabinet
✅ **Flexible Linking**: Annotations can reference or create entities
✅ **Complete Audit Trail**: Every change logged with user/time/data
✅ **Normalized Coordinates**: Scale-independent positioning
✅ **Type Safety**: Validated annotation types and relationships
✅ **Transaction Safety**: Atomic saves with rollback on error
✅ **Entity Sync**: Notes automatically sync to linked entities
✅ **Tree Navigation**: Two view modes (Room/Page) for different workflows
✅ **Page Type Awareness**: Different behaviors for Cover/Floor/Elevation pages
✅ **Real-time Updates**: Tree refreshes after entity creation/deletion

The system supports TCS Woodwork's workflow from PDF review to pricing generation while maintaining data integrity and providing full traceability through interconnected databases, APIs, and UI components.
