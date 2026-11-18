<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * `salary_profiles` — per-staff remuneration structure (spec §8.1).
 *
 * Remuneration types: MONTHLY | DAILY | WEEKLY | TASK_BASED | COMMISSION |
 * OTHER. Several profiles per staff are allowed (history); payroll
 * generation uses the latest ACTIVE profile. Confidential — exposed only
 * to holders of the accounting.payroll permission.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('salary_profiles', function (Blueprint $table) {
            $table->char('id', 36)->primary();
            $table->char('staff_id', 36);
            // MONTHLY | DAILY | WEEKLY | TASK_BASED | COMMISSION | OTHER
            $table->string('remuneration_type', 20)->default('MONTHLY');
            $table->decimal('base_amount', 14, 2)->default(0);
            $table->string('currency', 3)->default('XAF');
            $table->date('effective_date');
            // Payment frequency note (free text, e.g. "end of month").
            $table->string('frequency', 40)->nullable();
            $table->decimal('allowances', 14, 2)->default(0);
            // ACTIVE | INACTIVE
            $table->string('status', 10)->default('ACTIVE');
            $table->text('remarks')->nullable();
            $table->char('created_by_id', 36)->nullable();
            $table->string('created_by_name')->nullable();

            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrent();

            $table->foreign('staff_id')->references('id')->on('users')->restrictOnDelete();

            $table->index(['staff_id', 'status']);
            $table->index('effective_date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('salary_profiles');
    }
};
