<?php

// OSSZ ATELIER — EN strings for the daily business-health e-mail
// (Administration → Daily Report). Accessed via __('dailyreport.key').

return [
    'title' => 'Daily Email Report',
    'description' => 'Automated daily business-health report sent to the configured distribution list.',
    'pageAria' => 'Daily email report settings',

    // Header actions
    'sendNow' => 'Send Report Now',
    'sendTest' => 'Send Test to My Inbox',

    // Scheduling note
    'cronNoteTitle' => 'Scheduling requirement',
    'cronNote' => 'The report sends automatically once per day at the configured time (business timezone). This needs one cron job on the server running «php artisan schedule:run» every minute — see DEPLOYMENT.md section 7. The buttons above always send manually, regardless of the schedule.',

    // Settings card
    'settingsTitle' => 'Schedule & Content',
    'settingsDescription' => 'When the report is generated and what it contains.',
    'enabled' => 'Daily report enabled',
    'enabledHint' => 'When disabled, the scheduled send is skipped — manual sends still work.',
    'sendTime' => 'Send time',
    'timezone' => 'Business timezone',
    'replyTo' => 'Reply-to address',
    'sections' => 'Included sections',
    'sectionProduction' => 'Production health',
    'sectionFinancial' => 'Financial health (month to date)',
    'sectionStaff' => 'Staff workload',
    'sectionAlerts' => 'Attention list (overdue, blocked, deliveries due)',

    // Preview card
    'previewTitle' => 'Today’s Report Preview',
    'previewDescription' => 'Live figures — exactly what the next e-mail will contain.',
    'kpiNewOrders' => 'New orders',
    'kpiPiecesCompleted' => 'Pieces completed',
    'kpiPaymentsIn' => 'Payments in',
    'kpiExpensesOut' => 'Expenses out',
    'prodOrdersInProduction' => 'Orders in production',
    'prodActiveTasks' => 'Active tasks',
    'prodOverdue' => 'Overdue pieces',
    'prodBlocked' => 'Blocked',
    'prodOnTime' => 'On-time (30 days)',
    'finPaymentsMtd' => 'Payments received (MTD)',
    'finOutstanding' => 'Outstanding balances',
    'finExpensesMtd' => 'Expenses (MTD)',
    'finNetCash' => 'Net cash movement (MTD)',
    'previewUnavailable' => 'Preview is temporarily unavailable.',

    // Recipients card
    'recipientsTitle' => 'Recipients',
    'recipientsDescription' => 'Distribution list for the daily report. info@osszcollection.com is active by default; add or remove addresses below.',
    'addRecipient' => 'Add Recipient',
    'email' => 'Email address',
    'nameLabel' => 'Name',
    'colRecipient' => 'Recipient',
    'colStatus' => 'Status',
    'colAddedBy' => 'Added by',
    'colActions' => 'Actions',
    'active' => 'Active',
    'inactive' => 'Inactive',
    'remove' => 'Remove',
    'removeAria' => 'Remove recipient {email}',
    'toggleAria' => 'Activate or deactivate {email}',
    'addDescription' => 'The address will receive the daily business-health report immediately from the next send.',
    'emptyTitle' => 'No recipients yet',
    'emptyDescription' => 'Add at least one e-mail address to receive the daily report.',

    // History card
    'historyTitle' => 'Send History',
    'historyDescription' => 'Last 15 delivery runs.',
    'colDate' => 'Sent',
    'colTrigger' => 'Trigger',
    'colStatus' => 'Status',
    'colResult' => 'Result',
    'triggerScheduled' => 'Scheduled',
    'triggerManual' => 'Manual',
    'triggerTest' => 'Test',
    'statusSent' => 'Delivered',
    'statusPartial' => 'Partial',
    'statusFailed' => 'Failed',
    'historyEmpty' => 'No reports sent yet.',
    'lastSent' => 'Last successful send',
    'never' => 'Never',
    'toLabel' => 'to',

    // Confirm dialog (Send Now)
    'sendConfirmTitle' => 'Send report now',
    'sendConfirm' => 'Send the daily business-health report to all {count} active recipient(s) right now?',
    'confirmSend' => 'Send Now',

    // Remove dialog
    'removeConfirmTitle' => 'Remove recipient',
    'removeConfirm' => 'Remove {email} from the daily report distribution list?',
    'confirmRemove' => 'Remove Recipient',

    // Validation + flashes
    'errTimezone' => 'Please choose a valid timezone.',
    'settingsSaved' => 'Report settings saved.',
    'recipientAdded' => 'Recipient added to the daily report list.',
    'recipientRemoved' => 'Recipient removed from the daily report list.',
    'recipientActivated' => 'Recipient activated — they will receive the daily report.',
    'recipientDeactivated' => 'Recipient deactivated — they will no longer receive the daily report.',
    'sentNow' => 'Daily report sent to all active recipients.',
    'testSent' => 'Test report sent to your inbox.',
    'partialSent' => 'Report sent to {success} of {total} recipients — check the send history for the failed address.',
    'errNoRecipients' => 'There are no active recipients — add one first.',
    'errSendFailed' => 'The report could not be sent. Verify the mail settings in .env (see DEPLOYMENT.md section 6).',
];
