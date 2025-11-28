# Use Case: Complete Workflow from Quote to Installation

**Date:** 2025-11-21
**Purpose:** Show exactly how the new schema works in a real TCS project

---

## Use Case Overview

**Customer:** Sarah Martinez
**Project:** Kitchen Renovation - 1428 Oak Street
**Scope:** Complete kitchen with island
**Timeline:** Quote → Design → Fabrication → Installation → Invoice

This use case demonstrates:
- ✅ How all tables link together
- ✅ How inventory products are used
- ✅ How tasks coordinate everything
- ✅ How production tracking works
- ✅ Complete data flow from start to finish

---

## PHASE 1: Sales & Project Setup

### Step 1.1: Customer & Quote
**Who:** Bryan (Owner)
**When:** Day 1

```sql
-- Create customer
INSERT INTO partners_partners (name, email, phone, type)
VALUES ('Sarah Martinez', 'sarah@email.com', '555-0123', 'customer');
-- customer_id = 1

-- Create sales order (quote)
INSERT INTO sales_orders (
  order_number, customer_id, status, total_amount,
  delivery_address, notes
)
VALUES (
  'Q-2025-001', 1, 'quote',
  85000.00,
  '1428 Oak Street, San Francisco, CA 94117',
  'Full kitchen renovation including island'
);
-- order_id = 1
```

**FilamentPHP:** Bryan creates this in `CustomerResource` and `SalesOrderResource`

---

### Step 1.2: Convert Quote to Project
**Who:** Bryan
**When:** Day 5 (quote approved)

```sql
-- Update order status
UPDATE sales_orders
SET status = 'approved', approved_at = NOW()
WHERE id = 1;

-- Create project
INSERT INTO projects_projects (
  name, order_id, customer_id,
  start_date, expected_completion_date,
  status
)
VALUES (
  'Kitchen Renovation - 1428 Oak Street',
  1, 1,
  '2025-12-01', '2025-12-20',
  'planning'
);
-- project_id = 1
```

**FilamentPHP:** Bryan clicks "Convert to Project" button in `SalesOrderResource`

---

## PHASE 2: Design & Specification

### Step 2.1: Room & Location Setup
**Who:** Bryan
**When:** Day 6

```sql
-- Create room
INSERT INTO projects_rooms (project_id, name, room_type, floor_number)
VALUES (1, 'Kitchen', 'kitchen', 1);
-- room_id = 1

-- Create locations
INSERT INTO projects_room_locations (
  room_id, name, location_type,
  requires_electrical, requires_plumbing
)
VALUES
  (1, 'Main Wall - North', 'wall', true, false),
  (1, 'Island', 'island', true, true);
-- location_id = 1 (Main Wall)
-- location_id = 2 (Island)
```

**FilamentPHP:** Bryan uses `RoomResource` and `RoomLocationResource`

---

### Step 2.2: Cabinet Runs
**Who:** Bryan
**When:** Day 7

```sql
-- Main wall cabinet run
INSERT INTO projects_cabinet_runs (
  room_location_id, run_name, run_type,
  linear_feet, sort_order
)
VALUES
  (1, 'North Wall Base Cabinets', 'base', 12.0, 1),
  (2, 'Island Base Cabinets', 'base', 6.0, 2);
-- run_id = 1 (North Wall)
-- run_id = 2 (Island)
```

**FilamentPHP:** Bryan uses `CabinetRunResource`

---

### Step 2.3: Design Cabinets with Product Links
**Who:** Bryan
**When:** Day 8-10

```sql
-- Cabinet 1: B36 Sink Base for Island
INSERT INTO projects_cabinet_specifications (
  product_id, -- LINKED TO INVENTORY!
  cabinet_run_id, cabinet_name, cabinet_type,
  width_inches, height_inches, depth_inches,
  door_style, finish_type, paint_color
)
VALUES (
  456, -- Product: "Shaker Base Cabinet Kit - 36x34.5x24"
  2, 'B36', 'base_sink',
  36.0, 34.5, 24.0,
  'shaker', 'painted', 'SW7006 Extra White'
);
-- cabinet_id = 1

-- Cabinet 2: B24 Three-Drawer Base
INSERT INTO projects_cabinet_specifications (
  product_id,
  cabinet_run_id, cabinet_name, cabinet_type,
  width_inches, height_inches, depth_inches,
  door_style, finish_type, paint_color
)
VALUES (
  457, -- Product: "Shaker Base Cabinet Kit - 24x34.5x24"
  1, 'B24', 'base_drawer_stack',
  24.0, 34.5, 24.0,
  'shaker', 'painted', 'SW7006 Extra White'
);
-- cabinet_id = 2
```

