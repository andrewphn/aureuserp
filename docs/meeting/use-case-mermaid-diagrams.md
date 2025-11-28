# Use Case Mermaid Diagrams

**Date:** 2025-11-21
**Project:** Sarah Martinez Kitchen Renovation
**Purpose:** Visual workflow diagrams

---

## Diagram 1: Complete Workflow Sequence

```mermaid
sequenceDiagram
    participant Customer as Sarah Martinez<br/>(Customer)
    participant Bryan as Bryan<br/>(Owner)
    participant System as AureusERP<br/>Database
    participant Levi as Levi<br/>(Lead Craftsman)
    participant Aiden as Aiden<br/>(Warehouse)
    participant Sadie as Sadie<br/>(Purchasing)
    participant Inventory as Products<br/>Inventory

    rect rgb(240, 248, 255)
        Note over Customer,Bryan: PHASE 1: SALES & PROJECT SETUP
        Customer->>Bryan: Request kitchen renovation quote
        Bryan->>System: Create customer record<br/>(partners_partners)
        Bryan->>System: Create sales order Q-2025-001<br/>($85,000)
        Bryan->>Customer: Send quote
        Customer->>Bryan: Approve quote
        Bryan->>System: Convert to project<br/>(projects_projects)
    end

    rect rgb(255, 248, 240)
        Note over Bryan,System: PHASE 2: DESIGN & SPECIFICATION
        Bryan->>System: Create room: Kitchen<br/>(projects_rooms)
        Bryan->>System: Create location: Island<br/>(projects_room_locations)
        Bryan->>System: Create cabinet run<br/>(projects_cabinet_runs)
        Bryan->>System: Create cabinet B36<br/>Link to product_id: 456
        Bryan->>System: Create section: Door Opening<br/>(projects_cabinet_sections)
        Bryan->>System: Create door D1<br/>Link to product_id: 789
        Bryan->>System: Create door D2<br/>Link to product_id: 789
        System->>Inventory: Reserve 2 door blanks<br/>(25 → 23 available)
        Bryan->>System: Create pullout P1<br/>Link to product_id: 850
        System->>Inventory: Reserve Rev-A-Shelf unit<br/>(3 → 2 available)
    end

    rect rgb(240, 255, 240)
        Note over Bryan,System: PHASE 3: TASK GENERATION
        Bryan->>System: Generate production tasks
        System->>System: Create task: CNC cut D1<br/>Assign to Levi
        System->>System: Create task: CNC cut D2<br/>Assign to Levi
        System->>System: Create task: Edge band<br/>Assign to Aiden<br/>Depends on: [D1, D2]
        System->>System: Create task: Order pullout<br/>Assign to Sadie
    end

    rect rgb(255, 240, 255)
        Note over Levi,Inventory: PHASE 4: PRODUCTION - DAY 15
        Levi->>System: Start task: CNC cut D1
        Levi->>Levi: Cut door D1 on CNC
        Levi->>System: Complete task<br/>Set door.cnc_cut_at = NOW()
        System->>Inventory: Deplete inventory<br/>product_id: 789<br/>(23 → 22 blanks)

        Levi->>System: Start task: CNC cut D2
        Levi->>Levi: Cut door D2 on CNC
        Levi->>System: Complete task<br/>Set door.cnc_cut_at = NOW()
        System->>Inventory: Deplete inventory<br/>(22 → 21 blanks)

        Note over System,Inventory: Inventory check: 21 blanks remaining
        System->>System: Stock OK (threshold: 10)

        Aiden->>System: Start task: Edge band doors<br/>(Dependencies complete)
        Aiden->>Aiden: Apply edge banding
        Aiden->>System: Complete task<br/>Set edge_banded_at = NOW()

        Sadie->>System: Start task: Order pullout
        Sadie->>Inventory: Create PO to Rev-A-Shelf
        Sadie->>System: Complete task<br/>Set pullout.ordered_at = NOW()
    end

    rect rgb(255, 255, 240)
        Note over Levi,System: PHASE 5: ASSEMBLY - DAY 16-22
        Levi->>System: Set door.assembled_at = NOW()
        Levi->>System: Set door.sanded_at = NOW()
        Note over Levi,System: Send to finishing (external)
        System->>System: Set door.finished_at = NOW()<br/>(Day 22 - returned)
    end

    rect rgb(240, 255, 255)
        Note over Levi,System: PHASE 6: QC INSPECTION - DAY 23
        Levi->>Levi: Inspect door D1
        Levi->>System: door D1: qc_passed = true<br/>"Excellent finish"
        Levi->>Levi: Inspect door D2
        Levi->>System: door D2: qc_passed = false<br/>"Chip on bottom rail"
        System->>System: Auto-create task:<br/>"Rework door D2"
        Levi->>Levi: Touch up door D2
        Levi->>System: door D2: qc_passed = true
    end

    rect rgb(255, 240, 240)
        Note over Levi,System: PHASE 7: INSTALLATION - DAY 26
        Levi->>System: Set hardware_installed_at = NOW()
        Levi->>System: Set installed_in_cabinet_at = NOW()
        Levi->>Customer: Install at 1428 Oak Street
        Levi->>System: Complete installation task
        System->>System: Set project.status = completed
    end

    rect rgb(248, 248, 255)
        Note over Bryan,Customer: PHASE 8: INVOICING - DAY 27
        Bryan->>System: Calculate costs:<br/>Materials: $1,849.50<br/>Labor: $1,875
        Bryan->>System: Create invoice INV-2025-001<br/>$93,075 (incl tax)
        Bryan->>Customer: Send invoice
        Customer->>Bryan: Payment received
        Note over Bryan,Customer: Profit: $81,275.50 (95.6%)
    end
```

