<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Additive financial/workflow columns for the unified order flow and
 * order-level accounting (enhancement spec §3, §6, §11).
 *
 * - orders.total_value: the total order value in XAF (nullable — legacy
 *   orders may have no recorded value).
 * - orders.currency: ISO-4217 code, XAF by default (kept extensible).
 * - orders.status: DRAFT | REGISTERED. "Save as draft" keeps the order +
 *   its piece tasks out of staff notifications until finalized; legacy
 *   rows keep the default REGISTERED.
 * - production_tasks.quantity: pieces per task (spec §3.2 "Quantity" per
 *   garment piece). Legacy tasks default to 1.
 *
 * Strictly additive — no existing column is modified or dropped.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->decimal('total_value', 14, 2)->nullable();
            $table->string('currency', 3)->default('XAF');
            $table->string('status', 20)->default('REGISTERED');
        });

        Schema::table('production_tasks', function (Blueprint $table) {
            $table->integer('quantity')->default(1);
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn(['total_value', 'currency', 'status']);
        });

        Schema::table('production_tasks', function (Blueprint $table) {
            $table->dropColumn('quantity');
        });
    }
};