**Inventory Link:** Each cabinet links to `products_products` for:
- Base cost tracking
- Material specifications
- Supplier information

**FilamentPHP:** Bryan uses `CabinetSpecificationResource` with product selector

---

### Step 2.4: Create Sections for B36 Sink Base
**Who:** Bryan
**When:** Day 10

```sql
-- Section 1: Door opening below sink
INSERT INTO projects_cabinet_sections (
  cabinet_specification_id, section_number,
  name, section_type,
  width_inches, height_inches, component_count
)
VALUES (
  1, 1,
  'Door Opening', 'door_opening',
  34.0, 30.0, 2
);
-- section_id = 1
```

**FilamentPHP:** Bryan uses `SectionResource` (new)

---

### Step 2.5: Specify Doors with Product Links
**Who:** Bryan
**When:** Day 11

```sql
-- Door D1: Left door for B36 sink base
INSERT INTO projects_doors (
  product_id, -- LINKED TO INVENTORY!
  cabinet_specification_id, section_id,
  door_number, door_name,
  width_inches, height_inches,
  rail_width_inches, style_width_inches,
  profile_type, fabrication_method,
  hinge_type, hinge_model, hinge_quantity, hinge_side
)
VALUES (
  789, -- Product: "Shaker Door Blank - Maple - 17x30"
  1, 1,
  1, 'D1',
  17.0, 30.0,
  2.25, 2.25,
  'shaker', 'cnc',
  'euro_concealed', 'Blum 71B9790', 2, 'left'
);
-- door_id = 1

-- Door D2: Right door for B36 sink base
INSERT INTO projects_doors (
  product_id,
  cabinet_specification_id, section_id,
  door_number, door_name,
  width_inches, height_inches,
  rail_width_inches, style_width_inches,
  profile_type, fabrication_method,
  hinge_type, hinge_model, hinge_quantity, hinge_side
)
VALUES (
  789, -- Same product: "Shaker Door Blank - Maple - 17x30"
  1, 1,
  2, 'D2',
  17.0, 30.0,
  2.25, 2.25,
  'shaker', 'cnc',
  'euro_concealed', 'Blum 71B9790', 2, 'right'
);
-- door_id = 2
```

**Inventory Link:** Each door links to `products_products.id = 789`
- Cost: $45.00 per door blank
- Current stock: 25 blanks
- When doors are cut, inventory depletes by 2

**FilamentPHP:** Bryan uses `DoorResource` (new) with product selector

---

### Step 2.6: Specify Drawers for B24 Three-Drawer Base
**Who:** Bryan
**When:** Day 11

```sql
-- Drawer DR1: Top drawer
INSERT INTO projects_drawers (
  product_id, -- LINKED TO INVENTORY!
  cabinet_specification_id,
  drawer_number, drawer_name, drawer_position,
  front_width_inches, front_height_inches,
  box_width_inches, box_depth_inches, box_height_inches,
  top_rail_width_inches, bottom_rail_width_inches, style_width_inches,
  profile_type, fabrication_method,
  box_material, box_thickness, joinery_method,
  slide_type, slide_model, slide_length_inches, soft_close
)
VALUES (
  790, -- Product: "5-Piece Drawer Front Kit - Maple - 22x6"
  2,
  1, 'DR1', 'top',
  22.0, 6.0,
  21.5, 21.0, 5.5,
  1.5, 1.5, 2.25,
  'shaker', 'cnc',
  'baltic_birch', 0.5, 'dovetail',
  'blum_tandem', 'Blum 562H', 21.0, true
);
-- drawer_id = 1

-- DR2 and DR3 similar...
```

