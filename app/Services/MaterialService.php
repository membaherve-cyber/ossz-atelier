<?php

namespace App\Services;

use App\Models\GarmentComponent;
use App\Models\Material;
use App\Models\MaterialDeclaration;
use App\Models\MaterialIssue;
use App\Models\MaterialIssueLine;
use App\Models\MaterialLoss;
use App\Models\MaterialStockMovement;
use App\Models\MaterialTransfer;
use App\Models\Order;
use App\Models\ProductionTask;
use App\Models\User;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

/**
 * OSSZ ATELIER — material traceability service
 * (Finance & Comptabilité spec v1.3 §5).
 *
 * Implements the spec's core rules:
 *  - a purchase feeds STOCK, never an order's cost (§5.5: imputation
 *    happens only at the store exit, when the material is handed to an
 *    employee);
 *  - an issue to an employee is NOT a consumption (§10) — stock leaves,
 *    the employee is responsible, nothing is imputed yet;
 *  - the employee declares consumed / returned quantities (§5.6/§5.7);
 *  - the manager validates declarations (§5.8): approved returns feed
 *    stock back (RETURN_IN), rejected ones stay with their motif;
 *  - closure computes the loss per line — remis − consommé − restitué
 *    (approved only) — never manually entered (§5.10); an over-declared
 *    line (consumed + returned > issued) is an anomaly that blocks
 *    normal closure until corrected or justified;
 *  - employee-to-employee transfers preserve the chain of custody
 *    without any consumption (§5.11).
 *
 * Valuation: weighted-average cost refreshed on every IN movement;
 * client-supplied materials are traceable but never valued (§5.15).
 */
final class MaterialService
{
    // ------------------------------------------------------------------
    // Stock ledger
    // ------------------------------------------------------------------

    /**
     * Feed stock from a purchase (§5.5): +qty, weighted-average cost
     * refresh. Client-supplied materials stay at cost 0 (§5.15).
     */
    public static function stockIn(
        Material $material,
        float $quantity,
        float $unitCost,
        CarbonInterface $date,
        ?string $sourceType,
        ?string $sourceId,
        ?string $note,
        ?User $by,
    ): MaterialStockMovement {
        if ($quantity <= 0) {
            throw new InvalidArgumentException('stock-in quantity must be positive');
        }

        return DB::transaction(function () use ($material, $quantity, $unitCost, $date, $sourceType, $sourceId, $note, $by): MaterialStockMovement {
            $material = $material->refresh();

            if ($material->is_client_supplied) {
                $unitCost = 0.0;
            }

            // Weighted average over the CURRENT stock + the incoming lot.
            $oldQty = max(0.0, (float) $material->stock_qty);
            $newQty = $oldQty + $quantity;
            $avg = $newQty > 0
                ? round((($oldQty * (float) $material->avg_cost) + ($quantity * $unitCost)) / $newQty, 2)
                : round($unitCost, 2);

            $material->update([
                'stock_qty' => $newQty,
                'avg_cost' => $avg,
            ]);

            return MaterialStockMovement::query()->create([
                'material_id' => $material->id,
                'kind' => 'PURCHASE_IN',
                'quantity' => $quantity,
                'unit_cost' => $avg,
                'value' => round($quantity * $unitCost, 2),
                'source_type' => $sourceType,
                'source_id' => $sourceId,
                'note' => $note,
                'created_by_id' => $by?->id,
                'created_by_name' => $by?->name,
            ]);
        });
    }

    /**
     * Manual stock adjustment (physical count fix) — signed quantity.
     */
    public static function adjust(
        Material $material,
        float $signedQuantity,
        ?string $note,
        ?User $by,
    ): MaterialStockMovement {
        return DB::transaction(function () use ($material, $signedQuantity, $note, $by): MaterialStockMovement {
            $material = $material->refresh();
            $newQty = round((float) $material->stock_qty + $signedQuantity, 3);

            if ($newQty < 0) {
                throw new InvalidArgumentException('adjustment would make the stock negative');
            }

            $material->update(['stock_qty' => $newQty]);

            return MaterialStockMovement::query()->create([
                'material_id' => $material->id,
                'kind' => 'ADJUSTMENT',
                'quantity' => $signedQuantity,
                'unit_cost' => $material->avg_cost,
                'value' => round($signedQuantity * (float) $material->avg_cost, 2),
                'source_type' => 'ADJUSTMENT',
                'note' => $note,
                'created_by_id' => $by?->id,
                'created_by_name' => $by?->name,
            ]);
        });
    }

    // ------------------------------------------------------------------
    // Issues (remises) — workflow A of §5.6
    // ------------------------------------------------------------------

