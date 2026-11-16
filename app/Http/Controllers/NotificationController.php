<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Notification;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Internal notifications for the signed-in account (spec §3: /notifications).
 *
 * Ported from src/app/api/atelier/notifications/route.ts:
 * - GET  returns the account's own notifications, latest first;
 * - PATCH (here POST /notifications/read) marks one notification — or every
 *   unread one when `all` is truthy — as read, always scoped to the owner.
 *
 * Deliberately does not extend a base Controller class, mirroring
 * LoginController / LocaleController.
 */
class NotificationController
{
    /** Rows per page on the notifications screen. */
    private const PAGE_SIZE = 20;

    /**
     * GET /notifications — the signed-in user's notification history.
     *
     * Only rows addressed to $me are ever visible; the query string is kept
     * across pagination links.
     */
    public function index(Request $request): View
    {
        /** @var User $me */
        $me = $request->user();

        $notifications = Notification::query()
            ->where('user_id', $me->id)
            ->orderByDesc('created_at')
            ->paginate(self::PAGE_SIZE)
            ->withQueryString();

        return view('notifications.index', [
            'notifications' => $notifications,
        ]);
    }

    /**
     * POST /notifications/read — mark one or all notifications as read.
     *
     * Input contract (view contract §NotificationController): a single `id`
     * or a truthy `all` flag (1 / true / "on" — accepted via boolean()).
     * Single-id updates are always scoped to the owner so nobody can touch
     * another account's notifications. No toast on success, matching the
     * reference implementation.
     */
    public function read(Request $request): RedirectResponse
    {
        /** @var User $me */
        $me = $request->user();

        if ($request->boolean('all')) {
            Notification::query()
                ->where('user_id', $me->id)
                ->where('is_read', false)
                ->update(['is_read' => true]);
        } else {
            $id = trim((string) $request->input('id', ''));

            if ($id !== '') {
                Notification::query()
                    ->where('user_id', $me->id)
                    ->where('id', $id)
                    ->update(['is_read' => true]);
            }
        }

        return redirect()->back();
    }
}
