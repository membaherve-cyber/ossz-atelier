<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * `expenses` — the workshop's single cost ledger (spec §7).
 *
 * Every operating cost lives here exactly once: manual entries, incentives
 * paid (source_type=INCENTIVE_RECORD, created by the incentive payment
 * flow — never double counted with payroll bonuses) and paid supplier
 * purchases (source_type=PURCHASE, created when a purchase is marked
 * PAID). Advance recoveries deliberately never create expenses (spec §9).
 *
 * Approval workflow: DRAFT → PENDING → APPROVED → PAID (or REJECTED).
 * Only APPROVED + PAID rows count as costs in dashboards/reports.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('expenses', function (Blueprint $table) {
            $table->char('id', 36)->primary();
            $table->string('reference', 20)->unique(); // EXP-0001
            $table->char('expense_category_id', 36);
            $table->string('description', 500);
            $table->decimal('amount', 14, 2);
            $table->string('currency', 3)->default('XAF');
            $table->date('date_incurred');
            $table->date('date_paid')->nullable();
            // CASH | MTN_MOMO | ORANGE_MONEY | BANK_TRANSFER | OTHER
            $table->string('method', 20)->nullable();
            // Person or supplier receiving the payment (free text).
            $table->string('payee', 120)->nullable();
            // Optional production links (spec §7.2 / §7.3).
            $table->char('order_id', 36)->nullable();
            $table->char('task_id', 36)->nullable();
            $table->char('responsible_staff_id', 36)->nullable();
            // Receipt/image attachment path under storage/app/receipts.
            $table->string('receipt_path', 255)->nullable();
            $table->text('remarks')->nullable();
            // DRAFT | PENDING | APPROVED | REJECTED | PAID
            $table->string('status', 20)->default('PENDING');
            // Auto-generated rows carry their source (dedupe guard).
            $table->string('source_type', 30)->nullable(); // INCENTIVE_RECORD | PURCHASE
            $table->char('source_id', 36)->nullable();
            $table->char('recorded_by_id', 36)->nullable();
            $table->string('recorded_by_name')->nullable();
            $table->char('approved_by_id', 36)->nullable();
            $table->string('approved_by_name')->nullable();
            $table->timestamp('approved_at')->nullable();

            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrent();

            $table->foreign('expense_category_id')->references('id')->on('expense_categories')->restrictOnDelete();
            $table->foreign('order_id')->references('id')->on('orders')->restrictOnDelete();
            $table->foreign('task_id')->references('id')->on('production_tasks')->restrictOnDelete();
            $table->foreign('responsible_staff_id')->references('id')->on('users')->restrictOnDelete();

            $table->index('expense_category_id');
            $table->index('date_incurred');
            $table->index('order_id');
            $table->index('status');
            // One ledger row per source (nullable → multiple NULLs allowed).
            $table->unique(['source_type', 'source_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('expenses');
    }
};
