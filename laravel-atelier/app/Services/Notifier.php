<?php

namespace App\Services;

use App\Models\Notification;
use App\Models\User;
use App\Support\Atelier;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * OSSZ ATELIER — internal notifications (no external SMS/WhatsApp/Email).
 *
 * Ported from src/lib/atelier/notify.ts. The stored title is always the
 * ENGLISH title (matching the `en` values of labels.notifTitle.* in
 * lang/en/labels.php, which mirror the TS dict common.ts) so rows stay
 * readable in the database regardless of the generating user's locale.
 * Views render __('labels.notifTitle.'.$type) in the reader's locale and
 * fall back to the stored title.
 *
 * All methods are best-effort: failures are logged but never bubble up,
 * mirroring the try/catch behaviour of the reference implementation.
 */
final class Notifier
{
    /**
     * English notification titles (verbatim EN values of notifTitle.* in
     * src/components/atelier/i18n/dict/common.ts).
     */
    private const EN_TITLES = [
        'TASK_ASSIGNED' => 'New task assigned',
        'TASK_REASSIGNED' => 'Task reassigned to you',
        'DEADLINE_CHANGED' => 'Deadline changed',
        'NEW_INSTRUCTIONS' => 'New instructions',
        'CORRECTIONS_REQUESTED' => 'Corrections requested',
        'BLOCK_RESOLVED' => 'Block resolved',
        'TASK_APPROVED' => 'Work approved',
        'TASK_REOPENED' => 'Task reopened',
        'SUBMITTED_FOR_INSPECTION' => 'Work submitted for inspection',
        'TASK_OVERDUE' => 'Task overdue',
        'ISSUE_REPORTED' => 'Production issue reported',
        'TASK_BLOCKED' => 'Task blocked',
        'READY_FOR_PICKUP' => 'Ready for pickup',
        'TASK_DELIVERED' => 'Garment delivered/collected',
        'TASK_CANCELLED' => 'Task cancelled',
    ];

    /**
     * Notify a list of users (= notifyUsers() in notify.ts).
     *
     * @param  list<string>  $userIds
     */
    public static function users(array $userIds, string $type, ?string $message = null, ?string $taskId = null, ?string $orderId = null): void
    {
        $unique = array_values(array_unique(array_filter($userIds, static fn ($id) => (string) $id !== '')));

        if ($unique === []) {
            return;
        }

        try {
            foreach ($unique as $userId) {
                Notification::query()->create([
                    'user_id' => $userId,
                    'type' => $type,
                    'title' => self::englishTitle($type),
                    'message' => $message,
                    'task_id' => $taskId,
                    'order_id' => $orderId,
                    'is_read' => false,
                ]);
            }
        } catch (Throwable $e) {
            Log::error('[notify] failed to create notifications', ['type' => $type, 'error' => $e->getMessage()]);
        }
    }

    /**
     * Notify every ACTIVE user holding one of the given roles
     * (= notifyRoles() in notify.ts).
     *
     * @param  list<string>  $roles  subset of Atelier::ROLES
     */
    public static function roles(array $roles, string $type, ?string $message = null, ?string $taskId = null, ?string $orderId = null): void
    {
        try {
            $userIds = User::query()
                ->where('is_active', true)
                ->whereIn('role', array_values($roles))
                ->pluck('id')
                ->all();

            self::users($userIds, $type, $message, $taskId, $orderId);
        } catch (Throwable $e) {
            Log::error('[notifyRoles] failed to resolve recipients', ['type' => $type, 'error' => $e->getMessage()]);
        }
    }

    /**
     * Notify every active MANAGEMENT and ADMIN account
     * (= notifyManagement() in notify.ts).
     */
    public static function management(string $type, ?string $message = null, ?string $taskId = null, ?string $orderId = null): void
    {
        self::roles(['MANAGEMENT', 'ADMIN'], $type, $message, $taskId, $orderId);
    }

    /**
     * The English title stored on the row: the EN_TITLES map, falling back
     * to the lang/en file, falling back to the raw type code.
     */
    private static function englishTitle(string $type): string
    {
        if (isset(self::EN_TITLES[$type])) {
            return self::EN_TITLES[$type];
        }

        if (in_array($type, Atelier::NOTIFICATION_TYPES, true)) {
            $translated = trans('labels.notifTitle.'.$type, [], 'en');

            if (is_string($translated) && $translated !== 'labels.notifTitle.'.$type) {
                return $translated;
            }
        }

        return $type;
    }
}
