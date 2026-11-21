<?php

namespace App\Models;

use App\Models\Concerns\HasUuid;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Chart-of-accounts row (spec v1.3 §6, SYSCOHADA referential).
 *
 * Seeded with a compact SYSCOHADA subset (see migration 000038); the
 * accountant can extend or deactivate rows. Journal lines snapshot the
 * account code so entries stay readable even if the chart evolves.
 */
class AccountingAccount extends Model
{
    use HasUuid;

    protected $keyType = 'string';

    public $incrementing = false;

    protected $guarded = ['id'];

    protected $casts = [
        'is_active' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    /** Look up an account by its SYSCOHADA code. */
    public static function byCode(string $code): ?self
    {
        return static::query()->where('code', $code)->first();
    }

    /** Journal lines posted on this account. */
    public function journalLines(): HasMany
    {
        return $this->hasMany(JournalLine::class, 'account_id');
    }
}
