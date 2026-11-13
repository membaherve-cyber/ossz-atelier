// OSSZ ATELIER — bilingual entries for the orders module.

import type { DictEntries } from "../core";

const entries: DictEntries = {
  // --- Page header & actions ----------------------------------------------
  "orders.title": { en: "Orders", fr: "Commandes" },
  "orders.subtitle": {
    en: "Manually registered production orders and their progress.",
    fr: "Commandes de production enregistrées manuellement et leur avancement.",
  },
  "orders.register": { en: "Register Order", fr: "Enregistrer une commande" },

  // --- Search & filters -------------------------------------------------------
  "orders.searchPlaceholder": {
    en: "Search order number, customer, description…",
    fr: "Rechercher par numéro de commande, client, description…",
  },
  "orders.searchLabel": { en: "Search orders", fr: "Rechercher des commandes" },
  "orders.filterPriorityLabel": { en: "Filter by priority", fr: "Filtrer par priorité" },
  "orders.priorityPlaceholder": { en: "Priority", fr: "Priorité" },
  "orders.allPriorities": { en: "All priorities", fr: "Toutes les priorités" },
  "orders.fromDateLabel": { en: "From date", fr: "Date de début" },
  "orders.toLabel": { en: "to", fr: "à" },
  "orders.toDateLabel": { en: "To date", fr: "Date de fin" },
  "orders.clear": { en: "Clear", fr: "Effacer" },

  // --- Load errors & empty states ------------------------------------------------
  "orders.loadFail": { en: "Failed to load orders", fr: "Échec du chargement des commandes" },
  "orders.loadFailedTitle": { en: "Could not load orders", fr: "Impossible de charger les commandes" },
  "orders.loadOrderFailed": { en: "Could not load order", fr: "Impossible de charger la commande" },
  "orders.emptyFiltersTitle": {
    en: "No orders match your filters",
    fr: "Aucune commande ne correspond à vos filtres",
  },
  "orders.emptyTitle": { en: "No orders registered yet", fr: "Aucune commande enregistrée pour le moment" },
  "orders.emptyFiltersDesc": {
    en: "Adjust the search or date filters to see more results.",
    fr: "Ajustez la recherche ou les filtres de date pour voir plus de résultats.",
  },
  "orders.emptyDesc": {
    en: "Register a customer order to start planning production.",
    fr: "Enregistrez une commande client pour commencer à planifier la production.",
  },

  // --- Table headers ---------------------------------------------------------------
  "orders.colOrder": { en: "Order", fr: "Commande" },
  "orders.colCustomer": { en: "Customer", fr: "Client" },
  "orders.colGarment": { en: "Garment", fr: "Vêtement" },
  "orders.colPieces": { en: "Pieces", fr: "Pièces" },
  "orders.colPriority": { en: "Priority", fr: "Priorité" },
  "orders.colDelivery": { en: "Expected Delivery", fr: "Livraison prévue" },
  "orders.colProduction": { en: "Production", fr: "Production" },
  "orders.colRegistered": { en: "Registered", fr: "Enregistrée le" },

  // --- Progress cells -----------------------------------------------------------------
  "orders.noTasks": { en: "No tasks yet", fr: "Aucune tâche pour le moment" },
  "orders.tasksDone": { en: "{done}/{total} task done", fr: "{done}/{total} tâche terminée" },
  "orders.tasksDonePlural": { en: "{done}/{total} tasks done", fr: "{done}/{total} tâches terminées" },

  // --- Order detail dialog ----------------------------------------------------------------
  "orders.detailMeta": { en: "Registered {date} · {name}", fr: "Enregistrée le {date} · {name}" },
  "orders.statOrderDate": { en: "Order date", fr: "Date de commande" },
  "orders.statDelivery": { en: "Expected delivery", fr: "Livraison prévue" },
  "orders.statPieces": { en: "Pieces", fr: "Pièces" },
  "orders.statCategory": { en: "Category", fr: "Catégorie" },
  "orders.descriptionLabel": { en: "Description", fr: "Description" },
  "orders.instructionsLabel": { en: "Instructions", fr: "Instructions" },
  "orders.notesLabel": { en: "Notes", fr: "Notes" },
  "orders.productionTasks": { en: "Production tasks", fr: "Tâches de production" },
  "orders.summaryDone": {
    en: "{done}/{total} done · avg {progress}%",
    fr: "{done}/{total} terminée · moy. {progress} %",
  },
  "orders.summaryDonePlural": {
    en: "{done}/{total} done · avg {progress}%",
    fr: "{done}/{total} terminées · moy. {progress} %",
  },
  "orders.noTasksTitle": { en: "Not broken into tasks yet", fr: "Pas encore découpée en tâches" },
  "orders.noTasksDesc": {
    en: "Split this order into garment pieces to start production.",
    fr: "Découpez cette commande en pièces de vêtement pour démarrer la production.",
  },
  "orders.unassigned": { en: "Unassigned", fr: "Non affectée" },
  "orders.breakIntoTasks": { en: "Break into Tasks", fr: "Découper en tâches" },

  // --- Register / edit dialog ------------------------------------------------------------------
  "orders.editTitle": { en: "Edit Order {number}", fr: "Modifier la commande {number}" },
  "orders.editDesc": {
    en: "Update order details. The linked customer cannot be changed.",
    fr: "Mettez à jour les détails de la commande. Le client associé ne peut pas être modifié.",
  },
  "orders.registerDesc": {
    en: "Enter the business order reference and garment details.",
    fr: "Saisissez la référence de la commande et les détails du vêtement.",
  },
  "orders.customerLabel": { en: "Customer", fr: "Client" },
  "orders.existingCustomer": { en: "Existing customer", fr: "Client existant" },
  "orders.newCustomer": { en: "New customer", fr: "Nouveau client" },
  "orders.selectCustomer": { en: "Select customer *", fr: "Sélectionner un client *" },
  "orders.selectCustomerPlaceholder": { en: "Select a customer…", fr: "Sélectionner un client…" },
  "orders.loadingCustomers": { en: "Loading customers…", fr: "Chargement des clients…" },
  "orders.newCustomerDetails": { en: "New customer details", fr: "Détails du nouveau client" },
  "orders.ncName": { en: "Name *", fr: "Nom *" },
  "orders.ncPhone": { en: "Phone *", fr: "Téléphone *" },
  "orders.ncEmail": { en: "Email", fr: "E-mail" },
  "orders.ncAddress": { en: "Address", fr: "Adresse" },
  "orders.ncNotes": { en: "Customer notes", fr: "Notes client" },
  "orders.fieldOrderNumber": { en: "Order number *", fr: "Numéro de commande *" },
  "orders.numberPlaceholder": { en: "e.g. OSSZ-2026-042", fr: "ex. OSSZ-2026-042" },
  "orders.fieldPriority": { en: "Priority", fr: "Priorité" },
  "orders.fieldOrderDate": { en: "Order date *", fr: "Date de commande *" },
  "orders.fieldDelivery": { en: "Expected delivery", fr: "Livraison prévue" },
  "orders.fieldDescription": { en: "Description *", fr: "Description *" },
  "orders.descPlaceholder": {
    en: "e.g. Bridal gown with hand embroidery",
    fr: "ex. Robe de mariée avec broderie à la main",
  },
  "orders.fieldCategory": { en: "Garment category *", fr: "Catégorie de vêtement *" },
  "orders.categoryPlaceholder": { en: "e.g. Suiting, Evening wear", fr: "ex. Costumes, tenues de soirée" },
  "orders.fieldPieces": { en: "Number of pieces *", fr: "Nombre de pièces *" },
  "orders.fieldInstructions": { en: "Production instructions", fr: "Instructions de production" },
  "orders.instructionsPlaceholder": {
    en: "Fabrics, colours, special handling…",
    fr: "Tissus, couleurs, précautions particulières…",
  },
  "orders.fieldNotes": { en: "Notes", fr: "Notes" },
  "orders.notesPlaceholder": { en: "Payment terms, follow-ups…", fr: "Conditions de paiement, relances…" },

  // --- Toasts & validation ----------------------------------------------------------------------
  "orders.updatedToast": { en: "Order updated", fr: "Commande modifiée" },
  "orders.updatedToastDesc": { en: "{number} was saved.", fr: "{number} a été enregistrée." },
  "orders.registeredToast": { en: "Order registered", fr: "Commande enregistrée" },
  "orders.registeredToastDesc": { en: "{number} was created.", fr: "{number} a été créée." },
  "orders.updateFailed": { en: "Could not update order", fr: "Impossible de modifier la commande" },
  "orders.registerFailed": { en: "Could not register order", fr: "Impossible d'enregistrer la commande" },
  "orders.errOrderNumber": { en: "Order number is required.", fr: "Le numéro de commande est obligatoire." },
  "orders.errOrderDate": { en: "Order date is required.", fr: "La date de commande est obligatoire." },
  "orders.errDescription": { en: "Description is required.", fr: "La description est obligatoire." },
  "orders.errCategory": {
    en: "Garment category is required.",
    fr: "La catégorie de vêtement est obligatoire.",
  },
  "orders.errPieces": {
    en: "Number of pieces must be between 1 and 200.",
    fr: "Le nombre de pièces doit être compris entre 1 et 200.",
  },
  "orders.errCustomerSelect": {
    en: "Select an existing customer or choose “New customer”.",
    fr: "Sélectionnez un client existant ou choisissez « Nouveau client ».",
  },
  "orders.errNewCustomer": {
    en: "New customer name and phone are required.",
    fr: "Le nom et le téléphone du nouveau client sont obligatoires.",
  },
};

export default entries;
