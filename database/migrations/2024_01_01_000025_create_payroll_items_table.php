<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * `payroll_items` — one line per staff member per payroll period (spec §8.2).
 *
 * net_pay = salary_amount + allowances + bonuses − deductions −
 * advance_recovered. advance_recovered is mirrored in advance_recoveries
 * (and updates the matching staff_advances row), so recovered advances
 * are never counted as new expenses (spec §9). Incentive bonuses are NOT
 * entered here — approved incentives are paid through the incentive flow
 * which books a dedicated expense, avoiding double counting (spec §13).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payroll_items', function (Blueprint $table) {
            $table->char('id', 36)->primary();
            $table->char('payroll_id', 36);
            $table->char('staff_id', 36);
            $table->string('staff_name'); // snapshot
            $table->decimal('salary_amount', 14, 2)->default(0);
            $table->decimal('allowances', 14, 2)->default(0);
            $table->decimal('deductions', 14, 2)->default(0);
            $table->decimal('advance_recovered', 14, 2)->default(0);
            $table->decimal('bonuses', 14, 2)->default(0);
            $table->decimal('net_pay', 14, 2)->default(0);
            // PENDING | PAID
            $table->string('payment_status', 10)->default('PENDING');
            $table->date('payment_date')->nullable();
            $table->string('method', 20)->nullable();
            $table->string('txn_ref', 100)->nullable();
            $table->text('notes')->nullable();

            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrent();

            $table->foreign('payroll_id')->references('id')->on('payrolls')->cascadeOnDelete();
            $table->foreign('staff_id')->references('id')->on('users')->restrictOnDelete();

            $table->unique(['payroll_id', 'staff_id']);
            $table->index('staff_id');
            $table->index('payment_status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payroll_items');
    }
};
