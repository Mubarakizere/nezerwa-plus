<?php

use Illuminate\Support\Facades\Route;

use App\Http\Controllers\Auth\RoleRedirectController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DebitCreditController;
use App\Http\Controllers\ExpenseController;
use App\Http\Controllers\LoanController;
use App\Http\Controllers\LoanPaymentController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\PurchaseController;
use App\Http\Controllers\ReportsController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\SaleController;
use App\Http\Controllers\SaleReturnController;
use App\Http\Controllers\StockMovementController;
use App\Http\Controllers\SupplierController;
use App\Http\Controllers\TransactionController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\PurchaseReturnController;
use App\Http\Controllers\StatementController;
use App\Http\Controllers\ItemLoanController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
| Public web routes for the Stock Management System.
*/

/* -------- Global parameter constraints to avoid collisions -------- */
Route::pattern('sale', '[0-9]+');
Route::pattern('purchase', '[0-9]+');
Route::pattern('transaction', '[0-9]+');
Route::pattern('product', '[0-9]+');
Route::pattern('expense', '[0-9]+');
Route::pattern('loan', '[0-9]+');
Route::pattern('payment', '[0-9]+');

/* -------- Root redirect -------- */
Route::get('/', function () {
    return auth()->check()
        ? redirect()->route('dashboard')
        : redirect()->route('login');
});



/* -------- Role-based redirect after login (still fine to keep) -------- */
Route::get('/redirect-by-role', RoleRedirectController::class)
    ->middleware(['auth'])
    ->name('redirect.by.role');

