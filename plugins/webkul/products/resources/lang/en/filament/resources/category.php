<?php

return [
    'form' => [
        'sections' => [
            'general' => [
                'title'  => 'General',

                'fields' => [
                    'name'             => 'Name',
                    'name-placeholder' => 'eg. Lamps',
                    'parent'           => 'Parent',
                ],
            ],
        ],
    ],

    'table' => [
        'columns' => [
            'name'        => 'Name',
            'full-name'   => 'Full Name',
            'parent-path' => 'Parent Path',
            'parent'      => 'Parent',
            'creator'     => 'Creator',
            'created-at'  => 'Created At',
            'created-at'  => 'Created At',
            'updated-at'  => 'Updated At',
        ],

        'groups' => [
            'parent'     => 'Parent',
            'creator' => 'Creator',
            'created-at' => 'Created At',
            'updated-at' => 'Updated At',
        ],

        'filters' => [
            'parent' => 'Parent',
            'creator' => 'Creator',
        ],

        'actions' => [
            'delete' => [
                'notification' => [
                    'title' => 'Category deleted',
                    'body'  => 'The Category has been deleted successfully.',
                ],
            ],
        ],

        'bulk-actions' => [
            'delete' => [
                'notification' => [
                    'title' => 'Categories deleted',
                    'body'  => 'The categories has been deleted successfully.',
                ],
            ],
        ],
    ],

    'infolist' => [
        'sections' => [
            'general' => [
                'title' => 'General Information',

                'entries' => [
                    'name'        => 'Name',
                    'parent'      => 'Parent Category',
                    'full_name'   => 'Full Category Name',
                    'parent_path' => 'Category Path',
                ],
            ],

            'record-information' => [
                'title' => 'Record Information',

                'entries' => [
                    'creator'    => 'Created By',
                    'created_at' => 'Created At',
                    'updated_at' => 'Last Updated At',
                ],
            ],
        ],
    ],
];
