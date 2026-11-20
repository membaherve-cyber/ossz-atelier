<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\DailyReportLog;
use App\Models\DailyReportRecipient;
use App\Models\DailyReportSetting;
use App\Services\Audit;
use App\Services\DailyReportService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;
use Throwable;

/**
 * Daily business-health e-mail — settings page (GET /daily-report) plus
 * the recipients distribution-list management and the manual "Send now" /
 * "Send test" actions.
 *
 * Management and Admin only: every route is gated by perm:accounting.view
 * (the report contains confidential financial KPIs, so the permission
 * matrix keeps it away from STAFF — consistent with the accounting
 * module).
 *
 * The actual delivery runs through the `atelier:daily-report` command so
 * scheduled, manual and test sends share one code path and one log.
 */
class DailyReportController
{
    public function __construct(private readonly DailyReportService $service)
    {
    }

    /**
     * GET /daily-report — settings + recipients + send history + a live
     * preview of what today's report contains.
     */
    public function index(Request $request): View
    {
        /** @var \App\Models\User $me */
        $me = $request->user();

        $settings = DailyReportSetting::get();

        $preview = null;

        // Best-effort live preview (never blocks the page).
        try {
            $preview = $this->service->build($settings);
        } catch (Throwable $e) {
            Log::error('[daily-report] preview build failed', ['error' => $e->getMessage()]);
        }

        return view('dailyreport.index', [
            'settings' => $settings,
            'recipients' => DailyReportRecipient::query()->orderByDesc('is_active')->orderBy('email')->get(),
            'logs' => DailyReportLog::query()->orderByDesc('sent_at')->limit(15)->get(),
            'preview' => $preview,
            'timezones' => $this->timezoneOptions($settings),
            'me' => $me,
        ]);
    }

    /**
     * Curated timezone list for the settings form (business-relevant
     * zones first; the active setting is always present).
     *
     * @return list<string>
     */
    private function timezoneOptions(DailyReportSetting $settings): array
    {
        $zones = [
            'Africa/Douala',
            'Africa/Lagos',
            'Africa/Dakar',
            'Africa/Abidjan',
            'Africa/Nairobi',
            'Europe/Paris',
            'UTC',
        ];

        if (! in_array((string) $settings->report_timezone, $zones, true)) {
            $zones[] = (string) $settings->report_timezone;
        }

        return $zones;
    }

    /**
     * POST /daily-report/settings — save the schedule + section toggles.
     */
    public function updateSettings(Request $request): RedirectResponse
    {
        /** @var \App\Models\User $me */
        $me = $request->user();

        $data = $request->validate([
            'enabled' => ['nullable', 'boolean'],
            'sendTime' => ['required', 'regex:/^([01]\d|2[0-3]):[0-5]\d$/'],
            'reportTimezone' => ['required', 'string', function (string $attribute, mixed $value, \Closure $fail): void {
                if (! in_array($value, \DateTimeZone::listIdentifiers(), true)) {
                    $fail(__('dailyreport.errTimezone'));
                }
            }],
            'replyTo' => ['nullable', 'email', 'max:190'],
            'includeProduction' => ['nullable', 'boolean'],
            'includeFinancial' => ['nullable', 'boolean'],
            'includeStaff' => ['nullable', 'boolean'],
            'includeAlerts' => ['nullable', 'boolean'],
        ]);

        $settings = DailyReportSetting::get();

        $before = [
            'enabled' => (bool) $settings->enabled,
            'sendTime' => $settings->sendTime(),
            'timezone' => (string) $settings->report_timezone,
        ];

        $settings->fill([
            'enabled' => (bool) ($data['enabled'] ?? false),
            'send_time' => (string) $data['sendTime'],
            'report_timezone' => (string) $data['reportTimezone'],
            'reply_to' => $data['replyTo'] ?? null,
            'include_production' => (bool) ($data['includeProduction'] ?? false),
            'include_financial' => (bool) ($data['includeFinancial'] ?? false),
            'include_staff' => (bool) ($data['includeStaff'] ?? false),
            'include_alerts' => (bool) ($data['includeAlerts'] ?? false),
            'updated_by_id' => $me->id,
        ])->save();

        Audit::log($me, 'DAILY_REPORT_SETTINGS_UPDATED', 'DAILY_REPORT', null, [
            'before' => $before,
            'after' => [
                'enabled' => (bool) $settings->enabled,
                'sendTime' => $settings->sendTime(),
                'timezone' => (string) $settings->report_timezone,
            ],
        ]);

        return redirect()->route('dailyreport.index')->with('success', __('dailyreport.settingsSaved'));
    }

