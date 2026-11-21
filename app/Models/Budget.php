<?php

namespace App\Models;

use App\Models\Concerns\HasUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Budget for a period (spec v1.3 §8) — planned revenue and expenses.
 *
 * Actuals are always derived for the same period by
 * App\Services\BudgetService; forecasts stay distinct from the costs
 * actually recorded (§8).
 */
class Budget extends Model
{
    use HasUuid;

    protected $keyType = 'string';

    public $incrementing = false;

    protected $guarded = ['id'];

    protected $casts = [
        'period_start' => 'date',
        'period_end' => 'date',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /** The budget's planned lines (global or per expense category). */
    public function lines(): HasMany
    {
        return $this->hasMany(BudgetLine::class, 'budget_id');
    }

    /** Whether the budget's period includes today. */
    public function isCurrent(): bool
    {
        $today = today();

        return $today->between($this->period_start, $this->period_end);
    }
}
