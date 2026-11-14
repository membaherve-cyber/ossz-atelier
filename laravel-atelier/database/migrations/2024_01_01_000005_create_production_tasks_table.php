<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * `production_tasks` — one garment piece / activity per task.
 *
 * 1:1 port of the Prisma `ProductionTask` model. Status values:
 * UNASSIGNED | ASSIGNED | ONGOING | AWAITING_INSPECTION | CHANGES_REQUESTED
 * | APPROVED | READY_FOR_PICKUP | DELIVERED | BLOCKED | CANCELLED.
 *
 * `order_id` is RESTRICT (an order can only be deleted when it has no
 * tasks — spec §5); `assigned_staff_id` follows the Prisma default for
 * optional relations (SET NULL) so deleting an account unassigns tasks.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('production_tasks', function (Blueprint $table) {
            // Prisma: id String @id @default(cuid())
            $table->char('id', 36)->primary();

            // Prisma: code String @unique (TASK-0001, system generated)
            $table->string('code')->unique();
            $table->char('order_id', 36);
            $table->string('garment_piece');
            // Prisma: category String @default("FULL") — FULL|CUTTING|SEWING|FINISHING|IRONING|OTHER
            $table->string('category')->default('FULL');
            $table->text('instructions')->nullable();
            $table->timestamp('deadline')->nullable();
            // Prisma: priority String @default("NORMAL")
            $table->string('priority')->default('NORMAL');
            // Prisma: status String @default("UNASSIGNED")
            $table->string('status')->default('UNASSIGNED');
            // Prisma: progress Int @default(0) — 0-100
            $table->integer('progress')->default(0);
            $table->char('assigned_staff_id', 36)->nullable();
            $table->string('cancel_reason')->nullable();
            $table->string('blocked_reason')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('delivered_at')->nullable();
            // Prisma: createdById String? (snapshot, no relation in Prisma)
            $table->char('created_by_id', 36)->nullable();

            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrent();

            // Prisma relation: order (orders.id) — delete restricted.
            $table->foreign('order_id')->references('id')->on('orders')->restrictOnDelete();
            // Prisma relation: assignedStaff "TaskAssignee" (users.id) — optional => SET NULL.
            $table->foreign('assigned_staff_id')->references('id')->on('users')->nullOnDelete();

            // Prisma: @@index([orderId]) + @@index([assignedStaffId]) +
            //         @@index([status]) + @@index([deadline]) + @@index([updatedAt])
            $table->index('order_id');
            $table->index('assigned_staff_id');
            $table->index('status');
            $table->index('deadline');
            $table->index('updated_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('production_tasks');
    }
};
