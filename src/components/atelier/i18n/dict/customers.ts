// OSSZ ATELIER — bilingual entries for the customers module.

import type { DictEntries } from "../core";

const entries: DictEntries = {
  // --- Page header & actions ----------------------------------------------
  "cust.title": { en: "Customers", fr: "Clients" },
  "cust.subtitle": {
    en: "Directory of registered customers with their order history.",
    fr: "Répertoire des clients enregistrés avec leur historique de commandes.",
  },
  "cust.register": { en: "Register Customer", fr: "Enregistrer un client" },

  // --- Search ------------------------------------------------------------------
  "cust.searchPlaceholder": {
    en: "Search by name, phone, email or code…",
    fr: "Rechercher par nom, téléphone, e-mail ou code…",
  },
  "cust.searchLabel": { en: "Search customers", fr: "Rechercher des clients" },

  // --- Load errors & empty states ---------------------------------------------------
  "cust.loadFail": { en: "Failed to load customers", fr: "Échec du chargement des clients" },
  "cust.loadFailedTitle": { en: "Could not load customers", fr: "Impossible de charger les clients" },
  "cust.emptySearchTitle": {
    en: "No customers match your search",
    fr: "Aucun client ne correspond à votre recherche",
  },
  "cust.emptyTitle": { en: "No customers registered yet", fr: "Aucun client enregistré pour le moment" },
  "cust.emptySearchDesc": {
    en: "Try a different name, phone number or customer code.",
    fr: "Essayez un autre nom, numéro de téléphone ou code client.",
  },
  "cust.emptyDesc": {
    en: "Register your first customer to start taking orders.",
    fr: "Enregistrez votre premier client pour commencer à prendre des commandes.",
  },

  // --- Table headers --------------------------------------------------------------------
  "cust.colCode": { en: "Code", fr: "Code" },
  "cust.colName": { en: "Name", fr: "Nom" },
  "cust.colPhone": { en: "Phone", fr: "Téléphone" },
  "cust.colEmail": { en: "Email", fr: "E-mail" },
  "cust.colAddress": { en: "Address", fr: "Adresse" },
  "cust.colOrders": { en: "Orders", fr: "Commandes" },
  "cust.colRegistered": { en: "Registered", fr: "Enregistré le" },
  "cust.editAria": { en: "Edit {name}", fr: "Modifier {name}" },

  // --- Register / edit dialog ---------------------------------------------------------------
  "cust.editTitle": { en: "Edit Customer", fr: "Modifier le client" },
  "cust.editDesc": { en: "Update the details of {name} ({code}).", fr: "Mettez à jour les informations de {name} ({code})." },
  "cust.registerDesc": {
    en: "A unique customer code (CUST-XXXX) is generated automatically.",
    fr: "Un code client unique (CUST-XXXX) est généré automatiquement.",
  },
  "cust.fieldName": { en: "Name *", fr: "Nom *" },
  "cust.namePlaceholder": { en: "e.g. Amina Cissé", fr: "ex. Amina Cissé" },
  "cust.fieldPhone": { en: "Phone *", fr: "Téléphone *" },
  "cust.fieldEmail": { en: "Email", fr: "E-mail" },
  "cust.fieldAddress": { en: "Address", fr: "Adresse" },
  "cust.addressPlaceholder": { en: "Neighbourhood, city", fr: "Quartier, ville" },
  "cust.fieldNotes": { en: "Notes", fr: "Notes" },
  "cust.notesPlaceholder": {
    en: "Preferences, measurements remarks, payment history…",
    fr: "Préférences, remarques sur les mesures, historique de paiement…",
  },

  // --- Toasts & validation ----------------------------------------------------------------------
  "cust.namePhoneRequired": {
    en: "Customer name and phone number are required.",
    fr: "Le nom et le numéro de téléphone du client sont obligatoires.",
  },
  "cust.updatedToast": { en: "Customer updated", fr: "Client modifié" },
  "cust.updatedToastDesc": { en: "{name} was saved.", fr: "{name} a été enregistré." },
  "cust.registeredToast": { en: "Customer registered", fr: "Client enregistré" },
  "cust.registeredToastDesc": { en: "{name} received code {code}.", fr: "{name} a reçu le code {code}." },
  "cust.updateFailed": { en: "Could not update customer", fr: "Impossible de modifier le client" },
  "cust.registerFailed": { en: "Could not register customer", fr: "Impossible d'enregistrer le client" },
};

export default entries;
