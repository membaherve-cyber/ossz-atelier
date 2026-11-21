<?php

use App\Http\Controllers\Accounting\AdvanceController;
use App\Http\Controllers\Accounting\BudgetController;
use App\Http\Controllers\Accounting\DashboardController as AccountingDashboardController;
use App\Http\Controllers\Accounting\ExpenseController;
use App\Http\Controllers\Accounting\FinancialReportController;
use App\Http\Controllers\Accounting\GeneralAccountingController;
use App\Http\Controllers\Accounting\PaymentController;
use App\Http\Controllers\Accounting\PayrollController;
use App\Http\Controllers\Accounting\SupplierController;
use App\Http\Controllers\Accounting\TreasuryController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\AuditController;
use App\Http\Controllers\DailyReportController;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\IncentiveController;
use App\Http\Controllers\LocaleController;
use App\Http\Controllers\MaterialController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\PerformanceController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\StageController;
use App\Http\Controllers\TaskActionController;
use App\Http\Controllers\TaskController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes — OSSZ ATELIER
|--------------------------------------------------------------------------
|
| EXACT route table from the architecture spec §3. All routes run in the
| `web` middleware group (session, CSRF on POST) with SetLocale appended.
|
| Access levels:
|   - "public"   : no middleware
|   - "guest"    : RedirectIfAuthenticated (alias `guest`) — sends already
|                  authenticated users to "/" instead of /login
|   - "auth"     : authenticated users (STAFF scoping happens in the
|                  controller where the table notes "own tasks only")
|   - "perm:x"   : EnsurePermission — the role matrix in
|                  App\Services\Permissions decides (guests get 403)
|   - "mixed"    : authenticated at route level; the fine-grained rules
|                  (assignee vs supervisor, §5 workflow gates) are enforced
|                  inside the controller
|
| The health endpoint `/up` is registered in bootstrap/app.php.
|
*/

// --------------------------------------------------------------------------
// Language switch — public
// --------------------------------------------------------------------------
Route::get('/locale/{lang}', [LocaleController::class, 'switch'])
    ->name('locale.switch');

// --------------------------------------------------------------------------
// Authentication — guest only (POST throttled 5/min per email+IP, see
// AppServiceProvider::configureRateLimiting)
// --------------------------------------------------------------------------
Route::get('/login', [LoginController::class, 'show'])
    ->name('login')
    ->middleware('guest');

Route::post('/login', [LoginController::class, 'attempt'])
    ->name('login.attempt')
    ->middleware('guest', 'throttle:login');

Route::post('/logout', [LoginController::class, 'logout'])
    ->name('logout')
    ->middleware('auth');

// --------------------------------------------------------------------------
// Dashboard — auth (manager + staff variants chosen by the controller)
// --------------------------------------------------------------------------
Route::get('/', [DashboardController::class, 'index'])
    ->name('dashboard')
    ->middleware('auth');

// --------------------------------------------------------------------------
// Production tasks
// --------------------------------------------------------------------------
Route::get('/tasks', [TaskController::class, 'index'])
    ->name('tasks.index')
    ->middleware('auth'); // STAFF: own tasks only (scoped in controller)

Route::get('/tasks/create', [TaskController::class, 'create'])
    ->name('tasks.create')
    ->middleware('perm:production.create.tasks');

Route::post('/tasks', [TaskController::class, 'store'])
    ->name('tasks.store')
    ->middleware('perm:production.create.tasks');

Route::get('/tasks/{id}', [TaskController::class, 'show'])
    ->name('tasks.show')
    ->middleware('auth'); // STAFF: own only (enforced in controller)

// Workflow actions ("mixed" = auth here, role rules in the controller)
Route::post('/tasks/{id}/assign', [TaskActionController::class, 'assign'])
    ->name('tasks.assign')
    ->middleware('perm:production.assign');

Route::post('/tasks/{id}/status', [TaskActionController::class, 'status'])
    ->name('tasks.status')
    ->middleware('auth');

Route::post('/tasks/{id}/progress', [TaskActionController::class, 'progress'])
    ->name('tasks.progress')
    ->middleware('auth');

Route::post('/tasks/{id}/stages', [TaskActionController::class, 'stages'])
    ->name('tasks.stages')
    ->middleware('auth');

Route::post('/tasks/{id}/inspect', [TaskActionController::class, 'inspect'])
    ->name('tasks.inspect')
    ->middleware('perm:production.inspect');

