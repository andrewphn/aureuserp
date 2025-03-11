<?php

return [
    'label' => 'Cancel',

    'action' => [
        'notification' => [
            'warning' => [
                'title' => 'Cannot cancel order',
                'body'  => 'The order cannot be canceled. You must first cancel their related vendor bills.',
            ],

            'success' => [
                'title' => 'Order canceled',
                'body'  => 'The order has been canceled successfully.',
            ],
        ],
    ],
];
