<?php

return [
    'notification' => [
        'title' => 'Receipt updated',
        'body'  => 'The receipt has been updated successfully.',
    ],

    'header-actions' => [
        'print' => [
            'label' => 'Print',
        ],

        'delete' => [
            'notification' => [
                'title' => 'Receipt deleted',
                'body'  => 'The receipt has been deleted successfully.',
            ],
        ],
    ],
];
