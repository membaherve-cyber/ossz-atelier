<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Expense;
use App\Models\ExpenseCategory;
use App\Models\IncentiveRecord;
use App\Models\IncentiveScheme;
use App\Models\User;
use App\Services\Audit;
use App\Services\Codes;
use App\Services\Notifier;
use App\Services\PerformanceService;
use App\Services\Permissions;
use App\Support\Atelier;
use App\Support\Money;
use App\Support\Trans;
use Carbon\Carbon;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Throwable;

/**
 * Staff incentives (spec §3: GET /incentives + scheme management,
 * recommendation generation, review and payment recording).
 *
 * 1:1 port of the Next.js reference implementation:
 * - GET/POST src/app/api/atelier/incentives/schemes (+ [id]) — scheme
 *   definitions. The schema has a SINGLE nullable `amount` column (FCFA);
 *   schemes are never hard-deleted, only deactivated.
 * - POST    src/app/api/atelier/incentives/records — recommendation
 *   generation from the live performance engine (App\Services\
 *   PerformanceService, parallel-built A1/R1 contract).
 * - PATCH   src/app/api/atelier/incentives/records/[id] — review
 *   (approve / reject, MANAGEMENT only via `perm:incentives.approve`)
 *   and payment recording (`perm:incentives.payment`).
 *
 * Snapshot models: IncentiveRecord / IncentiveScheme keep denormalized
 * scheme_name / staff_name / approved_by_name columns and define NO
 * Eloquent relations (1:1 Prisma port), so this controller attaches the
 * related models IN MEMORY — both access styles work in the views:
 *   $record->user?->name / $record->scheme?->name /
 *   $record->approvedBy?->name (null when the account / scheme was
 *   deleted), or the never-null snapshots $record->staff_name /
 *   $record->scheme_name / $record->approved_by_name; schemes expose
 *   $scheme->recordsCount (also ->records_count).
 *
 * NO notifications are emitted for incentive decisions (report decision:
 * incentives are reviewed inside the Management UI — toasts only).
 *
 * Deliberately does not extend a base Controller class, mirroring
 * LoginController / LocaleController.
 */
class IncentiveController
{
    /** Evaluation records per page on the incentives screen. */
    private const RECORDS_PER_PAGE = 20;

    /**
     * Scheme period type => performance-engine mapping (SCHEME_PERIOD_MAP
     * port from the Next.js generate route): the range type handed to
     * PerformanceService::forRange(), the accepted periodValue pattern and
     * the toast key describing the expected format.
     *
     * MONTHLY / QUALITY / ON_TIME / CUSTOM are monthly formats, QUARTERLY
     * expects YYYY-QN, ANNUAL expects YYYY.
     *
     * @var array<string, array{range: string, pattern: string, format: string}>
     */
    private const SCHEME_PERIOD_MAP = [
        'MONTHLY' => ['range' => 'MONTH', 'pattern' => '/^\d{4}-(0[1-9]|1[0-2])$/', 'format' => 'formatMonth'],
        'QUARTERLY' => ['range' => 'QUARTER', 'pattern' => '/^\d{4}-Q[1-4]$/', 'format' => 'formatQuarter'],
        'ANNUAL' => ['range' => 'YEAR', 'pattern' => '/^\d{4}$/', 'format' => 'formatYear'],
        'QUALITY' => ['range' => 'MONTH', 'pattern' => '/^\d{4}-(0[1-9]|1[0-2])$/', 'format' => 'formatMonth'],
        'ON_TIME' => ['range' => 'MONTH', 'pattern' => '/^\d{4}-(0[1-9]|1[0-2])$/', 'format' => 'formatMonth'],
        'CUSTOM' => ['range' => 'MONTH', 'pattern' => '/^\d{4}-(0[1-9]|1[0-2])$/', 'format' => 'formatMonth'],
    ];

