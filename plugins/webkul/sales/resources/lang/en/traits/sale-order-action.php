<?php

return [
    'header-actions' => [
        'confirm' => [
            'title' => 'Confirm',

            'notification' => [
                'confirmed' => [
                    'title' => 'Quotation confirmed',
                    'body'  => 'Quotation has been confirmed and converted to sale successfully.',
                ],
            ],
        ],

        'back-to-quotation' => [
            'title' => 'Set as Quotation',

            'notification' => [
                'back-to-quotation' => [
                    'title' => 'Sale order set as Quotation',
                    'body'  => 'Sale order has been set as Quotation successfully.',
                ],
            ],
        ],

        'preview' => [
            'title' => 'Preview',

            'modal' => [
                'heading' => 'Preview Quotation',
            ],
        ],

        'create-invoice' => [
            'title' => 'Create Invoice',

            'modal' => [
                'heading' => 'Create Invoice',
            ],
        ],

        'send-by-email' => [
            'title' => 'Send By Email',

            'modal' => [
                'heading' => 'Send Quotation By Email',
            ],

            'form' => [
                'fields' => [
                    'partners'    => 'Partners',
                    'subject'     => 'Subject',
                    'description' => 'Description',
                    'attachment'  => 'Attachment',
                ],
            ],

            'actions' => [
                'notification' => [
                    'title' => 'Quotation sent',
                    'body'  => 'Quotation has been sent successfully.',
                ],
            ],
        ],

        'cancel' => [
            'title' => 'Cancel',
            'modal' => [
                'heading'     => 'Cancel Quotation',
                'description' => 'Are you sure you want to cancel this Quotation?',
            ],

            'footer-actions' => [
                'send-and-cancel' => [
                    'title' => 'Send & Cancel',

                    'notification' => [
                        'cancelled' => [
                            'title' => 'Quotation cancelled',
                            'body'  => 'Quotation has been cancelled and email has been sent successfully.',
                        ],
                    ],
                ],

                'cancel' => [
                    'title' => 'Cancel',

                    'notification' => [
                        'cancelled' => [
                            'title' => 'Quotation cancelled',
                            'body'  => 'Quotation has been cancelled successfully.',
                        ],
                    ],
                ],

                'close' => [
                    'title' => 'Close',
                ],
            ],

            'form' => [
                'fields' => [
                    'partner'             => 'Partner',
                    'subject'             => 'Subject',
                    'subject-placeholder' => 'Subject',
                    'subject-default'     => 'Quotation :name has been cancelled for Sales Order #:id',
                    'description'         => 'Description',
                    'description-default' => 'Dear <b>:partner_name</b>, <br/><br/>We would like to inform you that your Sales Order <b>:name</b> has been cancelled. As a result, no further charges will apply to this order. If a refund is required, it will be processed at the earliest convenience.<br/><br/>Should you have any questions or require further assistance, please feel free to reach out to us.',
                ],
            ],
        ],
    ],
];
