<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * `garment_components` — the vêtement/pièce entity of spec v1.3 §5.2.
 *
 * Each garment (veste, chemise, pantalon…) of an order is a first-class
 * row with a readable code derived from its order number
 * (OSSZ-2026-001 → OSSZ-2026-001-V01, -V02, …) plus an explicit
 * order_id relation, so the order of origin is recognisable at a glance
 * AND enforced by the database (not only by the code text). Codes are
 * unique and never reused.
 *
 * production_tasks.garment_id (added in 000036) links each task to the
 * exact piece it works on; material issues can target a garment too
 * (§5.4 traceability dimension "vêtement ou pièce").
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('garment_components', function (Blueprint $table) {
            $table->char('id', 36)->primary();
            $table->foreignId('order_id')->constrained('orders')->cascadeOnDelete();
            // Readable order-derived code: {ORDER_NUMBER}-V01 … (§5.2)
            $table->string('code', 64)->unique();
            $table->string('name', 120); // "Veste", "Chemise n°3"…
            $table->string('notes', 500)->nullable();
            $table->timestamps();

            $table->index('order_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('garment_components');
    }
};
