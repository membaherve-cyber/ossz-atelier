<?php

// OSSZ ATELIER — FR lines, revenus et paiements clients (module comptabilité §6).
// Accès via __('pay.key').

return [
    'title' => 'Revenus et Paiements',
    'subtitle' => 'Paiements clients encaissés sur les commandes enregistrées.',
    'nav' => 'Revenus et Paiements',

    'record' => 'Enregistrer un Paiement',
    'searchPlaceholder' => 'Rechercher référence, commande, client…',
    'filterMethod' => 'Moyen de paiement',
    'allMethods' => 'Tous les moyens',
    'fromDate' => 'Date de début',
    'toDate' => 'Date de fin',
    'clear' => 'Effacer',

    'colReference' => 'Référence',
    'colOrder' => 'Commande',
    'colCustomer' => 'Client',
    'colAmount' => 'Montant',
    'colMethod' => 'Moyen',
    'colDate' => 'Date de Paiement',
    'colTxn' => 'Réf. Transaction',
    'colRecordedBy' => 'Enregistré par',
    'colActions' => 'Actions',

    'fieldOrder' => 'Commande',
    'fieldAmount' => 'Montant reçu',
    'fieldDate' => 'Date de paiement',
    'fieldMethod' => 'Moyen de paiement',
    'fieldTxn' => 'Référence de transaction (facultatif)',
    'fieldNotes' => 'Notes',
    'orderValue' => 'Valeur totale de la commande',
    'alreadyPaid' => 'Déjà payé',
    'outstanding' => 'Solde restant après ce paiement',
    'noValueRecorded' => 'Cette commande n’a pas encore de valeur enregistrée — définissez d’abord la valeur de la commande.',
    'selectOrder' => 'Sélectionner une commande…',

    'editPayment' => 'Modifier le Paiement',
    'deletePayment' => 'Supprimer le Paiement',
    'deleteConfirm' => 'Supprimer ce paiement ? Le solde restant sera recalculé.',
    'deleteHasAuditNote' => 'La suppression est consignée dans l’historique d’audit.',

    'emptyTitle' => 'Aucun paiement enregistré',
    'emptyFiltersTitle' => 'Aucun paiement ne correspond aux filtres',
    'emptyDesc' => 'Enregistrez un paiement client pour commencer à suivre les revenus.',
    'emptyFiltersDesc' => 'Ajustez la recherche ou les dates pour voir plus de résultats.',

    'createdToast' => 'Paiement enregistré.',
    'updatedToast' => 'Paiement mis à jour.',
    'deletedToast' => 'Paiement supprimé.',
    'errAmount' => 'Saisissez le montant reçu.',
    'errOrder' => 'Sélectionnez la commande liée à ce paiement.',
    'errDate' => 'Saisissez une date de paiement valide.',
    'errExceedsBalance' => 'Ce montant dépasse le solde restant de la commande.',
    'errNoValue' => 'La commande sélectionnée n’a pas de valeur totale. Modifiez d’abord la commande.',
];
