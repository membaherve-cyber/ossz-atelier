<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * `task_assignments` — immutable assignment / reassignment history.
 *
 * 1:1 port of the Prisma `TaskAssignment` model: `staff_id` /
 * `assigned_by_id` are snapshots (no FK in Prisma), only `task_id`
 * cascades with its production task.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('task_assignments', function (Blueprint $table) {
            // Prisma: id String @id @default(cuid())
            $table->char('id', 36)->primary();

            $table->char('task_id', 36);
            // Snapshot columns (no relation in Prisma).
            $table->char('staff_id', 36);
            $table->string('staff_name');
            $table->char('assigned_by_id', 36);
            $table->string('assigned_by_name');
            // Prisma: type String — ASSIGN | REASSIGN | UNASSIGN
            $table->string('type');
            $table->string('note')->nullable();

            // Prisma: createdAt DateTime @default(now()) — history table,
            // only created_at exists.
            $table->timestamp('created_at')->useCurrent();

            // Prisma relation: task (production_tasks.id) — cascade.
            $table->foreign('task_id')->references('id')->on('production_tasks')->cascadeOnDelete();

            // Prisma: @@index([taskId, createdAt]) + @@index([staffId, createdAt])
            $table->index(['task_id', 'created_at']);
            $table->index(['staff_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('task_assignments');
    }
};