---

## Diagram 2: Database Relationships

```mermaid
erDiagram
    partners_partners ||--o{ sales_orders : "places"
    sales_orders ||--o| projects_projects : "becomes"
    projects_projects ||--o{ projects_rooms : "contains"
    projects_rooms ||--o{ projects_room_locations : "has"
    projects_room_locations ||--o{ projects_cabinet_runs : "groups"
    projects_cabinet_runs ||--o{ projects_cabinet_specifications : "contains"
    projects_cabinet_specifications ||--o{ projects_cabinet_sections : "subdivides"
    projects_cabinet_specifications ||--o{ projects_doors : "has"
    projects_cabinet_specifications ||--o{ projects_drawers : "has"
    projects_cabinet_specifications ||--o{ projects_shelves : "has"
    projects_cabinet_specifications ||--o{ projects_pullouts : "has"
    projects_cabinet_sections ||--o{ projects_doors : "organizes"
    projects_cabinet_sections ||--o{ projects_drawers : "organizes"

    products_products ||--o{ projects_cabinet_specifications : "used_in"
    products_products ||--o{ projects_doors : "used_in"
    products_products ||--o{ projects_drawers : "used_in"
    products_products ||--o{ projects_shelves : "used_in"
    products_products ||--o{ projects_pullouts : "used_in"

    projects_tasks ||--o| projects_projects : "assigned_to"
    projects_tasks ||--o| projects_cabinet_specifications : "assigned_to"
    projects_tasks ||--o| projects_cabinet_sections : "assigned_to"
    projects_tasks ||--o| projects_doors : "assigned_to"
    projects_tasks ||--o| projects_drawers : "assigned_to"
    projects_tasks ||--o| projects_shelves : "assigned_to"
    projects_tasks ||--o| projects_pullouts : "assigned_to"

    users ||--o{ projects_tasks : "performs"

    partners_partners {
        int id PK
        string name
        string type
    }

    sales_orders {
        int id PK
        int customer_id FK
        string order_number
        decimal total_amount
    }

    projects_projects {
        int id PK
        int order_id FK
        int customer_id FK
        string name
    }

    projects_cabinet_specifications {
        int id PK
        int product_id FK
        int cabinet_run_id FK
        string cabinet_name
    }

    projects_doors {
        int id PK
        int product_id FK
        int cabinet_specification_id FK
        int section_id FK
        string door_name
        timestamp cnc_cut_at
        timestamp finished_at
        boolean qc_passed
    }

    products_products {
        int id PK
        string name
        decimal cost
        int quantity_on_hand
    }

    projects_tasks {
        int id PK
        int project_id FK
        int cabinet_id FK
        int section_id FK
        string component_type
        int component_id
        int assigned_to FK
        string status
    }
```