    /**
     * GET /incentives — schemes tab + evaluations tab (view contract #12).
     *
     * Records are paginated latest-first and filterable via the query
     * string (status, period — uppercased, staff); the filter select
     * values are echoed back through $filters.
     */
    public function index(Request $request): View
    {
        /** @var User $me */
        $me = $request->user();

        $filters = [
            'status' => trim((string) $request->query('status', '')),
            'period' => strtoupper(trim((string) $request->query('period', ''))),
            'staff' => trim((string) $request->query('staff', '')),
        ];

        $recordsQuery = IncentiveRecord::query()->orderByDesc('created_at');

        if ($filters['status'] !== '' && in_array($filters['status'], Atelier::INCENTIVE_STATUSES, true)) {
            $recordsQuery->where('status', $filters['status']);
        }

        if ($filters['period'] !== '') {
            $recordsQuery->where('period', $filters['period']);
        }

        if ($filters['staff'] !== '') {
            $recordsQuery->where('staff_id', $filters['staff']);
        }

        $records = $recordsQuery
            ->paginate(self::RECORDS_PER_PAGE)
            ->withQueryString();

        $this->hydrateRecords($records);

        return view('incentives.index', [
            'schemes' => $this->schemesWithRecordCounts(),
            'records' => $records,
            'filters' => $filters,
            'staffList' => User::query()
                ->where('is_active', true)
                ->where('role', 'STAFF')
                ->orderBy('name')
                ->get(),
            'canApprove' => $me->role === 'MANAGEMENT',
            'canPrepare' => Permissions::allows($me, 'incentives.prepare'),
            'canManageSchemes' => Permissions::allows($me, 'incentives.manage.schemes'),
        ]);
    }

    /**
     * POST /incentives/schemes — create a scheme (route perm
     * incentives.manage.schemes — MANAGEMENT only).
     *
     * Input contract: name (2-120), periodType (INCENTIVE_PERIOD_TYPES),
     * minScore (nullable 0-100), amount (nullable >= 0 — single amount
     * column, FCFA), description (nullable max 2000), isActive (optional
     * boolean, default true).
     */
    public function storeScheme(Request $request): RedirectResponse
    {
        /** @var User $me */
        $me = $request->user();

        $data = $request->validate([
            'name' => ['required', 'string', 'min:2', 'max:120'],
            'periodType' => ['required', 'string', Rule::in(Atelier::INCENTIVE_PERIOD_TYPES)],
            'minScore' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'amount' => ['nullable', 'numeric', 'min:0'],
            'description' => ['nullable', 'string', 'max:2000'],
            'isActive' => ['nullable', 'boolean'],
        ]);

        $description = trim((string) ($data['description'] ?? ''));

        $scheme = IncentiveScheme::query()->create([
            'name' => trim((string) $data['name']),
            'period_type' => (string) $data['periodType'],
            'min_score' => isset($data['minScore']) ? (float) $data['minScore'] : null,
            'amount' => isset($data['amount']) ? (float) $data['amount'] : null,
            'description' => $description !== '' ? $description : null,
            'is_active' => array_key_exists('isActive', $request->all())
                ? $request->boolean('isActive')
                : true,
            'created_by_id' => $me->id,
            'created_by_name' => $me->name,
        ]);

        Audit::log($me, 'INCENTIVE_SCHEME_CREATED', 'INCENTIVE_SCHEME', $scheme->id, [
            'name' => $scheme->name,
            'periodType' => $scheme->period_type,
            'minScore' => $scheme->min_score,
            'amount' => $scheme->amount,
        ]);

        return redirect()
            ->back()
            ->with('success', __('inc.toast.schemeCreated'));
    }

