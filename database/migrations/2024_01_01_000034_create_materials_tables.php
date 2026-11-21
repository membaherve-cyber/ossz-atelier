<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * `material_*` + `task_work_logs` — the material traceability chain of
 * spec v1.3 §5 (the module's major functional requirement).
 *
 * Entities (§11 data model):
 *  - `materials` — the material master (tissus, doublures, boutons,
 *    fermetures, fils, entoilages, élastiques, accessoires, §5.4) with
 *    denormalized stock_qty + avg_cost for fast reads. Materials marked
 *    is_client_supplied (§5.15) stay traceable but are never valued.
 *  - `material_stock_movements` — the stock ledger: purchases feed stock
 *    (PURCHASE_IN), issues drain it (ISSUE_OUT), approved restitutions
 *    feed it back (RETURN_IN), manual adjustments fix physical counts.
 *    The weighted-average cost is recomputed on every IN movement.
 *  - `material_issues` — the remise (§5.5): THE moment a material is
 *    analytically assigned to an order (never at purchase). Holds the
 *    four traceability dimensions: order, garment, task/operation and
 *    beneficiary employee. Status OPEN → CLOSED (manager, §5.10) or
 *    CANCELLED (mistake, materials go back to stock).
 *  - `material_issue_lines` — per-material quantity + valuation snapshot
 *    (avg cost at issue time; 0 for client-supplied).
 *  - `material_declarations` — employee declarations of consumed /
 *    returned quantities (§5.6/§5.7), PENDING until the manager reviews
 *    (§5.8); rejected rows are kept with their motif, never deleted.
 *    Several declarations can accumulate on the same line (§5.10).
 *  - `material_losses` — the loss computed at closure (§5.10):
 *    qty_issued − Σ approved consumed − Σ approved returned, valued at
 *    the line's unit cost, with optional justification. Never manually
 *    entered.
 *  - `material_transfers` — employee-to-employee transfers (§5.11):
 *    preserve the chain of custody without creating a consumption.
 *  - `task_work_logs` — employee time tracking (§5.13) so labour cost
 *    per order/garment/operation/employee can be measured.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('materials', function (Blueprint $table) {
            $table->char('id', 36)->primary();
            $table->string('name', 190);
            $table->string('reference', 60)->nullable(); // supplier ref
            $table->string('category', 20)->default('OTHER'); // Atelier::MATERIAL_CATEGORIES
            $table->string('unit', 12)->default('m'); // m / pcs / kg / roll
            // §5.15 client-supplied: traceable, never valued
            $table->boolean('is_client_supplied')->default(false);
            $table->boolean('is_active')->default(true);
            // Denormalized stock state (ledger is material_stock_movements)
            $table->decimal('stock_qty', 12, 3)->default(0);
            $table->decimal('avg_cost', 14, 2)->default(0); // weighted average, XAF
            $table->string('notes', 500)->nullable();
            $table->timestamps();

            $table->index('category');
            $table->index('is_active');
        });

        Schema::create('material_stock_movements', function (Blueprint $table) {
            $table->char('id', 36)->primary();
            $table->foreignId('material_id')->constrained('materials')->cascadeOnDelete();
            // Atelier::STOCK_MOVEMENT_KINDS
            $table->string('kind', 20);
            // Signed: + for IN movements, − for OUT movements
            $table->decimal('quantity', 12, 3);
            // Weighted-average unit cost AFTER this IN movement (null on OUT)
            $table->decimal('unit_cost', 14, 2)->nullable();
            $table->decimal('value', 14, 2)->default(0); // movement value in XAF
            // What produced this movement (purchase_id, issue_line_id, …)
            $table->string('source_type', 40)->nullable();
            $table->char('source_id', 36)->nullable();
            $table->string('note', 500)->nullable();
            $table->foreignId('created_by_id')->nullable();
            $table->string('created_by_name', 120)->nullable();
            $table->timestamps();

            $table->index(['material_id', 'kind']);
            $table->index('source_type');
        });

        Schema::create('material_issues', function (Blueprint $table) {
            $table->char('id', 36)->primary();
            $table->string('code', 20)->unique(); // REM-0001 (Codes::materialIssue)
            $table->foreignId('order_id')->constrained('orders')->cascadeOnDelete();
            $table->foreignId('garment_id')->nullable()->constrained('garment_components')->nullOnDelete();
            $table->foreignId('task_id')->nullable()->constrained('production_tasks')->nullOnDelete();
            // Beneficiary employee (§5.1 four dimensions)
            $table->foreignId('staff_id')->constrained('users');
            $table->string('staff_name', 120);
            // OPEN | CLOSED | CANCELLED (Atelier::MATERIAL_ISSUE_STATUSES)
            $table->string('status', 12)->default('OPEN');
            $table->timestamp('issue_date');
            $table->string('note', 500)->nullable();
            $table->foreignId('closed_by_id')->nullable();
            $table->string('closed_by_name', 120)->nullable();
            $table->timestamp('closed_at')->nullable();
            $table->foreignId('created_by_id')->nullable();
            $table->string('created_by_name', 120)->nullable();
            $table->timestamps();

            $table->index(['status', 'issue_date']);
            $table->index('staff_id');
            $table->index('order_id');
        });

        Schema::create('material_issue_lines', function (Blueprint $table) {
            $table->char('id', 36)->primary();
            $table->char('issue_id', 36);
            $table->foreign('issue_id')->references('id')->on('material_issues')->cascadeOnDelete();
            $table->foreignId('material_id')->constrained('materials')->restrictOnDelete();
            $table->string('material_name', 190); // snapshot
            $table->string('unit', 12); // snapshot
            $table->decimal('qty_issued', 12, 3);
            // Weighted-average unit cost at issue time (0 for client-supplied)
            $table->decimal('unit_cost', 14, 2)->default(0);
            $table->timestamps();

            $table->index('issue_id');
            $table->index('material_id');
        });

        Schema::create('material_declarations', function (Blueprint $table) {
            $table->char('id', 36)->primary();
            $table->char('issue_line_id', 36);
            $table->foreign('issue_line_id')->references('id')->on('material_issue_lines')->cascadeOnDelete();
            $table->decimal('qty_consumed', 12, 3)->default(0);
            $table->decimal('qty_returned', 12, 3)->default(0);
            $table->string('note', 500)->nullable(); // employee comment / justification
            $table->foreignId('declared_by_id')->nullable(); // the employee
            $table->string('declared_by_name', 120)->nullable();
            $table->timestamp('declared_at');
            // PENDING | APPROVED | REJECTED (§5.8)
            $table->string('status', 12)->default('PENDING');
            $table->foreignId('reviewed_by_id')->nullable();
            $table->string('reviewed_by_name', 120)->nullable();
            $table->string('review_note', 500)->nullable(); // rejection motif / comment
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamps();

            $table->index(['status', 'declared_at']);
            $table->index('issue_line_id');
        });

        Schema::create('material_losses', function (Blueprint $table) {
            $table->char('id', 36)->primary();
            $table->char('issue_line_id', 36);
            $table->foreign('issue_line_id')->references('id')->on('material_issue_lines')->cascadeOnDelete();
            // Computed at closure: qty_issued − Σ approved consumed − Σ approved returned (§5.10)
            $table->decimal('qty_lost', 12, 3);
            $table->decimal('value', 14, 2)->default(0); // qty_lost × line unit_cost
            $table->string('justification', 500)->nullable();
            $table->timestamp('computed_at');
            $table->timestamps();

            $table->index('issue_line_id');
        });

        Schema::create('material_transfers', function (Blueprint $table) {
            $table->char('id', 36)->primary();
            $table->foreignId('from_staff_id')->constrained('users');
            $table->string('from_staff_name', 120);
            $table->foreignId('to_staff_id')->constrained('users');
            $table->string('to_staff_name', 120);
            $table->foreignId('order_id')->constrained('orders')->cascadeOnDelete();
            $table->foreignId('garment_id')->nullable()->constrained('garment_components')->nullOnDelete();
            $table->foreignId('task_id')->nullable()->constrained('production_tasks')->nullOnDelete();
            $table->foreignId('material_id')->constrained('materials')->restrictOnDelete();
            $table->string('material_name', 190); // snapshot
            $table->string('unit', 12); // snapshot
            $table->decimal('qty', 12, 3);
            $table->string('note', 500)->nullable();
            $table->foreignId('created_by_id')->nullable();
            $table->string('created_by_name', 120)->nullable();
            $table->timestamps();

            $table->index(['order_id', 'created_at']);
            $table->index('to_staff_id');
        });

        Schema::create('task_work_logs', function (Blueprint $table) {
            $table->char('id', 36)->primary();
            $table->foreignId('task_id')->constrained('production_tasks')->cascadeOnDelete();
            $table->foreignId('staff_id')->constrained('users');
            $table->string('staff_name', 120);
            $table->unsignedInteger('minutes');
            $table->date('logged_on');
            $table->string('note', 500)->nullable();
            $table->foreignId('created_by_id')->nullable();
            $table->timestamps();

            $table->index(['task_id', 'logged_on']);
            $table->index('staff_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('task_work_logs');
        Schema::dropIfExists('material_transfers');
        Schema::dropIfExists('material_losses');
        Schema::dropIfExists('material_declarations');
        Schema::dropIfExists('material_issue_lines');
        Schema::dropIfExists('material_issues');
        Schema::dropIfExists('material_stock_movements');
        Schema::dropIfExists('materials');
    }
};
