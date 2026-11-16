<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * `notifications` — internal notifications.
 *
 * 1:1 port of the Prisma `Notification` model. Types: TASK_ASSIGNED |
 * TASK_REASSIGNED | DEADLINE_CHANGED | NEW_INSTRUCTIONS |
 * CORRECTIONS_REQUESTED | BLOCK_RESOLVED | TASK_APPROVED | TASK_REOPENED |
 * SUBMITTED_FOR_INSPECTION | TASK_OVERDUE | ISSUE_REPORTED | TASK_BLOCKED |
 * READY_FOR_PICKUP | TASK_DELIVERED. Titles are stored in English; views
 * translate them at display time via lang keys. `task_id` / `order_id`
 * are nullable references without FK constraints (Prisma has no
 * relation), `user_id` cascades with its user.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('notifications', function (Blueprint $table) {
            // Prisma: id String @id @default(cuid())
            $table->char('id', 36)->primary();

            $table->char('user_id', 36);
            $table->string('type');
            $table->string('title');
            $table->string('message')->nullable();
            // Nullable references without FK (no relation in Prisma).
            $table->char('task_id', 36)->nullable();
            $table->char('order_id', 36)->nullable();
            // Prisma: isRead Boolean @default(false)
            $table->boolean('is_read')->default(false);

            // Prisma: createdAt DateTime @default(now()) — history table.
            $table->timestamp('created_at')->useCurrent();

            // Prisma relation: user (users.id) — cascade.
            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();

            // Prisma: @@index([userId, isRead, createdAt]) + @@index([type, taskId])
            $table->index(['user_id', 'is_read', 'created_at']);
            $table->index(['type', 'task_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notifications');
    }
};
