<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * `orders` — manually registered garment orders (business reference
 * OSSZ-2026-001 etc.).
 *
 * 1:1 port of the Prisma `Order` model. The customer FK is RESTRICT
 * because the application rule is "a customer may only be deleted when
 * it has no orders" (spec §5).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('orders', function (Blueprint $table) {
            // Prisma: id String @id @default(cuid())
            $table->char('id', 36)->primary();

            // Prisma: orderNumber String @unique (manually entered)
            $table->string('order_number')->unique();
            $table->char('customer_id', 36);
            $table->timestamp('order_date');
            $table->timestamp('expected_delivery_date')->nullable();
            $table->text('description');
            $table->string('garment_category');
            // Prisma: numberOfPieces Int @default(1)
            $table->integer('number_of_pieces')->default(1);
            $table->text('instructions')->nullable();
            // Prisma: priority String @default("NORMAL") — LOW|NORMAL|HIGH|URGENT
            $table->string('priority')->default('NORMAL');
            $table->text('notes')->nullable();
            // Prisma: createdById String? (snapshot, no relation in Prisma)
            $table->char('created_by_id', 36)->nullable();

            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrent();

            // Prisma relation: customer (customers.id) — delete restricted.
            $table->foreign('customer_id')->references('id')->on('customers')->restrictOnDelete();

            // Prisma: @@index([customerId]) + @@index([orderDate]) + @@index([priority])
            $table->index('customer_id');
            $table->index('order_date');
            $table->index('priority');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
