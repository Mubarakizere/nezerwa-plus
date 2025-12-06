<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Models\Sale;
use App\Models\Purchase;
use App\Models\Loan;
use App\Models\SaleReturn;
use App\Observers\SaleObserver;
use App\Models\LoanPayment;
use App\Observers\PurchaseObserver;
use App\Observers\LoanObserver;
use App\Observers\LoanPaymentObserver;
use App\Observers\SaleReturnObserver;
use App\Models\Expense;
use App\Observers\ExpenseObserver;
class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Register observers
        Sale::observe(SaleObserver::class);
        Purchase::observe(PurchaseObserver::class);
        Loan::observe(LoanObserver::class);
        LoanPayment::observe(LoanPaymentObserver::class);
        SaleReturn::observe(SaleReturnObserver::class);
        Expense::observe(ExpenseObserver::class);
    }
}
