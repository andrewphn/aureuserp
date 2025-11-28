<?php

namespace App\Services;

/**
 * Footer Field Registry class
 *
 */
class FooterFieldRegistry
{
    /**
     * Get all available fields for a specific context type.
     *
     * @param string $contextType
     * @return array
     */
    public function getAvailableFields(string $contextType): array
    {
        return match ($contextType) {
            'project' => $this->getProjectFields(),
            'sale' => $this->getSaleFields(),
            'inventory' => $this->getInventoryFields(),
            'production' => $this->getProductionFields(),
            default => []
        };
    }

    /**
     * Get project context fields.
     */
    protected function getProjectFields(): array
    {
        return [
            'project_number' => [
                'label' => 'Project Number',
                'type' => 'text',
                'description' => 'Unique project identifier',
                'data_key' => 'project_number'
            ],
            'customer_name' => [
                'label' => 'Customer Name',
                'type' => 'text',
                'description' => 'Name of the customer/partner',
                'data_key' => 'partner.name'
            ],
            'project_type' => [
                'label' => 'Project Type',
                'type' => 'badge',
                'description' => 'Type of woodworking project',
                'data_key' => 'project_type'
            ],
            'project_address' => [
                'label' => 'Project Address',
                'type' => 'text',
                'description' => 'Job site address',
                'data_key' => 'project_address'
            ],
            'linear_feet' => [
                'label' => 'Linear Feet',
                'type' => 'number',
                'description' => 'Estimated linear feet of cabinets',
                'data_key' => 'estimated_linear_feet',
                'suffix' => ' LF'
            ],
            'estimate_hours' => [
                'label' => 'Allocated Hours',
                'type' => 'number',
                'description' => 'Allocated production hours',
                'data_key' => 'allocated_hours',
                'suffix' => ' hrs'
            ],
            'estimate_days' => [
                'label' => 'Est. Days',
                'type' => 'metric',
                'description' => 'Estimated production days',
                'data_key' => 'estimate.days',
                'icon' => 'calendar',
                'color' => 'blue'
            ],
            'estimate_weeks' => [
                'label' => 'Est. Weeks',
                'type' => 'metric',
                'description' => 'Estimated production weeks',
                'data_key' => 'estimate.weeks',
                'icon' => 'chart',
                'color' => 'purple'
            ],
            'estimate_months' => [
                'label' => 'Est. Months',
                'type' => 'metric',
                'description' => 'Estimated production months',
                'data_key' => 'estimate.months',
                'icon' => 'calendar',
                'color' => 'teal'
            ],
            'timeline_alert' => [
                'label' => 'Timeline Alert',
                'type' => 'alert',
                'description' => 'Schedule variance alert',
                'data_key' => 'alert_level'
            ],
            'completion_date' => [
                'label' => 'Completion Date',
                'type' => 'date',
                'description' => 'Desired completion date',
                'data_key' => 'desired_completion_date'
            ],
            'tags' => [
                'label' => 'Tags',
                'type' => 'tags',
                'description' => 'Project tags and categories',
                'data_key' => 'tags'
            ],
        ];
    }

    /**
     * Get sales context fields.
     */
    protected function getSaleFields(): array
    {
        return [
            'order_number' => [
                'label' => 'Order Number',
                'type' => 'text',
                'description' => 'Sales order number',
                'data_key' => 'order_number'
            ],
            'quote_number' => [
                'label' => 'Quote Number',
                'type' => 'text',
                'description' => 'Quote number',
                'data_key' => 'quote_number'
            ],
            'customer_name' => [
                'label' => 'Customer',
                'type' => 'text',
                'description' => 'Customer name',
                'data_key' => 'partner.name'
            ],
            'order_total' => [
                'label' => 'Total Amount',
                'type' => 'currency',
                'description' => 'Order total amount',
                'data_key' => 'grand_total'
            ],
            'order_status' => [
                'label' => 'Order Status',
                'type' => 'badge',
                'description' => 'Current order status',
                'data_key' => 'status'
            ],
            'payment_status' => [
                'label' => 'Payment Status',
                'type' => 'badge',
                'description' => 'Payment status',
                'data_key' => 'payment_status'
            ],
            'order_date' => [
                'label' => 'Order Date',
                'type' => 'date',
                'description' => 'Date order was placed',
                'data_key' => 'order_date'
            ],
            'expected_delivery' => [
                'label' => 'Expected Delivery',
                'type' => 'date',
                'description' => 'Expected delivery date',
                'data_key' => 'expected_delivery_date'
            ],
        ];
    }

