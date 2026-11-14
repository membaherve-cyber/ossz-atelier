// OSSZ ATELIER — bilingual entries for the reports module.

import type { DictEntries } from "../core";

const entries: DictEntries = {
  // --- Page header ------------------------------------------------------------
  "rep.title": { en: "Production Reports", fr: "Rapports de production" },
  "rep.subtitle": {
    en: "Period summary of registered orders, deliveries, completions and staff performance.",
    fr: "Synthèse par période des commandes enregistrées, des livraisons, des achèvements et de la performance du personnel.",
  },
  "rep.exportCsv": { en: "Export CSV", fr: "Exporter en CSV" },

  // --- Period selector -----------------------------------------------------------
  "rep.reportingPeriod": { en: "Reporting Period", fr: "Période de reporting" },
  "rep.reportType": { en: "Report type", fr: "Type de rapport" },
  "rep.monthlyReport": { en: "Monthly report", fr: "Rapport mensuel" },
  "rep.annualReport": { en: "Annual report", fr: "Rapport annuel" },
  "rep.month": { en: "Month", fr: "Mois" },
  "rep.year": { en: "Year", fr: "Année" },

  // --- Errors & empty states ---------------------------------------------------------
  "rep.loadError": { en: "Could not load the report", fr: "Impossible de charger le rapport" },
  "rep.loadErrorFallback": {
    en: "Failed to load the report.",
    fr: "Échec du chargement du rapport.",
  },
  "rep.noStaff": { en: "No staff accounts", fr: "Aucun compte personnel" },
  "rep.noStaffDesc": {
    en: "Create staff accounts to include them in production reports.",
    fr: "Créez des comptes de personnel pour les inclure dans les rapports de production.",
  },

  // --- Summary cards ---------------------------------------------------------------------
  "rep.ordersRegistered": { en: "Orders Registered", fr: "Commandes enregistrées" },
  "rep.ordersDelivered": { en: "Orders Delivered", fr: "Commandes livrées" },
  "rep.ordersDeliveredHint": {
    en: "orders with a delivery in period",
    fr: "commandes avec une livraison sur la période",
  },
  "rep.tasksCompleted": { en: "Tasks Completed", fr: "Tâches terminées" },
  "rep.tasksCompletedHint": { en: "approved in period", fr: "approuvées sur la période" },
  "rep.piecesCompleted": { en: "Pieces Completed", fr: "Pièces terminées" },
  "rep.piecesCompletedHint": {
    en: "one task = one garment piece",
    fr: "une tâche = une pièce de vêtement",
  },
  "rep.onTimeCompletion": { en: "On-Time Completion", fr: "Achèvement dans les délais" },
  "rep.onTimeCompletionHint": {
    en: "completions before deadline",
    fr: "achèvements avant l'échéance",
  },

  // --- Staff section -------------------------------------------------------------------------
  "rep.staffTableTitle": {
    en: "Staff Performance — {period}",
    fr: "Performance du personnel — {period}",
  },
  "rep.footnote": {
    en: "Reports are generated from live production records — assignments, completions and inspection results.",
    fr: "Les rapports sont générés à partir des registres de production en temps réel — affectations, achèvements et résultats d'inspection.",
  },

  // --- CSV export -------------------------------------------------------------------------------
  "rep.csvTitle": { en: "OSSZ ATELIER — Production Report", fr: "OSSZ ATELIER — Rapport de production" },
  "rep.csvPeriod": { en: "Period", fr: "Période" },
  "rep.csvOnTimeRate": {
    en: "On-Time Completion Rate (%)",
    fr: "Taux d'achèvement dans les délais (%)",
  },
  "rep.csvStaff": { en: "Staff", fr: "Personnel" },
  "rep.csvEmail": { en: "Email", fr: "E-mail" },
  "rep.csvAssigned": { en: "Assigned", fr: "Affectées" },
  "rep.csvCompleted": { en: "Completed", fr: "Terminées" },
  "rep.csvOnTime": { en: "On-time", fr: "À temps" },
  "rep.csvOverdue": { en: "Overdue", fr: "En retard" },
  "rep.csvAvgHours": { en: "Avg Hours", fr: "Durée moy. (h)" },
  "rep.csvQualityPct": { en: "Quality %", fr: "Qualité %" },
  "rep.csvCorrections": { en: "Corrections", fr: "Retouches" },
  "rep.csvBlocked": { en: "Blocked", fr: "Bloquées" },
  "rep.csvProductivity": { en: "Productivity", fr: "Productivité" },
  "rep.csvTimeliness": { en: "Timeliness", fr: "Ponctualité" },
  "rep.csvQuality": { en: "Quality", fr: "Qualité" },
  "rep.csvReliability": { en: "Reliability", fr: "Fiabilité" },
  "rep.csvManagement": { en: "Management", fr: "Direction" },
  "rep.csvOverall": { en: "Overall", fr: "Global" },

  // --- Toast --------------------------------------------------------------------------------------
  "rep.exported": { en: "Report exported", fr: "Rapport exporté" },
  "rep.exportedDesc": { en: "{file} downloaded.", fr: "{file} téléchargé." },
};

export default entries;
