<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\AuditLog;
use App\Models\Customer;
use App\Models\IncentiveRecord;
use App\Models\IncentiveScheme;
use App\Models\InspectionRecord;
use App\Models\Notification;
use App\Models\Order;
use App\Models\PerformanceConfig;
use App\Models\ProductionStage;
use App\Models\ProductionTask;
use App\Models\StaffAssessment;
use App\Models\TaskAssignment;
use App\Models\TaskComment;
use App\Models\TaskProgressHistory;
use App\Models\TaskStageState;
use App\Models\TaskStageUpdate;
use App\Models\TaskStatusChange;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

/**
 * OSSZ ATELIER — standalone database seed.
 *
 * 1:1 Laravel port of prisma/seed.ts: creates the three account roles,
 * the 12 configurable production stages, the performance configuration,
 * incentive schemes and a realistic demo dataset matching the
 * specification scenario (John Doe / OSSZ-2026-001 three-piece suit,
 * allocated to Hamidou and Lawal) plus historical completed work for
 * the performance and incentive modules.
 *
 * Codes (USR-001, CUST-0001, TASK-0001 …) are the EXACT codes from the
 * reference seed — the Codes service is deliberately NOT used here so
 * the demo data matches the Next.js reference application.
 *
 * Idempotent: re-running against a database that already contains
 * users is a no-op, so `php artisan db:seed` never duplicates rows.
 */
class DatabaseSeeder extends Seeder
{
    /** Shared password for every seeded demo account. */
    private const DEMO_PASSWORD = 'Atelier@2026';

    /**
     * Configurable production stages (specification checklist):
     * [code, name, sortOrder, appliesTo] — every stage weighs 1.
     */
    private const STAGE_DEFS = [
        ['assigned', 'Assigned', 1, 'ALL'],
        ['ongoing', 'Ongoing', 2, 'ALL'],
        ['cutting', 'Cutting', 3, 'FULL,CUTTING'],
        ['sewing', 'Sewing/Stitching', 4, 'FULL,SEWING'],
        ['fitting', 'Fitting', 5, 'FULL,SEWING'],
        ['finishing', 'Finishing', 6, 'FULL,FINISHING'],
        ['ironing', 'Ironing', 7, 'FULL,IRONING,FINISHING'],
        ['quality-check', 'Quality Check', 8, 'ALL'],
        ['ready-for-pickup', 'Ready for Pickup', 9, 'ALL'],
        ['delivered', 'Delivered/Collected', 10, 'ALL'],
        ['blocked', 'Blocked', 11, 'ALL'],
        ['other', 'Other', 12, 'ALL'],
    ];

    /** @var array<string, User> demo accounts, keyed by first name (aminata, david, hamidou, lawal, ngozi). */
    private array $users = [];

    /** @var array<string, Customer> demo customers, keyed by first name (john, amina). */
    private array $customers = [];

    /** @var array<string, string> stage code => production stage id. */
    private array $stages = [];

    /** @var array<string, Order> demo orders, keyed by short name (suit, agbada, senegal, kaftan). */
    private array $orders = [];

    /** @var array<string, ProductionTask> demo tasks, keyed t1…t9. */
    private array $tasks = [];

    /** The monthly incentive scheme (referenced by the demo incentive records). */
    private ?IncentiveScheme $monthlyScheme = null;

    public function run(): void
    {
        // Idempotency guard — never duplicate an already-seeded database.
        if (User::count() > 0) {
            echo "Already seeded — users exist, nothing to do.\n";

            return;
        }

        echo "Seeding OSSZ ATELIER standalone database...\n";

        $this->seedUsers();              // §1 accounts (Management / Admin / Staff)
        $this->seedStages();             // §2 configurable production stages
        $this->seedPerformanceConfig();  // §3 performance config + incentive schemes
        $this->seedCustomersAndOrders(); // §4 customers & manually registered orders
        $this->seedTasks();              // §5 live production + §6 historical work
        $this->seedHistoryAndExtras();   // §7 comments, §8 notifications, §9 assessments/incentives, §10 audit

        echo "Seed complete.\n";
        echo 'Demo accounts (password: '.self::DEMO_PASSWORD."):\n";
        echo "  MANAGEMENT  management@atelier.com\n";
        echo "  ADMIN       admin@atelier.com\n";
        echo "  STAFF       hamidou@atelier.com\n";
        echo "  STAFF       lawal@atelier.com\n";
        echo "  STAFF       ngozi@atelier.com\n";
    }

    /*
    |--------------------------------------------------------------------------
    | §1 Accounts
    |--------------------------------------------------------------------------
    */

