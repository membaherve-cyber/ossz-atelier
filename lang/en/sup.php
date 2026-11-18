<?php

// OSSZ ATELIER — EN lines, suppliers and purchases (accounting enhancement §10).
// Accessed via __('sup.key').

return [
    'title' => 'Suppliers and Purchases',
    'subtitle' => 'Material suppliers and purchase records with automatic cost booking.',
    'nav' => 'Suppliers and Purchases',

    'tabSuppliers' => 'Suppliers',
    'tabPurchases' => 'Purchases',

    // ---- Suppliers --------------------------------------------------------
    'newSupplier' => 'New Supplier',
    'editSupplier' => 'Edit Supplier',
    'deleteSupplier' => 'Delete Supplier',
    'deleteConfirm' => 'Delete this supplier?',
    'deleteBlocked' => 'Suppliers with recorded purchases cannot be deleted.',

    'fieldName' => 'Supplier name',
    'fieldPhone' => 'Telephone',
    'fieldAddress' => 'Address',
    'fieldEmail' => 'Email (optional)',
    'fieldMaterials' => 'Materials supplied',
    'fieldNotes' => 'Additional notes',

    'colSupplier' => 'Supplier',
    'colPhone' => 'Telephone',
    'colMaterials' => 'Materials',
    'colPurchases' => 'Purchases',
    'colActions' => 'Actions',

    'supplierCreated' => 'Supplier created.',
    'supplierUpdated' => 'Supplier updated.',
    'supplierDeleted' => 'Supplier deleted.',
    'errName' => 'Enter the supplier name.',
    'emptySuppliers' => 'No suppliers registered yet.',
    'emptySuppliersDesc' => 'Register a material supplier to record purchases.',

    // ---- Purchases --------------------------------------------------------
    'newPurchase' => 'New Purchase',
    'editPurchase' => 'Edit Purchase',
    'deletePurchase' => 'Delete Purchase',
    'deleteConfirmPurchase' => 'Delete this purchase and its lines?',

    'fieldSupplier' => 'Supplier',
    'fieldPurchaseDate' => 'Purchase date',
    'fieldSummary' => 'Purchase summary',
    'fieldCategory' => 'Materials category',
    'fieldPaymentStatus' => 'Payment status',
    'fieldMethod' => 'Payment method',
    'fieldOrder' => 'Related order (optional)',
    'fieldReceipt' => 'Receipt attachment',
    'fieldReceiptCurrent' => 'Current receipt',
    'fieldNotes' => 'Notes',
    'selectSupplier' => 'Select a supplier…',
    'selectOrder' => 'No related order',
    'autoExpenseNote' => 'Marking a purchase as paid books its cost in the expense ledger automatically (once).',

    'itemsTitle' => 'Purchase lines',
    'addItem' => 'Add line',
    'itemDescription' => 'Material description',
    'itemQuantity' => 'Quantity',
    'itemUnitPrice' => 'Unit price',
    'itemTotal' => 'Line total',
    'purchaseTotal' => 'Purchase total',
    'removeItem' => 'Remove line',
    'atLeastOneItem' => 'Add at least one purchase line.',

    'colReference' => 'Reference',
    'colDate' => 'Date',
    'colDescription' => 'Description',
    'colTotal' => 'Total',
    'colPaymentStatus' => 'Payment',
    'colOrder' => 'Order',

    'receiptLink' => 'Receipt',
    'viewReceipt' => 'View receipt',
    'paidToast' => 'Purchase updated — expense booked in the ledger.',

    'emptyPurchases' => 'No purchases recorded yet.',
    'emptyPurchasesDesc' => 'Record a material purchase from a registered supplier.',
    'errSupplier' => 'Select a supplier.',
    'errPurchaseDate' => 'Enter a valid purchase date.',
    'errSummary' => 'Enter a purchase summary.',
    'errItems' => 'Each line needs a description and a quantity.',

    'purchaseCreated' => 'Purchase recorded.',
    'purchaseUpdated' => 'Purchase updated.',
    'purchaseDeleted' => 'Purchase deleted.',
];
