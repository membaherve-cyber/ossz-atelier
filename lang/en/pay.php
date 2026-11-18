<?php

// OSSZ ATELIER — EN lines, income and customer payments (accounting enhancement §6).
// Accessed via __('pay.key').

return [
    'title' => 'Income and Payments',
    'subtitle' => 'Customer payments received against registered orders.',
    'nav' => 'Income and Payments',

    'record' => 'Record Payment',
    'searchPlaceholder' => 'Search reference, order number, customer…',
    'filterMethod' => 'Payment method',
    'allMethods' => 'All methods',
    'fromDate' => 'From date',
    'toDate' => 'To date',
    'clear' => 'Clear',

    'colReference' => 'Reference',
    'colOrder' => 'Order',
    'colCustomer' => 'Customer',
    'colAmount' => 'Amount',
    'colMethod' => 'Method',
    'colDate' => 'Payment Date',
    'colTxn' => 'Transaction Ref',
    'colRecordedBy' => 'Recorded By',
    'colActions' => 'Actions',

    'fieldOrder' => 'Order',
    'fieldAmount' => 'Amount received',
    'fieldDate' => 'Payment date',
    'fieldMethod' => 'Payment method',
    'fieldTxn' => 'Transaction reference (optional)',
    'fieldNotes' => 'Notes',
    'orderValue' => 'Total order value',
    'alreadyPaid' => 'Already paid',
    'outstanding' => 'Outstanding balance after this payment',
    'noValueRecorded' => 'This order has no value recorded yet — set the order value first.',
    'selectOrder' => 'Select an order…',

    'editPayment' => 'Edit Payment',
    'deletePayment' => 'Delete Payment',
    'deleteConfirm' => 'Delete this payment record? The outstanding balance will be recalculated.',
    'deleteHasAuditNote' => 'The deletion is recorded in the audit history.',

    'emptyTitle' => 'No payments recorded yet',
    'emptyFiltersTitle' => 'No payments match your filters',
    'emptyDesc' => 'Record a customer payment to start tracking income.',
    'emptyFiltersDesc' => 'Adjust the search or date filters to see more results.',

    'createdToast' => 'Payment recorded.',
    'updatedToast' => 'Payment updated.',
    'deletedToast' => 'Payment deleted.',
    'errAmount' => 'Enter the amount received.',
    'errOrder' => 'Select the order this payment belongs to.',
    'errDate' => 'Enter a valid payment date.',
    'errExceedsBalance' => 'This amount exceeds the outstanding balance of the order.',
    'errNoValue' => 'The selected order has no total value recorded. Edit the order first.',
];
