// OSSZ ATELIER — bilingual entries for the dashboard module.

import type { DictEntries } from "../core";

const entries: DictEntries = {
  // --- Shared ------------------------------------------------------------------
  "dash.loadError": {
    en: "Could not load dashboard",
    fr: "Impossible de charger le tableau de bord",
  },

  // --- Staff variant -------------------------------------------------------------
  "dash.welcome": { en: "Welcome back, {name}", fr: "Bon retour, {name}" },
  "dash.staffDesc": {
    en: "Here is your personal production overview. Only your assigned tasks are visible.",
    fr: "Voici votre aperçu personnel de la production. Seules les tâches qui vous sont affectées sont visibles.",
  },
  "dash.stat.myActive": { en: "My Active Tasks", fr: "Mes tâches actives" },
  "dash.stat.awaiting": { en: "Awaiting Inspection", fr: "En attente d'inspection" },
  "dash.stat.completedThisMonth": { en: "Completed This Month", fr: "Terminées ce mois-ci" },
  "dash.stat.overdue": { en: "Overdue", fr: "En retard" },
  "dash.myTasks": { en: "My Production Tasks", fr: "Mes tâches de production" },
  "dash.viewAll": { en: "View all", fr: "Tout afficher" },
  "dash.noTasksTitle": { en: "No tasks assigned yet", fr: "Aucune tâche affectée pour le moment" },
  "dash.noTasksDesc": {
    en: "When Management allocates garment pieces to your account, they will appear here.",
    fr: "Lorsque la direction alloue des pièces à votre compte, elles apparaîtront ici.",
  },

  // --- Management / Admin variant --------------------------------------------------
  "dash.managerTitle": { en: "{role} Dashboard", fr: "Tableau de bord — {role}" },
  "dash.managerDesc": {
    en: "Complete production overview across all registered orders and staff.",
    fr: "Vue d'ensemble complète de la production, pour toutes les commandes enregistrées et tout le personnel.",
  },
  "dash.createTasks": { en: "Create Tasks", fr: "Créer des tâches" },
  "dash.stat.totalOrders": { en: "Registered Orders", fr: "Commandes enregistrées" },
  "dash.stat.activeTasks": { en: "Active Tasks", fr: "Tâches actives" },
  "dash.stat.unassigned": { en: "Unassigned", fr: "Non affectées" },
  "dash.stat.ongoing": { en: "Ongoing", fr: "En cours" },
  "dash.stat.approved": { en: "Approved", fr: "Approuvées" },
  "dash.stat.readyForPickup": { en: "Ready for Pickup", fr: "Prêtes pour retrait" },
  "dash.stat.delivered": { en: "Delivered", fr: "Livrées" },
  "dash.stat.blocked": { en: "Blocked", fr: "Bloquées" },
  "dash.overdueTitle": { en: "Overdue Tasks", fr: "Tâches en retard" },
  "dash.productionBoard": { en: "Production board", fr: "Tableau de production" },
  "dash.overdueEmptyTitle": { en: "Nothing overdue", fr: "Aucun retard" },
  "dash.overdueEmptyDesc": {
    en: "All deadlines are under control.",
    fr: "Toutes les échéances sont maîtrisées.",
  },
  "dash.unassigned": { en: "Unassigned", fr: "Non affectée" },
  "dash.dueSoonTitle": { en: "Due in the Next 7 Days", fr: "Échéances dans les 7 prochains jours" },
  "dash.dueSoonEmptyTitle": { en: "No imminent deadlines", fr: "Aucune échéance imminente" },
  "dash.dueSoonEmptyDesc": {
    en: "No tasks are due within the next seven days.",
    fr: "Aucune tâche n'arrive à échéance dans les sept prochains jours.",
  },
  "dash.workloadTitle": {
    en: "Staff Workload (Active Tasks)",
    fr: "Charge de travail du personnel (tâches actives)",
  },
  "dash.workloadEmpty": { en: "No active staff accounts", fr: "Aucun compte personnel actif" },
  "dash.awaitingCount": { en: "{count} awaiting inspection", fr: "{count} en attente d'inspection" },
  "dash.overdueCount": { en: "{count} overdue", fr: "{count} en retard" },
  "dash.activityTitle": { en: "Recent Activity", fr: "Activité récente" },
  "dash.activityEmpty": { en: "No activity recorded yet", fr: "Aucune activité enregistrée pour le moment" },
  "dash.system": { en: "System", fr: "Système" },
};

export default entries;
