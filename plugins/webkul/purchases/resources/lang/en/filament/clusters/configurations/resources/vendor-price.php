<?php

return [
    'navigation' => [
        'title' => 'Vendor Price Lists',
    ],

    'form' => [
        'sections' => [
            'general' => [
                'title'  => 'General',

                'fields' => [
                    'vendor'                      => 'Vendor',
                    'vendor-product-name'         => 'Vendor Product Name',
                    'vendor-product-name-tooltip' => 'This vendor\'s product name will be used when printing a request for quotation. Keep empty to use the internal one.',
                    'vendor-product-code'         => 'Vendor Product Code',
                    'vendor-product-code-tooltip' => 'This vendor\'s product code will be used when printing a request for quotation. Keep empty to use the internal one.',
                    'delay'                       => 'Delivery Lead Time (Days)',
                    'delay-tooltip'               => 'Lead time in days between the confirmation of the purchase order and the receipt of the products in your warehouse. Used by the scheduler for automatic computation of the purchase order planning.',
                ],
            ],

            'prices' => [
                'title'  => 'Prices',

                'fields' => [
                    'product'            => 'Product',
                    'quantity'           => 'Quantity',
                    'quantity-tooltip'   => 'The quantity to purchase from this vendor to benefit from the price, expressed in the vendor Product Unit of Measure if not any, in the default unit of measure of the product otherwise.',
                    'unit-price'         => 'Unit Price',
                    'unit-price-tooltip' => 'The price for a single unit of this product from this vendor, in the vendor Product Unit of Measure if not any, in the default unit of measure of the product otherwise.',
                    'currency'           => 'Currency',
                    'valid-from'         => 'Valid From',
                    'valid-to'           => 'Valid Until',
                    'discount'           => 'Discount (%)',
                    'company'            => 'Company',
                ],
            ],
        ],
    ],

    'table' => [
        'columns' => [
            'vendor'              => 'Vendor',
            'vendor-product-name' => 'Vendor Product Name',
            'vendor-product-code' => 'Vendor Product Code',
            'delay'               => 'Delivery Lead Time (Days)',
            'product'             => 'Product',
            'quantity'            => 'Quantity',
            'unit-price'          => 'Unit Price',
            'currency'            => 'Currency',
            'valid-from'          => 'Valid From',
            'valid-to'            => 'Valid Until',
            'discount'            => 'Discount (%)',
            'company'             => 'Company',
            'created-at'          => 'Created At',
            'updated-at'          => 'Updated At',
        ],

        'filters' => [
            'vendor'        => 'Filter by Vendor',
            'product'       => 'Filter by Product',
            'currency'      => 'Filter by Currency',
            'company'       => 'Filter by Company',
            'price-from'    => 'Minimum Price',
            'price-to'      => 'Maximum Price',
            'min-qty-from'  => 'Minimum Quantity From',
            'min-qty-to'    => 'Minimum Quantity To',
            'starts-from'   => 'Valid From Date',
            'ends-before'   => 'Valid To Date',
            'created-from'  => 'Created From',
            'created-until' => 'Created Until',
        ],

        'groups' => [
            'vendor'     => 'Vendor',
            'product'    => 'Product',
            'created-at' => 'Created At',
            'updated-at' => 'Updated At',
        ],

        'actions' => [
            'delete' => [
                'notification' => [
                    'title' => 'Vendor Price deleted',
                    'body'  => 'The vendor price has been deleted successfully.',
                ],
            ],
        ],

        'bulk-actions' => [
            'delete' => [
                'notification' => [
                    'title' => 'Vendor Price deleted',
                    'body'  => 'The vendor price has been deleted successfully.',
                ],
            ],
        ],
    ],

    'infolist' => [
        'sections' => [
            'general' => [
                'title'  => 'General',

                'entries' => [
                    'vendor'                      => 'Vendor',
                    'vendor-product-name'         => 'Vendor Product Name',
                    'vendor-product-name-tooltip' => 'This vendor\'s product name will be used when printing a request for quotation. Keep empty to use the internal one.',
                    'vendor-product-code'         => 'Vendor Product Code',
                    'vendor-product-code-tooltip' => 'This vendor\'s product code will be used when printing a request for quotation. Keep empty to use the internal one.',
                    'delay'                       => 'Delivery Lead Time (Days)',
                    'delay-tooltip'               => 'Lead time in days between the confirmation of the purchase order and the receipt of the products in your warehouse. Used by the scheduler for automatic computation of the purchase order planning.',
                ],
            ],

            'record-information' => [
                'title'  => 'Record Information',

                'entries' => [
                    'created-by'   => 'Created By',
                    'created-at'   => 'Created At',
                    'last-updated' => 'Last Updated',
                ],
            ],

            'prices' => [
                'title'  => 'Prices',

                'entries' => [
                    'product'            => 'Product',
                    'quantity'           => 'Quantity',
                    'quantity-tooltip'   => 'The quantity to purchase from this vendor to benefit from the price, expressed in the vendor Product Unit of Measure if not any, in the default unit of measure of the product otherwise.',
                    'unit-price'         => 'Unit Price',
                    'unit-price-tooltip' => 'The price for a single unit of this product from this vendor, in the vendor Product Unit of Measure if not any, in the default unit of measure of the product otherwise.',
                    'currency'           => 'Currency',
                    'valid-from'         => 'Valid From',
                    'valid-to'           => 'Valid Until',
                    'discount'           => 'Discount (%)',
                    'company'            => 'Company',
                ],
            ],
        ],
    ],
];
