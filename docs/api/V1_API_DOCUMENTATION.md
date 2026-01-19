# AureusERP API V1 Documentation

## Overview

The V1 API provides RESTful endpoints for external integrations (n8n, mobile apps, third-party systems). All endpoints use token-based authentication via Laravel Sanctum.

**Base URL:** `https://your-domain.com/api/v1`

## Authentication

All API requests require a Bearer token in the Authorization header:

```
Authorization: Bearer {your-api-token}
```

### Creating an API Token

1. Log into the admin panel at `/admin`
2. Navigate to **Settings > API Tokens**
3. Click "Create Token"
4. Give it a name and select abilities/scopes
5. Copy the generated token (shown only once)

### Token Abilities (Scopes)

- `*` - Full access to all endpoints
- `projects:read` - Read-only access to projects
- `projects:write` - Create/update/delete projects
- `cabinets:read` - Read-only access to cabinets
- `cabinets:write` - Create/update/delete cabinets
- Similar patterns for other resources

---

## Common Features

### Pagination

All list endpoints support pagination:

| Parameter | Default | Max | Description |
|-----------|---------|-----|-------------|
| `page` | 1 | - | Page number |
| `per_page` | 25 | 100 | Items per page |

**Response includes:**
```json
{
  "pagination": {
    "total": 150,
    "count": 25,
    "per_page": 25,
    "current_page": 1,
    "total_pages": 6,
    "links": {
      "first": "...?page=1",
      "last": "...?page=6",
      "next": "...?page=2",
      "previous": null
    }
  }
}
```

### Filtering

Filter results using query parameters:

```
GET /api/v1/projects?filter[is_active]=1&filter[partner_id]=5
```

**Operators:**
- Exact match: `?filter[status]=active`
- Greater than: `?filter[id]=gt:100`
- Greater or equal: `?filter[id]=gte:100`
- Less than: `?filter[id]=lt:100`
- Less or equal: `?filter[id]=lte:100`
- Not equal: `?filter[status]=ne:cancelled`
- Null values: `?filter[deleted_at]=null`
- LIKE search: `?filter[name]=*kitchen*`

### Sorting

Sort results using the `sort` parameter:

```
GET /api/v1/projects?sort=name           # Ascending
GET /api/v1/projects?sort=-created_at    # Descending (prefix with -)
GET /api/v1/projects?sort=-created_at,name  # Multiple fields
```

### Search

Full-text search across searchable fields:

```
GET /api/v1/projects?search=kitchen
```

### Including Relations

Eager-load related data:

```
GET /api/v1/projects/10?include=rooms,partner,cabinets
GET /api/v1/cabinets?include=cabinetRun,drawers,doors
```

---

## Response Format

### Success Response

```json
{
  "success": true,
  "message": "Resource retrieved",
  "data": { ... },
  "timestamp": "2026-01-19T12:00:00-05:00"
}
```

### List Response

```json
{
  "success": true,
  "message": "Projects retrieved",
  "data": [ ... ],
  "pagination": { ... },
  "timestamp": "2026-01-19T12:00:00-05:00"
}
```

### Error Response

```json
{
  "success": false,
  "message": "Validation failed",
  "errors": {
    "name": ["The name field is required."]
  },
  "timestamp": "2026-01-19T12:00:00-05:00"
}
```

---

## Endpoints

### Projects

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/projects` | List all projects |
| POST | `/projects` | Create a project |
| GET | `/projects/{id}` | Get a project |
| PUT | `/projects/{id}` | Update a project |
| DELETE | `/projects/{id}` | Delete a project |

**Filterable fields:** `id`, `is_active`, `is_converted`, `stage_id`, `partner_id`, `company_id`, `user_id`, `project_type`, `visibility`

**Searchable fields:** `name`, `project_number`, `draft_number`, `description`

**Sortable fields:** `id`, `name`, `project_number`, `created_at`, `updated_at`, `start_date`, `end_date`

**Includable relations:** `rooms`, `cabinets`, `partner`, `creator`, `user`, `stage`, `company`, `tags`, `milestones`, `tasks`

**Create/Update fields:**
```json
{
  "name": "Kitchen Renovation",
  "partner_id": 5,
  "project_type": "residential",
  "description": "Full kitchen remodel",
  "start_date": "2026-02-01",
  "end_date": "2026-04-01",
  "is_active": true
}
```

---

### Rooms

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/rooms` | List all rooms |
| GET | `/projects/{id}/rooms` | List rooms for a project |
| POST | `/projects/{id}/rooms` | Create room in a project |
| GET | `/rooms/{id}` | Get a room |
| PUT | `/rooms/{id}` | Update a room |
| DELETE | `/rooms/{id}` | Delete a room |