Route::post('/tasks/{id}/comment', [TaskActionController::class, 'comment'])
    ->name('tasks.comment')
    ->middleware('auth'); // STAFF may comment on own task only

Route::post('/tasks/{id}/block', [TaskActionController::class, 'block'])
    ->name('tasks.block')
    ->middleware('auth');

Route::post('/tasks/{id}/unblock', [TaskActionController::class, 'unblock'])
    ->name('tasks.unblock')
    ->middleware('auth');

Route::post('/tasks/{id}/update', [TaskActionController::class, 'update'])
    ->name('tasks.update')
    ->middleware('perm:production.manage.details');

// Work-time log (Finance spec v1.3 §5.13) — the employee (or a
// supervisor) records minutes spent on the task.
Route::post('/tasks/{id}/worklog', [TaskActionController::class, 'workLog'])
    ->name('tasks.worklog')
    ->middleware('auth');

// Reprise / rework marker (§5.15) — retouche after essayage; time and
// materials stay imputed to the original order and show separately.
Route::post('/tasks/{id}/rework', [TaskActionController::class, 'toggleRework'])
    ->name('tasks.rework')
    ->middleware('perm:production.manage.details');

// --------------------------------------------------------------------------
// Orders — the unified order registration + garment-piece task assignment
// workflow (enhancement spec §3) plus the dedicated order details page.
// --------------------------------------------------------------------------
Route::get('/orders', [OrderController::class, 'index'])
    ->name('orders.index')
    ->middleware('perm:orders.view');

Route::get('/orders/create', [OrderController::class, 'create'])
    ->name('orders.create')
    ->middleware('perm:orders.manage');

Route::post('/orders', [OrderController::class, 'store'])
    ->name('orders.store')
    ->middleware('perm:orders.manage');

Route::get('/orders/{id}', [OrderController::class, 'show'])
    ->name('orders.show')
    ->middleware('perm:orders.view');

Route::post('/orders/{id}/update', [OrderController::class, 'update'])
    ->name('orders.update')
    ->middleware('perm:orders.manage');

Route::post('/orders/{id}/finalize', [OrderController::class, 'finalize'])
    ->name('orders.finalize') // DRAFT → REGISTERED + staff notifications
    ->middleware('perm:orders.manage');

Route::post('/orders/{id}/labour', [OrderController::class, 'allocateLabour'])
    ->name('orders.labour') // explicit labour cost allocation (spec §11)
    ->middleware('perm:accounting.payments');

// Garments / pieces of an order (Finance spec v1.3 §5.2) — readable
// order-derived codes, explicit piece → order relation.
Route::post('/orders/{id}/garments', [OrderController::class, 'storeGarment'])
    ->name('orders.garments.store')
    ->middleware('perm:orders.manage');

Route::post('/garments/{id}/update', [OrderController::class, 'updateGarment'])
    ->name('garments.update')
    ->middleware('perm:orders.manage');

Route::post('/garments/{id}/delete', [OrderController::class, 'destroyGarment'])
    ->name('garments.destroy')
    ->middleware('perm:orders.manage');

Route::post('/orders/{id}/delete', [OrderController::class, 'destroy'])
    ->name('orders.destroy') // only when the order has no tasks
    ->middleware('perm:orders.manage');

// --------------------------------------------------------------------------
// Customers
// --------------------------------------------------------------------------
Route::get('/customers', [CustomerController::class, 'index'])
    ->name('customers.index')
    ->middleware('perm:customers.view');

Route::post('/customers', [CustomerController::class, 'store'])
    ->name('customers.store')
    ->middleware('perm:customers.manage');

Route::post('/customers/{id}/update', [CustomerController::class, 'update'])
    ->name('customers.update')
    ->middleware('perm:customers.manage');

Route::post('/customers/{id}/delete', [CustomerController::class, 'destroy'])
    ->name('customers.destroy') // only when the customer has no orders
    ->middleware('perm:customers.manage');

// --------------------------------------------------------------------------
// Users & roles (ADMIN: staff accounts only; last-active-MANAGEMENT
// safeguard — enforced in the controller per spec §5)
// --------------------------------------------------------------------------
Route::get('/users', [UserController::class, 'index'])
    ->name('users.index')
    ->middleware('perm:users.view');

Route::post('/users', [UserController::class, 'store'])
    ->name('users.store')
    ->middleware('perm:users.manage.staff');

Route::post('/users/{id}/update', [UserController::class, 'update'])
    ->name('users.update')
    ->middleware('perm:users.manage.staff');

