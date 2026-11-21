<?php

use App\Models\DailyReportSetting;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;
use Illuminate\Support\Facades\Schema;

/*
|--------------------------------------------------------------------------
| Console Routes
|--------------------------------------------------------------------------
|
| Closure based commands live here alongside the scheduled tasks.
|
| Daily business-health e-mail: fires `atelier:daily-report` every day at
| the send time configured on the Daily Report settings page (business
| timezone). Requires ONE server cron entry running
| `php artisan schedule:run` every minute — see DEPLOYMENT.md §7. The
| settings lookup is guarded so artisan still works before the first
| migration.
|
*/

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

$reportTime = '07:00';
$reportTimezone = 'Africa/Douala';

try {
    if (Schema::hasTable('daily_report_settings')) {
        $settings = DailyReportSetting::query()->find(1);

        if ($settings instanceof DailyReportSetting) {
            $reportTime = $settings->sendTime();
            $reportTimezone = $settings->timezone()->getName();
        }
    }
} catch (\Throwable) {
    // Fresh installation (table not migrated yet) — defaults apply.
}

Schedule::command('atelier:daily-report --trigger=scheduled')
    ->dailyAt($reportTime)
    ->timezone($reportTimezone);
