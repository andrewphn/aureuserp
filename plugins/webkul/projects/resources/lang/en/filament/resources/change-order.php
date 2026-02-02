<?php

return [
    'navigation' => [
        'title' => 'Change Orders',
        'group' => 'Projects',
    ],

    'form' => [
        'sections' => [
            'general' => [
                'title' => 'General Information',
            ],
            'details' => [
                'title' => 'Change Details',
            ],
            'impact' => [
                'title' => 'Impact Assessment',
            ],
        ],
        'fields' => [
            'project_id' => 'Project',
            'title' => 'Title',
            'description' => 'Description',
            'reason' => 'Reason',
            'affected_stage' => 'Affected Stage',
            'price_delta' => 'Price Impact',
            'labor_hours_delta' => 'Labor Hours Impact',
        ],
    ],

    'table' => [
        'columns' => [
            'change_order_number' => 'CO #',
            'title' => 'Title',
            'project' => 'Project',
            'status' => 'Status',
            'reason' => 'Reason',
            'price_delta' => 'Price Impact',
            'requested_at' => 'Requested',
        ],
        'filters' => [
            'status' => 'Status',
            'reason' => 'Reason',
            'project' => 'Project',
        ],
    ],

    'actions' => [
        'print' => [
            'label' => 'Print Change Order',
        ],
        'submit' => [
            'label' => 'Submit for Approval',
        ],
        'approve' => [
            'label' => 'Approve',
        ],
        'reject' => [
            'label' => 'Reject',
        ],
        'apply' => [
            'label' => 'Apply Changes',
        ],
        'cancel' => [
            'label' => 'Cancel',
        ],
    ],

    'notifications' => [
        'submitted' => [
            'title' => 'Change Order Submitted',
            'body' => 'Change order has been submitted for approval.',
        ],
        'approved' => [
            'title' => 'Change Order Approved',
            'body' => 'Change order has been approved.',
        ],
        'rejected' => [
            'title' => 'Change Order Rejected',
            'body' => 'Change order has been rejected.',
        ],
        'applied' => [
            'title' => 'Changes Applied',
            'body' => 'All changes have been applied to the project.',
        ],
        'cancelled' => [
            'title' => 'Change Order Cancelled',
            'body' => 'Change order has been cancelled.',
        ],
    ],
];
