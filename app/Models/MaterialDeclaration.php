<?php

namespace App\Models;

use App\Models\Concerns\HasUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Employee declaration of consumed / returned quantities
 * (spec v1.3 §5.6/§5.7 — both workflows end here).
 *
 * The employee (or the magasinier on their behalf) declares only facts:
 * qty consumed and qty returned. A declaration is PENDING until the
 * manager reviews it (§5.8); APPROVED restitutions generate the stock
 * RETURN_IN movement, REJECTED rows are kept with their motif — never
 * deleted (§5.8 "une déclaration rejetée n'est jamais supprimée").
 * The loss itself is never declared: it is computed at closure (§5.10).
 */
class MaterialDeclaration extends Model
{
    use HasUuid;

    protected $keyType = 'string';

    public $incrementing = false;

    protected $guarded = ['id'];

    protected $casts = [
        'qty_consumed' => 'float',
        'qty_returned' => 'float',
        'declared_at' => 'datetime',
        'reviewed_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function line(): BelongsTo
    {
        return $this->belongsTo(MaterialIssueLine::class, 'issue_line_id');
    }

    public function declaredBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'declared_by_id');
    }

    public function reviewedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by_id');
    }
}