**Filterable fields:** `id`, `project_id`, `name`

**Searchable fields:** `name`, `description`

**Includable relations:** `project`, `locations`, `cabinetRuns`

---

### Cabinets

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/cabinets` | List all cabinets |
| GET | `/cabinet-runs/{id}/cabinets` | List cabinets in a run |
| POST | `/cabinet-runs/{id}/cabinets` | Create cabinet in a run |
| GET | `/cabinets/{id}` | Get a cabinet |
| PUT | `/cabinets/{id}` | Update a cabinet |
| DELETE | `/cabinets/{id}` | Delete a cabinet |

**Filterable fields:** `id`, `cabinet_run_id`, `cabinet_number`, `door_count`, `drawer_count`

**Searchable fields:** `cabinet_number`, `notes`

**Sortable fields:** `id`, `cabinet_number`, `length_inches`, `depth_inches`, `height_inches`, `created_at`

**Includable relations:** `cabinetRun`, `sections`, `drawers`, `doors`, `stretchers`, `faceframes`, `shelves`

**Create/Update fields:**
```json
{
  "cabinet_number": "B24-1",
  "length_inches": 24.0,
  "depth_inches": 24.0,
  "height_inches": 34.75,
  "door_count": 2,
  "drawer_count": 0
}
```

---

### Drawers

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/drawers` | List all drawers |
| GET | `/sections/{id}/drawers` | List drawers in a section |
| POST | `/sections/{id}/drawers` | Create drawer in a section |
| GET | `/drawers/{id}` | Get a drawer |
| PUT | `/drawers/{id}` | Update a drawer |
| DELETE | `/drawers/{id}` | Delete a drawer |

---

### Doors

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/doors` | List all doors |
| GET | `/sections/{id}/doors` | List doors in a section |
| POST | `/sections/{id}/doors` | Create door in a section |
| GET | `/doors/{id}` | Get a door |
| PUT | `/doors/{id}` | Update a door |
| DELETE | `/doors/{id}` | Delete a door |

---

### Stretchers

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/stretchers` | List all stretchers |
| GET | `/cabinets/{id}/stretchers` | List stretchers for a cabinet |
| POST | `/cabinets/{id}/stretchers` | Create stretcher for a cabinet |
| GET | `/stretchers/{id}` | Get a stretcher |
| PUT | `/stretchers/{id}` | Update a stretcher |
| DELETE | `/stretchers/{id}` | Delete a stretcher |

---

### Faceframes

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/faceframes` | List all faceframes |
| GET | `/cabinets/{id}/faceframes` | List faceframes for a cabinet |
| POST | `/cabinets/{id}/faceframes` | Create faceframe for a cabinet |
| GET | `/faceframes/{id}` | Get a faceframe |
| PUT | `/faceframes/{id}` | Update a faceframe |
| DELETE | `/faceframes/{id}` | Delete a faceframe |

---

### Partners (Customers/Vendors)

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/partners` | List all partners |
| POST | `/partners` | Create a partner |
| GET | `/partners/{id}` | Get a partner |
| PUT | `/partners/{id}` | Update a partner |
| DELETE | `/partners/{id}` | Delete a partner |

**Filterable fields:** `id`, `sub_type`, `is_company`, `is_active`

**Searchable fields:** `name`, `email`, `phone`

**Includable relations:** `projects`, `contacts`, `company`

---

### Employees

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/employees` | List all employees |
| POST | `/employees` | Create an employee |
| GET | `/employees/{id}` | Get an employee |
| PUT | `/employees/{id}` | Update an employee |
| DELETE | `/employees/{id}` | Delete an employee |

**Filterable fields:** `id`, `department_id`, `user_id`, `is_active`

**Searchable fields:** `name`, `work_email`, `job_title`

**Includable relations:** `department`, `user`, `calendar`

---

### Products

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/products` | List all products |
| POST | `/products` | Create a product |
| GET | `/products/{id}` | Get a product |
| PUT | `/products/{id}` | Update a product |
| DELETE | `/products/{id}` | Delete a product |

