<?php

namespace App\Models;

use App\Models\Concerns\HasUuid;
use Illuminate\Database\Eloquent\Model;

/**
 * One daily-report send run (append-only delivery history).
 *
 * trigger_type: SCHEDULED (cron) | MANUAL (Send Now) | TEST (test e-mail
 * to the requesting Management user).
 * status: SENT (every recipient accepted) | PARTIAL (some failed) |
 * FAILED (nothing delivered / aborted before sending).
 */
class DailyReportLog extends Model
{
    use HasUuid;

    protected $keyType = 'string';

    public $incrementing = false;

    protected $guarded = ['id'];

    public const TRIGGERS = ['SCHEDULED', 'MANUAL', 'TEST'];
    public const STATUSES = ['SENT', 'PARTIAL', 'FAILED'];

    protected $casts = [
        'report_date' => 'date',
        'sent_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /** Whether a SCHEDULED report already went out for the given day. */
    public static function scheduledAlreadySent(\Carbon\CarbonInterface $reportDate): bool
    {
        return self::query()
            ->where('trigger_type', 'SCHEDULED')
            ->whereDate('report_date', $reportDate->toDateString())
            ->where('status', '!=', 'FAILED')
            ->exists();
    }
}
