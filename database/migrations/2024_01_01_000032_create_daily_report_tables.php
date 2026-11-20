<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * `daily_report_*` — automated daily business-health email (enhancement).
 *
 * Three tables:
 *  - `daily_report_settings` — exactly one row (id = 1): enable flag, send
 *    time (HH:MM, business timezone), section toggles, reply-to and the
 *    last successful send timestamp. Seeded here so EXISTING deployments
 *    get the defaults from `php artisan migrate --force` alone (no reseed).
 *  - `daily_report_recipients` — the e-mail distribution list, seeded with
 *    info@osszcollection.com. Addresses can be deactivated instead of
 *    deleted (audit-friendly).
 *  - `daily_report_logs` — one row per send run (scheduled / manual /
 *    test) with per-run success + failure counts, so double sends are
 *    detectable and delivery problems are traceable.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('daily_report_settings', function (Blueprint $table) {
            $table->id(); // always row 1
            $table->boolean('enabled')->default(true);
            $table->string('send_time', 5)->default('07:00'); // HH:MM
            $table->string('report_timezone', 64)->default('Africa/Douala');
            $table->string('reply_to', 190)->nullable();
            $table->boolean('include_production')->default(true);
            $table->boolean('include_financial')->default(true);
            $table->boolean('include_staff')->default(true);
            $table->boolean('include_alerts')->default(true);
            $table->unsignedBigInteger('updated_by_id')->nullable();
            $table->timestamp('last_sent_at')->nullable();
            $table->timestamps();
        });

        Schema::create('daily_report_recipients', function (Blueprint $table) {
            $table->char('id', 36)->primary();
            $table->string('email', 190)->unique();
            $table->string('name', 120)->nullable();
            $table->boolean('is_active')->default(true);
            $table->unsignedBigInteger('added_by_id')->nullable();
            $table->string('added_by_name', 120)->nullable();
            $table->timestamps();

            $table->index('is_active');
        });

        Schema::create('daily_report_logs', function (Blueprint $table) {
            $table->char('id', 36)->primary();
            $table->date('report_date');
            // SCHEDULED (cron) | MANUAL (Send Now) | TEST (test to my inbox)
            $table->string('trigger_type', 12);
            // SENT | PARTIAL | FAILED
            $table->string('status', 10);
            $table->unsignedTinyInteger('recipient_count')->default(0);
            $table->unsignedTinyInteger('success_count')->default(0);
            $table->unsignedTinyInteger('failure_count')->default(0);
            $table->text('recipients')->nullable(); // "to" list snapshot
            $table->text('error')->nullable(); // first transport error
            // Compact KPI snapshot so old reports stay reconstructable.
            $table->text('summary')->nullable(); // JSON
            $table->timestamp('sent_at');
            $table->timestamps();

            $table->index('report_date');
            $table->index('trigger_type');
        });

        // Defaults for brand-new AND existing installations alike.
        if (DB::table('daily_report_settings')->count() === 0) {
            DB::table('daily_report_settings')->insert([
                'id' => 1,
                'enabled' => true,
                'send_time' => '07:00',
                'report_timezone' => 'Africa/Douala',
                'reply_to' => null,
                'include_production' => true,
                'include_financial' => true,
                'include_staff' => true,
                'include_alerts' => true,
                'updated_by_id' => null,
                'last_sent_at' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        if (DB::table('daily_report_recipients')->count() === 0) {
            DB::table('daily_report_recipients')->insert([
                'id' => (string) Illuminate\Support\Str::uuid(),
                'email' => 'info@osszcollection.com',
                'name' => 'OSSZ Collection',
                'is_active' => true,
                'added_by_id' => null,
                'added_by_name' => 'System (default)',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('daily_report_logs');
        Schema::dropIfExists('daily_report_recipients');
        Schema::dropIfExists('daily_report_settings');
    }
};