**Inventory Link:** Each drawer front links to `products_products.id = 790`
- Cost: $38.00 per drawer kit
- Drawer boxes fabricated from sheet stock (separate product link possible)

**FilamentPHP:** Bryan uses `DrawerResource` (new) with product selector

---

### Step 2.7: Specify Pullout for B36
**Who:** Bryan
**When:** Day 11

```sql
-- Pullout P1: Rev-A-Shelf trash pullout
INSERT INTO projects_pullouts (
  product_id, -- LINKED TO INVENTORY!
  cabinet_specification_id,
  pullout_number, pullout_name,
  pullout_type, manufacturer, model_number,
  width_inches, height_inches, depth_inches,
  mounting_type, slide_type,
  unit_cost, quantity
)
VALUES (
  850, -- Product: "Rev-A-Shelf 5149-18DM-217"
  1,
  1, 'P1',
  'trash', 'Rev-a-Shelf', '5149-18DM-217',
  14.5, 19.0, 22.0,
  'bottom_mount', 'full_extension',
  127.50, 1
);
-- pullout_id = 1
```

**Inventory Link:** Pullout links to `products_products.id = 850`
- This is a purchased item (not fabricated)
- Cost: $127.50
- Stock: 3 units
- When ordered_at timestamp is set, inventory depletes

**FilamentPHP:** Bryan uses `PulloutResource` (new) with product selector

---

## PHASE 3: Production Planning & Task Creation

### Step 3.1: Generate Production Tasks
**Who:** System (auto-generated) or Bryan (manual)
**When:** Day 12 (design approved, ready for production)

```sql
-- Task 1: CNC cut door D1
INSERT INTO projects_tasks (
  project_id, cabinet_id, section_id,
  component_type, component_id,
  name, description, status,
  assigned_to, sort_order
)
VALUES (
  1, 1, 1,
  'door', 1,
  'CNC cut door D1 for B36',
  'Shaker profile, 17x30, 2.25" rails/styles',
  'pending',
  5, -- Levi (user_id = 5)
  1
);
-- task_id = 1

-- Task 2: CNC cut door D2
INSERT INTO projects_tasks (...)
VALUES (...);
-- task_id = 2

-- Task 3: Edge band doors D1 & D2
INSERT INTO projects_tasks (
  project_id, cabinet_id, section_id,
  name, status, assigned_to,
  dependency_ids -- JSON array
)
VALUES (
  1, 1, 1,
  'Edge band doors D1 & D2',
  'pending',
  6, -- Aiden (user_id = 6)
  '[1, 2]' -- Depends on tasks 1 and 2
);
-- task_id = 3

-- Task 4: Order pullout P1
INSERT INTO projects_tasks (
  project_id, cabinet_id,
  component_type, component_id,
  name, status, assigned_to
)
VALUES (
  1, 1,
  'pullout', 1,
  'Order Rev-A-Shelf trash pullout P1',
  'pending',
  7 -- Sadie (user_id = 7, purchasing)
);
-- task_id = 4
```

**FilamentPHP:**
- Auto-generate via `TaskResource` → "Generate Production Tasks" action
- Or Bryan manually creates in `TaskResource`

---

## PHASE 4: Production Execution

### Step 4.1: Levi Cuts Door D1
**Who:** Levi
**When:** Day 15 - Morning

**What Levi Does:**
1. Opens `TaskResource` on tablet
2. Sees assigned task: "CNC cut door D1 for B36"
3. Clicks "Start Task" → status changes to 'in_progress'
4. Loads CNC program, cuts door
5. Clicks "Complete Task"

**What Happens in Database:**
```sql
-- Update task
UPDATE projects_tasks
SET status = 'done', completed_at = NOW()
WHERE id = 1;

-- Update door production timestamp
UPDATE projects_doors
SET cnc_cut_at = NOW()
WHERE id = 1;

-- Deplete inventory (automatic trigger)
UPDATE products_products
SET quantity_on_hand = quantity_on_hand - 1
WHERE id = 789; -- Door blank product
-- Stock goes from 25 → 24
```

