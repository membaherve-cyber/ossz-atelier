<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * `staff_advances` — money advanced to staff before salary payment (spec §9).
 *
 * Status flow: PENDING_APPROVAL → APPROVED → DISBURSED →
 * (PARTIALLY_RECOVERED) → FULLY_RECOVERED, or CANCELLED from any
 * pre-disbursement state. amount_recovered mirrors the advance_recoveries
 * rows. Disbursements are cash out but NOT expenses (receivable);
 * recoveries reduce payroll net pay and never create expenses.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('staff_advances', function (Blueprint $table) {
            $table->char('id', 36)->primary();
            $table->string('reference', 20)->unique(); // ADV-0001
            $table->char('staff_id', 36);
            $table->string('staff_name'); // snapshot
            $table->decimal('amount', 14, 2);
            $table->string('currency', 3)->default('XAF');
            $table->date('date_issued');
            $table->string('reason', 500);
            // CASH | MTN_MOMO | ORANGE_MONEY | BANK_TRANSFER | OTHER
            $table->string('method', 20)->default('CASH');
            // Repayment arrangement note (free text).
            $table->string('repayment_plan', 120)->nullable();
            $table->decimal('amount_recovered', 14, 2)->default(0);
            // PENDING_APPROVAL | APPROVED | DISBURSED | PARTIALLY_RECOVERED | FULLY_RECOVERED | CANCELLED
            $table->string('status', 24)->default('PENDING_APPROVAL');
            $table->char('recorded_by_id', 36)->nullable();
            $table->string('recorded_by_name')->nullable();

            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrent();

            $table->foreign('staff_id')->references('id')->on('users')->restrictOnDelete();

            $table->index('staff_id');
            $table->index('status');
            $table->index('date_issued');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('staff_advances');
    }
};