**Filterable fields:** `id`, `type`, `category_id`, `is_active`

**Searchable fields:** `name`, `sku`, `description`

---

### Batch Operations

Perform multiple operations in a single request:

```
POST /api/v1/batch/{resource}
```

**Supported resources:** `projects`, `rooms`, `cabinets`, `drawers`, `doors`, `partners`, `employees`, `products`

**Request body:**
```json
{
  "operation": "create",
  "data": [
    { "name": "Project 1" },
    { "name": "Project 2" }
  ]
}
```

**Operations:**
- `create` - Create multiple records
- `update` - Update multiple records (requires `id` in each item)
- `delete` - Delete multiple records (requires `id` in each item)

**Example - Batch Update:**
```json
{
  "operation": "update",
  "data": [
    { "id": 1, "is_active": false },
    { "id": 2, "is_active": false }
  ]
}
```

**Example - Batch Delete:**
```json
{
  "operation": "delete",
  "data": [
    { "id": 1 },
    { "id": 2 }
  ]
}
```

---

### Webhooks

Subscribe to events and receive real-time notifications.

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/webhooks` | List your subscriptions |
| POST | `/webhooks/subscribe` | Create a subscription |
| GET | `/webhooks/{id}` | Get a subscription |
| PUT | `/webhooks/{id}` | Update a subscription |
| DELETE | `/webhooks/{id}` | Delete a subscription |
| GET | `/webhooks/events` | List available events |
| POST | `/webhooks/{id}/test` | Send a test webhook |
| GET | `/webhooks/{id}/deliveries` | View delivery history |

**Create subscription:**
```json
{
  "url": "https://your-server.com/webhook",
  "events": ["project.created", "project.updated"],
  "name": "My n8n Webhook",
  "secret": "your-secret-key-min-16-chars"
}
```

**Available events:**
- `project.created`, `project.updated`, `project.deleted`
- `room.created`, `room.updated`, `room.deleted`
- `cabinet.created`, `cabinet.updated`, `cabinet.deleted`
- `cabinet_run.created`, `cabinet_run.updated`, `cabinet_run.deleted`
- `drawer.created`, `drawer.updated`, `drawer.deleted`
- `door.created`, `door.updated`, `door.deleted`
- `task.created`, `task.updated`, `task.deleted`, `task.status_changed`
- `employee.created`, `employee.updated`, `employee.deleted`
- `product.created`, `product.updated`, `product.deleted`
- `partner.created`, `partner.updated`, `partner.deleted`

**Wildcards:**
- `*` - All events
- `project.*` - All project events
- `cabinet.*` - All cabinet events

**Webhook payload:**
```json
{
  "event": "project.created",
  "timestamp": "2026-01-19T12:00:00-05:00",
  "data": {
    "id": 123,
    "name": "New Kitchen Project",
    ...
  }
}
```

**Webhook headers:**
```
X-Webhook-Event: project.created
X-Webhook-Signature: sha256=abc123...
X-Webhook-Timestamp: 1737298800
```

**Verifying signatures:**
```php
$payload = file_get_contents('php://input');
$signature = hash_hmac('sha256', $payload, $yourSecret);
$valid = hash_equals($signature, $receivedSignature);
```

---

## Rate Limiting

API requests are rate limited to **60 requests per minute** per user.

Response headers include:
```
X-RateLimit-Limit: 60
X-RateLimit-Remaining: 45
```

When exceeded:
```json
{
  "message": "Too Many Attempts.",
  "retry_after": 30
}
```

---

## Error Codes

| Code | Description |
|------|-------------|
| 200 | Success |
| 201 | Created |
| 400 | Bad Request |
| 401 | Unauthorized (missing/invalid token) |
| 403 | Forbidden (insufficient permissions) |
| 404 | Not Found |
| 422 | Validation Error |
| 429 | Too Many Requests |
| 500 | Server Error |

---

## Examples

### cURL

```bash
# List projects
curl -H "Authorization: Bearer YOUR_TOKEN" \
  "https://your-domain.com/api/v1/projects?per_page=10"

# Create project
curl -X POST \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{"name": "New Project", "project_type": "residential"}' \
  "https://your-domain.com/api/v1/projects"

# Update project
curl -X PUT \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{"is_active": false}' \
  "https://your-domain.com/api/v1/projects/123"
