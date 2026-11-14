// OSSZ ATELIER — bilingual entries for the tasks module.

import type { DictEntries } from "../core";

const entries: DictEntries = {
  // --- Page header -------------------------------------------------------------
  "tasks.title": { en: "Production", fr: "Production" },
  "tasks.myTitle": { en: "My Tasks", fr: "Mes tâches" },
  "tasks.description": {
    en: "Monitor every garment production task across the atelier.",
    fr: "Suivez toutes les tâches de production de confection de l'atelier.",
  },
  "tasks.myDescription": {
    en: "Your assigned garment pieces and their production status.",
    fr: "Vos pièces de confection affectées et leur état de production.",
  },
  "tasks.createTasks": { en: "Create Tasks", fr: "Créer des tâches" },

  // --- Filter bar ----------------------------------------------------------------
  "tasks.searchPlaceholder": {
    en: "Order number, customer, piece or task code",
    fr: "Numéro de commande, client, pièce ou code de tâche",
  },
  "tasks.status": { en: "Status", fr: "Statut" },
  "tasks.filterByStatus": { en: "Filter by status", fr: "Filtrer par statut" },
  "tasks.allStatuses": { en: "All statuses", fr: "Tous les statuts" },
  "tasks.sortBy": { en: "Sort by", fr: "Trier par" },
  "tasks.sortAria": { en: "Sort tasks", fr: "Trier les tâches" },
  "tasks.sortUpdated": { en: "Recently updated", fr: "Mises à jour récemment" },
  "tasks.sortDeadline": { en: "Deadline", fr: "Échéance" },
  "tasks.sortProgress": { en: "Progress", fr: "Progression" },
  "tasks.sortCreated": { en: "Newest", fr: "Plus récentes" },
  "tasks.staff": { en: "Staff", fr: "Personnel" },
  "tasks.filterByStaff": { en: "Filter by staff", fr: "Filtrer par membre du personnel" },
  "tasks.allStaff": { en: "All staff", fr: "Tout le personnel" },
  "tasks.unassignedFilter": { en: "Unassigned", fr: "Non affectées" },
  "tasks.activeCount": { en: "{count} active", fr: "{count} en cours" },
  "tasks.priority": { en: "Priority", fr: "Priorité" },
  "tasks.filterByPriority": { en: "Filter by priority", fr: "Filtrer par priorité" },
  "tasks.allPriorities": { en: "All priorities", fr: "Toutes les priorités" },
  "tasks.deadlineFrom": { en: "Deadline from", fr: "Échéance du" },
  "tasks.deadlineTo": { en: "Deadline to", fr: "Échéance au" },
  "tasks.overdueOnly": { en: "Overdue only", fr: "En retard uniquement" },

  // --- Errors -----------------------------------------------------------------------
  "tasks.loadFailed": { en: "Failed to load tasks.", fr: "Échec du chargement des tâches." },
  "tasks.loadFailedTitle": { en: "Could not load tasks", fr: "Impossible de charger les tâches" },
  "tasks.refreshFailed": { en: "Could not refresh tasks", fr: "Impossible d'actualiser les tâches" },

  // --- Table ---------------------------------------------------------------------------
  "tasks.colTask": { en: "Task", fr: "Tâche" },
  "tasks.colOrder": { en: "Order", fr: "Commande" },
  "tasks.colAssignee": { en: "Assignee", fr: "Affectation" },
  "tasks.colStatus": { en: "Status", fr: "Statut" },
  "tasks.colProgress": { en: "Progress", fr: "Progression" },
  "tasks.colDeadline": { en: "Deadline", fr: "Échéance" },
  "tasks.colLastUpdate": { en: "Last Update", fr: "Dernière mise à jour" },
  "tasks.unassigned": { en: "Unassigned", fr: "Non affectée" },
  "tasks.openTaskAria": {
    en: "Open task {code} — {piece}",
    fr: "Ouvrir la tâche {code} — {piece}",
  },

  // --- Empty states -----------------------------------------------------------------------
  "tasks.emptyFilteredTitle": {
    en: "No tasks match your filters",
    fr: "Aucune tâche ne correspond à vos filtres",
  },
  "tasks.emptyTitle": { en: "No production tasks yet", fr: "Aucune tâche de production pour le moment" },
  "tasks.emptyStaffTitle": { en: "No tasks assigned to you", fr: "Aucune tâche ne vous est affectée" },
  "tasks.emptyFilteredDesc": {
    en: "Try adjusting the search or filters above.",
    fr: "Essayez de modifier la recherche ou les filtres ci-dessus.",
  },
  "tasks.emptyDesc": {
    en: "Create tasks from a customer order to start production.",
    fr: "Créez des tâches à partir d'une commande client pour démarrer la production.",
  },
  "tasks.emptyStaffDesc": {
    en: "Garment pieces allocated to your account will appear here.",
    fr: "Les pièces de confection affectées à votre compte apparaîtront ici.",
  },
  "tasks.clearFilters": { en: "Clear filters", fr: "Réinitialiser les filtres" },

  // --- Pagination ----------------------------------------------------------------------------
  "tasks.totalOne": { en: "{count} task total", fr: "{count} tâche au total" },
  "tasks.totalMany": { en: "{count} tasks total", fr: "{count} tâches au total" },
  "tasks.previous": { en: "Previous", fr: "Précédent" },
  "tasks.next": { en: "Next", fr: "Suivant" },
  "tasks.pageOf": { en: "Page {page} of {totalPages}", fr: "Page {page} sur {totalPages}" },
};

export default entries;