---

## Diagram 3: Task Dependencies Flow

```mermaid
graph TB
    Start([Project Approved]) --> GenTasks[Generate Production Tasks]

    GenTasks --> T1[Task 1: CNC cut door D1<br/>Assigned: Levi<br/>Status: pending]
    GenTasks --> T2[Task 2: CNC cut door D2<br/>Assigned: Levi<br/>Status: pending]
    GenTasks --> T4[Task 4: Order pullout P1<br/>Assigned: Sadie<br/>Status: pending]

    T1 --> T1_Done{D1 Cut Complete}
    T2 --> T2_Done{D2 Cut Complete}

    T1_Done -->|Yes| Inv1[Deplete Inventory<br/>Product 789: 25→24]
    T2_Done -->|Yes| Inv2[Deplete Inventory<br/>Product 789: 24→23]

    Inv1 --> CheckStock{Stock < 10?}
    Inv2 --> CheckStock

    CheckStock -->|Yes| AlertTask[Create Task:<br/>Reorder door blanks]
    CheckStock -->|No| T3Wait

    T1_Done --> T3Wait{Both doors cut?}
    T2_Done --> T3Wait

    T3Wait -->|Yes| T3[Task 3: Edge band doors<br/>Assigned: Aiden<br/>Status: pending]

    T3 --> T3_Done[Edge Banding Complete]

    T3_Done --> T5[Task 5: Assemble doors<br/>Assigned: Levi]
    T5 --> T6[Task 6: Sand doors<br/>Assigned: Levi]
    T6 --> T7[Task 7: Send to finishing<br/>External]
    T7 --> T8[Task 8: QC Inspection<br/>Assigned: Levi]

    T8 --> QC{QC Pass?}
    QC -->|D1: Yes| QC_D1_OK[door.qc_passed = true]
    QC -->|D2: No| QC_D2_Fail[door.qc_passed = false<br/>Create rework task]

    QC_D2_Fail --> T9[Task 9: Rework door D2<br/>Assigned: Levi]
    T9 --> QC_D2_Retest{QC Pass?}
    QC_D2_Retest -->|Yes| QC_D2_OK[door.qc_passed = true]

    QC_D1_OK --> T10[Task 10: Install hardware<br/>Assigned: Levi]
    QC_D2_OK --> T10

    T4 --> T4_Done[Pullout Ordered<br/>pullout.ordered_at = NOW]
    T4_Done --> T4_Wait[Wait for delivery...]
    T4_Wait --> T4_Recv[Pullout Received<br/>pullout.received_at = NOW]
    T4_Recv --> Inv3[Deplete Inventory<br/>Product 850: 3→2]

    T10 --> T11[Task 11: Install in cabinet<br/>Assigned: Levi]
    Inv3 --> T11

    T11 --> T12[Task 12: On-site installation<br/>Assigned: Levi + Bryan]
    T12 --> Complete([Project Complete])

    Complete --> Invoice[Generate Invoice<br/>Materials + Labor]

    style T1 fill:#e3f2fd
    style T2 fill:#e3f2fd
    style T3 fill:#fff3e0
    style T4 fill:#f3e5f5
    style T8 fill:#e8f5e9
    style QC_D2_Fail fill:#ffebee
    style Complete fill:#c8e6c9
    style Invoice fill:#fff9c4
```

---

## Diagram 4: Production Timeline (Gantt Chart)

