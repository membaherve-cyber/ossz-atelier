<?php

namespace App\Models;

use App\Models\Concerns\HasUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Balanced double-entry journal entry (spec v1.3 §6).
 *
 * Entries are generated automatically from recorded operations by
 * App\Services\AccountingPost (source_type + source_id, unique — an
 * operation is never posted twice); the user never chooses an account.
 * Manual entries (is_generated = false) are reserved for the Comptable
 * role, journal OD, for diverse operations and corrections (§6).
 */
class JournalEntry extends Model
{
    use HasUuid;

    protected $keyType = 'string';

    public $incrementing = false;

    protected $guarded = ['id'];

    protected $casts = [
        'entry_date' => 'date',
        'is_generated' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /** The entry's debit/credit lines (always balanced). */
    public function lines(): HasMany
    {
        return $this->hasMany(JournalLine::class, 'entry_id');
    }

    /** Σ debit of the entry. */
    public function totalDebit(): float
    {
        return (float) $this->lines()->sum('debit');
    }

    /** Σ credit of the entry. */
    public function totalCredit(): float
    {
        return (float) $this->lines()->sum('credit');
    }
}