    /**
     * Record a remise (§5.5): materials leave the store and become the
     * employee's responsibility, analytically assigned to the order
     * (and optionally the garment / task). Each line snapshots the
     * weighted-average cost at issue time; client-supplied lines cost 0.
     *
     * @param list<array{material_id: string, qty: float}> $lines
     */
    public static function issue(
        Order $order,
        ?GarmentComponent $garment,
        ?ProductionTask $task,
        User $staff,
        array $lines,
        CarbonInterface $date,
        ?string $note,
        User $by,
    ): MaterialIssue {
        if ($lines === []) {
            throw new InvalidArgumentException('an issue needs at least one material line');
        }

        return DB::transaction(function () use ($order, $garment, $task, $staff, $lines, $date, $note, $by): MaterialIssue {
            $issue = MaterialIssue::query()->create([
                'code' => Codes::materialIssue(),
                'order_id' => $order->id,
                'garment_id' => $garment?->id,
                'task_id' => $task?->id,
                'staff_id' => $staff->id,
                'staff_name' => $staff->name,
                'status' => 'OPEN',
                'issue_date' => $date,
                'note' => $note,
                'created_by_id' => $by->id,
                'created_by_name' => $by->name,
            ]);

            foreach ($lines as $line) {
                /** @var Material $material */
                $material = Material::query()->findOrFail($line['material_id']);
                $qty = (float) $line['qty'];

                if ($qty <= 0) {
                    throw new InvalidArgumentException('issue quantity must be positive');
                }

                if ((float) $material->stock_qty < $qty) {
                    throw new InvalidArgumentException(
                        'insufficient stock for '.$material->name.' ('.$material->stock_qty.' '.$material->unit.' left)'
                    );
                }

                $unitCost = $material->is_client_supplied ? 0.0 : (float) $material->avg_cost;

                MaterialIssueLine::query()->create([
                    'issue_id' => $issue->id,
                    'material_id' => $material->id,
                    'material_name' => $material->name,
                    'unit' => $material->unit,
                    'qty_issued' => $qty,
                    'unit_cost' => $unitCost,
                ]);

                // Stock leaves now; consumption will be declared later (§10).
                $material->update(['stock_qty' => round((float) $material->stock_qty - $qty, 3)]);

                MaterialStockMovement::query()->create([
                    'material_id' => $material->id,
                    'kind' => 'ISSUE_OUT',
                    'quantity' => -$qty,
                    'unit_cost' => $unitCost,
                    'value' => round($qty * $unitCost, 2),
                    'source_type' => 'MATERIAL_ISSUE_LINE',
                    'source_id' => $issue->id,
                    'note' => $issue->code.' → '.$staff->name,
                    'created_by_id' => $by->id,
                    'created_by_name' => $by->name,
                ]);
            }

            return $issue;
        });
    }

    /**
     * Employee declaration of consumed / returned quantities (§5.7
     * step 3, workflows A and B both end here). Validation of the
     * anomaly guard (consumed + returned may never exceed issued,
     * §5.10) happens here so an impossible declaration is rejected with
     * a clear message BEFORE it pollutes the history.
     */
    public static function declare(
        MaterialIssueLine $line,
        float $qtyConsumed,
        float $qtyReturned,
        ?string $note,
        User $declaredBy,
    ): MaterialDeclaration {
        if ($qtyConsumed < 0 || $qtyReturned < 0) {
            throw new InvalidArgumentException('declared quantities cannot be negative');
        }

        if ($qtyConsumed == 0.0 && $qtyReturned == 0.0) {
            throw new InvalidArgumentException('a declaration needs a consumed or returned quantity');
        }

        return DB::transaction(function () use ($line, $qtyConsumed, $qtyReturned, $note, $declaredBy): MaterialDeclaration {
            $line = $line->refresh();

            if ($line->issue->status !== 'OPEN') {
                throw new InvalidArgumentException('this remise is closed — declarations are no longer accepted');
            }

            // §5.10 anomaly guard: declared total may never exceed issued.
            if (round($line->declaredTotal() + $qtyConsumed + $qtyReturned, 3) > round((float) $line->qty_issued, 3) + 0.001) {
                throw new InvalidArgumentException(
                    'declared quantities exceed the quantity handed out ('.$line->qty_issued.' '.$line->unit.')'
                );
            }

            return MaterialDeclaration::query()->create([
                'issue_line_id' => $line->id,
                'qty_consumed' => $qtyConsumed,
                'qty_returned' => $qtyReturned,
                'note' => $note,
                'declared_by_id' => $declaredBy->id,
                'declared_by_name' => $declaredBy->name,
                'declared_at' => now(),
                'status' => 'PENDING',
            ]);
        });
    }

