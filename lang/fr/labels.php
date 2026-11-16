<?php

// OSSZ ATELIER — FR lines, ported from domain-label keys of common.ts.
// Accessed via __('labels.key').

return [
    'status' => [
        'UNASSIGNED' => 'Non affectée',
        'ASSIGNED' => 'Affectée',
        'ONGOING' => 'En cours',
        'AWAITING_INSPECTION' => 'En attente d\'inspection',
        'CHANGES_REQUESTED' => 'Retouches demandées',
        'APPROVED' => 'Approuvée',
        'READY_FOR_PICKUP' => 'Prêt pour retrait',
        'DELIVERED' => 'Livrée/Récupérée',
        'BLOCKED' => 'Bloquée',
        'CANCELLED' => 'Annulée',
    ],
    'priority' => [
        'LOW' => 'Basse',
        'NORMAL' => 'Normale',
        'HIGH' => 'Haute',
        'URGENT' => 'Urgente',
    ],
    'category' => [
        'FULL' => 'Production complète',
        'CUTTING' => 'Coupe uniquement',
        'SEWING' => 'Couture uniquement',
        'FINISHING' => 'Finition',
        'IRONING' => 'Repassage uniquement',
        'OTHER' => 'Autre activité',
    ],
    'stageState' => [
        'NOT_STARTED' => 'Non commencé',
        'IN_PROGRESS' => 'En cours',
        'COMPLETED' => 'Terminé',
        'NOT_APPLICABLE' => 'Non applicable',
    ],
    'role' => [
        'MANAGEMENT' => 'Direction',
        'ADMIN' => 'Admin',
        'STAFF' => 'Personnel',
    ],
    'inspection' => [
        'APPROVED' => 'Approuvé',
        'CHANGES_REQUESTED' => 'Retouches demandées',
        'REOPENED' => 'Rouvert',
    ],
    'incentivePeriod' => [
        'MONTHLY' => 'Productivité mensuelle',
        'QUARTERLY' => 'Performance trimestrielle',
        'ANNUAL' => 'Performance annuelle',
        'QUALITY' => 'Réalisations qualité',
        'ON_TIME' => 'Achèvement dans les délais',
        'CUSTOM' => 'Objectif défini par la direction',
    ],
    'incStatus' => [
        'PENDING' => 'En attente',
        'APPROVED' => 'Approuvée',
        'REJECTED' => 'Refusée',
    ],
    'paymentStatus' => [
        'UNPAID' => 'Impayé',
        'PAID' => 'Payé',
    ],
    'notifTitle' => [
        'TASK_ASSIGNED' => 'Nouvelle tâche affectée',
        'TASK_REASSIGNED' => 'Tâche réaffectée',
        'DEADLINE_CHANGED' => 'Échéance modifiée',
        'NEW_INSTRUCTIONS' => 'Nouvelles instructions',
        'CORRECTIONS_REQUESTED' => 'Retouches demandées',
        'BLOCK_RESOLVED' => 'Blocage résolu',
        'TASK_APPROVED' => 'Travail approuvé',
        'TASK_REOPENED' => 'Tâche rouverte',
        'SUBMITTED_FOR_INSPECTION' => 'Travail soumis à l\'inspection',
        'TASK_OVERDUE' => 'Tâche en retard',
        'ISSUE_REPORTED' => 'Problème de production signalé',
        'TASK_BLOCKED' => 'Tâche bloquée',
        'READY_FOR_PICKUP' => 'Prêt pour retrait',
        'TASK_DELIVERED' => 'Vêtement livré/récupéré',
        'TASK_CANCELLED' => 'Tâche annulée',
    ],
];
