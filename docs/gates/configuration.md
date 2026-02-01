# Gates Configuration Guide

## Creating a New Gate

### Via Database/Seeder

```php
use Webkul\Project\Models\Gate;
use Webkul\Project\Models\ProjectStage;

$stage = ProjectStage::where('stage_key', 'design')->first();

$gate = Gate::create([
    'stage_id' => $stage->id,
    'name' => 'Design Review Complete',
    'gate_key' => 'design_review_complete',
    'description' => 'Ensures all design reviews are finalized',
    'sequence' => 10,
    'is_blocking' => true,
    'is_active' => true,
    'applies_design_lock' => true,
    'applies_procurement_lock' => false,
    'applies_production_lock' => false,
]);
```

### Gate Properties

| Property | Required | Description |
|----------|----------|-------------|
| stage_id | Yes | Stage this gate belongs to |
| name | Yes | Display name (max 100 chars) |
| gate_key | Yes | Unique identifier (max 50 chars, use snake_case) |
| description | No | Longer description |
| sequence | No | Order within stage (default: 0) |
| is_blocking | No | Whether it blocks advancement (default: true) |
| is_active | No | Whether it's evaluated (default: true) |
| applies_design_lock | No | Locks design changes when passed (default: false) |
| applies_procurement_lock | No | Locks procurement when passed (default: false) |
| applies_production_lock | No | Locks production when passed (default: false) |

## Creating Requirements

### Field Not Null

Check if a field has a non-empty value.

```php
use Webkul\Project\Models\GateRequirement;

GateRequirement::create([
    'gate_id' => $gate->id,
    'requirement_type' => 'field_not_null',
    'target_field' => 'partner_id',
    'error_message' => 'No client assigned to project',
    'help_text' => 'Go to project settings and select a client',
    'action_label' => 'Assign Client',
    'action_route' => 'projects.edit',
    'sequence' => 1,
]);
```

### Field Equals

Check if a field equals a specific value.

```php
GateRequirement::create([
    'gate_id' => $gate->id,
    'requirement_type' => 'field_equals',
    'target_field' => 'visibility',
    'target_value' => 'public',
    'error_message' => 'Project must be public',
    'sequence' => 2,
]);
```

### Field Greater Than

Check if a numeric field exceeds a threshold.

```php
GateRequirement::create([
    'gate_id' => $gate->id,
    'requirement_type' => 'field_greater_than',
    'target_field' => 'allocated_hours',
    'target_value' => '40',
    'error_message' => 'Project must have more than 40 allocated hours',
    'sequence' => 3,
]);
```

### Relation Exists

Check if a relationship has at least one record.

```php
GateRequirement::create([
    'gate_id' => $gate->id,
    'requirement_type' => 'relation_exists',
    'target_relation' => 'rooms',
    'error_message' => 'No rooms defined',
    'help_text' => 'Add at least one room to the project',
    'action_label' => 'Add Room',
    'action_route' => 'projects.rooms.create',
    'sequence' => 4,
]);
```

### Relation Count

Check if relation count meets a threshold.

```php
GateRequirement::create([
    'gate_id' => $gate->id,
    'requirement_type' => 'relation_count',
    'target_relation' => 'cabinets',
    'target_value' => '5',
    'comparison_operator' => '>=',
    'error_message' => 'At least 5 cabinets required',
    'sequence' => 5,
]);
```

Supported operators: `=`, `!=`, `>`, `>=`, `<`, `<=`

### All Children Pass

Check if all related items meet a condition.

```php
GateRequirement::create([
    'gate_id' => $gate->id,
    'requirement_type' => 'all_children_pass',
    'target_relation' => 'cabinets',
    'target_field' => 'qc_passed',
    'target_value' => 'true',
    'error_message' => 'Not all cabinets have passed QC',
    'help_text' => 'Run QC on remaining cabinets',
    'sequence' => 6,
]);
```

### Document Uploaded

Check if a document collection has files.

```php
GateRequirement::create([
    'gate_id' => $gate->id,
    'requirement_type' => 'document_uploaded',
    'target_value' => 'design_drawings',  // Media collection name
    'error_message' => 'Design drawings not uploaded',
    'action_label' => 'Upload Drawings',
    'sequence' => 7,
]);
```

### Payment Received

Check if a payment milestone is met.

```php
GateRequirement::create([
    'gate_id' => $gate->id,
    'requirement_type' => 'payment_received',
    'target_value' => 'deposit',  // 'deposit' or 'final'
    'error_message' => 'Deposit payment not received',
    'sequence' => 8,
]);
```