    /**
     * Create the five demo accounts: one MANAGEMENT, one ADMIN and
     * three STAFF users (USR-001…USR-005), all sharing the demo
     * password hashed with bcrypt (Hash::make).
     */
    private function seedUsers(): void
    {
        $this->users['aminata'] = User::create([
            'code' => 'USR-001',
            'name' => 'Aminata Sow',
            'email' => 'management@atelier.com',
            'phone' => '+237 677 000 001',
            'job_title' => 'Atelier Director',
            'password_hash' => Hash::make(self::DEMO_PASSWORD),
            'role' => 'MANAGEMENT',
        ]);

        $this->users['david'] = User::create([
            'code' => 'USR-002',
            'name' => 'David Nkeng',
            'email' => 'admin@atelier.com',
            'phone' => '+237 677 000 002',
            'job_title' => 'Production Administrator',
            'password_hash' => Hash::make(self::DEMO_PASSWORD),
            'role' => 'ADMIN',
        ]);

        $this->users['hamidou'] = User::create([
            'code' => 'USR-003',
            'name' => 'Hamidou Abdoul',
            'email' => 'hamidou@atelier.com',
            'phone' => '+237 677 000 003',
            'job_title' => 'Master Tailor',
            'password_hash' => Hash::make(self::DEMO_PASSWORD),
            'role' => 'STAFF',
        ]);

        $this->users['lawal'] = User::create([
            'code' => 'USR-004',
            'name' => 'Lawal Sani',
            'email' => 'lawal@atelier.com',
            'phone' => '+237 677 000 004',
            'job_title' => 'Tailor',
            'password_hash' => Hash::make(self::DEMO_PASSWORD),
            'role' => 'STAFF',
        ]);

        $this->users['ngozi'] = User::create([
            'code' => 'USR-005',
            'name' => 'Ngozi Okafor',
            'email' => 'ngozi@atelier.com',
            'phone' => '+237 677 000 005',
            'job_title' => 'Finisher & Ironing',
            'password_hash' => Hash::make(self::DEMO_PASSWORD),
            'role' => 'STAFF',
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | §2 Production stages
    |--------------------------------------------------------------------------
    */

    /**
     * Create the 12 configurable checklist stages (weight 1 each) and
     * keep a code => id map for the task stage states created later.
     */
    private function seedStages(): void
    {
        foreach (self::STAGE_DEFS as [$code, $name, $sortOrder, $appliesTo]) {
            $stage = ProductionStage::create([
                'code' => $code,
                'name' => $name,
                'sort_order' => $sortOrder,
                'applies_to' => $appliesTo,
                'weight' => 1,
            ]);

            $this->stages[$code] = $stage->id;
        }
    }

    /*
    |--------------------------------------------------------------------------
    | §3 Performance configuration & incentive schemes
    |--------------------------------------------------------------------------
    */

    /**
     * Create the single 'main' performance configuration (weights
     * 30/25/30/10/5 totalling 100, pieceTarget 20/month) and the three
     * demo incentive schemes authored by Management.
     */
    private function seedPerformanceConfig(): void
    {
        PerformanceConfig::create([
            'id' => 'main',
            'productivity_weight' => 30,
            'timely_weight' => 25,
            'quality_weight' => 30,
            'reliability_weight' => 10,
            'management_weight' => 5,
            'piece_target' => 20,
        ]);

        $this->monthlyScheme = IncentiveScheme::create([
            'name' => 'Monthly Excellence Bonus',
            'period_type' => 'MONTHLY',
            'min_score' => 75,
            'amount' => 25000,
            'description' => 'Awarded to staff whose overall monthly performance score reaches 75/100 or above.',
            'created_by_id' => $this->users['aminata']->id,
            'created_by_name' => $this->users['aminata']->name,
        ]);

        IncentiveScheme::create([
            'name' => 'Annual Excellence Award',
            'period_type' => 'ANNUAL',
            'min_score' => 80,
            'amount' => 250000,
            'description' => 'Annual recognition of sustained excellence across the year.',
            'created_by_id' => $this->users['aminata']->id,
            'created_by_name' => $this->users['aminata']->name,
        ]);

        IncentiveScheme::create([
            'name' => 'Quality Champion',
            'period_type' => 'QUALITY',
            'min_score' => 90,
            'amount' => 15000,
            'description' => 'For a monthly quality approval rate of 90% or above with at least 3 completions.',
            'created_by_id' => $this->users['aminata']->id,
            'created_by_name' => $this->users['aminata']->name,
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | §4 Customers & orders
    |--------------------------------------------------------------------------
    */

    /**
     * Create the two demo customers and their four manually registered
     * orders (OSSZ-2026-001, OSSZ-2026-002, OSSZ-2025-014, OSSZ-2025-009).
     */
    private function seedCustomersAndOrders(): void
    {
        $aminata = $this->users['aminata'];
        $david = $this->users['david'];

        $this->customers['john'] = Customer::create([
            'code' => 'CUST-0001',
            'name' => 'John Doe',
            'phone' => '+237 677 123 456',
            'email' => 'johndoe@example.com',
            'address' => 'Bonapriso, Douala',
            'notes' => 'Prefers slim-fit cuts. Returning customer.',
            'created_by_id' => $aminata->id,
            'created_at' => $this->daysAgo(21),
        ]);

        $this->customers['amina'] = Customer::create([
            'code' => 'CUST-0002',
            'name' => 'Amina Cissé',
            'phone' => '+237 699 551 122',
            'email' => 'amina.cisse@example.com',
            'address' => 'Bastos, Yaoundé',
            'notes' => 'Measures on file from January fitting.',
            'created_by_id' => $david->id,
            'created_at' => $this->daysAgo(46),
        ]);

        $this->orders['suit'] = Order::create([
            'order_number' => 'OSSZ-2026-001',
            'customer_id' => $this->customers['john']->id,
            'order_date' => $this->daysAgo(20),
            'expected_delivery_date' => $this->daysFromNow(10),
            'description' => 'Three-piece suit',
            'garment_category' => 'Suit',
            'number_of_pieces' => 3,
            'instructions' => 'Slim fit, midnight blue fabric, gold buttons.',
            'priority' => 'HIGH',
            'notes' => 'Customer expects a fitting before finishing.',
            'created_by_id' => $aminata->id,
            'created_at' => $this->daysAgo(20),
        ]);

        $this->orders['agbada'] = Order::create([
            'order_number' => 'OSSZ-2026-002',
            'customer_id' => $this->customers['amina']->id,
            'order_date' => $this->daysAgo(6),
            'expected_delivery_date' => $this->daysFromNow(24),
            'description' => 'Agbada two-piece set',
            'garment_category' => 'Agbada',
            'number_of_pieces' => 2,
            'instructions' => 'Embroidered neckline, matching trousers.',
            'priority' => 'NORMAL',
            'created_by_id' => $david->id,
            'created_at' => $this->daysAgo(6),
        ]);

        $this->orders['senegal'] = Order::create([
            'order_number' => 'OSSZ-2025-014',
            'customer_id' => $this->customers['amina']->id,
            'order_date' => $this->daysAgo(45),
            'expected_delivery_date' => $this->daysAgo(12),
            'description' => 'Senegal two-piece with headwrap',
            'garment_category' => 'Senegal',
            'number_of_pieces' => 3,
            'priority' => 'NORMAL',
            'created_by_id' => $aminata->id,
            'created_at' => $this->daysAgo(45),
        ]);

        $this->orders['kaftan'] = Order::create([
            'order_number' => 'OSSZ-2025-009',
            'customer_id' => $this->customers['john']->id,
            'order_date' => $this->daysAgo(75),
            'expected_delivery_date' => $this->daysAgo(55),
            'description' => 'Grand kaftan',
            'garment_category' => 'Kaftan',
            'number_of_pieces' => 1,
            'priority' => 'NORMAL',
            'created_by_id' => $aminata->id,
            'created_at' => $this->daysAgo(75),
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | §5 Live production + §6 Historical work
    |--------------------------------------------------------------------------
    */

    /**
     * Create the production tasks: the current live production
     * (TASK-0001…TASK-0005 for the suit and agbada orders) and the
     * historical completed/delivered work TASK-0006…TASK-0009 that
     * feeds the performance and incentive modules.
     */
    private function seedTasks(): void
    {
        $suit = $this->orders['suit'];
        $agbada = $this->orders['agbada'];
        $senegal = $this->orders['senegal'];
        $kaftan = $this->orders['kaftan'];
        $aminata = $this->users['aminata'];
        $david = $this->users['david'];
        $hamidou = $this->users['hamidou'];
        $lawal = $this->users['lawal'];

        // ------------------------------------------------------------------
        // 5. Current live production (John Doe three-piece suit).
        // ------------------------------------------------------------------

        $this->tasks['t1'] = $this->createTask([
            'code' => 'TASK-0001',
            'order_id' => $suit->id,
            'garment_piece' => 'Jacket',
            'instructions' => 'Attach the customer-provided gold buttons. Single-breasted, two vents.',
            'deadline' => $this->daysFromNow(12),
            'priority' => 'HIGH',
            'status' => 'ONGOING',
            'progress' => 60,
            'assigned_staff_id' => $hamidou->id,
            'created_at' => $this->daysAgo(15),
            'stage_states' => [
                ['assigned', 'COMPLETED'],
                ['ongoing', 'COMPLETED'],
                ['cutting', 'COMPLETED'],
                ['sewing', 'IN_PROGRESS'],
                ['fitting', 'NOT_STARTED'],
                ['finishing', 'NOT_STARTED'],
                ['ironing', 'NOT_STARTED'],
                ['quality-check', 'NOT_STARTED'],
                ['ready-for-pickup', 'NOT_STARTED'],
                ['delivered', 'NOT_STARTED'],
                ['blocked', 'NOT_APPLICABLE'],
                ['other', 'NOT_APPLICABLE'],
            ],
            'stage_updates' => [
                ['stage_code' => 'assigned', 'from' => 'NOT_STARTED', 'to' => 'COMPLETED', 'by' => 'hamidou', 'at' => $this->daysAgo(15)],
                ['stage_code' => 'ongoing', 'from' => 'NOT_STARTED', 'to' => 'COMPLETED', 'by' => 'hamidou', 'at' => $this->daysAgo(14)],
                ['stage_code' => 'cutting', 'from' => 'NOT_STARTED', 'to' => 'IN_PROGRESS', 'by' => 'hamidou', 'at' => $this->daysAgo(12)],
                ['stage_code' => 'cutting', 'from' => 'IN_PROGRESS', 'to' => 'COMPLETED', 'by' => 'hamidou', 'at' => $this->daysAgo(10)],
                ['stage_code' => 'sewing', 'from' => 'NOT_STARTED', 'to' => 'IN_PROGRESS', 'by' => 'hamidou', 'at' => $this->daysAgo(8)],
            ],
            'status_chain' => [
                ['to' => 'ASSIGNED', 'by' => 'aminata', 'at' => $this->daysAgo(15)],
                ['to' => 'ONGOING', 'by' => 'hamidou', 'at' => $this->daysAgo(14)],
            ],
            'progress_history' => [
                ['from' => 0, 'to' => 25, 'by' => 'hamidou', 'at' => $this->daysAgo(14), 'note' => 'Fabric cut, assembly started.'],
                ['from' => 25, 'to' => 45, 'by' => 'hamidou', 'at' => $this->daysAgo(8)],
                ['from' => 45, 'to' => 60, 'by' => 'hamidou', 'at' => $this->daysAgo(2), 'note' => 'Body assembled, sleeves set.'],
            ],
        ]);

        $this->tasks['t2'] = $this->createTask([
            'code' => 'TASK-0002',
            'order_id' => $suit->id,
            'garment_piece' => 'Trousers',
            'instructions' => 'Slim fit, side adjusters instead of belt loops.',
            'deadline' => $this->daysFromNow(12),
            'priority' => 'HIGH',
            'status' => 'ONGOING',
            'progress' => 80,
            'assigned_staff_id' => $hamidou->id,
            'created_at' => $this->daysAgo(15),
            'stage_states' => [
                ['assigned', 'COMPLETED'],
                ['ongoing', 'COMPLETED'],
                ['cutting', 'COMPLETED'],
                ['sewing', 'COMPLETED'],
                ['fitting', 'COMPLETED'],
                ['finishing', 'IN_PROGRESS'],
                ['ironing', 'NOT_STARTED'],
                ['quality-check', 'NOT_STARTED'],
                ['ready-for-pickup', 'NOT_STARTED'],
                ['delivered', 'NOT_STARTED'],
                ['blocked', 'NOT_APPLICABLE'],
                ['other', 'NOT_APPLICABLE'],
            ],
            'stage_updates' => [
                ['stage_code' => 'assigned', 'from' => 'NOT_STARTED', 'to' => 'COMPLETED', 'by' => 'hamidou', 'at' => $this->daysAgo(15)],
                ['stage_code' => 'ongoing', 'from' => 'NOT_STARTED', 'to' => 'COMPLETED', 'by' => 'hamidou', 'at' => $this->daysAgo(15, 12)],
                ['stage_code' => 'cutting', 'from' => 'NOT_STARTED', 'to' => 'COMPLETED', 'by' => 'hamidou', 'at' => $this->daysAgo(11)],
                ['stage_code' => 'sewing', 'from' => 'NOT_STARTED', 'to' => 'COMPLETED', 'by' => 'hamidou', 'at' => $this->daysAgo(7)],
                ['stage_code' => 'fitting', 'from' => 'NOT_STARTED', 'to' => 'COMPLETED', 'by' => 'hamidou', 'at' => $this->daysAgo(4)],
                ['stage_code' => 'finishing', 'from' => 'NOT_STARTED', 'to' => 'IN_PROGRESS', 'by' => 'hamidou', 'at' => $this->daysAgo(2)],
            ],
            'status_chain' => [
                ['to' => 'ASSIGNED', 'by' => 'aminata', 'at' => $this->daysAgo(15)],
                ['to' => 'ONGOING', 'by' => 'hamidou', 'at' => $this->daysAgo(15, 12)],
            ],
            'progress_history' => [
                ['from' => 0, 'to' => 30, 'by' => 'hamidou', 'at' => $this->daysAgo(14)],
                ['from' => 30, 'to' => 60, 'by' => 'hamidou', 'at' => $this->daysAgo(7)],
                ['from' => 60, 'to' => 80, 'by' => 'hamidou', 'at' => $this->daysAgo(3), 'note' => 'Fitting done, finishing hem.'],
            ],
        ]);

        $this->tasks['t3'] = $this->createTask([
            'code' => 'TASK-0003',
            'order_id' => $suit->id,
            'garment_piece' => 'Vest',
            'deadline' => $this->daysAgo(2), // overdue
            'priority' => 'HIGH',
            'status' => 'ONGOING',
            'progress' => 30,
            'assigned_staff_id' => $lawal->id,
            'created_at' => $this->daysAgo(14),
            'stage_states' => [
                ['assigned', 'COMPLETED'],
                ['ongoing', 'COMPLETED'],
                ['cutting', 'IN_PROGRESS'],
                ['sewing', 'NOT_STARTED'],
                ['fitting', 'NOT_STARTED'],
                ['finishing', 'NOT_STARTED'],
                ['ironing', 'NOT_STARTED'],
                ['quality-check', 'NOT_STARTED'],
                ['ready-for-pickup', 'NOT_STARTED'],
                ['delivered', 'NOT_STARTED'],
                ['blocked', 'NOT_APPLICABLE'],
                ['other', 'NOT_APPLICABLE'],
            ],
            'stage_updates' => [
                ['stage_code' => 'assigned', 'from' => 'NOT_STARTED', 'to' => 'COMPLETED', 'by' => 'lawal', 'at' => $this->daysAgo(14)],
                ['stage_code' => 'ongoing', 'from' => 'NOT_STARTED', 'to' => 'COMPLETED', 'by' => 'lawal', 'at' => $this->daysAgo(13)],
                ['stage_code' => 'cutting', 'from' => 'NOT_STARTED', 'to' => 'IN_PROGRESS', 'by' => 'lawal', 'at' => $this->daysAgo(9)],
            ],
            'status_chain' => [
                ['to' => 'ASSIGNED', 'by' => 'aminata', 'at' => $this->daysAgo(14)],
                ['to' => 'ONGOING', 'by' => 'lawal', 'at' => $this->daysAgo(13)],
            ],
            'progress_history' => [
                ['from' => 0, 'to' => 15, 'by' => 'lawal', 'at' => $this->daysAgo(13)],
                ['from' => 15, 'to' => 30, 'by' => 'lawal', 'at' => $this->daysAgo(4)],
            ],
        ]);

        $this->tasks['t4'] = $this->createTask([
            'code' => 'TASK-0004',
            'order_id' => $agbada->id,
            'garment_piece' => 'Agbada Gown',
            'instructions' => 'Embroidered neckline as per chosen pattern A2.',
            'deadline' => $this->daysFromNow(24),
            'status' => 'UNASSIGNED',
            'progress' => 0,
            'created_at' => $this->daysAgo(5),
            'stage_states' => [],
        ]);

        $this->tasks['t5'] = $this->createTask([
            'code' => 'TASK-0005',
            'order_id' => $agbada->id,
            'garment_piece' => 'Matching Trousers',
            'deadline' => $this->daysFromNow(24),
            'status' => 'UNASSIGNED',
            'progress' => 0,
            'created_at' => $this->daysAgo(5),
            'stage_states' => [],
        ]);

        // ------------------------------------------------------------------
        // 6. Historical completed work (performance & incentive data).
        // ------------------------------------------------------------------

        // OSSZ-2025-014 — Senegal two-piece (delivered).
        $t6 = ProductionTask::create([
            'code' => 'TASK-0006',
            'order_id' => $senegal->id,
            'garment_piece' => 'Senegal Top',
            'category' => 'FULL',
            'deadline' => $this->daysAgo(13),
            'priority' => 'NORMAL',
            'status' => 'DELIVERED',
            'progress' => 100,
            'assigned_staff_id' => $hamidou->id,
            'created_by_id' => $aminata->id,
            'completed_at' => $this->daysAgo(14),
            'delivered_at' => $this->daysAgo(12),
            'created_at' => $this->daysAgo(30),
            'updated_at' => $this->daysAgo(12),
        ]);
        $this->tasks['t6'] = $t6;

        TaskAssignment::create([
            'task_id' => $t6->id,
            'staff_id' => $hamidou->id,
            'staff_name' => $hamidou->name,
            'assigned_by_id' => $aminata->id,
            'assigned_by_name' => $aminata->name,
            'type' => 'ASSIGN',
            'created_at' => $this->daysAgo(30),
        ]);

        foreach ($this->doneStates('FULL') as $code => $state) {
            TaskStageState::create([
                'task_id' => $t6->id,
                'stage_id' => $this->stages[$code],
                'state' => $state,
                'updated_by_id' => $hamidou->id,
                'updated_by_name' => $hamidou->name,
                'updated_at' => $this->daysAgo(14),
            ]);
        }

        foreach ([
            ['UNASSIGNED', 'ASSIGNED', $this->daysAgo(30)],
            ['ASSIGNED', 'ONGOING', $this->daysAgo(29)],
            ['ONGOING', 'BLOCKED', $this->daysAgo(20)],
            ['BLOCKED', 'ONGOING', $this->daysAgo(18)],
            ['ONGOING', 'AWAITING_INSPECTION', $this->daysAgo(15)],
            ['AWAITING_INSPECTION', 'APPROVED', $this->daysAgo(14)],
            ['APPROVED', 'READY_FOR_PICKUP', $this->daysAgo(13)],
            ['READY_FOR_PICKUP', 'DELIVERED', $this->daysAgo(12)],
        ] as [$from, $to, $at]) {
            TaskStatusChange::create([
                'task_id' => $t6->id,
                'user_id' => $aminata->id,
                'user_name' => $aminata->name,
                'from_status' => $from,
                'to_status' => $to,
                'reason' => $to === 'BLOCKED' ? 'Fabric shipment delayed at the market.' : null,
                'created_at' => $at,
            ]);
        }

        TaskProgressHistory::create([
            'task_id' => $t6->id,
            'user_id' => $hamidou->id,
            'user_name' => $hamidou->name,
            'previous_progress' => 0,
            'new_progress' => 40,
            'source' => 'MANUAL',
            'created_at' => $this->daysAgo(25),
        ]);

        TaskProgressHistory::create([
            'task_id' => $t6->id,
            'user_id' => $hamidou->id,
            'user_name' => $hamidou->name,
            'previous_progress' => 40,
            'new_progress' => 100,
            'source' => 'MANUAL',
            'note' => 'Top fully assembled.',
            'created_at' => $this->daysAgo(15),
        ]);

        InspectionRecord::create([
            'task_id' => $t6->id,
            'inspector_id' => $aminata->id,
            'inspector_name' => $aminata->name,
            'decision' => 'APPROVED',
            'comments' => 'Clean seams, excellent finish.',
            'created_at' => $this->daysAgo(14),
        ]);

        $t7 = ProductionTask::create([
            'code' => 'TASK-0007',
            'order_id' => $senegal->id,
            'garment_piece' => 'Wrap Skirt',
            'category' => 'FULL',
            'deadline' => $this->daysAgo(6),
            'priority' => 'NORMAL',
            'status' => 'DELIVERED',
            'progress' => 100,
            'assigned_staff_id' => $hamidou->id,
            'created_by_id' => $david->id,
            'completed_at' => $this->daysAgo(8),
            'delivered_at' => $this->daysAgo(7),
            'created_at' => $this->daysAgo(20),
            'updated_at' => $this->daysAgo(7),
        ]);
        $this->tasks['t7'] = $t7;

        TaskAssignment::create([
            'task_id' => $t7->id,
            'staff_id' => $hamidou->id,
            'staff_name' => $hamidou->name,
            'assigned_by_id' => $david->id,
            'assigned_by_name' => $david->name,
            'type' => 'ASSIGN',
            'created_at' => $this->daysAgo(20),
        ]);

        foreach ($this->doneStates('FULL') as $code => $state) {
            TaskStageState::create([
                'task_id' => $t7->id,
                'stage_id' => $this->stages[$code],
                'state' => $state,
                'updated_by_id' => $hamidou->id,
                'updated_by_name' => $hamidou->name,
                'updated_at' => $this->daysAgo(8),
            ]);
        }

        foreach ([
            ['UNASSIGNED', 'ASSIGNED', $this->daysAgo(20)],
            ['ASSIGNED', 'ONGOING', $this->daysAgo(19)],
            ['ONGOING', 'AWAITING_INSPECTION', $this->daysAgo(9)],
            ['AWAITING_INSPECTION', 'APPROVED', $this->daysAgo(8)],
            ['APPROVED', 'READY_FOR_PICKUP', $this->daysAgo(8, 14)],
            ['READY_FOR_PICKUP', 'DELIVERED', $this->daysAgo(7)],
        ] as [$from, $to, $at]) {
            TaskStatusChange::create([
                'task_id' => $t7->id,
                'user_id' => $david->id,
                'user_name' => $david->name,
                'from_status' => $from,
                'to_status' => $to,
                'reason' => null,
                'created_at' => $at,
            ]);
        }

        InspectionRecord::create([
            'task_id' => $t7->id,
            'inspector_id' => $david->id,
            'inspector_name' => $david->name,
            'decision' => 'APPROVED',
            'comments' => 'Good pleat alignment.',
            'created_at' => $this->daysAgo(8),
        ]);

        $t8 = ProductionTask::create([
            'code' => 'TASK-0008',
            'order_id' => $senegal->id,
            'garment_piece' => 'Headwrap',
            'category' => 'IRONING',
            'deadline' => $this->daysAgo(10),
            'priority' => 'NORMAL',
            'status' => 'DELIVERED',
            'progress' => 100,
            'assigned_staff_id' => $lawal->id,
            'created_by_id' => $aminata->id,
            'completed_at' => $this->daysAgo(9),
            'delivered_at' => $this->daysAgo(7),
            'created_at' => $this->daysAgo(20),
            'updated_at' => $this->daysAgo(7),
        ]);
        $this->tasks['t8'] = $t8;

        TaskAssignment::create([
            'task_id' => $t8->id,
            'staff_id' => $lawal->id,
            'staff_name' => $lawal->name,
            'assigned_by_id' => $aminata->id,
            'assigned_by_name' => $aminata->name,
            'type' => 'ASSIGN',
            'created_at' => $this->daysAgo(20),
        ]);

        foreach ($this->doneStates('IRONING') as $code => $state) {
            TaskStageState::create([
                'task_id' => $t8->id,
                'stage_id' => $this->stages[$code],
                'state' => $state,
                'updated_by_id' => $lawal->id,
                'updated_by_name' => $lawal->name,
                'updated_at' => $this->daysAgo(9),
            ]);
        }

        foreach ([
            ['UNASSIGNED', 'ASSIGNED', $this->daysAgo(20)],
            ['ASSIGNED', 'ONGOING', $this->daysAgo(19)],
            ['ONGOING', 'AWAITING_INSPECTION', $this->daysAgo(16)],
            ['AWAITING_INSPECTION', 'CHANGES_REQUESTED', $this->daysAgo(16, 15)],
            ['CHANGES_REQUESTED', 'ONGOING', $this->daysAgo(15)],
            ['ONGOING', 'AWAITING_INSPECTION', $this->daysAgo(9, 9)],
            ['AWAITING_INSPECTION', 'APPROVED', $this->daysAgo(9)],
            ['APPROVED', 'READY_FOR_PICKUP', $this->daysAgo(8)],
            ['READY_FOR_PICKUP', 'DELIVERED', $this->daysAgo(7)],
        ] as [$from, $to, $at]) {
            TaskStatusChange::create([
                'task_id' => $t8->id,
                'user_id' => $aminata->id,
                'user_name' => $aminata->name,
                'from_status' => $from,
                'to_status' => $to,
                'reason' => null,
                'created_at' => $at,
            ]);
        }

        InspectionRecord::create([
            'task_id' => $t8->id,
            'inspector_id' => $aminata->id,
            'inspector_name' => $aminata->name,
            'decision' => 'CHANGES_REQUESTED',
            'comments' => 'Pressing marks still visible on the edges. Redo the ironing.',
            'created_at' => $this->daysAgo(16, 15),
        ]);

        InspectionRecord::create([
            'task_id' => $t8->id,
            'inspector_id' => $aminata->id,
            'inspector_name' => $aminata->name,
            'decision' => 'APPROVED',
            'comments' => 'Corrected. Crisp finish.',
            'created_at' => $this->daysAgo(9),
        ]);

        // OSSZ-2025-009 — Kaftan (delivered).
        $t9 = ProductionTask::create([
            'code' => 'TASK-0009',
            'order_id' => $kaftan->id,
            'garment_piece' => 'Kaftan Gown',
            'category' => 'FULL',
            'deadline' => $this->daysAgo(56),
            'priority' => 'NORMAL',
            'status' => 'DELIVERED',
            'progress' => 100,
            'assigned_staff_id' => $hamidou->id,
            'created_by_id' => $aminata->id,
            'completed_at' => $this->daysAgo(58),
            'delivered_at' => $this->daysAgo(55),
            'created_at' => $this->daysAgo(70),
            'updated_at' => $this->daysAgo(55),
        ]);
        $this->tasks['t9'] = $t9;

        TaskAssignment::create([
            'task_id' => $t9->id,
            'staff_id' => $hamidou->id,
            'staff_name' => $hamidou->name,
            'assigned_by_id' => $aminata->id,
            'assigned_by_name' => $aminata->name,
            'type' => 'ASSIGN',
            'created_at' => $this->daysAgo(70),
        ]);

        foreach ($this->doneStates('FULL') as $code => $state) {
            TaskStageState::create([
                'task_id' => $t9->id,
                'stage_id' => $this->stages[$code],
                'state' => $state,
                'updated_by_id' => $hamidou->id,
                'updated_by_name' => $hamidou->name,
                'updated_at' => $this->daysAgo(58),
            ]);
        }

        InspectionRecord::create([
            'task_id' => $t9->id,
            'inspector_id' => $aminata->id,
            'inspector_name' => $aminata->name,
            'decision' => 'APPROVED',
            'comments' => 'Beautiful mandarin collar work.',
            'created_at' => $this->daysAgo(58),
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | §7–§10 History & extras
    |--------------------------------------------------------------------------
    */

    /**
     * Create the demo comments & reported issues (§7), internal
     * notifications (§8), Management assessments and incentive history
     * (§9) and the audit trail rows (§10).
     */
    private function seedHistoryAndExtras(): void
    {
        $aminata = $this->users['aminata'];
        $david = $this->users['david'];
        $hamidou = $this->users['hamidou'];
        $lawal = $this->users['lawal'];
        $john = $this->customers['john'];
        $suit = $this->orders['suit'];
        $t1 = $this->tasks['t1'];
        $t2 = $this->tasks['t2'];
        $t3 = $this->tasks['t3'];

        // ------------------------------------------------------------------
        // 7. Comments & reported issues.
        // ------------------------------------------------------------------

        TaskComment::create([
            'task_id' => $t1->id,
            'user_id' => $hamidou->id,
            'user_name' => $hamidou->name,
            'message' => 'Jacket body assembled. Will set the sleeves tomorrow.',
            'created_at' => $this->daysAgo(5),
        ]);

        TaskComment::create([
            'task_id' => $t3->id,
            'user_id' => $lawal->id,
            'user_name' => $lawal->name,
            'message' => 'Waiting for the vest lining fabric promised by the customer. Production is stalled.',
            'is_issue' => true,
            'created_at' => $this->daysAgo(3, 11),
        ]);

        // ------------------------------------------------------------------
        // 8. Notifications (internal).
        // ------------------------------------------------------------------

        Notification::create([
            'user_id' => $hamidou->id,
            'type' => 'TASK_ASSIGNED',
            'title' => 'New task assigned',
            'message' => "You were assigned {$t1->code} — Jacket (OSSZ-2026-001).",
            'task_id' => $t1->id,
            'created_at' => $this->daysAgo(15),
        ]);

        Notification::create([
            'user_id' => $hamidou->id,
            'type' => 'TASK_ASSIGNED',
            'title' => 'New task assigned',
            'message' => "You were assigned {$t2->code} — Trousers (OSSZ-2026-001).",
            'task_id' => $t2->id,
            'created_at' => $this->daysAgo(15),
        ]);

        Notification::create([
            'user_id' => $lawal->id,
            'type' => 'TASK_ASSIGNED',
            'title' => 'New task assigned',
            'message' => "You were assigned {$t3->code} — Vest (OSSZ-2026-001).",
            'task_id' => $t3->id,
            'created_at' => $this->daysAgo(14),
        ]);

        Notification::create([
            'user_id' => $aminata->id,
            'type' => 'ISSUE_REPORTED',
            'title' => 'Production issue reported',
            'message' => "{$lawal->name} reported an issue on {$t3->code} — Vest: missing lining fabric.",
            'task_id' => $t3->id,
            'created_at' => $this->daysAgo(3, 11),
        ]);

        Notification::create([
            'user_id' => $david->id,
            'type' => 'ISSUE_REPORTED',
            'title' => 'Production issue reported',
            'message' => "{$lawal->name} reported an issue on {$t3->code} — Vest: missing lining fabric.",
            'task_id' => $t3->id,
            'created_at' => $this->daysAgo(3, 11),
        ]);

        Notification::create([
            'user_id' => $aminata->id,
            'type' => 'TASK_OVERDUE',
            'title' => 'Task overdue',
            'message' => "{$t3->code} — Vest (OSSZ-2026-001) has passed its deadline.",
            'task_id' => $t3->id,
            'created_at' => $this->daysAgo(1),
        ]);

        Notification::create([
            'user_id' => $david->id,
            'type' => 'TASK_OVERDUE',
            'title' => 'Task overdue',
            'message' => "{$t3->code} — Vest (OSSZ-2026-001) has passed its deadline.",
            'task_id' => $t3->id,
            'created_at' => $this->daysAgo(1),
        ]);

        // ------------------------------------------------------------------
        // 9. Management assessments & incentive history.
        // ------------------------------------------------------------------

        // First day of the previous month => period label "YYYY-MM"
        // (prisma/seed.ts: new Date(year, month - 1, 1)).
        $lastMonthPeriod = Carbon::now()
            ->subMonthsNoOverflow(1)
            ->startOfMonth()
            ->format('Y-m');

        StaffAssessment::create([
            'staff_id' => $hamidou->id,
            'staff_name' => $hamidou->name,
            'period' => $lastMonthPeriod,
            'score' => 88,
            'comments' => 'Excellent craftsmanship and autonomy.',
            'updated_at' => $this->daysAgo(5),
        ]);

        StaffAssessment::create([
            'staff_id' => $lawal->id,
            'staff_name' => $lawal->name,
            'period' => $lastMonthPeriod,
            'score' => 65,
            'comments' => 'Good effort; watch deadlines and pressing quality.',
            'updated_at' => $this->daysAgo(5),
        ]);

        IncentiveRecord::create([
            'scheme_id' => $this->monthlyScheme->id,
            'scheme_name' => $this->monthlyScheme->name,
            'staff_id' => $hamidou->id,
            'staff_name' => $hamidou->name,
            'period' => $lastMonthPeriod,
            'performance_score' => 82.4,
            'eligible' => true,
            'proposed_amount' => 25000,
            'status' => 'APPROVED',
            'approved_by_id' => $aminata->id,
            'approved_by_name' => $aminata->name,
            'approval_comments' => 'Consistent excellence all month.',
            'payment_status' => 'PAID',
            'payment_date' => $this->daysAgo(4),
            'created_at' => $this->daysAgo(5),
            'updated_at' => $this->daysAgo(4),
        ]);

        IncentiveRecord::create([
            'scheme_id' => $this->monthlyScheme->id,
            'scheme_name' => $this->monthlyScheme->name,
            'staff_id' => $lawal->id,
            'staff_name' => $lawal->name,
            'period' => $lastMonthPeriod,
            'performance_score' => 48.6,
            'eligible' => false,
            'proposed_amount' => 0,
            'status' => 'REJECTED',
            'approved_by_id' => $aminata->id,
            'approved_by_name' => $aminata->name,
            'approval_comments' => 'Below the 75/100 threshold for this month.',
            'payment_status' => 'UNPAID',
            'created_at' => $this->daysAgo(5),
            'updated_at' => $this->daysAgo(5),
        ]);

        // ------------------------------------------------------------------
        // 10. Audit trail.
        // ------------------------------------------------------------------

        AuditLog::create([
            'user_id' => $aminata->id,
            'user_name' => $aminata->name,
            'action' => 'USER_CREATED',
            'entity_type' => 'USER',
            'entity_id' => $david->id,
            'details' => $this->encodeDetails(['role' => 'ADMIN']),
            'created_at' => $this->daysAgo(60),
        ]);

        AuditLog::create([
            'user_id' => $aminata->id,
            'user_name' => $aminata->name,
            'action' => 'CUSTOMER_CREATED',
            'entity_type' => 'CUSTOMER',
            'entity_id' => $john->id,
            'details' => $this->encodeDetails(['code' => 'CUST-0001']),
            'created_at' => $this->daysAgo(21),
        ]);

        AuditLog::create([
            'user_id' => $aminata->id,
            'user_name' => $aminata->name,
            'action' => 'ORDER_CREATED',
            'entity_type' => 'ORDER',
            'entity_id' => $suit->id,
            'details' => $this->encodeDetails(['orderNumber' => 'OSSZ-2026-001']),
            'created_at' => $this->daysAgo(20),
        ]);

        AuditLog::create([
            'user_id' => $aminata->id,
            'user_name' => $aminata->name,
            'action' => 'TASKS_CREATED',
            'entity_type' => 'ORDER',
            'entity_id' => $suit->id,
            'details' => $this->encodeDetails(['tasks' => [$t1->code, $t2->code, $t3->code]]),
            'created_at' => $this->daysAgo(15),
        ]);

        AuditLog::create([
            'user_id' => $aminata->id,
            'user_name' => $aminata->name,
            'action' => 'TASK_ASSIGNED',
            'entity_type' => 'TASK',
            'entity_id' => $t1->id,
            'details' => $this->encodeDetails(['staff' => $hamidou->name]),
            'created_at' => $this->daysAgo(15),
        ]);

        AuditLog::create([
            'user_id' => $aminata->id,
            'user_name' => $aminata->name,
            'action' => 'TASK_ASSIGNED',
            'entity_type' => 'TASK',
            'entity_id' => $t2->id,
            'details' => $this->encodeDetails(['staff' => $hamidou->name]),
            'created_at' => $this->daysAgo(15),
        ]);

        AuditLog::create([
            'user_id' => $aminata->id,
            'user_name' => $aminata->name,
            'action' => 'TASK_ASSIGNED',
            'entity_type' => 'TASK',
            'entity_id' => $t3->id,
            'details' => $this->encodeDetails(['staff' => $lawal->name]),
            'created_at' => $this->daysAgo(14),
        ]);

        AuditLog::create([
            'user_id' => $hamidou->id,
            'user_name' => $hamidou->name,
            'action' => 'PROGRESS_UPDATED',
            'entity_type' => 'TASK',
            'entity_id' => $t1->id,
            'details' => $this->encodeDetails(['from' => 45, 'to' => 60]),
            'created_at' => $this->daysAgo(2),
        ]);

        AuditLog::create([
            'user_id' => $lawal->id,
            'user_name' => $lawal->name,
            'action' => 'ISSUE_REPORTED',
            'entity_type' => 'TASK',
            'entity_id' => $t3->id,
            'details' => $this->encodeDetails(['issue' => 'Missing lining fabric']),
            'created_at' => $this->daysAgo(3, 11),
        ]);

        AuditLog::create([
            'user_id' => $aminata->id,
            'user_name' => $aminata->name,
            'action' => 'INCENTIVE_APPROVED',
            'entity_type' => 'INCENTIVE',
            'entity_id' => $hamidou->id,
            'details' => $this->encodeDetails(['period' => $lastMonthPeriod, 'amount' => 25000]),
            'created_at' => $this->daysAgo(4),
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | Task factory helper (createTask from prisma/seed.ts)
    |--------------------------------------------------------------------------
    */

    /**
     * Create one production task plus its checklist stage states, stage
     * update log, status workflow chain, manual progress history and
     * creation-time assignment row — 1:1 port of the `createTask()`
     * helper in prisma/seed.ts.
     *
     * Option keys:
     *  - code, order_id, garment_piece, status, progress, created_at  (required)
     *  - category (default FULL), instructions, priority (default NORMAL),
     *    assigned_staff_id, deadline                                  (optional)
     *  - stage_states:      list of [stageCode, state] pairs (appliesTo filtered)
     *  - stage_updates:     list of ['stage_code', 'from', 'to', 'by', 'at']
     *  - status_chain:      list of ['to', 'by', 'at', 'reason'?] (from = previous)
     *  - progress_history:  list of ['from', 'to', 'by', 'at', 'note'?]
     *
     * @param array<string, mixed> $opts
     */
    private function createTask(array $opts): ProductionTask
    {
        $aminata = $this->users['aminata'];
        $hamidou = $this->users['hamidou'];
        $lawal = $this->users['lawal'];

        $task = ProductionTask::create([
            'code' => $opts['code'],
            'order_id' => $opts['order_id'],
            'garment_piece' => $opts['garment_piece'],
            'category' => $opts['category'] ?? 'FULL',
            'instructions' => $opts['instructions'] ?? null,
            'deadline' => $opts['deadline'],
            'priority' => $opts['priority'] ?? 'NORMAL',
            'status' => $opts['status'],
            'progress' => $opts['progress'],
            'assigned_staff_id' => $opts['assigned_staff_id'] ?? null,
            'created_by_id' => $aminata->id,
            'created_at' => $opts['created_at'],
            'updated_at' => Carbon::now(),
        ]);

        $category = $opts['category'] ?? 'FULL';

        // Current checklist stage states (filtered by appliesTo vs category).
        foreach ($opts['stage_states'] as [$stageCode, $state]) {
            if (! $this->stageAppliesTo($stageCode, $category)) {
                continue;
            }

            TaskStageState::create([
                'task_id' => $task->id,
                'stage_id' => $this->stages[$stageCode],
                'state' => $state,
                'updated_by_id' => $opts['assigned_staff_id'] ?? null,
                'updated_by_name' => ($opts['assigned_staff_id'] ?? null) === $hamidou->id
                    ? $hamidou->name
                    : $lawal->name,
                'updated_at' => $opts['created_at'],
            ]);
        }

        // Immutable stage state change log.
        foreach ($opts['stage_updates'] ?? [] as $update) {
            if (! $this->stageAppliesTo($update['stage_code'], $category)) {
                continue;
            }

            TaskStageUpdate::create([
                'task_id' => $task->id,
                'stage_id' => $this->stages[$update['stage_code']],
                'stage_name' => $this->stageName($update['stage_code']),
                'user_id' => $update['by'] === 'hamidou'
                    ? $hamidou->id
                    : ($update['by'] === 'lawal' ? $lawal->id : $aminata->id),
                'user_name' => $this->users[$update['by']]->name,
                'previous_state' => $update['from'],
                'new_state' => $update['to'],
                'created_at' => $update['at'],
            ]);
        }

        // Status workflow history (each entry chains from the previous status).
        $previous = 'UNASSIGNED';

        foreach ($opts['status_chain'] ?? [] as $change) {
            TaskStatusChange::create([
                'task_id' => $task->id,
                'user_id' => $this->statusActorId($change['by']),
                'user_name' => ($this->users[$change['by']] ?? $aminata)->name,
                'from_status' => $previous,
                'to_status' => $change['to'],
                'reason' => $change['reason'] ?? null,
                'created_at' => $change['at'],
            ]);

            $previous = $change['to'];
        }

        // Manual progress history.
        foreach ($opts['progress_history'] ?? [] as $entry) {
            TaskProgressHistory::create([
                'task_id' => $task->id,
                'user_id' => $entry['by'] === 'hamidou' ? $hamidou->id : $lawal->id,
                'user_name' => $this->users[$entry['by']]->name,
                'previous_progress' => $entry['from'],
                'new_progress' => $entry['to'],
                'source' => 'MANUAL',
                'note' => $entry['note'] ?? null,
                'created_at' => $entry['at'],
            ]);
        }

        // Creation-time assignment row (assigned by Management).
        if (! empty($opts['assigned_staff_id'])) {
            TaskAssignment::create([
                'task_id' => $task->id,
                'staff_id' => $opts['assigned_staff_id'],
                'staff_name' => $opts['assigned_staff_id'] === $hamidou->id
                    ? $hamidou->name
                    : ($opts['assigned_staff_id'] === $lawal->id ? $lawal->name : ''),
                'assigned_by_id' => $aminata->id,
                'assigned_by_name' => $aminata->name,
                'type' => 'ASSIGN',
                'created_at' => $opts['created_at'],
            ]);
        }

        return $task;
    }

    /*
    |--------------------------------------------------------------------------
    | Utilities
    |--------------------------------------------------------------------------
    */

    /**
     * Stage states for a fully completed & delivered task of the given
     * category (doneStates from prisma/seed.ts): every applicable stage
     * COMPLETED except "blocked" / "other" which are NOT_APPLICABLE.
     *
     * @return array<string, string> stage code => state
     */
    private function doneStates(string $category): array
    {
        $states = [];

        foreach (self::STAGE_DEFS as [$code, , , $appliesTo]) {
            if (! ($appliesTo === 'ALL' || in_array($category, explode(',', $appliesTo), true))) {
                continue;
            }

            $states[$code] = ($code === 'blocked' || $code === 'other') ? 'NOT_APPLICABLE' : 'COMPLETED';
        }

        return $states;
    }

    /** True when the stage (by code) applies to the given task category. */
    private function stageAppliesTo(string $stageCode, string $category): bool
    {
        foreach (self::STAGE_DEFS as [$code, , , $appliesTo]) {
            if ($code === $stageCode) {
                return $appliesTo === 'ALL'
                    || in_array($category, explode(',', $appliesTo), true);
            }
        }

        return false;
    }

    /** Stage display name for a stage code (snapshot for history rows). */
    private function stageName(string $stageCode): string
    {
        foreach (self::STAGE_DEFS as [$code, $name]) {
            if ($code === $stageCode) {
                return $name;
            }
        }

        return $stageCode;
    }

    /**
     * User id for a status-chain actor key ('aminata', 'david',
     * 'hamidou'; anything else resolves to Lawal — faithful port of
     * prisma/seed.ts).
     */
    private function statusActorId(string $by): string
    {
        return match ($by) {
            'aminata' => $this->users['aminata']->id,
            'david' => $this->users['david']->id,
            'hamidou' => $this->users['hamidou']->id,
            default => $this->users['lawal']->id,
        };
    }

    /**
     * daysAgo from prisma/seed.ts — now minus N days with the clock
     * set to hour:00:00 (defaults to 10:00 like the reference).
     */
    private function daysAgo(int $days, int $hour = 10): Carbon
    {
        return Carbon::now()->subDays($days)->setTime($hour, 0, 0);
    }

    /**
     * daysFromNow from prisma/seed.ts — now plus N days with the clock
     * set to hour:00:00 (defaults to 17:00 like the reference).
     */
    private function daysFromNow(int $days, int $hour = 17): Carbon
    {
        return Carbon::now()->addDays($days)->setTime($hour, 0, 0);
    }

    /**
     * JSON-encode an audit `details` payload byte-identically to
     * JSON.stringify() in prisma/seed.ts (no slash/unicode escaping).
     */
    private function encodeDetails(array $details): string
    {
        return (string) json_encode($details, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    }
}
