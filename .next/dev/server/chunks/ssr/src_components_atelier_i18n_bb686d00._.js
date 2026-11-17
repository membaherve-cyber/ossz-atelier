module.exports = [
"[project]/src/components/atelier/i18n/dict/common.ts [app-ssr] (ecmascript)", ((__turbopack_context__) => {
"use strict";

// OSSZ ATELIER — shared bilingual entries: generic actions, workflow/domain
// labels and internal notification titles. Module-specific screens keep their
// strings in their own dictionary file.
__turbopack_context__.s([
    "default",
    ()=>__TURBOPACK__default__export__
]);
const entries = {
    // --- Generic actions & common UI ------------------------------------------
    "common.loading": {
        en: "Loading…",
        fr: "Chargement…"
    },
    "common.tryAgain": {
        en: "Try again",
        fr: "Réessayer"
    },
    "common.save": {
        en: "Save",
        fr: "Enregistrer"
    },
    "common.saveChanges": {
        en: "Save Changes",
        fr: "Enregistrer les modifications"
    },
    "common.cancel": {
        en: "Cancel",
        fr: "Annuler"
    },
    "common.close": {
        en: "Close",
        fr: "Fermer"
    },
    "common.create": {
        en: "Create",
        fr: "Créer"
    },
    "common.edit": {
        en: "Edit",
        fr: "Modifier"
    },
    "common.delete": {
        en: "Delete",
        fr: "Supprimer"
    },
    "common.actions": {
        en: "Actions",
        fr: "Actions"
    },
    "common.search": {
        en: "Search",
        fr: "Rechercher"
    },
    "common.filter": {
        en: "Filter",
        fr: "Filtrer"
    },
    "common.all": {
        en: "All",
        fr: "Tout"
    },
    "common.yes": {
        en: "Yes",
        fr: "Oui"
    },
    "common.no": {
        en: "No",
        fr: "Non"
    },
    "common.view": {
        en: "View",
        fr: "Consulter"
    },
    "common.back": {
        en: "Back",
        fr: "Retour"
    },
    "common.optional": {
        en: "optional",
        fr: "facultatif"
    },
    "common.required": {
        en: "required",
        fr: "obligatoire"
    },
    "common.somethingWrong": {
        en: "Something went wrong. Please try again.",
        fr: "Une erreur est survenue. Veuillez réessayer."
    },
    "common.showPassword": {
        en: "Show password",
        fr: "Afficher le mot de passe"
    },
    "common.hidePassword": {
        en: "Hide password",
        fr: "Masquer le mot de passe"
    },
    // --- Task workflow statuses -------------------------------------------------
    "status.UNASSIGNED": {
        en: "Unassigned",
        fr: "Non affectée"
    },
    "status.ASSIGNED": {
        en: "Assigned",
        fr: "Affectée"
    },
    "status.ONGOING": {
        en: "Ongoing",
        fr: "En cours"
    },
    "status.AWAITING_INSPECTION": {
        en: "Awaiting Inspection",
        fr: "En attente d'inspection"
    },
    "status.CHANGES_REQUESTED": {
        en: "Changes Requested",
        fr: "Retouches demandées"
    },
    "status.APPROVED": {
        en: "Approved",
        fr: "Approuvée"
    },
    "status.READY_FOR_PICKUP": {
        en: "Ready for Pickup",
        fr: "Prêt pour retrait"
    },
    "status.DELIVERED": {
        en: "Delivered/Collected",
        fr: "Livrée/Récupérée"
    },
    "status.BLOCKED": {
        en: "Blocked",
        fr: "Bloquée"
    },
    "status.CANCELLED": {
        en: "Cancelled",
        fr: "Annulée"
    },
    // --- Priorities --------------------------------------------------------------
    "priority.LOW": {
        en: "Low",
        fr: "Basse"
    },
    "priority.NORMAL": {
        en: "Normal",
        fr: "Normale"
    },
    "priority.HIGH": {
        en: "High",
        fr: "Haute"
    },
    "priority.URGENT": {
        en: "Urgent",
        fr: "Urgente"
    },
    // --- Task categories -----------------------------------------------------------
    "category.FULL": {
        en: "Full Production",
        fr: "Production complète"
    },
    "category.CUTTING": {
        en: "Cutting Only",
        fr: "Coupe uniquement"
    },
    "category.SEWING": {
        en: "Sewing/Stitching Only",
        fr: "Couture uniquement"
    },
    "category.FINISHING": {
        en: "Finishing",
        fr: "Finition"
    },
    "category.IRONING": {
        en: "Ironing Only",
        fr: "Repassage uniquement"
    },
    "category.OTHER": {
        en: "Other Activity",
        fr: "Autre activité"
    },
    // --- Checklist stage states -------------------------------------------------------
    "stageState.NOT_STARTED": {
        en: "Not Started",
        fr: "Non commencé"
    },
    "stageState.IN_PROGRESS": {
        en: "In Progress",
        fr: "En cours"
    },
    "stageState.COMPLETED": {
        en: "Completed",
        fr: "Terminé"
    },
    "stageState.NOT_APPLICABLE": {
        en: "Not Applicable",
        fr: "Non applicable"
    },
    // --- Roles ---------------------------------------------------------------------------
    "role.MANAGEMENT": {
        en: "Management",
        fr: "Direction"
    },
    "role.ADMIN": {
        en: "Admin",
        fr: "Admin"
    },
    "role.STAFF": {
        en: "Staff",
        fr: "Personnel"
    },
    // --- Quality inspection decisions ------------------------------------------------------
    "inspection.APPROVED": {
        en: "Approved",
        fr: "Approuvé"
    },
    "inspection.CHANGES_REQUESTED": {
        en: "Changes Requested",
        fr: "Retouches demandées"
    },
    "inspection.REOPENED": {
        en: "Reopened",
        fr: "Rouvert"
    },
    // --- Incentive schemes --------------------------------------------------------------------
    "incentivePeriod.MONTHLY": {
        en: "Monthly productivity",
        fr: "Productivité mensuelle"
    },
    "incentivePeriod.QUARTERLY": {
        en: "Quarterly performance",
        fr: "Performance trimestrielle"
    },
    "incentivePeriod.ANNUAL": {
        en: "Annual performance",
        fr: "Performance annuelle"
    },
    "incentivePeriod.QUALITY": {
        en: "Quality achievements",
        fr: "Réalisations qualité"
    },
    "incentivePeriod.ON_TIME": {
        en: "On-time completion",
        fr: "Achèvement dans les délais"
    },
    "incentivePeriod.CUSTOM": {
        en: "Management-defined target",
        fr: "Objectif défini par la direction"
    },
    // --- Incentive & payment statuses ------------------------------------------------------------
    "incStatus.PENDING": {
        en: "Pending",
        fr: "En attente"
    },
    "incStatus.APPROVED": {
        en: "Approved",
        fr: "Approuvée"
    },
    "incStatus.REJECTED": {
        en: "Rejected",
        fr: "Refusée"
    },
    "paymentStatus.UNPAID": {
        en: "Unpaid",
        fr: "Impayé"
    },
    "paymentStatus.PAID": {
        en: "Paid",
        fr: "Payé"
    },
    // --- Notification titles (keyed by stored notification type) -----------------------------------
    "notifTitle.TASK_ASSIGNED": {
        en: "New task assigned",
        fr: "Nouvelle tâche affectée"
    },
    "notifTitle.TASK_REASSIGNED": {
        en: "Task reassigned to you",
        fr: "Tâche réaffectée"
    },
    "notifTitle.DEADLINE_CHANGED": {
        en: "Deadline changed",
        fr: "Échéance modifiée"
    },
    "notifTitle.NEW_INSTRUCTIONS": {
        en: "New instructions",
        fr: "Nouvelles instructions"
    },
    "notifTitle.CORRECTIONS_REQUESTED": {
        en: "Corrections requested",
        fr: "Retouches demandées"
    },
    "notifTitle.BLOCK_RESOLVED": {
        en: "Block resolved",
        fr: "Blocage résolu"
    },
    "notifTitle.TASK_APPROVED": {
        en: "Work approved",
        fr: "Travail approuvé"
    },
    "notifTitle.TASK_REOPENED": {
        en: "Task reopened",
        fr: "Tâche rouverte"
    },
    "notifTitle.SUBMITTED_FOR_INSPECTION": {
        en: "Work submitted for inspection",
        fr: "Travail soumis à l'inspection"
    },
    "notifTitle.TASK_OVERDUE": {
        en: "Task overdue",
        fr: "Tâche en retard"
    },
    "notifTitle.ISSUE_REPORTED": {
        en: "Production issue reported",
        fr: "Problème de production signalé"
    },
    "notifTitle.TASK_BLOCKED": {
        en: "Task blocked",
        fr: "Tâche bloquée"
    },
    "notifTitle.READY_FOR_PICKUP": {
        en: "Ready for pickup",
        fr: "Prêt pour retrait"
    },
    "notifTitle.TASK_DELIVERED": {
        en: "Garment delivered/collected",
        fr: "Vêtement livré/récupéré"
    },
    "notifTitle.TASK_CANCELLED": {
        en: "Task cancelled",
        fr: "Tâche annulée"
    }
};
const __TURBOPACK__default__export__ = entries;
}),
"[project]/src/components/atelier/i18n/dict/shell.ts [app-ssr] (ecmascript)", ((__turbopack_context__) => {
"use strict";

// OSSZ ATELIER — bilingual entries for the application shell: sidebar,
// navigation, header, notification bell, user menu and footer.
__turbopack_context__.s([
    "default",
    ()=>__TURBOPACK__default__export__
]);
const entries = {
    // --- Brand & sidebar -------------------------------------------------------
    "shell.brandTag": {
        en: "PRODUCTION MGMT",
        fr: "GESTION DE PRODUCTION"
    },
    "shell.independent": {
        en: "Independent production system",
        fr: "Système de production indépendant"
    },
    "shell.navAria": {
        en: "Main navigation",
        fr: "Navigation principale"
    },
    // --- Nav sections -------------------------------------------------------------
    "shell.section.workspace": {
        en: "Workspace",
        fr: "Espace de travail"
    },
    "shell.section.overview": {
        en: "Overview",
        fr: "Vue d'ensemble"
    },
    "shell.section.production": {
        en: "Production",
        fr: "Production"
    },
    "shell.section.insights": {
        en: "Insights",
        fr: "Analyses"
    },
    "shell.section.administration": {
        en: "Administration",
        fr: "Administration"
    },
    // --- Nav items ------------------------------------------------------------------
    "shell.nav.dashboard": {
        en: "Dashboard",
        fr: "Tableau de bord"
    },
    "shell.nav.myDashboard": {
        en: "My Dashboard",
        fr: "Mon tableau de bord"
    },
    "shell.nav.myTasks": {
        en: "My Tasks",
        fr: "Mes tâches"
    },
    "shell.nav.notifications": {
        en: "Notifications",
        fr: "Notifications"
    },
    "shell.nav.tasks": {
        en: "Production",
        fr: "Production"
    },
    "shell.nav.taskCreate": {
        en: "Create Tasks",
        fr: "Créer des tâches"
    },
    "shell.nav.orders": {
        en: "Orders",
        fr: "Commandes"
    },
    "shell.nav.customers": {
        en: "Customers",
        fr: "Clients"
    },
    "shell.nav.performance": {
        en: "Performance",
        fr: "Performance"
    },
    "shell.nav.incentives": {
        en: "Incentives",
        fr: "Primes"
    },
    "shell.nav.reports": {
        en: "Reports",
        fr: "Rapports"
    },
    "shell.nav.users": {
        en: "Users & Roles",
        fr: "Utilisateurs & rôles"
    },
    "shell.nav.staffAccounts": {
        en: "Staff Accounts",
        fr: "Comptes du personnel"
    },
    "shell.nav.stages": {
        en: "Production Stages",
        fr: "Étapes de production"
    },
    "shell.nav.audit": {
        en: "Audit History",
        fr: "Historique d'audit"
    },
    // --- View titles (header) -----------------------------------------------------------
    "shell.view.dashboard": {
        en: "Dashboard",
        fr: "Tableau de bord"
    },
    "shell.view.tasks": {
        en: "Production",
        fr: "Production"
    },
    "shell.view.taskCreate": {
        en: "Create Production Tasks",
        fr: "Créer des tâches de production"
    },
    "shell.view.taskDetail": {
        en: "Task Detail",
        fr: "Détail de la tâche"
    },
    "shell.view.orders": {
        en: "Orders",
        fr: "Commandes"
    },
    "shell.view.customers": {
        en: "Customers",
        fr: "Clients"
    },
    "shell.view.performance": {
        en: "Staff Performance",
        fr: "Performance du personnel"
    },
    "shell.view.incentives": {
        en: "Incentive Management",
        fr: "Gestion des primes"
    },
    "shell.view.reports": {
        en: "Reports",
        fr: "Rapports"
    },
    "shell.view.users": {
        en: "Users & Roles",
        fr: "Utilisateurs & rôles"
    },
    "shell.view.staffAccounts": {
        en: "Staff Accounts",
        fr: "Comptes du personnel"
    },
    "shell.view.audit": {
        en: "Audit History",
        fr: "Historique d'audit"
    },
    "shell.view.stages": {
        en: "Production Stages",
        fr: "Étapes de production"
    },
    "shell.view.notifications": {
        en: "Notifications",
        fr: "Notifications"
    },
    // --- Header controls --------------------------------------------------------------------
    "shell.logoAria": {
        en: "OSSZ Atelier logo — go to dashboard",
        fr: "Logo OSSZ Atelier — aller au tableau de bord"
    },
    "shell.openMenu": {
        en: "Open menu",
        fr: "Ouvrir le menu"
    },
    "shell.navigation": {
        en: "Navigation",
        fr: "Navigation"
    },
    "shell.bellAria": {
        en: "Notifications",
        fr: "Notifications"
    },
    "shell.accountMenu": {
        en: "Account menu",
        fr: "Menu du compte"
    },
    "shell.languageAria": {
        en: "Language",
        fr: "Langue"
    },
    "shell.languageTitle": {
        en: "Switch language",
        fr: "Changer de langue"
    },
    // --- Notification bell --------------------------------------------------------------------
    "shell.bell.title": {
        en: "Notifications",
        fr: "Notifications"
    },
    "shell.bell.viewAll": {
        en: "View all",
        fr: "Tout afficher"
    },
    "shell.bell.markAllRead": {
        en: "Mark all read",
        fr: "Tout marquer comme lu"
    },
    "shell.bell.empty": {
        en: "No notifications yet.",
        fr: "Aucune notification pour le moment."
    },
    // --- User menu -------------------------------------------------------------------------------
    "shell.user.signOut": {
        en: "Sign out",
        fr: "Se déconnecter"
    },
    // --- Footer -----------------------------------------------------------------------------------
    "shell.footer.left": {
        en: "© {year} OSSZ Atelier — Independent Production Management System",
        fr: "© {year} OSSZ Atelier — Système indépendant de gestion de production"
    },
    "shell.footer.right": {
        en: "Own database · Own accounts · atelier.osszcollection.com",
        fr: "Base de données propre · Comptes propres · atelier.osszcollection.com"
    }
};
const __TURBOPACK__default__export__ = entries;
}),
"[project]/src/components/atelier/i18n/dict/login.ts [app-ssr] (ecmascript)", ((__turbopack_context__) => {
"use strict";

// OSSZ ATELIER — bilingual entries for the login screen (no public registration).
__turbopack_context__.s([
    "default",
    ()=>__TURBOPACK__default__export__
]);
const entries = {
    "login.heroTitle": {
        en: "From customer order to finished garment, every piece accounted for.",
        fr: "De la commande client au vêtement fini, chaque pièce est tracée."
    },
    "login.heroBody": {
        en: "Independent production management for OSSZ: manual order registration, staff task allocation, checklist tracking, quality control, performance and incentives.",
        fr: "Gestion de production indépendante pour OSSZ : enregistrement manuel des commandes, affectation des tâches au personnel, suivi par liste de contrôle, contrôle qualité, performance et primes."
    },
    "login.heroFooter": {
        en: "atelier.osszcollection.com — independent system, own database, own accounts.",
        fr: "atelier.osszcollection.com — système indépendant, base de données propre, comptes propres."
    },
    "login.brandTag": {
        en: "PRODUCTION MANAGEMENT",
        fr: "GESTION DE PRODUCTION"
    },
    "login.title": {
        en: "Sign in",
        fr: "Connexion"
    },
    "login.subtitle": {
        en: "Use the account provided to you by Management.",
        fr: "Utilisez le compte qui vous a été fourni par la direction."
    },
    "login.email": {
        en: "Email address",
        fr: "Adresse e-mail"
    },
    "login.emailPlaceholder": {
        en: "you@atelier.com",
        fr: "vous@atelier.com"
    },
    "login.password": {
        en: "Password",
        fr: "Mot de passe"
    },
    "login.passwordPlaceholder": {
        en: "Your password",
        fr: "Votre mot de passe"
    },
    "login.showPassword": {
        en: "Show password",
        fr: "Afficher le mot de passe"
    },
    "login.hidePassword": {
        en: "Hide password",
        fr: "Masquer le mot de passe"
    },
    "login.submit": {
        en: "Sign in",
        fr: "Se connecter"
    },
    "login.submitting": {
        en: "Signing in…",
        fr: "Connexion…"
    },
    "login.error": {
        en: "Unable to sign in. Please try again.",
        fr: "Impossible de se connecter. Veuillez réessayer."
    },
    "login.demoTitle": {
        en: "Demo accounts (password: Atelier@2026)",
        fr: "Comptes de démonstration (mot de passe : Atelier@2026)"
    },
    "login.demoManagement": {
        en: "Management",
        fr: "Direction"
    },
    "login.demoAdmin": {
        en: "Admin",
        fr: "Admin"
    },
    "login.demoStaffHamidou": {
        en: "Staff (Hamidou)",
        fr: "Personnel (Hamidou)"
    },
    "login.demoStaffLawal": {
        en: "Staff (Lawal)",
        fr: "Personnel (Lawal)"
    },
    "login.logoAlt": {
        en: "OSSZ Atelier logo",
        fr: "Logo OSSZ Atelier"
    }
};
const __TURBOPACK__default__export__ = entries;
}),
"[project]/src/components/atelier/i18n/dict/dashboard.ts [app-ssr] (ecmascript)", ((__turbopack_context__) => {
"use strict";

// OSSZ ATELIER — bilingual entries for the dashboard module.
__turbopack_context__.s([
    "default",
    ()=>__TURBOPACK__default__export__
]);
const entries = {
    // --- Shared ------------------------------------------------------------------
    "dash.loadError": {
        en: "Could not load dashboard",
        fr: "Impossible de charger le tableau de bord"
    },
    // --- Staff variant -------------------------------------------------------------
    "dash.welcome": {
        en: "Welcome back, {name}",
        fr: "Bon retour, {name}"
    },
    "dash.staffDesc": {
        en: "Here is your personal production overview. Only your assigned tasks are visible.",
        fr: "Voici votre aperçu personnel de la production. Seules les tâches qui vous sont affectées sont visibles."
    },
    "dash.stat.myActive": {
        en: "My Active Tasks",
        fr: "Mes tâches actives"
    },
    "dash.stat.awaiting": {
        en: "Awaiting Inspection",
        fr: "En attente d'inspection"
    },
    "dash.stat.completedThisMonth": {
        en: "Completed This Month",
        fr: "Terminées ce mois-ci"
    },
    "dash.stat.overdue": {
        en: "Overdue",
        fr: "En retard"
    },
    "dash.myTasks": {
        en: "My Production Tasks",
        fr: "Mes tâches de production"
    },
    "dash.viewAll": {
        en: "View all",
        fr: "Tout afficher"
    },
    "dash.noTasksTitle": {
        en: "No tasks assigned yet",
        fr: "Aucune tâche affectée pour le moment"
    },
    "dash.noTasksDesc": {
        en: "When Management allocates garment pieces to your account, they will appear here.",
        fr: "Lorsque la direction alloue des pièces à votre compte, elles apparaîtront ici."
    },
    // --- Management / Admin variant --------------------------------------------------
    "dash.managerTitle": {
        en: "{role} Dashboard",
        fr: "Tableau de bord — {role}"
    },
    "dash.managerDesc": {
        en: "Complete production overview across all registered orders and staff.",
        fr: "Vue d'ensemble complète de la production, pour toutes les commandes enregistrées et tout le personnel."
    },
    "dash.createTasks": {
        en: "Create Tasks",
        fr: "Créer des tâches"
    },
    "dash.stat.totalOrders": {
        en: "Registered Orders",
        fr: "Commandes enregistrées"
    },
    "dash.stat.activeTasks": {
        en: "Active Tasks",
        fr: "Tâches actives"
    },
    "dash.stat.unassigned": {
        en: "Unassigned",
        fr: "Non affectées"
    },
    "dash.stat.ongoing": {
        en: "Ongoing",
        fr: "En cours"
    },
    "dash.stat.approved": {
        en: "Approved",
        fr: "Approuvées"
    },
    "dash.stat.readyForPickup": {
        en: "Ready for Pickup",
        fr: "Prêtes pour retrait"
    },
    "dash.stat.delivered": {
        en: "Delivered",
        fr: "Livrées"
    },
    "dash.stat.blocked": {
        en: "Blocked",
        fr: "Bloquées"
    },
    "dash.overdueTitle": {
        en: "Overdue Tasks",
        fr: "Tâches en retard"
    },
    "dash.productionBoard": {
        en: "Production board",
        fr: "Tableau de production"
    },
    "dash.overdueEmptyTitle": {
        en: "Nothing overdue",
        fr: "Aucun retard"
    },
    "dash.overdueEmptyDesc": {
        en: "All deadlines are under control.",
        fr: "Toutes les échéances sont maîtrisées."
    },
    "dash.unassigned": {
        en: "Unassigned",
        fr: "Non affectée"
    },
    "dash.dueSoonTitle": {
        en: "Due in the Next 7 Days",
        fr: "Échéances dans les 7 prochains jours"
    },
    "dash.dueSoonEmptyTitle": {
        en: "No imminent deadlines",
        fr: "Aucune échéance imminente"
    },
    "dash.dueSoonEmptyDesc": {
        en: "No tasks are due within the next seven days.",
        fr: "Aucune tâche n'arrive à échéance dans les sept prochains jours."
    },
    "dash.workloadTitle": {
        en: "Staff Workload (Active Tasks)",
        fr: "Charge de travail du personnel (tâches actives)"
    },
    "dash.workloadEmpty": {
        en: "No active staff accounts",
        fr: "Aucun compte personnel actif"
    },
    "dash.awaitingCount": {
        en: "{count} awaiting inspection",
        fr: "{count} en attente d'inspection"
    },
    "dash.overdueCount": {
        en: "{count} overdue",
        fr: "{count} en retard"
    },
    "dash.activityTitle": {
        en: "Recent Activity",
        fr: "Activité récente"
    },
    "dash.activityEmpty": {
        en: "No activity recorded yet",
        fr: "Aucune activité enregistrée pour le moment"
    },
    "dash.system": {
        en: "System",
        fr: "Système"
    }
};
const __TURBOPACK__default__export__ = entries;
}),
"[project]/src/components/atelier/i18n/dict/notifications.ts [app-ssr] (ecmascript)", ((__turbopack_context__) => {
"use strict";

// OSSZ ATELIER — bilingual entries for the notifications module.
__turbopack_context__.s([
    "default",
    ()=>__TURBOPACK__default__export__
]);
const entries = {
    "notif.title": {
        en: "Notifications",
        fr: "Notifications"
    },
    "notif.desc": {
        en: "Production alerts for your account.",
        fr: "Alertes de production pour votre compte."
    },
    "notif.markAll": {
        en: "Mark all as read",
        fr: "Tout marquer comme lu"
    },
    "notif.marking": {
        en: "Marking…",
        fr: "Marquage…"
    },
    "notif.tabAll": {
        en: "All ({count})",
        fr: "Toutes ({count})"
    },
    "notif.tabUnread": {
        en: "Unread",
        fr: "Non lues"
    },
    "notif.tabUnreadCount": {
        en: "Unread ({count})",
        fr: "Non lues ({count})"
    },
    "notif.emptyTitle": {
        en: "You're all caught up.",
        fr: "Vous êtes à jour."
    },
    "notif.emptyUnreadDesc": {
        en: "No unread notifications — new alerts will appear here.",
        fr: "Aucune notification non lue — les nouvelles alertes s'afficheront ici."
    },
    "notif.emptyDesc": {
        en: "No notifications yet — production alerts will appear here.",
        fr: "Aucune notification pour le moment — les alertes de production s'afficheront ici."
    },
    "notif.loadError": {
        en: "Could not load your notifications",
        fr: "Impossible de charger vos notifications"
    },
    "notif.unexpectedError": {
        en: "Unexpected error.",
        fr: "Erreur inattendue."
    },
    "notif.markOneError": {
        en: "Could not mark the notification as read",
        fr: "Impossible de marquer la notification comme lue"
    },
    "notif.markAllSuccess": {
        en: "All notifications marked as read",
        fr: "Toutes les notifications ont été marquées comme lues"
    },
    "notif.markAllError": {
        en: "Could not mark notifications as read",
        fr: "Impossible de marquer les notifications comme lues"
    }
};
const __TURBOPACK__default__export__ = entries;
}),
"[project]/src/components/atelier/i18n/dict/tasks.ts [app-ssr] (ecmascript)", ((__turbopack_context__) => {
"use strict";

// OSSZ ATELIER — bilingual entries for the tasks module.
__turbopack_context__.s([
    "default",
    ()=>__TURBOPACK__default__export__
]);
const entries = {
    // --- Page header -------------------------------------------------------------
    "tasks.title": {
        en: "Production",
        fr: "Production"
    },
    "tasks.myTitle": {
        en: "My Tasks",
        fr: "Mes tâches"
    },
    "tasks.description": {
        en: "Monitor every garment production task across the atelier.",
        fr: "Suivez toutes les tâches de production de confection de l'atelier."
    },
    "tasks.myDescription": {
        en: "Your assigned garment pieces and their production status.",
        fr: "Vos pièces de confection affectées et leur état de production."
    },
    "tasks.createTasks": {
        en: "Create Tasks",
        fr: "Créer des tâches"
    },
    // --- Filter bar ----------------------------------------------------------------
    "tasks.searchPlaceholder": {
        en: "Order number, customer, piece or task code",
        fr: "Numéro de commande, client, pièce ou code de tâche"
    },
    "tasks.status": {
        en: "Status",
        fr: "Statut"
    },
    "tasks.filterByStatus": {
        en: "Filter by status",
        fr: "Filtrer par statut"
    },
    "tasks.allStatuses": {
        en: "All statuses",
        fr: "Tous les statuts"
    },
    "tasks.sortBy": {
        en: "Sort by",
        fr: "Trier par"
    },
    "tasks.sortAria": {
        en: "Sort tasks",
        fr: "Trier les tâches"
    },
    "tasks.sortUpdated": {
        en: "Recently updated",
        fr: "Mises à jour récemment"
    },
    "tasks.sortDeadline": {
        en: "Deadline",
        fr: "Échéance"
    },
    "tasks.sortProgress": {
        en: "Progress",
        fr: "Progression"
    },
    "tasks.sortCreated": {
        en: "Newest",
        fr: "Plus récentes"
    },
    "tasks.staff": {
        en: "Staff",
        fr: "Personnel"
    },
    "tasks.filterByStaff": {
        en: "Filter by staff",
        fr: "Filtrer par membre du personnel"
    },
    "tasks.allStaff": {
        en: "All staff",
        fr: "Tout le personnel"
    },
    "tasks.unassignedFilter": {
        en: "Unassigned",
        fr: "Non affectées"
    },
    "tasks.activeCount": {
        en: "{count} active",
        fr: "{count} en cours"
    },
    "tasks.priority": {
        en: "Priority",
        fr: "Priorité"
    },
    "tasks.filterByPriority": {
        en: "Filter by priority",
        fr: "Filtrer par priorité"
    },
    "tasks.allPriorities": {
        en: "All priorities",
        fr: "Toutes les priorités"
    },
    "tasks.deadlineFrom": {
        en: "Deadline from",
        fr: "Échéance du"
    },
    "tasks.deadlineTo": {
        en: "Deadline to",
        fr: "Échéance au"
    },
    "tasks.overdueOnly": {
        en: "Overdue only",
        fr: "En retard uniquement"
    },
    // --- Errors -----------------------------------------------------------------------
    "tasks.loadFailed": {
        en: "Failed to load tasks.",
        fr: "Échec du chargement des tâches."
    },
    "tasks.loadFailedTitle": {
        en: "Could not load tasks",
        fr: "Impossible de charger les tâches"
    },
    "tasks.refreshFailed": {
        en: "Could not refresh tasks",
        fr: "Impossible d'actualiser les tâches"
    },
    // --- Table ---------------------------------------------------------------------------
    "tasks.colTask": {
        en: "Task",
        fr: "Tâche"
    },
    "tasks.colOrder": {
        en: "Order",
        fr: "Commande"
    },
    "tasks.colAssignee": {
        en: "Assignee",
        fr: "Affectation"
    },
    "tasks.colStatus": {
        en: "Status",
        fr: "Statut"
    },
    "tasks.colProgress": {
        en: "Progress",
        fr: "Progression"
    },
    "tasks.colDeadline": {
        en: "Deadline",
        fr: "Échéance"
    },
    "tasks.colLastUpdate": {
        en: "Last Update",
        fr: "Dernière mise à jour"
    },
    "tasks.unassigned": {
        en: "Unassigned",
        fr: "Non affectée"
    },
    "tasks.openTaskAria": {
        en: "Open task {code} — {piece}",
        fr: "Ouvrir la tâche {code} — {piece}"
    },
    // --- Empty states -----------------------------------------------------------------------
    "tasks.emptyFilteredTitle": {
        en: "No tasks match your filters",
        fr: "Aucune tâche ne correspond à vos filtres"
    },
    "tasks.emptyTitle": {
        en: "No production tasks yet",
        fr: "Aucune tâche de production pour le moment"
    },
    "tasks.emptyStaffTitle": {
        en: "No tasks assigned to you",
        fr: "Aucune tâche ne vous est affectée"
    },
    "tasks.emptyFilteredDesc": {
        en: "Try adjusting the search or filters above.",
        fr: "Essayez de modifier la recherche ou les filtres ci-dessus."
    },
    "tasks.emptyDesc": {
        en: "Create tasks from a customer order to start production.",
        fr: "Créez des tâches à partir d'une commande client pour démarrer la production."
    },
    "tasks.emptyStaffDesc": {
        en: "Garment pieces allocated to your account will appear here.",
        fr: "Les pièces de confection affectées à votre compte apparaîtront ici."
    },
    "tasks.clearFilters": {
        en: "Clear filters",
        fr: "Réinitialiser les filtres"
    },
    // --- Pagination ----------------------------------------------------------------------------
    "tasks.totalOne": {
        en: "{count} task total",
        fr: "{count} tâche au total"
    },
    "tasks.totalMany": {
        en: "{count} tasks total",
        fr: "{count} tâches au total"
    },
    "tasks.previous": {
        en: "Previous",
        fr: "Précédent"
    },
    "tasks.next": {
        en: "Next",
        fr: "Suivant"
    },
    "tasks.pageOf": {
        en: "Page {page} of {totalPages}",
        fr: "Page {page} sur {totalPages}"
    }
};
const __TURBOPACK__default__export__ = entries;
}),
"[project]/src/components/atelier/i18n/dict/task-create.ts [app-ssr] (ecmascript)", ((__turbopack_context__) => {
"use strict";

// OSSZ ATELIER — bilingual entries for the task-create module.
__turbopack_context__.s([
    "default",
    ()=>__TURBOPACK__default__export__
]);
const entries = {
    // --- Page header & guards -----------------------------------------------------
    "tc.title": {
        en: "Create Production Tasks",
        fr: "Créer des tâches de production"
    },
    "tc.description": {
        en: "Break a customer order into individual garment-piece tasks.",
        fr: "Décomposez une commande client en tâches individuelles par pièce de confection."
    },
    "tc.descriptionFull": {
        en: "Break a customer order into individual garment-piece tasks. Each piece becomes a trackable task with its own checklist.",
        fr: "Décomposez une commande client en tâches individuelles par pièce de confection. Chaque pièce devient une tâche suivie avec sa propre liste de contrôle."
    },
    "tc.backToTasks": {
        en: "Back to tasks",
        fr: "Retour aux tâches"
    },
    "tc.notAuthorized": {
        en: "Not authorized",
        fr: "Non autorisé"
    },
    "tc.notAuthorizedDesc": {
        en: "Only Management and Admin accounts can create production tasks.",
        fr: "Seuls les comptes Direction et Admin peuvent créer des tâches de production."
    },
    // --- Order loading --------------------------------------------------------------
    "tc.loadOrdersFailed": {
        en: "Failed to load orders.",
        fr: "Échec du chargement des commandes."
    },
    "tc.loadOrdersTitle": {
        en: "Could not load orders",
        fr: "Impossible de charger les commandes"
    },
    "tc.noOrders": {
        en: "No orders yet",
        fr: "Aucune commande pour le moment"
    },
    "tc.noOrdersDesc": {
        en: "Register a customer order first — tasks are always created from an order.",
        fr: "Enregistrez d'abord une commande client — les tâches sont toujours créées à partir d'une commande."
    },
    "tc.goToOrders": {
        en: "Go to Orders",
        fr: "Aller aux commandes"
    },
    // --- Order selection ----------------------------------------------------------------
    "tc.customerOrder": {
        en: "Customer order",
        fr: "Commande client"
    },
    "tc.order": {
        en: "Order",
        fr: "Commande"
    },
    "tc.orderPlaceholder": {
        en: "Select the order to break into tasks",
        fr: "Sélectionnez la commande à décomposer en tâches"
    },
    "tc.customer": {
        en: "Customer",
        fr: "Client"
    },
    "tc.phone": {
        en: "Phone",
        fr: "Téléphone"
    },
    "tc.orderDate": {
        en: "Order date",
        fr: "Date de commande"
    },
    "tc.expectedDelivery": {
        en: "Expected delivery",
        fr: "Livraison prévue"
    },
    "tc.priority": {
        en: "Priority",
        fr: "Priorité"
    },
    "tc.pieces": {
        en: "Pieces",
        fr: "Pièces"
    },
    "tc.orderInstructions": {
        en: "Order instructions",
        fr: "Instructions de la commande"
    },
    "tc.existingTasks": {
        en: "Existing tasks on this order",
        fr: "Tâches existantes sur cette commande"
    },
    "tc.existingTasksSummary": {
        en: "{total} total ({completed} completed) — duplicate piece names are rejected.",
        fr: "{total} au total (terminées : {completed}) — les noms de pièces en double sont refusés."
    },
    "tc.existingTasksSummaryProgress": {
        en: "{total} total ({completed} completed, {progress}% average progress) — duplicate piece names are rejected.",
        fr: "{total} au total (terminées : {completed}, progression moyenne : {progress}\u00A0%) — les noms de pièces en double sont refusés."
    },
    // --- Piece rows -----------------------------------------------------------------------
    "tc.garmentPieces": {
        en: "Garment pieces",
        fr: "Pièces de confection"
    },
    "tc.addPiece": {
        en: "Add Piece",
        fr: "Ajouter une pièce"
    },
    "tc.pieceNumber": {
        en: "Piece {number}",
        fr: "Pièce {number}"
    },
    "tc.removePiece": {
        en: "Remove piece {number}",
        fr: "Supprimer la pièce {number}"
    },
    "tc.pieceName": {
        en: "Garment piece name *",
        fr: "Nom de la pièce de confection *"
    },
    "tc.pieceNamePlaceholder": {
        en: "e.g. Jacket, Matching Trousers",
        fr: "ex. Veste, Pantalon assorti"
    },
    "tc.category": {
        en: "Category",
        fr: "Catégorie"
    },
    "tc.categoryForPiece": {
        en: "Category for piece {number}",
        fr: "Catégorie de la pièce {number}"
    },
    "tc.assignedStaff": {
        en: "Assigned staff",
        fr: "Personnel affecté"
    },
    "tc.staffForPiece": {
        en: "Staff for piece {number}",
        fr: "Personnel pour la pièce {number}"
    },
    "tc.leaveUnassigned": {
        en: "Leave unassigned",
        fr: "Laisser non affectée"
    },
    "tc.activeCount": {
        en: "{count} active",
        fr: "{count} en cours"
    },
    "tc.priorityForPiece": {
        en: "Priority for piece {number}",
        fr: "Priorité de la pièce {number}"
    },
    "tc.deadline": {
        en: "Deadline",
        fr: "Échéance"
    },
    "tc.instructions": {
        en: "Instructions (optional)",
        fr: "Instructions (facultatif)"
    },
    "tc.instructionsPlaceholder": {
        en: "Piece-specific production instructions",
        fr: "Instructions de production propres à la pièce"
    },
    // --- Submit ------------------------------------------------------------------------------
    "tc.summaryOne": {
        en: "{count} piece on order {order}",
        fr: "{count} pièce sur la commande {order}"
    },
    "tc.summaryMany": {
        en: "{count} pieces on order {order}",
        fr: "{count} pièces sur la commande {order}"
    },
    "tc.createOne": {
        en: "Create {count} Task",
        fr: "Créer {count} tâche"
    },
    "tc.createMany": {
        en: "Create {count} Tasks",
        fr: "Créer {count} tâches"
    },
    // --- Validation & toasts --------------------------------------------------------------------
    "tc.selectOrderTitle": {
        en: "Select an order first",
        fr: "Sélectionnez d'abord une commande"
    },
    "tc.selectOrderDesc": {
        en: "Tasks are always created for a specific customer order.",
        fr: "Les tâches sont toujours créées pour une commande client précise."
    },
    "tc.missingNameTitle": {
        en: "Missing garment piece name",
        fr: "Nom de pièce de confection manquant"
    },
    "tc.missingNameDesc": {
        en: "Every garment piece needs a name (e.g. Jacket, Trousers).",
        fr: "Chaque pièce de confection doit porter un nom (ex. Veste, Pantalon)."
    },
    "tc.duplicateTitle": {
        en: "Duplicate garment piece",
        fr: "Pièce de confection en double"
    },
    "tc.duplicateDesc": {
        en: "\"{name}\" appears more than once in this form.",
        fr: "« {name} » apparaît plusieurs fois dans ce formulaire."
    },
    "tc.createdOne": {
        en: "{count} task created",
        fr: "{count} tâche créée"
    },
    "tc.createdMany": {
        en: "{count} tasks created",
        fr: "{count} tâches créées"
    },
    "tc.createdDesc": {
        en: "Production tasks were created for order {order}.",
        fr: "Les tâches de production ont été créées pour la commande {order}."
    },
    "tc.createFailed": {
        en: "Could not create tasks",
        fr: "Impossible de créer les tâches"
    },
    "tc.unexpectedError": {
        en: "Unexpected error.",
        fr: "Erreur inattendue."
    }
};
const __TURBOPACK__default__export__ = entries;
}),
"[project]/src/components/atelier/i18n/dict/task-detail.ts [app-ssr] (ecmascript)", ((__turbopack_context__) => {
"use strict";

// OSSZ ATELIER — bilingual entries for the task-detail module.
__turbopack_context__.s([
    "default",
    ()=>__TURBOPACK__default__export__
]);
const entries = {
    // --- Header, guards & generic feedback ---------------------------------------
    "td.backToTasks": {
        en: "Back to tasks",
        fr: "Retour aux tâches"
    },
    "td.goToTasks": {
        en: "Go to tasks",
        fr: "Aller aux tâches"
    },
    "td.loadFailed": {
        en: "Failed to load task.",
        fr: "Échec du chargement de la tâche."
    },
    "td.unknownError": {
        en: "Unknown error.",
        fr: "Erreur inconnue."
    },
    "td.unexpectedError": {
        en: "Unexpected error.",
        fr: "Erreur inattendue."
    },
    "td.actionFailed": {
        en: "Action failed",
        fr: "Échec de l'action"
    },
    "td.noTask.title": {
        en: "No task selected",
        fr: "Aucune tâche sélectionnée"
    },
    "td.noTask.desc": {
        en: "Open a task from the production board to see its details.",
        fr: "Ouvrez une tâche depuis le tableau de production pour consulter ses détails."
    },
    "td.taskUnavailable": {
        en: "Task unavailable",
        fr: "Tâche indisponible"
    },
    "td.couldNotLoad": {
        en: "Could not load task",
        fr: "Impossible de charger la tâche"
    },
    // --- Header --------------------------------------------------------------------
    "td.assigneeColon": {
        en: "Assignee:",
        fr: "Affectée à :"
    },
    "td.unassigned": {
        en: "Unassigned",
        fr: "Non affectée"
    },
    "td.createdOn": {
        en: "Created {date}",
        fr: "Créée le {date}"
    },
    "td.blockedAlert": {
        en: "Task blocked",
        fr: "Tâche bloquée"
    },
    "td.cancelledAlert": {
        en: "Task cancelled",
        fr: "Tâche annulée"
    },
    "td.cancelledFallback": {
        en: "This task was cancelled.",
        fr: "Cette tâche a été annulée."
    },
    "td.correctionsAlert": {
        en: "Corrections requested",
        fr: "Retouches demandées"
    },
    "td.correctionsAlertDesc": {
        en: "Corrections were requested — see inspection history below for details.",
        fr: "Des retouches ont été demandées — consultez l'historique des inspections ci-dessous pour plus de détails."
    },
    // --- Progress & checklist ---------------------------------------------------------
    "td.progressChecklist": {
        en: "Progress & Checklist",
        fr: "Progression & Checklist"
    },
    "td.currentProgress": {
        en: "Current progress:",
        fr: "Progression actuelle :"
    },
    "td.updateProgressManually": {
        en: "Update progress manually",
        fr: "Mettre à jour la progression manuellement"
    },
    "td.aria.newProgress": {
        en: "New progress percentage",
        fr: "Nouveau pourcentage de progression"
    },
    "td.optionalNote": {
        en: "Optional note",
        fr: "Note facultative"
    },
    "td.aria.progressNote": {
        en: "Progress note",
        fr: "Note de progression"
    },
    "td.update": {
        en: "Update",
        fr: "Mettre à jour"
    },
    "td.manualProgressNote": {
        en: 'Supervisor correction — manual values are recorded in the history with source "{source}".',
        fr: "Correction de la direction — les valeurs saisies manuellement sont enregistrées dans l'historique avec la source « {source} »."
    },
    "td.source.MANUAL": {
        en: "Manual",
        fr: "Manuel"
    },
    "td.source.STAGE": {
        en: "Stage",
        fr: "Étape"
    },
    "td.source.INSPECTION": {
        en: "Inspection",
        fr: "Inspection"
    },
    "td.checklistTitle": {
        en: "Production checklist",
        fr: "Checklist de production"
    },
    "td.pendingChange": {
        en: "1 pending change",
        fr: "1 modification en attente"
    },
    "td.pendingChanges": {
        en: "{count} pending changes",
        fr: "{count} modifications en attente"
    },
    "td.checklistHelp": {
        en: "Progress is recalculated from completed stages (in-progress counts half{weights}).",
        fr: "La progression est recalculée à partir des étapes terminées (une étape en cours compte pour moitié{weights})."
    },
    "td.checklistHelpWeights": {
        en: "; heavier stages count proportionally more",
        fr: " ; les étapes plus lourdes comptent proportionnellement plus"
    },
    "td.noStages.title": {
        en: "No checklist stages",
        fr: "Aucune étape de checklist"
    },
    "td.noStages.desc": {
        en: "No production stages apply to this task category.",
        fr: "Aucune étape de production ne s'applique à la catégorie de cette tâche."
    },
    "td.stageWeight": {
        en: "weight ×{weight}",
        fr: "poids ×{weight}"
    },
    "td.wasState": {
        en: "was {state}",
        fr: "état précédent : {state}"
    },
    "td.aria.stateFor": {
        en: "State for {stage}",
        fr: "État de {stage}"
    },
    "td.aria.setState": {
        en: "Set {stage} to {state}",
        fr: "Définir {stage} sur {state}"
    },
    "td.stageCommentPlaceholder": {
        en: "Optional comment attached to the next checklist save",
        fr: "Commentaire facultatif joint au prochain enregistrement de la checklist"
    },
    "td.aria.stageComment": {
        en: "Checklist save comment",
        fr: "Commentaire d'enregistrement de la checklist"
    },
    "td.saveChecklist": {
        en: "Save Checklist",
        fr: "Enregistrer la checklist"
    },
    // --- Workflow actions ---------------------------------------------------------------
    "td.workflowActions": {
        en: "Workflow Actions",
        fr: "Actions de workflow"
    },
    "td.submitForInspection": {
        en: "Submit for Inspection",
        fr: "Soumettre au contrôle qualité"
    },
    "td.reach100": {
        en: "Reach 100% progress first.",
        fr: "Atteignez d'abord 100 % de progression."
    },
    "td.inspectResult": {
        en: "Inspect Result",
        fr: "Contrôler le résultat"
    },
    "td.markReady": {
        en: "Mark Ready for Pickup",
        fr: "Marquer prête pour retrait"
    },
    "td.markDelivered": {
        en: "Mark Delivered/Collected",
        fr: "Marquer livrée/récupérée"
    },
    "td.assignStaff": {
        en: "Assign Staff",
        fr: "Affecter du personnel"
    },
    "td.reassignStaff": {
        en: "Reassign Staff",
        fr: "Réaffecter du personnel"
    },
    "td.editDetails": {
        en: "Edit Details",
        fr: "Modifier les détails"
    },
    "td.blockTask": {
        en: "Block Task",
        fr: "Bloquer la tâche"
    },
    "td.unblockTask": {
        en: "Unblock Task",
        fr: "Débloquer la tâche"
    },
    "td.reopenTask": {
        en: "Reopen Task",
        fr: "Rouvrir la tâche"
    },
    "td.cancelTask": {
        en: "Cancel Task",
        fr: "Annuler la tâche"
    },
    "td.noActions": {
        en: "No actions available for this task",
        fr: "Aucune action disponible pour cette tâche"
    },
    "td.noActionsClosed": {
        en: " — it is closed.",
        fr: " — elle est clôturée."
    },
    // --- Comments & issues ------------------------------------------------------------------
    "td.commentsIssues": {
        en: "Comments & Issues",
        fr: "Commentaires & Problèmes"
    },
    "td.commentsTotal": {
        en: "{count} total",
        fr: "{count} au total"
    },
    "td.noComments.title": {
        en: "No notes yet",
        fr: "Aucune note pour le moment"
    },
    "td.noComments.desc": {
        en: "Production notes and reported issues will appear here.",
        fr: "Les notes de production et les problèmes signalés apparaîtront ici."
    },
    "td.commentPlaceholder": {
        en: "Write a production note or describe a problem...",
        fr: "Rédigez une note de production ou décrivez un problème…"
    },
    "td.aria.newComment": {
        en: "New comment",
        fr: "Nouveau commentaire"
    },
    "td.addNote": {
        en: "Add Note",
        fr: "Ajouter une note"
    },
    "td.reportIssue": {
        en: "Report Issue",
        fr: "Signaler un problème"
    },
    // --- Task details card ---------------------------------------------------------------------
    "td.taskDetails": {
        en: "Task Details",
        fr: "Détails de la tâche"
    },
    "td.meta.order": {
        en: "Order",
        fr: "Commande"
    },
    "td.meta.piece": {
        en: "Garment piece",
        fr: "Pièce de vêtement"
    },
    "td.meta.assignee": {
        en: "Assignee",
        fr: "Affectée à"
    },
    "td.meta.category": {
        en: "Category",
        fr: "Catégorie"
    },
    "td.meta.priority": {
        en: "Priority",
        fr: "Priorité"
    },
    "td.meta.deadline": {
        en: "Deadline",
        fr: "Échéance"
    },
    "td.meta.created": {
        en: "Created",
        fr: "Créée le"
    },
    "td.meta.completed": {
        en: "Completed",
        fr: "Terminée le"
    },
    "td.meta.delivered": {
        en: "Delivered",
        fr: "Livrée le"
    },
    "td.instructions": {
        en: "Instructions",
        fr: "Instructions"
    },
    // --- History timeline ------------------------------------------------------------------------
    "td.history": {
        en: "History",
        fr: "Historique"
    },
    "td.eventsCount": {
        en: "{count} events",
        fr: "{count} événements"
    },
    "td.tab.progress": {
        en: "Progress",
        fr: "Progression"
    },
    "td.tab.checklist": {
        en: "Checklist",
        fr: "Checklist"
    },
    "td.tab.status": {
        en: "Status",
        fr: "Statut"
    },
    "td.tab.inspections": {
        en: "Inspections",
        fr: "Inspections"
    },
    "td.noEvents": {
        en: "No events recorded",
        fr: "Aucun événement enregistré"
    },
    "td.hist.assigned": {
        en: "assigned the task to {name}",
        fr: "a affecté la tâche à {name}"
    },
    "td.hist.reassigned": {
        en: "reassigned the task to {name}",
        fr: "a réaffecté la tâche à {name}"
    },
    "td.hist.statusChange": {
        en: "changed status from {from} to {to}",
        fr: "a changé le statut de {from} à {to}"
    },
    "td.hist.stageChange": {
        en: "{stage}: {from} → {to}",
        fr: "{stage} : {from} → {to}"
    },
    "td.hist.progress": {
        en: "updated progress {from}% → {to}%",
        fr: "a mis à jour la progression : {from} % → {to} %"
    },
    "td.hist.inspection": {
        en: "inspection decision — {decision}",
        fr: "décision d'inspection — {decision}"
    },
    "td.hist.issue": {
        en: "reported an issue",
        fr: "a signalé un problème"
    },
    "td.hist.note": {
        en: "added a note",
        fr: "a ajouté une note"
    },
    // --- Validation & toasts -----------------------------------------------------------------------
    "td.toast.selectStaff": {
        en: "Select a staff member",
        fr: "Sélectionnez un membre du personnel"
    },
    "td.toast.selectStaffDesc": {
        en: "Choose the account responsible for this garment piece.",
        fr: "Choisissez le compte responsable de cette pièce."
    },
    "td.toast.reasonRequired": {
        en: "A reason is required",
        fr: "Un motif est obligatoire"
    },
    "td.toast.reasonRequiredDesc": {
        en: "Provide {field} (at least {min} characters).",
        fr: "Indiquez {field} (au moins {min} caractères)."
    },
    "td.reason.block": {
        en: "the reason production is blocked",
        fr: "le motif du blocage de la production"
    },
    "td.reason.cancel": {
        en: "the cancellation reason",
        fr: "le motif de l'annulation"
    },
    "td.toast.assignedTo": {
        en: "Assigned to {name}",
        fr: "Affectée à {name}"
    },
    "td.toast.reassignedTo": {
        en: "Reassigned to {name}",
        fr: "Réaffectée à {name}"
    },
    "td.staffMember": {
        en: "staff member",
        fr: "un membre du personnel"
    },
    "td.toast.pieceRequired": {
        en: "Garment piece name is required",
        fr: "Le nom de la pièce est obligatoire"
    },
    "td.toast.nothingToUpdate": {
        en: "Nothing to update",
        fr: "Rien à mettre à jour"
    },
    "td.toast.nothingToUpdateDesc": {
        en: "No field was changed.",
        fr: "Aucun champ n'a été modifié."
    },
    "td.toast.detailsUpdated": {
        en: "Task details updated",
        fr: "Détails de la tâche mis à jour"
    },
    "td.toast.submitted": {
        en: "Submitted for inspection",
        fr: "Soumise au contrôle qualité"
    },
    "td.toast.inspectionApproved": {
        en: "Inspection approved",
        fr: "Inspection approuvée"
    },
    "td.toast.correctionsRequired": {
        en: "Correction instructions required",
        fr: "Instructions de retouche obligatoires"
    },
    "td.toast.correctionsRequiredDesc": {
        en: "Describe what needs to be corrected (at least 3 characters).",
        fr: "Décrivez les corrections à apporter (au moins 3 caractères)."
    },
    "td.toast.correctionsRequested": {
        en: "Corrections requested",
        fr: "Retouches demandées"
    },
    "td.toast.ready": {
        en: "Marked ready for pickup",
        fr: "Marquée prête pour retrait"
    },
    "td.toast.delivered": {
        en: "Marked as delivered/collected",
        fr: "Marquée comme livrée/récupérée"
    },
    "td.toast.reopenCommentsRequired": {
        en: "Comments are required",
        fr: "Des commentaires sont obligatoires"
    },
    "td.toast.reopenCommentsRequiredDesc": {
        en: "Explain why this task is being reopened (at least 3 characters).",
        fr: "Expliquez pourquoi cette tâche est rouverte (au moins 3 caractères)."
    },
    "td.toast.reopened": {
        en: "Task reopened",
        fr: "Tâche rouverte"
    },
    "td.toast.blocked": {
        en: "Task blocked",
        fr: "Tâche bloquée"
    },
    "td.toast.unblocked": {
        en: "Task unblocked",
        fr: "Tâche débloquée"
    },
    "td.toast.cancelled": {
        en: "Task cancelled",
        fr: "Tâche annulée"
    },
    "td.toast.progressUpdated": {
        en: "Progress updated to {value}%",
        fr: "Progression mise à jour à {value} %"
    },
    "td.toast.progressFailed": {
        en: "Could not update progress",
        fr: "Impossible de mettre à jour la progression"
    },
    "td.toast.checklistUpdated": {
        en: "Checklist updated",
        fr: "Checklist mise à jour"
    },
    "td.toast.checklistUpdatedDesc": {
        en: "Progress was recalculated from the completed stages.",
        fr: "La progression a été recalculée à partir des étapes terminées."
    },
    "td.toast.checklistFailed": {
        en: "Could not save checklist",
        fr: "Impossible d'enregistrer la checklist"
    },
    "td.toast.emptyComment": {
        en: "Write a message first",
        fr: "Rédigez d'abord un message"
    },
    "td.toast.emptyCommentIssue": {
        en: "Describe the problem you encountered.",
        fr: "Décrivez le problème rencontré."
    },
    "td.toast.emptyCommentNote": {
        en: "Type a note before sending.",
        fr: "Saisissez une note avant d'envoyer."
    },
    "td.toast.issueReported": {
        en: "Issue reported",
        fr: "Problème signalé"
    },
    "td.toast.issueReportedDesc": {
        en: "Management has been notified.",
        fr: "La direction a été notifiée."
    },
    "td.toast.noteAdded": {
        en: "Note added",
        fr: "Note ajoutée"
    },
    "td.toast.commentFailed": {
        en: "Could not add comment",
        fr: "Impossible d'ajouter le commentaire"
    },
    // --- Dialogs -----------------------------------------------------------------------------------------
    "td.dialog.assignTitle": {
        en: "Assign Task",
        fr: "Affecter la tâche"
    },
    "td.dialog.reassignTitle": {
        en: "Reassign Task",
        fr: "Réaffecter la tâche"
    },
    "td.dialog.assignDesc": {
        en: "Select the staff member responsible for {piece} ({code}).",
        fr: "Sélectionnez le membre du personnel responsable de {piece} ({code})."
    },
    "td.dialog.staffMember": {
        en: "Staff member",
        fr: "Membre du personnel"
    },
    "td.dialog.selectStaff": {
        en: "Select staff member",
        fr: "Sélectionner un membre du personnel"
    },
    "td.dialog.noStaff": {
        en: "No staff accounts available",
        fr: "Aucun compte de personnel disponible"
    },
    "td.dialog.activeTasks": {
        en: "({count} active)",
        fr: "({count} en cours)"
    },
    "td.dialog.currentAssignee": {
        en: " — current",
        fr: " — actuellement affecté"
    },
    "td.dialog.noteOptional": {
        en: "Note (optional)",
        fr: "Note (facultative)"
    },
    "td.dialog.assignNotePlaceholder": {
        en: "Note sent with the assignment notification",
        fr: "Note envoyée avec la notification d'affectation"
    },
    "td.dialog.assignAction": {
        en: "Assign",
        fr: "Affecter"
    },
    "td.dialog.reassignAction": {
        en: "Reassign",
        fr: "Réaffecter"
    },
    "td.dialog.editTitle": {
        en: "Edit Task Details",
        fr: "Modifier les détails de la tâche"
    },
    "td.dialog.editDesc": {
        en: "Only the fields you change are submitted.",
        fr: "Seuls les champs modifiés seront transmis."
    },
    "td.dialog.instructionsPlaceholder": {
        en: "Production instructions for this piece",
        fr: "Instructions de production pour cette pièce"
    },
    "td.dialog.submitDescSupervisor": {
        en: "The task moves to Awaiting Inspection for quality control.",
        fr: "La tâche passe en attente d'inspection pour le contrôle qualité."
    },
    "td.dialog.submitDescStaff": {
        en: "Your work will be reviewed by quality control. Make sure the checklist is complete.",
        fr: "Votre travail sera examiné par le contrôle qualité. Assurez-vous que la checklist est complète."
    },
    "td.dialog.submitNotePlaceholder": {
        en: "Anything the inspector should know",
        fr: "Tout ce que le contrôleur doit savoir"
    },
    "td.dialog.submitAction": {
        en: "Submit",
        fr: "Soumettre"
    },
    "td.dialog.inspectTitle": {
        en: "Quality Inspection",
        fr: "Contrôle qualité"
    },
    "td.dialog.inspectDesc": {
        en: "Approve passes the garment to the pickup flow. Request changes returns it to the worker with correction instructions.",
        fr: "Approuver envoie le vêtement au flux de retrait. Demander des retouches le renvoie à l'ouvrier avec des instructions de correction."
    },
    "td.dialog.inspectionComments": {
        en: "Inspection comments",
        fr: "Commentaires d'inspection"
    },
    "td.dialog.inspectPlaceholder": {
        en: "Required when requesting changes — describe the corrections needed",
        fr: "Obligatoire pour demander des retouches — décrivez les corrections nécessaires"
    },
    "td.dialog.requestChanges": {
        en: "Request Changes",
        fr: "Demander des retouches"
    },
    "td.dialog.approve": {
        en: "Approve",
        fr: "Approuver"
    },
    "td.dialog.deliverDesc": {
        en: "Confirm that {customer} collected {piece}.",
        fr: "Confirmez que {customer} a récupéré {piece}."
    },
    "td.dialog.deliverNotePlaceholder": {
        en: "e.g. Collected by the customer in person",
        fr: "ex. Récupérée par le client en personne"
    },
    "td.dialog.markDeliveredAction": {
        en: "Mark Delivered",
        fr: "Marquer comme livrée"
    },
    "td.dialog.reopenDesc": {
        en: "The task returns to Ongoing so production can resume. Comments are required.",
        fr: "La tâche repasse en cours afin que la production puisse reprendre. Des commentaires sont obligatoires."
    },
    "td.dialog.comments": {
        en: "Comments",
        fr: "Commentaires"
    },
    "td.dialog.reopenPlaceholder": {
        en: "Why is this task being reopened?",
        fr: "Pourquoi cette tâche est-elle rouverte ?"
    },
    "td.dialog.reopenAction": {
        en: "Reopen",
        fr: "Rouvrir"
    },
    "td.dialog.blockDesc": {
        en: "Report what is blocking production. Management is notified immediately.",
        fr: "Indiquez ce qui bloque la production. La direction est notifiée immédiatement."
    },
    "td.dialog.reasonRequired": {
        en: "Reason (required)",
        fr: "Motif (obligatoire)"
    },
    "td.dialog.blockPlaceholder": {
        en: "e.g. Missing customer-provided fabric",
        fr: "ex. Le tissu fourni par le client est manquant"
    },
    "td.dialog.unblockDesc": {
        en: "The blocker is cleared and production can resume.",
        fr: "Le blocage est levé et la production peut reprendre."
    },
    "td.dialog.unblockPlaceholder": {
        en: "How the blocker was resolved",
        fr: "Comment le blocage a été résolu"
    },
    "td.dialog.unblockAction": {
        en: "Unblock",
        fr: "Débloquer"
    },
    "td.dialog.cancelTitle": {
        en: "Cancel this task?",
        fr: "Annuler cette tâche ?"
    },
    "td.dialog.cancelDesc": {
        en: "{code} — {piece} ({order}) will be permanently closed. This cannot be undone.",
        fr: "{code} — {piece} ({order}) sera définitivement clôturée. Cette action est irréversible."
    },
    "td.dialog.cancelPlaceholder": {
        en: "e.g. Cancelled by the customer",
        fr: "ex. Annulée par le client"
    },
    "td.dialog.keepTask": {
        en: "Keep Task",
        fr: "Conserver la tâche"
    }
};
const __TURBOPACK__default__export__ = entries;
}),
"[project]/src/components/atelier/i18n/dict/orders.ts [app-ssr] (ecmascript)", ((__turbopack_context__) => {
"use strict";

// OSSZ ATELIER — bilingual entries for the orders module.
__turbopack_context__.s([
    "default",
    ()=>__TURBOPACK__default__export__
]);
const entries = {
    // --- Page header & actions ----------------------------------------------
    "orders.title": {
        en: "Orders",
        fr: "Commandes"
    },
    "orders.subtitle": {
        en: "Manually registered production orders and their progress.",
        fr: "Commandes de production enregistrées manuellement et leur avancement."
    },
    "orders.register": {
        en: "Register Order",
        fr: "Enregistrer une commande"
    },
    // --- Search & filters -------------------------------------------------------
    "orders.searchPlaceholder": {
        en: "Search order number, customer, description…",
        fr: "Rechercher par numéro de commande, client, description…"
    },
    "orders.searchLabel": {
        en: "Search orders",
        fr: "Rechercher des commandes"
    },
    "orders.filterPriorityLabel": {
        en: "Filter by priority",
        fr: "Filtrer par priorité"
    },
    "orders.priorityPlaceholder": {
        en: "Priority",
        fr: "Priorité"
    },
    "orders.allPriorities": {
        en: "All priorities",
        fr: "Toutes les priorités"
    },
    "orders.fromDateLabel": {
        en: "From date",
        fr: "Date de début"
    },
    "orders.toLabel": {
        en: "to",
        fr: "à"
    },
    "orders.toDateLabel": {
        en: "To date",
        fr: "Date de fin"
    },
    "orders.clear": {
        en: "Clear",
        fr: "Effacer"
    },
    // --- Load errors & empty states ------------------------------------------------
    "orders.loadFail": {
        en: "Failed to load orders",
        fr: "Échec du chargement des commandes"
    },
    "orders.loadFailedTitle": {
        en: "Could not load orders",
        fr: "Impossible de charger les commandes"
    },
    "orders.loadOrderFailed": {
        en: "Could not load order",
        fr: "Impossible de charger la commande"
    },
    "orders.emptyFiltersTitle": {
        en: "No orders match your filters",
        fr: "Aucune commande ne correspond à vos filtres"
    },
    "orders.emptyTitle": {
        en: "No orders registered yet",
        fr: "Aucune commande enregistrée pour le moment"
    },
    "orders.emptyFiltersDesc": {
        en: "Adjust the search or date filters to see more results.",
        fr: "Ajustez la recherche ou les filtres de date pour voir plus de résultats."
    },
    "orders.emptyDesc": {
        en: "Register a customer order to start planning production.",
        fr: "Enregistrez une commande client pour commencer à planifier la production."
    },
    // --- Table headers ---------------------------------------------------------------
    "orders.colOrder": {
        en: "Order",
        fr: "Commande"
    },
    "orders.colCustomer": {
        en: "Customer",
        fr: "Client"
    },
    "orders.colGarment": {
        en: "Garment",
        fr: "Vêtement"
    },
    "orders.colPieces": {
        en: "Pieces",
        fr: "Pièces"
    },
    "orders.colPriority": {
        en: "Priority",
        fr: "Priorité"
    },
    "orders.colDelivery": {
        en: "Expected Delivery",
        fr: "Livraison prévue"
    },
    "orders.colProduction": {
        en: "Production",
        fr: "Production"
    },
    "orders.colRegistered": {
        en: "Registered",
        fr: "Enregistrée le"
    },
    // --- Progress cells -----------------------------------------------------------------
    "orders.noTasks": {
        en: "No tasks yet",
        fr: "Aucune tâche pour le moment"
    },
    "orders.tasksDone": {
        en: "{done}/{total} task done",
        fr: "{done}/{total} tâche terminée"
    },
    "orders.tasksDonePlural": {
        en: "{done}/{total} tasks done",
        fr: "{done}/{total} tâches terminées"
    },
    // --- Order detail dialog ----------------------------------------------------------------
    "orders.detailTitle": {
        en: "Order details",
        fr: "Détails de la commande"
    },
    "orders.detailMeta": {
        en: "Registered {date} · {name}",
        fr: "Enregistrée le {date} · {name}"
    },
    "orders.statOrderDate": {
        en: "Order date",
        fr: "Date de commande"
    },
    "orders.statDelivery": {
        en: "Expected delivery",
        fr: "Livraison prévue"
    },
    "orders.statPieces": {
        en: "Pieces",
        fr: "Pièces"
    },
    "orders.statCategory": {
        en: "Category",
        fr: "Catégorie"
    },
    "orders.descriptionLabel": {
        en: "Description",
        fr: "Description"
    },
    "orders.instructionsLabel": {
        en: "Instructions",
        fr: "Instructions"
    },
    "orders.notesLabel": {
        en: "Notes",
        fr: "Notes"
    },
    "orders.productionTasks": {
        en: "Production tasks",
        fr: "Tâches de production"
    },
    "orders.summaryDone": {
        en: "{done}/{total} done · avg {progress}%",
        fr: "{done}/{total} terminée · moy. {progress} %"
    },
    "orders.summaryDonePlural": {
        en: "{done}/{total} done · avg {progress}%",
        fr: "{done}/{total} terminées · moy. {progress} %"
    },
    "orders.noTasksTitle": {
        en: "Not broken into tasks yet",
        fr: "Pas encore découpée en tâches"
    },
    "orders.noTasksDesc": {
        en: "Split this order into garment pieces to start production.",
        fr: "Découpez cette commande en pièces de vêtement pour démarrer la production."
    },
    "orders.unassigned": {
        en: "Unassigned",
        fr: "Non affectée"
    },
    "orders.breakIntoTasks": {
        en: "Break into Tasks",
        fr: "Découper en tâches"
    },
    // --- Register / edit dialog ------------------------------------------------------------------
    "orders.editTitle": {
        en: "Edit Order {number}",
        fr: "Modifier la commande {number}"
    },
    "orders.editDesc": {
        en: "Update order details. The linked customer cannot be changed.",
        fr: "Mettez à jour les détails de la commande. Le client associé ne peut pas être modifié."
    },
    "orders.registerDesc": {
        en: "Enter the business order reference and garment details.",
        fr: "Saisissez la référence de la commande et les détails du vêtement."
    },
    "orders.customerLabel": {
        en: "Customer",
        fr: "Client"
    },
    "orders.existingCustomer": {
        en: "Existing customer",
        fr: "Client existant"
    },
    "orders.newCustomer": {
        en: "New customer",
        fr: "Nouveau client"
    },
    "orders.selectCustomer": {
        en: "Select customer *",
        fr: "Sélectionner un client *"
    },
    "orders.selectCustomerPlaceholder": {
        en: "Select a customer…",
        fr: "Sélectionner un client…"
    },
    "orders.loadingCustomers": {
        en: "Loading customers…",
        fr: "Chargement des clients…"
    },
    "orders.newCustomerDetails": {
        en: "New customer details",
        fr: "Détails du nouveau client"
    },
    "orders.ncName": {
        en: "Name *",
        fr: "Nom *"
    },
    "orders.ncPhone": {
        en: "Phone *",
        fr: "Téléphone *"
    },
    "orders.ncEmail": {
        en: "Email",
        fr: "E-mail"
    },
    "orders.ncAddress": {
        en: "Address",
        fr: "Adresse"
    },
    "orders.ncNotes": {
        en: "Customer notes",
        fr: "Notes client"
    },
    "orders.fieldOrderNumber": {
        en: "Order number *",
        fr: "Numéro de commande *"
    },
    "orders.numberPlaceholder": {
        en: "e.g. OSSZ-2026-042",
        fr: "ex. OSSZ-2026-042"
    },
    "orders.fieldPriority": {
        en: "Priority",
        fr: "Priorité"
    },
    "orders.fieldOrderDate": {
        en: "Order date *",
        fr: "Date de commande *"
    },
    "orders.fieldDelivery": {
        en: "Expected delivery",
        fr: "Livraison prévue"
    },
    "orders.fieldDescription": {
        en: "Description *",
        fr: "Description *"
    },
    "orders.descPlaceholder": {
        en: "e.g. Bridal gown with hand embroidery",
        fr: "ex. Robe de mariée avec broderie à la main"
    },
    "orders.fieldCategory": {
        en: "Garment category *",
        fr: "Catégorie de vêtement *"
    },
    "orders.categoryPlaceholder": {
        en: "e.g. Suiting, Evening wear",
        fr: "ex. Costumes, tenues de soirée"
    },
    "orders.fieldPieces": {
        en: "Number of pieces *",
        fr: "Nombre de pièces *"
    },
    "orders.fieldInstructions": {
        en: "Production instructions",
        fr: "Instructions de production"
    },
    "orders.instructionsPlaceholder": {
        en: "Fabrics, colours, special handling…",
        fr: "Tissus, couleurs, précautions particulières…"
    },
    "orders.fieldNotes": {
        en: "Notes",
        fr: "Notes"
    },
    "orders.notesPlaceholder": {
        en: "Payment terms, follow-ups…",
        fr: "Conditions de paiement, relances…"
    },
    // --- Toasts & validation ----------------------------------------------------------------------
    "orders.updatedToast": {
        en: "Order updated",
        fr: "Commande modifiée"
    },
    "orders.updatedToastDesc": {
        en: "{number} was saved.",
        fr: "{number} a été enregistrée."
    },
    "orders.registeredToast": {
        en: "Order registered",
        fr: "Commande enregistrée"
    },
    "orders.registeredToastDesc": {
        en: "{number} was created.",
        fr: "{number} a été créée."
    },
    "orders.updateFailed": {
        en: "Could not update order",
        fr: "Impossible de modifier la commande"
    },
    "orders.registerFailed": {
        en: "Could not register order",
        fr: "Impossible d'enregistrer la commande"
    },
    "orders.errOrderNumber": {
        en: "Order number is required.",
        fr: "Le numéro de commande est obligatoire."
    },
    "orders.errOrderDate": {
        en: "Order date is required.",
        fr: "La date de commande est obligatoire."
    },
    "orders.errDescription": {
        en: "Description is required.",
        fr: "La description est obligatoire."
    },
    "orders.errCategory": {
        en: "Garment category is required.",
        fr: "La catégorie de vêtement est obligatoire."
    },
    "orders.errPieces": {
        en: "Number of pieces must be between 1 and 200.",
        fr: "Le nombre de pièces doit être compris entre 1 et 200."
    },
    "orders.errCustomerSelect": {
        en: "Select an existing customer or choose “New customer”.",
        fr: "Sélectionnez un client existant ou choisissez « Nouveau client »."
    },
    "orders.errNewCustomer": {
        en: "New customer name and phone are required.",
        fr: "Le nom et le téléphone du nouveau client sont obligatoires."
    }
};
const __TURBOPACK__default__export__ = entries;
}),
"[project]/src/components/atelier/i18n/dict/customers.ts [app-ssr] (ecmascript)", ((__turbopack_context__) => {
"use strict";

// OSSZ ATELIER — bilingual entries for the customers module.
__turbopack_context__.s([
    "default",
    ()=>__TURBOPACK__default__export__
]);
const entries = {
    // --- Page header & actions ----------------------------------------------
    "cust.title": {
        en: "Customers",
        fr: "Clients"
    },
    "cust.subtitle": {
        en: "Directory of registered customers with their order history.",
        fr: "Répertoire des clients enregistrés avec leur historique de commandes."
    },
    "cust.register": {
        en: "Register Customer",
        fr: "Enregistrer un client"
    },
    // --- Search ------------------------------------------------------------------
    "cust.searchPlaceholder": {
        en: "Search by name, phone, email or code…",
        fr: "Rechercher par nom, téléphone, e-mail ou code…"
    },
    "cust.searchLabel": {
        en: "Search customers",
        fr: "Rechercher des clients"
    },
    // --- Load errors & empty states ---------------------------------------------------
    "cust.loadFail": {
        en: "Failed to load customers",
        fr: "Échec du chargement des clients"
    },
    "cust.loadFailedTitle": {
        en: "Could not load customers",
        fr: "Impossible de charger les clients"
    },
    "cust.emptySearchTitle": {
        en: "No customers match your search",
        fr: "Aucun client ne correspond à votre recherche"
    },
    "cust.emptyTitle": {
        en: "No customers registered yet",
        fr: "Aucun client enregistré pour le moment"
    },
    "cust.emptySearchDesc": {
        en: "Try a different name, phone number or customer code.",
        fr: "Essayez un autre nom, numéro de téléphone ou code client."
    },
    "cust.emptyDesc": {
        en: "Register your first customer to start taking orders.",
        fr: "Enregistrez votre premier client pour commencer à prendre des commandes."
    },
    // --- Table headers --------------------------------------------------------------------
    "cust.colCode": {
        en: "Code",
        fr: "Code"
    },
    "cust.colName": {
        en: "Name",
        fr: "Nom"
    },
    "cust.colPhone": {
        en: "Phone",
        fr: "Téléphone"
    },
    "cust.colEmail": {
        en: "Email",
        fr: "E-mail"
    },
    "cust.colAddress": {
        en: "Address",
        fr: "Adresse"
    },
    "cust.colOrders": {
        en: "Orders",
        fr: "Commandes"
    },
    "cust.colRegistered": {
        en: "Registered",
        fr: "Enregistré le"
    },
    "cust.editAria": {
        en: "Edit {name}",
        fr: "Modifier {name}"
    },
    // --- Register / edit dialog ---------------------------------------------------------------
    "cust.editTitle": {
        en: "Edit Customer",
        fr: "Modifier le client"
    },
    "cust.editDesc": {
        en: "Update the details of {name} ({code}).",
        fr: "Mettez à jour les informations de {name} ({code})."
    },
    "cust.registerDesc": {
        en: "A unique customer code (CUST-XXXX) is generated automatically.",
        fr: "Un code client unique (CUST-XXXX) est généré automatiquement."
    },
    "cust.fieldName": {
        en: "Name *",
        fr: "Nom *"
    },
    "cust.namePlaceholder": {
        en: "e.g. Amina Cissé",
        fr: "ex. Amina Cissé"
    },
    "cust.fieldPhone": {
        en: "Phone *",
        fr: "Téléphone *"
    },
    "cust.fieldEmail": {
        en: "Email",
        fr: "E-mail"
    },
    "cust.fieldAddress": {
        en: "Address",
        fr: "Adresse"
    },
    "cust.addressPlaceholder": {
        en: "Neighbourhood, city",
        fr: "Quartier, ville"
    },
    "cust.fieldNotes": {
        en: "Notes",
        fr: "Notes"
    },
    "cust.notesPlaceholder": {
        en: "Preferences, measurements remarks, payment history…",
        fr: "Préférences, remarques sur les mesures, historique de paiement…"
    },
    // --- Toasts & validation ----------------------------------------------------------------------
    "cust.namePhoneRequired": {
        en: "Customer name and phone number are required.",
        fr: "Le nom et le numéro de téléphone du client sont obligatoires."
    },
    "cust.updatedToast": {
        en: "Customer updated",
        fr: "Client modifié"
    },
    "cust.updatedToastDesc": {
        en: "{name} was saved.",
        fr: "{name} a été enregistré."
    },
    "cust.registeredToast": {
        en: "Customer registered",
        fr: "Client enregistré"
    },
    "cust.registeredToastDesc": {
        en: "{name} received code {code}.",
        fr: "{name} a reçu le code {code}."
    },
    "cust.updateFailed": {
        en: "Could not update customer",
        fr: "Impossible de modifier le client"
    },
    "cust.registerFailed": {
        en: "Could not register customer",
        fr: "Impossible d'enregistrer le client"
    }
};
const __TURBOPACK__default__export__ = entries;
}),
"[project]/src/components/atelier/i18n/dict/performance.ts [app-ssr] (ecmascript)", ((__turbopack_context__) => {
"use strict";

// OSSZ ATELIER — bilingual entries for the performance module.
__turbopack_context__.s([
    "default",
    ()=>__TURBOPACK__default__export__
]);
const entries = {
    // --- Page header -------------------------------------------------------------
    "perf.title": {
        en: "Staff Performance",
        fr: "Performance du personnel"
    },
    "perf.subtitle": {
        en: "Metrics and weighted scores per staff member, computed from real assignments, completions and inspections.",
        fr: "Indicateurs et notes pondérées par membre du personnel, calculés à partir des affectations, des achèvements et des inspections réels."
    },
    "perf.exportCsv": {
        en: "Export CSV",
        fr: "Exporter en CSV"
    },
    // --- Period selector -----------------------------------------------------------
    "perf.reportingPeriod": {
        en: "Reporting Period",
        fr: "Période de reporting"
    },
    "perf.periodType": {
        en: "Period type",
        fr: "Type de période"
    },
    "perf.monthly": {
        en: "Monthly",
        fr: "Mensuel"
    },
    "perf.quarterly": {
        en: "Quarterly",
        fr: "Trimestriel"
    },
    "perf.annual": {
        en: "Annual",
        fr: "Annuel"
    },
    "perf.month": {
        en: "Month",
        fr: "Mois"
    },
    "perf.quarter": {
        en: "Quarter",
        fr: "Trimestre"
    },
    "perf.year": {
        en: "Year",
        fr: "Année"
    },
    "perf.quarter1": {
        en: "Q1",
        fr: "T1"
    },
    "perf.quarter2": {
        en: "Q2",
        fr: "T2"
    },
    "perf.quarter3": {
        en: "Q3",
        fr: "T3"
    },
    "perf.quarter4": {
        en: "Q4",
        fr: "T4"
    },
    "perf.prevPeriod": {
        en: "Previous period",
        fr: "Période précédente"
    },
    "perf.nextPeriod": {
        en: "Next period",
        fr: "Période suivante"
    },
    // --- Localized period labels (server-side labels are English-only) ----------------
    "perf.rangeMonth": {
        en: "{month} {year}",
        fr: "{month} {year}"
    },
    "perf.rangeQuarter": {
        en: "Q{quarter} {year}",
        fr: "T{quarter} {year}"
    },
    "perf.rangeYear": {
        en: "Year {year}",
        fr: "Année {year}"
    },
    // --- Staff performance table -------------------------------------------------------
    "perf.colStaff": {
        en: "Staff",
        fr: "Personnel"
    },
    "perf.colRecordedMetrics": {
        en: "Recorded metrics",
        fr: "Métriques enregistrées"
    },
    "perf.colScores": {
        en: "Scores",
        fr: "Notes"
    },
    "perf.colOverall": {
        en: "Overall",
        fr: "Global"
    },
    "perf.colMgmtAssessment": {
        en: "Mgmt Assessment",
        fr: "Évaluation direction"
    },
    "perf.colAssigned": {
        en: "Assigned",
        fr: "Affectées"
    },
    "perf.colCompleted": {
        en: "Completed",
        fr: "Terminées"
    },
    "perf.colOnTime": {
        en: "On-time",
        fr: "À temps"
    },
    "perf.colOverdue": {
        en: "Overdue",
        fr: "En retard"
    },
    "perf.colAvgHours": {
        en: "Avg Hours",
        fr: "Durée moy. (h)"
    },
    "perf.colQualityPct": {
        en: "Quality %",
        fr: "Qualité %"
    },
    "perf.colCorrections": {
        en: "Corrections",
        fr: "Retouches"
    },
    "perf.colBlocked": {
        en: "Blocked",
        fr: "Bloquées"
    },
    "perf.colProductivity": {
        en: "Productivity",
        fr: "Productivité"
    },
    "perf.colTimeliness": {
        en: "Timeliness",
        fr: "Ponctualité"
    },
    "perf.colQuality": {
        en: "Quality",
        fr: "Qualité"
    },
    "perf.colReliability": {
        en: "Reliability",
        fr: "Fiabilité"
    },
    "perf.colMgmt": {
        en: "Mgmt",
        fr: "Dir."
    },
    "perf.inactive": {
        en: "Inactive",
        fr: "Inactif"
    },
    // --- CSV export ----------------------------------------------------------------------
    "perf.csvEmail": {
        en: "Email",
        fr: "E-mail"
    },
    "perf.csvManagement": {
        en: "Management",
        fr: "Direction"
    },
    // --- Weights configuration (Management only) --------------------------------------------
    "perf.weightsTitle": {
        en: "Weights Configuration",
        fr: "Configuration des pondérations"
    },
    "perf.saveWeights": {
        en: "Save Weights",
        fr: "Enregistrer les pondérations"
    },
    "perf.saving": {
        en: "Saving…",
        fr: "Enregistrement…"
    },
    "perf.weightProductivity": {
        en: "Productivity %",
        fr: "Productivité %"
    },
    "perf.weightTimely": {
        en: "Timely Completion %",
        fr: "Ponctualité %"
    },
    "perf.weightQuality": {
        en: "Quality & Approval %",
        fr: "Qualité et approbation %"
    },
    "perf.weightReliability": {
        en: "Reliability %",
        fr: "Fiabilité %"
    },
    "perf.weightManagement": {
        en: "Management Assessment %",
        fr: "Évaluation direction %"
    },
    "perf.pieceTargetLabel": {
        en: "Piece Target / month",
        fr: "Objectif de pièces / mois"
    },
    "perf.totalWeight": {
        en: "Total weight: {total}%",
        fr: "Pondération totale : {total} %"
    },
    "perf.balanced": {
        en: "— balanced",
        fr: "— équilibrée"
    },
    "perf.mustEqual100": {
        en: "— must equal 100",
        fr: "— doit totaliser 100"
    },
    "perf.pieceTargetHint": {
        en: "Piece target: {target} pieces per month for 100% productivity.",
        fr: "Objectif de pièces : {target} pièces par mois pour 100 % de productivité."
    },
    // --- Toasts & errors ----------------------------------------------------------------------
    "perf.loadError": {
        en: "Could not load performance data",
        fr: "Impossible de charger les données de performance"
    },
    "perf.loadErrorFallback": {
        en: "Failed to load performance data.",
        fr: "Échec du chargement des données de performance."
    },
    "perf.unexpectedError": {
        en: "Unexpected error.",
        fr: "Erreur inattendue."
    },
    "perf.weightsSaved": {
        en: "Weights saved",
        fr: "Pondérations enregistrées"
    },
    "perf.weightsSavedDesc": {
        en: "Scoring weights updated — all scores were recomputed.",
        fr: "Pondérations de notation mises à jour — toutes les notes ont été recalculées."
    },
    "perf.weightsSaveError": {
        en: "Could not save weights",
        fr: "Impossible d'enregistrer les pondérations"
    },
    "perf.invalidScore": {
        en: "Invalid score",
        fr: "Note invalide"
    },
    "perf.invalidScoreDesc": {
        en: "Enter a management assessment between 0 and 100.",
        fr: "Saisissez une évaluation de la direction entre 0 et 100."
    },
    "perf.assessmentSaved": {
        en: "Assessment saved",
        fr: "Évaluation enregistrée"
    },
    "perf.assessmentSavedDesc": {
        en: "{name} — {score}/100 for {period}.",
        fr: "{name} — {score}/100 pour {period}."
    },
    "perf.assessmentSaveError": {
        en: "Could not save assessment",
        fr: "Impossible d'enregistrer l'évaluation"
    },
    "perf.assessmentAria": {
        en: "Management assessment for {name}",
        fr: "Évaluation de la direction pour {name}"
    },
    "perf.saveAssessmentAria": {
        en: "Save assessment for {name}",
        fr: "Enregistrer l'évaluation pour {name}"
    },
    // --- Empty states & notes --------------------------------------------------------------------
    "perf.noStaff": {
        en: "No staff accounts",
        fr: "Aucun compte personnel"
    },
    "perf.noStaffDesc": {
        en: "Create staff accounts to start tracking production performance.",
        fr: "Créez des comptes de personnel pour commencer à suivre la performance de production."
    },
    "perf.staffTableTitle": {
        en: "Staff Performance — {period}",
        fr: "Performance du personnel — {period}"
    },
    "perf.formulaNote": {
        en: "Productivity = pieces completed vs monthly target · Timeliness = on-time share of completions · Quality = share of approvals without corrections · Reliability = 100 − (blocks + corrections)/assigned · Overall = weighted sum (weights total 100)",
        fr: "Productivité = pièces terminées par rapport à l'objectif mensuel · Ponctualité = part des achèvements dans les délais · Qualité = part des approbations sans retouches · Fiabilité = 100 − (blocages + retouches)/affectées · Global = somme pondérée (total des pondérations : 100)"
    },
    "perf.colorNote": {
        en: "Scores are color coded: emerald 75+, amber 50+, red below 50.",
        fr: "Les notes sont codées par couleur : vert 75 et plus, ambre 50 et plus, rouge en dessous de 50."
    }
};
const __TURBOPACK__default__export__ = entries;
}),
"[project]/src/components/atelier/i18n/dict/incentives.ts [app-ssr] (ecmascript)", ((__turbopack_context__) => {
"use strict";

// OSSZ ATELIER — bilingual entries for the incentives module.
__turbopack_context__.s([
    "default",
    ()=>__TURBOPACK__default__export__
]);
const entries = {
    // --- Page header & tabs ------------------------------------------------------
    "inc.title": {
        en: "Staff Incentives",
        fr: "Primes du personnel"
    },
    "inc.subtitle": {
        en: "Define bonus schemes, generate evaluation recommendations from performance data, approve payments and record payouts.",
        fr: "Définissez les régimes de primes, générez les recommandations d'évaluation à partir des données de performance, approuvez les paiements et enregistrez les versements."
    },
    "inc.tab.schemes": {
        en: "Incentive Schemes",
        fr: "Régimes de primes"
    },
    "inc.tab.evaluations": {
        en: "Evaluations & Approvals",
        fr: "Évaluations & approbations"
    },
    // --- Schemes tab ---------------------------------------------------------------
    "inc.schemes.title": {
        en: "Incentive Schemes",
        fr: "Régimes de primes"
    },
    "inc.schemes.new": {
        en: "New Scheme",
        fr: "Nouveau régime"
    },
    "inc.schemes.emptyTitle": {
        en: "No incentive schemes yet",
        fr: "Aucun régime de primes pour le moment"
    },
    "inc.schemes.emptyDescManage": {
        en: "Create your first scheme to start rewarding staff performance.",
        fr: "Créez votre premier régime pour commencer à récompenser la performance du personnel."
    },
    "inc.schemes.emptyDescView": {
        en: "Management has not defined any incentive scheme yet.",
        fr: "La direction n'a pas encore défini de régime de primes."
    },
    "inc.schemes.col.name": {
        en: "Name",
        fr: "Nom"
    },
    "inc.schemes.col.periodType": {
        en: "Period Type",
        fr: "Type de période"
    },
    "inc.schemes.col.minScore": {
        en: "Min Score",
        fr: "Score min."
    },
    "inc.schemes.col.amount": {
        en: "Amount",
        fr: "Montant"
    },
    "inc.schemes.col.active": {
        en: "Active",
        fr: "Actif"
    },
    "inc.schemes.col.createdBy": {
        en: "Created By",
        fr: "Créé par"
    },
    "inc.schemes.anyScore": {
        en: "Any",
        fr: "Indifférent"
    },
    "inc.schemes.toggleAria": {
        en: "Toggle {name}",
        fr: "Activer/désactiver {name}"
    },
    "inc.schemes.active": {
        en: "Active",
        fr: "Actif"
    },
    "inc.schemes.inactive": {
        en: "Inactive",
        fr: "Inactif"
    },
    "inc.schemes.editAria": {
        en: "Edit {name}",
        fr: "Modifier {name}"
    },
    // --- Recommendation generation ----------------------------------------------------
    "inc.gen.title": {
        en: "Generate Recommendations",
        fr: "Générer les recommandations"
    },
    "inc.gen.schemeLabel": {
        en: "Scheme",
        fr: "Régime"
    },
    "inc.gen.selectScheme": {
        en: "Select a scheme",
        fr: "Sélectionner un régime"
    },
    "inc.gen.periodLabel": {
        en: "Period",
        fr: "Période"
    },
    "inc.gen.generating": {
        en: "Generating...",
        fr: "Génération…"
    },
    "inc.gen.hintQuarter": {
        en: "Enter a quarter (YYYY-QN)",
        fr: "Saisissez un trimestre (YYYY-QN)"
    },
    "inc.gen.hintYear": {
        en: "Enter a year (YYYY)",
        fr: "Saisissez une année (YYYY)"
    },
    "inc.gen.hintMonth": {
        en: "Enter a month (YYYY-MM)",
        fr: "Saisissez un mois (YYYY-MM)"
    },
    "inc.gen.hintScores": {
        en: "Scores are read from the performance engine; approved or rejected records are never overwritten by regeneration.",
        fr: "Les scores sont issus du moteur de performance ; les enregistrements approuvés ou refusés ne sont jamais écrasés par la régénération."
    },
    // --- Evaluation records ---------------------------------------------------------------
    "inc.records.title": {
        en: "Evaluation Records",
        fr: "Évaluations"
    },
    "inc.records.emptyTitle": {
        en: "No evaluations yet",
        fr: "Aucune évaluation pour le moment"
    },
    "inc.records.emptyDesc": {
        en: "Generate recommendations for a scheme and period to create evaluation records.",
        fr: "Générez les recommandations pour un régime et une période afin de créer des évaluations."
    },
    "inc.records.col.staff": {
        en: "Staff",
        fr: "Personnel"
    },
    "inc.records.col.scheme": {
        en: "Scheme",
        fr: "Régime"
    },
    "inc.records.col.period": {
        en: "Period",
        fr: "Période"
    },
    "inc.records.col.score": {
        en: "Score",
        fr: "Score"
    },
    "inc.records.col.eligible": {
        en: "Eligible",
        fr: "Admissible"
    },
    "inc.records.col.proposed": {
        en: "Proposed",
        fr: "Proposé"
    },
    "inc.records.col.status": {
        en: "Status",
        fr: "Statut"
    },
    "inc.records.col.approved": {
        en: "Approved",
        fr: "Approuvé"
    },
    "inc.records.col.approvedBy": {
        en: "Approved By",
        fr: "Approuvé par"
    },
    "inc.records.col.payment": {
        en: "Payment",
        fr: "Paiement"
    },
    "inc.records.notEligible": {
        en: "Not eligible",
        fr: "Non admissible"
    },
    "inc.records.review": {
        en: "Review",
        fr: "Examiner"
    },
    "inc.records.markPaid": {
        en: "Mark as Paid",
        fr: "Marquer comme payé"
    },
    // --- Scheme dialog -------------------------------------------------------------------------
    "inc.schemeDialog.editTitle": {
        en: "Edit Scheme",
        fr: "Modifier le régime"
    },
    "inc.schemeDialog.newTitle": {
        en: "New Incentive Scheme",
        fr: "Nouveau régime de primes"
    },
    "inc.schemeDialog.description": {
        en: "Define the evaluation rule. Amounts are in FCFA; minimum score is compared against the staff performance score for the period.",
        fr: "Définissez la règle d'évaluation. Les montants sont en FCFA ; le score minimum est comparé au score de performance du personnel pour la période."
    },
    "inc.schemeDialog.name": {
        en: "Name",
        fr: "Nom"
    },
    "inc.schemeDialog.namePlaceholder": {
        en: "e.g. Monthly Excellence Bonus",
        fr: "ex. Prime d'excellence mensuelle"
    },
    "inc.schemeDialog.periodType": {
        en: "Period type",
        fr: "Type de période"
    },
    "inc.schemeDialog.minScore": {
        en: "Min score (0-100)",
        fr: "Score min. (0-100)"
    },
    "inc.schemeDialog.amount": {
        en: "Amount (FCFA)",
        fr: "Montant (FCFA)"
    },
    "inc.schemeDialog.descriptionLabel": {
        en: "Description",
        fr: "Description"
    },
    "inc.schemeDialog.descPlaceholder": {
        en: "What this scheme rewards.",
        fr: "Ce que ce régime récompense."
    },
    "inc.schemeDialog.saving": {
        en: "Saving...",
        fr: "Enregistrement…"
    },
    "inc.schemeDialog.create": {
        en: "Create Scheme",
        fr: "Créer le régime"
    },
    // --- Review dialog ------------------------------------------------------------------------------
    "inc.review.title": {
        en: "Review Incentive",
        fr: "Examiner la prime"
    },
    "inc.review.description": {
        en: "Approve or reject this incentive recommendation. The decision is final — regeneration will not alter reviewed records.",
        fr: "Approuvez ou refusez cette recommandation de prime. La décision est définitive — la régénération ne modifiera pas les enregistrements examinés."
    },
    "inc.review.periodScore": {
        en: "Period {period} · Score {score}",
        fr: "Période {period} · Score {score}"
    },
    "inc.review.eligible": {
        en: "eligible",
        fr: "admissible"
    },
    "inc.review.notEligible": {
        en: "not eligible",
        fr: "non admissible"
    },
    "inc.review.proposedAmount": {
        en: "Proposed amount:",
        fr: "Montant proposé :"
    },
    "inc.review.approvedAmount": {
        en: "Approved amount (FCFA)",
        fr: "Montant approuvé (FCFA)"
    },
    "inc.review.comments": {
        en: "Comments",
        fr: "Commentaires"
    },
    "inc.review.commentsPlaceholder": {
        en: "Optional note recorded with the decision.",
        fr: "Note facultative enregistrée avec la décision."
    },
    "inc.review.reject": {
        en: "Reject",
        fr: "Refuser"
    },
    "inc.review.approve": {
        en: "Approve",
        fr: "Approuver"
    },
    // --- Toasts & validation ----------------------------------------------------------------------------
    "inc.toast.invalidNameTitle": {
        en: "Invalid name",
        fr: "Nom invalide"
    },
    "inc.toast.invalidNameDesc": {
        en: "Scheme name must be between 2 and 120 characters.",
        fr: "Le nom du régime doit contenir entre 2 et 120 caractères."
    },
    "inc.toast.invalidScoreTitle": {
        en: "Invalid minimum score",
        fr: "Score minimum invalide"
    },
    "inc.toast.invalidScoreDesc": {
        en: "Minimum score must be between 0 and 100.",
        fr: "Le score minimum doit être compris entre 0 et 100."
    },
    "inc.toast.invalidAmountTitle": {
        en: "Invalid amount",
        fr: "Montant invalide"
    },
    "inc.toast.invalidAmountDesc": {
        en: "Amount must be zero or more (FCFA).",
        fr: "Le montant doit être supérieur ou égal à zéro (FCFA)."
    },
    "inc.toast.invalidApprovedAmountDesc": {
        en: "Approved amount must be zero or more (FCFA).",
        fr: "Le montant approuvé doit être supérieur ou égal à zéro (FCFA)."
    },
    "inc.toast.schemeUpdated": {
        en: "Scheme updated",
        fr: "Régime mis à jour"
    },
    "inc.toast.schemeSavedDesc": {
        en: "{name} was saved.",
        fr: "{name} a été enregistré."
    },
    "inc.toast.schemeCreated": {
        en: "Scheme created",
        fr: "Régime créé"
    },
    "inc.toast.schemeCreatedDesc": {
        en: "{name} was added.",
        fr: "{name} a été ajouté."
    },
    "inc.toast.saveFailed": {
        en: "Could not save scheme",
        fr: "Impossible d'enregistrer le régime"
    },
    "inc.toast.schemeActivated": {
        en: "Scheme activated",
        fr: "Régime activé"
    },
    "inc.toast.schemeDeactivated": {
        en: "Scheme deactivated",
        fr: "Régime désactivé"
    },
    "inc.toast.updateFailed": {
        en: "Could not update scheme",
        fr: "Impossible de mettre à jour le régime"
    },
    "inc.toast.selectSchemeDesc": {
        en: "Choose the incentive scheme to evaluate.",
        fr: "Choisissez le régime de primes à évaluer."
    },
    "inc.toast.enterPeriod": {
        en: "Enter a period",
        fr: "Saisir une période"
    },
    "inc.toast.formatQuarter": {
        en: "Expected format: YYYY-QN (e.g. 2026-Q4).",
        fr: "Format attendu : YYYY-QN (ex. 2026-Q4)."
    },
    "inc.toast.formatYear": {
        en: "Expected format: YYYY (e.g. 2026).",
        fr: "Format attendu : YYYY (ex. 2026)."
    },
    "inc.toast.formatMonth": {
        en: "Expected format: YYYY-MM (e.g. 2026-11).",
        fr: "Format attendu : YYYY-MM (ex. 2026-11)."
    },
    "inc.toast.generatedTitle": {
        en: "Recommendations generated",
        fr: "Recommandations générées"
    },
    "inc.toast.generatedDesc": {
        en: "{total} evaluation(s) prepared — {eligible} eligible.",
        fr: "{total} évaluation(s) préparée(s) — {eligible} admissible(s)."
    },
    "inc.toast.generationFailed": {
        en: "Generation failed",
        fr: "Échec de la génération"
    },
    "inc.toast.approvedTitle": {
        en: "Incentive approved",
        fr: "Prime approuvée"
    },
    "inc.toast.rejectedTitle": {
        en: "Incentive rejected",
        fr: "Prime refusée"
    },
    "inc.toast.reviewedDesc": {
        en: "{staff} — {scheme} ({period}).",
        fr: "{staff} — {scheme} ({period})."
    },
    "inc.toast.reviewFailed": {
        en: "Review failed",
        fr: "Échec de l'examen"
    },
    "inc.toast.paymentRecorded": {
        en: "Payment recorded",
        fr: "Paiement enregistré"
    },
    "inc.toast.paymentRecordedDesc": {
        en: "{staff} — {amount}.",
        fr: "{staff} — {amount}."
    },
    "inc.toast.paymentFailed": {
        en: "Could not record payment",
        fr: "Impossible d'enregistrer le paiement"
    },
    "inc.toast.unexpected": {
        en: "Unexpected error.",
        fr: "Erreur inattendue."
    }
};
const __TURBOPACK__default__export__ = entries;
}),
"[project]/src/components/atelier/i18n/dict/reports.ts [app-ssr] (ecmascript)", ((__turbopack_context__) => {
"use strict";

// OSSZ ATELIER — bilingual entries for the reports module.
__turbopack_context__.s([
    "default",
    ()=>__TURBOPACK__default__export__
]);
const entries = {
    // --- Page header ------------------------------------------------------------
    "rep.title": {
        en: "Production Reports",
        fr: "Rapports de production"
    },
    "rep.subtitle": {
        en: "Period summary of registered orders, deliveries, completions and staff performance.",
        fr: "Synthèse par période des commandes enregistrées, des livraisons, des achèvements et de la performance du personnel."
    },
    "rep.exportCsv": {
        en: "Export CSV",
        fr: "Exporter en CSV"
    },
    // --- Period selector -----------------------------------------------------------
    "rep.reportingPeriod": {
        en: "Reporting Period",
        fr: "Période de reporting"
    },
    "rep.reportType": {
        en: "Report type",
        fr: "Type de rapport"
    },
    "rep.monthlyReport": {
        en: "Monthly report",
        fr: "Rapport mensuel"
    },
    "rep.annualReport": {
        en: "Annual report",
        fr: "Rapport annuel"
    },
    "rep.month": {
        en: "Month",
        fr: "Mois"
    },
    "rep.year": {
        en: "Year",
        fr: "Année"
    },
    // --- Errors & empty states ---------------------------------------------------------
    "rep.loadError": {
        en: "Could not load the report",
        fr: "Impossible de charger le rapport"
    },
    "rep.loadErrorFallback": {
        en: "Failed to load the report.",
        fr: "Échec du chargement du rapport."
    },
    "rep.noStaff": {
        en: "No staff accounts",
        fr: "Aucun compte personnel"
    },
    "rep.noStaffDesc": {
        en: "Create staff accounts to include them in production reports.",
        fr: "Créez des comptes de personnel pour les inclure dans les rapports de production."
    },
    // --- Summary cards ---------------------------------------------------------------------
    "rep.ordersRegistered": {
        en: "Orders Registered",
        fr: "Commandes enregistrées"
    },
    "rep.ordersDelivered": {
        en: "Orders Delivered",
        fr: "Commandes livrées"
    },
    "rep.ordersDeliveredHint": {
        en: "orders with a delivery in period",
        fr: "commandes avec une livraison sur la période"
    },
    "rep.tasksCompleted": {
        en: "Tasks Completed",
        fr: "Tâches terminées"
    },
    "rep.tasksCompletedHint": {
        en: "approved in period",
        fr: "approuvées sur la période"
    },
    "rep.piecesCompleted": {
        en: "Pieces Completed",
        fr: "Pièces terminées"
    },
    "rep.piecesCompletedHint": {
        en: "one task = one garment piece",
        fr: "une tâche = une pièce de vêtement"
    },
    "rep.onTimeCompletion": {
        en: "On-Time Completion",
        fr: "Achèvement dans les délais"
    },
    "rep.onTimeCompletionHint": {
        en: "completions before deadline",
        fr: "achèvements avant l'échéance"
    },
    // --- Staff section -------------------------------------------------------------------------
    "rep.staffTableTitle": {
        en: "Staff Performance — {period}",
        fr: "Performance du personnel — {period}"
    },
    "rep.footnote": {
        en: "Reports are generated from live production records — assignments, completions and inspection results.",
        fr: "Les rapports sont générés à partir des registres de production en temps réel — affectations, achèvements et résultats d'inspection."
    },
    // --- CSV export -------------------------------------------------------------------------------
    "rep.csvTitle": {
        en: "OSSZ ATELIER — Production Report",
        fr: "OSSZ ATELIER — Rapport de production"
    },
    "rep.csvPeriod": {
        en: "Period",
        fr: "Période"
    },
    "rep.csvOnTimeRate": {
        en: "On-Time Completion Rate (%)",
        fr: "Taux d'achèvement dans les délais (%)"
    },
    "rep.csvStaff": {
        en: "Staff",
        fr: "Personnel"
    },
    "rep.csvEmail": {
        en: "Email",
        fr: "E-mail"
    },
    "rep.csvAssigned": {
        en: "Assigned",
        fr: "Affectées"
    },
    "rep.csvCompleted": {
        en: "Completed",
        fr: "Terminées"
    },
    "rep.csvOnTime": {
        en: "On-time",
        fr: "À temps"
    },
    "rep.csvOverdue": {
        en: "Overdue",
        fr: "En retard"
    },
    "rep.csvAvgHours": {
        en: "Avg Hours",
        fr: "Durée moy. (h)"
    },
    "rep.csvQualityPct": {
        en: "Quality %",
        fr: "Qualité %"
    },
    "rep.csvCorrections": {
        en: "Corrections",
        fr: "Retouches"
    },
    "rep.csvBlocked": {
        en: "Blocked",
        fr: "Bloquées"
    },
    "rep.csvProductivity": {
        en: "Productivity",
        fr: "Productivité"
    },
    "rep.csvTimeliness": {
        en: "Timeliness",
        fr: "Ponctualité"
    },
    "rep.csvQuality": {
        en: "Quality",
        fr: "Qualité"
    },
    "rep.csvReliability": {
        en: "Reliability",
        fr: "Fiabilité"
    },
    "rep.csvManagement": {
        en: "Management",
        fr: "Direction"
    },
    "rep.csvOverall": {
        en: "Overall",
        fr: "Global"
    },
    // --- Toast --------------------------------------------------------------------------------------
    "rep.exported": {
        en: "Report exported",
        fr: "Rapport exporté"
    },
    "rep.exportedDesc": {
        en: "{file} downloaded.",
        fr: "{file} téléchargé."
    }
};
const __TURBOPACK__default__export__ = entries;
}),
"[project]/src/components/atelier/i18n/dict/users.ts [app-ssr] (ecmascript)", ((__turbopack_context__) => {
"use strict";

// OSSZ ATELIER — bilingual entries for the users module.
__turbopack_context__.s([
    "default",
    ()=>__TURBOPACK__default__export__
]);
const entries = {
    // --- Page header ------------------------------------------------------------
    "users.title": {
        en: "Users & Roles",
        fr: "Utilisateurs & rôles"
    },
    "users.description": {
        en: "Accounts with access to the atelier management system.",
        fr: "Comptes ayant accès au système de gestion de l'atelier."
    },
    "users.createAccount": {
        en: "Create Account",
        fr: "Créer un compte"
    },
    // --- Loading & error states ---------------------------------------------------
    "users.errorTitle": {
        en: "Could not load users",
        fr: "Impossible de charger les utilisateurs"
    },
    "users.loadFailed": {
        en: "Failed to load users",
        fr: "Le chargement des utilisateurs a échoué."
    },
    "users.emptyTitle": {
        en: "No accounts found",
        fr: "Aucun compte trouvé"
    },
    // --- Table headers ---------------------------------------------------------------
    "users.col.userId": {
        en: "User ID",
        fr: "Identifiant"
    },
    "users.col.user": {
        en: "User",
        fr: "Utilisateur"
    },
    "users.col.role": {
        en: "Role",
        fr: "Rôle"
    },
    "users.col.jobTitle": {
        en: "Job title",
        fr: "Fonction"
    },
    "users.col.phone": {
        en: "Phone",
        fr: "Téléphone"
    },
    "users.col.status": {
        en: "Status",
        fr: "Statut"
    },
    "users.col.activeTasks": {
        en: "Active tasks",
        fr: "Tâches actives"
    },
    "users.col.created": {
        en: "Created",
        fr: "Créé le"
    },
    // --- Row status & actions ---------------------------------------------------------
    "users.active": {
        en: "Active",
        fr: "Actif"
    },
    "users.inactive": {
        en: "Inactive",
        fr: "Inactif"
    },
    "users.actionsFor": {
        en: "Actions for {name}",
        fr: "Actions de {name}"
    },
    "users.editDetails": {
        en: "Edit details",
        fr: "Modifier les détails"
    },
    "users.resetPassword": {
        en: "Reset password",
        fr: "Réinitialiser le mot de passe"
    },
    "users.resetPasswordAction": {
        en: "Reset Password",
        fr: "Réinitialiser le mot de passe"
    },
    "users.deactivate": {
        en: "Deactivate",
        fr: "Désactiver"
    },
    "users.reactivate": {
        en: "Reactivate",
        fr: "Réactiver"
    },
    "users.adminAccessNote": {
        en: "Admin access: staff only",
        fr: "Accès admin : personnel uniquement"
    },
    // --- Create account dialog -----------------------------------------------------------
    "users.createDescription": {
        en: "New accounts can sign in immediately with the password you set.",
        fr: "Les nouveaux comptes peuvent se connecter immédiatement avec le mot de passe que vous définissez."
    },
    "users.fullName": {
        en: "Full name *",
        fr: "Nom complet *"
    },
    "users.namePlaceholder": {
        en: "e.g. Marie Ngo",
        fr: "ex. Marie Ngo"
    },
    "users.email": {
        en: "Email *",
        fr: "E-mail *"
    },
    "users.password": {
        en: "Password *",
        fr: "Mot de passe *"
    },
    "users.passwordPlaceholder": {
        en: "At least 8 characters",
        fr: "Au moins 8 caractères"
    },
    "users.roleRequired": {
        en: "Role *",
        fr: "Rôle *"
    },
    "users.phoneLabel": {
        en: "Phone",
        fr: "Téléphone"
    },
    "users.jobTitleLabel": {
        en: "Job title",
        fr: "Fonction"
    },
    "users.jobTitlePlaceholder": {
        en: "e.g. Tailor, Cutter",
        fr: "ex. Couturier, Coupeur"
    },
    "users.roleHint.management": {
        en: "Full access, final authority on incentives and critical decisions.",
        fr: "Accès complet, autorité finale sur les primes et les décisions critiques."
    },
    "users.roleHint.admin": {
        en: "Operational management of customers, orders, production and staff accounts.",
        fr: "Gestion opérationnelle des clients, commandes, production et comptes du personnel."
    },
    "users.roleHint.staff": {
        en: "Sees and updates only the tasks assigned to their own account.",
        fr: "Voit et met à jour uniquement les tâches affectées à son propre compte."
    },
    "users.roleHint.staffExtra": {
        en: "Staff accounts can be assigned production tasks.",
        fr: "Les comptes du personnel peuvent se voir affecter des tâches de production."
    },
    // --- Form validation & create toasts ------------------------------------------------
    "users.nameEmailRequired": {
        en: "Name and email are required.",
        fr: "Le nom et l'e-mail sont obligatoires."
    },
    "users.passwordTooShort": {
        en: "Password must be at least 8 characters.",
        fr: "Le mot de passe doit contenir au moins 8 caractères."
    },
    "users.createdToastTitle": {
        en: "Account created",
        fr: "Compte créé"
    },
    "users.createdToastDesc": {
        en: "{name} ({role}) can now sign in.",
        fr: "{name} ({role}) peut maintenant se connecter."
    },
    "users.createFailedToast": {
        en: "Could not create account",
        fr: "Impossible de créer le compte"
    },
    // --- Edit dialog ----------------------------------------------------------------------
    "users.editTitle": {
        en: "Edit Account",
        fr: "Modifier le compte"
    },
    "users.roleLabel": {
        en: "Role",
        fr: "Rôle"
    },
    "users.updateFailedToast": {
        en: "Could not update account",
        fr: "Impossible de mettre à jour le compte"
    },
    "users.nameTooShort": {
        en: "Name must be at least 2 characters.",
        fr: "Le nom doit contenir au moins 2 caractères."
    },
    "users.updatedToastTitle": {
        en: "Account updated",
        fr: "Compte mis à jour"
    },
    "users.savedToast": {
        en: "{name} was saved.",
        fr: "{name} a été enregistré(e)."
    },
    // --- Reset password dialog ---------------------------------------------------------------
    "users.resetPwDescription": {
        en: "Set a new password for {name} ({email}). They will use it at the next sign-in.",
        fr: "Définissez un nouveau mot de passe pour {name} ({email}). Il sera utilisé lors de la prochaine connexion."
    },
    "users.newPassword": {
        en: "New password *",
        fr: "Nouveau mot de passe *"
    },
    "users.resetToastTitle": {
        en: "Password reset",
        fr: "Mot de passe réinitialisé"
    },
    "users.resetToastDesc": {
        en: "{name} can sign in with the new password.",
        fr: "{name} peut se connecter avec le nouveau mot de passe."
    },
    "users.resetFailedToast": {
        en: "Could not reset password",
        fr: "Impossible de réinitialiser le mot de passe"
    },
    // --- Deactivate / reactivate dialog ----------------------------------------------------------
    "users.deactivateTitle": {
        en: "Deactivate account",
        fr: "Désactiver le compte"
    },
    "users.reactivateTitle": {
        en: "Reactivate account",
        fr: "Réactiver le compte"
    },
    "users.deactivateDesc": {
        en: "{name} will no longer be able to sign in. Task history and audit records are kept.",
        fr: "{name} ne pourra plus se connecter. L'historique des tâches et le journal d'audit sont conservés."
    },
    "users.reactivateDesc": {
        en: "{name} will be able to sign in again with their existing password.",
        fr: "{name} pourra de nouveau se connecter avec son mot de passe actuel."
    },
    "users.reactivatedToastTitle": {
        en: "Account reactivated",
        fr: "Compte réactivé"
    },
    "users.deactivatedToastTitle": {
        en: "Account deactivated",
        fr: "Compte désactivé"
    },
    "users.reactivatedToastDesc": {
        en: "{name} can sign in again.",
        fr: "{name} peut de nouveau se connecter."
    },
    "users.deactivatedToastDesc": {
        en: "{name} can no longer sign in.",
        fr: "{name} ne peut plus se connecter."
    }
};
const __TURBOPACK__default__export__ = entries;
}),
"[project]/src/components/atelier/i18n/dict/stages.ts [app-ssr] (ecmascript)", ((__turbopack_context__) => {
"use strict";

// OSSZ ATELIER — bilingual entries for the stages module.
__turbopack_context__.s([
    "default",
    ()=>__TURBOPACK__default__export__
]);
const entries = {
    // --- Page header ------------------------------------------------------------
    "stages.title": {
        en: "Production Stages",
        fr: "Étapes de production"
    },
    "stages.description": {
        en: "Configure the checklist stages available to production tasks. Different task categories use different stage sets.",
        fr: "Configurez les étapes de la liste de contrôle disponibles pour les tâches de production. Chaque catégorie de tâches utilise son propre ensemble d'étapes."
    },
    "stages.addStage": {
        en: "Add Stage",
        fr: "Ajouter une étape"
    },
    // --- History notice ------------------------------------------------------------
    "stages.historyTitle": {
        en: "Stage changes never rewrite history",
        fr: "Les modifications d'étapes ne réécrivent jamais l'historique"
    },
    "stages.historyDescription": {
        en: "Existing task histories keep the stage names at the time of recording. Deactivating a stage removes it from new tasks only.",
        fr: "L'historique des tâches existantes conserve les noms des étapes au moment de l'enregistrement. Désactiver une étape la retire uniquement des nouvelles tâches."
    },
    // --- Loading, error & empty states ------------------------------------------------
    "stages.errorTitle": {
        en: "Could not load the stage configuration",
        fr: "Impossible de charger la configuration des étapes"
    },
    "stages.unexpectedError": {
        en: "Unexpected error.",
        fr: "Une erreur inattendue s'est produite."
    },
    "stages.emptyTitle": {
        en: "No stages configured",
        fr: "Aucune étape configurée"
    },
    "stages.emptyDescription": {
        en: "Create the first checklist stage to start building the production workflow.",
        fr: "Créez la première étape de la liste de contrôle pour commencer à construire le flux de production."
    },
    // --- Table headers --------------------------------------------------------------------
    "stages.col.sort": {
        en: "Sort",
        fr: "Ordre"
    },
    "stages.col.name": {
        en: "Name",
        fr: "Nom"
    },
    "stages.col.code": {
        en: "Code",
        fr: "Code"
    },
    "stages.col.appliesTo": {
        en: "Applies To",
        fr: "S'applique à"
    },
    "stages.col.weight": {
        en: "Weight",
        fr: "Poids"
    },
    "stages.col.active": {
        en: "Active",
        fr: "Actif"
    },
    "stages.allCategories": {
        en: "All categories",
        fr: "Toutes les catégories"
    },
    // --- Row actions -------------------------------------------------------------------------
    "stages.deactivateStageAria": {
        en: "Deactivate stage {name}",
        fr: "Désactiver l'étape {name}"
    },
    "stages.activateStageAria": {
        en: "Activate stage {name}",
        fr: "Activer l'étape {name}"
    },
    "stages.editStageAria": {
        en: "Edit stage {name}",
        fr: "Modifier l'étape {name}"
    },
    // --- Add / edit dialog ----------------------------------------------------------------------
    "stages.editTitle": {
        en: "Edit Stage",
        fr: "Modifier l'étape"
    },
    "stages.editDescription": {
        en: "Update the stage details. The stage code stays unchanged.",
        fr: "Mettez à jour les détails de l'étape. Le code de l'étape reste inchangé."
    },
    "stages.addDescription": {
        en: "Define a new checklist stage and the task categories it applies to.",
        fr: "Définissez une nouvelle étape de la liste de contrôle ainsi que les catégories de tâches auxquelles elle s'applique."
    },
    "stages.nameLabel": {
        en: "Name",
        fr: "Nom"
    },
    "stages.namePlaceholder": {
        en: "e.g. Fabric cutting",
        fr: "ex. Coupe du tissu"
    },
    "stages.appliesToLabel": {
        en: "Applies to",
        fr: "S'applique à"
    },
    "stages.appliesToHint": {
        en: "Leave all unchecked or check all to apply to every category.",
        fr: "Laissez tout décoché ou cochez tout pour appliquer à toutes les catégories."
    },
    "stages.weightLabel": {
        en: "Weight",
        fr: "Poids"
    },
    "stages.sortOrderLabel": {
        en: "Sort order",
        fr: "Ordre de tri"
    },
    "stages.saving": {
        en: "Saving…",
        fr: "Enregistrement…"
    },
    "stages.createStage": {
        en: "Create stage",
        fr: "Créer l'étape"
    },
    // --- Validation toasts ------------------------------------------------------------------------
    "stages.invalidNameTitle": {
        en: "Invalid name",
        fr: "Nom invalide"
    },
    "stages.invalidNameDesc": {
        en: "The stage name must be between 2 and 80 characters.",
        fr: "Le nom de l'étape doit contenir entre 2 et 80 caractères."
    },
    "stages.invalidWeightTitle": {
        en: "Invalid weight",
        fr: "Poids invalide"
    },
    "stages.invalidWeightDesc": {
        en: "Weight must be a number between 0.1 and 10.",
        fr: "Le poids doit être un nombre compris entre 0,1 et 10."
    },
    "stages.invalidSortTitle": {
        en: "Invalid sort order",
        fr: "Ordre de tri invalide"
    },
    "stages.invalidSortDesc": {
        en: "Sort order must be a whole number between 0 and 999.",
        fr: "L'ordre de tri doit être un nombre entier compris entre 0 et 999."
    },
    // --- Save toasts ---------------------------------------------------------------------------------
    "stages.updatedToastTitle": {
        en: "Stage updated",
        fr: "Étape mise à jour"
    },
    "stages.createdToastTitle": {
        en: "Stage created",
        fr: "Étape créée"
    },
    "stages.savedToastDesc": {
        en: "\"{name}\" was saved successfully.",
        fr: "« {name} » a été enregistrée avec succès."
    },
    "stages.saveFailedToast": {
        en: "Could not save the stage",
        fr: "Impossible d'enregistrer l'étape"
    },
    // --- Activate / deactivate toasts -------------------------------------------------------------------
    "stages.activatedToastTitle": {
        en: "Stage activated",
        fr: "Étape activée"
    },
    "stages.deactivatedToastTitle": {
        en: "Stage deactivated",
        fr: "Étape désactivée"
    },
    "stages.activatedToastDesc": {
        en: "\"{name}\" is available to new tasks again.",
        fr: "« {name} » est de nouveau disponible pour les nouvelles tâches."
    },
    "stages.deactivatedToastDesc": {
        en: "\"{name}\" will no longer be added to new tasks.",
        fr: "« {name} » ne sera plus ajoutée aux nouvelles tâches."
    },
    "stages.updateFailedToast": {
        en: "Could not update the stage",
        fr: "Impossible de mettre à jour l'étape"
    }
};
const __TURBOPACK__default__export__ = entries;
}),
"[project]/src/components/atelier/i18n/dict/audit.ts [app-ssr] (ecmascript)", ((__turbopack_context__) => {
"use strict";

// OSSZ ATELIER — bilingual entries for the audit module.
__turbopack_context__.s([
    "default",
    ()=>__TURBOPACK__default__export__
]);
const entries = {
    // --- Page header ------------------------------------------------------------
    "audit.title": {
        en: "Audit History",
        fr: "Historique d'audit"
    },
    "audit.description": {
        en: "Permanent record of all system activity. Entries cannot be edited or deleted.",
        fr: "Journal permanent de toute l'activité du système. Les entrées ne peuvent être ni modifiées ni supprimées."
    },
    // --- Loading & error states ---------------------------------------------------
    "audit.errorTitle": {
        en: "Could not load the audit trail",
        fr: "Impossible de charger le journal d'audit"
    },
    "audit.unexpectedError": {
        en: "Unexpected error.",
        fr: "Une erreur inattendue s'est produite."
    },
    // --- Filters ---------------------------------------------------------------------
    "audit.filters": {
        en: "Filters",
        fr: "Filtres"
    },
    "audit.actionFilter": {
        en: "Action",
        fr: "Action"
    },
    "audit.allActions": {
        en: "All actions",
        fr: "Toutes les actions"
    },
    "audit.entityTypeFilter": {
        en: "Entity type",
        fr: "Type d'entité"
    },
    "audit.allTypes": {
        en: "All types",
        fr: "Tous les types"
    },
    "audit.from": {
        en: "From",
        fr: "Du"
    },
    "audit.to": {
        en: "To",
        fr: "Au"
    },
    "audit.reset": {
        en: "Reset",
        fr: "Réinitialiser"
    },
    // --- Entries table -------------------------------------------------------------------
    "audit.entries": {
        en: "Entries",
        fr: "Entrées"
    },
    "audit.emptyTitle": {
        en: "No audit entries match these filters.",
        fr: "Aucune entrée d'audit ne correspond à ces filtres."
    },
    "audit.emptyDescription": {
        en: "Try widening the date range or clearing the action and entity filters.",
        fr: "Essayez d'élargir la plage de dates ou de supprimer les filtres d'action et d'entité."
    },
    "audit.col.date": {
        en: "Date & Time",
        fr: "Date"
    },
    "audit.col.user": {
        en: "User",
        fr: "Utilisateur"
    },
    "audit.col.action": {
        en: "Action",
        fr: "Action"
    },
    "audit.col.entity": {
        en: "Entity",
        fr: "Entité"
    },
    "audit.col.details": {
        en: "Details",
        fr: "Détails"
    },
    "audit.system": {
        en: "System",
        fr: "Système"
    },
    // --- Pagination -------------------------------------------------------------------------
    "audit.paginationSingular": {
        en: "{total} entry · page {page} of {totalPages}",
        fr: "{total} entrée · page {page} sur {totalPages}"
    },
    "audit.paginationPlural": {
        en: "{total} entries · page {page} of {totalPages}",
        fr: "{total} entrées · page {page} sur {totalPages}"
    },
    "audit.previous": {
        en: "Previous",
        fr: "Précédent"
    },
    "audit.next": {
        en: "Next",
        fr: "Suivant"
    },
    // --- Audit action labels (every action code written by logAudit()) ---------------------------
    "audit.action.LOGIN_SUCCESS": {
        en: "Login successful",
        fr: "Connexion réussie"
    },
    "audit.action.LOGIN_FAILED": {
        en: "Login failed",
        fr: "Échec de connexion"
    },
    "audit.action.LOGIN_BLOCKED_INACTIVE": {
        en: "Login blocked (inactive account)",
        fr: "Connexion bloquée (compte désactivé)"
    },
    "audit.action.LOGOUT": {
        en: "Logout",
        fr: "Déconnexion"
    },
    "audit.action.USER_CREATED": {
        en: "User created",
        fr: "Utilisateur créé"
    },
    "audit.action.USER_UPDATED": {
        en: "User updated",
        fr: "Utilisateur mis à jour"
    },
    "audit.action.USER_PASSWORD_RESET": {
        en: "User password reset",
        fr: "Mot de passe utilisateur réinitialisé"
    },
    "audit.action.STAFF_ASSESSED": {
        en: "Staff assessed",
        fr: "Personnel évalué"
    },
    "audit.action.CUSTOMER_CREATED": {
        en: "Customer created",
        fr: "Client créé"
    },
    "audit.action.CUSTOMER_UPDATED": {
        en: "Customer updated",
        fr: "Client mis à jour"
    },
    "audit.action.ORDER_CREATED": {
        en: "Order created",
        fr: "Commande créée"
    },
    "audit.action.ORDER_UPDATED": {
        en: "Order updated",
        fr: "Commande mise à jour"
    },
    "audit.action.TASKS_CREATED": {
        en: "Tasks created",
        fr: "Tâches créées"
    },
    "audit.action.TASK_ASSIGNED": {
        en: "Task assigned",
        fr: "Tâche affectée"
    },
    "audit.action.TASK_REASSIGNED": {
        en: "Task reassigned",
        fr: "Tâche réaffectée"
    },
    "audit.action.TASK_UPDATED": {
        en: "Task updated",
        fr: "Tâche mise à jour"
    },
    "audit.action.TASK_CANCELLED": {
        en: "Task cancelled",
        fr: "Tâche annulée"
    },
    "audit.action.TASK_BLOCKED": {
        en: "Task blocked",
        fr: "Tâche bloquée"
    },
    "audit.action.BLOCK_RESOLVED": {
        en: "Block resolved",
        fr: "Blocage résolu"
    },
    "audit.action.SUBMITTED_FOR_INSPECTION": {
        en: "Submitted for inspection",
        fr: "Soumis à l'inspection"
    },
    "audit.action.INSPECTION_COMPLETED": {
        en: "Inspection completed",
        fr: "Inspection terminée"
    },
    "audit.action.TASK_READY_FOR_PICKUP": {
        en: "Task ready for pickup",
        fr: "Tâche prête pour retrait"
    },
    "audit.action.TASK_DELIVERED": {
        en: "Task delivered",
        fr: "Tâche livrée"
    },
    "audit.action.TASK_STATUS_CHANGED": {
        en: "Task status changed",
        fr: "Statut de tâche modifié"
    },
    "audit.action.STAGES_UPDATED": {
        en: "Stages updated",
        fr: "Étapes mises à jour"
    },
    "audit.action.PROGRESS_UPDATED": {
        en: "Progress updated",
        fr: "Progression mise à jour"
    },
    "audit.action.COMMENT_ADDED": {
        en: "Comment added",
        fr: "Commentaire ajouté"
    },
    "audit.action.ISSUE_REPORTED": {
        en: "Issue reported",
        fr: "Problème signalé"
    },
    "audit.action.STAGE_CREATED": {
        en: "Stage created",
        fr: "Étape créée"
    },
    "audit.action.STAGE_UPDATED": {
        en: "Stage updated",
        fr: "Étape mise à jour"
    },
    "audit.action.PERFORMANCE_CONFIG_UPDATED": {
        en: "Performance config updated",
        fr: "Configuration de performance mise à jour"
    },
    "audit.action.INCENTIVE_SCHEME_CREATED": {
        en: "Incentive scheme created",
        fr: "Régime de primes créé"
    },
    "audit.action.INCENTIVE_SCHEME_UPDATED": {
        en: "Incentive scheme updated",
        fr: "Régime de primes mis à jour"
    },
    "audit.action.INCENTIVE_RECOMMENDATIONS_GENERATED": {
        en: "Incentive recommendations generated",
        fr: "Recommandations de primes générées"
    },
    "audit.action.INCENTIVE_APPROVED": {
        en: "Incentive approved",
        fr: "Prime approuvée"
    },
    "audit.action.INCENTIVE_REJECTED": {
        en: "Incentive rejected",
        fr: "Prime refusée"
    },
    "audit.action.INCENTIVE_PAYMENT_RECORDED": {
        en: "Incentive payment recorded",
        fr: "Paiement de prime enregistré"
    }
};
const __TURBOPACK__default__export__ = entries;
}),
"[project]/src/components/atelier/i18n/dict/index.ts [app-ssr] (ecmascript)", ((__turbopack_context__) => {
"use strict";

// OSSZ ATELIER — merges every bilingual dictionary module into one lookup map.
// Each screen owns its own file so entries can be added independently.
__turbopack_context__.s([
    "merged",
    ()=>merged
]);
var __TURBOPACK__imported__module__$5b$project$5d2f$src$2f$components$2f$atelier$2f$i18n$2f$dict$2f$common$2e$ts__$5b$app$2d$ssr$5d$__$28$ecmascript$29$__ = __turbopack_context__.i("[project]/src/components/atelier/i18n/dict/common.ts [app-ssr] (ecmascript)");
var __TURBOPACK__imported__module__$5b$project$5d2f$src$2f$components$2f$atelier$2f$i18n$2f$dict$2f$shell$2e$ts__$5b$app$2d$ssr$5d$__$28$ecmascript$29$__ = __turbopack_context__.i("[project]/src/components/atelier/i18n/dict/shell.ts [app-ssr] (ecmascript)");
var __TURBOPACK__imported__module__$5b$project$5d2f$src$2f$components$2f$atelier$2f$i18n$2f$dict$2f$login$2e$ts__$5b$app$2d$ssr$5d$__$28$ecmascript$29$__ = __turbopack_context__.i("[project]/src/components/atelier/i18n/dict/login.ts [app-ssr] (ecmascript)");
var __TURBOPACK__imported__module__$5b$project$5d2f$src$2f$components$2f$atelier$2f$i18n$2f$dict$2f$dashboard$2e$ts__$5b$app$2d$ssr$5d$__$28$ecmascript$29$__ = __turbopack_context__.i("[project]/src/components/atelier/i18n/dict/dashboard.ts [app-ssr] (ecmascript)");
var __TURBOPACK__imported__module__$5b$project$5d2f$src$2f$components$2f$atelier$2f$i18n$2f$dict$2f$notifications$2e$ts__$5b$app$2d$ssr$5d$__$28$ecmascript$29$__ = __turbopack_context__.i("[project]/src/components/atelier/i18n/dict/notifications.ts [app-ssr] (ecmascript)");
var __TURBOPACK__imported__module__$5b$project$5d2f$src$2f$components$2f$atelier$2f$i18n$2f$dict$2f$tasks$2e$ts__$5b$app$2d$ssr$5d$__$28$ecmascript$29$__ = __turbopack_context__.i("[project]/src/components/atelier/i18n/dict/tasks.ts [app-ssr] (ecmascript)");
var __TURBOPACK__imported__module__$5b$project$5d2f$src$2f$components$2f$atelier$2f$i18n$2f$dict$2f$task$2d$create$2e$ts__$5b$app$2d$ssr$5d$__$28$ecmascript$29$__ = __turbopack_context__.i("[project]/src/components/atelier/i18n/dict/task-create.ts [app-ssr] (ecmascript)");
var __TURBOPACK__imported__module__$5b$project$5d2f$src$2f$components$2f$atelier$2f$i18n$2f$dict$2f$task$2d$detail$2e$ts__$5b$app$2d$ssr$5d$__$28$ecmascript$29$__ = __turbopack_context__.i("[project]/src/components/atelier/i18n/dict/task-detail.ts [app-ssr] (ecmascript)");
var __TURBOPACK__imported__module__$5b$project$5d2f$src$2f$components$2f$atelier$2f$i18n$2f$dict$2f$orders$2e$ts__$5b$app$2d$ssr$5d$__$28$ecmascript$29$__ = __turbopack_context__.i("[project]/src/components/atelier/i18n/dict/orders.ts [app-ssr] (ecmascript)");
var __TURBOPACK__imported__module__$5b$project$5d2f$src$2f$components$2f$atelier$2f$i18n$2f$dict$2f$customers$2e$ts__$5b$app$2d$ssr$5d$__$28$ecmascript$29$__ = __turbopack_context__.i("[project]/src/components/atelier/i18n/dict/customers.ts [app-ssr] (ecmascript)");
var __TURBOPACK__imported__module__$5b$project$5d2f$src$2f$components$2f$atelier$2f$i18n$2f$dict$2f$performance$2e$ts__$5b$app$2d$ssr$5d$__$28$ecmascript$29$__ = __turbopack_context__.i("[project]/src/components/atelier/i18n/dict/performance.ts [app-ssr] (ecmascript)");
var __TURBOPACK__imported__module__$5b$project$5d2f$src$2f$components$2f$atelier$2f$i18n$2f$dict$2f$incentives$2e$ts__$5b$app$2d$ssr$5d$__$28$ecmascript$29$__ = __turbopack_context__.i("[project]/src/components/atelier/i18n/dict/incentives.ts [app-ssr] (ecmascript)");
var __TURBOPACK__imported__module__$5b$project$5d2f$src$2f$components$2f$atelier$2f$i18n$2f$dict$2f$reports$2e$ts__$5b$app$2d$ssr$5d$__$28$ecmascript$29$__ = __turbopack_context__.i("[project]/src/components/atelier/i18n/dict/reports.ts [app-ssr] (ecmascript)");
var __TURBOPACK__imported__module__$5b$project$5d2f$src$2f$components$2f$atelier$2f$i18n$2f$dict$2f$users$2e$ts__$5b$app$2d$ssr$5d$__$28$ecmascript$29$__ = __turbopack_context__.i("[project]/src/components/atelier/i18n/dict/users.ts [app-ssr] (ecmascript)");
var __TURBOPACK__imported__module__$5b$project$5d2f$src$2f$components$2f$atelier$2f$i18n$2f$dict$2f$stages$2e$ts__$5b$app$2d$ssr$5d$__$28$ecmascript$29$__ = __turbopack_context__.i("[project]/src/components/atelier/i18n/dict/stages.ts [app-ssr] (ecmascript)");
var __TURBOPACK__imported__module__$5b$project$5d2f$src$2f$components$2f$atelier$2f$i18n$2f$dict$2f$audit$2e$ts__$5b$app$2d$ssr$5d$__$28$ecmascript$29$__ = __turbopack_context__.i("[project]/src/components/atelier/i18n/dict/audit.ts [app-ssr] (ecmascript)");
;
;
;
;
;
;
;
;
;
;
;
;
;
;
;
;
const merged = Object.assign({}, __TURBOPACK__imported__module__$5b$project$5d2f$src$2f$components$2f$atelier$2f$i18n$2f$dict$2f$common$2e$ts__$5b$app$2d$ssr$5d$__$28$ecmascript$29$__["default"], __TURBOPACK__imported__module__$5b$project$5d2f$src$2f$components$2f$atelier$2f$i18n$2f$dict$2f$shell$2e$ts__$5b$app$2d$ssr$5d$__$28$ecmascript$29$__["default"], __TURBOPACK__imported__module__$5b$project$5d2f$src$2f$components$2f$atelier$2f$i18n$2f$dict$2f$login$2e$ts__$5b$app$2d$ssr$5d$__$28$ecmascript$29$__["default"], __TURBOPACK__imported__module__$5b$project$5d2f$src$2f$components$2f$atelier$2f$i18n$2f$dict$2f$dashboard$2e$ts__$5b$app$2d$ssr$5d$__$28$ecmascript$29$__["default"], __TURBOPACK__imported__module__$5b$project$5d2f$src$2f$components$2f$atelier$2f$i18n$2f$dict$2f$notifications$2e$ts__$5b$app$2d$ssr$5d$__$28$ecmascript$29$__["default"], __TURBOPACK__imported__module__$5b$project$5d2f$src$2f$components$2f$atelier$2f$i18n$2f$dict$2f$tasks$2e$ts__$5b$app$2d$ssr$5d$__$28$ecmascript$29$__["default"], __TURBOPACK__imported__module__$5b$project$5d2f$src$2f$components$2f$atelier$2f$i18n$2f$dict$2f$task$2d$create$2e$ts__$5b$app$2d$ssr$5d$__$28$ecmascript$29$__["default"], __TURBOPACK__imported__module__$5b$project$5d2f$src$2f$components$2f$atelier$2f$i18n$2f$dict$2f$task$2d$detail$2e$ts__$5b$app$2d$ssr$5d$__$28$ecmascript$29$__["default"], __TURBOPACK__imported__module__$5b$project$5d2f$src$2f$components$2f$atelier$2f$i18n$2f$dict$2f$orders$2e$ts__$5b$app$2d$ssr$5d$__$28$ecmascript$29$__["default"], __TURBOPACK__imported__module__$5b$project$5d2f$src$2f$components$2f$atelier$2f$i18n$2f$dict$2f$customers$2e$ts__$5b$app$2d$ssr$5d$__$28$ecmascript$29$__["default"], __TURBOPACK__imported__module__$5b$project$5d2f$src$2f$components$2f$atelier$2f$i18n$2f$dict$2f$performance$2e$ts__$5b$app$2d$ssr$5d$__$28$ecmascript$29$__["default"], __TURBOPACK__imported__module__$5b$project$5d2f$src$2f$components$2f$atelier$2f$i18n$2f$dict$2f$incentives$2e$ts__$5b$app$2d$ssr$5d$__$28$ecmascript$29$__["default"], __TURBOPACK__imported__module__$5b$project$5d2f$src$2f$components$2f$atelier$2f$i18n$2f$dict$2f$reports$2e$ts__$5b$app$2d$ssr$5d$__$28$ecmascript$29$__["default"], __TURBOPACK__imported__module__$5b$project$5d2f$src$2f$components$2f$atelier$2f$i18n$2f$dict$2f$users$2e$ts__$5b$app$2d$ssr$5d$__$28$ecmascript$29$__["default"], __TURBOPACK__imported__module__$5b$project$5d2f$src$2f$components$2f$atelier$2f$i18n$2f$dict$2f$stages$2e$ts__$5b$app$2d$ssr$5d$__$28$ecmascript$29$__["default"], __TURBOPACK__imported__module__$5b$project$5d2f$src$2f$components$2f$atelier$2f$i18n$2f$dict$2f$audit$2e$ts__$5b$app$2d$ssr$5d$__$28$ecmascript$29$__["default"]);
;
}),
"[project]/src/components/atelier/i18n/core.ts [app-ssr] (ecmascript)", ((__turbopack_context__) => {
"use strict";

// OSSZ ATELIER — bilingual i18n core (English / French).
// Plain types, dictionary lookup and domain label helpers. No React code,
// so it can be imported from any client module (including non-hook utilities).
__turbopack_context__.s([
    "categoryLabel",
    ()=>categoryLabel,
    "currentLang",
    ()=>currentLang,
    "incentivePeriodLabel",
    ()=>incentivePeriodLabel,
    "incentiveStatusLabel",
    ()=>incentiveStatusLabel,
    "inspectionLabel",
    ()=>inspectionLabel,
    "isLang",
    ()=>isLang,
    "notifTitle",
    ()=>notifTitle,
    "paymentStatusLabel",
    ()=>paymentStatusLabel,
    "persistLang",
    ()=>persistLang,
    "priorityLabel",
    ()=>priorityLabel,
    "roleLabel",
    ()=>roleLabel,
    "stageStateLabel",
    ()=>stageStateLabel,
    "statusLabel",
    ()=>statusLabel,
    "translate",
    ()=>translate
]);
// ---------------------------------------------------------------------------
// Dictionary lookup
// ---------------------------------------------------------------------------
var __TURBOPACK__imported__module__$5b$project$5d2f$src$2f$components$2f$atelier$2f$i18n$2f$dict$2f$index$2e$ts__$5b$app$2d$ssr$5d$__$28$ecmascript$29$__ = __turbopack_context__.i("[project]/src/components/atelier/i18n/dict/index.ts [app-ssr] (ecmascript)");
const LANG_STORAGE_KEY = "atelier_lang";
function isLang(value) {
    return value === "en" || value === "fr";
}
function currentLang() {
    if ("TURBOPACK compile-time truthy", 1) return "en";
    //TURBOPACK unreachable
    ;
}
function persistLang(lang) {
    try {
        window.localStorage.setItem(LANG_STORAGE_KEY, lang);
        document.documentElement.lang = lang;
    } catch  {
    // storage unavailable — keep in-memory only
    }
}
;
function lookup(key, lang) {
    const entry = __TURBOPACK__imported__module__$5b$project$5d2f$src$2f$components$2f$atelier$2f$i18n$2f$dict$2f$index$2e$ts__$5b$app$2d$ssr$5d$__$28$ecmascript$29$__["merged"][key];
    if (!entry) return null;
    const text = lang === "fr" ? entry.fr || entry.en : entry.en;
    return text ?? null;
}
function interpolate(text, params) {
    if (!params) return text;
    let out = text;
    for (const [name, value] of Object.entries(params)){
        out = out.split(`{${name}}`).join(String(value));
    }
    return out;
}
function translate(key, lang = currentLang(), params) {
    const text = lookup(key, lang);
    if (text == null) {
        if ("TURBOPACK compile-time truthy", 1) {
            console.warn(`[i18n] missing key "${key}" (${lang})`);
        }
        return interpolate(key, params);
    }
    return interpolate(text, params);
}
function statusLabel(status, lang = currentLang()) {
    return translate(`status.${status}`, lang);
}
function priorityLabel(priority, lang = currentLang()) {
    return translate(`priority.${priority}`, lang);
}
function categoryLabel(category, lang = currentLang()) {
    return translate(`category.${category}`, lang);
}
function stageStateLabel(state, lang = currentLang()) {
    return translate(`stageState.${state}`, lang);
}
function roleLabel(role, lang = currentLang()) {
    return translate(`role.${role}`, lang);
}
function inspectionLabel(decision, lang = currentLang()) {
    return translate(`inspection.${decision}`, lang);
}
function incentivePeriodLabel(periodType, lang = currentLang()) {
    return translate(`incentivePeriod.${periodType}`, lang);
}
function incentiveStatusLabel(status, lang = currentLang()) {
    return translate(`incStatus.${status}`, lang);
}
function paymentStatusLabel(status, lang = currentLang()) {
    return translate(`paymentStatus.${status}`, lang);
}
function notifTitle(type, fallback, lang = currentLang()) {
    const text = lookup(`notifTitle.${type}`, lang);
    return text ?? fallback;
}
}),
"[project]/src/components/atelier/i18n/use-i18n.ts [app-ssr] (ecmascript)", ((__turbopack_context__) => {
"use strict";

__turbopack_context__.s([
    "useI18n",
    ()=>useI18n
]);
// OSSZ ATELIER — React binding for the bilingual i18n core. Components use
// useI18n() to read the active language and translate keys; the language
// switcher updates the store which re-renders every subscribed view.
var __TURBOPACK__imported__module__$5b$project$5d2f$node_modules$2f$next$2f$dist$2f$server$2f$route$2d$modules$2f$app$2d$page$2f$vendored$2f$ssr$2f$react$2e$js__$5b$app$2d$ssr$5d$__$28$ecmascript$29$__ = __turbopack_context__.i("[project]/node_modules/next/dist/server/route-modules/app-page/vendored/ssr/react.js [app-ssr] (ecmascript)");
var __TURBOPACK__imported__module__$5b$project$5d2f$src$2f$components$2f$atelier$2f$store$2e$ts__$5b$app$2d$ssr$5d$__$28$ecmascript$29$__ = __turbopack_context__.i("[project]/src/components/atelier/store.ts [app-ssr] (ecmascript)");
var __TURBOPACK__imported__module__$5b$project$5d2f$src$2f$components$2f$atelier$2f$i18n$2f$core$2e$ts__$5b$app$2d$ssr$5d$__$28$ecmascript$29$__ = __turbopack_context__.i("[project]/src/components/atelier/i18n/core.ts [app-ssr] (ecmascript)");
"use client";
;
;
;
function useI18n() {
    const lang = (0, __TURBOPACK__imported__module__$5b$project$5d2f$src$2f$components$2f$atelier$2f$store$2e$ts__$5b$app$2d$ssr$5d$__$28$ecmascript$29$__["useAppStore"])((s)=>s.lang);
    const setLang = (0, __TURBOPACK__imported__module__$5b$project$5d2f$src$2f$components$2f$atelier$2f$store$2e$ts__$5b$app$2d$ssr$5d$__$28$ecmascript$29$__["useAppStore"])((s)=>s.setLang);
    const t = (0, __TURBOPACK__imported__module__$5b$project$5d2f$node_modules$2f$next$2f$dist$2f$server$2f$route$2d$modules$2f$app$2d$page$2f$vendored$2f$ssr$2f$react$2e$js__$5b$app$2d$ssr$5d$__$28$ecmascript$29$__["useCallback"])((key, params)=>(0, __TURBOPACK__imported__module__$5b$project$5d2f$src$2f$components$2f$atelier$2f$i18n$2f$core$2e$ts__$5b$app$2d$ssr$5d$__$28$ecmascript$29$__["translate"])(key, lang, params), [
        lang
    ]);
    return {
        t,
        lang,
        setLang
    };
}
}),
"[project]/src/components/atelier/i18n/language-toggle.tsx [app-ssr] (ecmascript)", ((__turbopack_context__) => {
"use strict";

__turbopack_context__.s([
    "LanguageToggle",
    ()=>LanguageToggle
]);
var __TURBOPACK__imported__module__$5b$project$5d2f$node_modules$2f$next$2f$dist$2f$server$2f$route$2d$modules$2f$app$2d$page$2f$vendored$2f$ssr$2f$react$2d$jsx$2d$dev$2d$runtime$2e$js__$5b$app$2d$ssr$5d$__$28$ecmascript$29$__ = __turbopack_context__.i("[project]/node_modules/next/dist/server/route-modules/app-page/vendored/ssr/react-jsx-dev-runtime.js [app-ssr] (ecmascript)");
// OSSZ ATELIER — compact EN/FR language switch button used in the login
// screen and the application header.
var __TURBOPACK__imported__module__$5b$project$5d2f$node_modules$2f$lucide$2d$react$2f$dist$2f$esm$2f$icons$2f$languages$2e$js__$5b$app$2d$ssr$5d$__$28$ecmascript$29$__$3c$export__default__as__Languages$3e$__ = __turbopack_context__.i("[project]/node_modules/lucide-react/dist/esm/icons/languages.js [app-ssr] (ecmascript) <export default as Languages>");
var __TURBOPACK__imported__module__$5b$project$5d2f$src$2f$lib$2f$utils$2e$ts__$5b$app$2d$ssr$5d$__$28$ecmascript$29$__ = __turbopack_context__.i("[project]/src/lib/utils.ts [app-ssr] (ecmascript)");
var __TURBOPACK__imported__module__$5b$project$5d2f$src$2f$components$2f$atelier$2f$i18n$2f$use$2d$i18n$2e$ts__$5b$app$2d$ssr$5d$__$28$ecmascript$29$__ = __turbopack_context__.i("[project]/src/components/atelier/i18n/use-i18n.ts [app-ssr] (ecmascript)");
var __TURBOPACK__imported__module__$5b$project$5d2f$src$2f$components$2f$atelier$2f$i18n$2f$core$2e$ts__$5b$app$2d$ssr$5d$__$28$ecmascript$29$__ = __turbopack_context__.i("[project]/src/components/atelier/i18n/core.ts [app-ssr] (ecmascript)");
"use client";
;
;
;
;
;
const CHOICES = [
    {
        value: "en",
        short: "EN"
    },
    {
        value: "fr",
        short: "FR"
    }
];
function LanguageToggle({ className }) {
    const { lang, setLang, t } = (0, __TURBOPACK__imported__module__$5b$project$5d2f$src$2f$components$2f$atelier$2f$i18n$2f$use$2d$i18n$2e$ts__$5b$app$2d$ssr$5d$__$28$ecmascript$29$__["useI18n"])();
    return /*#__PURE__*/ (0, __TURBOPACK__imported__module__$5b$project$5d2f$node_modules$2f$next$2f$dist$2f$server$2f$route$2d$modules$2f$app$2d$page$2f$vendored$2f$ssr$2f$react$2d$jsx$2d$dev$2d$runtime$2e$js__$5b$app$2d$ssr$5d$__$28$ecmascript$29$__["jsxDEV"])("div", {
        role: "group",
        "aria-label": t("shell.languageAria"),
        title: lang === "fr" ? (0, __TURBOPACK__imported__module__$5b$project$5d2f$src$2f$components$2f$atelier$2f$i18n$2f$core$2e$ts__$5b$app$2d$ssr$5d$__$28$ecmascript$29$__["translate"])("shell.languageTitle", "en") : (0, __TURBOPACK__imported__module__$5b$project$5d2f$src$2f$components$2f$atelier$2f$i18n$2f$core$2e$ts__$5b$app$2d$ssr$5d$__$28$ecmascript$29$__["translate"])("shell.languageTitle", "fr"),
        className: (0, __TURBOPACK__imported__module__$5b$project$5d2f$src$2f$lib$2f$utils$2e$ts__$5b$app$2d$ssr$5d$__$28$ecmascript$29$__["cn"])("flex items-center gap-0.5 rounded-full border bg-background p-0.5 select-none", className),
        children: [
            /*#__PURE__*/ (0, __TURBOPACK__imported__module__$5b$project$5d2f$node_modules$2f$next$2f$dist$2f$server$2f$route$2d$modules$2f$app$2d$page$2f$vendored$2f$ssr$2f$react$2d$jsx$2d$dev$2d$runtime$2e$js__$5b$app$2d$ssr$5d$__$28$ecmascript$29$__["jsxDEV"])(__TURBOPACK__imported__module__$5b$project$5d2f$node_modules$2f$lucide$2d$react$2f$dist$2f$esm$2f$icons$2f$languages$2e$js__$5b$app$2d$ssr$5d$__$28$ecmascript$29$__$3c$export__default__as__Languages$3e$__["Languages"], {
                className: "hidden sm:block size-3.5 ml-1 text-muted-foreground",
                "aria-hidden": "true"
            }, void 0, false, {
                fileName: "[project]/src/components/atelier/i18n/language-toggle.tsx",
                lineNumber: 29,
                columnNumber: 7
            }, this),
            CHOICES.map((choice)=>{
                const active = lang === choice.value;
                return /*#__PURE__*/ (0, __TURBOPACK__imported__module__$5b$project$5d2f$node_modules$2f$next$2f$dist$2f$server$2f$route$2d$modules$2f$app$2d$page$2f$vendored$2f$ssr$2f$react$2d$jsx$2d$dev$2d$runtime$2e$js__$5b$app$2d$ssr$5d$__$28$ecmascript$29$__["jsxDEV"])("button", {
                    type: "button",
                    onClick: ()=>setLang(choice.value),
                    "aria-pressed": active,
                    className: (0, __TURBOPACK__imported__module__$5b$project$5d2f$src$2f$lib$2f$utils$2e$ts__$5b$app$2d$ssr$5d$__$28$ecmascript$29$__["cn"])("rounded-full px-1 sm:px-2 py-0.5 text-[10px] sm:text-[11px] font-bold uppercase tracking-wide transition-colors", active ? "bg-primary text-primary-foreground" : "text-muted-foreground hover:text-foreground"),
                    children: choice.short
                }, choice.value, false, {
                    fileName: "[project]/src/components/atelier/i18n/language-toggle.tsx",
                    lineNumber: 33,
                    columnNumber: 11
                }, this);
            })
        ]
    }, void 0, true, {
        fileName: "[project]/src/components/atelier/i18n/language-toggle.tsx",
        lineNumber: 20,
        columnNumber: 5
    }, this);
}
}),
];

//# sourceMappingURL=src_components_atelier_i18n_bb686d00._.js.map