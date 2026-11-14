<?php

namespace App\Models;

use App\Models\Concerns\HasUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * A configurable production checklist stage.
 *
 * 1:1 port of the Prisma `ProductionStage` model. `applies_to` is "ALL"
 * or a comma-separated list of task categories (e.g. "FULL,CUTTING");
 * `weight` drives the weighted progress calculation. State values for
 * TaskStageState: NOT_STARTED | IN_PROGRESS | COMPLETED |
 * NOT_APPLICABLE.
 */
class ProductionStage extends Model
{
    use HasUuid;

    /** Prisma `String @id @default(cuid())` — string UUID key. */
    protected $keyType = 'string';

    /** The primary key is not auto-incrementing. */
    public $incrementing = false;

    /** Mass assignment is open for every attribute except the key. */
    protected $guarded = ['id'];

    /** Prisma integers/floats/booleans + datetimes. */
    protected $casts = [
        'sort_order' => 'integer',
        'weight' => 'float',
        'is_active' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /** Stage states recorded against this stage. */
    public function stageStates(): HasMany
    {
        return $this->hasMany(TaskStageState::class, 'stage_id');
    }
}
