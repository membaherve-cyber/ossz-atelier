// OSSZ ATELIER — bilingual entries for the incentives module.

import type { DictEntries } from "../core";

const entries: DictEntries = {
  // --- Page header & tabs ------------------------------------------------------
  "inc.title": { en: "Staff Incentives", fr: "Primes du personnel" },
  "inc.subtitle": {
    en: "Define bonus schemes, generate evaluation recommendations from performance data, approve payments and record payouts.",
    fr: "Définissez les régimes de primes, générez les recommandations d'évaluation à partir des données de performance, approuvez les paiements et enregistrez les versements.",
  },
  "inc.tab.schemes": { en: "Incentive Schemes", fr: "Régimes de primes" },
  "inc.tab.evaluations": { en: "Evaluations & Approvals", fr: "Évaluations & approbations" },

  // --- Schemes tab ---------------------------------------------------------------
  "inc.schemes.title": { en: "Incentive Schemes", fr: "Régimes de primes" },
  "inc.schemes.new": { en: "New Scheme", fr: "Nouveau régime" },
  "inc.schemes.emptyTitle": { en: "No incentive schemes yet", fr: "Aucun régime de primes pour le moment" },
  "inc.schemes.emptyDescManage": {
    en: "Create your first scheme to start rewarding staff performance.",
    fr: "Créez votre premier régime pour commencer à récompenser la performance du personnel.",
  },
  "inc.schemes.emptyDescView": {
    en: "Management has not defined any incentive scheme yet.",
    fr: "La direction n'a pas encore défini de régime de primes.",
  },
  "inc.schemes.col.name": { en: "Name", fr: "Nom" },
  "inc.schemes.col.periodType": { en: "Period Type", fr: "Type de période" },
  "inc.schemes.col.minScore": { en: "Min Score", fr: "Score min." },
  "inc.schemes.col.amount": { en: "Amount", fr: "Montant" },
  "inc.schemes.col.active": { en: "Active", fr: "Actif" },
  "inc.schemes.col.createdBy": { en: "Created By", fr: "Créé par" },
  "inc.schemes.anyScore": { en: "Any", fr: "Indifférent" },
  "inc.schemes.toggleAria": { en: "Toggle {name}", fr: "Activer/désactiver {name}" },
  "inc.schemes.active": { en: "Active", fr: "Actif" },
  "inc.schemes.inactive": { en: "Inactive", fr: "Inactif" },
  "inc.schemes.editAria": { en: "Edit {name}", fr: "Modifier {name}" },

  // --- Recommendation generation ----------------------------------------------------
  "inc.gen.title": { en: "Generate Recommendations", fr: "Générer les recommandations" },
  "inc.gen.schemeLabel": { en: "Scheme", fr: "Régime" },
  "inc.gen.selectScheme": { en: "Select a scheme", fr: "Sélectionner un régime" },
  "inc.gen.periodLabel": { en: "Period", fr: "Période" },
  "inc.gen.generating": { en: "Generating...", fr: "Génération…" },
  "inc.gen.hintQuarter": { en: "Enter a quarter (YYYY-QN)", fr: "Saisissez un trimestre (YYYY-QN)" },
  "inc.gen.hintYear": { en: "Enter a year (YYYY)", fr: "Saisissez une année (YYYY)" },
  "inc.gen.hintMonth": { en: "Enter a month (YYYY-MM)", fr: "Saisissez un mois (YYYY-MM)" },
  "inc.gen.hintScores": {
    en: "Scores are read from the performance engine; approved or rejected records are never overwritten by regeneration.",
    fr: "Les scores sont issus du moteur de performance ; les enregistrements approuvés ou refusés ne sont jamais écrasés par la régénération.",
  },

  // --- Evaluation records ---------------------------------------------------------------
  "inc.records.title": { en: "Evaluation Records", fr: "Évaluations" },
  "inc.records.emptyTitle": { en: "No evaluations yet", fr: "Aucune évaluation pour le moment" },
  "inc.records.emptyDesc": {
    en: "Generate recommendations for a scheme and period to create evaluation records.",
    fr: "Générez les recommandations pour un régime et une période afin de créer des évaluations.",
  },
  "inc.records.col.staff": { en: "Staff", fr: "Personnel" },
  "inc.records.col.scheme": { en: "Scheme", fr: "Régime" },
  "inc.records.col.period": { en: "Period", fr: "Période" },
  "inc.records.col.score": { en: "Score", fr: "Score" },
  "inc.records.col.eligible": { en: "Eligible", fr: "Admissible" },
  "inc.records.col.proposed": { en: "Proposed", fr: "Proposé" },
  "inc.records.col.status": { en: "Status", fr: "Statut" },
  "inc.records.col.approved": { en: "Approved", fr: "Approuvé" },
  "inc.records.col.approvedBy": { en: "Approved By", fr: "Approuvé par" },
  "inc.records.col.payment": { en: "Payment", fr: "Paiement" },
  "inc.records.notEligible": { en: "Not eligible", fr: "Non admissible" },
  "inc.records.review": { en: "Review", fr: "Examiner" },
  "inc.records.markPaid": { en: "Mark as Paid", fr: "Marquer comme payé" },

  // --- Scheme dialog -------------------------------------------------------------------------
  "inc.schemeDialog.editTitle": { en: "Edit Scheme", fr: "Modifier le régime" },
  "inc.schemeDialog.newTitle": { en: "New Incentive Scheme", fr: "Nouveau régime de primes" },
  "inc.schemeDialog.description": {
    en: "Define the evaluation rule. Amounts are in FCFA; minimum score is compared against the staff performance score for the period.",
    fr: "Définissez la règle d'évaluation. Les montants sont en FCFA ; le score minimum est comparé au score de performance du personnel pour la période.",
  },
  "inc.schemeDialog.name": { en: "Name", fr: "Nom" },
  "inc.schemeDialog.namePlaceholder": {
    en: "e.g. Monthly Excellence Bonus",
    fr: "ex. Prime d'excellence mensuelle",
  },
  "inc.schemeDialog.periodType": { en: "Period type", fr: "Type de période" },
  "inc.schemeDialog.minScore": { en: "Min score (0-100)", fr: "Score min. (0-100)" },
  "inc.schemeDialog.amount": { en: "Amount (FCFA)", fr: "Montant (FCFA)" },
  "inc.schemeDialog.descriptionLabel": { en: "Description", fr: "Description" },
  "inc.schemeDialog.descPlaceholder": {
    en: "What this scheme rewards.",
    fr: "Ce que ce régime récompense.",
  },
  "inc.schemeDialog.saving": { en: "Saving...", fr: "Enregistrement…" },
  "inc.schemeDialog.create": { en: "Create Scheme", fr: "Créer le régime" },

  // --- Review dialog ------------------------------------------------------------------------------
  "inc.review.title": { en: "Review Incentive", fr: "Examiner la prime" },
  "inc.review.description": {
    en: "Approve or reject this incentive recommendation. The decision is final — regeneration will not alter reviewed records.",
    fr: "Approuvez ou refusez cette recommandation de prime. La décision est définitive — la régénération ne modifiera pas les enregistrements examinés.",
  },
  "inc.review.periodScore": { en: "Period {period} · Score {score}", fr: "Période {period} · Score {score}" },
  "inc.review.eligible": { en: "eligible", fr: "admissible" },
  "inc.review.notEligible": { en: "not eligible", fr: "non admissible" },
  "inc.review.proposedAmount": { en: "Proposed amount:", fr: "Montant proposé :" },
  "inc.review.approvedAmount": { en: "Approved amount (FCFA)", fr: "Montant approuvé (FCFA)" },
  "inc.review.comments": { en: "Comments", fr: "Commentaires" },
  "inc.review.commentsPlaceholder": {
    en: "Optional note recorded with the decision.",
    fr: "Note facultative enregistrée avec la décision.",
  },
  "inc.review.reject": { en: "Reject", fr: "Refuser" },
  "inc.review.approve": { en: "Approve", fr: "Approuver" },

  // --- Toasts & validation ----------------------------------------------------------------------------
  "inc.toast.invalidNameTitle": { en: "Invalid name", fr: "Nom invalide" },
  "inc.toast.invalidNameDesc": {
    en: "Scheme name must be between 2 and 120 characters.",
    fr: "Le nom du régime doit contenir entre 2 et 120 caractères.",
  },
  "inc.toast.invalidScoreTitle": { en: "Invalid minimum score", fr: "Score minimum invalide" },
  "inc.toast.invalidScoreDesc": {
    en: "Minimum score must be between 0 and 100.",
    fr: "Le score minimum doit être compris entre 0 et 100.",
  },
  "inc.toast.invalidAmountTitle": { en: "Invalid amount", fr: "Montant invalide" },
  "inc.toast.invalidAmountDesc": {
    en: "Amount must be zero or more (FCFA).",
    fr: "Le montant doit être supérieur ou égal à zéro (FCFA).",
  },
  "inc.toast.invalidApprovedAmountDesc": {
    en: "Approved amount must be zero or more (FCFA).",
    fr: "Le montant approuvé doit être supérieur ou égal à zéro (FCFA).",
  },
  "inc.toast.schemeUpdated": { en: "Scheme updated", fr: "Régime mis à jour" },
  "inc.toast.schemeSavedDesc": { en: "{name} was saved.", fr: "{name} a été enregistré." },
  "inc.toast.schemeCreated": { en: "Scheme created", fr: "Régime créé" },
  "inc.toast.schemeCreatedDesc": { en: "{name} was added.", fr: "{name} a été ajouté." },
  "inc.toast.saveFailed": { en: "Could not save scheme", fr: "Impossible d'enregistrer le régime" },
  "inc.toast.schemeActivated": { en: "Scheme activated", fr: "Régime activé" },
  "inc.toast.schemeDeactivated": { en: "Scheme deactivated", fr: "Régime désactivé" },
  "inc.toast.updateFailed": {
    en: "Could not update scheme",
    fr: "Impossible de mettre à jour le régime",
  },
  "inc.toast.selectSchemeDesc": {
    en: "Choose the incentive scheme to evaluate.",
    fr: "Choisissez le régime de primes à évaluer.",
  },
  "inc.toast.enterPeriod": { en: "Enter a period", fr: "Saisir une période" },
  "inc.toast.formatQuarter": {
    en: "Expected format: YYYY-QN (e.g. 2026-Q4).",
    fr: "Format attendu : YYYY-QN (ex. 2026-Q4).",
  },
  "inc.toast.formatYear": {
    en: "Expected format: YYYY (e.g. 2026).",
    fr: "Format attendu : YYYY (ex. 2026).",
  },
  "inc.toast.formatMonth": {
    en: "Expected format: YYYY-MM (e.g. 2026-11).",
    fr: "Format attendu : YYYY-MM (ex. 2026-11).",
  },
  "inc.toast.generatedTitle": { en: "Recommendations generated", fr: "Recommandations générées" },
  "inc.toast.generatedDesc": {
    en: "{total} evaluation(s) prepared — {eligible} eligible.",
    fr: "{total} évaluation(s) préparée(s) — {eligible} admissible(s).",
  },
  "inc.toast.generationFailed": { en: "Generation failed", fr: "Échec de la génération" },
  "inc.toast.approvedTitle": { en: "Incentive approved", fr: "Prime approuvée" },
  "inc.toast.rejectedTitle": { en: "Incentive rejected", fr: "Prime refusée" },
  "inc.toast.reviewedDesc": { en: "{staff} — {scheme} ({period}).", fr: "{staff} — {scheme} ({period})." },
  "inc.toast.reviewFailed": { en: "Review failed", fr: "Échec de l'examen" },
  "inc.toast.paymentRecorded": { en: "Payment recorded", fr: "Paiement enregistré" },
  "inc.toast.paymentRecordedDesc": { en: "{staff} — {amount}.", fr: "{staff} — {amount}." },
  "inc.toast.paymentFailed": {
    en: "Could not record payment",
    fr: "Impossible d'enregistrer le paiement",
  },
  "inc.toast.unexpected": { en: "Unexpected error.", fr: "Erreur inattendue." },
};

export default entries;
