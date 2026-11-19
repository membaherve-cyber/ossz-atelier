<?php

// OSSZ ATELIER — EN lines, staff advances (accounting enhancement §9).
// Accessed via __('adv.key').

return [
    'title' => 'Staff Advances',
    'subtitle' => 'Money advanced to staff before salary payment, and its recovery through payroll.',
    'nav' => 'Staff Advances',

    'record' => 'Record Advance',
    'filterStaff' => 'Staff member',
    'allStaff' => 'All staff',
    'filterStatus' => 'Status',
    'allStatuses' => 'All statuses',
    'clear' => 'Clear',

    'colReference' => 'Reference',
    'colStaff' => 'Staff Member',
    'colAmount' => 'Amount',
    'colRecovered' => 'Recovered',
    'colOutstanding' => 'Outstanding',
    'colIssued' => 'Date Issued',
    'colStatus' => 'Status',
    'colActions' => 'Actions',

    'fieldStaff' => 'Staff member',
    'fieldAmount' => 'Amount',
    'fieldDateIssued' => 'Date issued',
    'fieldReason' => 'Reason',
    'fieldMethod' => 'Payment method',
    'fieldRepaymentPlan' => 'Repayment arrangement',
    'selectStaff' => 'Select a staff member…',
    'repaymentPlaceholder' => 'e.g. recovered from next two payrolls',

    'reviewApprove' => 'Approve',
    'reviewReject' => 'Reject',
    'disburse' => 'Mark Disbursed',
    'disburseConfirm' => 'Confirm the money has been handed to the staff member?',
    'cancelAdvance' => 'Cancel',
    'cancelConfirm' => 'Cancel this advance?',
    'approveConfirm' => 'Approve this advance?',
    'rejectReason' => 'Rejection reason',

    'deleteAdvance' => 'Delete Advance',
    'deleteConfirm' => 'Delete this advance record?',
    'deleteBlocked' => 'Disbursed advances cannot be deleted — they must be recovered.',

    'recoveryHistory' => 'Recovery history',
    'recoveredThrough' => 'Recovered via {payroll} on {date}',
    'noRecoveries' => 'No recoveries recorded yet.',

    'emptyTitle' => 'No advances recorded yet',
    'emptyFiltersTitle' => 'No advances match your filters',
    'emptyFiltersDesc' => 'Adjust the staff or status filters to see more results.',
    'emptyDesc' => 'Record an advance when staff receive money before salary payment.',

    'createdToast' => 'Advance recorded.',
    'approvedToast' => 'Advance approved.',
    'rejectedToast' => 'Advance rejected.',
    'disbursedToast' => 'Advance marked as disbursed.',
    'cancelledToast' => 'Advance cancelled.',
    'deletedToast' => 'Advance deleted.',
    'errStaff' => 'Select a staff member.',
    'errAmount' => 'Enter the amount.',
    'errDate' => 'Enter a valid date issued.',
    'errReason' => 'Enter the reason for the advance.',

    'note' => 'Recovered advances are deducted from payroll net pay and never counted as new expenses.',
];