// --------------------------------------------------------------------------
// Production stages (ADMIN holds production.stages.configure too, so
// Admin can view and configure — spec §3)
// --------------------------------------------------------------------------
Route::get('/stages', [StageController::class, 'index'])
    ->name('stages.index')
    ->middleware('perm:production.stages.configure');

Route::post('/stages', [StageController::class, 'store'])
    ->name('stages.store')
    ->middleware('perm:production.stages.configure');

Route::post('/stages/{id}', [StageController::class, 'update'])
    ->name('stages.update')
    ->middleware('perm:production.stages.configure');

// --------------------------------------------------------------------------
// Performance (weights MANAGEMENT-only via the permission matrix)
// --------------------------------------------------------------------------
Route::get('/performance', [PerformanceController::class, 'index'])
    ->name('performance.index')
    ->middleware('perm:performance.view');

Route::post('/performance/config', [PerformanceController::class, 'updateConfig'])
    ->name('performance.config')
    ->middleware('perm:performance.configure');

Route::post('/performance/assess', [PerformanceController::class, 'assess'])
    ->name('performance.assess')
    ->middleware('perm:performance.assess');

// --------------------------------------------------------------------------
// Incentives (approve/payment are MANAGEMENT-only via the matrix)
// --------------------------------------------------------------------------
Route::get('/incentives', [IncentiveController::class, 'index'])
    ->name('incentives.index')
    ->middleware('perm:incentives.view');

Route::post('/incentives/schemes', [IncentiveController::class, 'storeScheme'])
    ->name('incentives.schemes.store')
    ->middleware('perm:incentives.manage.schemes');

Route::post('/incentives/schemes/{id}', [IncentiveController::class, 'updateScheme'])
    ->name('incentives.schemes.update')
    ->middleware('perm:incentives.manage.schemes');

Route::post('/incentives/generate', [IncentiveController::class, 'generate'])
    ->name('incentives.generate')
    ->middleware('perm:incentives.prepare');

Route::post('/incentives/{id}/review', [IncentiveController::class, 'review'])
    ->name('incentives.review') // approve/reject — MANAGEMENT only
    ->middleware('perm:incentives.approve');

Route::post('/incentives/{id}/payment', [IncentiveController::class, 'payment'])
    ->name('incentives.payment') // mark PAID — MANAGEMENT only
    ->middleware('perm:incentives.payment');

// --------------------------------------------------------------------------
// Reports
// --------------------------------------------------------------------------
Route::get('/reports', [ReportController::class, 'index'])
    ->name('reports.index')
    ->middleware('perm:production.reports');

// --------------------------------------------------------------------------
// Notifications (own account only)
// --------------------------------------------------------------------------
Route::get('/notifications', [NotificationController::class, 'index'])
    ->name('notifications.index')
    ->middleware('auth');

Route::post('/notifications/read', [NotificationController::class, 'read'])
    ->name('notifications.read')
    ->middleware('auth');

// --------------------------------------------------------------------------
// Audit history — Management only (audit.view is not granted to ADMIN)
// --------------------------------------------------------------------------
Route::get('/audit', [AuditController::class, 'index'])
    ->name('audit.index')
    ->middleware('perm:audit.view');

// --------------------------------------------------------------------------
// ACCOUNTING (enhancement spec §4) — confidential financial module.
// Management-only via the permission matrix; enforced SERVER-SIDE here
// (hiding menu entries is never the authorization).
// --------------------------------------------------------------------------
Route::get('/accounting', [AccountingDashboardController::class, 'index'])
    ->name('accounting.dashboard')
    ->middleware('perm:accounting.view');

// Income and customer payments (spec §6)
Route::get('/payments', [PaymentController::class, 'index'])
    ->name('payments.index')
    ->middleware('perm:accounting.payments');

Route::post('/payments', [PaymentController::class, 'store'])
    ->name('payments.store')
    ->middleware('perm:accounting.payments');

Route::post('/payments/{id}/update', [PaymentController::class, 'update'])
    ->name('payments.update')
    ->middleware('perm:accounting.payments');

Route::post('/payments/{id}/delete', [PaymentController::class, 'destroy'])
    ->name('payments.destroy')
    ->middleware('perm:accounting.payments');

// Printable receipt of a customer payment (Finance spec v1.3 §4.2)
Route::get('/payments/{id}/receipt', [PaymentController::class, 'receipt'])
    ->name('payments.receipt')
    ->middleware('perm:accounting.payments');

