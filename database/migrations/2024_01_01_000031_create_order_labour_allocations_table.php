<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * `order_labour_allocations` — explicit, transparent labour cost
 * allocations against individual orders (spec §11).
 *
 * Salaries are NEVER auto-allocated to orders; Management records a
 * deliberate allocation (staff + amount + note) here, and order
 * profitability reports these amounts separately under "Allocated
 * labour" so actual recorded costs (expenses/purchases) stay
 * distinguishable from manual allocations.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('order_labour_allocations', function (Blueprint $table) {
            $table->char('id', 36)->primary();
            $table->char('order_id', 36);
            $table->char('staff_id', 36);
            $table->string('staff_name'); // snapshot
            $table->decimal('amount', 14, 2);
            $table->string('currency', 3)->default('XAF');
            // Allocation method note (e.g. "task-based", "20% of monthly").
            $table->string('method', 60)->default('MANUAL');
            $table->string('note', 300)->nullable();
            $table->char('created_by_id', 36)->nullable();
            $table->string('created_by_name')->nullable();

            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrent();

            $table->foreign('order_id')->references('id')->on('orders')->restrictOnDelete();
            $table->foreign('staff_id')->references('id')->on('users')->restrictOnDelete();

            $table->index('order_id');
            $table->index('staff_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('order_labour_allocations');
    }
};
