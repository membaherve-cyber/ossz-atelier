<?php

namespace App\Models;

use App\Models\Concerns\HasUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Money advanced to a staff member before salary payment (spec §9).
 *
 * PENDING_APPROVAL → APPROVED → DISBURSED → PARTIALLY_RECOVERED →
 * FULLY_RECOVERED (or CANCELLED before disbursement). Outstanding
 * amount = amount − amount_recovered (mirrored from advance_recoveries).
 */
class StaffAdvance extends Model
{
    use HasUuid;

    protected $keyType = 'string';

    public $incrementing = false;

    protected $guarded = ['id'];

    /** Statuses in which the advance is money already handed out. */
    public const DISBURSED_STATUSES = ['DISBURSED', 'PARTIALLY_RECOVERED'];

    protected $casts = [
        'amount' => 'float',
        'amount_recovered' => 'float',
        'date_issued' => 'date',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function staff(): BelongsTo
    {
        return $this->belongsTo(User::class, 'staff_id');
    }

    public function recoveries(): HasMany
    {
        return $this->hasMany(AdvanceRecovery::class, 'advance_id');
    }

    /** Outstanding (not yet recovered) amount. */
    public function outstanding(): float
    {
        return max(0.0, (float) $this->amount - (float) $this->amount_recovered);
    }

    /** Whether the advance still has an outstanding balance. */
    public function hasOutstanding(): bool
    {
        return $this->outstanding() > 0.005;
    }

    /** Refresh amount_recovered + status from the recovery rows. */
    public function refreshRecoveryState(): void
    {
        $recovered = (float) $this->recoveries()->sum('amount');
        $this->amount_recovered = $recovered;

        if ($recovered <= 0.005) {
            if ($this->status === 'PARTIALLY_RECOVERED' || $this->status === 'FULLY_RECOVERED') {
                $this->status = 'DISBURSED';
            }
        } elseif ($recovered + 0.005 >= (float) $this->amount) {
            $this->status = 'FULLY_RECOVERED';
        } else {
            $this->status = 'PARTIALLY_RECOVERED';
        }

        $this->save();
    }
}