```mermaid
gantt
    title Sarah Martinez Kitchen Renovation Timeline
    dateFormat YYYY-MM-DD

    section Sales & Setup
    Customer quote           :done, sales1, 2025-12-01, 1d
    Quote approval          :done, sales2, 2025-12-05, 1d
    Create project          :done, sales3, 2025-12-05, 1d

    section Design
    Room & location setup   :done, design1, 2025-12-06, 1d
    Cabinet specifications  :done, design2, 2025-12-08, 3d
    Component spec (doors)  :done, design3, 2025-12-11, 1d
    Component spec (drawers):done, design4, 2025-12-11, 1d

    section Production Planning
    Generate tasks          :done, plan1, 2025-12-12, 1d

    section Fabrication
    CNC cut door D1 (Levi)  :done, fab1, 2025-12-15, 2h
    CNC cut door D2 (Levi)  :done, fab2, 2025-12-15, 2h
    Edge band doors (Aiden) :done, fab3, 2025-12-15, 3h
    Assemble doors (Levi)   :done, fab4, 2025-12-16, 1d
    Sand doors (Levi)       :done, fab5, 2025-12-17, 1d

    section Finishing
    Send to finishing       :done, finish1, 2025-12-18, 1d
    External finishing      :active, finish2, 2025-12-19, 3d
    Return from finishing   :finish3, 2025-12-22, 1d

    section Quality Control
    QC inspection (Levi)    :qc1, 2025-12-23, 4h
    Rework door D2 (Levi)   :qc2, 2025-12-23, 2h
    Re-inspect D2 (Levi)    :qc3, 2025-12-23, 1h

    section Hardware & Assembly
    Install hinges (Levi)   :hw1, 2025-12-24, 4h
    Assemble cabinet (Levi) :hw2, 2025-12-25, 1d

    section Procurement
    Order pullout (Sadie)   :done, proc1, 2025-12-15, 1d
    Wait for delivery       :proc2, 2025-12-16, 5d
    Receive pullout         :proc3, 2025-12-21, 1d
    Install pullout (Levi)  :proc4, 2025-12-25, 2h

    section Installation
    On-site install         :install1, 2025-12-26, 1d
    Final walkthrough       :install2, 2025-12-26, 2h

    section Invoicing
    Calculate costs         :invoice1, 2025-12-27, 2h
    Generate invoice        :invoice2, 2025-12-27, 1h
    Send to customer        :invoice3, 2025-12-27, 1h
```

---

## Diagram 5: Inventory Integration Flow

```mermaid
graph LR
    subgraph Design Phase
        A[Bryan creates door D1] --> B{Select Product}
        B --> C[Product: Door Blank<br/>ID: 789<br/>Cost: $45<br/>Stock: 25]
        C --> D[Link door.product_id = 789]
        D --> E[Stock reserved<br/>25 → 24 available]
    end

    subgraph Production Phase
        F[Levi starts task:<br/>CNC cut door D1] --> G[Cut door on CNC]
        G --> H[Complete task<br/>door.cnc_cut_at = NOW]
    end

    subgraph Inventory Update
        H --> I{Trigger: cnc_cut_at set}
        I --> J[Find product_id: 789]
        J --> K[Deplete inventory<br/>quantity_on_hand - 1]
        K --> L[Update: 24 → 23]
        L --> M{Stock < reorder_point?}
        M -->|Yes, < 10| N[Create task:<br/>Reorder door blanks<br/>Assign: Sadie]
        M -->|No, >= 10| O[Continue production]
    end

    subgraph Cost Tracking
        P[End of project] --> Q[Query all components<br/>with product_id]
        Q --> R[Sum product.cost<br/>for all linked products]
        R --> S[Total Materials: $1,849.50]
        S --> T[Calculate profit margin]
    end

    style C fill:#e3f2fd
    style E fill:#fff3e0
    style H fill:#e8f5e9
    style L fill:#ffecb3
    style N fill:#ffccbc
    style S fill:#c8e6c9
```

---

## Diagram 6: Task-Component Polymorphic Relationship

