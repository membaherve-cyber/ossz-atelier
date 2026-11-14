<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * `inspection_records` — quality control decisions
 * (APPROVED | CHANGES_REQUESTED | REOPENED).
 *
 * 1:1 port of the Prisma `InspectionRecord` model: `inspector_id` is a
 * snapshot (no FK in Prisma), only `task_id` cascades with its
 * production task.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inspection_records', function (Blueprint $table) {
            // Prisma: id String @id @default(cuid())
            $table->char('id', 36)->primary();

            $table->char('task_id', 36);
            // Snapshot columns (no relation in Prisma).
            $table->char('inspector_id', 36);
            $table->string('inspector_name');
            // Prisma: decision String — APPROVED | CHANGES_REQUESTED | REOPENED
            $table->string('decision');
            $table->text('comments')->nullable();

            // Prisma: createdAt DateTime @default(now()) — history table.
            $table->timestamp('created_at')->useCurrent();

            // Prisma relation: task (production_tasks.id) — cascade.
            $table->foreign('task_id')->references('id')->on('production_tasks')->cascadeOnDelete();

            // Prisma: @@index([taskId, createdAt]) + @@index([inspectorId, createdAt])
            $table->index(['task_id', 'created_at']);
            $table->index(['inspector_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inspection_records');
    }
};