// Expenses (spec §7)
Route::get('/expenses', [ExpenseController::class, 'index'])
    ->name('expenses.index')
    ->middleware('perm:accounting.expenses');

Route::post('/expenses', [ExpenseController::class, 'store'])
    ->name('expenses.store')
    ->middleware('perm:accounting.expenses');

Route::post('/expenses/{id}/update', [ExpenseController::class, 'update'])
    ->name('expenses.update')
    ->middleware('perm:accounting.expenses');

Route::post('/expenses/{id}/review', [ExpenseController::class, 'review'])
    ->name('expenses.review') // approve / reject / mark paid — accounting.approve
    ->middleware('perm:accounting.approve');

Route::post('/expenses/{id}/delete', [ExpenseController::class, 'destroy'])
    ->name('expenses.destroy')
    ->middleware('perm:accounting.expenses');

Route::get('/expenses/{id}/receipt', [ExpenseController::class, 'receipt'])
    ->name('expenses.receipt')
    ->middleware('perm:accounting.expenses');

Route::post('/expense-categories', [ExpenseController::class, 'storeCategory'])
    ->name('expenses.categories.store')
    ->middleware('perm:accounting.expenses');

Route::post('/expense-categories/{id}', [ExpenseController::class, 'updateCategory'])
    ->name('expenses.categories.update')
    ->middleware('perm:accounting.expenses');

// Salaries and payroll (spec §8)
Route::get('/payroll', [PayrollController::class, 'index'])
    ->name('payroll.index')
    ->middleware('perm:accounting.payroll');

Route::post('/payroll', [PayrollController::class, 'store'])
    ->name('payroll.store') // create period + proposed items from profiles
    ->middleware('perm:accounting.payroll');

Route::post('/payroll/{id}/approve', [PayrollController::class, 'approve'])
    ->name('payroll.approve')
    ->middleware('perm:accounting.approve');

Route::post('/payroll/items/{id}/update', [PayrollController::class, 'updateItem'])
    ->name('payroll.items.update') // amounts + advance recovery application
    ->middleware('perm:accounting.payroll');

Route::post('/payroll/items/{id}/pay', [PayrollController::class, 'payItem'])
    ->name('payroll.items.pay')
    ->middleware('perm:accounting.payroll');

Route::post('/payroll/{id}/delete', [PayrollController::class, 'destroy'])
    ->name('payroll.destroy')
    ->middleware('perm:accounting.payroll');

// Salary profiles (spec §8.1) — managed from the payroll screen's
// profiles tab
Route::post('/salary-profiles', [PayrollController::class, 'storeProfile'])
    ->name('salary.profiles.store')
    ->middleware('perm:accounting.payroll');

Route::post('/salary-profiles/{id}', [PayrollController::class, 'updateProfile'])
    ->name('salary.profiles.update')
    ->middleware('perm:accounting.payroll');

// Staff advances (spec §9)
Route::get('/advances', [AdvanceController::class, 'index'])
    ->name('advances.index')
    ->middleware('perm:accounting.advances');

Route::post('/advances', [AdvanceController::class, 'store'])
    ->name('advances.store')
    ->middleware('perm:accounting.advances');

Route::post('/advances/{id}/review', [AdvanceController::class, 'review'])
    ->name('advances.review') // approve / reject — accounting.approve
    ->middleware('perm:accounting.approve');

Route::post('/advances/{id}/disburse', [AdvanceController::class, 'disburse'])
    ->name('advances.disburse') // mark handed out + notify staff
    ->middleware('perm:accounting.approve');

Route::post('/advances/{id}/cancel', [AdvanceController::class, 'cancel'])
    ->name('advances.cancel')
    ->middleware('perm:accounting.approve');

// Suppliers and purchases (spec §10)
Route::get('/suppliers', [SupplierController::class, 'index'])
    ->name('suppliers.index')
    ->middleware('perm:accounting.suppliers');

Route::post('/suppliers', [SupplierController::class, 'store'])
    ->name('suppliers.store')
    ->middleware('perm:accounting.suppliers');

Route::post('/suppliers/{id}/update', [SupplierController::class, 'update'])
    ->name('suppliers.update')
    ->middleware('perm:accounting.suppliers');

Route::post('/suppliers/{id}/delete', [SupplierController::class, 'destroy'])
    ->name('suppliers.destroy')
    ->middleware('perm:accounting.suppliers');

