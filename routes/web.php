<?php

use Illuminate\Support\Facades\Route;

use App\Http\Controllers\Auth\RoleRedirectController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DebitCreditController;
use App\Http\Controllers\ExpenseController;
use App\Http\Controllers\LoanController;
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

/* -------- Root redirect -------- */
Route::get('/', function () {
    return auth()->check()
        ? redirect()->route('dashboard')
        : redirect()->route('login');
});

/* -------- Role-based redirect after login -------- */
Route::get('/redirect-by-role', RoleRedirectController::class)
    ->middleware(['auth'])
    ->name('redirect.by.role');

/* =========================================================================
| Authenticated & Verified Routes
|========================================================================= */
Route::middleware(['auth', 'verified'])->group(function () {

    /* Dashboard */
    Route::get('/dashboard', [DashboardController::class, 'index'])
        ->name('dashboard')
        ->middleware('role:admin|manager|cashier|accountant');

    Route::get('/dashboard/sales-chart', [DashboardController::class, 'salesChartData'])
        ->name('dashboard.sales.chart')
        ->middleware('role:admin|manager');

    /* Admin-only: Users & Roles */
    Route::middleware('role:admin')->group(function () {
        Route::resource('users', UserController::class)->except(['show']);
        Route::resource('roles', RoleController::class)->except(['show']);
    });

    /* Debits & Credits */
    Route::resource('debits-credits', DebitCreditController::class)
        ->middleware('role:admin|manager|cashier|accountant');

    /* Transactions */
    Route::middleware(['role:admin|manager|accountant'])->group(function () {
        Route::get('/transactions', [TransactionController::class, 'index'])->name('transactions.index');
        Route::get('/transactions/create', [TransactionController::class, 'create'])->name('transactions.create');
        Route::post('/transactions', [TransactionController::class, 'store'])->name('transactions.store');
        Route::get('/transactions/{transaction}', [TransactionController::class, 'show'])->name('transactions.show');
        Route::get('/transactions/{transaction}/edit', [TransactionController::class, 'edit'])->name('transactions.edit');
        Route::put('/transactions/{transaction}', [TransactionController::class, 'update'])->name('transactions.update');
        Route::delete('/transactions/{transaction}', [TransactionController::class, 'destroy'])->name('transactions.destroy');

        // Exports
        Route::get('/transactions/export/csv', [TransactionController::class, 'exportCsv'])->name('transactions.export.csv');
        Route::get('/transactions/export/pdf', [TransactionController::class, 'exportPdf'])->name('transactions.export.pdf');
    });

    /* Inventory */
    Route::resource('categories', CategoryController::class)->middleware('role:admin|manager');
    Route::resource('products', ProductController::class)->middleware('role:admin|manager|cashier');
    Route::resource('suppliers', SupplierController::class)->middleware('role:admin|manager');
    Route::resource('customers', CustomerController::class)->middleware('role:admin|manager|cashier');

    /* Stock Movements (history) */
    Route::get('/stock-history', [StockMovementController::class, 'index'])
        ->name('stock.history')
        ->middleware('role:admin|manager|cashier');

    Route::get('/stock-history/export/csv', [StockMovementController::class, 'exportCsv'])
        ->name('stock.history.export.csv')
        ->middleware('role:admin|manager');

    Route::get('/stock-history/export/pdf', [StockMovementController::class, 'exportPdf'])
        ->name('stock.history.export.pdf')
        ->middleware('role:admin|manager');

    /* Purchases & Sales */

    // Purchases
    Route::resource('purchases', PurchaseController::class)->middleware('role:admin|manager');
    Route::get('/purchases/{purchase}/invoice', [PurchaseController::class, 'invoice'])
        ->name('purchases.invoice')
        ->middleware('role:admin|manager');
    Route::post('/purchases/{purchase}/returns', [PurchaseReturnController::class, 'store'])
    ->name('purchases.returns.store');
    Route::delete('/purchases/returns/{return}', [PurchaseReturnController::class, 'destroy'])
    ->name('purchases.returns.destroy');
    Route::get('/purchases/returns/{return}/note', [PurchaseReturnController::class, 'note'])
    ->name('purchases.returns.note');

    // Sales exports must be defined BEFORE the sales resource to avoid collision with {sale}
    Route::get('/sales/export', [SaleController::class, 'export'])
        ->name('sales.export')
        ->middleware('role:admin|manager|cashier');

    // Sales resource
    Route::resource('sales', SaleController::class)
        ->whereNumber('sale')
        ->middleware('role:admin|manager|cashier');

    // Sales invoice
    Route::get('/sales/{sale}/invoice', [SaleController::class, 'invoice'])
        ->name('sales.invoice')
        ->middleware('role:admin|manager|cashier');

    /* Sales Returns */
    Route::middleware(['role:admin|manager|accountant'])->group(function () {
        Route::post('/sales/{sale}/returns', [SaleReturnController::class, 'store'])
            ->name('sales.returns.store')
            ->whereNumber('sale');

        Route::delete('/sales/returns/{return}', [SaleReturnController::class, 'destroy'])
            ->name('sales.returns.destroy');
    });
    /* suppliersStatement */
    Route::get('/reports/suppliers/statement', [StatementController::class, 'supplier'])
    ->name('reports.suppliers.statement')
    ->middleware('role:admin|manager|cashier');
    Route::get('/reports/customers/statement', [StatementController::class, 'customer'])
    ->name('reports.customers.statement')
    ->middleware('role:admin|manager|cashier');
    /* Loans */
    Route::middleware(['role:admin|manager|accountant'])->group(function () {
        Route::resource('loans', LoanController::class);
    });

    Route::get('loans/export/pdf', [LoanController::class, 'exportPdf'])
        ->name('loans.export.pdf')
        ->middleware('role:admin');

    Route::prefix('loans/{loan}')->group(function () {
        Route::get('payments/create', [\App\Http\Controllers\LoanPaymentController::class, 'create'])
            ->name('loan-payments.create');
        Route::post('payments', [\App\Http\Controllers\LoanPaymentController::class, 'store'])
            ->name('loan-payments.store');
    });

    /* Reports */
    Route::middleware('role:admin|accountant')->group(function () {
        Route::get('/reports', [ReportsController::class, 'index'])->name('reports.index');
        Route::get('/reports/export/sales/csv', [ReportsController::class, 'exportSalesCsv'])->name('reports.export.sales.csv');
        Route::get('/reports/export/finance/pdf', [ReportsController::class, 'exportFinancePdf'])->name('reports.export.finance.pdf');
        Route::get('/reports/export/insights/pdf', [ReportsController::class, 'exportInsightsPdf'])->name('reports.export.insights.pdf');
    });

    /* Expenses */
    Route::middleware(['role:admin|manager|accountant'])->group(function () {
        Route::get('/expenses', [ExpenseController::class, 'index'])->name('expenses.index');
        Route::get('/expenses/create', [ExpenseController::class, 'create'])->name('expenses.create');
        Route::post('/expenses', [ExpenseController::class, 'store'])->name('expenses.store');
        Route::get('/expenses/{expense}', [ExpenseController::class, 'show'])->name('expenses.show');
        Route::get('/expenses/{expense}/edit', [ExpenseController::class, 'edit'])->name('expenses.edit');
        Route::put('/expenses/{expense}', [ExpenseController::class, 'update'])->name('expenses.update');
        Route::delete('/expenses/{expense}', [ExpenseController::class, 'destroy'])->name('expenses.destroy');
    });

    /* User Profile (Breeze) */
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

/* Auth routes (Breeze) */
require __DIR__ . '/auth.php';
