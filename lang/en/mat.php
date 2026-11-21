<?php

// OSSZ ATELIER — EN lines, materials and stock (Finance & Comptabilité spec §5).
// Accessed via __('mat.key').

return [
    'indexTitle' => 'Materials & Stock',
    'indexSubtitle' => 'Raw materials, stock levels and traceability',

    // ---- Materials --------------------------------------------------------
    'addMaterial' => 'Add material',
    'editMaterial' => 'Edit material',
    'materialName' => 'Material',
    'reference' => 'Reference',
    'category' => 'Category',
    'unit' => 'Unit',
    'openingQty' => 'Opening quantity',
    'openingCost' => 'Unit cost (XAF)',
    'clientSupplied' => 'Supplied by the client (never valued)',
    'stockQty' => 'In stock',
    'avgCost' => 'Average cost',
    'stockValue' => 'Stock value',
    'adjustQty' => 'Stock adjustment (± qty)',
    'adjustNote' => 'Adjustment reason',
    'ledger' => 'Stock movements',
    'materialCreated' => 'Material created.',
    'materialUpdated' => 'Material updated.',

    // ---- Material issues (remises) ----------------------------------------
    'issuesTitle' => 'Material issues (remises)',
    'issuesSubtitle' => 'Materials handed to employees for a production order',
    'newIssue' => 'New issue to an employee',
    'issueCode' => 'Issue',
    'issueDate' => 'Date',
    'beneficiary' => 'Employee (beneficiary)',
    'garment' => 'Garment / piece (optional)',
    'operation' => 'Operation / task (optional)',
    'lines' => 'Materials and quantities',
    'addLine' => 'Add a line',
    'issueRecorded' => 'Issue :code recorded.',
    'issueCancelled' => 'Issue :code cancelled — quantities returned to stock.',
    'issueClosed' => 'Issue :code closed — losses computed.',
    'closeIssue' => 'Close the issue (compute losses)',
    'cancelIssue' => 'Cancel this issue',
    'qtyIssued' => 'Handed out',
    'qtyConsumed' => 'Consumed',
    'qtyReturned' => 'Returned',
    'toReconcile' => 'Still to reconcile',
    'loss' => 'Computed loss',
    'lossValue' => 'Loss value',
    'justification' => 'Justification (optional)',

    // ---- Employee declaration ----------------------------------------------
    'declareTitle' => 'My declaration',
    'declareHelp' => 'Declare only what you used and what you return. The loss is computed automatically — never enter it.',
    'declareConsumed' => 'Quantity consumed',
    'declareReturned' => 'Quantity returned',
    'declarationSubmitted' => 'Declaration submitted for review.',
    'declarations' => 'Declarations',

    // ---- Validation ---------------------------------------------------------
    'validationTitle' => 'Declarations to validate',
    'validationSubtitle' => 'Compare declared quantities with what was handed out, then approve or reject',
    'approve' => 'Approve',
    'reject' => 'Reject',
    'approved' => 'Approved',
    'rejected' => 'Rejected',
    'reviewNote' => 'Review note (motif on rejection)',
    'reviewRecorded' => 'Review recorded.',

    // ---- Transfers ----------------------------------------------------------
    'transfersTitle' => 'Transfers between employees',
    'transferFrom' => 'Handed over by',
    'transferTo' => 'Received by',
    'transferQty' => 'Quantity',
    'transferRecorded' => 'Transfer recorded — chain of custody preserved.',

    // ---- Per-employee dashboard ----------------------------------------------
    'employeeDashboard' => 'Per-employee materials',
    'employee' => 'Employee',
    'received' => 'Received',
    'consumedValue' => 'Consumed value',
    'issuesCount' => 'Issues',
    'openIssues' => 'Open',
    'closedIssues' => 'Closed',

    // ---- My issues -------------------------------------------------------------
    'myIssues' => 'My material issues',
    'noIssues' => 'No material issue yet.',
    'orderColumn' => 'Order',
    'status' => 'Status',
    'actions' => 'Actions',
    'view' => 'View',

    // ---- Notifications ----------------------------------------------------------
    'notifIssueReceived' => 'Materials handed to you (:code)',
    'notifDeclarationReviewed' => 'Your material declaration was reviewed (:decision)',

    // ---- Category labels -----------------------------------------------------------
    'cat_FABRIC' => 'Fabric',
    'cat_LINING' => 'Lining',
    'cat_BUTTONS' => 'Buttons',
    'cat_ZIPPERS' => 'Zippers',
    'cat_THREAD' => 'Thread',
    'cat_INTERFACING' => 'Interfacing',
    'cat_ELASTIC' => 'Elastic',
    'cat_ACCESSORY' => 'Accessory',
    'cat_OTHER' => 'Other',

    // ---- Issue status labels ----------------------------------------------------------
    'st_OPEN' => 'Open',
    'st_CLOSED' => 'Closed',
    'st_CANCELLED' => 'Cancelled',

    // ---- Declaration status labels ------------------------------------------------------
    'ds_PENDING' => 'Pending',
    'ds_APPROVED' => 'Approved',
    'ds_REJECTED' => 'Rejected',

    // ---- Stock movement kinds --------------------------------------------------------------
    'mk_PURCHASE_IN' => 'Purchase in',
    'mk_ISSUE_OUT' => 'Issue to employee',
    'mk_RETURN_IN' => 'Return to stock',
    'mk_ADJUSTMENT' => 'Adjustment',
];
