<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * `purchase_items` — purchase lines (description, quantity, unit price).
 * line_total = quantity × unit_price, recomputed server-side.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('purchase_items', function (Blueprint $table) {
            $table->char('id', 36)->primary();
            $table->char('purchase_id', 36);
            $table->string('description', 300);
            $table->decimal('quantity', 10, 2)->default(1);
            $table->decimal('unit_price', 14, 2)->default(0);
            $table->decimal('line_total', 14, 2)->default(0);

            $table->timestamp('created_at')->useCurrent();

            $table->foreign('purchase_id')->references('id')->on('purchases')->cascadeOnDelete();

            $table->index('purchase_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('purchase_items');
    }
};