/* =========================================================================
| Authenticated & Verified Routes
|========================================================================= */
Route::middleware(['auth', 'verified'])->group(function () {

    /*
    |--------------------------------------------------------------------------
    | Dashboard
    |--------------------------------------------------------------------------
    | Using reports.view as the "can see dashboard" permission.
    */
    Route::get('/dashboard', [DashboardController::class, 'index'])
        ->name('dashboard')
        ->middleware('permission:reports.view');

    Route::get('/dashboard/sales-chart', [DashboardController::class, 'salesChartData'])
        ->name('dashboard.sales.chart')
        ->middleware('permission:reports.view');

    /*
    |--------------------------------------------------------------------------
    | Users & Roles Management
    |--------------------------------------------------------------------------
    | Guarded by users.view and roles.view.
    | In practice only your "admin" role will have these permissions.
    */
    Route::resource('users', UserController::class)
        ->except(['show'])
        ->middleware('permission:users.view');

    Route::resource('roles', RoleController::class)
        ->except(['show'])
        ->middleware('permission:roles.view');

    /*
    |--------------------------------------------------------------------------
    | Debits & Credits
    |--------------------------------------------------------------------------
    */
    Route::resource('debits-credits', DebitCreditController::class)
        ->middleware('permission:debits-credits.view');

    /*
    |--------------------------------------------------------------------------
    | Transactions - FIXED ROUTE ORDER
    |--------------------------------------------------------------------------
    | All guarded by transactions.view for now.
    */
    Route::middleware('permission:transactions.view')->group(function () {

        // EXPORTS MUST COME FIRST - before any {transaction} routes
        Route::get('/transactions/export/csv', [TransactionController::class, 'exportCsv'])
            ->name('transactions.export.csv');

        Route::get('/transactions/export/pdf', [TransactionController::class, 'exportPdf'])
            ->name('transactions.export.pdf');

        // List & create (no parameters)
        Route::get('/transactions', [TransactionController::class, 'index'])
            ->name('transactions.index');

        Route::get('/transactions/create', [TransactionController::class, 'create'])
            ->name('transactions.create');

        Route::post('/transactions', [TransactionController::class, 'store'])
            ->name('transactions.store');

        // Parameterized routes LAST with whereNumber constraint
        Route::get('/transactions/{transaction}', [TransactionController::class, 'show'])
            ->whereNumber('transaction')
            ->name('transactions.show');

        Route::get('/transactions/{transaction}/edit', [TransactionController::class, 'edit'])
            ->whereNumber('transaction')
            ->name('transactions.edit');

        Route::put('/transactions/{transaction}', [TransactionController::class, 'update'])
            ->whereNumber('transaction')
            ->name('transactions.update');

        Route::delete('/transactions/{transaction}', [TransactionController::class, 'destroy'])
            ->whereNumber('transaction')
            ->name('transactions.destroy');
    });

    /*
    |--------------------------------------------------------------------------
    | Inventory: Categories, Products, Suppliers, Customers
    |--------------------------------------------------------------------------
    */
    // Categories
    Route::resource('categories', CategoryController::class)
        ->middleware('permission:categories.view');

    Route::post('categories/{id}/restore', [CategoryController::class, 'restore'])
        ->name('categories.restore')
        ->middleware('permission:categories.view');

    Route::delete('categories/{id}/force', [CategoryController::class, 'forceDestroy'])
        ->name('categories.forceDestroy')
        ->middleware('permission:categories.view');

    // Products
    Route::resource('products', ProductController::class)
        ->middleware('permission:products.view');

    // Suppliers
    Route::resource('suppliers', SupplierController::class)
        ->middleware('permission:suppliers.view');

    // Customers
    Route::resource('customers', CustomerController::class)
        ->middleware('permission:customers.view');

    /*
    |--------------------------------------------------------------------------
    | Stock Movements (history)
    |--------------------------------------------------------------------------
    */
    Route::middleware('permission:stock.view')->group(function () {
        Route::get('/stock-history', [StockMovementController::class, 'index'])
            ->name('stock.history');

        Route::get('/stock-history/export/csv', [StockMovementController::class, 'exportCsv'])
            ->name('stock.history.export.csv');

        Route::get('/stock-history/export/pdf', [StockMovementController::class, 'exportPdf'])
            ->name('stock.history.export.pdf');
    });

    /*
    |--------------------------------------------------------------------------
    | Purchases
    |--------------------------------------------------------------------------
    */
    Route::middleware('permission:purchases.view')->group(function () {
        Route::resource('purchases', PurchaseController::class);

        Route::get('/purchases/{purchase}/invoice', [PurchaseController::class, 'invoice'])
            ->name('purchases.invoice')
            ->whereNumber('purchase');

        Route::post('/purchases/{purchase}/returns', [PurchaseReturnController::class, 'store'])
            ->name('purchases.returns.store')
            ->whereNumber('purchase');

        Route::delete('/purchases/returns/{return}', [PurchaseReturnController::class, 'destroy'])
            ->name('purchases.returns.destroy');

        Route::get('/purchases/returns/{return}/note', [PurchaseReturnController::class, 'note'])
            ->name('purchases.returns.note');
    });

    /*
    |--------------------------------------------------------------------------
    | Sales
    |--------------------------------------------------------------------------
    */
    Route::middleware('permission:sales.view')->group(function () {

        // Sales exports must be defined BEFORE the sales resource
        Route::get('/sales/export', [SaleController::class, 'export'])
            ->name('sales.export');

        // Sales resource
        Route::resource('sales', SaleController::class)
            ->whereNumber('sale');

        // Sales payments report PDF
        Route::get('/reports/sales/payments/pdf', [SaleController::class, 'exportPaymentsPdf'])
            ->name('sales.payments.pdf'); // or ->name('reports.sales.payments.pdf');

        // Sales invoice
        Route::get('/sales/{sale}/invoice', [SaleController::class, 'invoice'])
            ->name('sales.invoice')
            ->whereNumber('sale');
    });

    /*
    |--------------------------------------------------------------------------
    | Sales Returns
    |--------------------------------------------------------------------------
    | Using sales.view for now; later we can tighten to sales.edit.
    */
    Route::middleware('permission:sales.view')->group(function () {
        Route::post('/sales/{sale}/returns', [SaleReturnController::class, 'store'])
            ->name('sales.returns.store')
            ->whereNumber('sale');

        Route::delete('/sales/returns/{return}', [SaleReturnController::class, 'destroy'])
            ->name('sales.returns.destroy');
    });

    /*
    |--------------------------------------------------------------------------
    | Supplier & Customer Statements
    |--------------------------------------------------------------------------
    | These are basically reports.
    */
    Route::middleware('permission:reports.view')->group(function () {
        Route::get('/reports/suppliers/statement', [StatementController::class, 'supplier'])
            ->name('reports.suppliers.statement');
        Route::get('reports/suppliers/statement/pdf', [StatementController::class, 'supplierPdf'])
            ->name('reports.suppliers.statement.pdf');
        Route::get('/reports/customers/statement', [StatementController::class, 'customer'])
            ->name('reports.customers.statement');
        Route::get('reports/customers/statement/pdf', [StatementController::class, 'customerPdf'])
            ->name('reports.customers.statement.pdf');
    });

    /*
    |--------------------------------------------------------------------------
    | Loans (Pro)
    |--------------------------------------------------------------------------
    */
    Route::middleware('permission:loans.view')->group(function () {

        // Core resource
        Route::resource('loans', LoanController::class)
            ->whereNumber('loan');

        // Time-window views (cards/table/summary); ?from=YYYY-MM-DD&to=YYYY-MM-DD for custom
        Route::get('loans/range/{range}', [LoanController::class, 'range'])
            ->whereIn('range', ['day','week','month','quarter','year','custom'])
            ->name('loans.range');

        // Party-focused view: loans for a specific customer or supplier
        Route::get('loans/party/{party}/{id}', [LoanController::class, 'party'])
            ->whereIn('party', ['customer', 'supplier'])
            ->whereNumber('id')
            ->name('loans.party');

        // Aggregates for dashboards (JSON or HTML)
        Route::get('loans/insights', [LoanController::class, 'insights'])
            ->name('loans.insights');

        // Due-date calendar feed (JSON; later can add ICS)
        Route::get('loans/calendar-feed', [LoanController::class, 'calendarFeed'])
            ->name('loans.calendar.feed');

        // Actions
        Route::post('loans/{loan}/mark-paid', [LoanController::class, 'markPaid'])
            ->whereNumber('loan')
            ->name('loans.mark-paid');

        Route::post('loans/{loan}/recalculate', [LoanController::class, 'recalculate'])
            ->whereNumber('loan')
            ->name('loans.recalculate');

        // Exports
        Route::get('loans/export/csv', [LoanController::class, 'exportCsv'])
            ->name('loans.export.csv');

        // Keep your PDF export (still under loans.view; if you want admin-only,
        // we can add a custom permission later like loans.export)
        Route::get('loans/export/pdf', [LoanController::class, 'exportPdf'])
            ->name('loans.export.pdf');
    });

    /*
    |--------------------------------------------------------------------------
    | Loan Payments (nested + shallow)
    |--------------------------------------------------------------------------
    */
    Route::prefix('loans/{loan}')
        ->whereNumber('loan')
        ->middleware('permission:loans.view')
        ->group(function () {
            Route::get('payments/create', [LoanPaymentController::class, 'create'])
                ->name('loan-payments.create');

            Route::post('payments', [LoanPaymentController::class, 'store'])
                ->name('loan-payments.store');
        });

    Route::middleware('permission:loans.view')->group(function () {
        Route::get('loan-payments/{payment}/edit', [LoanPaymentController::class, 'edit'])
            ->whereNumber('payment')
            ->name('loan-payments.edit');

        Route::put('loan-payments/{payment}', [LoanPaymentController::class, 'update'])
            ->whereNumber('payment')
            ->name('loan-payments.update');

        Route::delete('loan-payments/{payment}', [LoanPaymentController::class, 'destroy'])
            ->whereNumber('payment')
            ->name('loan-payments.destroy');

        Route::get('loan-payments/{payment}/receipt', [LoanPaymentController::class, 'receipt'])
            ->whereNumber('payment')
            ->name('loan-payments.receipt');
    });

    /*
    |--------------------------------------------------------------------------
    | Inter-company Item Loans
    |--------------------------------------------------------------------------
    | Re-using loans.view permission for this module.
    */
    /*
    |--------------------------------------------------------------------------
    | Inter-company Item Loans
    |--------------------------------------------------------------------------
    */
    Route::middleware('permission:item-loans.view')->group(function () {
        Route::resource('inter-loans', ItemLoanController::class)
            ->parameters(['inter-loans' => 'itemLoan'])
            ->names('item-loans');

        Route::post('inter-loans/{itemLoan}/return', [ItemLoanController::class, 'recordReturn'])
            ->name('item-loans.return');
    });

    // Partner Companies (for AJAX creation)
    Route::post('/partner-companies', [App\Http\Controllers\PartnerCompanyController::class, 'store'])
        ->name('partner-companies.store');

    /*
    |--------------------------------------------------------------------------
    | Reports
    |--------------------------------------------------------------------------
    */
    Route::middleware('permission:reports.view')->group(function () {
        Route::get('/reports', [ReportsController::class, 'index'])
            ->name('reports.index');

        Route::get('/reports/export/sales.csv', [ReportsController::class, 'exportSalesCsv'])
            ->name('reports.export.sales.csv');

        Route::get('/reports/export/finance.pdf', [ReportsController::class, 'exportFinancePdf'])
            ->name('reports.export.finance.pdf');

        Route::get('/reports/export/insights.pdf', [ReportsController::class, 'exportInsightsPdf'])
            ->name('reports.export.insights.pdf');

        // Profit & Loss PDF
        Route::get('/reports/export/pl.pdf', [ReportsController::class, 'exportProfitLossPdf'])
            ->name('reports.export.pl.pdf');
    });

    /*
    |--------------------------------------------------------------------------
    | Expenses
    |--------------------------------------------------------------------------
    | For now, we reuse transactions.view (it's part of finance flow).
    | If you prefer a dedicated "expenses" module, we can add it to PermissionSeeder.
    */
    /*
    |--------------------------------------------------------------------------
    | Expenses
    |--------------------------------------------------------------------------
    */
    Route::middleware('permission:expenses.view')->group(function () {
        Route::get('/expenses', [ExpenseController::class, 'index'])
            ->name('expenses.index');

        Route::get('/expenses/create', [ExpenseController::class, 'create'])
            ->name('expenses.create');

        Route::post('/expenses', [ExpenseController::class, 'store'])
            ->name('expenses.store');

        Route::get('/expenses/{expense}', [ExpenseController::class, 'show'])
            ->name('expenses.show');

        Route::get('/expenses/{expense}/edit', [ExpenseController::class, 'edit'])
            ->name('expenses.edit');

        Route::put('/expenses/{expense}', [ExpenseController::class, 'update'])
            ->name('expenses.update');

        Route::delete('/expenses/{expense}', [ExpenseController::class, 'destroy'])
            ->name('expenses.destroy');
    });

    /*
    |--------------------------------------------------------------------------
    | User Profile (Breeze)
    |--------------------------------------------------------------------------
    | Everyone who can log in can manage their own profile.
    */
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

/* Auth routes (Breeze) */
require __DIR__ . '/auth.php';
