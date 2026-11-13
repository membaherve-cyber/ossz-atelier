// OSSZ ATELIER — bilingual entries for the users module.

import type { DictEntries } from "../core";

const entries: DictEntries = {
  // --- Page header ------------------------------------------------------------
  "users.title": { en: "Users & Roles", fr: "Utilisateurs & rôles" },
  "users.description": {
    en: "Accounts with access to the atelier management system.",
    fr: "Comptes ayant accès au système de gestion de l'atelier.",
  },
  "users.createAccount": { en: "Create Account", fr: "Créer un compte" },

  // --- Loading & error states ---------------------------------------------------
  "users.errorTitle": { en: "Could not load users", fr: "Impossible de charger les utilisateurs" },
  "users.loadFailed": {
    en: "Failed to load users",
    fr: "Le chargement des utilisateurs a échoué.",
  },
  "users.emptyTitle": { en: "No accounts found", fr: "Aucun compte trouvé" },

  // --- Table headers ---------------------------------------------------------------
  "users.col.userId": { en: "User ID", fr: "Identifiant" },
  "users.col.user": { en: "User", fr: "Utilisateur" },
  "users.col.role": { en: "Role", fr: "Rôle" },
  "users.col.jobTitle": { en: "Job title", fr: "Fonction" },
  "users.col.phone": { en: "Phone", fr: "Téléphone" },
  "users.col.status": { en: "Status", fr: "Statut" },
  "users.col.activeTasks": { en: "Active tasks", fr: "Tâches actives" },
  "users.col.created": { en: "Created", fr: "Créé le" },

  // --- Row status & actions ---------------------------------------------------------
  "users.active": { en: "Active", fr: "Actif" },
  "users.inactive": { en: "Inactive", fr: "Inactif" },
  "users.actionsFor": { en: "Actions for {name}", fr: "Actions de {name}" },
  "users.editDetails": { en: "Edit details", fr: "Modifier les détails" },
  "users.resetPassword": { en: "Reset password", fr: "Réinitialiser le mot de passe" },
  "users.resetPasswordAction": {
    en: "Reset Password",
    fr: "Réinitialiser le mot de passe",
  },
  "users.deactivate": { en: "Deactivate", fr: "Désactiver" },
  "users.reactivate": { en: "Reactivate", fr: "Réactiver" },
  "users.adminAccessNote": {
    en: "Admin access: staff only",
    fr: "Accès admin : personnel uniquement",
  },

  // --- Create account dialog -----------------------------------------------------------
  "users.createDescription": {
    en: "New accounts can sign in immediately with the password you set.",
    fr: "Les nouveaux comptes peuvent se connecter immédiatement avec le mot de passe que vous définissez.",
  },
  "users.fullName": { en: "Full name *", fr: "Nom complet *" },
  "users.namePlaceholder": { en: "e.g. Marie Ngo", fr: "ex. Marie Ngo" },
  "users.email": { en: "Email *", fr: "E-mail *" },
  "users.password": { en: "Password *", fr: "Mot de passe *" },
  "users.passwordPlaceholder": {
    en: "At least 8 characters",
    fr: "Au moins 8 caractères",
  },
  "users.roleRequired": { en: "Role *", fr: "Rôle *" },
  "users.phoneLabel": { en: "Phone", fr: "Téléphone" },
  "users.jobTitleLabel": { en: "Job title", fr: "Fonction" },
  "users.jobTitlePlaceholder": { en: "e.g. Tailor, Cutter", fr: "ex. Couturier, Coupeur" },
  "users.roleHint.management": {
    en: "Full access, final authority on incentives and critical decisions.",
    fr: "Accès complet, autorité finale sur les primes et les décisions critiques.",
  },
  "users.roleHint.admin": {
    en: "Operational management of customers, orders, production and staff accounts.",
    fr: "Gestion opérationnelle des clients, commandes, production et comptes du personnel.",
  },
  "users.roleHint.staff": {
    en: "Sees and updates only the tasks assigned to their own account.",
    fr: "Voit et met à jour uniquement les tâches affectées à son propre compte.",
  },
  "users.roleHint.staffExtra": {
    en: "Staff accounts can be assigned production tasks.",
    fr: "Les comptes du personnel peuvent se voir affecter des tâches de production.",
  },

  // --- Form validation & create toasts ------------------------------------------------
  "users.nameEmailRequired": {
    en: "Name and email are required.",
    fr: "Le nom et l'e-mail sont obligatoires.",
  },
  "users.passwordTooShort": {
    en: "Password must be at least 8 characters.",
    fr: "Le mot de passe doit contenir au moins 8 caractères.",
  },
  "users.createdToastTitle": { en: "Account created", fr: "Compte créé" },
  "users.createdToastDesc": {
    en: "{name} ({role}) can now sign in.",
    fr: "{name} ({role}) peut maintenant se connecter.",
  },
  "users.createFailedToast": {
    en: "Could not create account",
    fr: "Impossible de créer le compte",
  },

  // --- Edit dialog ----------------------------------------------------------------------
  "users.editTitle": { en: "Edit Account", fr: "Modifier le compte" },
  "users.roleLabel": { en: "Role", fr: "Rôle" },
  "users.updateFailedToast": {
    en: "Could not update account",
    fr: "Impossible de mettre à jour le compte",
  },
  "users.nameTooShort": {
    en: "Name must be at least 2 characters.",
    fr: "Le nom doit contenir au moins 2 caractères.",
  },
  "users.updatedToastTitle": { en: "Account updated", fr: "Compte mis à jour" },
  "users.savedToast": {
    en: "{name} was saved.",
    fr: "{name} a été enregistré(e).",
  },

  // --- Reset password dialog ---------------------------------------------------------------
  "users.resetPwDescription": {
    en: "Set a new password for {name} ({email}). They will use it at the next sign-in.",
    fr: "Définissez un nouveau mot de passe pour {name} ({email}). Il sera utilisé lors de la prochaine connexion.",
  },
  "users.newPassword": { en: "New password *", fr: "Nouveau mot de passe *" },
  "users.resetToastTitle": {
    en: "Password reset",
    fr: "Mot de passe réinitialisé",
  },
  "users.resetToastDesc": {
    en: "{name} can sign in with the new password.",
    fr: "{name} peut se connecter avec le nouveau mot de passe.",
  },
  "users.resetFailedToast": {
    en: "Could not reset password",
    fr: "Impossible de réinitialiser le mot de passe",
  },

  // --- Deactivate / reactivate dialog ----------------------------------------------------------
  "users.deactivateTitle": { en: "Deactivate account", fr: "Désactiver le compte" },
  "users.reactivateTitle": { en: "Reactivate account", fr: "Réactiver le compte" },
  "users.deactivateDesc": {
    en: "{name} will no longer be able to sign in. Task history and audit records are kept.",
    fr: "{name} ne pourra plus se connecter. L'historique des tâches et le journal d'audit sont conservés.",
  },
  "users.reactivateDesc": {
    en: "{name} will be able to sign in again with their existing password.",
    fr: "{name} pourra de nouveau se connecter avec son mot de passe actuel.",
  },
  "users.reactivatedToastTitle": { en: "Account reactivated", fr: "Compte réactivé" },
  "users.deactivatedToastTitle": { en: "Account deactivated", fr: "Compte désactivé" },
  "users.reactivatedToastDesc": {
    en: "{name} can sign in again.",
    fr: "{name} peut de nouveau se connecter.",
  },
  "users.deactivatedToastDesc": {
    en: "{name} can no longer sign in.",
    fr: "{name} ne peut plus se connecter.",
  },
};

export default entries;
