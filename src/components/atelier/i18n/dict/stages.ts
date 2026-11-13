// OSSZ ATELIER — bilingual entries for the stages module.

import type { DictEntries } from "../core";

const entries: DictEntries = {
  // --- Page header ------------------------------------------------------------
  "stages.title": { en: "Production Stages", fr: "Étapes de production" },
  "stages.description": {
    en: "Configure the checklist stages available to production tasks. Different task categories use different stage sets.",
    fr: "Configurez les étapes de la liste de contrôle disponibles pour les tâches de production. Chaque catégorie de tâches utilise son propre ensemble d'étapes.",
  },
  "stages.addStage": { en: "Add Stage", fr: "Ajouter une étape" },

  // --- History notice ------------------------------------------------------------
  "stages.historyTitle": {
    en: "Stage changes never rewrite history",
    fr: "Les modifications d'étapes ne réécrivent jamais l'historique",
  },
  "stages.historyDescription": {
    en: "Existing task histories keep the stage names at the time of recording. Deactivating a stage removes it from new tasks only.",
    fr: "L'historique des tâches existantes conserve les noms des étapes au moment de l'enregistrement. Désactiver une étape la retire uniquement des nouvelles tâches.",
  },

  // --- Loading, error & empty states ------------------------------------------------
  "stages.errorTitle": {
    en: "Could not load the stage configuration",
    fr: "Impossible de charger la configuration des étapes",
  },
  "stages.unexpectedError": {
    en: "Unexpected error.",
    fr: "Une erreur inattendue s'est produite.",
  },
  "stages.emptyTitle": { en: "No stages configured", fr: "Aucune étape configurée" },
  "stages.emptyDescription": {
    en: "Create the first checklist stage to start building the production workflow.",
    fr: "Créez la première étape de la liste de contrôle pour commencer à construire le flux de production.",
  },

  // --- Table headers --------------------------------------------------------------------
  "stages.col.sort": { en: "Sort", fr: "Ordre" },
  "stages.col.name": { en: "Name", fr: "Nom" },
  "stages.col.code": { en: "Code", fr: "Code" },
  "stages.col.appliesTo": { en: "Applies To", fr: "S'applique à" },
  "stages.col.weight": { en: "Weight", fr: "Poids" },
  "stages.col.active": { en: "Active", fr: "Actif" },
  "stages.allCategories": { en: "All categories", fr: "Toutes les catégories" },

  // --- Row actions -------------------------------------------------------------------------
  "stages.deactivateStageAria": {
    en: "Deactivate stage {name}",
    fr: "Désactiver l'étape {name}",
  },
  "stages.activateStageAria": {
    en: "Activate stage {name}",
    fr: "Activer l'étape {name}",
  },
  "stages.editStageAria": { en: "Edit stage {name}", fr: "Modifier l'étape {name}" },

  // --- Add / edit dialog ----------------------------------------------------------------------
  "stages.editTitle": { en: "Edit Stage", fr: "Modifier l'étape" },
  "stages.editDescription": {
    en: "Update the stage details. The stage code stays unchanged.",
    fr: "Mettez à jour les détails de l'étape. Le code de l'étape reste inchangé.",
  },
  "stages.addDescription": {
    en: "Define a new checklist stage and the task categories it applies to.",
    fr: "Définissez une nouvelle étape de la liste de contrôle ainsi que les catégories de tâches auxquelles elle s'applique.",
  },
  "stages.nameLabel": { en: "Name", fr: "Nom" },
  "stages.namePlaceholder": { en: "e.g. Fabric cutting", fr: "ex. Coupe du tissu" },
  "stages.appliesToLabel": { en: "Applies to", fr: "S'applique à" },
  "stages.appliesToHint": {
    en: "Leave all unchecked or check all to apply to every category.",
    fr: "Laissez tout décoché ou cochez tout pour appliquer à toutes les catégories.",
  },
  "stages.weightLabel": { en: "Weight", fr: "Poids" },
  "stages.sortOrderLabel": { en: "Sort order", fr: "Ordre de tri" },
  "stages.saving": { en: "Saving…", fr: "Enregistrement…" },
  "stages.createStage": { en: "Create stage", fr: "Créer l'étape" },

  // --- Validation toasts ------------------------------------------------------------------------
  "stages.invalidNameTitle": { en: "Invalid name", fr: "Nom invalide" },
  "stages.invalidNameDesc": {
    en: "The stage name must be between 2 and 80 characters.",
    fr: "Le nom de l'étape doit contenir entre 2 et 80 caractères.",
  },
  "stages.invalidWeightTitle": { en: "Invalid weight", fr: "Poids invalide" },
  "stages.invalidWeightDesc": {
    en: "Weight must be a number between 0.1 and 10.",
    fr: "Le poids doit être un nombre compris entre 0,1 et 10.",
  },
  "stages.invalidSortTitle": { en: "Invalid sort order", fr: "Ordre de tri invalide" },
  "stages.invalidSortDesc": {
    en: "Sort order must be a whole number between 0 and 999.",
    fr: "L'ordre de tri doit être un nombre entier compris entre 0 et 999.",
  },

  // --- Save toasts ---------------------------------------------------------------------------------
  "stages.updatedToastTitle": { en: "Stage updated", fr: "Étape mise à jour" },
  "stages.createdToastTitle": { en: "Stage created", fr: "Étape créée" },
  "stages.savedToastDesc": {
    en: "\"{name}\" was saved successfully.",
    fr: "« {name} » a été enregistrée avec succès.",
  },
  "stages.saveFailedToast": {
    en: "Could not save the stage",
    fr: "Impossible d'enregistrer l'étape",
  },

  // --- Activate / deactivate toasts -------------------------------------------------------------------
  "stages.activatedToastTitle": { en: "Stage activated", fr: "Étape activée" },
  "stages.deactivatedToastTitle": { en: "Stage deactivated", fr: "Étape désactivée" },
  "stages.activatedToastDesc": {
    en: "\"{name}\" is available to new tasks again.",
    fr: "« {name} » est de nouveau disponible pour les nouvelles tâches.",
  },
  "stages.deactivatedToastDesc": {
    en: "\"{name}\" will no longer be added to new tasks.",
    fr: "« {name} » ne sera plus ajoutée aux nouvelles tâches.",
  },
  "stages.updateFailedToast": {
    en: "Could not update the stage",
    fr: "Impossible de mettre à jour l'étape",
  },
};

export default entries;
