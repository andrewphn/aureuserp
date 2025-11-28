# Complete Table Relationships Map

**Date:** 2025-11-21
**Purpose:** Show how ALL tables link together - Projects, Inventory, Products, Sales, Tasks

---

## Executive Summary

**✅ YES** - Extending `projects_tasks` is the **BEST approach** because:

1. **Central Hub:** Tasks become the central coordination point for ALL work
2. **Polymorphic Power:** One task can link to ANY level of the hierarchy
3. **Unified Tracking:** Production, sales, inventory, and project management all tracked through tasks
4. **Flexible Assignment:** Assign tasks to customers, orders, projects, cabinets, components, or employees

---

## Complete Relationship Diagram

```
┌─────────────────────────────────────────────────────────────────────────┐
│                         CUSTOMERS & SALES                                │
└─────────────────────────────────────────────────────────────────────────┘
                                    │
        partners_partners (customers)
                │
                │ customer_id
                ▼
        sales_orders (quotes/orders)
                │
                │ order_id
                ▼
┌─────────────────────────────────────────────────────────────────────────┐
│                            PROJECTS                                      │
└─────────────────────────────────────────────────────────────────────────┘
                │
    projects_projects ◄──────────────┐
                │                     │
                │ project_id          │
                ▼                     │
    projects_rooms                   │
                │                     │
                │ room_id             │
                ▼                     │
    projects_room_locations          │
                │                     │
                │ location_id         │
                ▼                     │
    projects_cabinet_runs            │
                │                     │
                │ run_id              │
                ▼                     │
    projects_cabinet_specifications  │
                │                     │
                │ cabinet_id          │
                ├─────────────────────┘
                │
                ├── product_id ──────────┐
                │                        │
                │                        ▼
                ▼                 products_products ◄─────┐
    projects_cabinet_sections            │                │
                │                        │                │
                │ section_id             │ product_id     │
                ▼                        │                │
        ┌───────┴───────┐              │                │
        │               │              │                │
        ▼               ▼              ▼                │
projects_doors  projects_drawers  projects_shelves     │
        │               │              │                │
        │               │              │                │
        └───────┬───────┴──────┬───────┘                │
                │              │                        │
                │ product_id   │ product_id             │
                └──────────────┴────────────────────────┘
                                │
                                │
    projects_pullouts ──────────┘
                │
                │ product_id
                │
┌───────────────▼─────────────────────────────────────────────────────────┐
│                      INVENTORY & PRODUCTS                                │
└─────────────────────────────────────────────────────────────────────────┘
                │
    products_products
                │
                ├── category_id ──► products_categories
                ├── uom_id ──────► unit_of_measures
                ├── company_id ──► companies
                │
                │
    ┌───────────┴────────────┐
    │                        │
    ▼                        ▼
products_product_suppliers  inventories_warehouses
    │                        │
    │ supplier_id            │ warehouse_id
    ▼                        ▼
partners_partners       inventories_stock_moves
(suppliers)

┌─────────────────────────────────────────────────────────────────────────┐
│                         TASKS (CENTRAL HUB)                              │
└─────────────────────────────────────────────────────────────────────────┘

    projects_tasks
        │
        ├── project_id ──────────► projects_projects
        ├── room_id ─────────────► projects_rooms
        ├── location_id ─────────► projects_room_locations
        ├── run_id ──────────────► projects_cabinet_runs
        ├── cabinet_id ──────────► projects_cabinet_specifications
        ├── section_id ──────────► projects_cabinet_sections
        │
        ├── component_type ──┐
        └── component_id ────┼──► projects_doors
                             ├──► projects_drawers
                             ├──► projects_shelves
                             └──► projects_pullouts
        │
        ├── assigned_to ─────────► users (employees)
        ├── created_by ──────────► users
        │
        └── taskable_type ───┐
            taskable_id ─────┼──► ANY model (polymorphic)
                             ├──► sales_orders
                             ├──► partners_partners
                             └──► Any other entity
```

---

## Key Relationships Explained

### 1. **Customer → Sales → Project Flow**

```sql
-- Customer places order
partners_partners (customer_id)
    ↓
sales_orders (order_id, customer_id)
    ↓
projects_projects (project_id, order_id, customer_id)
```

**Purpose:** Track the business relationship from quote to delivery

