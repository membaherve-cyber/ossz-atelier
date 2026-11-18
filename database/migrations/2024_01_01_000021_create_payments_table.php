<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * `payments` — customer payments received against orders (spec §6).
 *
 * Multiple payments per order are supported (deposit → balance); the
 * outstanding balance is always DERIVED (order total_value − SUM(payments))
 * and never stored, so edited orders or payments can never go stale.
 * References are system-generated (PAY-0001) and unique.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payments', function (Blueprint $table) {
            $table->char('id', 36)->primary();
            $table->string('reference', 20)->unique(); // PAY-0001
            $table->char('order_id', 36);
            $table->char('customer_id', 36);
            $table->decimal('amount', 14, 2);
            $table->string('currency', 3)->default('XAF');
            $table->date('payment_date');
            // CASH | MTN_MOMO | ORANGE_MONEY | BANK_TRANSFER | OTHER
            $table->string('method', 20)->default('CASH');
            $table->string('txn_ref', 100)->nullable();
            $table->text('notes')->nullable();
            // Snapshot of the recording user (uniform with the app convention).
            $table->char('recorded_by_id', 36)->nullable();
            $table->string('recorded_by_name')->nullable();

            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrent();

            $table->foreign('order_id')->references('id')->on('orders')->restrictOnDelete();
            $table->foreign('customer_id')->references('id')->on('customers')->restrictOnDelete();

            $table->index('order_id');
            $table->index('customer_id');
            $table->index('payment_date');
            $table->index('method');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
