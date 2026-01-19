# AureusERP API V1 Audit Report

**Generated:** 2026-01-19
**Auditor:** Claude Code
**Scope:** Complete audit of database tables vs V1 API endpoints

---

## Summary

| Category | Count |
|----------|-------|
| **Total Database Tables** | 274 |
| **Tables with V1 API** | 26 |
| **Tables without V1 API** | 248 |
| **API Coverage** | 9.5% |

---

## Existing V1 API Controllers (26 Resources)

### Projects Module (14 controllers)
| Controller | Table | Status |
|------------|-------|--------|
| ProjectController | `projects_projects` | ✅ Full CRUD |
| RoomController | `projects_rooms` | ✅ Full CRUD |
| RoomLocationController | `projects_room_locations` | ✅ Full CRUD |
| CabinetRunController | `projects_cabinet_runs` | ✅ Full CRUD |
| CabinetController | `projects_cabinets` | ✅ Full CRUD + calculate + cut-list |
| CabinetSectionController | `projects_cabinet_sections` | ✅ Full CRUD |
| DrawerController | `projects_drawers` | ✅ Full CRUD |
| DoorController | `projects_doors` | ✅ Full CRUD |
| ShelfController | `projects_shelves` | ✅ Full CRUD |
| PulloutController | `projects_pullouts` | ✅ Full CRUD |
| StretcherController | `projects_stretchers` | ✅ Full CRUD |
| FaceframeController | `projects_faceframes` | ✅ Full CRUD |
| TaskController | `projects_tasks` | ✅ Full CRUD |
| MilestoneController | `projects_milestones` | ✅ Full CRUD |

### Employees Module (3 controllers)
| Controller | Table | Status |
|------------|-------|--------|
| EmployeeController | `employees_employees` | ✅ Full CRUD |
| DepartmentController | `employees_departments` | ✅ Full CRUD |
| CalendarController | `employees_calendars` | ✅ Full CRUD |

### Inventory Module (4 controllers)
| Controller | Table | Status |
|------------|-------|--------|
| ProductController | `products_products` | ✅ Full CRUD |
| WarehouseController | `inventories_warehouses` | ✅ Full CRUD |
| LocationController | `inventories_locations` | ✅ Full CRUD |
| MoveController | `inventories_moves` | ✅ Full CRUD |

### Partners Module (1 controller)
| Controller | Table | Status |
|------------|-------|--------|
| PartnerController | `partners_partners` | ✅ Full CRUD |

### Special Controllers (4)
| Controller | Description |
|------------|-------------|
| BatchController | Bulk operations for all resources |
| WebhookController | Event subscriptions & delivery |
| RhinoExtractionController | CAD/Rhino integration |
| ApiInfoController | API discovery endpoint |

---

## Missing V1 APIs by Module (Priority Assessment)

### HIGH PRIORITY - Core Business Operations

#### Sales Module (0% coverage)
| Table | Model | Priority | Notes |
|-------|-------|----------|-------|
| `sales_orders` | Order | 🔴 HIGH | **Critical for n8n** - Sales order management |
| `sales_order_lines` | OrderLine | 🔴 HIGH | Line items for sales orders |
| `sales_order_line_items` | - | 🟡 MEDIUM | Order line details |
| `sales_order_tags` | - | 🟢 LOW | Tagging system |
| `sales_order_templates` | OrderTemplate | 🟡 MEDIUM | Order templates |
| `sales_order_template_products` | - | 🟢 LOW | Template products |
| `sales_teams` | Team | 🟡 MEDIUM | Sales team management |
| `sales_team_members` | TeamMember | 🟢 LOW | Team membership |
| `sales_tags` | Tag | 🟢 LOW | Sales tagging |