```mermaid
graph TB
    subgraph Task Assignment Options
        Task[projects_tasks<br/>task_id: 1001]

        Task --> Opt1{Assignment Level}

        Opt1 -->|Project Level| P[project_id: 1<br/>component_type: NULL<br/>component_id: NULL<br/><br/>Example: Design review]

        Opt1 -->|Cabinet Level| C[project_id: 1<br/>cabinet_id: 123<br/>component_type: NULL<br/>component_id: NULL<br/><br/>Example: Assemble B36]

        Opt1 -->|Section Level| S[project_id: 1<br/>cabinet_id: 123<br/>section_id: 5<br/>component_type: NULL<br/><br/>Example: Edge band<br/>all doors in section]

        Opt1 -->|Component Level| Comp{Component Type}

        Comp -->|Door| D[project_id: 1<br/>cabinet_id: 123<br/>section_id: 5<br/>component_type: 'door'<br/>component_id: 45<br/><br/>Example: CNC cut door D1]

        Comp -->|Drawer| DR[component_type: 'drawer'<br/>component_id: 23<br/><br/>Example: Install<br/>drawer slide DR2]

        Comp -->|Shelf| SH[component_type: 'shelf'<br/>component_id: 12<br/><br/>Example: Cut shelf S1]

        Comp -->|Pullout| PO[component_type: 'pullout'<br/>component_id: 1<br/><br/>Example: Order pullout P1]
    end

    subgraph Component Tables
        D -.->|Polymorphic Link| DoorTable[(projects_doors<br/>id: 45<br/>door_name: D1)]
        DR -.->|Polymorphic Link| DrawerTable[(projects_drawers<br/>id: 23<br/>drawer_name: DR2)]
        SH -.->|Polymorphic Link| ShelfTable[(projects_shelves<br/>id: 12<br/>shelf_name: S1)]
        PO -.->|Polymorphic Link| PulloutTable[(projects_pullouts<br/>id: 1<br/>pullout_name: P1)]
    end

    style Task fill:#e1bee7
    style D fill:#ffccbc
    style DR fill:#b2dfdb
    style SH fill:#c5e1a5
    style PO fill:#ffe082
```

---

## Diagram 7: QC Workflow Decision Tree

```mermaid
graph TD
    Start[Component finished] --> QC[Levi performs QC inspection]

    QC --> Check{Inspect component}

    Check -->|Door D1| D1_Check{Quality OK?}
    D1_Check -->|Yes| D1_Pass[door.qc_passed = true<br/>door.qc_notes = 'Excellent finish'<br/>door.qc_inspected_at = NOW<br/>door.qc_inspector_id = 5]
    D1_Check -->|No| D1_Fail[door.qc_passed = false<br/>door.qc_notes = 'Defect found'<br/>door.qc_inspected_at = NOW]

    Check -->|Door D2| D2_Check{Quality OK?}
    D2_Check -->|Yes| D2_Pass[door.qc_passed = true]
    D2_Check -->|No| D2_Fail[door.qc_passed = false<br/>door.qc_notes = 'Chip on bottom rail']

    D1_Pass --> Ready1[Door D1 ready for installation]
    D2_Pass --> Ready2[Door D2 ready for installation]

    D1_Fail --> AutoTask1[Auto-create task:<br/>'Rework door D1'<br/>Assign: Levi<br/>Priority: High]
    D2_Fail --> AutoTask2[Auto-create task:<br/>'Rework door D2 - chip repair'<br/>Assign: Levi<br/>Priority: High]

    AutoTask1 --> Rework1[Levi fixes door D1]
    AutoTask2 --> Rework2[Levi fixes door D2]

    Rework1 --> Retest1{Re-inspect D1}
    Rework2 --> Retest2{Re-inspect D2}

    Retest1 -->|Pass| D1_Pass
    Retest1 -->|Fail again| Scrap1{Salvageable?}

    Retest2 -->|Pass| D2_Pass
    Retest2 -->|Fail again| Scrap2{Salvageable?}

    Scrap1 -->|Yes| Rework1
    Scrap1 -->|No| ScrapTask1[Create task:<br/>Fabricate replacement D1]

    Scrap2 -->|Yes| Rework2
    Scrap2 -->|No| ScrapTask2[Create task:<br/>Fabricate replacement D2]

    style D1_Pass fill:#c8e6c9
    style D2_Pass fill:#c8e6c9
    style D1_Fail fill:#ffccbc
    style D2_Fail fill:#ffccbc
    style AutoTask1 fill:#fff9c4
    style AutoTask2 fill:#fff9c4
    style ScrapTask1 fill:#ffccbc
    style ScrapTask2 fill:#ffccbc
```

