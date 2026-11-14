<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * `users` — atelier staff accounts (MANAGEMENT | ADMIN | STAFF).
 *
 * 1:1 port of the Prisma `User` model from prisma/schema.prisma:
 * UUID primary key, unique system code (USR-001), unique email, password
 * hash and the role / active flag used by App\Services\Permissions.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('users', function (Blueprint $table) {
            // Prisma: id String @id @default(cuid())
            $table->char('id', 36)->primary();

            // Prisma: code String? @unique (USR-001)
            $table->string('code')->nullable()->unique();
            $table->string('name');
            $table->string('email')->unique();
            $table->string('phone')->nullable();
            $table->string('job_title')->nullable();
            // Prisma: passwordHash String
            $table->string('password_hash');
            // Prisma: role String @default("STAFF")
            $table->string('role')->default('STAFF');
            // Prisma: isActive Boolean @default(true)
            $table->boolean('is_active')->default(true);

            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrent();

            // Prisma: @@index([role, isActive]) + @@index([isActive])
            $table->index(['role', 'is_active']);
            $table->index('is_active');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('users');
    }
};
