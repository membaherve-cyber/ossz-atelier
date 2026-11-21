<?php

// OSSZ ATELIER — FR lines, comptabilité générale (module Finance & Comptabilité §6).
// Accès via __('gen.key').

return [
    'indexTitle' => 'Comptabilité générale',
    'indexSubtitle' => 'Plan comptable SYSCOHADA, journal, grand livre et balance',

    // ---- Plan comptable ------------------------------------------------------
    'chart' => 'Plan comptable',
    'code' => 'Compte',
    'accountName' => 'Libellé',
    'type' => 'Type',
    'addAccount' => 'Ajouter un compte',
    'accountCreated' => 'Compte ajouté.',

    // ---- Journal ----------------------------------------------------------------
    'journal' => 'Journal',
    'entryDate' => 'Date',
    'description' => 'Libellé',
    'debit' => 'Débit',
    'credit' => 'Crédit',
    'balanced' => 'Équilibrée',

    // ---- Écriture manuelle (OD) ------------------------------------------------------
    'manualEntry' => 'Écriture manuelle (OD)',
    'manualEntryHelp' => 'Réservée au comptable — opérations diverses et corrections.',
    'entryDescription' => 'Libellé de l’écriture',
    'addLine' => 'Ajouter une ligne',
    'entryPosted' => 'Écriture enregistrée.',
    'unbalancedEntry' => 'Les débits doivent être égaux aux crédits.',
    'unknownAccount' => 'Compte inconnu :code.',

    // ---- Génération depuis l’historique ---------------------------------------------------
    'generate' => 'Générer depuis l’historique',
    'generateHelp' => 'Crée les écritures de toutes les opérations enregistrées (idempotent — jamais de doublon).',
    'generated' => ':created écritures créées, :skipped déjà présentes.',

    // ---- Grand livre & balance ----------------------------------------------------------------
    'grandLivre' => 'Grand livre',
    'ledgerOf' => 'Grand livre — compte :code',
    'balanceTitle' => 'Balance des comptes',
    'total' => 'Total',
    'result' => 'Résultat',
    'revenue' => 'Produits',
    'expense' => 'Charges',
    'netResult' => 'Résultat net',

    // ---- Badges d’écriture -----------------------------------------------------------------------
    'generatedBadge' => 'auto',
    'manualBadge' => 'manuel',

    // ---- Libellés des écritures générées ------------------------------------------------------------
    'revenueEntry' => 'Vente commande',
    'supplier' => 'Fournisseur',
    'payrollEntry' => 'Paiement de salaire',
    'advanceEntry' => 'Avance au personnel',
    'transferEntry' => 'Transfert de trésorerie',
    'manualMovementEntry' => 'Mouvement de trésorerie',

    // ---- Types de comptes -----------------------------------------------------------------------------
    'accountType_ASSET' => 'Actif',
    'accountType_LIABILITY' => 'Passif',
    'accountType_EQUITY' => 'Capitaux propres',
    'accountType_REVENUE' => 'Produits',
    'accountType_EXPENSE' => 'Charges',

    // ---- Journaux ----------------------------------------------------------------------------------------
    'j_VT' => 'Ventes',
    'j_AC' => 'Achats',
    'j_BQ' => 'Banque',
    'j_CS' => 'Caisse',
    'j_OD' => 'OD',
];
