<?php

namespace App\Models;

use App\Models\Concerns\HasUuid;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * One e-mail address on the daily business-health report distribution
 * list (enhancement).
 *
 * Deactivating (is_active = false) keeps the address + history without
 * sending to it — the friendlier default for a distribution list managed
 * from the UI. Deleting removes the address entirely.
 */
class DailyReportRecipient extends Model
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

    /** Scope: addresses that receive the report. */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }
}
