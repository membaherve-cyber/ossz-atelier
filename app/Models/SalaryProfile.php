<?php

namespace App\Models;

use App\Models\Concerns\HasUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Per-staff remuneration structure (spec §8.1). Payroll generation uses
 * the latest ACTIVE profile of each active STAFF member.
 */
class SalaryProfile extends Model
{
    use HasUuid;

    protected $keyType = 'string';

    public $incrementing = false;

    protected $guarded = ['id'];

    protected $casts = [
        'base_amount' => 'float',
        'allowances' => 'float',
        'effective_date' => 'date',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function staff(): BelongsTo
    {
        return $this->belongsTo(User::class, 'staff_id');
    }

    /**
     * The latest ACTIVE profile of a staff member (or null when the staff
     * has no configured remuneration yet).
     */
    public static function activeFor(string $staffId): ?self
    {
        return self::query()
            ->where('staff_id', $staffId)
            ->where('status', 'ACTIVE')
            ->orderByDesc('effective_date')
            ->first();
    }
}