### Task Completed

Check if a specific task type is done.

```php
GateRequirement::create([
    'gate_id' => $gate->id,
    'requirement_type' => 'task_completed',
    'target_value' => 'design_review',  // task_type value
    'error_message' => 'Design review task not completed',
    'sequence' => 9,
]);
```

### Custom Check

Execute a custom PHP class/method for complex logic.

```php
GateRequirement::create([
    'gate_id' => $gate->id,
    'requirement_type' => 'custom_check',
    'custom_check_class' => 'Webkul\\Project\\Services\\Gates\\Checks\\MaterialSourcingCheck',
    'custom_check_method' => 'checkAllMaterialsSourced',
    'error_message' => 'Not all materials sourced',
    'sequence' => 10,
]);
```

## Creating Custom Check Classes

Custom checks allow complex business logic:

```php
<?php

namespace Webkul\Project\Services\Gates\Checks;

use Webkul\Project\Models\GateRequirement;
use Webkul\Project\Models\Project;
use Webkul\Project\Services\Gates\RequirementCheckResult;

class MaterialSourcingCheck
{
    public function checkAllMaterialsSourced(
        Project $project,
        GateRequirement $requirement
    ): RequirementCheckResult {
        $bomLines = $project->bomLines()->get();

        if ($bomLines->isEmpty()) {
            return new RequirementCheckResult(
                false,
                'No BOM lines found',
                ['bom_count' => 0]
            );
        }

        $unsourced = $bomLines->filter(fn($line) => !$line->supplier_id);

        if ($unsourced->isNotEmpty()) {
            return new RequirementCheckResult(
                false,
                "{$unsourced->count()} materials not sourced",
                ['unsourced_count' => $unsourced->count(), 'total' => $bomLines->count()]
            );
        }

        return new RequirementCheckResult(
            true,
            'All materials sourced',
            ['total' => $bomLines->count()]
        );
    }
}
```

## Checking Target Models

Requirements can check fields on related models:

| target_model | Description |
|--------------|-------------|
| `Project` (default) | The project itself |
| `SalesOrder` | First linked sales order |
| `Partner` | The project's partner |

```php
// Check partner has email
GateRequirement::create([
    'gate_id' => $gate->id,
    'requirement_type' => 'field_not_null',
    'target_model' => 'Partner',
    'target_field' => 'email',
    'error_message' => 'Client email not set',
]);
```

## TCS Woodwork Default Configuration

### Discovery Stage

```php
// Gate: discovery_complete
$requirements = [
    ['field_not_null', 'partner_id', 'No client assigned'],
    ['relation_exists', 'orders', 'No sales order linked'],
    ['custom_check', 'DepositCheck', 'checkDepositReceived', 'Deposit not received'],
    ['relation_exists', 'rooms', 'No rooms defined'],
];
```

### Design Stage

```php
// Gate: design_lock (applies_design_lock = true)
$requirements = [
    ['custom_check', 'DimensionCheck', 'checkAllCabinetsDimensioned', 'Cabinets missing dimensions'],
    ['relation_count', 'bomLines', '>=', 1, 'BOM not generated'],
    ['field_not_null', 'design_approved_at', 'Design not approved'],
    ['field_not_null', 'redline_approved_at', 'Redline not confirmed'],
];
```

### Production Stage

```php
// Gate: production_complete (applies_production_lock = true)
$requirements = [
    ['custom_check', 'ProductionTaskCheck', 'checkAllTasksComplete', 'Production tasks incomplete'],
];
```

### QC Stage

```php
// Gate: qc_passed
$requirements = [
    ['all_children_pass', 'cabinets', 'qc_passed', true, 'Cabinets not QC\'d'],
    ['custom_check', 'DefectCheck', 'checkNoBlockingDefects', 'Blocking defects open'],
];
```

## Best Practices

1. **Use descriptive gate_keys** - `design_lock` not `gate_1`
2. **Keep error_messages user-friendly** - "No client assigned" not "partner_id is null"
3. **Provide help_text** - Guide users on how to resolve
4. **Use action_label/action_route** - Enable one-click resolution
5. **Order by sequence** - Most important first
6. **Test with factories** - Use GateRequirementFactory states

```php
// Factory usage
GateRequirement::factory()
    ->fieldNotNull('partner_id')
    ->withAction('Assign Client', 'projects.edit')
    ->create(['gate_id' => $gate->id]);
```