Route::post('/purchases', [SupplierController::class, 'storePurchase'])
    ->name('purchases.store')
    ->middleware('perm:accounting.suppliers');

Route::post('/purchases/{id}/update', [SupplierController::class, 'updatePurchase'])
    ->name('purchases.update')
    ->middleware('perm:accounting.suppliers');

Route::post('/purchases/{id}/delete', [SupplierController::class, 'destroyPurchase'])
    ->name('purchases.destroy')
    ->middleware('perm:accounting.suppliers');

Route::get('/purchases/{id}/receipt', [SupplierController::class, 'purchaseReceipt'])
    ->name('purchases.receipt')
    ->middleware('perm:accounting.suppliers');

// Purchase payment instalments (Finance spec v1.3 §4.6) — engagement and
// settlement are distinct events; status derived from the payments.
Route::post('/purchases/{id}/payments', [SupplierController::class, 'storePurchasePayment'])
    ->name('purchases.payments.store')
    ->middleware('perm:accounting.suppliers');

// Financial reports (spec §12) — CSV export via ?export=csv
Route::get('/accounting/reports', [FinancialReportController::class, 'index'])
    ->name('accounting.reports')
    ->middleware('perm:accounting.reports');

// --------------------------------------------------------------------------
// TREASURY (Finance spec v1.3 §4.4) — multi-account treasury: caisse,
// banque, MTN MoMo, Orange Money + configurable accounts. Read side
// treasury.view; writes (accounts, transfers, movements, reconcile)
// need treasury.manage (Comptable / Management / Admin).
// --------------------------------------------------------------------------
Route::get('/treasury', [TreasuryController::class, 'index'])
    ->name('treasury.index')
    ->middleware('perm:treasury.view');

Route::post('/treasury/accounts', [TreasuryController::class, 'storeAccount'])
    ->name('treasury.accounts.store')
    ->middleware('perm:treasury.manage');

Route::post('/treasury/accounts/{id}', [TreasuryController::class, 'updateAccount'])
    ->name('treasury.accounts.update')
    ->middleware('perm:treasury.manage');

Route::post('/treasury/transfer', [TreasuryController::class, 'transfer'])
    ->name('treasury.transfer')
    ->middleware('perm:treasury.manage');

Route::post('/treasury/movement', [TreasuryController::class, 'storeMovement'])
    ->name('treasury.movement')
    ->middleware('perm:treasury.manage');

Route::post('/treasury/movements/{id}/reconcile', [TreasuryController::class, 'toggleReconcile'])
    ->name('treasury.reconcile')
    ->middleware('perm:treasury.manage');

// --------------------------------------------------------------------------
// MATERIALS & STOCK (Finance spec v1.3 §5) — the traceability chain.
// Master + ledger need materials.view; issues to employees need
// materials.issue; declarations need materials.declare (STAFF holds it,
// controller-scoped to their own issues); validation/closure needs
// materials.validate (Manager).
// --------------------------------------------------------------------------
Route::get('/materials', [MaterialController::class, 'index'])
    ->name('materials.index')
    ->middleware('perm:materials.view');

Route::post('/materials', [MaterialController::class, 'store'])
    ->name('materials.store')
    ->middleware('perm:materials.manage');

// NOTE: create before {id} — route order matters.
Route::get('/materials/issues/create', [MaterialController::class, 'createIssue'])
    ->name('materials.issues.create')
    ->middleware('perm:materials.issue');

Route::get('/materials/issues', [MaterialController::class, 'issues'])
    ->name('materials.issues.index')
    ->middleware('auth'); // scoped in controller (own issues for STAFF)

Route::post('/materials/issues', [MaterialController::class, 'storeIssue'])
    ->name('materials.issues.store')
    ->middleware('perm:materials.issue');

Route::get('/materials/issues/{id}', [MaterialController::class, 'showIssue'])
    ->name('materials.issues.show')
    ->middleware('auth'); // scoped in controller

Route::post('/materials/issues/{id}/close', [MaterialController::class, 'closeIssue'])
    ->name('materials.issues.close')
    ->middleware('perm:materials.validate');

Route::post('/materials/issues/{id}/cancel', [MaterialController::class, 'cancelIssue'])
    ->name('materials.issues.cancel')
    ->middleware('perm:materials.issue');

Route::post('/materials/lines/{id}/declare', [MaterialController::class, 'declare'])
    ->name('materials.declare')
    ->middleware('perm:materials.declare');