#### Purchases Module (0% coverage)
| Table | Model | Priority | Notes |
|-------|-------|----------|-------|
| `purchases_orders` | Order | 🔴 HIGH | **Critical for n8n** - Purchase orders |
| `purchases_order_lines` | - | 🔴 HIGH | PO line items |
| `purchases_requisitions` | Requisition | 🟡 MEDIUM | Purchase requests |
| `purchases_requisition_lines` | RequisitionLine | 🟡 MEDIUM | Request line items |
| `purchases_order_groups` | - | 🟢 LOW | Order grouping |

#### Invoices Module (0% coverage)
| Table | Model | Priority | Notes |
|-------|-------|----------|-------|
| `accounts_account_moves` | Move (Invoice) | 🔴 HIGH | **Critical** - Invoices & bills |
| `accounts_account_move_lines` | MoveLine | 🔴 HIGH | Invoice line items |
| `accounts_account_payments` | Payment | 🔴 HIGH | Payment records |
| `accounts_journals` | Journal | 🟡 MEDIUM | Accounting journals |
| `accounts_accounts` | Account | 🟡 MEDIUM | Chart of accounts |
| `accounts_taxes` | Tax | 🟡 MEDIUM | Tax configuration |
| `accounts_tax_groups` | TaxGroup | 🟢 LOW | Tax grouping |
| `accounts_payment_terms` | PaymentTerm | 🟢 LOW | Payment terms |
| `accounts_payment_methods` | PaymentMethod | 🟢 LOW | Payment methods |

### MEDIUM PRIORITY - Operational Efficiency

#### Projects Module - Missing Tables
| Table | Priority | Notes |
|-------|----------|-------|
| `projects_tags` | 🟡 MEDIUM | Project tagging |
| `projects_project_stages` | 🟡 MEDIUM | Project lifecycle stages |
| `projects_task_stages` | 🟡 MEDIUM | Task status stages |
| `projects_bom` | 🟡 MEDIUM | Bill of materials |
| `projects_change_orders` | 🟡 MEDIUM | Change order tracking |
| `projects_change_order_lines` | 🟡 MEDIUM | Change order details |
| `projects_fixed_dividers` | 🟢 LOW | Cabinet dividers |
| `projects_false_fronts` | 🟢 LOW | False front panels |
| `projects_door_presets` | 🟢 LOW | Door configurations |
| `projects_drawer_presets` | 🟢 LOW | Drawer configurations |
| `projects_shelf_presets` | 🟢 LOW | Shelf configurations |
| `projects_pullout_presets` | 🟢 LOW | Pullout configurations |
| `projects_production_estimates` | 🟡 MEDIUM | Production planning |
| `projects_material_reservations` | 🟡 MEDIUM | Material allocation |
| `projects_cnc_programs` | 🟢 LOW | CNC machine programs |
| `projects_cnc_program_parts` | 🟢 LOW | CNC part definitions |
| `projects_gates` | 🟢 LOW | Quality gates |
| `projects_gate_requirements` | 🟢 LOW | Gate requirements |
| `projects_gate_evaluations` | 🟢 LOW | Gate evaluations |

#### Employees Module - Missing Tables
| Table | Priority | Notes |
|-------|----------|-------|
| `employees_job_positions` | 🟡 MEDIUM | Job title definitions |
| `employees_skills` | 🟢 LOW | Skill definitions |
| `employees_skill_types` | 🟢 LOW | Skill categories |
| `employees_skill_levels` | 🟢 LOW | Skill proficiency |
| `employees_employee_skills` | 🟢 LOW | Employee skill assignments |
| `employees_work_locations` | 🟢 LOW | Work location definitions |
| `employees_departure_reasons` | 🟢 LOW | Termination reasons |
| `employees_employment_types` | 🟢 LOW | Employment classifications |
| `employees_employee_resumes` | 🟢 LOW | Resume/CV storage |
| `employees_categories` | 🟢 LOW | Employee categories |
| `employees_calendar_attendances` | 🟡 MEDIUM | Work schedule |
| `employees_calendar_leaves` | 🟡 MEDIUM | Time off tracking |

