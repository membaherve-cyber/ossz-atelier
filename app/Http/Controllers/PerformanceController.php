<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\StaffAssessment;
use App\Models\User;
use App\Services\Audit;
use App\Services\PerformanceService;
use App\Support\Trans;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * Staff performance overview (spec §3: GET /performance, POST
 * /performance/config, POST /performance/assess).
 *
 * 1:1 port of the Next.js reference implementation:
 * - GET  src/app/api/atelier/performance/route.ts — period-scoped staff
 *   metrics & weighted scores (engine: App\Services\PerformanceService);
 * - PATCH src/app/api/atelier/performance/config/route.ts — weights +
 *   piece target configuration (must total 100);
 * - PUT  src/app/api/atelier/performance/assessment/route.ts — manual
 *   Management assessment, upserted per (staff, period).
 *
 * Route permissions (performance.view / performance.configure /
 * performance.assess) are enforced by the `perm` middleware.
 *
 * Deliberately does not extend a base Controller class, mirroring
 * LoginController / TaskActionController.
 */
class PerformanceController
{
    /**
     * Lowercase period types accepted by the range picker (view contract
     * passes $rangeType through in this exact form).
     */
    private const PERIOD_TYPES = ['month', 'quarter', 'year'];

    /**
     * GET /performance — staff performance table for the selected period
     * (default: the current month).
     */
    public function index(Request $request): View
    {
        $type = strtolower(trim((string) $request->query('type', 'month')));

        if (! in_array($type, self::PERIOD_TYPES, true)) {
            $type = 'month';
        }

        $value = $this->resolvePeriodValue($type, $request->query('value'));

        $data = PerformanceService::forRange(strtoupper($type), $value);

        $assessments = StaffAssessment::query()
            ->orderByDesc('updated_at')
            ->limit(10)
            ->get();

        // StaffAssessment keeps snapshots only (staff_id / staff_name, no
        // relations in the 1:1 Prisma port), so the assessed staff account
        // is attached in memory for the view contract. The assessor is
        // recorded in the audit trail only — the schema has no assessor
        // column (matching the Next.js implementation).
        $staffById = User::query()
            ->whereIn('id', $assessments->pluck('staff_id')->filter()->unique()->values())
            ->get()
            ->keyBy('id');

        foreach ($assessments as $assessment) {
            $assessment->setRelation('user', $staffById->get($assessment->staff_id));
        }

        return view('performance.index', [
            'rows' => $data['rows'],
            'config' => $data['config'],
            'rangeType' => $type,
            'rangeValue' => $value,
            'assessments' => $assessments,
            'staffList' => User::query()
                ->where('is_active', true)
                ->where('role', 'STAFF')
                ->orderBy('name')
                ->get(),
        ]);
    }

    /**
     * POST /performance/config — update the scoring weights and the monthly
     * piece target. The five weights must total exactly 100 (ported from
     * the PATCH handler of performance/config/route.ts).
     */
    public function updateConfig(Request $request): RedirectResponse
    {
        $me = $request->user();

        $data = $request->validate([
            'productivityWeight' => ['required', 'integer', 'min:0', 'max:100'],
            'timelyWeight' => ['required', 'integer', 'min:0', 'max:100'],
            'qualityWeight' => ['required', 'integer', 'min:0', 'max:100'],
            'reliabilityWeight' => ['required', 'integer', 'min:0', 'max:100'],
            'managementWeight' => ['required', 'integer', 'min:0', 'max:100'],
            'pieceTarget' => ['required', 'integer', 'min:1', 'max:500'],
        ]);

        $weights = [
            'productivity' => (int) $data['productivityWeight'],
            'timely' => (int) $data['timelyWeight'],
            'quality' => (int) $data['qualityWeight'],
            'reliability' => (int) $data['reliabilityWeight'],
            'management' => (int) $data['managementWeight'],
        ];

        if (array_sum($weights) !== 100) {
            return redirect()
                ->back()
                ->with('error', __('perf.weightsSaveError'))
                ->withInput();
        }

        PerformanceService::config()->forceFill([
            'productivity_weight' => $weights['productivity'],
            'timely_weight' => $weights['timely'],
            'quality_weight' => $weights['quality'],
            'reliability_weight' => $weights['reliability'],
            'management_weight' => $weights['management'],
            'piece_target' => (int) $data['pieceTarget'],
        ])->save();

        Audit::log($me, 'PERFORMANCE_CONFIG_UPDATED', 'SYSTEM', null, [
            'weights' => $weights,
            'pieceTarget' => (int) $data['pieceTarget'],
        ]);

        return redirect()->back()->with('success', __('perf.weightsSaved'));
    }

    /**
     * POST /performance/assess — upsert the manual Management assessment
     * for one staff member and period (ported from the PUT handler of
     * performance/assessment/route.ts; updates refresh the staff name
     * snapshot like the reference implementation).
     */
    public function assess(Request $request): RedirectResponse
    {
        $me = $request->user();

        $data = $request->validate([
            'staffId' => [
                'required',
                'string',
                Rule::exists('users', 'id')->where('is_active', true)->where('role', 'STAFF'),
            ],
            'score' => ['required', 'integer', 'min:0', 'max:100'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'periodValue' => ['nullable', 'string', 'regex:/^\d{4}(-Q[1-4]|-(0[1-9]|1[0-2]))?$/'],
        ]);

        /** @var User $staff */
        $staff = User::query()->findOrFail($data['staffId']);

        $period = trim((string) ($data['periodValue'] ?? ''));

        if ($period === '') {
            $period = now()->format('Y-m');
        }

        $notes = trim((string) ($data['notes'] ?? ''));
        $score = (int) $data['score'];

        $assessment = StaffAssessment::query()->firstOrNew([
            'staff_id' => $staff->id,
            'period' => $period,
        ]);

        $assessment->forceFill([
            'staff_id' => $staff->id,
            'staff_name' => $staff->name,
            'period' => $period,
            'score' => $score,
            'comments' => $notes !== '' ? $notes : null,
        ])->save();

        Audit::log($me, 'STAFF_ASSESSED', 'USER', $staff->id, [
            'staffId' => $staff->id,
            'period' => $period,
            'score' => $score,
        ]);

        return redirect()->back()->with('success', Trans::fill('perf.assessmentSavedDesc', [
            'name' => $staff->name,
            'score' => $score,
            'period' => $period,
        ]));
    }

    // ------------------------------------------------------------------
    // Shared helpers
    // ------------------------------------------------------------------

    /**
     * The period value for the requested type: the query value when it
     * matches the expected format (PERIOD_PATTERNS in the reference
     * implementation), otherwise the current period.
     */
    private function resolvePeriodValue(string $type, mixed $value): string
    {
        $value = strtoupper(trim(is_string($value) ? $value : ''));

        if (preg_match($this->periodPattern($type), $value) === 1) {
            return $value;
        }

        return match ($type) {
            'quarter' => sprintf('%d-Q%d', (int) now()->format('Y'), (int) now()->quarter),
            'year' => now()->format('Y'),
            default => now()->format('Y-m'),
        };
    }

    /**
     * Format pattern per period type (ported from the API route).
     */
    private function periodPattern(string $type): string
    {
        return match ($type) {
            'quarter' => '/^\d{4}-Q[1-4]$/',
            'year' => '/^\d{4}$/',
            default => '/^\d{4}-(0[1-9]|1[0-2])$/',
        };
    }
}