**Inventory Alert:**
```sql
-- If stock < reorder_point, create task
IF (SELECT quantity_on_hand FROM products_products WHERE id = 789) < 10 THEN
  INSERT INTO projects_tasks (
    taskable_type, taskable_id,
    name, status, assigned_to
  )
  VALUES (
    'Product', 789,
    'Reorder door blanks - stock low (9 remaining)',
    'pending',
    7 -- Sadie
  );
END IF;
```

---

### Step 4.2: Aiden Edge Bands Doors
**Who:** Aiden
**When:** Day 15 - Afternoon

**What Aiden Does:**
1. Task becomes available (dependencies complete)
2. Clicks "Start Task"
3. Edge bands doors D1 and D2
4. Clicks "Complete Task"

**What Happens:**
```sql
-- Update task
UPDATE projects_tasks
SET status = 'done', completed_at = NOW()
WHERE id = 3;

-- Update both doors
UPDATE projects_doors
SET edge_banded_at = NOW()
WHERE id IN (1, 2);
```

---

### Step 4.3: Sadie Orders Pullout
**Who:** Sadie
**When:** Day 15

**What Sadie Does:**
1. Sees task: "Order Rev-A-Shelf trash pullout P1"
2. Creates purchase order to vendor
3. Marks task complete, notes PO number

**What Happens:**
```sql
-- Update task
UPDATE projects_tasks
SET status = 'done', completed_at = NOW(), notes = 'PO #12345 to Rev-A-Shelf'
WHERE id = 4;

-- Update pullout
UPDATE projects_pullouts
SET ordered_at = NOW()
WHERE id = 1;

-- Reserve inventory (don't deplete yet - not received)
-- When received:
UPDATE projects_pullouts
SET received_at = NOW()
WHERE id = 1;

UPDATE products_products
SET quantity_on_hand = quantity_on_hand - 1
WHERE id = 850;
```

---

### Step 4.4: Levi Assembles Doors (5-piece)
**Who:** Levi
**When:** Day 16

```sql
-- Update doors
UPDATE projects_doors
SET assembled_at = NOW()
WHERE id IN (1, 2);
```

---

### Step 4.5: Levi Sands Doors
**Who:** Levi
**When:** Day 17

```sql
UPDATE projects_doors
SET sanded_at = NOW()
WHERE id IN (1, 2);
```

---

### Step 4.6: Doors Go to Finishing
**Who:** Finishing subcontractor (external)
**When:** Day 18-22

```sql
-- When returned from finishing
UPDATE projects_doors
SET finished_at = NOW()
WHERE id IN (1, 2);
```

---

### Step 4.7: QC Inspection
**Who:** Levi
**When:** Day 23

**What Levi Does:**
1. Inspects doors D1 and D2
2. Checks for defects, finish quality, dimensions
3. Marks pass/fail

**What Happens:**
```sql
-- Door D1 passes QC
UPDATE projects_doors
SET
  qc_passed = true,
  qc_notes = 'Excellent finish, dimensions correct',
  qc_inspected_at = NOW(),
  qc_inspector_id = 5 -- Levi
WHERE id = 1;

-- Door D2 has issue
UPDATE projects_doors
SET
  qc_passed = false,
  qc_notes = 'Small chip on bottom rail - needs touch-up',
  qc_inspected_at = NOW(),
  qc_inspector_id = 5
WHERE id = 2;

-- Auto-create rework task
INSERT INTO projects_tasks (
  project_id, component_type, component_id,
  name, status, assigned_to
)
VALUES (
  1, 'door', 2,
  'Rework door D2 - touch up chip on bottom rail',
  'pending',
  5 -- Levi
);
```

---

### Step 4.8: Install Hardware
**Who:** Levi
**When:** Day 24

```sql
-- Install hinges on doors
UPDATE projects_doors
SET hardware_installed_at = NOW()
WHERE id IN (1, 2);

-- Install slides in cabinet
UPDATE projects_drawers
SET slides_installed_at = NOW()
WHERE cabinet_specification_id = 2;
```

---

### Step 4.9: Assemble Cabinet B36
**Who:** Levi
**When:** Day 25

