<?php

return [
    'navigation' => [
        'title' => 'Replenishment',
        'group' => 'Procurement',
    ],

    'form' => [
        'fields' => [
            'product'      => 'Product',
            'warehouse'    => 'Warehouse',
            'location'     => 'Location',
            'min-qty'      => 'Minimum Qty (Reorder Point)',
            'max-qty'      => 'Maximum Qty (Order Up To)',
            'qty-multiple' => 'Order Multiple',
            'trigger'      => 'Trigger Type',
        ],
    ],

    'table' => [
        'columns' => [
            'product'      => 'Product',
            'warehouse'    => 'Warehouse',
            'location'     => 'Location',
            'route'        => 'Route',
            'vendor'       => 'Vendor',
            'trigger'      => 'Trigger',
            'on-hand'      => 'On Hand',
            'min-qty'      => 'Min Qty',
            'max-qty'      => 'Max Qty',
            'status'       => 'Status',
            'qty-to-order' => 'Qty to Order',
            'uom'          => 'UOM',
            'company'      => 'Company',
            'created-at'   => 'Created At',
        ],

        'groups' => [
            'warehouse' => 'Warehouse',
            'location'  => 'Location',
            'product'   => 'Product',
            'category'  => 'Category',
            'trigger'   => 'Trigger Type',
        ],

        'filters' => [
            'product' => 'Product',
            'min-qty' => 'Minimum Quantity',
            'max-qty' => 'Maximum Quantity',
        ],

        'header-actions' => [
            'create' => [
                'label' => 'Add Replenishment',

                'notification' => [
                    'title' => 'Replenishment added',
                    'body'  => 'The reorder point has been added successfully.',
                ],

                'before' => [
                    'notification' => [
                        'title' => 'Replenishment already exists',
                        'body'  => 'A replenishment already exists for this configuration. Please update the existing replenishment instead.',
                    ],
                ],
            ],
        ],

        'actions' => [
        ],
    ],
];
