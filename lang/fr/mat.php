<?php

// OSSZ ATELIER — FR lines, matières et stock (module Finance & Comptabilité §5).
// Accès via __('mat.key').

return [
    'indexTitle' => 'Matières et stock',
    'indexSubtitle' => 'Matières premières, niveaux de stock et traçabilité',

    // ---- Matières ---------------------------------------------------------
    'addMaterial' => 'Ajouter une matière',
    'editMaterial' => 'Modifier la matière',
    'materialName' => 'Matière',
    'reference' => 'Référence',
    'category' => 'Catégorie',
    'unit' => 'Unité',
    'openingQty' => 'Quantité initiale',
    'openingCost' => 'Coût unitaire (XAF)',
    'clientSupplied' => 'Fournie par le client (jamais valorisée)',
    'stockQty' => 'En stock',
    'avgCost' => 'Coût moyen',
    'stockValue' => 'Valeur du stock',
    'adjustQty' => 'Ajustement de stock (± qté)',
    'adjustNote' => 'Motif de l’ajustement',
    'ledger' => 'Mouvements de stock',
    'materialCreated' => 'Matière créée.',
    'materialUpdated' => 'Matière mise à jour.',

    // ---- Remises de matières ----------------------------------------------
    'issuesTitle' => 'Remises de matières',
    'issuesSubtitle' => 'Matières remises aux employés pour une commande',
    'newIssue' => 'Nouvelle remise à un employé',
    'issueCode' => 'Remise',
    'issueDate' => 'Date',
    'beneficiary' => 'Employé (bénéficiaire)',
    'garment' => 'Vêtement / pièce (optionnel)',
    'operation' => 'Opération / tâche (optionnel)',
    'lines' => 'Matières et quantités',
    'addLine' => 'Ajouter une ligne',
    'issueRecorded' => 'Remise :code enregistrée.',
    'issueCancelled' => 'Remise :code annulée — quantités retournées en stock.',
    'issueClosed' => 'Remise :code clôturée — pertes calculées.',
    'closeIssue' => 'Clôturer la remise (calcul des pertes)',
    'cancelIssue' => 'Annuler cette remise',
    'qtyIssued' => 'Remis',
    'qtyConsumed' => 'Consommé',
    'qtyReturned' => 'Restitué',
    'toReconcile' => 'Encore à rapprocher',
    'loss' => 'Perte calculée',
    'lossValue' => 'Valeur de la perte',
    'justification' => 'Justification (optionnelle)',

    // ---- Déclaration de l’employé -------------------------------------------
    'declareTitle' => 'Ma déclaration',
    'declareHelp' => 'Déclarez uniquement ce que vous avez consommé et ce que vous restituez. La perte est calculée automatiquement — ne la saisissez jamais.',
    'declareConsumed' => 'Quantité consommée',
    'declareReturned' => 'Quantité restituée',
    'declarationSubmitted' => 'Déclaration transmise pour validation.',
    'declarations' => 'Déclarations',

    // ---- Validation -----------------------------------------------------------
    'validationTitle' => 'Déclarations à valider',
    'validationSubtitle' => 'Comparez les quantités déclarées avec les quantités remises, puis validez ou rejetez',
    'approve' => 'Valider',
    'reject' => 'Rejeter',
    'approved' => 'Validée',
    'rejected' => 'Rejetée',
    'reviewNote' => 'Note de validation (motif en cas de rejet)',
    'reviewRecorded' => 'Validation enregistrée.',

    // ---- Transferts -------------------------------------------------------------
    'transfersTitle' => 'Transferts entre employés',
    'transferFrom' => 'Remis par',
    'transferTo' => 'Reçu par',
    'transferQty' => 'Quantité',
    'transferRecorded' => 'Transfert enregistré — chaîne de garde conservée.',

    // ---- Matières par employé ------------------------------------------------------
    'employeeDashboard' => 'Matières par employé',
    'employee' => 'Employé',
    'received' => 'Reçu',
    'consumedValue' => 'Valeur consommée',
    'issuesCount' => 'Remises',
    'openIssues' => 'Ouvertes',
    'closedIssues' => 'Clôturées',

    // ---- Mes remises -----------------------------------------------------------------
    'myIssues' => 'Mes remises de matières',
    'noIssues' => 'Aucune remise de matières pour le moment.',
    'orderColumn' => 'Commande',
    'status' => 'Statut',
    'actions' => 'Actions',
    'view' => 'Voir',

    // ---- Notifications ------------------------------------------------------------------
    'notifIssueReceived' => 'Matières qui vous ont été remises (:code)',
    'notifDeclarationReviewed' => 'Votre déclaration de matières a été traitée (:decision)',

    // ---- Catégories -----------------------------------------------------------------------
    'cat_FABRIC' => 'Tissu',
    'cat_LINING' => 'Doublure',
    'cat_BUTTONS' => 'Boutons',
    'cat_ZIPPERS' => 'Fermetures',
    'cat_THREAD' => 'Fil',
    'cat_INTERFACING' => 'Entoilage',
    'cat_ELASTIC' => 'Élastique',
    'cat_ACCESSORY' => 'Accessoire',
    'cat_OTHER' => 'Autre',

    // ---- Statuts des remises ----------------------------------------------------------------
    'st_OPEN' => 'Ouverte',
    'st_CLOSED' => 'Clôturée',
    'st_CANCELLED' => 'Annulée',

    // ---- Statuts des déclarations -------------------------------------------------------------
    'ds_PENDING' => 'En attente',
    'ds_APPROVED' => 'Validée',
    'ds_REJECTED' => 'Rejetée',

    // ---- Types de mouvements de stock ------------------------------------------------------------
    'mk_PURCHASE_IN' => 'Entrée achat',
    'mk_ISSUE_OUT' => 'Remise à un employé',
    'mk_RETURN_IN' => 'Retour en stock',
    'mk_ADJUSTMENT' => 'Ajustement',
];