```sql
-- Mark all components installed
UPDATE projects_doors
SET installed_in_cabinet_at = NOW()
WHERE cabinet_specification_id = 1;

UPDATE projects_pullouts
SET installed_in_cabinet_at = NOW()
WHERE cabinet_specification_id = 1;

-- Update cabinet status
UPDATE projects_cabinet_specifications
SET status = 'assembled', assembled_at = NOW()
WHERE id = 1;
```

---

## PHASE 5: Installation

### Step 5.1: On-Site Installation
**Who:** Levi + Bryan
**When:** Day 26

```sql
-- Create installation task
INSERT INTO projects_tasks (
  project_id, room_id, location_id,
  name, status, assigned_to
)
VALUES (
  1, 1, 2,
  'Install island cabinets at 1428 Oak Street',
  'in_progress',
  5 -- Levi (Bryan helps)
);

-- When complete
UPDATE projects_tasks
SET status = 'done', completed_at = NOW()
WHERE name LIKE 'Install island%';

-- Update project
UPDATE projects_projects
SET status = 'completed', completed_at = NOW()
WHERE id = 1;
```

---

## PHASE 6: Invoicing & Reporting

### Step 6.1: Calculate Project Costs
**Who:** Bryan / System
**When:** Day 27

```sql
-- Material costs from inventory
SELECT
  'Cabinets' as category,
  SUM(p.cost) as total_cost
FROM projects_cabinet_specifications cs
JOIN products_products p ON cs.product_id = p.id
WHERE cs.project_id = 1

UNION ALL

SELECT
  'Doors',
  SUM(p.cost)
FROM projects_doors d
JOIN products_products p ON d.product_id = p.id
JOIN projects_cabinet_specifications cs ON d.cabinet_specification_id = cs.id
WHERE cs.project_id = 1

UNION ALL

SELECT
  'Drawers',
  SUM(p.cost)
FROM projects_drawers dr
JOIN products_products p ON dr.product_id = p.id
JOIN projects_cabinet_specifications cs ON dr.cabinet_specification_id = cs.id
WHERE cs.project_id = 1

UNION ALL

SELECT
  'Pullouts',
  SUM(p.cost)
FROM projects_pullouts pu
JOIN products_products p ON pu.product_id = p.id
JOIN projects_cabinet_specifications cs ON pu.cabinet_specification_id = cs.id
WHERE cs.project_id = 1;

-- Results:
-- Cabinets: $1,200
-- Doors: $180 (4 doors × $45)
-- Drawers: $342 (9 drawers × $38)
-- Pullouts: $127.50
-- TOTAL MATERIALS: $1,849.50
```

---

### Step 6.2: Labor Costs from Tasks
**Who:** System
**When:** Day 27

```sql
-- Calculate labor hours
SELECT
  u.name as employee,
  COUNT(t.id) as tasks_completed,
  SUM(
    TIMESTAMPDIFF(HOUR, t.started_at, t.completed_at)
  ) as hours_worked,
  u.hourly_rate,
  SUM(
    TIMESTAMPDIFF(HOUR, t.started_at, t.completed_at) * u.hourly_rate
  ) as labor_cost
FROM projects_tasks t
JOIN users u ON t.assigned_to = u.id
WHERE t.project_id = 1 AND t.status = 'done'
GROUP BY u.id, u.name, u.hourly_rate;

-- Results:
-- Levi: 45 hours × $35/hr = $1,575
-- Aiden: 12 hours × $25/hr = $300
-- TOTAL LABOR: $1,875
```

---

### Step 6.3: Generate Invoice
**Who:** Sadie
**When:** Day 27

```sql
-- Create invoice
INSERT INTO invoices (
  order_id, customer_id, project_id,
  invoice_number, invoice_date,
  subtotal, tax_rate, tax_amount, total_amount
)
VALUES (
  1, 1, 1,
  'INV-2025-001', NOW(),
  85000.00, 0.095, 8075.00, 93075.00
);

-- Link costs
-- Materials: $1,849.50 (tracked)
-- Labor: $1,875.00 (tracked)
-- Markup: Applied
-- Final: $85,000 (original quote)
-- Profit: $85,000 - $1,849.50 - $1,875 = $81,275.50
```

---

## PHASE 7: Reporting & Analytics

