<?php

// OSSZ ATELIER — FR lines, tableau de bord financier (module comptabilité §5).
// Accès via __('fin.key').

return [
    'title' => 'Tableau de Bord Financier',
    'subtitle' => 'Revenus, dépenses et performance financière de l’atelier — calculés uniquement à partir des données enregistrées.',
    'nav' => 'Tableau de Bord Financier',

    'period' => 'Période',
    'periodToday' => 'Aujourd’hui',
    'periodWeek' => 'Cette semaine',
    'periodMonth' => 'Ce mois',
    'periodLastMonth' => 'Mois dernier',
    'periodYear' => 'Cette année',
    'periodAll' => 'Tout l’historique',
    'periodCustom' => 'Personnalisée',
    'from' => 'Du',
    'to' => 'Au',
    'apply' => 'Appliquer',

    'cardRevenue' => 'Revenu Total',
    'cardRevenueHint' => 'Valeur des commandes enregistrées sur la période',
    'cardPayments' => 'Paiements Reçus',
    'cardPaymentsHint' => 'Argent réellement encaissé sur la période',
    'cardOutstanding' => 'Soldes Clients Dus',
    'cardOutstandingHint' => 'Soldes restant à payer — à ce jour',
    'cardExpenses' => 'Dépenses Totales',
    'cardExpensesHint' => 'Dépenses approuvées et payées sur la période',
    'cardSalaries' => 'Salaires Totaux',
    'cardSalariesHint' => 'Paie approuvée sur la période',
    'cardAdvances' => 'Avances en Cours',
    'cardAdvancesHint' => 'Avances staff non encore récupérées — à ce jour',
    'cardNetResult' => 'Résultat Net d’Exploitation',
    'cardNetResultHint' => 'Revenu − dépenses − salaires',
    'cardMonthRevenue' => 'Revenu du Mois en Cours',
    'cardMonthExpenses' => 'Dépenses du Mois en Cours',

    'advancesDisbursed' => 'Avances versées sur la période',
    'cashMovement' => 'Mouvement de trésorerie net',
    'cashIn' => 'Entrées (paiements)',
    'cashOut' => 'Sorties (dépenses + salaires + avances)',

    'chartRevenue' => 'Revenus dans le Temps',
    'chartExpenses' => 'Dépenses dans le Temps',
    'chartComparison' => 'Revenus vs Dépenses',
    'chartCategories' => 'Répartition des Dépenses par Catégorie',
    'chartMonthly' => 'Performance Financière Mensuelle',
    'legendRevenue' => 'Revenus',
    'legendPayments' => 'Paiements',
    'legendExpenses' => 'Dépenses',
    'legendSalaries' => 'Salaires',
    'legendNet' => 'Résultat net',
    'noChartData' => 'Aucune activité enregistrée sur cette période.',
    'viewExpenses' => 'Voir les dépenses',
    'viewPayments' => 'Voir les paiements',

    'colMonth' => 'Mois',
    'colRevenue' => 'Revenus',
    'colPayments' => 'Paiements',
    'colExpenses' => 'Dépenses',
    'colSalaries' => 'Salaires',
    'colNet' => 'Net',
    'totalRow' => 'Total',

    'rulesTitle' => 'Comment ces chiffres sont calculés',
    'rulesBody' => 'Le revenu compte la valeur des commandes enregistrées, et non l’argent encaissé. Les paiements reçus et les soldes dus sont suivis séparément : un acompte n’apparaît jamais comme un paiement complet. Les salaires proviennent des paies approuvées, et les avances staff récupérées ne sont jamais comptées comme de nouvelles dépenses.',
];