    /**
     * POST /incentives/schemes/{id} — update a scheme (same fields as
     * storeScheme, all optional: only keys actually present in the request
     * are applied — PATCH semantics; present-but-empty values clear the
     * nullable columns, mirroring the reference route).
     */
    public function updateScheme(Request $request, string $id): RedirectResponse
    {
        /** @var User $me */
        $me = $request->user();

        $data = $request->validate([
            'name' => ['sometimes', 'string', 'min:2', 'max:120'],
            'periodType' => ['sometimes', 'string', Rule::in(Atelier::INCENTIVE_PERIOD_TYPES)],
            'minScore' => ['sometimes', 'nullable', 'numeric', 'min:0', 'max:100'],
            'amount' => ['sometimes', 'nullable', 'numeric', 'min:0'],
            'description' => ['sometimes', 'nullable', 'string', 'max:2000'],
            'isActive' => ['sometimes', 'boolean'],
        ]);

        $scheme = IncentiveScheme::query()->findOrFail($id);

        $input = $request->all();
        $has = static fn (string $key): bool => array_key_exists($key, $input);

        $update = [];
        $changes = [];

        if ($has('name')) {
            $update['name'] = trim((string) ($data['name'] ?? ''));
            $changes['name'] = $update['name'];
        }

        if ($has('periodType')) {
            $update['period_type'] = (string) $data['periodType'];
            $changes['periodType'] = $update['period_type'];
        }

        if ($has('minScore')) {
            $update['min_score'] = $data['minScore'] !== null ? (float) $data['minScore'] : null;
            $changes['minScore'] = $update['min_score'];
        }

        if ($has('amount')) {
            $update['amount'] = $data['amount'] !== null ? (float) $data['amount'] : null;
            $changes['amount'] = $update['amount'];
        }

        if ($has('description')) {
            $description = trim((string) ($data['description'] ?? ''));
            $update['description'] = $description !== '' ? $description : null;
            $changes['description'] = $update['description'];
        }

        if ($has('isActive')) {
            $update['is_active'] = $request->boolean('isActive');
            $changes['isActive'] = $update['is_active'];
        }

        if ($update !== []) {
            $scheme->forceFill($update)->save();
        }

        Audit::log($me, 'INCENTIVE_SCHEME_UPDATED', 'INCENTIVE_SCHEME', $scheme->id, [
            'name' => $scheme->name,
            'updated' => $changes,
        ]);

        return redirect()
            ->back()
            ->with('success', __('inc.toast.schemeUpdated'));
    }

