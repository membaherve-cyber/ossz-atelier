<?php

namespace App\Models;

use App\Models\Concerns\HasUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Customer payment received against an order (spec §6).
 *
 * Outstanding balances are derived (order.total_value − SUM(payments)),
 * never stored — multiple partial payments per order are the norm
 * (deposit → balance). Reference PAY-0001 is system-generated.
 */
class Payment extends Model
{
    use HasUuid;

    protected $keyType = 'string';

    public $incrementing = false;

    protected $guarded = ['id'];

    protected $casts = [
        'amount' => 'float',
        'payment_date' => 'date',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class, 'order_id');
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class, 'customer_id');
    }

    /** Treasury account the payment landed on (spec v1.3 §4.2/§4.4). */
    public function treasuryAccount(): BelongsTo
    {
        return $this->belongsTo(TreasuryAccount::class, 'treasury_account_id');
    }
}