#### Inventory Module - Missing Tables
| Table | Priority | Notes |
|-------|----------|-------|
| `inventories_lots` | 🟡 MEDIUM | Lot/batch tracking |
| `inventories_packages` | 🟡 MEDIUM | Package tracking |
| `inventories_package_types` | 🟢 LOW | Package type definitions |
| `inventories_operations` | 🟡 MEDIUM | Inventory operations |
| `inventories_operation_types` | 🟢 LOW | Operation type definitions |
| `inventories_move_lines` | 🟡 MEDIUM | Move line details |
| `inventories_scraps` | 🟡 MEDIUM | Scrap/waste tracking |
| `inventories_routes` | 🟢 LOW | Inventory routes |
| `inventories_rules` | 🟢 LOW | Reorder rules |
| `inventories_order_points` | 🟡 MEDIUM | Reorder points |
| `inventories_storage_categories` | 🟢 LOW | Storage categorization |
| `inventories_tags` | 🟢 LOW | Inventory tagging |
| `inventories_product_quantities` | 🟡 MEDIUM | Stock levels |

#### Products Module - Missing Tables
| Table | Priority | Notes |
|-------|----------|-------|
| `products_categories` | 🟡 MEDIUM | Product categories |
| `products_attributes` | 🟡 MEDIUM | Product attributes |
| `products_attribute_options` | 🟢 LOW | Attribute values |
| `products_packagings` | 🟢 LOW | Packaging definitions |
| `products_tags` | 🟢 LOW | Product tagging |
| `products_product_suppliers` | 🟡 MEDIUM | Supplier info |
| `products_price_rules` | 🟡 MEDIUM | Pricing rules |
| `products_price_rule_items` | 🟢 LOW | Price rule details |
| `products_product_price_lists` | 🟡 MEDIUM | Price lists |

#### Partners Module - Missing Tables
| Table | Priority | Notes |
|-------|----------|-------|
| `partners_bank_accounts` | 🟡 MEDIUM | Banking info |
| `partners_industries` | 🟢 LOW | Industry classifications |
| `partners_tags` | 🟢 LOW | Partner tagging |
| `partners_titles` | 🟢 LOW | Contact titles |

### LOW PRIORITY - Reference Data & Internal

#### Time Off Module (0% coverage)
| Table | Priority | Notes |
|-------|----------|-------|
| `time_off_leaves` | 🟢 LOW | Leave requests |
| `time_off_leave_types` | 🟢 LOW | Leave type definitions |
| `time_off_leave_allocations` | 🟢 LOW | Leave balances |
| `time_off_leave_accrual_plans` | 🟢 LOW | Accrual rules |

#### Recruitment Module (0% coverage)
| Table | Priority | Notes |
|-------|----------|-------|
| `recruitments_applicants` | 🟢 LOW | Job applicants |
| `recruitments_candidates` | 🟢 LOW | Candidate profiles |
| `recruitments_stages` | 🟢 LOW | Hiring stages |

#### TCS Custom Tables (0% coverage)
| Table | Priority | Notes |
|-------|----------|-------|
| `tcs_materials` | 🟡 MEDIUM | TCS material library |
| `tcs_material_inventory_mappings` | 🟢 LOW | Material mappings |
| `tcs_portfolio_projects` | 🟢 LOW | Portfolio showcase |
| `tcs_team_members` | 🟢 LOW | Website team display |
| `tcs_services` | 🟢 LOW | Website services |
| `tcs_journals` | 🟢 LOW | Blog/journal |
| `tcs_faqs` | 🟢 LOW | FAQ content |

#### System Tables (No API Needed)
| Table | Notes |
|-------|-------|
| `migrations` | Laravel internal |
| `sessions` | Session storage |
| `jobs` | Queue system |
| `failed_jobs` | Queue failures |
| `cache` | Cache storage |
| `personal_access_tokens` | Sanctum tokens |
| `permissions` | Spatie permissions |
| `roles` | Spatie roles |
| `model_has_permissions` | Spatie pivot |
| `model_has_roles` | Spatie pivot |
| `role_has_permissions` | Spatie pivot |
| `media` | Spatie media library |
| `settings` | System settings |
| `plugins` | Plugin registry |