    /**
     * POST /incentives/generate — create PENDING recommendations from the
     * performance engine (route perm incentives.prepare).
     *
     * Input contract: periodType (INCENTIVE_PERIOD_TYPES) + periodValue
     * (required, format depends on the period type: MONTHLY / QUALITY /
     * ON_TIME / CUSTOM = YYYY-MM, QUARTERLY = YYYY-QN, ANNUAL = YYYY);
     * OPTIONAL schemeId narrows the run to a single scheme, otherwise
     * every ACTIVE scheme of the posted period type is evaluated against
     * every active STAFF account.
     *
     * Below-minScore staff are skipped; existing (scheme, staff, period)
     * records are skipped silently (decisions are final — the reference
     * route never touches APPROVED / REJECTED rows, and this port keeps
     * PENDING rows immutable too). Created rows: eligible = true,
     * proposed_amount = scheme amount (all created rows are eligible),
     * status PENDING, payment_status UNPAID.
     */
    public function generate(Request $request): RedirectResponse
    {
        /** @var User $me */
        $me = $request->user();

        $data = $request->validate([
            'periodType' => ['required', 'string', Rule::in(Atelier::INCENTIVE_PERIOD_TYPES)],
            'periodValue' => ['required', 'string', 'max:20'],
            'schemeId' => ['nullable', 'string'],
        ]);

        $periodType = (string) $data['periodType'];
        $periodValue = strtoupper(trim((string) $data['periodValue']));
        $format = self::SCHEME_PERIOD_MAP[$periodType];

        if ($periodValue === '' || preg_match($format['pattern'], $periodValue) !== 1) {
            return redirect()
                ->back()
                ->with('error', __('inc.toast.'.$format['format']));
        }

        // Target schemes: every ACTIVE scheme of the posted period type,
        // or the single scheme named by the optional schemeId input.
        $schemeId = trim((string) ($data['schemeId'] ?? ''));

        $schemes = IncentiveScheme::query()
            ->where('is_active', true)
            ->when($schemeId !== '', fn ($query) => $query->where('id', $schemeId))
            ->when($schemeId === '', fn ($query) => $query->where('period_type', $periodType))
            ->orderBy('name')
            ->get();

        if ($schemes->isEmpty()) {
            return redirect()
                ->back()
                ->with('error', __('inc.toast.selectSchemeDesc'));
        }

        $activeStaff = User::query()
            ->where('is_active', true)
            ->where('role', 'STAFF')
            ->orderBy('name')
            ->get();

        // Existing (scheme, staff, period) rows — skipped silently.
        $existing = IncentiveRecord::query()
            ->where('period', $periodValue)
            ->whereIn('scheme_id', $schemes->pluck('id'))
            ->get(['scheme_id', 'staff_id']);

        $existingKeys = [];

        foreach ($existing as $row) {
            $existingKeys[$row->scheme_id.'|'.$row->staff_id] = true;
        }

        $created = 0;

        try {
            // Performance results cached per range type (MONTH | QUARTER |
            // YEAR — SCHEME_PERIOD_MAP port); the service is owned by the
            // parallel R1 agent and called through its documented API.
            $performance = [];

            foreach ($schemes as $scheme) {
                $rangeType = self::SCHEME_PERIOD_MAP[$scheme->period_type]['range'] ?? 'MONTH';

                if (! array_key_exists($rangeType, $performance)) {
                    $performance[$rangeType] = $this->performanceScores(
                        PerformanceService::forRange($rangeType, $periodValue)
                    );
                }

                $scores = $performance[$rangeType];
                $field = $this->schemeScoreField($scheme->period_type);

                foreach ($activeStaff as $staff) {
                    $score = $scores[$staff->id][$field] ?? null;

                    if ($score === null
                        || ($scheme->min_score !== null && $score < $scheme->min_score)) {
                        continue; // no score, or below the scheme threshold
                    }

                    if (isset($existingKeys[$scheme->id.'|'.$staff->id])) {
                        continue; // already generated for (scheme, staff, period)
                    }

                    IncentiveRecord::query()->create([
                        'scheme_id' => $scheme->id,
                        'scheme_name' => $scheme->name,
                        'staff_id' => $staff->id,
                        'staff_name' => $staff->name,
                        'period' => $periodValue,
                        'performance_score' => $score,
                        'eligible' => true,
                        'proposed_amount' => $scheme->amount ?? 0,
                        'status' => 'PENDING',
                        'payment_status' => 'UNPAID',
                    ]);

                    $existingKeys[$scheme->id.'|'.$staff->id] = true;
                    $created++;
                }
            }
        } catch (Throwable) {
            return redirect()
                ->back()
                ->with('error', __('inc.toast.generationFailed'));
        }

        Audit::log(
            $me,
            'INCENTIVE_RECOMMENDATIONS_GENERATED',
            'INCENTIVE_SCHEME',
            $schemes->count() === 1 ? $schemes->first()->id : null,
            [
                'created' => $created,
                'period' => $periodValue,
                'periodType' => $periodType,
                'schemes' => $schemes->pluck('name')->all(),
            ]
        );

        return redirect()
            ->back()
            ->with('success', Trans::fill('inc.toast.generatedDesc', [
                'total' => $created,
                'eligible' => $created,
            ]));
    }