```

### n8n HTTP Request Node

1. **Method:** GET/POST/PUT/DELETE
2. **URL:** `https://your-domain.com/api/v1/projects`
3. **Authentication:** Header Auth
   - Name: `Authorization`
   - Value: `Bearer YOUR_TOKEN`
4. **Headers:**
   - `Accept`: `application/json`
   - `Content-Type`: `application/json`

### JavaScript (fetch)

```javascript
const response = await fetch('https://your-domain.com/api/v1/projects', {
  headers: {
    'Authorization': 'Bearer YOUR_TOKEN',
    'Accept': 'application/json'
  }
});
const data = await response.json();
```

### PHP (Guzzle)

```php
$client = new \GuzzleHttp\Client();
$response = $client->get('https://your-domain.com/api/v1/projects', [
    'headers' => [
        'Authorization' => 'Bearer YOUR_TOKEN',
        'Accept' => 'application/json',
    ]
]);
$data = json_decode($response->getBody(), true);
```

---

---

## Sales Module

### Sales Orders

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/sales-orders` | List all sales orders |
| POST | `/sales-orders` | Create a sales order |
| GET | `/sales-orders/{id}` | Get a sales order |
| PUT | `/sales-orders/{id}` | Update a sales order |
| DELETE | `/sales-orders/{id}` | Delete a sales order |
| POST | `/sales-orders/{id}/confirm` | Confirm quote as sales order |
| POST | `/sales-orders/{id}/cancel` | Cancel sales order |
| POST | `/sales-orders/{id}/send` | Send to customer (email) |
| POST | `/sales-orders/{id}/reset-to-draft` | Reset to draft status |

**Filterable fields:** `id`, `partner_id`, `state`, `user_id`, `project_id`, `company_id`

**Searchable fields:** `name`, `client_order_ref`, `origin`

**Sortable fields:** `id`, `name`, `date_order`, `amount_total`, `created_at`

**Includable relations:** `partner`, `lines`, `user`, `project`, `company`

**States:** `draft`, `sent`, `sale`, `done`, `cancel`

### Sales Order Lines

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/sales-orders/{id}/lines` | List lines for an order |
| POST | `/sales-orders/{id}/lines` | Create line in an order |
| GET | `/lines/{id}` | Get a line |
| PUT | `/lines/{id}` | Update a line |
| DELETE | `/lines/{id}` | Delete a line |

---

## Purchases Module

### Purchase Orders

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/purchase-orders` | List all purchase orders |
| POST | `/purchase-orders` | Create a purchase order |
| GET | `/purchase-orders/{id}` | Get a purchase order |
| PUT | `/purchase-orders/{id}` | Update a purchase order |
| DELETE | `/purchase-orders/{id}` | Delete a purchase order |
| POST | `/purchase-orders/{id}/confirm` | Confirm RFQ as purchase order |
| POST | `/purchase-orders/{id}/cancel` | Cancel purchase order |
| POST | `/purchase-orders/{id}/send` | Send to vendor (email) |
| POST | `/purchase-orders/{id}/done` | Mark as complete |
| POST | `/purchase-orders/{id}/reset-to-draft` | Reset to draft status |

**Filterable fields:** `id`, `partner_id`, `state`, `user_id`, `project_id`, `company_id`

**Searchable fields:** `name`, `partner_ref`, `origin`

**Sortable fields:** `id`, `name`, `date_order`, `amount_total`, `created_at`

**Includable relations:** `partner`, `lines`, `user`, `company`

**States:** `draft`, `sent`, `purchase`, `done`, `cancel`

### Purchase Order Lines

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/purchase-orders/{id}/lines` | List lines for an order |
| POST | `/purchase-orders/{id}/lines` | Create line in an order |
| GET | `/lines/{id}` | Get a line |
| PUT | `/lines/{id}` | Update a line |
| DELETE | `/lines/{id}` | Delete a line |

---

## Accounts Module