    /**
     * Manager review of a declaration (§5.8): APPROVED (returns feed
     * stock back immediately) or REJECTED (kept with the motif, never
     * deleted). A manager cannot validate their own declaration without
     * the explicit users.manage.all permission (§5.6 BOTH rule).
     */
    public static function review(
        MaterialDeclaration $declaration,
        string $decision, // APPROVED | REJECTED
        ?string $reviewNote,
        User $by,
    ): MaterialDeclaration {
        if (! in_array($decision, ['APPROVED', 'REJECTED'], true)) {
            throw new InvalidArgumentException('decision must be APPROVED or REJECTED');
        }

        return DB::transaction(function () use ($declaration, $decision, $reviewNote, $by): MaterialDeclaration {
            $declaration = $declaration->refresh();

            if ($declaration->status !== 'PENDING') {
                throw new InvalidArgumentException('this declaration was already reviewed');
            }

            if ($declaration->declared_by_id === $by->id
                && ! Permissions::allows($by, 'users.manage.all')) {
                throw new InvalidArgumentException('you cannot validate your own declaration');
            }

            $declaration->update([
                'status' => $decision,
                'reviewed_by_id' => $by->id,
                'reviewed_by_name' => $by->name,
                'review_note' => $reviewNote,
                'reviewed_at' => now(),
            ]);

            if ($decision === 'APPROVED' && (float) $declaration->qty_returned > 0) {
                $line = $declaration->line;
                /** @var Material $material */
                $material = $line->material;

                // §5.9 restitution → back to stock.
                $material->update(['stock_qty' => round((float) $material->stock_qty + (float) $declaration->qty_returned, 3)]);

                MaterialStockMovement::query()->create([
                    'material_id' => $material->id,
                    'kind' => 'RETURN_IN',
                    'quantity' => (float) $declaration->qty_returned,
                    'unit_cost' => $line->unit_cost,
                    'value' => round((float) $declaration->qty_returned * (float) $line->unit_cost, 2),
                    'source_type' => 'MATERIAL_DECLARATION',
                    'source_id' => $declaration->id,
                    'note' => $line->issue->code.' — restitution '.$declaration->declared_by_name,
                    'created_by_id' => $by->id,
                    'created_by_name' => $by->name,
                ]);
            }

            return $declaration;
        });
    }

    /**
     * Close a remise (§5.10): compute the loss per line
     * (issued − Σ approved consumed − Σ approved returned), store it
     * with its value and mark the issue CLOSED. Lines with an
     * over-declared balance (anomaly) block the closure; lines with
     * PENDING declarations block it too (the loss must be definitive,
     * not provisional).
     *
     * @param list<array{issue_line_id: string, justification: ?string}> $justifications
     * @return list<MaterialLoss>
     */
    public static function closeIssue(MaterialIssue $issue, User $by, array $justifications = []): array
    {
        $losses = DB::transaction(function () use ($issue, $by, $justifications): array {
            $issue = $issue->refresh();

            if ($issue->status !== 'OPEN') {
                throw new InvalidArgumentException('only an OPEN remise can be closed');
            }

            $pending = $issue->lines()
                ->whereHas('declarations', fn ($query) => $query->where('status', 'PENDING'))
                ->exists();

            if ($pending) {
                throw new InvalidArgumentException('a declaration is still awaiting review — review it before closing');
            }

            $created = [];
            $justByLine = collect($justifications)
                ->mapWithKeys(static fn (array $j): array => [$j['issue_line_id'] => $j['justification']]);

            foreach ($issue->lines as $line) {
                $anomaly = round($line->declaredTotal() - (float) $line->qty_issued, 3);

                if ($anomaly > 0.001) {
                    throw new InvalidArgumentException(
                        'anomaly on '.$line->material_name.': declared exceeds handed out by '.$anomaly.' '.$line->unit
                    );
                }

                $lossQty = round((float) $line->qty_issued - $line->approvedConsumed() - $line->approvedReturned(), 3);

                if ($lossQty < 0) {
                    $lossQty = 0.0; // rejected declarations can leave a residue — keep it factual
                }

                if ($lossQty <= 0) {
                    continue; // §5.10: no gap, no loss row
                }

                $created[] = MaterialLoss::query()->create([
                    'issue_line_id' => $line->id,
                    'qty_lost' => $lossQty,
                    'value' => round($lossQty * (float) $line->unit_cost, 2),
                    'justification' => $justByLine->get($line->id),
                    'computed_at' => now(),
                ]);
            }

            $issue->update([
                'status' => 'CLOSED',
                'closed_by_id' => $by->id,
                'closed_by_name' => $by->name,
                'closed_at' => now(),
            ]);

            return $created;
        });

        return $losses;
    }

