<?php

return [
    'form' => [
        'sections' => [
            'general' => [
                'title' => 'General',

                'fields' => [
                    'vendor'                   => 'Vendor',
                    'vendor-reference'         => 'Vendor Reference',
                    'vendor-reference-tooltip' => 'Reference of the sales order or bid sent by the vendor. It\'s used to do the matching when you receive the products as this reference is usually written on the delivery order sent by your vendor.',
                    'agreement'                => 'Agreement',
                    'currency'                 => 'Currency',
                    'confirmation-date'        => 'Confirmation Date',
                    'order-deadline'           => 'Order Deadline',
                    'expected-arrival'         => 'Expected Arrival',
                    'confirmed-by-vendor'      => 'Confirmed by Vendor',
                ],
            ],
        ],

        'tabs' => [
            'products' => [
                'title' => 'Products',

                'repeater' => [
                    'products' => [
                        'title'            => 'Products',
                        'add-product-line' => 'Add Product',

                        'fields' => [
                            'product'             => 'Product',
                            'expected-arrival'    => 'Expected Arrival',
                            'quantity'            => 'Quantity',
                            'received'            => 'Received',
                            'billed'              => 'Billed',
                            'unit'                => 'Unit',
                            'packaging-qty'       => 'Packaging Qty',
                            'packaging'           => 'Packaging',
                            'taxes'               => 'Taxes',
                            'discount-percentage' => 'Discount (%)',
                            'unit-price'          => 'Unit Price',
                            'amount'              => 'Amount',
                        ],
                    ],

                    'section' => [
                        'title' => 'Add Section',

                        'fields' => [
                        ],
                    ],

                    'note' => [
                        'title' => 'Add Note',

                        'fields' => [
                        ],
                    ],
                ],
            ],

            'additional' => [
                'title' => 'Additional Information',

                'fields' => [
                    'buyer'             => 'Buyer',
                    'company'           => 'Company',
                    'source-document'   => 'Source Document',
                    'incoterm'          => 'Incoterm',
                    'incoterm-tooltip'  => 'International Commercial Terms are a series of predefined commercial terms used in international transactions.',
                    'incoterm-location' => 'Incoterm Location',
                    'payment-term'      => 'Payment Term',
                    'fiscal-position'   => 'Fiscal Position',
                ],
            ],

            'terms' => [
                'title' => 'Terms and Conditions',
            ],
        ],
    ],

    'table' => [
        'columns' => [
            'priority'         => 'Priority',
            'vendor-reference' => 'Vendor Reference',
            'reference'        => 'Reference',
            'vendor'           => 'Vendor',
            'buyer'            => 'Buyer',
            'company'          => 'Company',
            'order-deadline'   => 'Order Deadline',
            'source-document'  => 'Source Document',
            'untaxed-amount'   => 'Untaxed Amount',
            'total-amount'     => 'Total Amount',
            'status'           => 'Status',
            'billing-status'   => 'Billing Status',
            'currency'         => 'Currency',
            'billing-status'   => 'Billing Status',
        ],

        'groups' => [
            'vendor'     => 'Vendor',
            'buyer'      => 'Buyer',
            'state'      => 'State',
            'created-at' => 'Created At',
            'updated-at' => 'Updated At',
        ],

        'filters' => [
            'status'           => 'Status',
            'vendor-reference' => 'Vendor Reference',
            'reference'        => 'Reference',
            'untaxed-amount'   => 'Untaxed Amount',
            'total-amount'     => 'Total Amount',
            'order-deadline'   => 'Order Deadline',
            'vendor'           => 'Vendor',
            'buyer'            => 'Buyer',
            'company'          => 'Company',
            'payment-term'     => 'Payment Term',
            'incoterm'         => 'Incoterm',
            'status'           => 'Status',
            'created-at'       => 'Created At',
            'updated-at'       => 'Updated At',
        ],

        'actions' => [
            'delete' => [
                'notification' => [
                    'title' => 'Order deleted',
                    'body'  => 'The order has been deleted successfully.',
                ],
            ],
        ],

        'bulk-actions' => [
            'delete' => [
                'notification' => [
                    'title' => 'Order deleted',
                    'body'  => 'The order has been deleted successfully.',
                ],
            ],
        ],
    ],

    'infolist' => [
        'sections' => [
            'general' => [
                'title' => 'General',

                'entries' => [
                    'vendor'                   => 'Vendor',
                    'vendor-reference'         => 'Vendor Reference',
                    'vendor-reference-tooltip' => 'Reference of the sales order or bid sent by the vendor. It\'s used to do the matching when you receive the products as this reference is usually written on the delivery order sent by your vendor.',
                    'agreement'                => 'Agreement',
                    'currency'                 => 'Currency',
                    'confirmation-date'        => 'Confirmation Date',
                    'order-deadline'           => 'Order Deadline',
                    'expected-arrival'         => 'Expected Arrival',
                    'confirmed-by-vendor'      => 'Confirmed by Vendor',
                ],
            ],
        ],

        'tabs' => [
            'products' => [
                'title' => 'Products',

                'repeater' => [
                    'products' => [
                        'title'            => 'Products',
                        'add-product-line' => 'Add Product',

                        'entries' => [
                            'product'             => 'Product',
                            'expected-arrival'    => 'Expected Arrival',
                            'quantity'            => 'Quantity',
                            'received'            => 'Received',
                            'billed'              => 'Billed',
                            'unit'                => 'Unit',
                            'packaging-qty'       => 'Packaging Qty',
                            'packaging'           => 'Packaging',
                            'taxes'               => 'Taxes',
                            'discount-percentage' => 'Discount (%)',
                            'unit-price'          => 'Unit Price',
                            'amount'              => 'Amount',
                        ],
                    ],

                    'section' => [
                        'title' => 'Add Section',
                    ],

                    'note' => [
                        'title' => 'Add Note',
                    ],
                ],
            ],

            'additional' => [
                'title' => 'Additional Information',

                'entries' => [
                    'buyer'             => 'Buyer',
                    'company'           => 'Company',
                    'source-document'   => 'Source Document',
                    'incoterm'          => 'Incoterm',
                    'incoterm-tooltip'  => 'International Commercial Terms are a series of predefined commercial terms used in international transactions.',
                    'incoterm-location' => 'Incoterm Location',
                    'payment-term'      => 'Payment Term',
                    'fiscal-position'   => 'Fiscal Position',
                ],
            ],

            'terms' => [
                'title' => 'Terms and Conditions',
            ],
        ],
    ],
];
