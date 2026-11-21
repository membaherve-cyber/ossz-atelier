<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * `budgets` + `budget_lines` — budgeting and forecasts (spec v1.3 §8).
 *
 * A budget covers a period (label + start/end) and holds one line per
 * planned revenue (global) or planned expense (global or per expense
 * category). Actuals are always DERIVED from the accounting module for
 * the same period (App\Services\BudgetService), so planned vs real
 * comparisons can never go stale. Forecasts stay distinct from the
 * costs actually recorded (§8).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('budgets', function (Blueprint $table) {
            $table->char('id', 36)->primary();
            $table->string('label', 120)->unique();
            $table->date('period_start');
            $table->date('period_end');
            // ACTIVE | CLOSED (Atelier::BUDGET_STATUSES)
            $table->string('status', 10)->default('ACTIVE');
            $table->string('notes', 500)->nullable();
            $table->char('created_by_id', 36)->nullable();
            $table->string('created_by_name', 120)->nullable();
            $table->timestamps();
        });

        Schema::create('budget_lines', function (Blueprint $table) {
            $table->char('id', 36)->primary();
            $table->char('budget_id', 36);
            $table->foreign('budget_id')->references('id')->on('budgets')->cascadeOnDelete();
            // REVENUE | EXPENSE (Atelier::BUDGET_LINE_KINDS)
            $table->string('kind', 10);
            // Null = the global line of its kind (not per category)
            $table->char('expense_category_id', 36)->nullable();
            $table->foreign('expense_category_id')->references('id')->on('expense_categories')->nullOnDelete();
            $table->decimal('planned', 14, 2)->default(0);
            $table->string('note', 500)->nullable();
            $table->timestamps();

            $table->index('budget_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('budget_lines');
        Schema::dropIfExists('budgets');
    }
};
