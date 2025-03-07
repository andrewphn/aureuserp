<?php

return [
    'navigation' => [
        'title' => 'Blog Posts',
        'group' => 'Website',
    ],

    'form' => [
        'sections' => [
        ],
    ],

    'table' => [
        'columns' => [
            'title' => 'Title',
            'slug' => 'Slug',
            'author' => 'Author',
            'category' => 'Category',
            'creator' => 'Created By',
            'is-published' => 'Is Published',
            'created-at' => 'Created At',
            'updated-at' => 'Updated At',
        ],

        'groups' => [
            'category'   => 'Category',
            'author'     => 'Author',
            'created-at' => 'Created At',
        ],

        'filters' => [
            'company'  => 'Company',
        ],

        'actions' => [
            'edit' => [
                'notification' => [
                    'title' => 'Blog Post updated',
                    'body'  => 'The blog post has been updated successfully.',
                ],
            ],

            'restore' => [
                'notification' => [
                    'title' => 'Blog Post restored',
                    'body'  => 'The blog post has been restored successfully.',
                ],
            ],

            'delete' => [
                'notification' => [
                    'title' => 'Blog Post deleted',
                    'body'  => 'The blog post has been deleted successfully.',
                ],
            ],

            'force-delete' => [
                'notification' => [
                    'title' => 'Blog Post force deleted',
                    'body'  => 'The blog post has been force deleted successfully.',
                ],
            ],
        ],

        'bulk-actions' => [
            'restore' => [
                'notification' => [
                    'title' => 'Blog Posts restored',
                    'body'  => 'The blog posts has been restored successfully.',
                ],
            ],

            'delete' => [
                'notification' => [
                    'title' => 'Blog Posts deleted',
                    'body'  => 'The blog posts has been deleted successfully.',
                ],
            ],

            'force-delete' => [
                'notification' => [
                    'title' => 'Blog Posts force deleted',
                    'body'  => 'The blog posts has been force deleted successfully.',
                ],
            ],
        ],
    ],

    'infolist' => [
    ],
];
