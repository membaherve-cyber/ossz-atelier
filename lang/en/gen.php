<?php

// OSSZ ATELIER — EN lines, general accounting (Finance & Comptabilité spec §6).
// Accessed via __('gen.key').

return [
    'indexTitle' => 'General accounting',
    'indexSubtitle' => 'SYSCOHADA chart, journal, general ledger and trial balance',

    // ---- Chart of accounts --------------------------------------------------
    'chart' => 'Chart of accounts',
    'code' => 'Account',
    'accountName' => 'Name',
    'type' => 'Type',
    'addAccount' => 'Add an account',
    'accountCreated' => 'Account added.',

    // ---- Journal ---------------------------------------------------------------
    'journal' => 'Journal',
    'entryDate' => 'Date',
    'description' => 'Description',
    'debit' => 'Debit',
    'credit' => 'Credit',
    'balanced' => 'Balanced',

    // ---- Manual entry (OD) --------------------------------------------------------
    'manualEntry' => 'Manual entry (OD)',
    'manualEntryHelp' => 'Reserved for the accountant — diverse operations and corrections.',
    'entryDescription' => 'Entry description',
    'addLine' => 'Add a line',
    'entryPosted' => 'Journal entry posted.',
    'unbalancedEntry' => 'Debits must equal credits.',
    'unknownAccount' => 'Unknown account :code.',

    // ---- Generate from history ------------------------------------------------------
    'generate' => 'Generate from history',
    'generateHelp' => 'Creates the journal entries for every recorded operation (idempotent — never duplicates).',
    'generated' => ':created entries created, :skipped already present.',

    // ---- General ledger & trial balance --------------------------------------------------
    'grandLivre' => 'General ledger',
    'ledgerOf' => 'General ledger — account :code',
    'balanceTitle' => 'Trial balance',
    'total' => 'Total',
    'result' => 'Result',
    'revenue' => 'Revenue',
    'expense' => 'Expenses',
    'netResult' => 'Net result',

    // ---- Entry badges --------------------------------------------------------------------
    'generatedBadge' => 'auto',
    'manualBadge' => 'manual',

    // ---- Generated entry descriptions ------------------------------------------------------
    'revenueEntry' => 'Order revenue',
    'supplier' => 'Supplier',
    'payrollEntry' => 'Salary payment',
    'advanceEntry' => 'Staff advance',
    'transferEntry' => 'Treasury transfer',
    'manualMovementEntry' => 'Treasury movement',

    // ---- Account types -----------------------------------------------------------------------
    'accountType_ASSET' => 'Asset',
    'accountType_LIABILITY' => 'Liability',
    'accountType_EQUITY' => 'Equity',
    'accountType_REVENUE' => 'Revenue',
    'accountType_EXPENSE' => 'Expense',

    // ---- Journal labels -------------------------------------------------------------------------
    'j_VT' => 'Sales',
    'j_AC' => 'Purchases',
    'j_BQ' => 'Bank',
    'j_CS' => 'Cash',
    'j_OD' => 'Misc',
];
