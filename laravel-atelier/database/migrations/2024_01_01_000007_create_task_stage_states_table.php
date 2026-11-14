<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * `task_stage_states` — current state of a checklist stage for a task
 * (NOT_STARTED | IN_PROGRESS | COMPLETED | NOT_APPLICABLE).
 *
 * 1:1 port of the Prisma `TaskStageState` model: unique per
 * (task_id, stage_id); only `updated_at` exists (no created_at), exactly
 * like the Prisma model.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('task_stage_states', function (Blueprint $table) {
            // Prisma: id String @id @default(cuid())
            $table->char('id', 36)->primary();

            $table->char('task_id', 36);
            $table->char('stage_id', 36);
            // Prisma: state String @default("NOT_STARTED")
            $table->string('state')->default('NOT_STARTED');
            // Snapshot columns (no relation in Prisma).
            $table->char('updated_by_id', 36)->nullable();
            $table->string('updated_by_name')->nullable();

            // Prisma: updatedAt DateTime @updatedAt — only updated_at exists.
            $table->timestamp('updated_at')->useCurrent();

            // Prisma relation: task (production_tasks.id) — cascade.
            $table->foreign('task_id')->references('id')->on('production_tasks')->cascadeOnDelete();

            // Prisma: @@unique([taskId, stageId]) + @@index([taskId])
            $table->unique(['task_id', 'stage_id']);
            $table->index('task_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('task_stage_states');
    }
};
