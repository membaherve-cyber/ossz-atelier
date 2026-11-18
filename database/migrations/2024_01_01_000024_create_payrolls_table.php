<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * `payrolls` — payroll periods (spec §8.2 / §8.3).
 *
 * Status flow: DRAFT → PENDING → APPROVED → PARTIALLY_PAID → PAID.
 * Items live in payroll_items (one per staff). Changes to approved
 * payrolls are audited (PAYROLL_UPDATED) — rows themselves are never
 * silently rewritten without a trace.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payrolls', function (Blueprint $table) {
            $table->char('id', 36)->primary();
            // Business label, e.g. "January 2027 Payroll".
            $table->string('label', 60)->unique();
            $table->date('period_start');
            $table->date('period_end');
            // DRAFT | PENDING | APPROVED | PARTIALLY_PAID | PAID
            $table->string('status', 20)->default('PENDING');
            $table->text('notes')->nullable();
            $table->char('created_by_id', 36)->nullable();
            $table->string('created_by_name')->nullable();
            $table->char('approved_by_id', 36)->nullable();
            $table->string('approved_by_name')->nullable();
            $table->timestamp('approved_at')->nullable();

            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrent();

            $table->index('period_start');
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payrolls');
    }
};
