<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Finance module v1.3 — additive columns on existing tables.
 *
 *  - payments.treasury_account_id: the treasury account a customer
 *    payment landed on (§4.2/§4.4). Nullable: payments recorded before
 *    the module keep their method-derived routing.
 *  - expenses.supplier_id: optional supplier link on a current expense
 *    (§4.3 "association à un fournisseur"), next to the free-text payee.
 *  - production_tasks.is_rework: the "reprise" marker (§5.15) — its
 *    time and materials are imputed to the original order/garment and
 *    shown separately in profitability.
 *  - production_tasks.garment_id: explicit task → garment relation
 *    (§5.1 four traceability dimensions).
 *  - production_tasks.time_spent_minutes: optional total elapsed time
 *    (§5.13) alongside the granular task_work_logs.
 *  - orders.deposit_rate: configurable deposit percentage for the
 *    ready/pickup receivable alert (§5.15 "taux d'acompte").
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->char('treasury_account_id', 36)->nullable()->after('method');
            $table->foreign('treasury_account_id')->references('id')->on('treasury_accounts')->nullOnDelete();
        });

        Schema::table('expenses', function (Blueprint $table) {
            $table->foreignId('supplier_id')->nullable()->after('payee');
            $table->foreign('supplier_id')->references('id')->on('suppliers')->nullOnDelete();
        });

        Schema::table('production_tasks', function (Blueprint $table) {
            $table->boolean('is_rework')->default(false)->after('category');
            $table->char('garment_id', 36)->nullable()->after('order_id');
            $table->foreign('garment_id')->references('id')->on('garment_components')->nullOnDelete();
            $table->unsignedInteger('time_spent_minutes')->nullable()->after('progress');
        });

        Schema::table('orders', function (Blueprint $table) {
            $table->decimal('deposit_rate', 5, 2)->nullable()->after('total_value'); // percent
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn('deposit_rate');
        });

        Schema::table('production_tasks', function (Blueprint $table) {
            $table->dropColumn('time_spent_minutes');
            $table->dropForeign(['garment_id']);
            $table->dropColumn('garment_id');
            $table->dropColumn('is_rework');
        });

        Schema::table('expenses', function (Blueprint $table) {
            $table->dropForeign(['supplier_id']);
            $table->dropColumn('supplier_id');
        });

        Schema::table('payments', function (Blueprint $table) {
            $table->dropForeign(['treasury_account_id']);
            $table->dropColumn('treasury_account_id');
        });
    }
};
