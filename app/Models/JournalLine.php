<?php

namespace App\Models;

use App\Models\Concerns\HasUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * One debit/credit line of a journal entry (spec v1.3 §6).
 *
 * Exactly one of debit / credit is non-zero (the other stays 0). The
 * SYSCOHADA account code is snapshotted onto the line so the grand
 * livre stays readable even if the chart is later renamed.
 */
class JournalLine extends Model
{
    use HasUuid;

    protected $keyType = 'string';

    public $incrementing = false;

    protected $guarded = ['id'];

    protected $casts = [
        'debit' => 'float',
        'credit' => 'float',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function entry(): BelongsTo
    {
        return $this->belongsTo(JournalEntry::class, 'entry_id');
    }

    public function account(): BelongsTo
    {
        return $this->belongsTo(AccountingAccount::class, 'account_id');
    }
}
