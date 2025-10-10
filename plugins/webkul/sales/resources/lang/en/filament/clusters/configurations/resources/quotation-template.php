<?php

return [
    'title' => 'Quotation Template',

    'navigation' => [
        'title'  => 'Quotation Template',
        'group'  => 'Sales Orders',
    ],

    'form' => [
        'fields' => [
            'name'                  => 'Template Name',
            'number_of_days'        => 'Validity (Days)',
            'journal'               => 'Sale Journal',
            'note'                  => 'Terms & Conditions',
            'is_active'             => 'Active',
            'require_signature'     => 'Require Signature',
            'require_payment'       => 'Require Payment',
            'prepayment_percentage' => 'Prepayment %',
        ],

        'tabs' => [
            'products' => [
                'title'  => 'Products',
                'fields' => [
                    'products'     => 'Products',
                    'name'         => 'Name',
                    'quantity'     => 'Quantity',
                ],
            ],

            'terms-and-conditions' => [
                'title'  => 'Terms & Conditions',
                'fields' => [
                    'note-placeholder' => 'Write your terms and conditions for the quotations.',
                ],
            ],
        ],

        'sections' => [
            'general' => [
                'title' => 'General Information',

                'fields' => [
                    'name'               => 'Name',
                    'quotation-validity' => 'Quotation Validity',
                    'sale-journal'       => 'Sale Journal',
                ],
            ],

            'signature-and-payment' => [
                'title' => 'Signature & Payments',

                'fields' => [
                    'online-signature'      => 'Online Signature',
                    'online-payment'        => 'Online Payment',
                    'prepayment-percentage' => 'Prepayment Percentage',
                ],
            ],
        ],
    ],

    'table' => [
        'columns' => [
            'created-by'         => 'Created by',
            'company'            => 'Company',
            'name'               => 'Name',
            'number_of_days'     => 'Validity',
            'journal'            => 'Journal',
            'is_active'          => 'Active',
            'require_signature'  => 'Signature',
            'require_payment'    => 'Payment',
        ],
        'groups'  => [
            'company' => 'Company',
            'name'    => 'Name',
            'journal' => 'Journal',
        ],
        'filters' => [
            'created-by' => 'Created By',
            'company'    => 'Company',
            'name'       => 'Name',
            'created-at' => 'Created At',
            'updated-at' => 'Updated At',
        ],
        'actions' => [
            'edit' => [
                'notification' => [
                    'title' => 'Template updated',
                    'body'  => 'The quotation template has been updated successfully.',
                ],
            ],
            'delete' => [
                'notification' => [
                    'title' => 'Template deleted',
                    'body'  => 'The quotation template has been deleted successfully.',
                ],
            ],
        ],
        'bulk-actions' => [
            'delete' => [
                'notification' => [
                    'title' => 'Templates deleted',
                    'body'  => 'The quotation templates have been deleted successfully.',
                ],
            ],
        ],
    ],

    'infolist' => [
        'tabs' => [
            'products' => [
                'title' => 'Products',
            ],
            'terms-and-conditions' => [
                'title' => 'Terms & Conditions',
            ],
        ],
        'sections' => [
            'general' => [
                'title' => 'General Information',
            ],
            'signature_and_payment' => [
                'title' => 'Signature & Payment',
            ],
        ],
        'entries' => [
            'product'               => 'Product',
            'description'           => 'Description',
            'quantity'              => 'Quantity',
            'unit-price'            => 'Unit Price',
            'section-name'          => 'Section Name',
            'note-title'            => 'Note Title',
            'name'                  => 'Template Name',
            'number_of_days'        => 'Validity',
            'journal'               => 'Journal',
            'note'                  => 'Terms & Conditions',
            'is_active'             => 'Status',
            'require_signature'     => 'Signature Required',
            'require_payment'       => 'Payment Required',
            'prepayment_percentage' => 'Prepayment %',
        ],
    ],
];
