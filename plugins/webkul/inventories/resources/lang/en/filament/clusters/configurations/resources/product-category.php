<?php

return [
    'navigation' => [
        'title' => 'Categories',
        'group' => 'Products',
    ],

    'form' => [
        'sections' => [
            'inventory' => [
                'title' => 'Inventory',

                'fieldsets' => [
                    'logistics' => [
                        'title' => 'Logistics',
                        
                        'fields' => [
                            'routes' => 'Routes',
                        ],
                    ],
                ],
            ],
        ],
    ],
];
