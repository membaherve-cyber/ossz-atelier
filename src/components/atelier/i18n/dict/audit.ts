// OSSZ ATELIER — bilingual entries for the audit module.

import type { DictEntries } from "../core";

const entries: DictEntries = {
  // --- Page header ------------------------------------------------------------
  "audit.title": { en: "Audit History", fr: "Historique d'audit" },
  "audit.description": {
    en: "Permanent record of all system activity. Entries cannot be edited or deleted.",
    fr: "Journal permanent de toute l'activité du système. Les entrées ne peuvent être ni modifiées ni supprimées.",
  },

  // --- Loading & error states ---------------------------------------------------
  "audit.errorTitle": {
    en: "Could not load the audit trail",
    fr: "Impossible de charger le journal d'audit",
  },
  "audit.unexpectedError": {
    en: "Unexpected error.",
    fr: "Une erreur inattendue s'est produite.",
  },

  // --- Filters ---------------------------------------------------------------------
  "audit.filters": { en: "Filters", fr: "Filtres" },
  "audit.actionFilter": { en: "Action", fr: "Action" },
  "audit.allActions": { en: "All actions", fr: "Toutes les actions" },
  "audit.entityTypeFilter": { en: "Entity type", fr: "Type d'entité" },
  "audit.allTypes": { en: "All types", fr: "Tous les types" },
  "audit.from": { en: "From", fr: "Du" },
  "audit.to": { en: "To", fr: "Au" },
  "audit.reset": { en: "Reset", fr: "Réinitialiser" },

  // --- Entries table -------------------------------------------------------------------
  "audit.entries": { en: "Entries", fr: "Entrées" },
  "audit.emptyTitle": {
    en: "No audit entries match these filters.",
    fr: "Aucune entrée d'audit ne correspond à ces filtres.",
  },
  "audit.emptyDescription": {
    en: "Try widening the date range or clearing the action and entity filters.",
    fr: "Essayez d'élargir la plage de dates ou de supprimer les filtres d'action et d'entité.",
  },
  "audit.col.date": { en: "Date & Time", fr: "Date" },
  "audit.col.user": { en: "User", fr: "Utilisateur" },
  "audit.col.action": { en: "Action", fr: "Action" },
  "audit.col.entity": { en: "Entity", fr: "Entité" },
  "audit.col.details": { en: "Details", fr: "Détails" },
  "audit.system": { en: "System", fr: "Système" },

  // --- Pagination -------------------------------------------------------------------------
  "audit.paginationSingular": {
    en: "{total} entry · page {page} of {totalPages}",
    fr: "{total} entrée · page {page} sur {totalPages}",
  },
  "audit.paginationPlural": {
    en: "{total} entries · page {page} of {totalPages}",
    fr: "{total} entrées · page {page} sur {totalPages}",
  },
  "audit.previous": { en: "Previous", fr: "Précédent" },
  "audit.next": { en: "Next", fr: "Suivant" },

  // --- Audit action labels (every action code written by logAudit()) ---------------------------
  "audit.action.LOGIN_SUCCESS": { en: "Login successful", fr: "Connexion réussie" },
  "audit.action.LOGIN_FAILED": { en: "Login failed", fr: "Échec de connexion" },
  "audit.action.LOGIN_BLOCKED_INACTIVE": {
    en: "Login blocked (inactive account)",
    fr: "Connexion bloquée (compte désactivé)",
  },
  "audit.action.LOGOUT": { en: "Logout", fr: "Déconnexion" },
  "audit.action.USER_CREATED": { en: "User created", fr: "Utilisateur créé" },
  "audit.action.USER_UPDATED": { en: "User updated", fr: "Utilisateur mis à jour" },
  "audit.action.USER_PASSWORD_RESET": {
    en: "User password reset",
    fr: "Mot de passe utilisateur réinitialisé",
  },
  "audit.action.STAFF_ASSESSED": { en: "Staff assessed", fr: "Personnel évalué" },
  "audit.action.CUSTOMER_CREATED": { en: "Customer created", fr: "Client créé" },
  "audit.action.CUSTOMER_UPDATED": { en: "Customer updated", fr: "Client mis à jour" },
  "audit.action.ORDER_CREATED": { en: "Order created", fr: "Commande créée" },
  "audit.action.ORDER_UPDATED": { en: "Order updated", fr: "Commande mise à jour" },
  "audit.action.TASKS_CREATED": { en: "Tasks created", fr: "Tâches créées" },
  "audit.action.TASK_ASSIGNED": { en: "Task assigned", fr: "Tâche affectée" },
  "audit.action.TASK_REASSIGNED": { en: "Task reassigned", fr: "Tâche réaffectée" },
  "audit.action.TASK_UPDATED": { en: "Task updated", fr: "Tâche mise à jour" },
  "audit.action.TASK_CANCELLED": { en: "Task cancelled", fr: "Tâche annulée" },
  "audit.action.TASK_BLOCKED": { en: "Task blocked", fr: "Tâche bloquée" },
  "audit.action.BLOCK_RESOLVED": { en: "Block resolved", fr: "Blocage résolu" },
  "audit.action.SUBMITTED_FOR_INSPECTION": {
    en: "Submitted for inspection",
    fr: "Soumis à l'inspection",
  },
  "audit.action.INSPECTION_COMPLETED": {
    en: "Inspection completed",
    fr: "Inspection terminée",
  },
  "audit.action.TASK_READY_FOR_PICKUP": {
    en: "Task ready for pickup",
    fr: "Tâche prête pour retrait",
  },
  "audit.action.TASK_DELIVERED": { en: "Task delivered", fr: "Tâche livrée" },
  "audit.action.TASK_STATUS_CHANGED": {
    en: "Task status changed",
    fr: "Statut de tâche modifié",
  },
  "audit.action.STAGES_UPDATED": { en: "Stages updated", fr: "Étapes mises à jour" },
  "audit.action.PROGRESS_UPDATED": { en: "Progress updated", fr: "Progression mise à jour" },
  "audit.action.COMMENT_ADDED": { en: "Comment added", fr: "Commentaire ajouté" },
  "audit.action.ISSUE_REPORTED": { en: "Issue reported", fr: "Problème signalé" },
  "audit.action.STAGE_CREATED": { en: "Stage created", fr: "Étape créée" },
  "audit.action.STAGE_UPDATED": { en: "Stage updated", fr: "Étape mise à jour" },
  "audit.action.PERFORMANCE_CONFIG_UPDATED": {
    en: "Performance config updated",
    fr: "Configuration de performance mise à jour",
  },
  "audit.action.INCENTIVE_SCHEME_CREATED": {
    en: "Incentive scheme created",
    fr: "Régime de primes créé",
  },
  "audit.action.INCENTIVE_SCHEME_UPDATED": {
    en: "Incentive scheme updated",
    fr: "Régime de primes mis à jour",
  },
  "audit.action.INCENTIVE_RECOMMENDATIONS_GENERATED": {
    en: "Incentive recommendations generated",
    fr: "Recommandations de primes générées",
  },
  "audit.action.INCENTIVE_APPROVED": { en: "Incentive approved", fr: "Prime approuvée" },
  "audit.action.INCENTIVE_REJECTED": { en: "Incentive rejected", fr: "Prime refusée" },
  "audit.action.INCENTIVE_PAYMENT_RECORDED": {
    en: "Incentive payment recorded",
    fr: "Paiement de prime enregistré",
  },
};

export default entries;
