// OSSZ ATELIER — bilingual entries for the task-create module.

import type { DictEntries } from "../core";

const entries: DictEntries = {
  // --- Page header & guards -----------------------------------------------------
  "tc.title": { en: "Create Production Tasks", fr: "Créer des tâches de production" },
  "tc.description": {
    en: "Break a customer order into individual garment-piece tasks.",
    fr: "Décomposez une commande client en tâches individuelles par pièce de confection.",
  },
  "tc.descriptionFull": {
    en: "Break a customer order into individual garment-piece tasks. Each piece becomes a trackable task with its own checklist.",
    fr: "Décomposez une commande client en tâches individuelles par pièce de confection. Chaque pièce devient une tâche suivie avec sa propre liste de contrôle.",
  },
  "tc.backToTasks": { en: "Back to tasks", fr: "Retour aux tâches" },
  "tc.notAuthorized": { en: "Not authorized", fr: "Non autorisé" },
  "tc.notAuthorizedDesc": {
    en: "Only Management and Admin accounts can create production tasks.",
    fr: "Seuls les comptes Direction et Admin peuvent créer des tâches de production.",
  },

  // --- Order loading --------------------------------------------------------------
  "tc.loadOrdersFailed": {
    en: "Failed to load orders.",
    fr: "Échec du chargement des commandes.",
  },
  "tc.loadOrdersTitle": { en: "Could not load orders", fr: "Impossible de charger les commandes" },
  "tc.noOrders": { en: "No orders yet", fr: "Aucune commande pour le moment" },
  "tc.noOrdersDesc": {
    en: "Register a customer order first — tasks are always created from an order.",
    fr: "Enregistrez d'abord une commande client — les tâches sont toujours créées à partir d'une commande.",
  },
  "tc.goToOrders": { en: "Go to Orders", fr: "Aller aux commandes" },

  // --- Order selection ----------------------------------------------------------------
  "tc.customerOrder": { en: "Customer order", fr: "Commande client" },
  "tc.order": { en: "Order", fr: "Commande" },
  "tc.orderPlaceholder": {
    en: "Select the order to break into tasks",
    fr: "Sélectionnez la commande à décomposer en tâches",
  },
  "tc.customer": { en: "Customer", fr: "Client" },
  "tc.phone": { en: "Phone", fr: "Téléphone" },
  "tc.orderDate": { en: "Order date", fr: "Date de commande" },
  "tc.expectedDelivery": { en: "Expected delivery", fr: "Livraison prévue" },
  "tc.priority": { en: "Priority", fr: "Priorité" },
  "tc.pieces": { en: "Pieces", fr: "Pièces" },
  "tc.orderInstructions": { en: "Order instructions", fr: "Instructions de la commande" },
  "tc.existingTasks": {
    en: "Existing tasks on this order",
    fr: "Tâches existantes sur cette commande",
  },
  "tc.existingTasksSummary": {
    en: "{total} total ({completed} completed) — duplicate piece names are rejected.",
    fr: "{total} au total (terminées : {completed}) — les noms de pièces en double sont refusés.",
  },
  "tc.existingTasksSummaryProgress": {
    en: "{total} total ({completed} completed, {progress}% average progress) — duplicate piece names are rejected.",
    fr: "{total} au total (terminées : {completed}, progression moyenne : {progress}\u00A0%) — les noms de pièces en double sont refusés.",
  },

  // --- Piece rows -----------------------------------------------------------------------
  "tc.garmentPieces": { en: "Garment pieces", fr: "Pièces de confection" },
  "tc.addPiece": { en: "Add Piece", fr: "Ajouter une pièce" },
  "tc.pieceNumber": { en: "Piece {number}", fr: "Pièce {number}" },
  "tc.removePiece": { en: "Remove piece {number}", fr: "Supprimer la pièce {number}" },
  "tc.pieceName": { en: "Garment piece name *", fr: "Nom de la pièce de confection *" },
  "tc.pieceNamePlaceholder": {
    en: "e.g. Jacket, Matching Trousers",
    fr: "ex. Veste, Pantalon assorti",
  },
  "tc.category": { en: "Category", fr: "Catégorie" },
  "tc.categoryForPiece": {
    en: "Category for piece {number}",
    fr: "Catégorie de la pièce {number}",
  },
  "tc.assignedStaff": { en: "Assigned staff", fr: "Personnel affecté" },
  "tc.staffForPiece": { en: "Staff for piece {number}", fr: "Personnel pour la pièce {number}" },
  "tc.leaveUnassigned": { en: "Leave unassigned", fr: "Laisser non affectée" },
  "tc.activeCount": { en: "{count} active", fr: "{count} en cours" },
  "tc.priorityForPiece": {
    en: "Priority for piece {number}",
    fr: "Priorité de la pièce {number}",
  },
  "tc.deadline": { en: "Deadline", fr: "Échéance" },
  "tc.instructions": { en: "Instructions (optional)", fr: "Instructions (facultatif)" },
  "tc.instructionsPlaceholder": {
    en: "Piece-specific production instructions",
    fr: "Instructions de production propres à la pièce",
  },

  // --- Submit ------------------------------------------------------------------------------
  "tc.summaryOne": {
    en: "{count} piece on order {order}",
    fr: "{count} pièce sur la commande {order}",
  },
  "tc.summaryMany": {
    en: "{count} pieces on order {order}",
    fr: "{count} pièces sur la commande {order}",
  },
  "tc.createOne": { en: "Create {count} Task", fr: "Créer {count} tâche" },
  "tc.createMany": { en: "Create {count} Tasks", fr: "Créer {count} tâches" },

  // --- Validation & toasts --------------------------------------------------------------------
  "tc.selectOrderTitle": { en: "Select an order first", fr: "Sélectionnez d'abord une commande" },
  "tc.selectOrderDesc": {
    en: "Tasks are always created for a specific customer order.",
    fr: "Les tâches sont toujours créées pour une commande client précise.",
  },
  "tc.missingNameTitle": { en: "Missing garment piece name", fr: "Nom de pièce de confection manquant" },
  "tc.missingNameDesc": {
    en: "Every garment piece needs a name (e.g. Jacket, Trousers).",
    fr: "Chaque pièce de confection doit porter un nom (ex. Veste, Pantalon).",
  },
  "tc.duplicateTitle": { en: "Duplicate garment piece", fr: "Pièce de confection en double" },
  "tc.duplicateDesc": {
    en: "\"{name}\" appears more than once in this form.",
    fr: "« {name} » apparaît plusieurs fois dans ce formulaire.",
  },
  "tc.createdOne": { en: "{count} task created", fr: "{count} tâche créée" },
  "tc.createdMany": { en: "{count} tasks created", fr: "{count} tâches créées" },
  "tc.createdDesc": {
    en: "Production tasks were created for order {order}.",
    fr: "Les tâches de production ont été créées pour la commande {order}.",
  },
  "tc.createFailed": { en: "Could not create tasks", fr: "Impossible de créer les tâches" },
  "tc.unexpectedError": { en: "Unexpected error.", fr: "Erreur inattendue." },
};

export default entries;
