<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * `staff_assessments` — manual Management assessment input (0-100) per
 * staff member per period ("2026-11" month | "2026-Q4" quarter |
 * "2026" year).
 *
 * 1:1 port of the Prisma `StaffAssessment` model: `staff_id` /
 * `staff_name` are snapshots (no FK), one row per (staff, period), and
 * only `updated_at` exists (Prisma has no createdAt).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('staff_assessments', function (Blueprint $table) {
            // Prisma: id String @id @default(cuid())
            $table->char('id', 36)->primary();

            // Snapshot columns (no relation in Prisma).
            $table->char('staff_id', 36);
            $table->string('staff_name');
            // Prisma: period String — "2026-11" | "2026-Q4" | "2026"
            $table->string('period');
            // Prisma: score Float @default(0)
            $table->double('score')->default(0);
            $table->text('comments')->nullable();

            // Prisma: updatedAt DateTime @updatedAt — only updated_at exists.
            $table->timestamp('updated_at')->useCurrent();

            // Prisma: @@unique([staffId, period])
            $table->unique(['staff_id', 'period']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('staff_assessments');
    }
};
