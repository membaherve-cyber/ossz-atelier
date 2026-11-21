<?php

// OSSZ ATELIER — FR lines, fournisseurs et achats (module comptabilité §10).
// Accès via __('sup.key').

return [
    'title' => 'Fournisseurs et Achats',
    'subtitle' => 'Fournisseurs de matières et registre des achats avec réservation automatique des coûts.',
    'nav' => 'Fournisseurs et Achats',

    'tabSuppliers' => 'Fournisseurs',
    'tabPurchases' => 'Achats',

    'newSupplier' => 'Nouveau Fournisseur',
    'editSupplier' => 'Modifier le Fournisseur',
    'deleteSupplier' => 'Supprimer le Fournisseur',
    'deleteConfirm' => 'Supprimer ce fournisseur ?',
    'deleteBlocked' => 'Les fournisseurs avec des achats enregistrés ne peuvent pas être supprimés.',

    'fieldName' => 'Nom du fournisseur',
    'fieldPhone' => 'Téléphone',
    'fieldAddress' => 'Adresse',
    'fieldEmail' => 'Email (facultatif)',
    'fieldMaterials' => 'Matières fournies',
    'fieldNotes' => 'Notes supplémentaires',

    'colSupplier' => 'Fournisseur',
    'colPhone' => 'Téléphone',
    'colMaterials' => 'Matières',
    'colPurchases' => 'Achats',
    'colActions' => 'Actions',

    'supplierCreated' => 'Fournisseur créé.',
    'supplierUpdated' => 'Fournisseur mis à jour.',
    'supplierDeleted' => 'Fournisseur supprimé.',
    'errName' => 'Saisissez le nom du fournisseur.',
    'emptySuppliers' => 'Aucun fournisseur enregistré.',
    'emptySuppliersDesc' => 'Enregistrez un fournisseur de matières pour saisir des achats.',

    'newPurchase' => 'Nouvel Achat',
    'editPurchase' => 'Modifier l’Achat',
    'deletePurchase' => 'Supprimer l’Achat',
    'deleteConfirmPurchase' => 'Supprimer cet achat et ses lignes ?',

    'fieldSupplier' => 'Fournisseur',
    'fieldPurchaseDate' => 'Date d’achat',
    'fieldSummary' => 'Résumé de l’achat',
    'fieldCategory' => 'Catégorie de matières',
    'fieldPaymentStatus' => 'Statut de paiement',
    'fieldMethod' => 'Moyen de paiement',
    'fieldOrder' => 'Commande liée (facultatif)',
    'fieldReceipt' => 'Reçu / justificatif',
    'fieldReceiptCurrent' => 'Reçu actuel',
    'fieldNotes' => 'Notes',
    'selectSupplier' => 'Sélectionner un fournisseur…',
    'selectOrder' => 'Aucune commande liée',
    'autoExpenseNote' => 'Marquer un achat comme payé réserve automatiquement son coût dans le registre des dépenses (une seule fois).',

    'itemsTitle' => 'Lignes d’achat',
    'addItem' => 'Ajouter une ligne',
    'itemDescription' => 'Description de la matière',
    'itemQuantity' => 'Quantité',
    'itemUnitPrice' => 'Prix unitaire',
    'itemTotal' => 'Total ligne',
    'purchaseTotal' => 'Total de l’achat',
    'removeItem' => 'Supprimer la ligne',
    'atLeastOneItem' => 'Ajoutez au moins une ligne d’achat.',

    'colReference' => 'Référence',
    'colDate' => 'Date',
    'colDescription' => 'Description',
    'colTotal' => 'Total',
    'colPaymentStatus' => 'Paiement',
    'colOrder' => 'Commande',

    'receiptLink' => 'Reçu',
    'viewReceipt' => 'Voir le reçu',
    'paidToast' => 'Achat mis à jour — dépense réservée dans le registre.',

    'emptyPurchases' => 'Aucun achat enregistré.',
    'emptyPurchasesDesc' => 'Enregistrez un achat de matières auprès d’un fournisseur enregistré.',
    'errSupplier' => 'Sélectionnez un fournisseur.',
    'errPurchaseDate' => 'Saisissez une date d’achat valide.',
    'errSummary' => 'Saisissez un résumé d’achat.',
    'errItems' => 'Chaque ligne doit avoir une description et une quantité.',

    'purchaseCreated' => 'Achat enregistré.',
    'purchaseUpdated' => 'Achat mis à jour.',
    'purchaseDeleted' => 'Achat supprimé.',

    // ---- Paiements des achats (Finance & Comptabilité v1.3) ------------------
    'supplierBalance' => 'Solde dû',
    'purchasePayments' => 'Paiements de l’achat',
    'addPayment' => 'Enregistrer un paiement',
    'paymentRecorded' => 'Paiement enregistré.',
    'paidSoFar' => 'Déjà payé',
    'remainingDue' => 'Reste à payer',
    'paymentDate' => 'Date de paiement',
    'amount' => 'Montant',
    'paymentMethod' => 'Moyen',
    'treasuryAccount' => 'Compte de trésorerie',
    'errPaymentExceedsBalance' => "Le paiement dépasse le solde restant dû.",
];
