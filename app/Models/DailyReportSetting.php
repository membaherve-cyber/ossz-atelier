<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Single-row (id = 1) configuration for the automated daily
 * business-health e-mail.
 *
 * Created + seeded by 2024_01_01_000032_create_daily_report_tables.
 * `DailyReportSetting::get()` always returns the row, creating it with
 * the documented defaults when a deployment somehow lost it.
 */
class DailyReportSetting extends Model
{
    protected $guarded = [];

    protected $casts = [
        'enabled' => 'boolean',
        'include_production' => 'boolean',
        'include_financial' => 'boolean',
        'include_staff' => 'boolean',
        'include_alerts' => 'boolean',
        'last_sent_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /** Valid section toggles (validated keys on the settings form). */
    public const SECTIONS = ['include_production', 'include_financial', 'include_staff', 'include_alerts'];

    /** Fetch the singleton settings row (lazily self-healing). */
    public static function get(): self
    {
        $row = self::query()->find(1);

        if ($row instanceof self) {
            return $row;
        }

        return self::query()->create([
            'id' => 1,
            'enabled' => true,
            'send_time' => '07:00',
            'report_timezone' => 'Africa/Douala',
        ]);
    }

    /** Business timezone for report generation + schedule firing. */
    public function timezone(): \DateTimeZone
    {
        $tz = (string) $this->report_timezone;

        return in_array($tz, \DateTimeZone::listIdentifiers(), true)
            ? new \DateTimeZone($tz)
            : new \DateTimeZone('Africa/Douala');
    }

    /** Send time as "HH:MM" (safe fallback 07:00). */
    public function sendTime(): string
    {
        return preg_match('/^\d{2}:\d{2}$/', (string) $this->send_time)
            ? (string) $this->send_time
            : '07:00';
    }

    /** Whether a section ("production", "financial", …) is included. */
    public function includes(string $section): bool
    {
        $key = 'include_'.$section;

        return in_array($key, self::SECTIONS, true) && (bool) $this->{$key};
    }
}
