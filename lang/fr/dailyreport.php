<?php

// OSSZ ATELIER — FR strings for the daily business-health e-mail
// (Administration → Rapport quotidien). Accessed via __('dailyreport.key').

return [
    'title' => 'Rapport quotidien par e-mail',
    'description' => 'Rapport quotidien automatique sur la santé de l’atelier, envoyé à la liste de diffusion de la direction.',
    'pageAria' => 'Paramètres du rapport quotidien',

    // Header actions
    'sendNow' => 'Envoyer le rapport maintenant',
    'sendTest' => 'Envoyer un test sur ma boîte',

    // Scheduling note
    'cronNoteTitle' => 'Prérequis de planification',
    'cronNote' => 'Le rapport part automatiquement une fois par jour à l’heure configurée (fuseau horaire de l’atelier). Cela nécessite une tâche cron sur le serveur exécutant «php artisan schedule:run» chaque minute — voir DEPLOYMENT.md section 7. Les boutons ci-dessus permettent toujours un envoi manuel, indépendamment de la planification.',

    // Settings card
    'settingsTitle' => 'Planification et contenu',
    'settingsDescription' => 'Quand le rapport est généré et ce qu’il contient.',
    'enabled' => 'Rapport quotidien activé',
    'enabledHint' => 'Désactivé, l’envoi planifié est ignoré — les envois manuels restent possibles.',
    'sendTime' => 'Heure d’envoi',
    'timezone' => 'Fuseau horaire de l’atelier',
    'replyTo' => 'Adresse de réponse',
    'sections' => 'Sections incluses',
    'sectionProduction' => 'Santé de la production',
    'sectionFinancial' => 'Santé financière (mois en cours)',
    'sectionStaff' => 'Charge de travail du personnel',
    'sectionAlerts' => 'Liste d’attention (retards, blocages, livraisons imminentes)',

    // Preview card
    'previewTitle' => 'Aperçu du rapport du jour',
    'previewDescription' => 'Chiffres en direct — exactement ce que contiendra le prochain e-mail.',
    'kpiNewOrders' => 'Nouvelles commandes',
    'kpiPiecesCompleted' => 'Pièces terminées',
    'kpiPaymentsIn' => 'Encaissements',
    'kpiExpensesOut' => 'Dépenses',
    'prodOrdersInProduction' => 'Commandes en production',
    'prodActiveTasks' => 'Tâches actives',
    'prodOverdue' => 'Pièces en retard',
    'prodBlocked' => 'Bloquées',
    'prodOnTime' => 'À l’heure (30 jours)',
    'finPaymentsMtd' => 'Paiements reçus (mois)',
    'finOutstanding' => 'Soldes clients dus',
    'finExpensesMtd' => 'Dépenses (mois)',
    'finNetCash' => 'Mouvement de trésorerie net (mois)',
    'previewUnavailable' => 'L’aperçu est temporairement indisponible.',

    // Recipients card
    'recipientsTitle' => 'Destinataires',
    'recipientsDescription' => 'Liste de diffusion du rapport quotidien. info@osszcollection.com est actif par défaut ; ajoutez ou retirez des adresses ci-dessous.',
    'addRecipient' => 'Ajouter un destinataire',
    'email' => 'Adresse e-mail',
    'nameLabel' => 'Nom',
    'colRecipient' => 'Destinataire',
    'colStatus' => 'Statut',
    'colAddedBy' => 'Ajouté par',
    'colActions' => 'Actions',
    'active' => 'Actif',
    'inactive' => 'Inactif',
    'remove' => 'Retirer',
    'removeAria' => 'Retirer le destinataire {email}',
    'toggleAria' => 'Activer ou désactiver {email}',
    'addDescription' => 'Cette adresse recevra le rapport quotidien dès le prochain envoi.',
    'emptyTitle' => 'Aucun destinataire',
    'emptyDescription' => 'Ajoutez au moins une adresse e-mail pour recevoir le rapport quotidien.',

    // History card
    'historyTitle' => 'Historique des envois',
    'historyDescription' => 'Les 15 derniers envois.',
    'colDate' => 'Envoyé',
    'colTrigger' => 'Déclencheur',
    'colStatus' => 'Statut',
    'colResult' => 'Résultat',
    'triggerScheduled' => 'Planifié',
    'triggerManual' => 'Manuel',
    'triggerTest' => 'Test',
    'statusSent' => 'Délivré',
    'statusPartial' => 'Partiel',
    'statusFailed' => 'Échec',
    'historyEmpty' => 'Aucun rapport envoyé pour le moment.',
    'lastSent' => 'Dernier envoi réussi',
    'never' => 'Jamais',
    'toLabel' => 'à',

    // Confirm dialog (Send Now)
    'sendConfirmTitle' => 'Envoyer le rapport maintenant',
    'sendConfirm' => 'Envoyer dès maintenant le rapport quotidien à tous les {count} destinataire(s) actif(s) ?',
    'confirmSend' => 'Envoyer',

    // Remove dialog
    'removeConfirmTitle' => 'Retirer le destinataire',
    'removeConfirm' => 'Retirer {email} de la liste de diffusion du rapport quotidien ?',
    'confirmRemove' => 'Retirer le destinataire',

    // Validation + flashes
    'errTimezone' => 'Veuillez choisir un fuseau horaire valide.',
    'settingsSaved' => 'Paramètres du rapport enregistrés.',
    'recipientAdded' => 'Destinataire ajouté à la liste du rapport quotidien.',
    'recipientRemoved' => 'Destinataire retiré de la liste du rapport quotidien.',
    'recipientActivated' => 'Destinataire activé — il recevra le rapport quotidien.',
    'recipientDeactivated' => 'Destinataire désactivé — il ne recevra plus le rapport quotidien.',
    'sentNow' => 'Rapport quotidien envoyé à tous les destinataires actifs.',
    'testSent' => 'Rapport de test envoyé sur votre boîte.',
    'partialSent' => 'Rapport envoyé à {success} destinataire(s) sur {total} — consultez l’historique pour l’adresse en échec.',
    'errNoRecipients' => 'Aucun destinataire actif — ajoutez-en un d’abord.',
    'errSendFailed' => 'Le rapport n’a pas pu être envoyé. Vérifiez les paramètres mail du fichier .env (voir DEPLOYMENT.md section 6).',
];
