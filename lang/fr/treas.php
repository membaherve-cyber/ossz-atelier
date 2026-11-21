<?php

// OSSZ ATELIER — FR lines, trésorerie (module Finance & Comptabilité §4.4).
// Accès via __('treas.key').

return [
    'indexTitle' => 'Trésorerie',
    'indexSubtitle' => 'Caisses, banques et Mobile Money — soldes, transferts et journal',

    // ---- Comptes -----------------------------------------------------------
    'totalBalance' => 'Solde total de trésorerie',
    'account' => 'Compte',
    'kind' => 'Type',
    'openingBalance' => 'Solde d’ouverture',
    'balance' => 'Solde',
    'addAccount' => 'Ajouter un compte',
    'accountCreated' => 'Compte créé.',
    'accountUpdated' => 'Compte mis à jour.',

    // ---- Transferts ----------------------------------------------------------
    'transfer' => 'Transfert entre comptes',
    'transferFrom' => 'Depuis',
    'transferTo' => 'Vers',
    'transferNote' => 'Note (optionnelle)',
    'transferHelp' => 'Un transfert interne n’est ni une recette ni une dépense.',
    'transferRecorded' => 'Transfert enregistré.',

    // ---- Mouvements manuels -----------------------------------------------------
    'manualMovement' => 'Entrée / sortie de fonds',
    'movementIn' => 'Entrée de fonds (dépôt)',
    'movementOut' => 'Sortie de fonds (retrait)',
    'movementRecorded' => 'Mouvement enregistré.',

    // ---- Journal ------------------------------------------------------------------
    'journal' => 'Journal de caisse (par jour)',
    'dayIn' => 'Entrées',
    'dayOut' => 'Sorties',
    'movements' => 'Mouvements',
    'counterparty' => 'Contrepartie',
    'reconciled' => 'Rapproché',
    'reconcile' => 'Marquer rapproché',
    'unreconcile' => 'Annuler le rapprochement',

    // ---- Types de mouvements ----------------------------------------------------------
    'k_RECEIPT' => 'Encaissement client',
    'k_EXPENSE' => 'Dépense payée',
    'k_PURCHASE' => 'Paiement fournisseur',
    'k_PAYROLL' => 'Salaire payé',
    'k_ADVANCE' => 'Avance au personnel',
    'k_TRANSFER_IN' => 'Transfert reçu',
    'k_TRANSFER_OUT' => 'Transfert émis',
    'k_DEPOSIT' => 'Dépôt',
    'k_WITHDRAWAL' => 'Retrait',

    // ---- Types de comptes -----------------------------------------------------------------
    'tk_CASH' => 'Caisse',
    'tk_BANK' => 'Banque',
    'tk_MOMO' => 'Mobile Money',
    'tk_OTHER' => 'Autre',
    'unreconciled' => "Rapprochement annulé.",
];
