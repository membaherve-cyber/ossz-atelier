<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * `production_stages` — configurable checklist stages.
 *
 * 1:1 port of the Prisma `ProductionStage` model. `applies_to` is "ALL"
 * or a comma-separated list of task categories (e.g. "FULL,CUTTING");
 * `weight` drives the weighted progress calculation.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('production_stages', function (Blueprint $table) {
            // Prisma: id String @id @default(cuid())
            $table->char('id', 36)->primary();

            // Prisma: code String @unique (slug code, e.g. "quality-check")
            $table->string('code')->unique();
            $table->string('name');
            // Prisma: sortOrder Int @default(0)
            $table->integer('sort_order')->default(0);
            // Prisma: weight Float @default(1)
            $table->double('weight')->default(1);
            // Prisma: appliesTo String @default("ALL")
            $table->string('applies_to')->default('ALL');
            // Prisma: isActive Boolean @default(true)
            $table->boolean('is_active')->default(true);

            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrent();

            // Prisma: @@index([isActive, sortOrder])
            $table->index(['is_active', 'sort_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('production_stages');
    }
};