    /**
     * POST /incentives/{id}/review — approve or reject a recommendation
     * (route perm incentives.approve — MANAGEMENT only).
     *
     * Input contract: decision (approve|reject). approve requires
     * approvedAmount (numeric > 0); reject requires rejectionReason
     * (min 3, stored in management_comments — rejection is a Management
     * decision). Optional comments / approvalComments are stored in
     * approval_comments. Only PENDING records are reviewable; the
     * decision is final (regeneration never touches reviewed rows).
     */
    public function review(Request $request, string $id): RedirectResponse
    {
        /** @var User $me */
        $me = $request->user();

        $base = $request->validate([
            'decision' => ['required', 'string', Rule::in(['approve', 'reject'])],
        ]);

        $decision = (string) $base['decision'];

        // Decision-specific requirements (validation errors, not flashes).
        $data = $request->validate($decision === 'approve' ? [
            'approvedAmount' => ['required', 'numeric', 'gt:0'],
            'comments' => ['nullable', 'string', 'max:2000'],
            'approvalComments' => ['nullable', 'string', 'max:2000'],
        ] : [
            'rejectionReason' => ['required', 'string', 'min:3', 'max:2000'],
            'comments' => ['nullable', 'string', 'max:2000'],
            'approvalComments' => ['nullable', 'string', 'max:2000'],
        ]);

        $record = IncentiveRecord::query()->findOrFail($id);

        if ($record->status !== 'PENDING') {
            return redirect()
                ->back()
                ->with('error', __('inc.toast.reviewFailed'));
        }

        $note = trim((string) ($data['approvalComments'] ?? $data['comments'] ?? ''));

        if ($decision === 'approve') {
            $amount = (float) $data['approvedAmount'];

            $record->forceFill([
                'status' => 'APPROVED',
                'approved_amount' => $amount,
                'approved_by_id' => $me->id,
                'approved_by_name' => $me->name,
                'approval_comments' => $note !== '' ? $note : null,
            ])->save();

            Audit::log($me, 'INCENTIVE_APPROVED', 'INCENTIVE_RECORD', $record->id, [
                'recordId' => $record->id,
                'amount' => $amount,
                'staffName' => $record->staff_name,
                'period' => $record->period,
            ]);

            // Accounting integration (spec §13): notify the staff member
            // of the approval — the payable itself is booked when paid.
            Notifier::users(
                [$record->staff_id],
                'INCENTIVE_APPROVED',
                Trans::fill('inc.notifApproved', ['amount' => Money::format($amount), 'period' => $record->period]),
                null,
                null,
            );

            return redirect()
                ->back()
                ->with('success', __('inc.toast.approvedTitle'));
        }

        $record->forceFill([
            'status' => 'REJECTED',
            'approved_by_id' => $me->id,
            'approved_by_name' => $me->name,
            'management_comments' => trim((string) $data['rejectionReason']),
            'approval_comments' => $note !== '' ? $note : null,
        ])->save();

        Audit::log($me, 'INCENTIVE_REJECTED', 'INCENTIVE_RECORD', $record->id, [
            'recordId' => $record->id,
            'amount' => $record->proposed_amount,
            'staffName' => $record->staff_name,
            'period' => $record->period,
        ]);

        return redirect()
            ->back()
            ->with('success', __('inc.toast.rejectedTitle'));
    }

