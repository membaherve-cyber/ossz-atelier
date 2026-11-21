<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\GarmentComponent;
use App\Models\Material;
use App\Models\MaterialDeclaration;
use App\Models\MaterialIssue;
use App\Models\MaterialIssueLine;
use App\Models\MaterialStockMovement;
use App\Models\MaterialTransfer;
use App\Models\Order;
use App\Models\User;
use App\Services\Audit;
use App\Services\MaterialService;
use App\Services\Notifier;
use App\Services\Permissions;
use App\Support\Atelier;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * Materials & stock (Finance & Comptabilité spec v1.3 §5).
 *
 * The whole material traceability chain behind GET/POST /materials*:
 *  - the material master + stock ledger (§5.4);
 *  - remises (issues) to employees with the mandatory order / garment /
 *    employee assignment (§5.5-§5.6 workflow A) — permission
 *    materials.issue (MANAGER / MAGASINIER / MANAGEMENT / ADMIN);
 *  - employee declarations of consumed / returned quantities (§5.7)
 *    — permission materials.declare; STAFF is scoped in the controller
 *    to issues where they are the beneficiary;
 *  - the manager validation queue (§5.8) — materials.validate;
 *  - closure with the computed loss (§5.10) — materials.validate;
 *  - employee-to-employee transfers (§5.11);
 *  - the per-employee analytical dashboard (§5.14).
 *
 * Vocabulary is atelier-first ("matière remise", "j'ai consommé") —
 * no accounting jargon in the forms (§1.2).
 */
class MaterialController
{
    /** Issues per page. */
    private const PER_PAGE = 20;

    // ------------------------------------------------------------------
    // Material master + stock
    // ------------------------------------------------------------------

    /**
     * GET /materials — master list with stock, movements ledger,
     * transfers section and the analytical per-employee dashboard.
     */
    public function index(Request $request): View
    {
        $q = trim((string) ($request->query('q') ?? ''));
        $category = (string) ($request->query('category') ?? '');

        $materials = Material::query();

        if ($q !== '') {
            $materials->where(static function (Builder $query) use ($q): void {
                $query->where('name', 'like', "%{$q}%")
                    ->orWhere('reference', 'like', "%{$q}%");
            });
        }

        if ($category !== '' && in_array($category, Atelier::MATERIAL_CATEGORIES, true)) {
            $materials->where('category', $category);
        }

        $materials = $materials->orderBy('name')->paginate(self::PER_PAGE)->withQueryString();

        $movements = MaterialStockMovement::query()
            ->with(['material:id,name,unit', 'material'])
            ->orderByDesc('created_at')
            ->limit(40)
            ->get();

        return view('materials.index', [
            'materials' => $materials,
            'movements' => $movements,
            'transfers' => MaterialTransfer::query()->with(['material:id,name,unit'])->orderByDesc('created_at')->limit(15)->get(),
            'categories' => Atelier::MATERIAL_CATEGORIES,
            'dashboard' => MaterialService::employeeDashboard(),
            'filters' => ['q' => $q, 'category' => $category],
            'canManage' => Permissions::allows($request->user(), 'materials.manage'),
            'canIssue' => Permissions::allows($request->user(), 'materials.issue'),
        ]);
    }