### Invoices (Customer)

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/invoices` | List all customer invoices |
| POST | `/invoices` | Create an invoice |
| GET | `/invoices/{id}` | Get an invoice |
| PUT | `/invoices/{id}` | Update an invoice |
| DELETE | `/invoices/{id}` | Delete an invoice |
| POST | `/invoices/{id}/post` | Post invoice (confirm) |
| POST | `/invoices/{id}/cancel` | Cancel invoice |
| POST | `/invoices/{id}/reset-to-draft` | Reset to draft |
| POST | `/invoices/{id}/credit-note` | Create credit note |

**Filterable fields:** `id`, `partner_id`, `state`, `payment_state`, `journal_id`, `company_id`

**Searchable fields:** `name`, `ref`, `narration`

**States:** `draft`, `posted`, `cancel`

**Payment States:** `not_paid`, `in_payment`, `paid`, `partial`, `reversed`

### Bills (Vendor)

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/bills` | List all vendor bills |
| POST | `/bills` | Create a bill |
| GET | `/bills/{id}` | Get a bill |
| PUT | `/bills/{id}` | Update a bill |
| DELETE | `/bills/{id}` | Delete a bill |
| POST | `/bills/{id}/post` | Post bill (confirm) |
| POST | `/bills/{id}/cancel` | Cancel bill |
| POST | `/bills/{id}/reset-to-draft` | Reset to draft |
| POST | `/bills/{id}/refund` | Create refund |
| POST | `/bills/from-purchase-order/{purchaseOrderId}` | Create bill from PO |

### Payments

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/payments` | List all payments |
| POST | `/payments` | Create a payment |
| GET | `/payments/{id}` | Get a payment |
| PUT | `/payments/{id}` | Update a payment |
| DELETE | `/payments/{id}` | Delete a payment |
| POST | `/payments/register` | Register payment for invoice/bill |
| POST | `/payments/{id}/post` | Post payment |
| POST | `/payments/{id}/cancel` | Cancel payment |
| POST | `/payments/{id}/reset-to-draft` | Reset to draft |

**Register Payment Request:**
```json
{
  "move_id": 123,
  "amount": 500.00,
  "journal_id": 1,
  "payment_date": "2026-01-19",
  "memo": "Partial payment"
}
```

---

## Calculators

Cabinet construction calculation endpoints.

### Cabinet Calculator

```
POST /api/v1/calculators/cabinet
```

**Request:**
```json
{
  "exterior": {
    "width": 36,
    "height": 30,
    "depth": 24
  },
  "cabinet_type": "base",
  "toe_kick_height": 4.5
}
```

**Response includes:** Box dimensions, face frame calculations, stretcher positions, cut list with materials.

### Drawer Calculator

```
POST /api/v1/calculators/drawer
```

**Request:**
```json
{
  "opening_width": 18,
  "opening_height": 6,
  "opening_depth": 21
}
```

**Response includes:** Drawer box dimensions, slide requirements, validation warnings.

### Other Calculator Endpoints

| Method | Endpoint | Description |
|--------|----------|-------------|
| POST | `/calculators/drawer-stack` | Calculate multiple drawers |
| POST | `/calculators/drawer-cut-list` | Get drawer cut list |
| POST | `/calculators/drawer-quick-quote` | Quick drawer pricing |
| POST | `/calculators/depth-validation` | Validate cabinet depth for slides |
| POST | `/calculators/max-slide` | Get max slide for cabinet depth |
| POST | `/calculators/required-depth` | Get required depth for slide |
| POST | `/calculators/stretcher` | Calculate stretcher requirements |
| GET | `/calculators/blum-specs` | Get Blum TANDEM 563H specs |
| GET | `/calculators/min-cabinet-depths` | Get minimum depths by slide |
| GET | `/calculators/face-frame-styles` | Get face frame style options |

---

## Project Workflows

### Project Special Operations

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/projects/{id}/tree` | Get full project hierarchy |
| POST | `/projects/{id}/change-stage` | Change project stage |
| GET | `/projects/{id}/calculate` | Calculate project metrics |
| POST | `/projects/{id}/clone` | Clone entire project |
| GET | `/projects/{id}/gate-status` | Get workflow gate status |
| GET | `/projects/{id}/bom` | Get bill of materials summary |
| POST | `/projects/{id}/generate-order` | Generate sales order from project |

**Clone Request:**
```json
{
  "name": "Project Copy",
  "partner_id": 5,
  "include_addresses": true,
  "include_tags": true
}
```

**Gate Status Response:**
```json
{
  "project_id": 1,
  "current_stage": "design",
  "gates": {
    "design": {
      "name": "Design Gate",
      "requirements": {
        "has_rooms": {"status": true, "message": "..."},
        "has_cabinets": {"status": false, "message": "..."}
      },
      "passed": false,
      "progress": "1/3"
    }
  }
}
```

---