---

## Diagram 8: Complete 7-Level Cabinet Hierarchy

```mermaid
graph TB
    subgraph Level 1: Project
        Project[Sarah Martinez<br/>Kitchen Renovation<br/><br/>projects_projects<br/>id: 1<br/>name: 'Sarah Martinez Kitchen'<br/>project_number: 'PRJ-2025-001'<br/>total_value: $85,000]
    end

    subgraph Level 2: Rooms
        Project --> Room1[Kitchen<br/><br/>projects_rooms<br/>id: 5<br/>name: 'Kitchen'<br/>room_type: 'kitchen']
        Project --> Room2[Pantry<br/><br/>projects_rooms<br/>id: 6<br/>name: 'Butler Pantry']
    end

    subgraph Level 3: Room Locations
        Room1 --> Loc1[Island<br/><br/>projects_room_locations<br/>id: 8<br/>name: 'Center Island'<br/>position: 'Center']
        Room1 --> Loc2[North Wall<br/><br/>projects_room_locations<br/>id: 9<br/>name: 'North Wall Uppers']
        Room1 --> Loc3[South Wall<br/><br/>projects_room_locations<br/>id: 10<br/>name: 'South Wall Base']
    end

    subgraph Level 4: Cabinet Runs
        Loc1 --> Run1[Island Base Cabinets<br/><br/>projects_cabinet_runs<br/>id: 3<br/>run_name: 'Island Base Run'<br/>total_linear_feet: 8.0<br/>sequence_order: 1]
        Loc2 --> Run2[Upper Wall Cabinets<br/><br/>projects_cabinet_runs<br/>id: 4<br/>total_linear_feet: 12.5]
    end

    subgraph Level 5: Cabinets
        Run1 --> Cab1[B36 Sink Base<br/><br/>projects_cabinet_specifications<br/>id: 123<br/>product_id: 456<br/>cabinet_name: 'B36'<br/>width_inches: 36<br/>height_inches: 34.5<br/>depth_inches: 24]
        Run1 --> Cab2[B24 Base<br/><br/>projects_cabinet_specifications<br/>id: 124<br/>product_id: 457<br/>cabinet_name: 'B24']
        Run1 --> Cab3[B18 Base<br/><br/>projects_cabinet_specifications<br/>id: 125<br/>cabinet_name: 'B18']
    end

    subgraph Level 6: Sections
        Cab1 --> Sec1[Door Opening Section<br/><br/>projects_cabinet_sections<br/>id: 5<br/>section_number: 1<br/>section_type: 'door_opening'<br/>width_inches: 36<br/>height_inches: 28<br/>component_count: 2]
        Cab1 --> Sec2[Pullout Section<br/><br/>projects_cabinet_sections<br/>id: 6<br/>section_number: 2<br/>section_type: 'pullout_area'<br/>component_count: 1]
    end

    subgraph Level 7: Components - 4 Types
        Sec1 --> Door1[Door D1<br/><br/>projects_doors<br/>id: 45<br/>product_id: 789<br/>door_number: 1<br/>door_name: 'D1'<br/>width: 17.5"<br/>height: 28"<br/>hinge_type: 'full_overlay'<br/><br/>Production:<br/>cnc_cut_at: 2025-12-15<br/>finished_at: 2025-12-22<br/>qc_passed: true]

        Sec1 --> Door2[Door D2<br/><br/>projects_doors<br/>id: 46<br/>product_id: 789<br/>door_number: 2<br/>door_name: 'D2'<br/>width: 17.5"<br/>height: 28"<br/><br/>Production:<br/>cnc_cut_at: 2025-12-15<br/>qc_passed: false<br/>qc_notes: 'Chip on rail']

        Sec2 --> Pullout1[Pullout P1<br/><br/>projects_pullouts<br/>id: 1<br/>product_id: 850<br/>pullout_number: 1<br/>pullout_name: 'P1'<br/>pullout_type: 'trash'<br/>manufacturer: 'Rev-A-Shelf'<br/>model: '5149-18DM-217'<br/><br/>Procurement:<br/>ordered_at: 2025-12-15<br/>received_at: 2025-12-21]

        Cab2 --> Sec3[Drawer Stack Section<br/><br/>projects_cabinet_sections<br/>id: 7<br/>section_type: 'drawer_stack'<br/>component_count: 3]

        Sec3 --> Drawer1[Drawer DR1<br/><br/>projects_drawers<br/>id: 23<br/>product_id: 801<br/>drawer_number: 1<br/>drawer_name: 'DR1 - Top'<br/>drawer_position: 'top'<br/>front_width: 22"<br/>front_height: 6"<br/><br/>Box Details:<br/>box_material: 'maple'<br/>slide_type: 'blum_undermount']

        Sec3 --> Drawer2[Drawer DR2<br/><br/>projects_drawers<br/>id: 24<br/>drawer_name: 'DR2 - Middle']

        Sec3 --> Drawer3[Drawer DR3<br/><br/>projects_drawers<br/>id: 25<br/>drawer_name: 'DR3 - Bottom']

        Cab3 --> Sec4[Open Shelving Section<br/><br/>projects_cabinet_sections<br/>id: 8<br/>section_type: 'open_shelving'<br/>component_count: 2]

        Sec4 --> Shelf1[Shelf S1<br/><br/>projects_shelves<br/>id: 12<br/>product_id: 820<br/>shelf_number: 1<br/>shelf_name: 'S1'<br/>shelf_type: 'adjustable'<br/>width: 17"<br/>depth: 23"<br/>material: 'plywood'<br/>edge_treatment: 'edge_banded']

        Sec4 --> Shelf2[Shelf S2<br/><br/>projects_shelves<br/>id: 13<br/>shelf_name: 'S2']
    end

    %% Color coding by level
    style Project fill:#e1f5ff,stroke:#01579b,stroke-width:3px
    style Room1 fill:#f3e5f5,stroke:#4a148c,stroke-width:2px
    style Room2 fill:#f3e5f5,stroke:#4a148c,stroke-width:2px
    style Loc1 fill:#e8f5e9,stroke:#1b5e20,stroke-width:2px
    style Loc2 fill:#e8f5e9,stroke:#1b5e20,stroke-width:2px
    style Loc3 fill:#e8f5e9,stroke:#1b5e20,stroke-width:2px
    style Run1 fill:#fff3e0,stroke:#e65100,stroke-width:2px
    style Run2 fill:#fff3e0,stroke:#e65100,stroke-width:2px
    style Cab1 fill:#fce4ec,stroke:#880e4f,stroke-width:2px
    style Cab2 fill:#fce4ec,stroke:#880e4f,stroke-width:2px
    style Cab3 fill:#fce4ec,stroke:#880e4f,stroke-width:2px
    style Sec1 fill:#e0f2f1,stroke:#004d40,stroke-width:2px
    style Sec2 fill:#e0f2f1,stroke:#004d40,stroke-width:2px
    style Sec3 fill:#e0f2f1,stroke:#004d40,stroke-width:2px
    style Sec4 fill:#e0f2f1,stroke:#004d40,stroke-width:2px

    %% Color coding by component type
    style Door1 fill:#ffccbc,stroke:#bf360c,stroke-width:2px
    style Door2 fill:#ffccbc,stroke:#bf360c,stroke-width:2px
    style Drawer1 fill:#b2dfdb,stroke:#00695c,stroke-width:2px
    style Drawer2 fill:#b2dfdb,stroke:#00695c,stroke-width:2px
    style Drawer3 fill:#b2dfdb,stroke:#00695c,stroke-width:2px
    style Shelf1 fill:#c5e1a5,stroke:#33691e,stroke-width:2px
    style Shelf2 fill:#c5e1a5,stroke:#33691e,stroke-width:2px
    style Pullout1 fill:#ffe082,stroke:#f57f17,stroke-width:2px
```

