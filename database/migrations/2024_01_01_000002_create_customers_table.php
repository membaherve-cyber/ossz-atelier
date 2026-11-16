<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * `customers` — manually registered atelier customers.
 *
 * 1:1 port of the Prisma `Customer` model: UUID primary key, unique code
 * (CUST-0001), contact details, free-text address/notes and the
 * `createdById` snapshot (no FK — matches the Prisma schema).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('customers', function (Blueprint $table) {
            // Prisma: id String @id @default(cuid())
            $table->char('id', 36)->primary();

            // Prisma: code String @unique (CUST-0001)
            $table->string('code')->unique();
            $table->string('name');
            $table->string('phone');
            $table->string('email')->nullable();
            $table->text('address')->nullable();
            $table->text('notes')->nullable();
            // Prisma: createdById String? (snapshot, no relation in Prisma)
            $table->char('created_by_id', 36)->nullable();

            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrent();

            // Prisma: @@index([name])
            $table->index('name');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('customers');
    }
};
