<?php

// OSSZ ATELIER — EN lines, financial dashboard (accounting enhancement §5).
// Accessed via __('fin.key').

return [
    'title' => 'Financial Dashboard',
    'subtitle' => 'Workshop income, expenses and financial performance — computed from recorded data only.',
    'nav' => 'Financial Dashboard',

    // Period filters
    'period' => 'Period',
    'periodToday' => 'Today',
    'periodWeek' => 'This week',
    'periodMonth' => 'This month',
    'periodLastMonth' => 'Last month',
    'periodYear' => 'This year',
    'periodAll' => 'All time',
    'periodCustom' => 'Custom',
    'from' => 'From',
    'to' => 'To',
    'apply' => 'Apply',

    // Summary cards
    'cardRevenue' => 'Total Revenue',
    'cardRevenueHint' => 'Order value registered in the period',
    'cardPayments' => 'Payments Received',
    'cardPaymentsHint' => 'Money actually received in the period',
    'cardOutstanding' => 'Outstanding Balances',
    'cardOutstandingHint' => 'Customer balances owed — as of now',
    'cardExpenses' => 'Total Expenses',
    'cardExpensesHint' => 'Approved & paid expenses in the period',
    'cardSalaries' => 'Total Salaries',
    'cardSalariesHint' => 'Approved payroll in the period',
    'cardAdvances' => 'Advances Outstanding',
    'cardAdvancesHint' => 'Staff advances not yet recovered — as of now',
    'cardNetResult' => 'Net Operating Result',
    'cardNetResultHint' => 'Revenue − expenses − salaries',
    'cardMonthRevenue' => 'Current Month Revenue',
    'cardMonthExpenses' => 'Current Month Expenses',

    // Secondary figures
    'advancesDisbursed' => 'Advances disbursed in period',
    'cashMovement' => 'Net cash movement',
    'cashIn' => 'Cash in (payments)',
    'cashOut' => 'Cash out (expenses + salaries + advances)',

    // Charts
    'chartRevenue' => 'Revenue Over Time',
    'chartExpenses' => 'Expenses Over Time',
    'chartComparison' => 'Revenue vs Expenses',
    'chartCategories' => 'Expense Breakdown by Category',
    'chartMonthly' => 'Monthly Financial Performance',
    'legendRevenue' => 'Revenue',
    'legendPayments' => 'Payments',
    'legendExpenses' => 'Expenses',
    'legendSalaries' => 'Salaries',
    'legendNet' => 'Net result',
    'noChartData' => 'No recorded activity in this period yet.',
    'viewExpenses' => 'View expenses',
    'viewPayments' => 'View payments',

    // Monthly table
    'colMonth' => 'Month',
    'colRevenue' => 'Revenue',
    'colPayments' => 'Payments',
    'colExpenses' => 'Expenses',
    'colSalaries' => 'Salaries',
    'colNet' => 'Net',
    'totalRow' => 'Total',

    // Calculation rules note
    'rulesTitle' => 'How these figures are calculated',
    'rulesBody' => 'Revenue counts registered order values, not money received. Payments received and outstanding balances are tracked separately, so a deposit never appears as full payment. Salaries come from approved payroll periods, and recovered staff advances are never counted as new expenses.',
];