### Hierarchy Summary

**Level 1: Project** (Blue)
- `projects_projects` - Top level container
- Example: Sarah Martinez Kitchen Renovation ($85,000 project)

**Level 2: Room** (Purple)
- `projects_rooms` - Physical rooms in the project
- Example: Kitchen, Butler Pantry

**Level 3: Room Location** (Green)
- `projects_room_locations` - Specific areas within rooms
- Example: Center Island, North Wall, South Wall

**Level 4: Cabinet Run** (Orange)
- `projects_cabinet_runs` - Groups of adjacent cabinets
- Example: Island Base Run (8.0 linear feet)

**Level 5: Cabinet** (Pink)
- `projects_cabinet_specifications` - Individual cabinet specifications
- Example: B36 Sink Base (36" wide, 34.5" high, 24" deep)
- Links to `products_products` via `product_id`

**Level 6: Section** (Teal)
- `projects_cabinet_sections` - Subdivisions within cabinets
- Types: door_opening, drawer_stack, open_shelving, pullout_area
- Example: Door Opening Section (2 doors)

**Level 7: Component** (4 Types)
- **Doors** (Coral) - `projects_doors`
  - Example: D1 - Full overlay door, CNC cut, QC passed

- **Drawers** (Mint) - `projects_drawers`
  - Example: DR1 - Top drawer with Blum undermount slides

- **Shelves** (Light Green) - `projects_shelves`
  - Example: S1 - Adjustable shelf with edge banding

- **Pullouts** (Yellow) - `projects_pullouts`
  - Example: P1 - Rev-A-Shelf trash pullout

### Key Relationships

```sql
-- Navigate from Project to Component
SELECT doors.*
FROM projects_projects
JOIN projects_rooms ON projects_rooms.project_id = projects_projects.id
JOIN projects_room_locations ON projects_room_locations.room_id = projects_rooms.id
JOIN projects_cabinet_runs ON projects_cabinet_runs.room_location_id = projects_room_locations.id
JOIN projects_cabinet_specifications ON projects_cabinet_specifications.cabinet_run_id = projects_cabinet_runs.id
JOIN projects_cabinet_sections ON projects_cabinet_sections.cabinet_specification_id = projects_cabinet_specifications.id
JOIN projects_doors AS doors ON doors.section_id = projects_cabinet_sections.id
WHERE projects_projects.id = 1;
```

### Task Assignment at Any Level

```sql
-- Project-level task
INSERT INTO projects_tasks (project_id, title)
VALUES (1, 'Design review for entire project');

-- Cabinet-level task
INSERT INTO projects_tasks (project_id, cabinet_specification_id, title)
VALUES (1, 123, 'Assemble cabinet B36');

-- Section-level task
INSERT INTO projects_tasks (project_id, section_id, title)
VALUES (1, 5, 'Install door opening hardware');

-- Component-level task (door)
INSERT INTO projects_tasks (project_id, component_type, component_id, title)
VALUES (1, 'door', 45, 'CNC cut door D1');

-- Component-level task (drawer)
INSERT INTO projects_tasks (project_id, component_type, component_id, title)
VALUES (1, 'drawer', 23, 'Install drawer slide DR1');
```

---

## How to View These Diagrams

### Option 1: GitHub/GitLab
- Push this file to your repo
- GitHub/GitLab will render Mermaid automatically

### Option 2: VS Code
- Install "Markdown Preview Mermaid Support" extension
- Open this file and press `Ctrl+Shift+V` (preview)

### Option 3: Mermaid Live Editor
- Copy any diagram code
- Paste into https://mermaid.live
- Export as PNG/SVG

### Option 4: FilamentPHP Documentation
- Use these diagrams in your internal docs
- Export as images for training materials

---

## Next Step: Run Migrations

**Ready to make this real?** 🚀

```bash
DB_CONNECTION=mysql php artisan migrate
```

This will create all the tables shown in these diagrams!

---

**Document Created:** 2025-11-21
**Diagrams:** 7 Mermaid visualizations of complete workflow
**Purpose:** Visual representation of use case from quote to completion
