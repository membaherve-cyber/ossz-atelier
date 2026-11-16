<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * `performance_configs` — single global scoring configuration.
 *
 * 1:1 port of the Prisma `PerformanceConfig` model. The primary key is
 * the fixed string 'main' (NOT a UUID — the only table with a
 * non-UUID key). Weights must total 100; only `updated_at` exists
 * (Prisma has no createdAt).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('performance_configs', function (Blueprint $table) {
            // Prisma: id String @id @default("main") — fixed key 'main'.
            $table->string('id')->default('main')->primary();

            // Prisma: productivityWeight Float @default(30)
            $table->double('productivity_weight')->default(30);
            // Prisma: timelyWeight Float @default(25)
            $table->double('timely_weight')->default(25);
            // Prisma: qualityWeight Float @default(30)
            $table->double('quality_weight')->default(30);
            // Prisma: reliabilityWeight Float @default(10)
            $table->double('reliability_weight')->default(10);
            // Prisma: managementWeight Float @default(5)
            $table->double('management_weight')->default(5);
            // Prisma: pieceTarget Int @default(20) — pieces/month for 100% productivity
            $table->integer('piece_target')->default(20);

            // Prisma: updatedAt DateTime @updatedAt — only updated_at exists.
            $table->timestamp('updated_at')->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('performance_configs');
    }
};
