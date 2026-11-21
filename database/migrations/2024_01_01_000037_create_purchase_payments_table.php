<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * `purchase_payments` — supplier purchase payment records (§4.6).
 *
 * A purchase's engagement and its actual payment are two distinct
 * events (§10): one purchase can be settled in several instalments.
 * Each row is one payment; the purchase's payment_status
 * (UNPAID | PARTIAL | PAID) and amount_paid are recomputed from these
 * rows, and each payment optionally lands on a treasury account.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('purchase_payments', function (Blueprint $table) {
            $table->char('id', 36)->primary();
            $table->char('purchase_id', 36);
            $table->foreign('purchase_id')->references('id')->on('purchases')->cascadeOnDelete();
            $table->decimal('amount', 14, 2);
            $table->timestamp('payment_date');
            $table->string('method', 20)->default('CASH'); // Atelier::PAYMENT_METHODS
            $table->char('treasury_account_id', 36)->nullable();
            $table->foreign('treasury_account_id')->references('id')->on('treasury_accounts')->nullOnDelete();
            $table->string('reference', 60)->nullable(); // txn ref
            $table->string('note', 500)->nullable();
            $table->char('recorded_by_id', 36)->nullable();
            $table->string('recorded_by_name', 120)->nullable();
            $table->timestamps();

            $table->index(['purchase_id', 'payment_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('purchase_payments');
    }
};
