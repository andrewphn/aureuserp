<?php

return [
    'title' => 'Quotations',

    'navigation' => [
        'title' => 'Quotations',
    ],

    'form' => [
        'tabs' => [
            'products' => [
                'title' => 'Products',

                'fields' => [
                    'product'      => 'Product',
                    'total'        => 'Total',
                    'subtotal'     => 'Sub Total',
                    'unit-price'   => 'Unit Price',
                    'lead-time'    => 'Lead Time',
                    'taxes'        => 'Taxes',
                    'quantity'     => 'Quantity',
                    'display-type' => 'Display Type',
                    'name'         => 'Name',
                ],
            ],
            'other-information' => [
                'title' => 'Other Information',

                'fieldset' => [
                    'sales' => [
                        'title' => 'Sales',

                        'fields' => [
                            'sales-person'       => 'Sales Person',
                            'sales-team'         => 'Sales Team',
                            'customer-reference' => 'Customer Reference',

                            'fieldset' => [
                                'signature-and-payment' => [
                                    'title'  => 'Signature & Payment',
                                    'fields' => [
                                        'online-signature'      => 'Online Signature',
                                        'online-payment'        => 'Online Payment',
                                        'prepayment-percentage' => 'Prepayment Percentage',
                                    ],
                                ],
                            ],
                        ],
                    ],
                    'invoicing' => [
                        'title' => 'Invoicing',

                        'fields' => [
                            'fiscal-position'   => 'Fiscal Position',
                            'invoicing-journal' => 'Invoicing Journal',
                        ],
                    ],

                    'shipping' => [
                        'title' => 'Shipping',

                        'fields' => [
                            'commitment-date' => 'Delivery Date',
                        ],
                    ],

                    'tracking' => [
                        'title' => 'Tracking',

                        'fields' => [
                            'source-document' => 'Source Document',
                            'medium'          => 'Medium',
                            'source'          => 'Source',
                        ],
                    ],
                ],
            ],
            'term-and-conditions' => [
                'title' => 'Term & Conditions',
            ],
        ],

        'fields' => [
            'customer'           => 'Customer',
            'payment-terms'      => 'Payment Terms',
            'quotation-template' => 'Quotation Template',
        ],

        'fieldset' => [
            'invoice-and-delivery-addresses' => [
                'title' => 'Invoice & Delivery Addresses',

                'fields' => [
                    'invoice-address'  => 'Invoice Address',
                    'delivery-address' => 'Delivery Address',
                ],
            ],
            'expiration-and-quotation-date' => [
                'title' => 'Expiration & Quotation Date',

                'fields' => [
                    'expiration-date' => 'Expiration Date',
                    'quotation-date'  => 'Quotation Date',
                ],
            ],
        ],
    ],

    'table' => [
        'columns' => [
            'number'             => 'Number',
            'status'             => 'Status',
            'invoice-status'     => 'Invoice Status',
            'creation-date'      => 'Creation Date',
            'commitment-date'    => 'Commitment Date',
            'expected-date'      => 'Expected Date',
            'customer'           => 'Customer',
            'sales-person'       => 'Sales Person',
            'sales-team'         => 'Sales Team',
            'untaxed-amount'     => 'Untaxed Amount',
            'amount-tax'         => 'Amount Tax',
            'amount-total'       => 'Amount Total',
            'customer-reference' => 'Customer Reference',
        ],

        'filters' => [
            'sales-person'     => 'Sales Person',
            'utm-source'       => 'UTM Source',
            'company'          => 'Company',
            'customer'         => 'Customer',
            'journal'          => 'Journal',
            'invoice-address'  => 'Invoice Address',
            'shipping-address' => 'Shipping Address',
            'fiscal-position'  => 'Fiscal Position',
            'payment-term'     => 'Payment Term',
            'currency'         => 'Currency',
            'created-at'       => 'Created At',
            'updated-at'       => 'Updated At',
        ],

        'groups' => [
            'medium'          => 'Medium',
            'source'          => 'Source',
            'team'            => 'Team',
            'sales-person'    => 'Sales Person',
            'currency'        => 'Currency',
            'company'         => 'Company',
            'customer'        => 'Customer',
            'quotation-date'  => 'Quotation Date',
            'commitment-date' => 'Commitment Date',
        ],

        'actions' => [
            'restore' => [
                'notification' => [
                    'title' => 'Quotation restored',
                    'body'  => 'The quotation has been restored successfully.',
                ],
            ],

            'delete' => [
                'notification' => [
                    'title' => 'Quotation deleted',
                    'body'  => 'The quotation has been deleted successfully.',
                ],
            ],

            'force-delete' => [
                'notification' => [
                    'title' => 'Quotation force deleted',
                    'body'  => 'The quotation has been force deleted successfully.',
                ],
            ],
        ],

        'bulk-actions' => [
            'restore' => [
                'notification' => [
                    'title' => 'Quotations restored',
                    'body'  => 'The quotations has been restored successfully.',
                ],
            ],

            'delete' => [
                'notification' => [
                    'title' => 'Quotations deleted',
                    'body'  => 'The quotations has been deleted successfully.',
                ],
            ],

            'force-delete' => [
                'notification' => [
                    'title' => 'Quotations force deleted',
                    'body'  => 'The quotations has been force deleted successfully.',
                ],
            ],
        ],

        'empty-state-action' => [
            'create' => [
                'notification' => [
                    'title' => 'Quotations created',
                    'body'  => 'The quotations has been created successfully.',
                ],
            ],
        ],
    ],

    'infolist' => [
        'tabs' => [
            'products' => [
                'title'  => 'Products',
                'fields' => [
                    'product'      => 'Product',
                    'total'        => 'Total',
                    'subtotal'     => 'Sub Total',
                    'unit-price'   => 'Unit Price',
                    'lead-time'    => 'Lead Time',
                    'taxes'        => 'Taxes',
                    'quantity'     => 'Quantity',
                    'display-type' => 'Display Type',
                    'name'         => 'Name',
                ],
            ],
            'other-information' => [
                'title'    => 'Other Information',
                'fieldset' => [
                    'sales' => [
                        'title'  => 'Sales',
                        'fields' => [
                            'sales-person'       => 'Sales Person',
                            'sales-team'         => 'Sales Team',
                            'customer-reference' => 'Customer Reference',
                            'fieldset'           => [
                                'signature-and-payment' => [
                                    'title'  => 'Signature & Payment',
                                    'fields' => [
                                        'online-signature'      => 'Online Signature',
                                        'online-payment'        => 'Online Payment',
                                        'prepayment-percentage' => 'Prepayment Percentage',
                                    ],
                                ],
                            ],
                        ],
                    ],
                    'invoicing' => [
                        'title'  => 'Invoicing',
                        'fields' => [
                            'fiscal-position'   => 'Fiscal Position',
                            'invoicing-journal' => 'Invoicing Journal',
                        ],
                    ],
                    'shipping' => [
                        'title'  => 'Shipping',
                        'fields' => [
                            'commitment-date' => 'Delivery Date',
                        ],
                    ],
                    'tracking' => [
                        'title'  => 'Tracking',
                        'fields' => [
                            'source-document' => 'Source Document',
                            'medium'          => 'Medium',
                            'source'          => 'Source',
                        ],
                    ],
                ],
            ],
            'term-and-conditions' => [
                'title' => 'Terms & Conditions',
            ],
        ],
        'fields' => [
            'customer'           => 'Customer',
            'payment-terms'      => 'Payment Terms',
            'partner-address'    => 'Partner Address',
            'quotation-template' => 'Quotation Template',
        ],
        'fieldset' => [
            'invoice-and-delivery-addresses' => [
                'title'  => 'Invoice & Delivery Addresses',
                'fields' => [
                    'invoice-address'  => 'Invoice Address',
                    'delivery-address' => 'Delivery Address',
                ],
            ],
            'expiration-and-quotation-date' => [
                'title'  => 'Expiration & Quotation Date',
                'fields' => [
                    'expiration-date' => 'Expiration Date',
                    'quotation-date'  => 'Quotation Date',
                ],
            ],
        ],
    ],
];