    /**
     * Get inventory context fields.
     */
    protected function getInventoryFields(): array
    {
        return [
            'item_name' => [
                'label' => 'Item Name',
                'type' => 'text',
                'description' => 'Inventory item name',
                'data_key' => 'name'
            ],
            'sku' => [
                'label' => 'SKU',
                'type' => 'text',
                'description' => 'Stock keeping unit',
                'data_key' => 'sku'
            ],
            'quantity' => [
                'label' => 'Quantity',
                'type' => 'number',
                'description' => 'Current quantity in stock',
                'data_key' => 'quantity'
            ],
            'unit' => [
                'label' => 'Unit',
                'type' => 'text',
                'description' => 'Unit of measurement',
                'data_key' => 'unit'
            ],
            'location' => [
                'label' => 'Location',
                'type' => 'text',
                'description' => 'Storage location',
                'data_key' => 'location'
            ],
            'reorder_level' => [
                'label' => 'Reorder Level',
                'type' => 'number',
                'description' => 'Minimum stock level before reorder',
                'data_key' => 'reorder_level'
            ],
            'supplier' => [
                'label' => 'Supplier',
                'type' => 'text',
                'description' => 'Primary supplier',
                'data_key' => 'supplier.name'
            ],
            'unit_cost' => [
                'label' => 'Unit Cost',
                'type' => 'currency',
                'description' => 'Cost per unit',
                'data_key' => 'unit_cost'
            ],
        ];
    }

    /**
     * Get production context fields.
     */
    protected function getProductionFields(): array
    {
        return [
            'job_number' => [
                'label' => 'Job Number',
                'type' => 'text',
                'description' => 'Production job number',
                'data_key' => 'job_number'
            ],
            'project_name' => [
                'label' => 'Project Name',
                'type' => 'text',
                'description' => 'Associated project name',
                'data_key' => 'project.name'
            ],
            'customer_name' => [
                'label' => 'Customer',
                'type' => 'text',
                'description' => 'Customer name',
                'data_key' => 'customer_name'
            ],
            'production_status' => [
                'label' => 'Status',
                'type' => 'badge',
                'description' => 'Production status',
                'data_key' => 'status'
            ],
            'assigned_to' => [
                'label' => 'Assigned To',
                'type' => 'text',
                'description' => 'Lead woodworker',
                'data_key' => 'assigned_to.name'
            ],
            'start_date' => [
                'label' => 'Start Date',
                'type' => 'date',
                'description' => 'Production start date',
                'data_key' => 'start_date'
            ],
            'due_date' => [
                'label' => 'Due Date',
                'type' => 'date',
                'description' => 'Production due date',
                'data_key' => 'due_date'
            ],
        ];
    }

    /**
     * Get field definition by key and context.
     */
    public function getFieldDefinition(string $contextType, string $fieldKey): ?array
    {
        $fields = $this->getAvailableFields($contextType);
        return $fields[$fieldKey] ?? null;
    }

    /**
     * Get all context types.
     */
    public function getContextTypes(): array
    {
        return [
            'project' => 'Projects',
            'sale' => 'Sales Orders',
            'inventory' => 'Inventory',
            'production' => 'Production Jobs',
        ];
    }
}