    /**
     * POST /materials — create a material in the master (§5.4).
     */
    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'min:2', 'max:190'],
            'reference' => ['nullable', 'string', 'max:60'],
            'category' => ['required', 'string', Rule::in(Atelier::MATERIAL_CATEGORIES)],
            'unit' => ['required', 'string', 'max:12'],
            'opening_qty' => ['nullable', 'numeric', 'min:0'],
            'opening_cost' => ['nullable', 'numeric', 'min:0'],
            'is_client_supplied' => ['nullable'],
            'notes' => ['nullable', 'string', 'max:500'],
        ]);

        /** @var User $me */
        $me = $request->user();

        $material = Material::query()->create([
            'name' => $data['name'],
            'reference' => $data['reference'] ?? null,
            'category' => $data['category'],
            'unit' => $data['unit'],
            'is_client_supplied' => $request->has('is_client_supplied'),
            'notes' => $data['notes'] ?? null,
        ]);

        // Optional opening stock via the ledger (§5.5 stock feed).
        if ((float) ($data['opening_qty'] ?? 0) > 0) {
            MaterialService::stockIn(
                $material,
                (float) $data['opening_qty'],
                $material->is_client_supplied ? 0.0 : (float) ($data['opening_cost'] ?? 0),
                now(),
                'OPENING',
                null,
                'Stock initial',
                $me,
            );
        }

        Audit::log($me, 'MATERIAL_CREATED', 'MATERIAL', $material->id, [
            'name' => $material->name,
            'category' => $material->category,
            'unit' => $material->unit,
        ]);

        return redirect()->back()->with('success', __('mat.materialCreated'));
    }

    /**
     * POST /materials/{id} — edit master data / adjust stock (§5.4).
     */
    public function update(Request $request, string $id): RedirectResponse
    {
        $material = Material::query()->findOrFail($id);

        $data = $request->validate([
            'name' => ['required', 'string', 'min:2', 'max:190'],
            'reference' => ['nullable', 'string', 'max:60'],
            'category' => ['required', 'string', Rule::in(Atelier::MATERIAL_CATEGORIES)],
            'unit' => ['required', 'string', 'max:12'],
            'is_client_supplied' => ['nullable'],
            'is_active' => ['nullable'],
            'notes' => ['nullable', 'string', 'max:500'],
            'adjust_qty' => ['nullable', 'numeric'],
            'adjust_note' => ['nullable', 'string', 'max:500'],
        ]);

        /** @var User $me */
        $me = $request->user();

        $material->update([
            'name' => $data['name'],
            'reference' => $data['reference'] ?? null,
            'category' => $data['category'],
            'unit' => $data['unit'],
            'is_client_supplied' => $request->has('is_client_supplied'),
            'is_active' => $request->has('is_active') ? $request->boolean('is_active') : $material->is_active,
            'notes' => $data['notes'] ?? null,
        ]);

        // Physical count adjustment (signed).
        if (isset($data['adjust_qty']) && (float) $data['adjust_qty'] !== 0.0) {
            try {
                MaterialService::adjust($material, (float) $data['adjust_qty'], $data['adjust_note'] ?? null, $me);
            } catch (\InvalidArgumentException $e) {
                return redirect()->back()->with('error', $e->getMessage());
            }
        }

        Audit::log($me, 'MATERIAL_UPDATED', 'MATERIAL', $material->id, [
            'name' => $material->name,
            'adjusted' => (float) ($data['adjust_qty'] ?? 0),
        ]);

        return redirect()->back()->with('success', __('mat.materialUpdated'));
    }

    // ------------------------------------------------------------------
    // Issues (remises) — §5.5/§5.6 workflow A
    // ------------------------------------------------------------------

    /**
     * GET /materials/issues — issue listing with per-line progress
     * (declared vs issued), and the create form's data.
     */
    public function issues(Request $request): View
    {
        /** @var User $me */
        $me = $request->user();
        $status = (string) ($request->query('status') ?? '');
        $mine = $request->boolean('mine');

        $issues = MaterialIssue::query()
            ->with(['order:id,order_number,status', 'garment:id,code,name', 'staff:id,name', 'lines']);

        // STAFF + materials.declare without materials.view → own issues only.
        if ($mine || ! Permissions::allows($me, 'materials.view')) {
            $issues->where('staff_id', $me->id);
        }

        if ($status !== '' && in_array($status, Atelier::MATERIAL_ISSUE_STATUSES, true)) {
            $issues->where('status', $status);
        }

        $issues = $issues->orderByDesc('issue_date')->paginate(self::PER_PAGE)->withQueryString();

        return view('materials.issues', [
            'issues' => $issues,
            'statuses' => Atelier::MATERIAL_ISSUE_STATUSES,
            'filters' => ['status' => $status, 'mine' => $mine],
            'canIssue' => Permissions::allows($me, 'materials.issue'),
            'canValidate' => Permissions::allows($me, 'materials.validate'),
        ]);
    }

    /**
     * GET /materials/issues/create — the remise form (§5.5): pick the
     * order (REGISTERED), optionally garment / task, the beneficiary
     * employee, materials + quantities.
     */
    public function createIssue(Request $request): View
    {
        /** @var User $me */
        $me = $request->user();
        $orderId = (string) ($request->query('order') ?? '');

        $order = $orderId !== ''
            ? Order::query()->with('garments')->findOrFail($orderId)
            : null;

        return view('materials.issue-form', [
            'orders' => Order::query()
                ->where('status', 'REGISTERED')
                ->with(['garments', 'tasks:id,order_id,code,garment_piece,category'])
                ->orderByDesc('order_date')
                ->limit(60)
                ->get(),
            'selectedOrder' => $order,
            'materials' => Material::query()->active()->orderBy('name')->get(),
            'staff' => User::query()
                ->whereIn('role', Permissions::assignableRoles())
                ->where('is_active', true)
                ->orderBy('name')
                ->get(['id', 'name', 'role']),
        ]);
    }

    /**
     * POST /materials/issues — record the remise (§5.5): stock leaves,
     * the employee becomes responsible, the order link is created HERE
     * (never at purchase).
     */
    public function storeIssue(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'order_id' => ['required', 'uuid', 'exists:orders,id'],
            'garment_id' => ['nullable', 'uuid', 'exists:garment_components,id'],
            'task_id' => ['nullable', 'uuid', 'exists:production_tasks,id'],
            'staff_id' => ['required', 'uuid', 'exists:users,id'],
            'date' => ['required', 'date'],
            'note' => ['nullable', 'string', 'max:500'],
            'lines' => ['required', 'array', 'min:1'],
            'lines.*.material_id' => ['required', 'uuid', 'exists:materials,id'],
            'lines.*.qty' => ['required', 'numeric', 'min:0.001'],
        ]);

        /** @var User $me */
        $me = $request->user();

        try {
            $issue = MaterialService::issue(
                Order::query()->findOrFail($data['order_id']),
                $data['garment_id'] ? GarmentComponent::query()->find($data['garment_id']) : null,
                $data['task_id'] ? \App\Models\ProductionTask::query()->find($data['task_id']) : null,
                User::query()->findOrFail($data['staff_id']),
                array_map(static fn (array $line): array => [
                    'material_id' => $line['material_id'],
                    'qty' => (float) $line['qty'],
                ], $data['lines']),
                \Carbon\Carbon::parse($data['date']),
                $data['note'] ?? null,
                $me,
            );
        } catch (\InvalidArgumentException $e) {
            return redirect()->back()->withInput()->with('error', $e->getMessage());
        }

        Audit::log($me, 'MATERIAL_ISSUE_RECORDED', 'MATERIAL_ISSUE', $issue->id, [
            'code' => $issue->code,
            'order' => $issue->order?->order_number,
            'garment' => $issue->garment?->code,
            'staff' => $issue->staff_name,
            'lines' => count($data['lines']),
        ]);

        // §5.6 workflow A step 2: the employee is told what they received.
        Notifier::users(
            [$issue->staff_id],
            'MATERIAL_ISSUE_RECEIVED',
            __('mat.notifIssueReceived', ['code' => $issue->code]),
        );

        return redirect()
            ->route('materials.issues.show', ['id' => $issue->id])
            ->with('success', __('mat.issueRecorded', ['code' => $issue->code]));
    }

    /**
     * GET /materials/issues/{id} — remise detail: lines with declared
     * progress, declarations history, declare / review / close forms.
     */
    public function showIssue(Request $request, string $id): View
    {
        /** @var User $me */
        $me = $request->user();
        $issue = MaterialIssue::query()
            ->with([
                'order:id,order_number,status,total_value,currency',
                'garment:id,code,name',
                'task:id,code,garment_piece,category',
                'staff:id,name',
                'lines.material:id,name,unit,is_client_supplied',
                'lines.declarations' => static fn (Builder $query) => $query->latest(),
                'lines.losses',
            ])
            ->findOrFail($id);

        // STAFF scope: beneficiaries see their own issue; material
        // holders see everything.
        if (! Permissions::allows($me, 'materials.view') && $issue->staff_id !== $me->id) {
            abort(403);
        }

        return view('materials.issue-show', [
            'issue' => $issue,
            'canDeclare' => Permissions::allows($me, 'materials.declare')
                && ($issue->staff_id === $me->id || Permissions::allows($me, 'materials.issue')),
            'canValidate' => Permissions::allows($me, 'materials.validate'),
        ]);
    }

    /**
     * POST /materials/issues/{id}/close — manager closure (§5.10):
     * loss computed per line, issue marked CLOSED.
     */
    public function closeIssue(Request $request, string $id): RedirectResponse
    {
        $issue = MaterialIssue::query()->findOrFail($id);

        $data = $request->validate([
            'justifications' => ['nullable', 'array'],
            'justifications.*' => ['nullable', 'string', 'max:500'],
        ]);

        /** @var User $me */
        $me = $request->user();

        $justifications = [];
        foreach ($data['justifications'] ?? [] as $lineId => $text) {
            $justifications[] = ['issue_line_id' => $lineId, 'justification' => $text !== '' ? $text : null];
        }

        try {
            $losses = MaterialService::closeIssue($issue, $me, $justifications);
        } catch (\InvalidArgumentException $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }

        Audit::log($me, 'MATERIAL_ISSUE_CLOSED', 'MATERIAL_ISSUE', $issue->id, [
            'code' => $issue->code,
            'lossLines' => count($losses),
            'lossValue' => round(collect($losses)->sum('value'), 2),
        ]);

        return redirect()->back()->with('success', __('mat.issueClosed', ['code' => $issue->code]));
    }

    /**
     * POST /materials/issues/{id}/cancel — cancel a mistaken remise
     * (OPEN only; quantities return to stock, rows kept for audit).
     */
    public function cancelIssue(Request $request, string $id): RedirectResponse
    {
        $issue = MaterialIssue::query()->findOrFail($id);

        try {
            MaterialService::cancelIssue($issue, $request->user());
        } catch (\InvalidArgumentException $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }

        Audit::log($request->user(), 'MATERIAL_ISSUE_CANCELLED', 'MATERIAL_ISSUE', $issue->id, [
            'code' => $issue->code,
        ]);

        return redirect()->route('materials.issues.index')->with('success', __('mat.issueCancelled', ['code' => $issue->code]));
    }

    // ------------------------------------------------------------------
    // Declarations + validation (§5.7/§5.8)
    // ------------------------------------------------------------------

    /**
     * POST /materials/lines/{id}/declare — employee declaration (§5.7).
     */
    public function declare(Request $request, string $id): RedirectResponse
    {
        $line = MaterialIssueLine::query()->with('issue')->findOrFail($id);

        /** @var User $me */
        $me = $request->user();

        // STAFF scope: only the beneficiary (or an issue holder) declares.
        if (! Permissions::allows($me, 'materials.issue') && $line->issue->staff_id !== $me->id) {
            abort(403);
        }

        $data = $request->validate([
            'qty_consumed' => ['nullable', 'numeric', 'min:0'],
            'qty_returned' => ['nullable', 'numeric', 'min:0'],
            'note' => ['nullable', 'string', 'max:500'],
        ]);

        try {
            $declaration = MaterialService::declare(
                $line,
                (float) ($data['qty_consumed'] ?? 0),
                (float) ($data['qty_returned'] ?? 0),
                $data['note'] ?? null,
                $me,
            );
        } catch (\InvalidArgumentException $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }

        Audit::log($me, 'MATERIAL_DECLARATION_SUBMITTED', 'MATERIAL_DECLARATION', $declaration->id, [
            'issue' => $line->issue->code,
            'material' => $line->material_name,
            'consumed' => (float) ($data['qty_consumed'] ?? 0),
            'returned' => (float) ($data['qty_returned'] ?? 0),
        ]);

        return redirect()->back()->with('success', __('mat.declarationSubmitted'));
    }

    /**
     * GET /materials/validation — the manager's pending queue (§5.8).
     */
    public function validationQueue(Request $request): View
    {
        $pending = MaterialDeclaration::query()
            ->where('status', 'PENDING')
            ->with([
                'line.issue:id,code,order_id,staff_id,staff_name,status',
                'line.issue.order:id,order_number',
                'line:id,issue_id,material_id,material_name,unit,qty_issued',
                'declaredBy:id,name',
            ])
            ->orderBy('declared_at')
            ->paginate(self::PER_PAGE);

        return view('materials.validation', ['pending' => $pending]);
    }

    /**
     * POST /materials/declarations/{id}/review — manager decision
     * (§5.8): APPROVED (returns feed stock) or REJECTED (kept + motif).
     */
    public function reviewDeclaration(Request $request, string $id): RedirectResponse
    {
        $declaration = MaterialDeclaration::query()->findOrFail($id);

        $data = $request->validate([
            'decision' => ['required', Rule::in(['APPROVED', 'REJECTED'])],
            'review_note' => ['nullable', 'string', 'max:500'],
        ]);

        /** @var User $me */
        $me = $request->user();

        try {
            $declaration = MaterialService::review($declaration, $data['decision'], $data['review_note'] ?? null, $me);
        } catch (\InvalidArgumentException $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }

        Audit::log($me, 'MATERIAL_DECLARATION_REVIEWED', 'MATERIAL_DECLARATION', $declaration->id, [
            'decision' => $data['decision'],
            'issue' => $declaration->line?->issue?->code,
            'material' => $declaration->line?->material_name,
        ]);

        if ($declaration->declared_by_id) {
            Notifier::users(
                [$declaration->declared_by_id],
                'MATERIAL_DECLARATION_REVIEWED',
                __('mat.notifDeclarationReviewed', ['decision' => __(strtolower($data['decision']) === 'approved' ? 'mat.approved' : 'mat.rejected')]),
            );
        }

        return redirect()->back()->with('success', __('mat.reviewRecorded'));
    }

    // ------------------------------------------------------------------
    // Employee-to-employee transfers (§5.11)
    // ------------------------------------------------------------------

    /**
     * POST /materials/transfers — custody transfer, no consumption.
     */
    public function storeTransfer(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'order_id' => ['required', 'uuid', 'exists:orders,id'],
            'garment_id' => ['nullable', 'uuid', 'exists:garment_components,id'],
            'task_id' => ['nullable', 'uuid', 'exists:production_tasks,id'],
            'material_id' => ['required', 'uuid', 'exists:materials,id'],
            'qty' => ['required', 'numeric', 'min:0.001'],
            'from_staff' => ['required', 'uuid', 'exists:users,id'],
            'to_staff' => ['required', 'uuid', 'exists:users,id', 'different:from_staff'],
            'note' => ['nullable', 'string', 'max:500'],
        ]);

        /** @var User $me */
        $me = $request->user();

        try {
            $transfer = MaterialService::transfer(
                Order::query()->findOrFail($data['order_id']),
                $data['garment_id'] ? GarmentComponent::query()->find($data['garment_id']) : null,
                $data['task_id'] ? \App\Models\ProductionTask::query()->find($data['task_id']) : null,
                Material::query()->findOrFail($data['material_id']),
                (float) $data['qty'],
                User::query()->findOrFail($data['from_staff']),
                User::query()->findOrFail($data['to_staff']),
                $data['note'] ?? null,
                $me,
            );
        } catch (\InvalidArgumentException $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }

        Audit::log($me, 'MATERIAL_TRANSFER_RECORDED', 'MATERIAL_TRANSFER', $transfer->id, [
            'order' => $transfer->order?->order_number,
            'material' => $transfer->material_name,
            'qty' => (float) $data['qty'],
            'from' => $transfer->from_staff_name,
            'to' => $transfer->to_staff_name,
        ]);

        return redirect()->back()->with('success', __('mat.transferRecorded'));
    }
}
