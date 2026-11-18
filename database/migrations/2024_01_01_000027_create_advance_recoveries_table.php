<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * `advance_recoveries` — immutable history of every advance deduction
 * applied through payroll (spec §9). Each row also keeps the optional
 * link to the payroll item that carried the deduction.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('advance_recoveries', function (Blueprint $table) {
            $table->char('id', 36)->primary();
            $table->char('advance_id', 36);
            $table->char('payroll_item_id', 36)->nullable();
            $table->decimal('amount', 14, 2);
            $table->timestamp('recovered_at')->useCurrent();
            $table->char('recovered_by_id', 36)->nullable();
            $table->string('recovered_by_name')->nullable();

            $table->timestamp('created_at')->useCurrent();

            $table->foreign('advance_id')->references('id')->on('staff_advances')->cascadeOnDelete();
            $table->foreign('payroll_item_id')->references('id')->on('payroll_items')->nullOnDelete();

            $table->index('advance_id');
            $table->unique(['advance_id', 'payroll_item_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('advance_recoveries');
    }
};