    /**
     * POST /incentives/{id}/payment — record a payout (route perm
     * incentives.payment — MANAGEMENT only).
     *
     * Input contract: paidAt (optional date, defaults to now) sets
     * payment_status PAID + the payment_date column. Only APPROVED and
     * still-unpaid records can be paid.
     */
    public function payment(Request $request, string $id): RedirectResponse
    {
        /** @var User $me */
        $me = $request->user();

        $data = $request->validate([
            'paidAt' => ['nullable', 'date'],
            'method' => ['nullable', 'string', Rule::in(Atelier::PAYMENT_METHODS)],
        ]);

        $record = IncentiveRecord::query()->findOrFail($id);

        if ($record->status !== 'APPROVED' || $record->payment_status === 'PAID') {
            return redirect()
                ->back()
                ->with('error', __('inc.toast.paymentFailed'));
        }

        $paidAt = isset($data['paidAt']) && $data['paidAt'] !== null
            ? Carbon::parse((string) $data['paidAt'])
            : now();

        // Accounting integration (spec §13): mark paid AND book the
        // financial payable as ONE ledger expense (source INCENTIVE_RECORD,
        // unique per record) inside a single transaction — the incentive is
        // never counted twice (payroll bonuses are separate manual entries
        // and never auto-filled from incentives).
        DB::transaction(function () use ($record, $paidAt, $me, $data): void {
            $record->forceFill([
                'payment_status' => 'PAID',
                'payment_date' => $paidAt,
            ])->save();

            $category = ExpenseCategory::ensureBonusesCategory();

            Expense::bookForSource('INCENTIVE_RECORD', $record->id, [
                'expense_category_id' => $category->id,
                'description' => "Incentive: {$record->staff_name} — {$record->scheme_name} ({$record->period})",
                'amount' => (float) $record->approved_amount,
                'currency' => Money::DEFAULT_CURRENCY,
                'date_incurred' => $paidAt->toDateString(),
                'date_paid' => $paidAt->toDateString(),
                'method' => $data['method'] ?? 'CASH',
                'payee' => $record->staff_name,
                'responsible_staff_id' => $record->staff_id,
                'status' => 'PAID',
                'recorded_by_id' => $me->id,
                'recorded_by_name' => $me->name,
                'approved_by_id' => $me->id,
                'approved_by_name' => $me->name,
                'approved_at' => $paidAt,
            ]);
        });

        Audit::log($me, 'INCENTIVE_PAYMENT_RECORDED', 'INCENTIVE_RECORD', $record->id, [
            'recordId' => $record->id,
            'staffName' => $record->staff_name,
            'amount' => $record->approved_amount,
            'paymentDate' => $paidAt->format(DATE_ATOM),
            'expenseBooked' => true,
        ]);

        return redirect()
            ->back()
            ->with('success', __('inc.toast.paymentRecorded'));
    }

    // ------------------------------------------------------------------
    // View-data helpers (snapshot hydration)
    // ------------------------------------------------------------------

    /**
     * All schemes, newest first, each carrying its evaluation count as an
     * in-memory recordsCount / records_count attribute (IncentiveScheme
     * has no Eloquent relations — the count cannot be eager-loaded).
     */
    private function schemesWithRecordCounts(): Collection
    {
        $schemes = IncentiveScheme::query()->orderByDesc('created_at')->get();

        $counts = IncentiveRecord::query()
            ->whereIn('scheme_id', $schemes->pluck('id'))
            ->groupBy('scheme_id')
            ->selectRaw('scheme_id, count(*) as aggregate')
            ->pluck('aggregate', 'scheme_id');

        return $schemes->each(static function (IncentiveScheme $scheme) use ($counts): void {
            $count = (int) ($counts[$scheme->id] ?? 0);

            $scheme->setAttribute('recordsCount', $count);
            $scheme->setAttribute('records_count', $count);
        });
    }

    /**
     * Attach the related User / IncentiveScheme models to each record row
     * IN MEMORY (IncentiveRecord has no Eloquent relations): ->user,
     * ->scheme and ->approvedBy, null when the account / scheme was
     * deleted — the snapshots (staff_name, scheme_name,
     * approved_by_name) always exist.
     */
    private function hydrateRecords(LengthAwarePaginator $records): void
    {
        $rows = $records->getCollection();

        $staffIds = $rows->pluck('staff_id')
            ->filter(static fn ($id) => (string) $id !== '')
            ->unique()
            ->values();

        $approverIds = $rows->pluck('approved_by_id')
            ->filter(static fn ($id) => (string) $id !== '')
            ->unique()
            ->values();

        $schemeIds = $rows->pluck('scheme_id')
            ->filter(static fn ($id) => (string) $id !== '')
            ->unique()
            ->values();

        $userIds = $staffIds->merge($approverIds)->unique()->values();

        $users = $userIds->isEmpty()
            ? Collection::make()
            : User::query()->whereIn('id', $userIds)->get()->keyBy('id');

        $schemes = $schemeIds->isEmpty()
            ? Collection::make()
            : IncentiveScheme::query()->whereIn('id', $schemeIds)->get()->keyBy('id');

        $rows->each(static function (IncentiveRecord $record) use ($users, $schemes): void {
            $record->setAttribute('user', $users->get($record->staff_id));
            $record->setAttribute('scheme', $schemes->get($record->scheme_id));
            $record->setAttribute('approvedBy', $users->get($record->approved_by_id));
        });
    }

