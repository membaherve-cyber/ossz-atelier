<?php

// OSSZ ATELIER — EN lines, treasury (Finance & Comptabilité spec §4.4).
// Accessed via __('treas.key').

return [
    'indexTitle' => 'Treasury',
    'indexSubtitle' => 'Cash, bank and Mobile Money accounts — balances, transfers and journal',

    // ---- Accounts ----------------------------------------------------------
    'totalBalance' => 'Total treasury balance',
    'account' => 'Account',
    'kind' => 'Type',
    'openingBalance' => 'Opening balance',
    'balance' => 'Balance',
    'addAccount' => 'Add an account',
    'accountCreated' => 'Account created.',
    'accountUpdated' => 'Account updated.',

    // ---- Transfers -----------------------------------------------------------
    'transfer' => 'Transfer between accounts',
    'transferFrom' => 'From',
    'transferTo' => 'To',
    'transferNote' => 'Note (optional)',
    'transferHelp' => 'An internal transfer is neither a receipt nor an expense.',
    'transferRecorded' => 'Transfer recorded.',

    // ---- Manual movements -------------------------------------------------------
    'manualMovement' => 'Cash in / cash out',
    'movementIn' => 'Cash in (deposit)',
    'movementOut' => 'Cash out (withdrawal)',
    'movementRecorded' => 'Movement recorded.',

    // ---- Journal -----------------------------------------------------------------
    'journal' => 'Cash journal (by day)',
    'dayIn' => 'In',
    'dayOut' => 'Out',
    'movements' => 'Movements',
    'counterparty' => 'Counterparty',
    'reconciled' => 'Reconciled',
    'reconcile' => 'Mark reconciled',
    'unreconcile' => 'Unmark',

    // ---- Movement kinds -------------------------------------------------------------
    'k_RECEIPT' => 'Customer payment',
    'k_EXPENSE' => 'Expense paid',
    'k_PURCHASE' => 'Supplier payment',
    'k_PAYROLL' => 'Salary paid',
    'k_ADVANCE' => 'Staff advance',
    'k_TRANSFER_IN' => 'Transfer received',
    'k_TRANSFER_OUT' => 'Transfer sent',
    'k_DEPOSIT' => 'Deposit',
    'k_WITHDRAWAL' => 'Withdrawal',

    // ---- Account kinds ------------------------------------------------------------------
    'tk_CASH' => 'Cash box',
    'tk_BANK' => 'Bank',
    'tk_MOMO' => 'Mobile Money',
    'tk_OTHER' => 'Other',
    'unreconciled' => "Reconciliation mark removed.",
];
