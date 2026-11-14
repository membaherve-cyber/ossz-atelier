<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * `incentive_schemes` — incentive definitions.
 *
 * 1:1 port of the Prisma `IncentiveScheme` model. periodType: MONTHLY |
 * QUARTERLY | ANNUAL | QUALITY | ON_TIME | CUSTOM. `created_by_id` /
 * `created_by_name` are snapshots (no relation in Prisma).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('incentive_schemes', function (Blueprint $table) {
            // Prisma: id String @id @default(cuid())
            $table->char('id', 36)->primary();

            $table->string('name');
            // Prisma: periodType String — MONTHLY | QUARTERLY | ANNUAL | QUALITY | ON_TIME | CUSTOM
            $table->string('period_type');
            // Prisma: minScore Float?
            $table->double('min_score')->nullable();
            // Prisma: amount Float? (FCFA)
            $table->double('amount')->nullable();
            $table->text('description')->nullable();
            // Prisma: isActive Boolean @default(true)
            $table->boolean('is_active')->default(true);
            // Snapshot columns (no relation in Prisma).
            $table->char('created_by_id', 36)->nullable();
            $table->string('created_by_name')->nullable();

            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('incentive_schemes');
    }
};