    /**
     * Cancel a remise recorded by mistake: OPEN only, every line's
     * quantity returns to stock, the rows stay for the audit trail.
     */
    public static function cancelIssue(MaterialIssue $issue, User $by): void
    {
        DB::transaction(function () use ($issue, $by): void {
            $issue = $issue->refresh();

            if ($issue->status !== 'OPEN') {
                throw new InvalidArgumentException('only an OPEN remise can be cancelled');
            }

            foreach ($issue->lines as $line) {
                /** @var Material $material */
                $material = $line->material;
                $material->update(['stock_qty' => round((float) $material->stock_qty + (float) $line->qty_issued, 3)]);

                MaterialStockMovement::query()->create([
                    'material_id' => $material->id,
                    'kind' => 'RETURN_IN',
                    'quantity' => (float) $line->qty_issued,
                    'unit_cost' => $line->unit_cost,
                    'value' => round((float) $line->qty_issued * (float) $line->unit_cost, 2),
                    'source_type' => 'MATERIAL_ISSUE_CANCEL',
                    'source_id' => $issue->id,
                    'note' => $issue->code.' cancelled — back to stock',
                    'created_by_id' => $by->id,
                    'created_by_name' => $by->name,
                ]);
            }

            $issue->update(['status' => 'CANCELLED', 'closed_by_id' => $by->id, 'closed_by_name' => $by->name, 'closed_at' => now()]);
        });
    }

    // ------------------------------------------------------------------
    // Employee-to-employee transfers (§5.11)
    // ------------------------------------------------------------------

    /**
     * Record a custody transfer between two employees: no stock
     * movement, no consumption — the chain of responsibility moves.
     */
    public static function transfer(
        Order $order,
        ?GarmentComponent $garment,
        ?ProductionTask $task,
        Material $material,
        float $qty,
        User $from,
        User $to,
        ?string $note,
        User $by,
    ): MaterialTransfer {
        if ($qty <= 0) {
            throw new InvalidArgumentException('transfer quantity must be positive');
        }

        if ($from->id === $to->id) {
            throw new InvalidArgumentException('a transfer needs two different employees');
        }

        return MaterialTransfer::query()->create([
            'from_staff_id' => $from->id,
            'from_staff_name' => $from->name,
            'to_staff_id' => $to->id,
            'to_staff_name' => $to->name,
            'order_id' => $order->id,
            'garment_id' => $garment?->id,
            'task_id' => $task?->id,
            'material_id' => $material->id,
            'material_name' => $material->name,
            'unit' => $material->unit,
            'qty' => $qty,
            'note' => $note,
            'created_by_id' => $by->id,
            'created_by_name' => $by->name,
        ]);
    }

    // ------------------------------------------------------------------
    // Analytical dashboards (§5.14)
    // ------------------------------------------------------------------

    /**
     * Per-employee material dashboard (§5.14): received / transferred
     * out / consumed (approved) / returned / loss (computed at closure)
     * / consumed value, for a given period or all time.
     *
     * @return list<array<string, mixed>>
     */
    public static function employeeDashboard(?CarbonInterface $from = null, ?CarbonInterface $to = null): array
    {
        $issues = MaterialIssue::query()
            ->whereIn('status', ['OPEN', 'CLOSED'])
            ->when($from, static fn ($q) => $q->where('issue_date', '>=', $from))
            ->when($to, static fn ($q) => $q->where('issue_date', '<=', $to))
            ->with(['lines' => static fn ($q) => $q->with(['declarations' => static fn ($dq) => $dq->whereIn('status', ['APPROVED', 'REJECTED'])])])
            ->get();

        $rows = [];

        foreach ($issues as $issue) {
            $staffId = $issue->staff_id;
            $rows[$staffId] ??= [
                'staff_id' => $staffId,
                'staff_name' => $issue->staff_name,
                'received' => 0.0,       // qty handed to the employee (any unit mix → values kept per line)
                'consumed' => 0.0,
                'returned' => 0.0,
                'loss' => 0.0,
                'consumed_value' => 0.0, // XAF
                'loss_value' => 0.0,     // XAF
                'issues' => 0,
            ];
            $rows[$staffId]['issues']++;

            foreach ($issue->lines as $line) {
                $consumed = $line->declarations->where('status', 'APPROVED')->sum('qty_consumed');
                $returned = $line->declarations->where('status', 'APPROVED')->sum('qty_returned');
                $approvedDeclared = $consumed + $returned;
                $lossQty = $issue->status === 'CLOSED'
                    ? max(0.0, (float) $line->qty_issued - $approvedDeclared)
                    : 0.0;

                $rows[$staffId]['received'] += (float) $line->qty_issued;
                $rows[$staffId]['consumed'] += (float) $consumed;
                $rows[$staffId]['returned'] += (float) $returned;
                $rows[$staffId]['loss'] += $lossQty;
                $rows[$staffId]['consumed_value'] += round((float) $consumed * (float) $line->unit_cost, 2);
                $rows[$staffId]['loss_value'] += round($lossQty * (float) $line->unit_cost, 2);
            }
        }

        return array_values($rows);
    }
}
