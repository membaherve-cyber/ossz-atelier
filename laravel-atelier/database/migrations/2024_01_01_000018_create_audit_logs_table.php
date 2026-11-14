<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * `audit_logs` — permanent audit trail (Management-only visibility).
 *
 * 1:1 port of the Prisma `AuditLog` model: `user_id` / `user_name` are
 * snapshots (no FK), `details` is a JSON document stored as text,
 * append-only with `created_at`.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('audit_logs', function (Blueprint $table) {
            // Prisma: id String @id @default(cuid())
            $table->char('id', 36)->primary();

            // Snapshot columns (no relation in Prisma).
            $table->char('user_id', 36)->nullable();
            $table->string('user_name')->nullable();
            // Prisma: action String (audit.action.* codes)
            $table->string('action');
            $table->string('entity_type')->nullable();
            $table->char('entity_id', 36)->nullable();
            // Prisma: details String? — JSON payload
            $table->text('details')->nullable();

            // Prisma: createdAt DateTime @default(now()) — history table.
            $table->timestamp('created_at')->useCurrent();

            // Prisma: @@index([createdAt]) + @@index([entityType, entityId]) + @@index([action])
            $table->index('created_at');
            $table->index(['entity_type', 'entity_id']);
            $table->index('action');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('audit_logs');
    }
};
