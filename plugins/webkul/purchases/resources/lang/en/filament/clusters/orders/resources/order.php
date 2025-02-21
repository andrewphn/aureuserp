<?php

return [
    'form' => [
        'sections' => [
            'general' => [
                'title' => 'General',

                'fields' => [
                    'vendor' => 'Vendor',
                    'vendor-reference' => 'Vendor Reference',
                    'vendor-reference-tooltip' => 'Reference of the sales order or bid sent by the vendor. It\'s used to do the matching when you receive the products as this reference is usually written on the delivery order sent by your vendor.',
                    'agreement' => 'Agreement',
                    'currency' => 'Currency',
                    'order-deadline' => 'Order Deadline',
                    'expected-arrival' => 'Expected Arrival',
                ],
            ],
        ],

        'tabs' => [
            'products' => [
                'title' => 'Products',

                'fields' => [
                ],
            ],

            'additional' => [
                'title' => 'Additional Information',
            ],

            'terms' => [
                'title' => 'Terms and Conditions',
            ],
        ],
    ]
];