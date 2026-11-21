<?php

declare(strict_types=1);

namespace App\Http\Controllers\Accounting;

use App\Models\Budget;
use App\Models\ExpenseCategory;
use App\Models\User;
use App\Services\Audit;
use App\Services\BudgetService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * Budgets (Finance & Comptabilité spec v1.3 §8) — GET/POST /budgets.
 *
 * A budget covers a period and holds planned revenue (global) and
 * planned expenses (global or per expense category). The comparison
 * view derives the actuals from the accounting records for the same
 * period, so planned-vs-real never goes stale (§8). Overrun alerts
 * show on the index for ACTIVE budgets.
 */
class BudgetController
{
    /**
     * GET /budgets — list with comparisons + create form.
     */
    public function index(Request $request): View
    {
        $budgets = Budget::query()
            ->with(['lines.expenseCategory:id,name,group'])
            ->orderByDesc('period_start')
            ->paginate(15);

        $budgets->getCollection()->transform(static function (Budget $budget): Budget {
            $budget->setAttribute('comparison', BudgetService::comparison($budget));

            return $budget;
        });

        return view('accounting.budgets.index', [
            'budgets' => $budgets,
            'categories' => ExpenseCategory::query()->orderBy('name')->get(['id', 'name']),
        ]);
    }

    /**
     * POST /budgets — create a budget with its initial lines.
     */
    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'label' => ['required', 'string', 'min:3', 'max:120', Rule::unique('budgets', 'label')],
            'period_start' => ['required', 'date'],
            'period_end' => ['required', 'date', 'after_or_equal:period_start'],
            'notes' => ['nullable', 'string', 'max:500'],
            'planned_revenue' => ['nullable', 'numeric', 'min:0'],
            'lines' => ['nullable', 'array'],
            'lines.*.expense_category_id' => ['required_with:lines', 'uuid', 'exists:expense_categories,id'],
            'lines.*.planned' => ['required_with:lines', 'numeric', 'min:0'],
        ]);

        /** @var User $me */
        $me = $request->user();

        $budget = DB::transaction(static function () use ($data, $me): Budget {
            $budget = Budget::query()->create([
                'label' => $data['label'],
                'period_start' => $data['period_start'],
                'period_end' => $data['period_end'],
                'status' => 'ACTIVE',
                'notes' => $data['notes'] ?? null,
                'created_by_id' => $me->id,
                'created_by_name' => $me->name,
            ]);

            if ((float) ($data['planned_revenue'] ?? 0) > 0) {
                $budget->lines()->create([
                    'kind' => 'REVENUE',
                    'expense_category_id' => null,
                    'planned' => (float) $data['planned_revenue'],
                ]);
            }

            foreach ($data['lines'] ?? [] as $line) {
                $budget->lines()->create([
                    'kind' => 'EXPENSE',
                    'expense_category_id' => $line['expense_category_id'],
                    'planned' => (float) $line['planned'],
                ]);
            }

            return $budget;
        });

        Audit::log($me, 'BUDGET_CREATED', 'BUDGET', $budget->id, [
            'label' => $budget->label,
            'period' => $budget->period_start->format('Y-m-d').' → '.$budget->period_end->format('Y-m-d'),
        ]);

        return redirect()->back()->with('success', __('bud.created'));
    }

    /**
     * POST /budgets/{id}/lines — add a planned line to a budget.
     */
    public function storeLine(Request $request, string $id): RedirectResponse
    {
        $budget = Budget::query()->findOrFail($id);

        if ($budget->status !== 'ACTIVE') {
            return redirect()->back()->with('error', __('bud.closedBudget'));
        }

        $data = $request->validate([
            'kind' => ['required', Rule::in(['REVENUE', 'EXPENSE'])],
            'expense_category_id' => ['nullable', 'uuid', 'exists:expense_categories,id'],
            'planned' => ['required', 'numeric', 'min:0.01'],
            'note' => ['nullable', 'string', 'max:500'],
        ]);

        if ($data['kind'] === 'REVENUE') {
            $data['expense_category_id'] = null;
        }

        $budget->lines()->create([
            'kind' => $data['kind'],
            'expense_category_id' => $data['expense_category_id'] ?? null,
            'planned' => (float) $data['planned'],
            'note' => $data['note'] ?? null,
        ]);

        Audit::log($request->user(), 'BUDGET_UPDATED', 'BUDGET', $budget->id, [
            'label' => $budget->label,
            'added' => $data['kind'],
        ]);

        return redirect()->back()->with('success', __('bud.lineAdded'));
    }

    /**
     * POST /budgets/{id}/close — close a budget (kept read-only).
     */
    public function close(Request $request, string $id): RedirectResponse
    {
        $budget = Budget::query()->findOrFail($id);

        $budget->update(['status' => 'CLOSED']);

        Audit::log($request->user(), 'BUDGET_CLOSED', 'BUDGET', $budget->id, [
            'label' => $budget->label,
        ]);

        return redirect()->back()->with('success', __('bud.closed'));
    }
}
