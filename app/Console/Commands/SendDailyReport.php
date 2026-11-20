<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Mail\DailyHealthReport;
use App\Models\DailyReportLog;
use App\Models\DailyReportRecipient;
use App\Models\DailyReportSetting;
use App\Services\Audit;
use App\Services\DailyReportService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Throwable;

/**
 * Sends the automated daily business-health report e-mail.
 *
 * Three trigger types:
 *  - SCHEDULED — fired by the Laravel scheduler (routes/console.php) or a
 *    direct cPanel cron. Honours the enable flag and refuses to double
 *    send when a scheduled report already went out today.
 *  - MANUAL    — "Send Now" from the Daily Report settings page (ignores
 *    the duplicate guard on purpose).
 *  - TEST      — test e-mail to a single address (the requesting
 *    Management user or --to).
 *
 * E-mails are sent per recipient so one failing address never blocks the
 * others; every run is recorded in daily_report_logs.
 */
class SendDailyReport extends Command
{
    protected $signature = 'atelier:daily-report
                            {--trigger=scheduled : SCHEDULED, MANUAL or TEST}
                            {--to=* : Override recipient e-mail address(es) (comma-separated)}';

    protected $description = 'Send the daily business-health report e-mail to the distribution list';

    public function handle(DailyReportService $service): int
    {
        $settings = DailyReportSetting::get();

        $trigger = strtoupper(trim((string) $this->option('trigger')));
        $trigger = in_array($trigger, DailyReportLog::TRIGGERS, true) ? $trigger : 'SCHEDULED';

        $now = now($settings->timezone());

        if ($trigger === 'SCHEDULED' && ! $settings->enabled) {
            $this->info('Daily report is disabled — nothing sent.');

            return self::SUCCESS;
        }

        // Duplicate guard: exactly one scheduled report per day, even when
        // both the scheduler and a direct cron are installed.
        if ($trigger === 'SCHEDULED' && DailyReportLog::scheduledAlreadySent($now)) {
            $this->info('A scheduled report was already sent for '.$now->toDateString().' — skipping.');

            return self::SUCCESS;
        }

        // Recipients: --to override, else the active distribution list.
        $override = array_filter(array_map(
            static fn (string $value): string => strtolower(trim(explode(',', $value)[0])),
            (array) $this->option('to'),
        ));

        if ($override !== []) {
            $recipients = array_map(
                static fn (string $email): DailyReportRecipient => new DailyReportRecipient(['email' => $email]),
                $override,
            );
        } else {
            $recipients = DailyReportRecipient::query()->active()->orderBy('email')->get()->all();
        }

        if ($recipients === []) {
            $this->warn('No active daily-report recipients — add one under Administration → Daily Report.');

            return self::SUCCESS;
        }

        $report = $service->build($settings);
        $summary = $service->summaryOf($report);

        $success = 0;
        $failures = 0;
        $firstError = null;

        foreach ($recipients as $recipient) {
            try {
                Mail::to($recipient->email)->send(new DailyHealthReport($report, $settings, $recipient));
                $success++;
            } catch (Throwable $e) {
                $failures++;
                $firstError === null and $firstError = $e->getMessage();

                Log::error('[daily-report] delivery failed', [
                    'email' => $recipient->email,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        $status = $failures === 0 ? 'SENT' : ($success > 0 ? 'PARTIAL' : 'FAILED');

        DailyReportLog::query()->create([
            'report_date' => $now->toDateString(),
            'trigger_type' => $trigger,
            'status' => $status,
            'recipient_count' => count($recipients),
            'success_count' => $success,
            'failure_count' => $failures,
            'recipients' => implode(', ', array_map(
                static fn (DailyReportRecipient $r): string => $r->email,
                $recipients,
            )),
            'error' => $firstError !== null ? mb_substr($firstError, 0, 1000) : null,
            'summary' => json_encode($summary),
            'sent_at' => now(),
        ]);

        if ($success > 0) {
            $settings->forceFill(['last_sent_at' => now()])->save();
        }

        Audit::log(
            null,
            'DAILY_REPORT_SENT',
            'DAILY_REPORT',
            null,
            ['trigger' => $trigger, 'status' => $status, 'success' => $success, 'failures' => $failures],
        );

        $this->info("Daily report [{$status}] — {$success} delivered, {$failures} failed.");

        return $failures === 0 || $success > 0 ? self::SUCCESS : self::FAILURE;
    }
}
