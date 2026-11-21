<?php

namespace App\Models;

use App\Models\Concerns\HasUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Employee work-time log (spec v1.3 §5.13 EmployeeWorkLog).
 *
 * Each row records minutes spent by a staff member on a production
 * task on a given day — the granular base for measuring labour per
 * order, garment, operation and employee (§5.14 dashboards).
 */
class TaskWorkLog extends Model
{
    use HasUuid;

    protected $keyType = 'string';

    public $incrementing = false;

    protected $guarded = ['id'];

    protected $casts = [
        'minutes' => 'integer',
        'logged_on' => 'date',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function task(): BelongsTo
    {
        return $this->belongsTo(ProductionTask::class, 'task_id');
    }

    public function staff(): BelongsTo
    {
        return $this->belongsTo(User::class, 'staff_id');
    }
}
