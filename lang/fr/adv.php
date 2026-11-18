<?php

// OSSZ ATELIER — FR lines, avances au staff (module comptabilité §9).
// Accès via __('adv.key').

return [
    'title' => 'Avances au Staff',
    'subtitle' => 'Argent avancé au staff avant le paiement du salaire, et sa récupération via la paie.',
    'nav' => 'Avances au Staff',

    'record' => 'Enregistrer une Avance',
    'filterStaff' => 'Membre du staff',
    'allStaff' => 'Tout le staff',
    'filterStatus' => 'Statut',
    'allStatuses' => 'Tous les statuts',
    'clear' => 'Effacer',

    'colReference' => 'Référence',
    'colStaff' => 'Membre du Staff',
    'colAmount' => 'Montant',
    'colRecovered' => 'Récupéré',
    'colOutstanding' => 'Restant',
    'colIssued' => 'Date Octroyée',
    'colStatus' => 'Statut',
    'colActions' => 'Actions',

    'fieldStaff' => 'Membre du staff',
    'fieldAmount' => 'Montant',
    'fieldDateIssued' => 'Date d’octroi',
    'fieldReason' => 'Motif',
    'fieldMethod' => 'Moyen de paiement',
    'fieldRepaymentPlan' => 'Modalités de remboursement',
    'selectStaff' => 'Sélectionner un membre du staff…',
    'repaymentPlaceholder' => 'ex. récupéré sur les deux prochaines paies',

    'reviewApprove' => 'Approuver',
    'reviewReject' => 'Rejeter',
    'disburse' => 'Marquer Versée',
    'disburseConfirm' => 'Confirmer que l’argent a été remis au membre du staff ?',
    'cancelAdvance' => 'Annuler',
    'cancelConfirm' => 'Annuler cette avance ?',
    'approveConfirm' => 'Approuver cette avance ?',
    'rejectReason' => 'Motif du rejet',

    'deleteAdvance' => 'Supprimer l’Avance',
    'deleteConfirm' => 'Supprimer cette avance ?',
    'deleteBlocked' => 'Les avances versées ne peuvent pas être supprimées — elles doivent être récupérées.',

    'recoveryHistory' => 'Historique des récupérations',
    'recoveredThrough' => 'Récupéré via {payroll} le {date}',
    'noRecoveries' => 'Aucune récupération enregistrée pour l’instant.',

    'emptyTitle' => 'Aucune avance enregistrée',
    'emptyFiltersTitle' => 'Aucune avance ne correspond aux filtres',
    'emptyDesc' => 'Enregistrez une avance quand un membre du staff reçoit de l’argent avant la paie.',

    'createdToast' => 'Avance enregistrée.',
    'approvedToast' => 'Avance approuvée.',
    'rejectedToast' => 'Avance rejetée.',
    'disbursedToast' => 'Avance marquée comme versée.',
    'cancelledToast' => 'Avance annulée.',
    'deletedToast' => 'Avance supprimée.',
    'errStaff' => 'Sélectionnez un membre du staff.',
    'errAmount' => 'Saisissez le montant.',
    'errDate' => 'Saisissez une date d’octroi valide.',
    'errReason' => 'Saisissez le motif de l’avance.',

    'note' => 'Les avances récupérées sont déduites du net à payer et ne sont jamais comptées comme de nouvelles dépenses.',
];