    // ------------------------------------------------------------------
    // PerformanceService row parsing (defensive)
    // ------------------------------------------------------------------

    /**
     * Normalize a PerformanceService result into a plain list of row
     * arrays. Defensive per the A3 contract: accepts a plain list of rows
     * OR a ['rows' => …] / ['staff' => …] envelope, arrays or Traversable.
     *
     * @return list<array<string, mixed>>
     */
    private function performanceRows(mixed $result): array
    {
        if ($result instanceof Traversable) {
            $result = iterator_to_array($result);
        }

        if (! is_array($result)) {
            return [];
        }

        foreach (['rows', 'staff'] as $key) {
            if (isset($result[$key]) && is_array($result[$key])) {
                return array_values($result[$key]);
            }
        }

        return array_values($result);
    }

    /**
     * Staff id of a performance row — read from the explicit staffId /
     * staff_id / userId / user_id keys (Next.js StaffPerformance shape)
     * or from the nested User model ('user' => User, the PerformanceService
     * row shape).
     *
     * @param  array<string, mixed>  $row
     */
    private function rowStaffId(array $row): ?string
    {
        foreach (['staffId', 'staff_id', 'userId', 'user_id'] as $key) {
            if (isset($row[$key]) && (string) $row[$key] !== '') {
                return (string) $row[$key];
            }
        }

        $user = $row['user'] ?? null;

        return $user instanceof User ? (string) $user->id : null;
    }

    /**
     * One numeric score of a performance row, read defensively: nested
     * scores.<field> first (Next.js shape), then the flat <field> key
     * (PerformanceService shape), then the overallScore / overall_score
     * fallbacks for the overall score.
     *
     * @param  array<string, mixed>  $row
     */
    private function numericField(array $row, string $field): ?float
    {
        $scores = $row['scores'] ?? null;

        if (is_array($scores) && isset($scores[$field]) && is_numeric($scores[$field])) {
            return (float) $scores[$field];
        }

        if (isset($row[$field]) && is_numeric($row[$field])) {
            return (float) $row[$field];
        }

        if ($field === 'overall') {
            foreach (['overallScore', 'overall_score'] as $key) {
                if (isset($row[$key]) && is_numeric($row[$key])) {
                    return (float) $row[$key];
                }
            }
        }

        return null;
    }

    /**
     * staffId => per-field performance scores (quality / timeliness /
     * overall) for one PerformanceService result.
     *
     * @return array<string, array{quality: ?float, timeliness: ?float, overall: ?float}>
     */
    private function performanceScores(mixed $result): array
    {
        $scores = [];

        foreach ($this->performanceRows($result) as $row) {
            $staffId = $this->rowStaffId($row);

            if ($staffId === null) {
                continue;
            }

            $scores[$staffId] = [
                'quality' => $this->numericField($row, 'quality'),
                'timeliness' => $this->numericField($row, 'timeliness'),
                'overall' => $this->numericField($row, 'overall'),
            ];
        }

        return $scores;
    }

    /**
     * Performance field a scheme scores against (Next.js parity): QUALITY
     * schemes evaluate the quality score, ON_TIME the timeliness score,
     * everything else the overall score.
     */
    private function schemeScoreField(string $periodType): string
    {
        if ($periodType === 'QUALITY') {
            return 'quality';
        }

        if ($periodType === 'ON_TIME') {
            return 'timeliness';
        }

        return 'overall';
    }
}