## Product Categories

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/product-categories` | List all categories |
| POST | `/product-categories` | Create a category |
| GET | `/product-categories/{id}` | Get a category |
| PUT | `/product-categories/{id}` | Update a category |
| DELETE | `/product-categories/{id}` | Delete a category |
| GET | `/product-categories/tree` | Get category tree hierarchy |

**Filterable fields:** `id`, `parent_id`, `company_id`

**Searchable fields:** `name`, `complete_name`

---

## Stock (Inventory Quantities)

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/stock` | List all stock records |
| POST | `/stock` | Create a stock record |
| GET | `/stock/{id}` | Get a stock record |
| PUT | `/stock/{id}` | Update a stock record |
| DELETE | `/stock/{id}` | Delete a stock record |
| GET | `/stock/by-product/{productId}` | Get stock by product |
| GET | `/stock/by-location/{locationId}` | Get stock by location |
| GET | `/stock/by-warehouse/{warehouseId}` | Get stock by warehouse |
| POST | `/stock/adjust` | Adjust stock quantity |
| POST | `/stock/availability` | Check availability |

**Adjust Stock Request:**
```json
{
  "product_id": 123,
  "location_id": 12,
  "quantity": 50,
  "reason": "Inventory count adjustment"
}
```

**Check Availability Request:**
```json
{
  "products": [
    {"product_id": 1, "quantity_needed": 10},
    {"product_id": 2, "quantity_needed": 5}
  ],
  "warehouse_id": 1
}
```

---

## Bill of Materials (BOM)

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/bom` | List all BOM items |
| POST | `/bom` | Create a BOM item |
| GET | `/bom/{id}` | Get a BOM item |
| PUT | `/bom/{id}` | Update a BOM item |
| DELETE | `/bom/{id}` | Delete a BOM item |
| GET | `/bom/by-project/{projectId}` | Get BOM for project |
| GET | `/bom/by-cabinet/{cabinetId}` | Get BOM for cabinet |
| POST | `/bom/generate/{projectId}` | Generate BOM from cabinets |
| POST | `/bom/bulk-update-status` | Bulk update BOM status |

**Material Types:** `sheet_good`, `hardware`, `edge_banding`, `finish`, `other`

**Status Values:** `pending`, `ordered`, `received`, `issued`

**Generate BOM Request:**
```json
{
  "overwrite": true
}
```

---

## Change Orders

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/change-orders` | List all change orders |
| POST | `/change-orders` | Create a change order |
| GET | `/change-orders/{id}` | Get a change order |
| PUT | `/change-orders/{id}` | Update a change order |
| DELETE | `/change-orders/{id}` | Delete a change order |
| POST | `/change-orders/{id}/submit` | Submit for approval |
| POST | `/change-orders/{id}/approve` | Approve change order |
| POST | `/change-orders/{id}/reject` | Reject change order |
| POST | `/change-orders/{id}/cancel` | Cancel change order |
| GET | `/change-orders/by-project/{projectId}` | Get change orders for project |

**Change Types:** `scope`, `pricing`, `schedule`, `design`, `materials`, `other`

**Priority:** `low`, `medium`, `high`, `critical`

**Status:** `draft`, `pending`, `approved`, `rejected`, `cancelled`

**Create Request:**
```json
{
  "project_id": 1,
  "title": "Add extra drawer",
  "description": "Customer requested additional drawer in cabinet B24-1",
  "change_type": "scope",
  "priority": "medium",
  "amount": 150.00,
  "days_impact": 1
}
```

**Approve Request:**
```json
{
  "notes": "Approved per customer call on 1/19"
}
```

**Reject Request:**
```json
{
  "reason": "Budget constraints"
}
```

---

## Changelog

### v1.1.0 (2026-01-19)
- Added Sales Orders with workflow actions (confirm, cancel, send)
- Added Purchase Orders with workflow actions
- Added Invoices with post, cancel, credit note actions
- Added Bills with post, cancel, refund actions
- Added Payments with registration and reconciliation
- Added Calculator APIs for cabinet/drawer design
- Added Project workflow endpoints (clone, gate-status, bom)
- Added Product Categories with tree hierarchy
- Added Stock/Inventory quantities with adjustment
- Added Bill of Materials (BOM) management
- Added Change Orders with approval workflow

### v1.0.0 (2026-01-19)
- Initial release
- Projects, Rooms, Cabinets, and component endpoints
- Partners, Employees, Products endpoints
- Batch operations
- Webhook subscriptions
