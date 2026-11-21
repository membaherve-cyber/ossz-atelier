<?php

namespace App\Models;

use App\Models\Concerns\HasUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * One treasury movement (spec v1.3 §4.4) — the cash journal entry of
 * an account. Every movement knows its source (customer payment,
 * expense, purchase payment, payroll, advance, transfer leg, manual
 * deposit/withdrawal) and its counterparty. Internal transfers post
 * two legs (TRANSFER_OUT + TRANSFER_IN) and are neither a receipt nor
 * an expense (§4.4). `reconciled` supports bank reconciliation.
 */
class TreasuryMovement extends Model
{
    use HasUuid;

    protected $keyType = 'string';

    public $incrementing = false;

    protected $guarded = ['id'];

    protected $casts = [
        'amount' => 'float',
        'movement_date' => 'datetime',
        'reconciled' => 'boolean',
        'reconciled_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function account(): BelongsTo
    {
        return $this->belongsTo(TreasuryAccount::class, 'account_id');
    }
}