    /**
     * POST /daily-report/recipients — add a distribution-list address.
     */
    public function storeRecipient(Request $request): RedirectResponse
    {
        /** @var \App\Models\User $me */
        $me = $request->user();

        $data = $request->validate([
            'email' => ['required', 'email', 'max:190', 'unique:daily_report_recipients,email'],
            'name' => ['nullable', 'string', 'max:120'],
        ]);

        $recipient = DailyReportRecipient::query()->create([
            'email' => strtolower((string) $data['email']),
            'name' => $data['name'] ?? null,
            'is_active' => true,
            'added_by_id' => $me->id,
            'added_by_name' => $me->name,
        ]);

        Audit::log($me, 'DAILY_REPORT_RECIPIENT_ADDED', 'DAILY_REPORT_RECIPIENT', $recipient->id, [
            'email' => $recipient->email,
        ]);

        return redirect()->route('dailyreport.index')->with('success', __('dailyreport.recipientAdded'));
    }

    /**
     * POST /daily-report/recipients/{id} — toggle a recipient on/off.
     */
    public function updateRecipient(Request $request, string $id): RedirectResponse
    {
        /** @var \App\Models\User $me */
        $me = $request->user();

        /** @var DailyReportRecipient $recipient */
        $recipient = DailyReportRecipient::query()->findOrFail($id);

        $recipient->forceFill(['is_active' => ! $recipient->is_active])->save();

        Audit::log($me, 'DAILY_REPORT_RECIPIENT_UPDATED', 'DAILY_REPORT_RECIPIENT', $recipient->id, [
            'email' => $recipient->email,
            'isActive' => (bool) $recipient->is_active,
        ]);

        return redirect()->route('dailyreport.index')->with(
            'success',
            $recipient->is_active ? __('dailyreport.recipientActivated') : __('dailyreport.recipientDeactivated'),
        );
    }

    /**
     * POST /daily-report/recipients/{id}/delete — remove an address.
     */
    public function destroyRecipient(Request $request, string $id): RedirectResponse
    {
        /** @var \App\Models\User $me */
        $me = $request->user();

        /** @var DailyReportRecipient $recipient */
        $recipient = DailyReportRecipient::query()->findOrFail($id);

        $email = $recipient->email;
        $recipient->delete();

        Audit::log($me, 'DAILY_REPORT_RECIPIENT_REMOVED', 'DAILY_REPORT_RECIPIENT', $recipient->id, [
            'email' => $email,
        ]);

        return redirect()->route('dailyreport.index')->with('success', __('dailyreport.recipientRemoved'));
    }

    /**
     * POST /daily-report/send — send the report now to every active
     * recipient (trigger MANUAL — bypasses the one-per-day guard).
     */
    public function send(Request $request): RedirectResponse
    {
        if (DailyReportRecipient::query()->active()->count() === 0) {
            return redirect()->route('dailyreport.index')->with('error', __('dailyreport.errNoRecipients'));
        }

        return $this->runSendCommand(['--trigger' => 'MANUAL'], 'dailyreport.sentNow');
    }

    /**
     * POST /daily-report/send-test — test e-mail to the requesting
     * Management/Admin user's own inbox only (trigger TEST).
     */
    public function sendTest(Request $request): RedirectResponse
    {
        /** @var \App\Models\User $me */
        $me = $request->user();

        return $this->runSendCommand(
            ['--trigger' => 'TEST', '--to' => [$me->email]],
            'dailyreport.testSent',
        );
    }

    /**
     * Invoke the shared send command in-process and flash the outcome.
     *
     * @param  array<string, mixed>  $parameters
     */
    private function runSendCommand(array $parameters, string $successKey): RedirectResponse
    {
        try {
            Artisan::call('atelier:daily-report', $parameters);
        } catch (Throwable $e) {
            Log::error('[daily-report] manual send failed', ['error' => $e->getMessage()]);

            return redirect()->route('dailyreport.index')->with('error', __('dailyreport.errSendFailed'));
        }

        $latest = DailyReportLog::query()->latest('sent_at')->first();

        if ($latest === null) {
            return redirect()->route('dailyreport.index')->with('error', __('dailyreport.errSendFailed'));
        }

        if ($latest->status === 'FAILED') {
            return redirect()->route('dailyreport.index')->with(
                'error',
                __('dailyreport.errSendFailed').($latest->error ? ' — '.mb_substr((string) $latest->error, 0, 160) : ''),
            );
        }

        if ($latest->status === 'PARTIAL') {
            return redirect()->route('dailyreport.index')->with(
                'error',
                __('dailyreport.partialSent', [
                    'success' => (int) $latest->success_count,
                    'total' => (int) $latest->recipient_count,
                ]),
            );
        }

        return redirect()->route('dailyreport.index')->with('success', __($successKey));
    }
}
