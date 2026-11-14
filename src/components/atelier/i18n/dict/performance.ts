// OSSZ ATELIER — bilingual entries for the performance module.

import type { DictEntries } from "../core";

const entries: DictEntries = {
  // --- Page header -------------------------------------------------------------
  "perf.title": { en: "Staff Performance", fr: "Performance du personnel" },
  "perf.subtitle": {
    en: "Metrics and weighted scores per staff member, computed from real assignments, completions and inspections.",
    fr: "Indicateurs et notes pondérées par membre du personnel, calculés à partir des affectations, des achèvements et des inspections réels.",
  },
  "perf.exportCsv": { en: "Export CSV", fr: "Exporter en CSV" },

  // --- Period selector -----------------------------------------------------------
  "perf.reportingPeriod": { en: "Reporting Period", fr: "Période de reporting" },
  "perf.periodType": { en: "Period type", fr: "Type de période" },
  "perf.monthly": { en: "Monthly", fr: "Mensuel" },
  "perf.quarterly": { en: "Quarterly", fr: "Trimestriel" },
  "perf.annual": { en: "Annual", fr: "Annuel" },
  "perf.month": { en: "Month", fr: "Mois" },
  "perf.quarter": { en: "Quarter", fr: "Trimestre" },
  "perf.year": { en: "Year", fr: "Année" },
  "perf.quarter1": { en: "Q1", fr: "T1" },
  "perf.quarter2": { en: "Q2", fr: "T2" },
  "perf.quarter3": { en: "Q3", fr: "T3" },
  "perf.quarter4": { en: "Q4", fr: "T4" },
  "perf.prevPeriod": { en: "Previous period", fr: "Période précédente" },
  "perf.nextPeriod": { en: "Next period", fr: "Période suivante" },

  // --- Localized period labels (server-side labels are English-only) ----------------
  "perf.rangeMonth": { en: "{month} {year}", fr: "{month} {year}" },
  "perf.rangeQuarter": { en: "Q{quarter} {year}", fr: "T{quarter} {year}" },
  "perf.rangeYear": { en: "Year {year}", fr: "Année {year}" },

  // --- Staff performance table -------------------------------------------------------
  "perf.colStaff": { en: "Staff", fr: "Personnel" },
  "perf.colRecordedMetrics": { en: "Recorded metrics", fr: "Métriques enregistrées" },
  "perf.colScores": { en: "Scores", fr: "Notes" },
  "perf.colOverall": { en: "Overall", fr: "Global" },
  "perf.colMgmtAssessment": { en: "Mgmt Assessment", fr: "Évaluation direction" },
  "perf.colAssigned": { en: "Assigned", fr: "Affectées" },
  "perf.colCompleted": { en: "Completed", fr: "Terminées" },
  "perf.colOnTime": { en: "On-time", fr: "À temps" },
  "perf.colOverdue": { en: "Overdue", fr: "En retard" },
  "perf.colAvgHours": { en: "Avg Hours", fr: "Durée moy. (h)" },
  "perf.colQualityPct": { en: "Quality %", fr: "Qualité %" },
  "perf.colCorrections": { en: "Corrections", fr: "Retouches" },
  "perf.colBlocked": { en: "Blocked", fr: "Bloquées" },
  "perf.colProductivity": { en: "Productivity", fr: "Productivité" },
  "perf.colTimeliness": { en: "Timeliness", fr: "Ponctualité" },
  "perf.colQuality": { en: "Quality", fr: "Qualité" },
  "perf.colReliability": { en: "Reliability", fr: "Fiabilité" },
  "perf.colMgmt": { en: "Mgmt", fr: "Dir." },
  "perf.inactive": { en: "Inactive", fr: "Inactif" },

  // --- CSV export ----------------------------------------------------------------------
  "perf.csvEmail": { en: "Email", fr: "E-mail" },
  "perf.csvManagement": { en: "Management", fr: "Direction" },

  // --- Weights configuration (Management only) --------------------------------------------
  "perf.weightsTitle": { en: "Weights Configuration", fr: "Configuration des pondérations" },
  "perf.saveWeights": { en: "Save Weights", fr: "Enregistrer les pondérations" },
  "perf.saving": { en: "Saving…", fr: "Enregistrement…" },
  "perf.weightProductivity": { en: "Productivity %", fr: "Productivité %" },
  "perf.weightTimely": { en: "Timely Completion %", fr: "Ponctualité %" },
  "perf.weightQuality": { en: "Quality & Approval %", fr: "Qualité et approbation %" },
  "perf.weightReliability": { en: "Reliability %", fr: "Fiabilité %" },
  "perf.weightManagement": { en: "Management Assessment %", fr: "Évaluation direction %" },
  "perf.pieceTargetLabel": { en: "Piece Target / month", fr: "Objectif de pièces / mois" },
  "perf.totalWeight": { en: "Total weight: {total}%", fr: "Pondération totale : {total} %" },
  "perf.balanced": { en: "— balanced", fr: "— équilibrée" },
  "perf.mustEqual100": { en: "— must equal 100", fr: "— doit totaliser 100" },
  "perf.pieceTargetHint": {
    en: "Piece target: {target} pieces per month for 100% productivity.",
    fr: "Objectif de pièces : {target} pièces par mois pour 100 % de productivité.",
  },

  // --- Toasts & errors ----------------------------------------------------------------------
  "perf.loadError": {
    en: "Could not load performance data",
    fr: "Impossible de charger les données de performance",
  },
  "perf.loadErrorFallback": {
    en: "Failed to load performance data.",
    fr: "Échec du chargement des données de performance.",
  },
  "perf.unexpectedError": { en: "Unexpected error.", fr: "Erreur inattendue." },
  "perf.weightsSaved": { en: "Weights saved", fr: "Pondérations enregistrées" },
  "perf.weightsSavedDesc": {
    en: "Scoring weights updated — all scores were recomputed.",
    fr: "Pondérations de notation mises à jour — toutes les notes ont été recalculées.",
  },
  "perf.weightsSaveError": {
    en: "Could not save weights",
    fr: "Impossible d'enregistrer les pondérations",
  },
  "perf.invalidScore": { en: "Invalid score", fr: "Note invalide" },
  "perf.invalidScoreDesc": {
    en: "Enter a management assessment between 0 and 100.",
    fr: "Saisissez une évaluation de la direction entre 0 et 100.",
  },
  "perf.assessmentSaved": { en: "Assessment saved", fr: "Évaluation enregistrée" },
  "perf.assessmentSavedDesc": {
    en: "{name} — {score}/100 for {period}.",
    fr: "{name} — {score}/100 pour {period}.",
  },
  "perf.assessmentSaveError": {
    en: "Could not save assessment",
    fr: "Impossible d'enregistrer l'évaluation",
  },
  "perf.assessmentAria": {
    en: "Management assessment for {name}",
    fr: "Évaluation de la direction pour {name}",
  },
  "perf.saveAssessmentAria": {
    en: "Save assessment for {name}",
    fr: "Enregistrer l'évaluation pour {name}",
  },

  // --- Empty states & notes --------------------------------------------------------------------
  "perf.noStaff": { en: "No staff accounts", fr: "Aucun compte personnel" },
  "perf.noStaffDesc": {
    en: "Create staff accounts to start tracking production performance.",
    fr: "Créez des comptes de personnel pour commencer à suivre la performance de production.",
  },
  "perf.staffTableTitle": {
    en: "Staff Performance — {period}",
    fr: "Performance du personnel — {period}",
  },
  "perf.formulaNote": {
    en: "Productivity = pieces completed vs monthly target · Timeliness = on-time share of completions · Quality = share of approvals without corrections · Reliability = 100 − (blocks + corrections)/assigned · Overall = weighted sum (weights total 100)",
    fr: "Productivité = pièces terminées par rapport à l'objectif mensuel · Ponctualité = part des achèvements dans les délais · Qualité = part des approbations sans retouches · Fiabilité = 100 − (blocages + retouches)/affectées · Global = somme pondérée (total des pondérations : 100)",
  },
  "perf.colorNote": {
    en: "Scores are color coded: emerald 75+, amber 50+, red below 50.",
    fr: "Les notes sont codées par couleur : vert 75 et plus, ambre 50 et plus, rouge en dessous de 50.",
  },
};

export default entries;