**Example:**
```
Customer: "John Smith"
  → Order: #12345 "Montgomery Street Kitchen"
    → Project: "Kitchen Renovation - 392 N Montgomery St"
      → Room: "Kitchen"
        → Location: "Island"
          → Cabinet Run: "Island Base Cabinets"
            → Cabinet: "B36 - 36" Sink Base"
              → Door: "D1 - Left blind inset door"
```

---

### 2. **Product Inventory → Components**

```sql
products_products (product_id)
    ↓
projects_cabinet_specifications (cabinet_id, product_id)
projects_doors (door_id, product_id)
projects_drawers (drawer_id, product_id)
projects_shelves (shelf_id, product_id)
projects_pullouts (pullout_id, product_id)
```

**Purpose:**
- Track which inventory items are used
- Calculate material costs from inventory
- Deplete inventory when components are fabricated
- Trigger reordering when stock is low

**Example:**
```sql
-- Door D1 uses a door blank from inventory
Door D1 (id: 45)
  → product_id: 789
    → Product: "Shaker Door Blank - 3/4" Maple - 18x30"
      → cost: $45.00
      → quantity_on_hand: 12
      → supplier: "Hardwood Lumber Co."
```

**Inventory Depletion Flow:**
```sql
1. Door D1 is marked as cnc_cut_at = NOW()
2. Trigger decrements products_products.quantity_on_hand
3. If quantity_on_hand < reorder_point, create purchase order
4. Task created: "Order door blanks from supplier"
```

---

### 3. **Tasks → Everything (Polymorphic Hub)**

```sql
projects_tasks
    ├── Hierarchy Assignment (Specific Levels)
    │   ├── project_id → Work on entire project
    │   ├── room_id → Work on specific room
    │   ├── location_id → Work on specific location
    │   ├── run_id → Work on cabinet run
    │   ├── cabinet_id → Work on specific cabinet
    │   ├── section_id → Work on cabinet section
    │   └── component_type + component_id → Work on specific component
    │
    ├── Employee Assignment
    │   └── assigned_to → users (Levi, Aiden, etc.)
    │
    └── Polymorphic Assignment (ANY entity)
        ├── taskable_type = 'SalesOrder'
        ├── taskable_id = order_id
        └── Examples:
            - "Follow up on quote #12345"
            - "Ship completed cabinets for order #12345"
            - "Invoice customer for project"
```

**Why Tasks Extension is BEST:**

✅ **One Table to Rule Them All**
- Single source of truth for all work
- No duplicate task tracking in multiple places
- Unified reporting and dashboards

✅ **Maximum Flexibility**
- Can assign task to ANY level (project down to individual door)
- Can assign task to ANY entity (order, customer, supplier)
- Can assign to ANY employee or team

✅ **Complete Visibility**
```sql
-- See all work for a cabinet (including all its components)
SELECT * FROM projects_tasks
WHERE cabinet_id = 123
   OR (component_id IN (
       SELECT id FROM projects_doors WHERE cabinet_specification_id = 123
       UNION SELECT id FROM projects_drawers WHERE cabinet_specification_id = 123
       UNION SELECT id FROM projects_shelves WHERE cabinet_specification_id = 123
       UNION SELECT id FROM projects_pullouts WHERE cabinet_specification_id = 123
   ));
```

✅ **Production Timeline**
```sql
-- Workflow for Door D1
Task 1: "CNC cut door D1"
  → component_type='door', component_id=45
  → assigned_to=Levi
  → status='done'

Task 2: "Edge band door D1"
  → component_type='door', component_id=45
  → assigned_to=Aiden
  → status='in_progress'

Task 3: "Sand door D1"
  → component_type='door', component_id=45
  → assigned_to=Levi
  → status='pending'
  → dependencies=[Task 2]
```

---

## Real-World Example: Complete Flow

### Scenario: Build Cabinet B36 for Montgomery Street Kitchen

