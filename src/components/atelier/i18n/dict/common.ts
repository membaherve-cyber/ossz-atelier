// OSSZ ATELIER — shared bilingual entries: generic actions, workflow/domain
// labels and internal notification titles. Module-specific screens keep their
// strings in their own dictionary file.

import type { DictEntries } from "../core";

const entries: DictEntries = {
  // --- Generic actions & common UI ------------------------------------------
  "common.loading": { en: "Loading…", fr: "Chargement…" },
  "common.tryAgain": { en: "Try again", fr: "Réessayer" },
  "common.save": { en: "Save", fr: "Enregistrer" },
  "common.saveChanges": { en: "Save Changes", fr: "Enregistrer les modifications" },
  "common.cancel": { en: "Cancel", fr: "Annuler" },
  "common.close": { en: "Close", fr: "Fermer" },
  "common.create": { en: "Create", fr: "Créer" },
  "common.edit": { en: "Edit", fr: "Modifier" },
  "common.delete": { en: "Delete", fr: "Supprimer" },
  "common.actions": { en: "Actions", fr: "Actions" },
  "common.search": { en: "Search", fr: "Rechercher" },
  "common.filter": { en: "Filter", fr: "Filtrer" },
  "common.all": { en: "All", fr: "Tout" },
  "common.yes": { en: "Yes", fr: "Oui" },
  "common.no": { en: "No", fr: "Non" },
  "common.view": { en: "View", fr: "Consulter" },
  "common.back": { en: "Back", fr: "Retour" },
  "common.optional": { en: "optional", fr: "facultatif" },
  "common.required": { en: "required", fr: "obligatoire" },
  "common.somethingWrong": {
    en: "Something went wrong. Please try again.",
    fr: "Une erreur est survenue. Veuillez réessayer.",
  },
  "common.showPassword": { en: "Show password", fr: "Afficher le mot de passe" },
  "common.hidePassword": { en: "Hide password", fr: "Masquer le mot de passe" },

  // --- Task workflow statuses -------------------------------------------------
  "status.UNASSIGNED": { en: "Unassigned", fr: "Non affectée" },
  "status.ASSIGNED": { en: "Assigned", fr: "Affectée" },
  "status.ONGOING": { en: "Ongoing", fr: "En cours" },
  "status.AWAITING_INSPECTION": { en: "Awaiting Inspection", fr: "En attente d'inspection" },
  "status.CHANGES_REQUESTED": { en: "Changes Requested", fr: "Retouches demandées" },
  "status.APPROVED": { en: "Approved", fr: "Approuvée" },
  "status.READY_FOR_PICKUP": { en: "Ready for Pickup", fr: "Prêt pour retrait" },
  "status.DELIVERED": { en: "Delivered/Collected", fr: "Livrée/Récupérée" },
  "status.BLOCKED": { en: "Blocked", fr: "Bloquée" },
  "status.CANCELLED": { en: "Cancelled", fr: "Annulée" },

  // --- Priorities --------------------------------------------------------------
  "priority.LOW": { en: "Low", fr: "Basse" },
  "priority.NORMAL": { en: "Normal", fr: "Normale" },
  "priority.HIGH": { en: "High", fr: "Haute" },
  "priority.URGENT": { en: "Urgent", fr: "Urgente" },

  // --- Task categories -----------------------------------------------------------
  "category.FULL": { en: "Full Production", fr: "Production complète" },
  "category.CUTTING": { en: "Cutting Only", fr: "Coupe uniquement" },
  "category.SEWING": { en: "Sewing/Stitching Only", fr: "Couture uniquement" },
  "category.FINISHING": { en: "Finishing", fr: "Finition" },
  "category.IRONING": { en: "Ironing Only", fr: "Repassage uniquement" },
  "category.OTHER": { en: "Other Activity", fr: "Autre activité" },

  // --- Checklist stage states -------------------------------------------------------
  "stageState.NOT_STARTED": { en: "Not Started", fr: "Non commencé" },
  "stageState.IN_PROGRESS": { en: "In Progress", fr: "En cours" },
  "stageState.COMPLETED": { en: "Completed", fr: "Terminé" },
  "stageState.NOT_APPLICABLE": { en: "Not Applicable", fr: "Non applicable" },

  // --- Roles ---------------------------------------------------------------------------
  "role.MANAGEMENT": { en: "Management", fr: "Direction" },
  "role.ADMIN": { en: "Admin", fr: "Admin" },
  "role.STAFF": { en: "Staff", fr: "Personnel" },

  // --- Quality inspection decisions ------------------------------------------------------
  "inspection.APPROVED": { en: "Approved", fr: "Approuvé" },
  "inspection.CHANGES_REQUESTED": { en: "Changes Requested", fr: "Retouches demandées" },
  "inspection.REOPENED": { en: "Reopened", fr: "Rouvert" },

  // --- Incentive schemes --------------------------------------------------------------------
  "incentivePeriod.MONTHLY": { en: "Monthly productivity", fr: "Productivité mensuelle" },
  "incentivePeriod.QUARTERLY": { en: "Quarterly performance", fr: "Performance trimestrielle" },
  "incentivePeriod.ANNUAL": { en: "Annual performance", fr: "Performance annuelle" },
  "incentivePeriod.QUALITY": { en: "Quality achievements", fr: "Réalisations qualité" },
  "incentivePeriod.ON_TIME": { en: "On-time completion", fr: "Achèvement dans les délais" },
  "incentivePeriod.CUSTOM": { en: "Management-defined target", fr: "Objectif défini par la direction" },

  // --- Incentive & payment statuses ------------------------------------------------------------
  "incStatus.PENDING": { en: "Pending", fr: "En attente" },
  "incStatus.APPROVED": { en: "Approved", fr: "Approuvée" },
  "incStatus.REJECTED": { en: "Rejected", fr: "Refusée" },
  "paymentStatus.UNPAID": { en: "Unpaid", fr: "Impayé" },
  "paymentStatus.PAID": { en: "Paid", fr: "Payé" },

  // --- Notification titles (keyed by stored notification type) -----------------------------------
  "notifTitle.TASK_ASSIGNED": { en: "New task assigned", fr: "Nouvelle tâche affectée" },
  "notifTitle.TASK_REASSIGNED": { en: "Task reassigned to you", fr: "Tâche réaffectée" },
  "notifTitle.DEADLINE_CHANGED": { en: "Deadline changed", fr: "Échéance modifiée" },
  "notifTitle.NEW_INSTRUCTIONS": { en: "New instructions", fr: "Nouvelles instructions" },
  "notifTitle.CORRECTIONS_REQUESTED": { en: "Corrections requested", fr: "Retouches demandées" },
  "notifTitle.BLOCK_RESOLVED": { en: "Block resolved", fr: "Blocage résolu" },
  "notifTitle.TASK_APPROVED": { en: "Work approved", fr: "Travail approuvé" },
  "notifTitle.TASK_REOPENED": { en: "Task reopened", fr: "Tâche rouverte" },
  "notifTitle.SUBMITTED_FOR_INSPECTION": {
    en: "Work submitted for inspection",
    fr: "Travail soumis à l'inspection",
  },
  "notifTitle.TASK_OVERDUE": { en: "Task overdue", fr: "Tâche en retard" },
  "notifTitle.ISSUE_REPORTED": { en: "Production issue reported", fr: "Problème de production signalé" },
  "notifTitle.TASK_BLOCKED": { en: "Task blocked", fr: "Tâche bloquée" },
  "notifTitle.READY_FOR_PICKUP": { en: "Ready for pickup", fr: "Prêt pour retrait" },
  "notifTitle.TASK_DELIVERED": { en: "Garment delivered/collected", fr: "Vêtement livré/récupéré" },
  "notifTitle.TASK_CANCELLED": { en: "Task cancelled", fr: "Tâche annulée" },
};

export default entries;
