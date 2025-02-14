<?php

return [
    'form' => [
        'name'    => 'Name',
        'barcode' => 'Barcode',
        'product' => 'Product',
        'routes'  => 'Routes',
        'qty'     => 'Qty',
        'company' => 'Company',
    ],

    'table' => [
        'columns' => [
            'name'       => 'Name',
            'product'    => 'Product',
            'routes'     => 'Routes',
            'qty'        => 'Qty',
            'company'    => 'Company',
            'barcode'    => 'Barcode',
            'created-at' => 'Created At',
            'updated-at' => 'Updated At',
        ],

        'groups' => [
            'product'    => 'Product',
            'created-at' => 'Created At',
            'updated-at' => 'Updated At',
        ],

        'filters' => [
            'product' => 'Product',
        ],

        'actions' => [
            'edit' => [
                'notification' => [
                    'title' => 'Packaging update',
                    'body'  => 'The packaging has been update successfully.',
                ],
            ],

            'delete' => [
                'notification' => [
                    'title' => 'Packaging deleted',
                    'body'  => 'The packaging has been deleted successfully.',
                ],
            ],
        ],

        'bulk-actions' => [
            'print' => [
                'label' => 'Print',
            ],

            'delete' => [
                'notification' => [
                    'title' => 'Packagings deleted',
                    'body'  => 'The packagings has been deleted successfully.',
                ],
            ],
        ],

        'empty-state-actions' => [
            'create' => [
                'label' => 'New Packaging',

                'notification' => [
                    'title' => 'Packaging created',
                    'body'  => 'The packaging has been created successfully.',
                ],
            ],
        ],
    ],

    'infolist' => [
        'sections' => [
            'general' => [
                'title' => 'General Information',

                'entries' => [
                    'name'    => 'Package Name',
                    'barcode' => 'Barcode',
                    'product' => 'Product',
                    'qty'     => 'Quantity',
                ],
            ],

            'organization' => [
                'title' => 'Organization Details',

                'entries' => [
                    'company'    => 'Company',
                    'creator'    => 'Created By',
                    'created_at' => 'Created At',
                    'updated_at' => 'Last Updated At',
                ],
            ],
        ],
    ],
];