```
┌─────────────────────────────────────────────────────────────┐
│ STEP 1: CUSTOMER & SALES                                    │
└─────────────────────────────────────────────────────────────┘

Customer: "John & Sarah Smith" (partners_partners)
  │
  ▼
Quote: #12345 "Kitchen Renovation" (sales_orders)
  - Total: $45,000
  - Status: approved
  │
  ▼
Project: "392 N Montgomery St - Kitchen" (projects_projects)
  - project_id: 1
  - order_id: 12345
  - customer_id: 789

┌─────────────────────────────────────────────────────────────┐
│ STEP 2: PROJECT HIERARCHY                                   │
└─────────────────────────────────────────────────────────────┘

Room: "Kitchen" (projects_rooms)
  - room_id: 5
  │
  ▼
Location: "Island" (projects_room_locations)
  - location_id: 8
  │
  ▼
Cabinet Run: "Island Base Cabinets" (projects_cabinet_runs)
  - run_id: 3
  │
  ▼
Cabinet: "B36 - 36\" Sink Base" (projects_cabinet_specifications)
  - cabinet_id: 123
  - product_id: 456 → "Shaker Base Cabinet - 36x34.5x24"
  │
  ▼
Section 1: "Door Opening" (projects_cabinet_sections)
  - section_id: 5
  │
  ├─► Door D1: "Left blind inset door" (projects_doors)
  │   - door_id: 45
  │   - product_id: 789 → "Door Blank - Maple - 17.75x30"
  │
  └─► Door D2: "Right standard door" (projects_doors)
      - door_id: 46
      - product_id: 789 → "Door Blank - Maple - 17.75x30"

┌─────────────────────────────────────────────────────────────┐
│ STEP 3: INVENTORY LINKAGE                                   │
└─────────────────────────────────────────────────────────────┘

Product: "Door Blank - Maple - 17.75x30" (products_products)
  - product_id: 789
  - cost: $45.00
  - quantity_on_hand: 12
  - supplier_id: 999 → "Hardwood Lumber Co."
  │
  └─► Used by:
      - Door D1 (door_id: 45)
      - Door D2 (door_id: 46)

When doors are cut:
  - Deplete inventory: quantity_on_hand = 12 - 2 = 10
  - If quantity < reorder_point: Create purchase order task

┌─────────────────────────────────────────────────────────────┐
│ STEP 4: TASKS (UNIFYING EVERYTHING)                         │
└─────────────────────────────────────────────────────────────┘

Task #1: "CNC cut door D1 for B36"
  - task_id: 1001
  - project_id: 1
  - cabinet_id: 123
  - section_id: 5
  - component_type: 'door'
  - component_id: 45
  - assigned_to: Levi
  - status: 'done'
  - completed_at: 2025-11-20 14:30:00

Task #2: "CNC cut door D2 for B36"
  - task_id: 1002
  - component_type: 'door'
  - component_id: 46
  - assigned_to: Levi
  - status: 'done'

Task #3: "Edge band doors D1 & D2"
  - task_id: 1003
  - section_id: 5 (applies to whole section)
  - assigned_to: Aiden
  - status: 'in_progress'
  - dependencies: [1001, 1002]

Task #4: "Assemble cabinet B36"
  - task_id: 1004
  - cabinet_id: 123
  - assigned_to: Levi
  - status: 'pending'
  - dependencies: [all door/drawer tasks complete]

Task #5: "Install cabinet B36 on site"
  - task_id: 1005
  - location_id: 8 (Island)
  - assigned_to: Levi + Bryan
  - status: 'pending'

Task #6: "Invoice customer for project"
  - task_id: 1006
  - project_id: 1
  - taskable_type: 'SalesOrder'
  - taskable_id: 12345
  - assigned_to: Sadie
  - status: 'pending'
  - dependencies: [installation complete]
```

---

## Database Query Examples

### Query 1: Get All Work for Cabinet B36
```sql
SELECT t.*,
       u.name as assigned_to_name,
       CASE
         WHEN t.component_type = 'door' THEN d.door_name
         WHEN t.component_type = 'drawer' THEN dr.drawer_name
         WHEN t.component_type = 'shelf' THEN s.shelf_name
         WHEN t.component_type = 'pullout' THEN p.pullout_name
       END as component_name
FROM projects_tasks t
LEFT JOIN users u ON t.assigned_to = u.id
LEFT JOIN projects_doors d ON t.component_type = 'door' AND t.component_id = d.id
LEFT JOIN projects_drawers dr ON t.component_type = 'drawer' AND t.component_id = dr.id
LEFT JOIN projects_shelves s ON t.component_type = 'shelf' AND t.component_id = s.id
LEFT JOIN projects_pullouts p ON t.component_type = 'pullout' AND t.component_id = p.id
WHERE t.cabinet_id = 123
ORDER BY t.sort_order;
```