---

## Recommended Implementation Order

### Phase 1: Critical n8n Integration (HIGH PRIORITY)
1. **Sales Orders API** - `SalesOrderController`
2. **Purchase Orders API** - `PurchaseOrderController`
3. **Invoice/Bill API** - `InvoiceController`
4. **Payment API** - `PaymentController`

### Phase 2: Operational APIs (MEDIUM PRIORITY)
5. **Product Categories API** - `ProductCategoryController`
6. **Inventory Stock API** - `StockController`
7. **BOM API** - `BomController`
8. **Change Orders API** - `ChangeOrderController`
9. **Production Estimates API** - `ProductionEstimateController`

### Phase 3: Supporting APIs (LOW PRIORITY)
10. Tags APIs (Project, Product, Partner)
11. Presets APIs (Door, Drawer, Shelf)
12. Employee Skills/Calendar APIs
13. Recruitment APIs
14. Time Off APIs

---

## Immediate Action Items

### 1. Sales Orders API (Highest Priority)
```php
// Suggested endpoints
GET    /api/v1/sales-orders
POST   /api/v1/sales-orders
GET    /api/v1/sales-orders/{id}
PUT    /api/v1/sales-orders/{id}
DELETE /api/v1/sales-orders/{id}
GET    /api/v1/sales-orders/{id}/lines
POST   /api/v1/sales-orders/{id}/lines
POST   /api/v1/sales-orders/{id}/confirm
POST   /api/v1/sales-orders/{id}/cancel
```

### 2. Purchase Orders API
```php
// Suggested endpoints
GET    /api/v1/purchase-orders
POST   /api/v1/purchase-orders
GET    /api/v1/purchase-orders/{id}
PUT    /api/v1/purchase-orders/{id}
DELETE /api/v1/purchase-orders/{id}
GET    /api/v1/purchase-orders/{id}/lines
POST   /api/v1/purchase-orders/{id}/receive
```

### 3. Invoice API
```php
// Suggested endpoints
GET    /api/v1/invoices
POST   /api/v1/invoices
GET    /api/v1/invoices/{id}
PUT    /api/v1/invoices/{id}
POST   /api/v1/invoices/{id}/post
POST   /api/v1/invoices/{id}/pay
```

---

## Webhook Events to Add

Current webhook events cover:
- `project.*`, `room.*`, `cabinet.*`, `cabinet_run.*`
- `drawer.*`, `door.*`, `task.*`, `employee.*`
- `product.*`, `partner.*`

Missing webhook events for new APIs:
- `sales_order.*` - Order created/updated/confirmed/shipped
- `purchase_order.*` - PO created/updated/received
- `invoice.*` - Invoice created/posted/paid
- `payment.*` - Payment received/refunded
- `stock.*` - Stock level changes
- `bom.*` - BOM changes

---

## Technical Recommendations

### 1. Model Discovery
Many tables have corresponding models in `plugins/webkul/*/src/Models/`. Verify model existence before creating new controllers.

### 2. Validation Rules
Each new controller should define proper Laravel validation rules matching the database schema.

### 3. API Resources
Consider using Laravel API Resources for consistent response transformation.

### 4. Rate Limiting
Current rate limit is 60 requests/minute. Consider increasing for batch operations.

### 5. Scopes/Abilities
Add new token abilities for new resources:
- `sales:read`, `sales:write`
- `purchases:read`, `purchases:write`
- `invoices:read`, `invoices:write`

---

## Files to Create for Phase 1

```
app/Http/Controllers/Api/V1/
├── SalesOrderController.php
├── SalesOrderLineController.php
├── PurchaseOrderController.php
├── PurchaseOrderLineController.php
├── InvoiceController.php
├── InvoiceLineController.php
├── PaymentController.php
└── ProductCategoryController.php
```

---

*Report generated by Claude Code API Audit Tool*
