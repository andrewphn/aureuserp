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
                ],
            ],
        ],

        'tabs' => [
            'products' => [
                'title' => 'Products',

                'repeater' => [
                    'products' => [
                        'title' => 'Products',
                        'add-product-line' => 'Add Product',


                        'fields' => [
                            'product' => 'Product',
                            'quantity' => 'Quantity',
                            'unit' => 'Unit',
                            'taxes' => 'Taxes',
                            'discount-percentage' => 'Discount (%)',
                            'unit-price' => 'Unit Price',
                            'sub-total' => 'Sub Total',
                            'total' => 'Total',
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
];
