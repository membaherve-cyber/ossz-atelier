<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * `task_comments` — production notes & reported issues.
 *
 * 1:1 port of the Prisma `TaskComment` model: `user_id` is a snapshot
 * (no FK in Prisma), only `task_id` cascades with its production task.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('task_comments', function (Blueprint $table) {
            // Prisma: id String @id @default(cuid())
            $table->char('id', 36)->primary();

            $table->char('task_id', 36);
            // Snapshot columns (no relation in Prisma).
            $table->char('user_id', 36);
            $table->string('user_name');
            $table->text('message');
            // Prisma: isIssue Boolean @default(false)
            $table->boolean('is_issue')->default(false);

            // Prisma: createdAt DateTime @default(now()) — history table.
            $table->timestamp('created_at')->useCurrent();

            // Prisma relation: task (production_tasks.id) — cascade.
            $table->foreign('task_id')->references('id')->on('production_tasks')->cascadeOnDelete();

            // Prisma: @@index([taskId, createdAt]) + @@index([isIssue, createdAt])
            $table->index(['task_id', 'created_at']);
            $table->index(['is_issue', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('task_comments');
    }
};
