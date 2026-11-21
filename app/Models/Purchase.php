<?php

namespace App\Models;

use App\Models\Concerns\HasUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Material purchase from a supplier (spec §10). Lines live in
 * purchase_items; marking the purchase PAID books exactly one expense
 * row (source_type=PURCHASE) in the single cost ledger.
 */
class Purchase extends Model
{
    use HasUuid;

    protected $keyType = 'string';

    public $incrementing = false;

    protected $guarded = ['id'];

    protected $casts = [
        'total_amount' => 'float',
        'amount_paid' => 'float',
        'purchase_date' => 'date',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class, 'supplier_id');
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class, 'order_id');
    }

    /** Optional expense category (used by the journal mapping, §6). */
    public function expenseCategory(): BelongsTo
    {
        return $this->belongsTo(ExpenseCategory::class, 'expense_category_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(PurchaseItem::class, 'purchase_id');
    }

    /** Instalments paid on this purchase (spec v1.3 §4.6). */
    public function payments(): HasMany
    {
        return $this->hasMany(PurchasePayment::class, 'purchase_id');
    }

    /** Σ payments recorded so far (XAF). */
    public function paidTotal(): float
    {
        return (float) $this->payments()->sum('amount');
    }

    /** Remaining supplier debt on this purchase (XAF). */
    public function balanceDue(): float
    {
        return round(max(0.0, $this->total_amount - $this->paidTotal()), 2);
    }

    /** Recompute amount_paid + payment_status from the payment rows. */
    public function refreshPaymentState(): void
    {
        $paid = $this->paidTotal();

        if ($paid <= 0) {
            $status = 'UNPAID';
        } elseif ($paid + 0.005 >= (float) $this->total_amount) {
            $status = 'PAID';
        } else {
            $status = 'PARTIAL';
        }

        $this->update(['amount_paid' => $paid, 'payment_status' => $status]);
    }
}
