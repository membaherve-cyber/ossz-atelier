<?php

namespace App\Services;

use App\Models\PayrollItem;
use App\Models\SalaryProfile;
use App\Models\StaffAdvance;
use App\Models\User;
use Illuminate\Support\Collection;

/**
 * OSSZ ATELIER — payroll preparation engine (enhancement spec §8).
 *
 * Builds proposed payroll lines from each active STAFF member's latest
 * ACTIVE salary profile (MONTHLY base + allowances by default; other
 * remuneration types are proposed at their base and adjusted by
 * Management before approval). Outstanding staff advances are surfaced
 * per staff so deductions can be applied transparently — recoveries are
 * recorded as advance_recoveries rows and never become expenses (§9).
 */
final class PayrollService
{
    /**
     * Proposed payroll lines for every active STAFF member.
     *
     * @return list<array{staff: User, profile: ?SalaryProfile, salary: float, allowances: float, bonuses: float, deductions: float, advanceSuggested: float, net: float, advances: list<StaffAdvance>}>
     */
    public static function previewItems(): array
    {
        $items = [];

        $staff = User::query()
            ->where('role', 'STAFF')
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        foreach ($staff as $member) {
            $profile = SalaryProfile::activeFor($member->id);

            $salary = (float) ($profile?->base_amount ?? 0);
            $allowances = (float) ($profile?->allowances ?? 0);

            $items[] = [
                'staff' => $member,
                'profile' => $profile,
                'salary' => $salary,
                'allowances' => $allowances,
                'bonuses' => 0.0,
                'deductions' => 0.0,
                'advanceSuggested' => 0.0,
                'net' => self::netPay($salary, $allowances, 0, 0, 0),
                'advances' => [],
            ];
        }

        return $items;
    }

    /**
     * Net pay formula (spec §8.2): salary + allowances + bonuses −
     * deductions − advance recovered.
     */
    public static function netPay(float $salary, float $allowances, float $bonuses, float $deductions, float $advanceRecovered): float
    {
        return round($salary + $allowances + $bonuses - $deductions - $advanceRecovered, 2);
    }

    /**
     * Outstanding (recoverable) advances of one staff member.
     *
     * @return list<StaffAdvance>
     */
    public static function outstandingAdvances(string $staffId): array
    {
        return StaffAdvance::query()
            ->where('staff_id', $staffId)
            ->whereIn('status', StaffAdvance::DISBURSED_STATUSES)
            ->whereColumn('amount_recovered', '<', 'amount')
            ->orderBy('date_issued')
            ->get()
            ->all();
    }

    /**
     * Outstanding advance total of one staff member (deduction ceiling).
     */
    public static function outstandingAdvanceTotal(string $staffId): float
    {
        return round(array_sum(array_map(
            static fn (StaffAdvance $a) => $a->outstanding(),
            self::outstandingAdvances($staffId),
        )), 2);
    }

    /**
     * Apply a recovery to one advance from a payroll item: writes the
     * advance_recoveries row, refreshes the advance state and returns the
     * actually-applied amount (capped at the outstanding balance).
     */
    public static function applyRecovery(StaffAdvance $advance, PayrollItem $item, float $amount, string $byId, string $byName): float
    {
        $applied = min($amount, $advance->outstanding());

        if ($applied <= 0.005) {
            return 0.0;
        }

        $item->advanceRecoveries()->create([
            'advance_id' => $advance->id,
            'amount' => $applied,
            'recovered_at' => now(),
            'recovered_by_id' => $byId,
            'recovered_by_name' => $byName,
        ]);

        $advance->refreshRecoveryState();

        return $applied;
    }
}
