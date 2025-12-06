<?php

namespace App\Observers;

use App\Models\Expense;
use App\Models\Transaction;
use App\Models\DebitCredit;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class ExpenseObserver
{
    /**
     * Handle the Expense "created" event.
     */
    public function created(Expense $expense): void
    {
        DB::afterCommit(function () use ($expense) {
            try {
                // 1. Create Transaction (Debit)
                $txn = Transaction::create([
                    'type'             => 'debit',
                    'user_id'          => $expense->created_by ?? Auth::id(),
                    'supplier_id'      => $expense->supplier_id,
                    'amount'           => $expense->amount,
                    'transaction_date' => $expense->date,
                    'method'           => $expense->method ?? 'cash', // Default to cash if null
                    'notes'            => "Expense #{$expense->id}: " . ($expense->note ?? 'N/A'),
                ]);

                // 2. Create DebitCredit (Debit)
                DebitCredit::create([
                    'type'           => 'debit',
                    'amount'         => $expense->amount,
                    'description'    => "Expense: " . ($expense->category->name ?? 'Uncategorized'),
                    'date'           => $expense->date,
                    'user_id'        => $expense->created_by ?? Auth::id(),
                    'transaction_id' => $txn->id,
                ]);

                Log::info("ExpenseObserver: Created financial records for Expense #{$expense->id}");

            } catch (\Throwable $e) {
                Log::error("ExpenseObserver: Failed to create records for Expense #{$expense->id}", ['error' => $e->getMessage()]);
            }
        });
    }

    /**
     * Handle the Expense "updated" event.
     */
    public function updated(Expense $expense): void
    {
        DB::afterCommit(function () use ($expense) {
            try {
                // Find existing transaction linked to this expense?
                // Currently Expense model doesn't link to Transaction directly, 
                // but we can search by notes or we should add 'expense_id' to transactions table?
                // For now, let's search by notes pattern or just add a column later.
                // Searching by unique note pattern "Expense #{id}:"
                
                $txn = Transaction::where('notes', 'LIKE', "Expense #{$expense->id}:%")->first();

                if ($txn) {
                    $txn->update([
                        'amount'           => $expense->amount,
                        'transaction_date' => $expense->date,
                        'method'           => $expense->method ?? 'cash',
                        'notes'            => "Expense #{$expense->id}: " . ($expense->note ?? 'N/A'),
                    ]);

                    $dc = DebitCredit::where('transaction_id', $txn->id)->first();
                    if ($dc) {
                        $dc->update([
                            'amount'      => $expense->amount,
                            'description' => "Expense: " . ($expense->category->name ?? 'Uncategorized'),
                            'date'        => $expense->date,
                        ]);
                    }
                }

            } catch (\Throwable $e) {
                Log::error("ExpenseObserver: Failed to update records for Expense #{$expense->id}", ['error' => $e->getMessage()]);
            }
        });
    }

    /**
     * Handle the Expense "deleted" event.
     */
    public function deleted(Expense $expense): void
    {
        DB::afterCommit(function () use ($expense) {
            try {
                $txn = Transaction::where('notes', 'LIKE', "Expense #{$expense->id}:%")->first();
                if ($txn) {
                    DebitCredit::where('transaction_id', $txn->id)->delete();
                    $txn->delete();
                }
            } catch (\Throwable $e) {
                Log::error("ExpenseObserver: Failed to delete records for Expense #{$expense->id}", ['error' => $e->getMessage()]);
            }
        });
    }
}
