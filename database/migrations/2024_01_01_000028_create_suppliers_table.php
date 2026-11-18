<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * `suppliers` — workshop material suppliers (spec §10).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('suppliers', function (Blueprint $table) {
            $table->char('id', 36)->primary();
            $table->string('name');
            $table->string('phone', 40)->nullable();
            $table->string('address', 200)->nullable();
            $table->string('email', 120)->nullable();
            // Materials typically supplied (free text).
            $table->string('materials', 300)->nullable();
            $table->text('notes')->nullable();

            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrent();

            $table->index('name');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('suppliers');
    }
};
