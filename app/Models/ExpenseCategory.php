<?php

namespace App\Models;

use App\Models\Concerns\HasUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Configurable expense category (spec §7.1).
 *
 * Group: MATERIALS | TRANSPORT | OPERATIONS | SALARIES | ADMIN | OTHER.
 * The workshop's default categories are seeded lazily (ensureDefaults)
 * exactly once — the PerformanceConfig::ensure() pattern — so existing
 * databases gain the defaults on the first accounting page visit without
 * any seeder run.
 */
class ExpenseCategory extends Model
{
    use HasUuid;

    protected $keyType = 'string';

    public $incrementing = false;

    protected $guarded = ['id'];

    protected $casts = [
        'is_system' => 'boolean',
        'is_active' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function expenses(): HasMany
    {
        return $this->hasMany(Expense::class, 'expense_category_id');
    }

    /**
     * The default workshop categories (spec §7.1 A–F). Idempotent:
     * existing names (case-insensitive) are kept, missing ones created.
     */
    public static function ensureDefaults(): void
    {
        $defaults = [
            'MATERIALS' => [
                'Fabrics', 'Threads', 'Buttons', 'Zippers', 'Linings',
                'Interfacing', 'Needles', 'Other tailoring materials',
            ],
            'TRANSPORT' => [
                'Market transport (material purchase)', 'Collection transport (fabrics/accessories)',
                'Customer assignment transport', 'Delivery and collection expenses',
                'Staff transport allowance', 'Motorcycle/taxi fares', 'Other logistics expenses',
            ],
            'OPERATIONS' => [
                'Electricity', 'Water', 'Internet', 'Rent', 'Cleaning',
                'Maintenance', 'Machine repairs', 'Equipment servicing',
            ],
            'SALARIES' => [
                'Monthly salaries', 'Daily wages', 'Temporary staff payments',
                'Overtime', 'Staff allowances', 'Staff bonuses',
            ],
            'ADMIN' => [
                'Stationery', 'Printing', 'Communication', 'Office supplies',
                'Bank charges', 'Other administrative costs',
            ],
            'OTHER' => ['Other expenses'],
        ];

        $existing = self::query()
            ->whereIn('group', array_keys($defaults))
            ->pluck('name')
            ->map(static fn (string $name): string => mb_strtolower($name))
            ->all();

        foreach ($defaults as $group => $names) {
            foreach ($names as $name) {
                if (! in_array(mb_strtolower($name), $existing, true)) {
                    self::query()->create([
                        'name' => $name,
                        'group' => $group,
                        'is_system' => true,
                        'is_active' => true,
                    ]);
                }
            }
        }
    }

    /**
     * Find (or lazily create) the system category used to book paid
     * supplier purchases; falls back to "Other tailoring materials".
     */
    public static function defaultPurchaseCategory(): self
    {
        self::ensureDefaults();

        return self::query()
            ->where('name', 'Other tailoring materials')
            ->where('is_active', true)
            ->first()
            ?? self::query()->where('group', 'MATERIALS')->where('is_active', true)->firstOrFail();
    }

    /**
     * Find (or lazily create) the system "Staff bonuses" category used to
     * book paid incentives (spec §13 — the single booking point for
     * incentive payouts, so they are never double counted).
     */
    public static function ensureBonusesCategory(): self
    {
        self::ensureDefaults();

        return self::query()
            ->where('name', 'Staff bonuses')
            ->where('is_active', true)
            ->first()
            ?? self::query()->where('group', 'SALARIES')->where('is_active', true)->firstOrFail();
    }
}
