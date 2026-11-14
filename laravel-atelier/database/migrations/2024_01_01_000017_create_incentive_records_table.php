<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * `incentive_records` — generated incentive recommendations and their
 * approval/payment lifecycle.
 *
 * 1:1 port of the Prisma `IncentiveRecord` model. status: PENDING |
 * APPROVED | REJECTED ; paymentStatus: UNPAID | PAID. `scheme_id` /
 * `staff_id` / `approved_by_id` are snapshots (no relation in Prisma),
 * which is why there is no foreign key on this table.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('incentive_records', function (Blueprint $table) {
            // Prisma: id String @id @default(cuid())
            $table->char('id', 36)->primary();

            // Snapshot columns (no relation in Prisma).
            $table->char('scheme_id', 36);
            $table->string('scheme_name');
            $table->char('staff_id', 36);
            $table->string('staff_name');
            // Prisma: period String
            $table->string('period');
            // Prisma: performanceScore Float
            $table->double('performance_score');
            // Prisma: eligible Boolean
            $table->boolean('eligible');
            // Prisma: proposedAmount Float (FCFA)
            $table->double('proposed_amount');
            // Prisma: approvedAmount Float?
            $table->double('approved_amount')->nullable();
            // Prisma: status String @default("PENDING")
            $table->string('status')->default('PENDING');
            $table->char('approved_by_id', 36)->nullable();
            $table->string('approved_by_name')->nullable();
            $table->text('approval_comments')->nullable();
            $table->text('management_comments')->nullable();
            // Prisma: paymentStatus String @default("UNPAID")
            $table->string('payment_status')->default('UNPAID');
            $table->timestamp('payment_date')->nullable();

            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrent();

            // Prisma: @@unique([schemeId, staffId, period])
            $table->unique(['scheme_id', 'staff_id', 'period']);

            // Prisma: @@index([staffId]) + @@index([period]) + @@index([status])
            $table->index('staff_id');
            $table->index('period');
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('incentive_records');
    }
};
