<?php

namespace App\Models;

use App\Models\Concerns\HasUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * One payment of a supplier purchase (spec v1.3 §4.6).
 *
 * A purchase's engagement and its settlement are distinct events
 * (§10): instalments are allowed — each row is one payment. The
 * purchase's payment_status and amount_paid are recomputed from these
 * rows (SupplierController), and the payment optionally lands on a
 * treasury account (§4.4).
 */
class PurchasePayment extends Model
{
    use HasUuid;

    protected $keyType = 'string';

    public $incrementing = false;

    protected $guarded = ['id'];

    protected $casts = [
        'amount' => 'float',
        'payment_date' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function purchase(): BelongsTo
    {
        return $this->belongsTo(Purchase::class, 'purchase_id');
    }

    public function treasuryAccount(): BelongsTo
    {
        return $this->belongsTo(TreasuryAccount::class, 'treasury_account_id');
    }
}
