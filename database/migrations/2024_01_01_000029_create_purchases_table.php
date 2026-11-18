<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * `purchases` — material purchases from suppliers (spec §10).
 *
 * Items live in purchase_items; total_amount is the sum of line totals
 * (recomputed server-side). Marking a purchase PAID automatically books
 * one expense row (source_type=PURCHASE) in the materials category chosen
 * on the purchase, so supplier costs enter the single ledger exactly
 * once. Unpaid purchases stay out of the expense ledger.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('purchases', function (Blueprint $table) {
            $table->char('id', 36)->primary();
            $table->string('reference', 20)->unique(); // PUR-0001
            $table->char('supplier_id', 36);
            $table->string('supplier_name'); // snapshot
            $table->date('purchase_date');
            $table->string('description', 500);
            // Materials category used when the paid purchase books an expense.
            $table->char('expense_category_id', 36)->nullable();
            $table->decimal('total_amount', 14, 2)->default(0);
            $table->string('currency', 3)->default('XAF');
            // UNPAID | PARTIAL | PAID
            $table->string('payment_status', 10)->default('UNPAID');
            $table->string('method', 20)->nullable();
            $table->string('receipt_path', 255)->nullable();
            $table->char('order_id', 36)->nullable();
            $table->text('notes')->nullable();
            $table->char('recorded_by_id', 36)->nullable();
            $table->string('recorded_by_name')->nullable();

            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrent();

            $table->foreign('supplier_id')->references('id')->on('suppliers')->restrictOnDelete();
            $table->foreign('expense_category_id')->references('id')->on('expense_categories')->restrictOnDelete();
            $table->foreign('order_id')->references('id')->on('orders')->restrictOnDelete();

            $table->index('supplier_id');
            $table->index('purchase_date');
            $table->index('order_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('purchases');
    }
};
