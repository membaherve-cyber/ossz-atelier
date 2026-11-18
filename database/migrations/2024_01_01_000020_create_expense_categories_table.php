<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * `expense_categories` — configurable expense categories (spec §7.1).
 *
 * Groups: MATERIALS | TRANSPORT | OPERATIONS | SALARIES | ADMIN | OTHER.
 * System categories (is_system) are seeded lazily by
 * ExpenseCategory::ensureDefaults() and cannot be deleted, only
 * deactivated; Management can create additional categories (spec §7.1.F).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('expense_categories', function (Blueprint $table) {
            $table->char('id', 36)->primary();
            $table->string('name');
            // MATERIALS | TRANSPORT | OPERATIONS | SALARIES | ADMIN | OTHER
            $table->string('group', 20)->default('OTHER');
            $table->boolean('is_system')->default(false);
            $table->boolean('is_active')->default(true);
            // Snapshot of the creating user (uniform with the app convention).
            $table->char('created_by_id', 36)->nullable();
            $table->string('created_by_name')->nullable();

            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrent();

            $table->index(['group', 'is_active']);
            $table->index('name');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('expense_categories');
    }
};
