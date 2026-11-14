// OSSZ ATELIER — bilingual entries for the notifications module.

import type { DictEntries } from "../core";

const entries: DictEntries = {
  "notif.title": { en: "Notifications", fr: "Notifications" },
  "notif.desc": { en: "Production alerts for your account.", fr: "Alertes de production pour votre compte." },
  "notif.markAll": { en: "Mark all as read", fr: "Tout marquer comme lu" },
  "notif.marking": { en: "Marking…", fr: "Marquage…" },
  "notif.tabAll": { en: "All ({count})", fr: "Toutes ({count})" },
  "notif.tabUnread": { en: "Unread", fr: "Non lues" },
  "notif.tabUnreadCount": { en: "Unread ({count})", fr: "Non lues ({count})" },
  "notif.emptyTitle": { en: "You're all caught up.", fr: "Vous êtes à jour." },
  "notif.emptyUnreadDesc": {
    en: "No unread notifications — new alerts will appear here.",
    fr: "Aucune notification non lue — les nouvelles alertes s'afficheront ici.",
  },
  "notif.emptyDesc": {
    en: "No notifications yet — production alerts will appear here.",
    fr: "Aucune notification pour le moment — les alertes de production s'afficheront ici.",
  },
  "notif.loadError": {
    en: "Could not load your notifications",
    fr: "Impossible de charger vos notifications",
  },
  "notif.unexpectedError": { en: "Unexpected error.", fr: "Erreur inattendue." },
  "notif.markOneError": {
    en: "Could not mark the notification as read",
    fr: "Impossible de marquer la notification comme lue",
  },
  "notif.markAllSuccess": {
    en: "All notifications marked as read",
    fr: "Toutes les notifications ont été marquées comme lues",
  },
  "notif.markAllError": {
    en: "Could not mark notifications as read",
    fr: "Impossible de marquer les notifications comme lues",
  },
};

export default entries;
