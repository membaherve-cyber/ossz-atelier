<?php

namespace App\Models;

use App\Models\Concerns\HasUuid;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Atelier staff account (MANAGEMENT | ADMIN | STAFF).
 *
 * 1:1 port of the Prisma `User` model (prisma/schema.prisma): UUID
 * primary key, unique system code (USR-001), unique email, bcrypt
 * password hash stored in `password_hash` (Prisma column name kept),
 * role and active flag.
 */
class User extends Authenticatable
{
    use HasUuid;

    // Aliasing keeps the Laravel notification relations reachable while
    // `notifications` is re-pointed at the atelier's own notifications
    // table below (no signature-compatibility risk with the trait).
    use Notifiable {
        notifications as baseNotifications;
        readNotifications as baseReadNotifications;
        unreadNotifications as baseUnreadNotifications;
    }

    /** Prisma `String @id @default(cuid())` — string UUID key. */
    protected $keyType = 'string';

    /** The primary key is not auto-incrementing. */
    public $incrementing = false;

    /** Mass assignment is open for every attribute except the key. */
    protected $guarded = ['id'];

    /** Never expose the hash through array/JSON serialization. */
    protected $hidden = ['password_hash'];

    /** Prisma booleans + datetimes. */
    protected $casts = [
        'is_active' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /*
    |--------------------------------------------------------------------------
    | Relations
    |--------------------------------------------------------------------------
    */

    /**
     * Production tasks assigned to this staff member
     * (Prisma relation "TaskAssignee").
     */
    public function assignedTasks(): HasMany
    {
        return $this->hasMany(ProductionTask::class, 'assigned_staff_id');
    }

    /**
     * Internal atelier notifications addressed to this user.
     *
     * This application owns a plain `notifications` table (user_id /
     * type / title / …) instead of Laravel's morph-based database
     * notifications, so the relation is re-pointed at
     * App\Models\Notification. The Notifiable originals stay available
     * as baseNotifications() / baseReadNotifications() /
     * baseUnreadNotifications().
     */
    public function notifications(): HasMany
    {
        return $this->hasMany(Notification::class, 'user_id')->latest();
    }

    /** Notifications addressed to this user that are still unread. */
    public function unreadNotifications(): HasMany
    {
        return $this->hasMany(Notification::class, 'user_id')
            ->where('is_read', false)
            ->latest();
    }

    /** Notifications addressed to this user that have been read. */
    public function readNotifications(): HasMany
    {
        return $this->hasMany(Notification::class, 'user_id')
            ->where('is_read', true)
            ->latest();
    }

    /*
    |--------------------------------------------------------------------------
    | Authentication glue
    |--------------------------------------------------------------------------
    */

    /**
     * The password hash lives in `password_hash` (Prisma column name)
     * rather than Laravel's default `password` column, so the auth
     * contract is adapted accordingly.
     */
    public function getAuthPassword(): string
    {
        return $this->password_hash;
    }

    /** Column name of the password hash (see getAuthPassword()). */
    public function getAuthPasswordName(): string
    {
        return 'password_hash';
    }

    /*
    |--------------------------------------------------------------------------
    | Scopes
    |--------------------------------------------------------------------------
    */

    /** Restrict to active accounts (Prisma `isActive`). */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    /** Restrict to a single role (MANAGEMENT | ADMIN | STAFF). */
    public function scopeRole(Builder $query, string $role): Builder
    {
        return $query->where('role', $role);
    }
}
