<?php

// OSSZ ATELIER — EN lines, ported from domain-label keys of common.ts.
// Accessed via __('labels.key').

return [
    'status' => [
        'UNASSIGNED' => 'Unassigned',
        'ASSIGNED' => 'Assigned',
        'ONGOING' => 'Ongoing',
        'AWAITING_INSPECTION' => 'Awaiting Inspection',
        'CHANGES_REQUESTED' => 'Changes Requested',
        'APPROVED' => 'Approved',
        'READY_FOR_PICKUP' => 'Ready for Pickup',
        'DELIVERED' => 'Delivered/Collected',
        'BLOCKED' => 'Blocked',
        'CANCELLED' => 'Cancelled',
    ],
    'priority' => [
        'LOW' => 'Low',
        'NORMAL' => 'Normal',
        'HIGH' => 'High',
        'URGENT' => 'Urgent',
    ],
    'category' => [
        'FULL' => 'Full Production',
        'CUTTING' => 'Cutting Only',
        'SEWING' => 'Sewing/Stitching Only',
        'FINISHING' => 'Finishing',
        'IRONING' => 'Ironing Only',
        'OTHER' => 'Other Activity',
    ],
    'stageState' => [
        'NOT_STARTED' => 'Not Started',
        'IN_PROGRESS' => 'In Progress',
        'COMPLETED' => 'Completed',
        'NOT_APPLICABLE' => 'Not Applicable',
    ],
    'role' => [
        'MANAGEMENT' => 'Management',
        'ADMIN' => 'Admin',
        'STAFF' => 'Staff',
    ],
    'inspection' => [
        'APPROVED' => 'Approved',
        'CHANGES_REQUESTED' => 'Changes Requested',
        'REOPENED' => 'Reopened',
    ],
    'incentivePeriod' => [
        'MONTHLY' => 'Monthly productivity',
        'QUARTERLY' => 'Quarterly performance',
        'ANNUAL' => 'Annual performance',
        'QUALITY' => 'Quality achievements',
        'ON_TIME' => 'On-time completion',
        'CUSTOM' => 'Management-defined target',
    ],
    'incStatus' => [
        'PENDING' => 'Pending',
        'APPROVED' => 'Approved',
        'REJECTED' => 'Rejected',
    ],
    'paymentStatus' => [
        'UNPAID' => 'Unpaid',
        'PAID' => 'Paid',
    ],
    'notifTitle' => [
        'TASK_ASSIGNED' => 'New task assigned',
        'TASK_REASSIGNED' => 'Task reassigned to you',
        'DEADLINE_CHANGED' => 'Deadline changed',
        'NEW_INSTRUCTIONS' => 'New instructions',
        'CORRECTIONS_REQUESTED' => 'Corrections requested',
        'BLOCK_RESOLVED' => 'Block resolved',
        'TASK_APPROVED' => 'Work approved',
        'TASK_REOPENED' => 'Task reopened',
        'SUBMITTED_FOR_INSPECTION' => 'Work submitted for inspection',
        'TASK_OVERDUE' => 'Task overdue',
        'ISSUE_REPORTED' => 'Production issue reported',
        'TASK_BLOCKED' => 'Task blocked',
        'READY_FOR_PICKUP' => 'Ready for pickup',
        'TASK_DELIVERED' => 'Garment delivered/collected',
        'TASK_CANCELLED' => 'Task cancelled',
    ],
];
