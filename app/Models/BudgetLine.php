<?php

namespace App\Models;

use App\Models\Concerns\HasUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * One planned line of a budget (spec v1.3 §8).
 *
 * REVENUE lines are global (no category); EXPENSE lines are either
 * global (null category) or per expense category. `planned` is the
 * XAF amount the atelier expects for the budget's period.
 */
class BudgetLine extends Model
{
    use HasUuid;

    protected $keyType = 'string';

    public $incrementing = false;

    protected $guarded = ['id'];

    protected $casts = [
        'planned' => 'float',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function budget(): BelongsTo
    {
        return $this->belongsTo(Budget::class, 'budget_id');
    }

    public function expenseCategory(): BelongsTo
    {
        return $this->belongsTo(ExpenseCategory::class, 'expense_category_id');
    }
}
