<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * `task_stage_updates` — immutable record of every stage status change.
 *
 * 1:1 port of the Prisma `TaskStageUpdate` model: `stage_id` / `user_id`
 * are snapshots (no FK in Prisma), only `task_id` cascades with its
 * production task.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('task_stage_updates', function (Blueprint $table) {
            // Prisma: id String @id @default(cuid())
            $table->char('id', 36)->primary();

            $table->char('task_id', 36);
            $table->char('stage_id', 36);
            // Snapshot of the stage name at update time.
            $table->string('stage_name');
            // Snapshot columns (no relation in Prisma).
            $table->char('user_id', 36);
            $table->string('user_name');
            $table->string('previous_state');
            $table->string('new_state');
            $table->string('comment')->nullable();

            // Prisma: createdAt DateTime @default(now()) — history table.
            $table->timestamp('created_at')->useCurrent();

            // Prisma relation: task (production_tasks.id) — cascade.
            $table->foreign('task_id')->references('id')->on('production_tasks')->cascadeOnDelete();

            // Prisma: @@index([taskId, createdAt])
            $table->index(['task_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('task_stage_updates');
    }
};
