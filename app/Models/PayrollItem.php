<?php

namespace App\Models;

use App\Models\Concerns\HasUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * One payroll line per staff member (spec §8.2).
 *
 * net_pay = salary_amount + allowances + bonuses − deductions −
 * advance_recovered. Advance recoveries are mirrored in
 * advance_recoveries rows and never become expenses (spec §9).
 */
class PayrollItem extends Model
{
    use HasUuid;

    protected $keyType = 'string';

    public $incrementing = false;

    protected $guarded = ['id'];

    protected $casts = [
        'salary_amount' => 'float',
        'allowances' => 'float',
        'deductions' => 'float',
        'advance_recovered' => 'float',
        'bonuses' => 'float',
        'net_pay' => 'float',
        'payment_date' => 'date',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function payroll(): BelongsTo
    {
        return $this->belongsTo(Payroll::class, 'payroll_id');
    }

    public function staff(): BelongsTo
    {
        return $this->belongsTo(User::class, 'staff_id');
    }

    public function advanceRecoveries(): HasMany
    {
        return $this->hasMany(AdvanceRecovery::class, 'payroll_item_id');
    }
}
