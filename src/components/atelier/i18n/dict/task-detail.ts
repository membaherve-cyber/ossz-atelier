// OSSZ ATELIER — bilingual entries for the task-detail module.

import type { DictEntries } from "../core";

const entries: DictEntries = {
  // --- Header, guards & generic feedback ---------------------------------------
  "td.backToTasks": { en: "Back to tasks", fr: "Retour aux tâches" },
  "td.goToTasks": { en: "Go to tasks", fr: "Aller aux tâches" },
  "td.loadFailed": { en: "Failed to load task.", fr: "Échec du chargement de la tâche." },
  "td.unknownError": { en: "Unknown error.", fr: "Erreur inconnue." },
  "td.unexpectedError": { en: "Unexpected error.", fr: "Erreur inattendue." },
  "td.actionFailed": { en: "Action failed", fr: "Échec de l'action" },
  "td.noTask.title": { en: "No task selected", fr: "Aucune tâche sélectionnée" },
  "td.noTask.desc": {
    en: "Open a task from the production board to see its details.",
    fr: "Ouvrez une tâche depuis le tableau de production pour consulter ses détails.",
  },
  "td.taskUnavailable": { en: "Task unavailable", fr: "Tâche indisponible" },
  "td.couldNotLoad": { en: "Could not load task", fr: "Impossible de charger la tâche" },

  // --- Header --------------------------------------------------------------------
  "td.assigneeColon": { en: "Assignee:", fr: "Affectée à :" },
  "td.unassigned": { en: "Unassigned", fr: "Non affectée" },
  "td.createdOn": { en: "Created {date}", fr: "Créée le {date}" },
  "td.blockedAlert": { en: "Task blocked", fr: "Tâche bloquée" },
  "td.cancelledAlert": { en: "Task cancelled", fr: "Tâche annulée" },
  "td.cancelledFallback": {
    en: "This task was cancelled.",
    fr: "Cette tâche a été annulée.",
  },
  "td.correctionsAlert": { en: "Corrections requested", fr: "Retouches demandées" },
  "td.correctionsAlertDesc": {
    en: "Corrections were requested — see inspection history below for details.",
    fr: "Des retouches ont été demandées — consultez l'historique des inspections ci-dessous pour plus de détails.",
  },

  // --- Progress & checklist ---------------------------------------------------------
  "td.progressChecklist": { en: "Progress & Checklist", fr: "Progression & Checklist" },
  "td.currentProgress": { en: "Current progress:", fr: "Progression actuelle :" },
  "td.updateProgressManually": {
    en: "Update progress manually",
    fr: "Mettre à jour la progression manuellement",
  },
  "td.aria.newProgress": {
    en: "New progress percentage",
    fr: "Nouveau pourcentage de progression",
  },
  "td.optionalNote": { en: "Optional note", fr: "Note facultative" },
  "td.aria.progressNote": { en: "Progress note", fr: "Note de progression" },
  "td.update": { en: "Update", fr: "Mettre à jour" },
  "td.manualProgressNote": {
    en: 'Supervisor correction — manual values are recorded in the history with source "{source}".',
    fr: "Correction de la direction — les valeurs saisies manuellement sont enregistrées dans l'historique avec la source « {source} ».",
  },
  "td.source.MANUAL": { en: "Manual", fr: "Manuel" },
  "td.source.STAGE": { en: "Stage", fr: "Étape" },
  "td.source.INSPECTION": { en: "Inspection", fr: "Inspection" },
  "td.checklistTitle": { en: "Production checklist", fr: "Checklist de production" },
  "td.pendingChange": { en: "1 pending change", fr: "1 modification en attente" },
  "td.pendingChanges": { en: "{count} pending changes", fr: "{count} modifications en attente" },
  "td.checklistHelp": {
    en: "Progress is recalculated from completed stages (in-progress counts half{weights}).",
    fr: "La progression est recalculée à partir des étapes terminées (une étape en cours compte pour moitié{weights}).",
  },
  "td.checklistHelpWeights": {
    en: "; heavier stages count proportionally more",
    fr: " ; les étapes plus lourdes comptent proportionnellement plus",
  },
  "td.noStages.title": { en: "No checklist stages", fr: "Aucune étape de checklist" },
  "td.noStages.desc": {
    en: "No production stages apply to this task category.",
    fr: "Aucune étape de production ne s'applique à la catégorie de cette tâche.",
  },
  "td.stageWeight": { en: "weight ×{weight}", fr: "poids ×{weight}" },
  "td.wasState": { en: "was {state}", fr: "état précédent : {state}" },
  "td.aria.stateFor": { en: "State for {stage}", fr: "État de {stage}" },
  "td.aria.setState": { en: "Set {stage} to {state}", fr: "Définir {stage} sur {state}" },
  "td.stageCommentPlaceholder": {
    en: "Optional comment attached to the next checklist save",
    fr: "Commentaire facultatif joint au prochain enregistrement de la checklist",
  },
  "td.aria.stageComment": {
    en: "Checklist save comment",
    fr: "Commentaire d'enregistrement de la checklist",
  },
  "td.saveChecklist": { en: "Save Checklist", fr: "Enregistrer la checklist" },

  // --- Workflow actions ---------------------------------------------------------------
  "td.workflowActions": { en: "Workflow Actions", fr: "Actions de workflow" },
  "td.submitForInspection": {
    en: "Submit for Inspection",
    fr: "Soumettre au contrôle qualité",
  },
  "td.reach100": {
    en: "Reach 100% progress first.",
    fr: "Atteignez d'abord 100 % de progression.",
  },
  "td.inspectResult": { en: "Inspect Result", fr: "Contrôler le résultat" },
  "td.markReady": { en: "Mark Ready for Pickup", fr: "Marquer prête pour retrait" },
  "td.markDelivered": { en: "Mark Delivered/Collected", fr: "Marquer livrée/récupérée" },
  "td.assignStaff": { en: "Assign Staff", fr: "Affecter du personnel" },
  "td.reassignStaff": { en: "Reassign Staff", fr: "Réaffecter du personnel" },
  "td.editDetails": { en: "Edit Details", fr: "Modifier les détails" },
  "td.blockTask": { en: "Block Task", fr: "Bloquer la tâche" },
  "td.unblockTask": { en: "Unblock Task", fr: "Débloquer la tâche" },
  "td.reopenTask": { en: "Reopen Task", fr: "Rouvrir la tâche" },
  "td.cancelTask": { en: "Cancel Task", fr: "Annuler la tâche" },
  "td.noActions": {
    en: "No actions available for this task",
    fr: "Aucune action disponible pour cette tâche",
  },
  "td.noActionsClosed": { en: " — it is closed.", fr: " — elle est clôturée." },

  // --- Comments & issues ------------------------------------------------------------------
  "td.commentsIssues": { en: "Comments & Issues", fr: "Commentaires & Problèmes" },
  "td.commentsTotal": { en: "{count} total", fr: "{count} au total" },
  "td.noComments.title": { en: "No notes yet", fr: "Aucune note pour le moment" },
  "td.noComments.desc": {
    en: "Production notes and reported issues will appear here.",
    fr: "Les notes de production et les problèmes signalés apparaîtront ici.",
  },
  "td.commentPlaceholder": {
    en: "Write a production note or describe a problem...",
    fr: "Rédigez une note de production ou décrivez un problème…",
  },
  "td.aria.newComment": { en: "New comment", fr: "Nouveau commentaire" },
  "td.addNote": { en: "Add Note", fr: "Ajouter une note" },
  "td.reportIssue": { en: "Report Issue", fr: "Signaler un problème" },

  // --- Task details card ---------------------------------------------------------------------
  "td.taskDetails": { en: "Task Details", fr: "Détails de la tâche" },
  "td.meta.order": { en: "Order", fr: "Commande" },
  "td.meta.piece": { en: "Garment piece", fr: "Pièce de vêtement" },
  "td.meta.assignee": { en: "Assignee", fr: "Affectée à" },
  "td.meta.category": { en: "Category", fr: "Catégorie" },
  "td.meta.priority": { en: "Priority", fr: "Priorité" },
  "td.meta.deadline": { en: "Deadline", fr: "Échéance" },
  "td.meta.created": { en: "Created", fr: "Créée le" },
  "td.meta.completed": { en: "Completed", fr: "Terminée le" },
  "td.meta.delivered": { en: "Delivered", fr: "Livrée le" },
  "td.instructions": { en: "Instructions", fr: "Instructions" },

  // --- History timeline ------------------------------------------------------------------------
  "td.history": { en: "History", fr: "Historique" },
  "td.eventsCount": { en: "{count} events", fr: "{count} événements" },
  "td.tab.progress": { en: "Progress", fr: "Progression" },
  "td.tab.checklist": { en: "Checklist", fr: "Checklist" },
  "td.tab.status": { en: "Status", fr: "Statut" },
  "td.tab.inspections": { en: "Inspections", fr: "Inspections" },
  "td.noEvents": { en: "No events recorded", fr: "Aucun événement enregistré" },
  "td.hist.assigned": { en: "assigned the task to {name}", fr: "a affecté la tâche à {name}" },
  "td.hist.reassigned": {
    en: "reassigned the task to {name}",
    fr: "a réaffecté la tâche à {name}",
  },
  "td.hist.statusChange": {
    en: "changed status from {from} to {to}",
    fr: "a changé le statut de {from} à {to}",
  },
  "td.hist.stageChange": {
    en: "{stage}: {from} → {to}",
    fr: "{stage} : {from} → {to}",
  },
  "td.hist.progress": {
    en: "updated progress {from}% → {to}%",
    fr: "a mis à jour la progression : {from} % → {to} %",
  },
  "td.hist.inspection": {
    en: "inspection decision — {decision}",
    fr: "décision d'inspection — {decision}",
  },
  "td.hist.issue": { en: "reported an issue", fr: "a signalé un problème" },
  "td.hist.note": { en: "added a note", fr: "a ajouté une note" },

  // --- Validation & toasts -----------------------------------------------------------------------
  "td.toast.selectStaff": {
    en: "Select a staff member",
    fr: "Sélectionnez un membre du personnel",
  },
  "td.toast.selectStaffDesc": {
    en: "Choose the account responsible for this garment piece.",
    fr: "Choisissez le compte responsable de cette pièce.",
  },
  "td.toast.reasonRequired": { en: "A reason is required", fr: "Un motif est obligatoire" },
  "td.toast.reasonRequiredDesc": {
    en: "Provide {field} (at least {min} characters).",
    fr: "Indiquez {field} (au moins {min} caractères).",
  },
  "td.reason.block": {
    en: "the reason production is blocked",
    fr: "le motif du blocage de la production",
  },
  "td.reason.cancel": { en: "the cancellation reason", fr: "le motif de l'annulation" },
  "td.toast.assignedTo": { en: "Assigned to {name}", fr: "Affectée à {name}" },
  "td.toast.reassignedTo": { en: "Reassigned to {name}", fr: "Réaffectée à {name}" },
  "td.staffMember": { en: "staff member", fr: "un membre du personnel" },
  "td.toast.pieceRequired": {
    en: "Garment piece name is required",
    fr: "Le nom de la pièce est obligatoire",
  },
  "td.toast.nothingToUpdate": { en: "Nothing to update", fr: "Rien à mettre à jour" },
  "td.toast.nothingToUpdateDesc": {
    en: "No field was changed.",
    fr: "Aucun champ n'a été modifié.",
  },
  "td.toast.detailsUpdated": {
    en: "Task details updated",
    fr: "Détails de la tâche mis à jour",
  },
  "td.toast.submitted": {
    en: "Submitted for inspection",
    fr: "Soumise au contrôle qualité",
  },
  "td.toast.inspectionApproved": {
    en: "Inspection approved",
    fr: "Inspection approuvée",
  },
  "td.toast.correctionsRequired": {
    en: "Correction instructions required",
    fr: "Instructions de retouche obligatoires",
  },
  "td.toast.correctionsRequiredDesc": {
    en: "Describe what needs to be corrected (at least 3 characters).",
    fr: "Décrivez les corrections à apporter (au moins 3 caractères).",
  },
  "td.toast.correctionsRequested": {
    en: "Corrections requested",
    fr: "Retouches demandées",
  },
  "td.toast.ready": { en: "Marked ready for pickup", fr: "Marquée prête pour retrait" },
  "td.toast.delivered": {
    en: "Marked as delivered/collected",
    fr: "Marquée comme livrée/récupérée",
  },
  "td.toast.reopenCommentsRequired": {
    en: "Comments are required",
    fr: "Des commentaires sont obligatoires",
  },
  "td.toast.reopenCommentsRequiredDesc": {
    en: "Explain why this task is being reopened (at least 3 characters).",
    fr: "Expliquez pourquoi cette tâche est rouverte (au moins 3 caractères).",
  },
  "td.toast.reopened": { en: "Task reopened", fr: "Tâche rouverte" },
  "td.toast.blocked": { en: "Task blocked", fr: "Tâche bloquée" },
  "td.toast.unblocked": { en: "Task unblocked", fr: "Tâche débloquée" },
  "td.toast.cancelled": { en: "Task cancelled", fr: "Tâche annulée" },
  "td.toast.progressUpdated": {
    en: "Progress updated to {value}%",
    fr: "Progression mise à jour à {value} %",
  },
  "td.toast.progressFailed": {
    en: "Could not update progress",
    fr: "Impossible de mettre à jour la progression",
  },
  "td.toast.checklistUpdated": { en: "Checklist updated", fr: "Checklist mise à jour" },
  "td.toast.checklistUpdatedDesc": {
    en: "Progress was recalculated from the completed stages.",
    fr: "La progression a été recalculée à partir des étapes terminées.",
  },
  "td.toast.checklistFailed": {
    en: "Could not save checklist",
    fr: "Impossible d'enregistrer la checklist",
  },
  "td.toast.emptyComment": { en: "Write a message first", fr: "Rédigez d'abord un message" },
  "td.toast.emptyCommentIssue": {
    en: "Describe the problem you encountered.",
    fr: "Décrivez le problème rencontré.",
  },
  "td.toast.emptyCommentNote": {
    en: "Type a note before sending.",
    fr: "Saisissez une note avant d'envoyer.",
  },
  "td.toast.issueReported": { en: "Issue reported", fr: "Problème signalé" },
  "td.toast.issueReportedDesc": {
    en: "Management has been notified.",
    fr: "La direction a été notifiée.",
  },
  "td.toast.noteAdded": { en: "Note added", fr: "Note ajoutée" },
  "td.toast.commentFailed": {
    en: "Could not add comment",
    fr: "Impossible d'ajouter le commentaire",
  },

  // --- Dialogs -----------------------------------------------------------------------------------------
  "td.dialog.assignTitle": { en: "Assign Task", fr: "Affecter la tâche" },
  "td.dialog.reassignTitle": { en: "Reassign Task", fr: "Réaffecter la tâche" },
  "td.dialog.assignDesc": {
    en: "Select the staff member responsible for {piece} ({code}).",
    fr: "Sélectionnez le membre du personnel responsable de {piece} ({code}).",
  },
  "td.dialog.staffMember": { en: "Staff member", fr: "Membre du personnel" },
  "td.dialog.selectStaff": {
    en: "Select staff member",
    fr: "Sélectionner un membre du personnel",
  },
  "td.dialog.noStaff": {
    en: "No staff accounts available",
    fr: "Aucun compte de personnel disponible",
  },
  "td.dialog.activeTasks": { en: "({count} active)", fr: "({count} en cours)" },
  "td.dialog.currentAssignee": { en: " — current", fr: " — actuellement affecté" },
  "td.dialog.noteOptional": { en: "Note (optional)", fr: "Note (facultative)" },
  "td.dialog.assignNotePlaceholder": {
    en: "Note sent with the assignment notification",
    fr: "Note envoyée avec la notification d'affectation",
  },
  "td.dialog.assignAction": { en: "Assign", fr: "Affecter" },
  "td.dialog.reassignAction": { en: "Reassign", fr: "Réaffecter" },
  "td.dialog.editTitle": { en: "Edit Task Details", fr: "Modifier les détails de la tâche" },
  "td.dialog.editDesc": {
    en: "Only the fields you change are submitted.",
    fr: "Seuls les champs modifiés seront transmis.",
  },
  "td.dialog.instructionsPlaceholder": {
    en: "Production instructions for this piece",
    fr: "Instructions de production pour cette pièce",
  },
  "td.dialog.submitDescSupervisor": {
    en: "The task moves to Awaiting Inspection for quality control.",
    fr: "La tâche passe en attente d'inspection pour le contrôle qualité.",
  },
  "td.dialog.submitDescStaff": {
    en: "Your work will be reviewed by quality control. Make sure the checklist is complete.",
    fr: "Votre travail sera examiné par le contrôle qualité. Assurez-vous que la checklist est complète.",
  },
  "td.dialog.submitNotePlaceholder": {
    en: "Anything the inspector should know",
    fr: "Tout ce que le contrôleur doit savoir",
  },
  "td.dialog.submitAction": { en: "Submit", fr: "Soumettre" },
  "td.dialog.inspectTitle": { en: "Quality Inspection", fr: "Contrôle qualité" },
  "td.dialog.inspectDesc": {
    en: "Approve passes the garment to the pickup flow. Request changes returns it to the worker with correction instructions.",
    fr: "Approuver envoie le vêtement au flux de retrait. Demander des retouches le renvoie à l'ouvrier avec des instructions de correction.",
  },
  "td.dialog.inspectionComments": {
    en: "Inspection comments",
    fr: "Commentaires d'inspection",
  },
  "td.dialog.inspectPlaceholder": {
    en: "Required when requesting changes — describe the corrections needed",
    fr: "Obligatoire pour demander des retouches — décrivez les corrections nécessaires",
  },
  "td.dialog.requestChanges": { en: "Request Changes", fr: "Demander des retouches" },
  "td.dialog.approve": { en: "Approve", fr: "Approuver" },
  "td.dialog.deliverDesc": {
    en: "Confirm that {customer} collected {piece}.",
    fr: "Confirmez que {customer} a récupéré {piece}.",
  },
  "td.dialog.deliverNotePlaceholder": {
    en: "e.g. Collected by the customer in person",
    fr: "ex. Récupérée par le client en personne",
  },
  "td.dialog.markDeliveredAction": { en: "Mark Delivered", fr: "Marquer comme livrée" },
  "td.dialog.reopenDesc": {
    en: "The task returns to Ongoing so production can resume. Comments are required.",
    fr: "La tâche repasse en cours afin que la production puisse reprendre. Des commentaires sont obligatoires.",
  },
  "td.dialog.comments": { en: "Comments", fr: "Commentaires" },
  "td.dialog.reopenPlaceholder": {
    en: "Why is this task being reopened?",
    fr: "Pourquoi cette tâche est-elle rouverte ?",
  },
  "td.dialog.reopenAction": { en: "Reopen", fr: "Rouvrir" },
  "td.dialog.blockDesc": {
    en: "Report what is blocking production. Management is notified immediately.",
    fr: "Indiquez ce qui bloque la production. La direction est notifiée immédiatement.",
  },
  "td.dialog.reasonRequired": { en: "Reason (required)", fr: "Motif (obligatoire)" },
  "td.dialog.blockPlaceholder": {
    en: "e.g. Missing customer-provided fabric",
    fr: "ex. Le tissu fourni par le client est manquant",
  },
  "td.dialog.unblockDesc": {
    en: "The blocker is cleared and production can resume.",
    fr: "Le blocage est levé et la production peut reprendre.",
  },
  "td.dialog.unblockPlaceholder": {
    en: "How the blocker was resolved",
    fr: "Comment le blocage a été résolu",
  },
  "td.dialog.unblockAction": { en: "Unblock", fr: "Débloquer" },
  "td.dialog.cancelTitle": { en: "Cancel this task?", fr: "Annuler cette tâche ?" },
  "td.dialog.cancelDesc": {
    en: "{code} — {piece} ({order}) will be permanently closed. This cannot be undone.",
    fr: "{code} — {piece} ({order}) sera définitivement clôturée. Cette action est irréversible.",
  },
  "td.dialog.cancelPlaceholder": {
    en: "e.g. Cancelled by the customer",
    fr: "ex. Annulée par le client",
  },
  "td.dialog.keepTask": { en: "Keep Task", fr: "Conserver la tâche" },
};

export default entries;
