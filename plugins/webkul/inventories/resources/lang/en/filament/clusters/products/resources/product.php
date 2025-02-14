<?php

return [
    'navigation' => [
        'title' => 'Products',
        'group' => 'Inventory',
    ],

    'form' => [
        'sections' => [
            'inventory' => [
                'title' => 'Inventory',

                'fieldsets' => [
                    'tracking' => [
                        'title' => 'Tracking',

                        'fields' => [
                            'track-inventory'              => 'Track Inventory',
                            'track-inventory-hint-tooltip' => 'A storable product is a product for which you manage stock.',
                            'track-by'                     => 'Track By',
                            'expiration-date'              => 'Expiration Date',
                            'expiration-date-hint-tooltip' => 'When this box is ticked, you have the possibility to specify dates to manage product expiration, on the product and on the corresponding lot/serial numbers',
                        ],
                    ],

                    'operation' => [
                        'title' => 'Operations',

                        'fields' => [
                            'routes'              => 'Routes',
                            'routes-hint-tooltip' => 'Depending on the modules installed, this will allow you to define the route of the product: whether it will be bought, manufactured, replenished on order, etc.',
                        ],
                    ],

                    'logistics' => [
                        'title' => 'Logistics',

                        'fields' => [
                            'responsible'              => 'Responsible',
                            'responsible-hint-tooltip' => 'Delivery lead time, in days. It\'s the number of days, promised to the customer, between the confirmation of the sales order and the delivery.',
                            'weight'                   => 'Weight',
                            'volume'                   => 'Volume',
                            'sale-delay'               => 'Customer Lead Time (Days)',
                            'sale-delay-hint-tooltip'  => 'Delivery lead time, in days. It\'s the number of days, promised to the customer, between the confirmation of the sales order and the delivery.',
                        ],
                    ],

                    'traceability' => [
                        'title' => 'Traceability',

                        'fields' => [
                            'expiration-date'               => 'Expiration Date (Days)',
                            'expiration-date-hint-tooltip'  => 'When this box is ticked, you have the possibility to specify dates to manage product expiration, on the product and on the corresponding lot/serial numbers',
                            'best-before-date'              => 'Best Before Date (Days)',
                            'best-before-date-hint-tooltip' => 'Number of days before the Expiration Date after which the goods starts deteriorating, without being dangerous yet. It will be computed on the lot/serial number.',
                            'removal-date'                  => 'Removal Date (Days)',
                            'removal-date-hint-tooltip'     => 'Number of days before the Expiration Date after which the goods should be removed from the stock. It will be computed on the lot/serial number.',
                            'alert-date'                    => 'Alert Date (Days)',
                            'alert-date-hint-tooltip'       => 'Number of days before the Expiration Date after which an alert should be raised on the lot/serial number. It will be computed on the lot/serial number.',
                        ],
                    ],
                ],
            ],

            'additional' => [
                'title' => 'Additional',
            ],
        ],
    ],

    'infolist' => [
        'sections' => [
            'inventory' => [
                'title' => 'Inventory',

                'entries' => [
                ],

                'fieldsets' => [
                    'tracking' => [
                        'title' => 'Tracking',

                        'entries' => [
                            'track-inventory' => 'Track Inventory',
                            'track-by'        => 'Track By',
                            'expiration-date' => 'Expiration Date',
                        ],
                    ],

                    'operation' => [
                        'title' => 'Operations',

                        'entries' => [
                            'routes' => 'Routes',
                        ],
                    ],

                    'logistics' => [
                        'title' => 'Logistics',

                        'entries' => [
                            'responsible' => 'Responsible',
                            'weight'      => 'Weight',
                            'volume'      => 'Volume',
                            'sale-delay'  => 'Customer Lead Time (Days)',
                        ],
                    ],

                    'traceability' => [
                        'title' => 'Traceability',

                        'entries' => [
                            'expiration-date'  => 'Expiration Date (Days)',
                            'best-before-date' => 'Best Before Date (Days)',
                            'removal-date'     => 'Removal Date (Days)',
                            'alert-date'       => 'Alert Date (Days)',
                        ],
                    ],
                ],
            ],
        ],
    ],
];
