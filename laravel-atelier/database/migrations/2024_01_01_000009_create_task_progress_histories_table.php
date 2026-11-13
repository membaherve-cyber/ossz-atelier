<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * `task_progress_histories` — immutable numerical progress history
 * (append-only).
 *
 * 1:1 port of the Prisma `TaskProgressHistory` model: `user_id` is a
 * snapshot (no FK in Prisma), only `task_id` cascades with its
 * production task.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('task_progress_histories', function (Blueprint $table) {
            // Prisma: id String @id @default(cuid())
            $table->char('id', 36)->primary();

            $table->char('task_id', 36);
            // Snapshot columns (no relation in Prisma).
            $table->char('user_id', 36);
            $table->string('user_name');
            $table->integer('previous_progress');
            $table->integer('new_progress');
            // Prisma: source String @default("MANUAL") — MANUAL | STAGE | INSPECTION
            $table->string('source')->default('MANUAL');
            $table->string('note')->nullable();

            // Prisma: createdAt DateTime @default(now()) — history table.
            $table->timestamp('created_at')->useCurrent();

            // Prisma relation: task (production_tasks.id) — cascade.
            $table->foreign('task_id')->references('id')->on('production_tasks')->cascadeOnDelete();

            // Prisma: @@index([taskId, createdAt]) + @@index([userId, createdAt])
            $table->index(['task_id', 'created_at']);
            $table->index(['user_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('task_progress_histories');
    }
};
