<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\SupplierController;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\PurchaseController;
use App\Http\Controllers\SaleController;
use App\Http\Controllers\LoanController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\TransactionController;
use App\Http\Controllers\DebitCreditController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\StockMovementController;
use App\Http\Controllers\ReportsController;
use App\Http\Controllers\Auth\RoleRedirectController;

/*
|--------------------------------------------------------------------------
| 🌐 Web Routes
|--------------------------------------------------------------------------
| Defines all web-accessible routes for the Stock Management System.
| Includes authentication, role-based access, and dashboard modules.
|--------------------------------------------------------------------------
*/

// ======================================================
// 🏠 Root Redirect Logic
// ======================================================
Route::get('/', function () {
    return auth()->check()
        ? redirect()->route('dashboard')
        : redirect()->route('login');
});

// ======================================================
// 🚦 Role-Based Redirect after Login (Laravel Breeze)
// ======================================================
Route::get('/redirect-by-role', RoleRedirectController::class)
    ->middleware(['auth'])
    ->name('redirect.by.role');

// ======================================================
// 🔐 Authenticated & Verified Routes
// ======================================================
Route::middleware(['auth', 'verified'])->group(function () {

    // =======================
    // 🏠 Dashboard & Charts
    // =======================
    Route::get('/dashboard', [DashboardController::class, 'index'])
        ->name('dashboard')
        ->middleware('role:admin|manager|cashier|accountant');

    // 🔄 AJAX Sales Chart (30-day)
    Route::get('/dashboard/sales-chart', [DashboardController::class, 'salesChartData'])
        ->name('dashboard.sales.chart')
        ->middleware('role:admin|manager');

    // =======================
    // 👥 User Management (Admin only)
    // =======================
    Route::middleware('role:admin')->group(function () {
        Route::resource('users', UserController::class)->except(['show']);
        Route::resource('roles', \App\Http\Controllers\RoleController::class)->except(['show']);
    });

    // =======================
    // 🧾 Finance & Debits / Credits
    // =======================
    Route::resource('debits-credits', DebitCreditController::class)
        ->middleware('role:admin|manager|cashier|accountant');

    // =======================
    // 💳 Transactions
    // =======================
    Route::middleware(['role:admin|manager|accountant'])->group(function () {
        Route::get('/transactions', [TransactionController::class, 'index'])->name('transactions.index');
        Route::get('/transactions/create', [TransactionController::class, 'create'])->name('transactions.create');
        Route::post('/transactions', [TransactionController::class, 'store'])->name('transactions.store');
        Route::get('/transactions/{transaction}', [TransactionController::class, 'show'])->name('transactions.show');
        Route::get('/transactions/{transaction}/edit', [TransactionController::class, 'edit'])->name('transactions.edit');
        Route::put('/transactions/{transaction}', [TransactionController::class, 'update'])->name('transactions.update');
        Route::delete('/transactions/{transaction}', [TransactionController::class, 'destroy'])->name('transactions.destroy');

        // Export Routes
        Route::get('/transactions/export/csv', [TransactionController::class, 'exportCsv'])->name('transactions.export.csv');
        Route::get('/transactions/export/pdf', [TransactionController::class, 'exportPdf'])->name('transactions.export.pdf');
    });

    // =======================
    // 📦 Inventory Management
    // =======================
    Route::resource('categories', CategoryController::class)->middleware('role:admin|manager');
    Route::resource('products', ProductController::class)->middleware('role:admin|manager|cashier');
    Route::get('/products/{product}', [ProductController::class, 'show'])
        ->name('products.show')
        ->middleware('role:admin|manager|cashier');

    Route::resource('suppliers', SupplierController::class)->middleware('role:admin|manager');
    Route::resource('customers', CustomerController::class)->middleware('role:admin|manager|cashier');

    // =======================
    // 📊 Stock Movements
    // =======================
    Route::get('/stock-history', [StockMovementController::class, 'index'])
        ->name('stock.history')
        ->middleware('role:admin|manager|cashier');

    Route::get('/stock-history/export/csv', [StockMovementController::class, 'exportCsv'])
        ->name('stock.history.export.csv')
        ->middleware('role:admin|manager');

    Route::get('/stock-history/export/pdf', [StockMovementController::class, 'exportPdf'])
        ->name('stock.history.export.pdf')
        ->middleware('role:admin|manager');

    // =======================
    // 💰 Purchases & Sales
    // =======================
    Route::resource('purchases', PurchaseController::class)->middleware('role:admin|manager');
    Route::resource('sales', SaleController::class)->middleware('role:admin|manager|cashier');

    // 🧾 Invoices
    Route::get('/sales/{sale}/invoice', [SaleController::class, 'invoice'])
        ->name('sales.invoice')
        ->middleware('role:admin|manager|cashier');

    Route::get('/purchases/{purchase}/invoice', [PurchaseController::class, 'invoice'])
        ->name('purchases.invoice')
        ->middleware('role:admin|manager');

    // =======================
    // 🏦 Loans Management
    // =======================
    Route::middleware(['auth'])->group(function () {

        Route::middleware('role:admin|manager|accountant')->group(function () {
            Route::resource('loans', LoanController::class);
        });

        // Loan report export (admin only)
        Route::get('loans/export/pdf', [LoanController::class, 'exportPdf'])
            ->name('loans.export.pdf')
            ->middleware('role:admin');

        Route::prefix('loans/{loan}')->middleware('auth')->group(function () {
            Route::get('payments/create', [\App\Http\Controllers\LoanPaymentController::class, 'create'])
                ->name('loan-payments.create');
            Route::post('payments', [\App\Http\Controllers\LoanPaymentController::class, 'store'])
                ->name('loan-payments.store');
        });
    });

    // =======================
    // 📈 Reports (Admin & Accountant)
    // =======================
    Route::middleware('role:admin|accountant')->group(function () {
        Route::get('/reports', [ReportsController::class, 'index'])->name('reports.index');
        Route::get('/reports/export/sales/csv', [ReportsController::class, 'exportSalesCsv'])->name('reports.export.sales.csv');
        Route::get('/reports/export/finance/pdf', [ReportsController::class, 'exportFinancePdf'])->name('reports.export.finance.pdf');
        Route::get('/reports/export/insights/pdf', [ReportsController::class, 'exportInsightsPdf'])->name('reports.export.insights.pdf');
    });

    // =======================
    // 👤 User Profile (Breeze)
    // =======================
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

// ======================================================
// 🔁 Auth Routes (Laravel Breeze)
// ======================================================
require __DIR__ . '/auth.php';
