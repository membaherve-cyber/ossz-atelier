// OSSZ ATELIER — bilingual entries for the application shell: sidebar,
// navigation, header, notification bell, user menu and footer.

import type { DictEntries } from "../core";

const entries: DictEntries = {
  // --- Brand & sidebar -------------------------------------------------------
  "shell.brandTag": { en: "PRODUCTION MGMT", fr: "GESTION DE PRODUCTION" },
  "shell.independent": { en: "Independent production system", fr: "Système de production indépendant" },
  "shell.navAria": { en: "Main navigation", fr: "Navigation principale" },

  // --- Nav sections -------------------------------------------------------------
  "shell.section.workspace": { en: "Workspace", fr: "Espace de travail" },
  "shell.section.overview": { en: "Overview", fr: "Vue d'ensemble" },
  "shell.section.production": { en: "Production", fr: "Production" },
  "shell.section.insights": { en: "Insights", fr: "Analyses" },
  "shell.section.administration": { en: "Administration", fr: "Administration" },

  // --- Nav items ------------------------------------------------------------------
  "shell.nav.dashboard": { en: "Dashboard", fr: "Tableau de bord" },
  "shell.nav.myDashboard": { en: "My Dashboard", fr: "Mon tableau de bord" },
  "shell.nav.myTasks": { en: "My Tasks", fr: "Mes tâches" },
  "shell.nav.notifications": { en: "Notifications", fr: "Notifications" },
  "shell.nav.tasks": { en: "Production", fr: "Production" },
  "shell.nav.taskCreate": { en: "Create Tasks", fr: "Créer des tâches" },
  "shell.nav.orders": { en: "Orders", fr: "Commandes" },
  "shell.nav.customers": { en: "Customers", fr: "Clients" },
  "shell.nav.performance": { en: "Performance", fr: "Performance" },
  "shell.nav.incentives": { en: "Incentives", fr: "Primes" },
  "shell.nav.reports": { en: "Reports", fr: "Rapports" },
  "shell.nav.users": { en: "Users & Roles", fr: "Utilisateurs & rôles" },
  "shell.nav.staffAccounts": { en: "Staff Accounts", fr: "Comptes du personnel" },
  "shell.nav.stages": { en: "Production Stages", fr: "Étapes de production" },
  "shell.nav.audit": { en: "Audit History", fr: "Historique d'audit" },

  // --- View titles (header) -----------------------------------------------------------
  "shell.view.dashboard": { en: "Dashboard", fr: "Tableau de bord" },
  "shell.view.tasks": { en: "Production", fr: "Production" },
  "shell.view.taskCreate": { en: "Create Production Tasks", fr: "Créer des tâches de production" },
  "shell.view.taskDetail": { en: "Task Detail", fr: "Détail de la tâche" },
  "shell.view.orders": { en: "Orders", fr: "Commandes" },
  "shell.view.customers": { en: "Customers", fr: "Clients" },
  "shell.view.performance": { en: "Staff Performance", fr: "Performance du personnel" },
  "shell.view.incentives": { en: "Incentive Management", fr: "Gestion des primes" },
  "shell.view.reports": { en: "Reports", fr: "Rapports" },
  "shell.view.users": { en: "Users & Roles", fr: "Utilisateurs & rôles" },
  "shell.view.staffAccounts": { en: "Staff Accounts", fr: "Comptes du personnel" },
  "shell.view.audit": { en: "Audit History", fr: "Historique d'audit" },
  "shell.view.stages": { en: "Production Stages", fr: "Étapes de production" },
  "shell.view.notifications": { en: "Notifications", fr: "Notifications" },

  // --- Header controls --------------------------------------------------------------------
  "shell.logoAria": {
    en: "OSSZ Atelier logo — go to dashboard",
    fr: "Logo OSSZ Atelier — aller au tableau de bord",
  },
  "shell.openMenu": { en: "Open menu", fr: "Ouvrir le menu" },
  "shell.navigation": { en: "Navigation", fr: "Navigation" },
  "shell.bellAria": { en: "Notifications", fr: "Notifications" },
  "shell.accountMenu": { en: "Account menu", fr: "Menu du compte" },
  "shell.languageAria": { en: "Language", fr: "Langue" },
  "shell.languageTitle": { en: "Switch language", fr: "Changer de langue" },

  // --- Notification bell --------------------------------------------------------------------
  "shell.bell.title": { en: "Notifications", fr: "Notifications" },
  "shell.bell.viewAll": { en: "View all", fr: "Tout afficher" },
  "shell.bell.markAllRead": { en: "Mark all read", fr: "Tout marquer comme lu" },
  "shell.bell.empty": { en: "No notifications yet.", fr: "Aucune notification pour le moment." },

  // --- User menu -------------------------------------------------------------------------------
  "shell.user.signOut": { en: "Sign out", fr: "Se déconnecter" },

  // --- Footer -----------------------------------------------------------------------------------
  "shell.footer.left": {
    en: "© {year} OSSZ Atelier — Independent Production Management System",
    fr: "© {year} OSSZ Atelier — Système indépendant de gestion de production",
  },
  "shell.footer.right": {
    en: "Own database · Own accounts · atelier.osszcollection.com",
    fr: "Base de données propre · Comptes propres · atelier.osszcollection.com",
  },
};

export default entries;