### Query 2: Calculate Project Costs from Inventory
```sql
SELECT
  cs.cabinet_name,
  SUM(COALESCE(p_cabinet.cost, 0)) as cabinet_material_cost,
  SUM(COALESCE(p_door.cost, 0)) as door_material_cost,
  SUM(COALESCE(p_drawer.cost, 0)) as drawer_material_cost,
  SUM(COALESCE(p_shelf.cost, 0)) as shelf_material_cost,
  SUM(COALESCE(p_pullout.cost, 0)) as pullout_material_cost,
  SUM(
    COALESCE(p_cabinet.cost, 0) +
    COALESCE(p_door.cost, 0) +
    COALESCE(p_drawer.cost, 0) +
    COALESCE(p_shelf.cost, 0) +
    COALESCE(p_pullout.cost, 0)
  ) as total_material_cost
FROM projects_cabinet_specifications cs
LEFT JOIN products_products p_cabinet ON cs.product_id = p_cabinet.id
LEFT JOIN projects_doors d ON d.cabinet_specification_id = cs.id
LEFT JOIN products_products p_door ON d.product_id = p_door.id
LEFT JOIN projects_drawers dr ON dr.cabinet_specification_id = cs.id
LEFT JOIN products_products p_drawer ON dr.product_id = p_drawer.id
LEFT JOIN projects_shelves s ON s.cabinet_specification_id = cs.id
LEFT JOIN products_products p_shelf ON s.product_id = p_shelf.id
LEFT JOIN projects_pullouts p ON p.cabinet_specification_id = cs.id
LEFT JOIN products_products p_pullout ON p.product_id = p_pullout.id
WHERE cs.project_id = 1
GROUP BY cs.id, cs.cabinet_name;
```

### Query 3: Track Inventory Usage
```sql
-- Which products are needed for upcoming projects?
SELECT
  p.name as product_name,
  p.quantity_on_hand,
  COUNT(d.id) as doors_needed,
  COUNT(dr.id) as drawers_needed,
  COUNT(s.id) as shelves_needed,
  COUNT(pu.id) as pullouts_needed,
  (COUNT(d.id) + COUNT(dr.id) + COUNT(s.id) + COUNT(pu.id)) as total_needed,
  p.quantity_on_hand - (COUNT(d.id) + COUNT(dr.id) + COUNT(s.id) + COUNT(pu.id)) as shortage
FROM products_products p
LEFT JOIN projects_doors d ON d.product_id = p.id AND d.cnc_cut_at IS NULL
LEFT JOIN projects_drawers dr ON dr.product_id = p.id AND dr.cnc_cut_at IS NULL
LEFT JOIN projects_shelves s ON s.product_id = p.id AND s.cnc_cut_at IS NULL
LEFT JOIN projects_pullouts pu ON pu.product_id = p.id AND pu.ordered_at IS NULL
WHERE p.quantity_on_hand IS NOT NULL
GROUP BY p.id, p.name, p.quantity_on_hand
HAVING total_needed > 0
ORDER BY shortage ASC;
```

---

## Benefits of This Architecture

### 1. **Unified Data Model**
- Customer → Order → Project → Components → Inventory
- Everything connected through foreign keys
- No duplicate data

### 2. **Task-Centric Workflow**
- Tasks link to ANY entity at ANY level
- One place to see all work
- Easy to assign, track, and report

### 3. **Inventory Integration**
- Real-time cost tracking
- Automatic depletion
- Reorder triggers
- Supplier linking

### 4. **Scalability**
- Add new component types easily
- Add new task types easily
- Add new product types easily

### 5. **Reporting Power**
```sql
-- Production Dashboard
SELECT
  COUNT(*) FILTER (WHERE status = 'pending') as pending_tasks,
  COUNT(*) FILTER (WHERE status = 'in_progress') as active_tasks,
  COUNT(*) FILTER (WHERE status = 'done') as completed_tasks,
  COUNT(DISTINCT project_id) as active_projects,
  COUNT(DISTINCT cabinet_id) as cabinets_in_progress,
  SUM(material_cost) as total_material_cost
FROM projects_tasks
WHERE assigned_to = :employee_id;
```

---

## Conclusion

✅ **YES - Extending `projects_tasks` is the BEST approach** because:

1. **Central Hub:** All work flows through tasks
2. **Complete Tracking:** From quote to delivery
3. **Inventory Integration:** Automatic cost and stock tracking
4. **Flexible Assignment:** Tasks can link to ANY entity
5. **Unified Reporting:** One table for all dashboards

The polymorphic design allows tasks to be the **"glue"** that connects:
- Sales (orders/quotes)
- Projects (hierarchy from project → component)
- Inventory (products/materials)
- Employees (assignments)
- Customers (relationships)

**This is the industry-standard approach used by modern ERP systems.**

---

**Document Created:** 2025-11-21
**Purpose:** Explain complete table relationships and task unification strategy
