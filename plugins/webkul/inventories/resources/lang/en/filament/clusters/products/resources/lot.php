<?php

return [
    'navigation' => [
        'title' => 'Lots / Serial Numbers',
        'group' => 'Inventory',
    ],

    'form' => [
        'sections' => [
            'general' => [
                'title' => 'General',

                'fields' => [
                    'name'                   => 'Name',
                    'name-placeholder'       => 'e.g. LOT/0001/20121',
                    'product'                => 'Product',
                    'product-hint-tooltip'   => 'The product associated with this lot/serial number. It cannot be changed if it has already been moved.',
                    'reference'              => 'Reference',
                    'reference-hint-tooltip' => 'An internal reference number, if different from the manufacturer\'s lot/serial number.',
                    'description'            => 'Description',
                ],
            ],
        ],
    ],

    'table' => [
        'columns' => [
            'name'         => 'Name',
            'product'      => 'Product',
            'on-hand-qty'  => 'On Hand Quantity',
            'reference'    => 'Internal Reference',
            'created-at'   => 'Created At',
            'updated-at'   => 'Updated At',
        ],

        'groups' => [
            'product'        => 'Product',
            'location'       => 'Location',
            'created-at'     => 'Created At',
        ],

        'filters' => [
            'product'  => 'Product',
            'location' => 'Location',
            'creator'  => 'Creator',
            'company'  => 'Company',
        ],

        'actions' => [
            'delete' => [
                'notification' => [
                    'title' => 'Lot deleted',
                    'body'  => 'The lot has been deleted successfully.',
                ],
            ],
        ],

        'bulk-actions' => [
            'print' => [
                'label' => 'Print Barcode',
            ],

            'delete' => [
                'notification' => [
                    'title' => 'Lots deleted',
                    'body'  => 'The lots has been deleted successfully.',
                ],
            ],
        ],
    ],

    'infolist' => [
        'sections' => [
            'general' => [
                'title' => 'Lot Details',

                'entries' => [
                    'name'        => 'Lot Name',
                    'product'     => 'Product',
                    'reference'   => 'Reference',
                    'description' => 'Description',
                    'on-hand-qty' => 'On-Hand Quantity',
                    'company'     => 'Company',
                    'created-at'  => 'Created At',
                    'updated-at'  => 'Last Updated',
                ],
            ],

            'record-information' => [
                'title' => 'Record Information',

                'entries' => [
                    'created-by'   => 'Created By',
                    'created-at'   => 'Created At',
                    'last-updated' => 'Last Updated',
                ],
            ],
        ],
    ],
];
