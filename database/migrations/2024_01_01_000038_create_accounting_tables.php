<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * `accounting_*` — the general accounting layer of spec v1.3 §6
 * (SYSCOHADA referential, Cameroun).
 *
 *  - `accounting_accounts`: the chart of accounts (plan comptable). A
 *    compact SYSCOHADA subset is seeded here so existing deployments get
 *    a working chart from `php artisan migrate --force` alone; the
 *    accountant can extend it later. Final configuration and official
 *    use must be validated by a professional accountant (§6).
 *  - `journal_entries` + `journal_lines`: balanced double-entry
 *    journal. Entries are generated AUTOMATICALLY from recorded
 *    operations (payments, expenses, payroll, advances, transfers,
 *    revenue at order registration) via App\Services\AccountingPost —
 *    the user never picks an account. Manual entries (OD journal) are
 *    reserved for the Comptable role. The unique (source_type,
 *    source_id) index guarantees an operation is never posted twice.
 */
return new class extends Migration
{
    /** Compact SYSCOHADA chart: [code, name, type]. */
    private const CHART = [
        ['101', 'Capital social', 'EQUITY'],
        ['130', 'Résultat de l\'exercice', 'EQUITY'],
        ['241', 'Matériel et outillage', 'ASSET'],
        ['281', 'Amortissements du matériel', 'ASSET'],
        ['401', 'Fournisseurs', 'LIABILITY'],
        ['411', 'Clients', 'ASSET'],
        ['421', 'Personnel — avances et acomptes', 'LIABILITY'],
        ['521', 'Banques', 'ASSET'],
        ['522', 'Établissements financiers (Mobile Money)', 'ASSET'],
        ['571', 'Caisse', 'ASSET'],
        ['585', 'Virements internes', 'LIABILITY'],
        ['6021', 'Achats de matières et fournitures', 'EXPENSE'],
        ['608', 'Autres achats', 'EXPENSE'],
        ['618', 'Transports', 'EXPENSE'],
        ['624', 'Entretien et réparations', 'EXPENSE'],
        ['628', 'Autres charges externes', 'EXPENSE'],
        ['6611', 'Rémunérations du personnel', 'EXPENSE'],
        ['701', 'Ventes de produits finis', 'REVENUE'],
        ['812', 'Pertes sur matières premières', 'EXPENSE'],
    ];

    public function up(): void
    {
        Schema::create('accounting_accounts', function (Blueprint $table) {
            $table->char('id', 36)->primary();
            $table->string('code', 12)->unique();
            $table->string('name', 190);
            // ASSET | LIABILITY | EQUITY | REVENUE | EXPENSE (§6)
            $table->string('type', 12);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('journal_entries', function (Blueprint $table) {
            $table->char('id', 36)->primary();
            // VT | AC | BQ | CS | OD (Atelier::JOURNALS)
            $table->string('journal', 2)->default('OD');
            $table->date('entry_date');
            $table->string('reference', 60)->nullable();
            $table->string('description', 300);
            // What generated this entry (Payment, Expense, …); null for manual
            $table->string('source_type', 40)->nullable();
            $table->char('source_id', 36)->nullable();
            $table->boolean('is_generated')->default(true);
            $table->char('posted_by_id', 36)->nullable();
            $table->string('posted_by_name', 120)->nullable();
            $table->timestamps();

            $table->index(['entry_date', 'journal']);
            $table->unique(['source_type', 'source_id']);
        });

        Schema::create('journal_lines', function (Blueprint $table) {
            $table->char('id', 36)->primary();
            $table->char('entry_id', 36);
            $table->foreign('entry_id')->references('id')->on('journal_entries')->cascadeOnDelete();
            $table->char('account_id', 36);
            $table->foreign('account_id')->references('id')->on('accounting_accounts')->restrictOnDelete();
            $table->string('account_code', 12); // snapshot
            $table->string('label', 190)->nullable();
            $table->decimal('debit', 14, 2)->default(0);
            $table->decimal('credit', 14, 2)->default(0);
            $table->timestamps();

            $table->index('entry_id');
            $table->index('account_id');
        });

        // Seed the compact SYSCOHADA chart (existing installs included).
        if (DB::table('accounting_accounts')->count() === 0) {
            $now = now();
            foreach (self::CHART as [$code, $name, $type]) {
                DB::table('accounting_accounts')->insert([
                    'id' => (string) Illuminate\Support\Str::uuid(),
                    'code' => $code,
                    'name' => $name,
                    'type' => $type,
                    'is_active' => true,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('journal_lines');
        Schema::dropIfExists('journal_entries');
        Schema::dropIfExists('accounting_accounts');
    }
};