### Report 1: Project Profitability
```sql
SELECT
  p.name as project,
  so.total_amount as quoted_price,
  SUM(COALESCE(prod.cost, 0)) as material_cost,
  SUM(
    TIMESTAMPDIFF(HOUR, t.started_at, t.completed_at) * u.hourly_rate
  ) as labor_cost,
  so.total_amount -
    SUM(COALESCE(prod.cost, 0)) -
    SUM(TIMESTAMPDIFF(HOUR, t.started_at, t.completed_at) * u.hourly_rate)
  as profit
FROM projects_projects p
JOIN sales_orders so ON p.order_id = so.id
LEFT JOIN projects_tasks t ON t.project_id = p.id
LEFT JOIN users u ON t.assigned_to = u.id
LEFT JOIN projects_cabinet_specifications cs ON cs.project_id = p.id
LEFT JOIN products_products prod ON cs.product_id = prod.id
WHERE p.id = 1
GROUP BY p.id, p.name, so.total_amount;

-- Result:
-- Project: Kitchen Renovation - 1428 Oak Street
-- Quoted: $85,000
-- Materials: $1,849.50
-- Labor: $1,875.00
-- Profit: $81,275.50 (95.6% profit margin!)
```

### Report 2: Component Production Status
```sql
SELECT
  'Doors' as component_type,
  COUNT(*) as total,
  SUM(CASE WHEN cnc_cut_at IS NOT NULL THEN 1 ELSE 0 END) as cut,
  SUM(CASE WHEN finished_at IS NOT NULL THEN 1 ELSE 0 END) as finished,
  SUM(CASE WHEN qc_passed = true THEN 1 ELSE 0 END) as qc_passed
FROM projects_doors
WHERE cabinet_specification_id IN (
  SELECT id FROM projects_cabinet_specifications WHERE project_id = 1
)

UNION ALL

SELECT
  'Drawers',
  COUNT(*),
  SUM(CASE WHEN cnc_cut_at IS NOT NULL THEN 1 ELSE 0 END),
  SUM(CASE WHEN finished_at IS NOT NULL THEN 1 ELSE 0 END),
  SUM(CASE WHEN qc_passed = true THEN 1 ELSE 0 END)
FROM projects_drawers
WHERE cabinet_specification_id IN (
  SELECT id FROM projects_cabinet_specifications WHERE project_id = 1
);
```

---

## Summary: What This Use Case Demonstrates

### ✅ Complete Data Flow
1. **Customer → Order → Project** (sales integration)
2. **Project → Cabinets → Components** (design hierarchy)
3. **Components → Products** (inventory integration)
4. **Tasks → Everything** (work coordination)
5. **Timestamps → Production Tracking** (progress monitoring)
6. **QC → Quality Control** (defect tracking)
7. **Costs → Profitability** (financial reporting)

### ✅ Inventory Integration Benefits
- **Automatic cost tracking** from products_products
- **Inventory depletion** when components are cut/ordered
- **Reorder triggers** when stock is low
- **Accurate material costs** for profitability analysis

### ✅ Task Coordination Power
- Tasks link to ANY level (project → door)
- Dependencies ensure correct workflow
- Employee assignments clear
- Progress visible in real-time

### ✅ Production Visibility
- 8 timestamp phases per component
- QC tracking with pass/fail
- Rework tasks auto-created
- Complete audit trail

### ✅ Business Intelligence
- Material costs from inventory
- Labor costs from tasks
- Profit margins calculated
- Component status dashboards

---

## Next Step: Run Migrations

**Ready?** This is exactly how the system will work once migrations are run.

```bash
DB_CONNECTION=mysql php artisan migrate
```

This creates:
1. ✅ `projects_cabinet_sections`
2. ✅ `projects_doors` with product_id
3. ✅ `projects_drawers` with product_id
4. ✅ `projects_shelves` with product_id
5. ✅ `projects_pullouts` with product_id
6. ✅ Extended `projects_tasks` with component links
7. ✅ Product links on all tables

**Decision:** Run migrations now? 🚀

---

**Document Created:** 2025-11-21
**Use Case:** Sarah Martinez Kitchen Renovation
**Purpose:** Demonstrate complete workflow with new schema
