<?php

return [
    'title' => 'Invoices',

    'navigation' => [
        'title' => 'Invoices',
        'group' => 'Invoices'
    ],

    'global-search' => [
        'number'           => 'Number',
        'customer'         => 'Customer',
        'invoice-date'     => 'Invoice Date',
        'invoice-due-date' => 'Invoice Due Date',
    ],

    'form' => [
        'tabs' => [
            'products' => [
                'title' => 'Products',

                'repeater' => [
                    'products' => [
                        'title' => 'Products',
                        'add-product-line' => 'Add Product Line',

                        'fields' => [
                            'product'             => 'Product',
                            'quantity'            => 'Quantity',
                            'taxes'               => 'Taxes',
                            'discount-percentage' => 'Discount (%)',
                            'unit-price'          => 'Unit Price',
                            'sub-total'           => 'Sub Total',
                            'total'               => 'Total',
                        ]
                    ],
                    'section' => [
                        'title' => 'Add a section',
                    ],

                    'note' => [
                        'title' => 'Add a note',
                    ]
                ]
            ],

            'other-information' => [
                'title' => 'Other Information',

                'fields' => [
                    'fieldset' => [
                        'invoice' => [
                            'title' => 'Invoice',
                            'fields' => [
                                'customer-reference' => 'Customer Reference',
                                'sale-person'        => 'Sale Person',
                                'sales-team'         => 'Sales Team',
                                'recipient-bank'     => 'Recipient Bank',
                                'payment-reference'  => 'Payment Reference',
                                'delivery-date'      => 'Delivery Date',
                                'sales-person'       => 'Sales Person',
                            ]
                        ],

                        'accounting' => [
                            'title' => 'Accounting',
                            'fields' => [
                                'incoterm'          => 'Incoterm',
                                'incoterm-location' => 'Incoterm Location',
                                'fiscal-position'   => 'Fiscal Position',
                                'payment-method'    => 'Payment Method',
                                'auto-post'         => 'Auto Post',
                                'checked'           => 'Checked',
                            ]
                        ],
                    ],
                ]
            ],

            'term-and-conditions' => [
                'title' => 'Term & Conditions',

                'fields' => [
                    'narration' => 'Term & Conditions',
                ]
            ]
        ],

        'section' => [
            'fieldset' => [
                'general' => [
                    'title' => 'General',

                    'fields' => [
                        'customer' => 'Customer',
                    ]
                ],

                'invoice-date-and-payment-term' => [
                    'title' => 'Invoice Date & Payment Term',

                    'fields' => [
                        'invoice-date'     => 'Invoice Date',
                        'due-date'         => 'Due Date',
                        'payment-term'     => 'Payment Term',
                    ]
                ],

                'marketing' => [
                    'title' => 'Marketing',

                    'fields' => [
                        'campaign' => 'Campaign',
                        'medium'   => 'Medium',
                        'source'   => 'Source',
                    ]
                ]
            ]
        ]
    ],

    'table' => [
        'columns' => [],

        'groups' => [
            'created-at' => 'Created At',
            'updated-at' => 'Updated At',
        ],

        'filters' => [
            'created-at'                       => 'Created At',
            'updated-at'                       => 'Updated At',
        ],

        'actions' => [
            'delete' => [
                'notification' => [
                    'title' => 'Payment deleted',
                    'body'  => 'The payment has been deleted successfully.',
                ],
            ],
        ],

        'bulk-actions' => [
            'delete' => [
                'notification' => [
                    'title' => 'Payments deleted',
                    'body'  => 'The payments has been deleted successfully.',
                ],
            ],
        ],
    ],


    'infolist' => [
        'sections' => [
            'payment-information' => [
                'title' => 'Payment Information',
                'entries' => [
                    'state'        => 'State',
                    'payment-type' => 'Payment Type',
                    'journal'      => 'Journal',
                    'customer-bank-account' => 'Customer Bank Account',
                    'customer'     => 'Customer',
                ]
            ],

            'payment-details' => [
                'title' => 'Payment Details',
                'entries' => [
                    'amount' => 'Amount',
                    'date' => 'Date',
                    'memo' => 'Memo',
                ]
            ],

            'payment-method' => [
                'title' => 'Payment Method',
                'entries' => [
                    'payment-method' => 'Payment Method',
                ]
            ]
        ]
    ],

];
