<?php

namespace App\Models;

use App\Models\Concerns\HasUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Immutable history of advance recoveries applied through payroll
 * (spec §9). History table with created_at only.
 */
class AdvanceRecovery extends Model
{
    use HasUuid;

    protected $keyType = 'string';

    public $incrementing = false;

    protected $guarded = ['id'];

    public $timestamps = false;

    public const CREATED_AT = 'created_at';

    protected $casts = [
        'amount' => 'float',
        'recovered_at' => 'datetime',
        'created_at' => 'datetime',
    ];

    public function advance(): BelongsTo
    {
        return $this->belongsTo(StaffAdvance::class, 'advance_id');
    }

    public function payrollItem(): BelongsTo
    {
        return $this->belongsTo(PayrollItem::class, 'payroll_item_id');
    }
}