Route::get('/materials/validation', [MaterialController::class, 'validationQueue'])
    ->name('materials.validation')
    ->middleware('perm:materials.validate');

Route::post('/materials/declarations/{id}/review', [MaterialController::class, 'reviewDeclaration'])
    ->name('materials.declarations.review')
    ->middleware('perm:materials.validate');

Route::post('/materials/transfers', [MaterialController::class, 'storeTransfer'])
    ->name('materials.transfers.store')
    ->middleware('perm:materials.issue');

// Material master edit — registered AFTER the literal /materials/* POST
// routes so it can never shadow them ({id} is constrained to a UUID).
Route::post('/materials/{id}', [MaterialController::class, 'update'])
    ->name('materials.update')
    ->middleware('perm:materials.manage')
    ->where('id', '[0-9a-f-]{36}');

// --------------------------------------------------------------------------
// GENERAL ACCOUNTING (Finance spec v1.3 §6, SYSCOHADA) — chart of
// accounts, journal, grand livre, trial balance, manual OD entries and
// retroactive generation. Comptable / Management / Admin (accounting.journal).
// --------------------------------------------------------------------------
Route::get('/accounting/general', [GeneralAccountingController::class, 'index'])
    ->name('accounting.general')
    ->middleware('perm:accounting.journal');

Route::post('/accounting/general/entry', [GeneralAccountingController::class, 'storeEntry'])
    ->name('accounting.general.entry')
    ->middleware('perm:accounting.journal');

Route::post('/accounting/general/generate', [GeneralAccountingController::class, 'generate'])
    ->name('accounting.general.generate')
    ->middleware('perm:accounting.journal');

Route::get('/accounting/ledger/{id}', [GeneralAccountingController::class, 'ledger'])
    ->name('accounting.ledger')
    ->middleware('perm:accounting.journal');

Route::get('/accounting/balance', [GeneralAccountingController::class, 'balance'])
    ->name('accounting.balance')
    ->middleware('perm:accounting.journal');

Route::post('/accounting/accounts', [GeneralAccountingController::class, 'storeAccount'])
    ->name('accounting.accounts.store')
    ->middleware('perm:accounting.journal');

// --------------------------------------------------------------------------
// BUDGETS (Finance spec v1.3 §8) — planned vs real, overrun alerts.
// --------------------------------------------------------------------------
Route::get('/budgets', [BudgetController::class, 'index'])
    ->name('budgets.index')
    ->middleware('perm:budgets.view');

Route::post('/budgets', [BudgetController::class, 'store'])
    ->name('budgets.store')
    ->middleware('perm:budgets.manage');

Route::post('/budgets/{id}/lines', [BudgetController::class, 'storeLine'])
    ->name('budgets.lines.store')
    ->middleware('perm:budgets.manage');

Route::post('/budgets/{id}/close', [BudgetController::class, 'close'])
    ->name('budgets.close')
    ->middleware('perm:budgets.manage');

// --------------------------------------------------------------------------
// DAILY BUSINESS-HEALTH E-MAIL (enhancement) — automated daily report to
// the management distribution list. Management and Admin via accounting.view
// (the report carries confidential financial KPIs — never shown to STAFF).
// Delivery itself runs through the `atelier:daily-report` artisan command
// (scheduler / cron).
// --------------------------------------------------------------------------
Route::get('/daily-report', [DailyReportController::class, 'index'])
    ->name('dailyreport.index')
    ->middleware('perm:accounting.view');

Route::post('/daily-report/settings', [DailyReportController::class, 'updateSettings'])
    ->name('dailyreport.settings')
    ->middleware('perm:accounting.view');

Route::post('/daily-report/recipients', [DailyReportController::class, 'storeRecipient'])
    ->name('dailyreport.recipients.store')
    ->middleware('perm:accounting.view');

Route::post('/daily-report/recipients/{id}', [DailyReportController::class, 'updateRecipient'])
    ->name('dailyreport.recipients.update')
    ->middleware('perm:accounting.view');

Route::post('/daily-report/recipients/{id}/delete', [DailyReportController::class, 'destroyRecipient'])
    ->name('dailyreport.recipients.destroy')
    ->middleware('perm:accounting.view');

Route::post('/daily-report/send', [DailyReportController::class, 'send'])
    ->name('dailyreport.send')
    ->middleware('perm:accounting.view');

Route::post('/daily-report/send-test', [DailyReportController::class, 'sendTest'])
    ->name('dailyreport.sendTest')
    ->middleware('perm:accounting.view');
