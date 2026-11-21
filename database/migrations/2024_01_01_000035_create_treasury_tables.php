<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * `treasury_*` — the multi-account treasury of spec v1.3 §4.4.
 *
 * `treasury_accounts`: caisse physique, comptes bancaires, MTN Mobile
 * Money, Orange Money and any other configured account. Each account
 * declares the payment method (Atelier::PAYMENT_METHODS) it serves, so
 * payments/expenses can be routed to the right account automatically.
 *
 * `treasury_movements`: every money in/out with its source (receipt,
 * expense, purchase, payroll, advance, transfer leg, manual
 * deposit/withdrawal) — the cash journal (§4.4). Balances are always
 * DERIVED (opening_balance + Σ IN − Σ OUT), never stored. An internal
 * transfer produces two legs (TRANSFER_OUT + TRANSFER_IN) and is neither
 * a receipt nor an expense (§4.4). `reconciled` supports the
 * rapprochement bancaire.
 *
 * Seeded with the four standard accounts so existing deployments get a
 * working treasury from `php artisan migrate --force` alone.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('treasury_accounts', function (Blueprint $table) {
            $table->char('id', 36)->primary();
            $table->string('name', 120)->unique();
            // CASH | BANK | MOMO | OTHER (Atelier::TREASURY_KINDS)
            $table->string('kind', 10);
            // The payment method this account serves
            $table->string('method', 20);
            $table->decimal('opening_balance', 14, 2)->default(0);
            $table->boolean('is_active')->default(true);
            $table->string('notes', 500)->nullable();
            $table->timestamps();
        });

        Schema::create('treasury_movements', function (Blueprint $table) {
            $table->char('id', 36)->primary();
            $table->char('account_id', 36);
            $table->foreign('account_id')->references('id')->on('treasury_accounts')->cascadeOnDelete();
            // IN | OUT
            $table->string('direction', 3);
            // Atelier::TREASURY_MOVEMENT_KINDS
            $table->string('kind', 20);
            $table->decimal('amount', 14, 2);
            $table->timestamp('movement_date');
            // What produced this movement (Payment, Expense, …)
            $table->string('source_type', 40)->nullable();
            $table->char('source_id', 36)->nullable();
            $table->string('counterparty', 190)->nullable(); // client, fournisseur…
            $table->string('note', 500)->nullable();
            // Rapprochement bancaire (§4.4)
            $table->boolean('reconciled')->default(false);
            $table->timestamp('reconciled_at')->nullable();
            $table->char('recorded_by_id', 36)->nullable();
            $table->string('recorded_by_name', 120)->nullable();
            $table->timestamps();

            $table->index(['account_id', 'movement_date']);
            $table->index('source_type');
        });

        // Default accounts for brand-new AND existing installations.
        if (DB::table('treasury_accounts')->count() === 0) {
            $now = now();
            DB::table('treasury_accounts')->insert([
                ['id' => (string) Illuminate\Support\Str::uuid(), 'name' => 'Caisse principale', 'kind' => 'CASH', 'method' => 'CASH', 'opening_balance' => 0, 'is_active' => true, 'created_at' => $now, 'updated_at' => $now],
                ['id' => (string) Illuminate\Support\Str::uuid(), 'name' => 'Banque', 'kind' => 'BANK', 'method' => 'BANK_TRANSFER', 'opening_balance' => 0, 'is_active' => true, 'created_at' => $now, 'updated_at' => $now],
                ['id' => (string) Illuminate\Support\Str::uuid(), 'name' => 'MTN Mobile Money', 'kind' => 'MOMO', 'method' => 'MTN_MOMO', 'opening_balance' => 0, 'is_active' => true, 'created_at' => $now, 'updated_at' => $now],
                ['id' => (string) Illuminate\Support\Str::uuid(), 'name' => 'Orange Money', 'kind' => 'MOMO', 'method' => 'ORANGE_MONEY', 'opening_balance' => 0, 'is_active' => true, 'created_at' => $now, 'updated_at' => $now],
            ]);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('treasury_movements');
        Schema::dropIfExists('treasury_accounts');
    }
};
