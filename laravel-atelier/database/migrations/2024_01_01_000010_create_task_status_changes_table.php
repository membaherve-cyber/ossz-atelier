<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * `task_status_changes` — immutable status workflow history.
 *
 * 1:1 port of the Prisma `TaskStatusChange` model: `user_id` is a
 * snapshot (no FK in Prisma), only `task_id` cascades with its
 * production task.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('task_status_changes', function (Blueprint $table) {
            // Prisma: id String @id @default(cuid())
            $table->char('id', 36)->primary();

            $table->char('task_id', 36);
            // Snapshot columns (no relation in Prisma).
            $table->char('user_id', 36);
            $table->string('user_name');
            $table->string('from_status');
            $table->string('to_status');
            $table->string('reason')->nullable();

            // Prisma: createdAt DateTime @default(now()) — history table.
            $table->timestamp('created_at')->useCurrent();

            // Prisma relation: task (production_tasks.id) — cascade.
            $table->foreign('task_id')->references('id')->on('production_tasks')->cascadeOnDelete();

            // Prisma: @@index([taskId, createdAt]) + @@index([toStatus, createdAt])
            $table->index(['task_id', 'created_at']);
            $table->index(['to_status', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('task_status_changes');
    }
};
