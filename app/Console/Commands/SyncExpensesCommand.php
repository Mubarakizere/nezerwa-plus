<?php

namespace App\Console\Commands;

use App\Models\Expense;
use App\Models\Transaction;
use App\Models\DebitCredit;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class SyncExpensesCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:sync-expenses';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Syncs existing expenses to financial transactions';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info("Starting Expense Sync...");

        $expenses = Expense::all();
        $count = 0;

        foreach ($expenses as $expense) {
            // Check if txn exists using the note pattern
            $exists = Transaction::where('notes', 'LIKE', "Expense #{$expense->id}:%")->exists();
            
            if (!$exists) {
                DB::transaction(function () use ($expense) {
                    // 1. Create Transaction (Debit)
                    $txn = Transaction::create([
                        'type'             => 'debit',
                        'user_id'          => $expense->created_by ?? 1,
                        'supplier_id'      => $expense->supplier_id,
                        'amount'           => $expense->amount,
                        'transaction_date' => $expense->date,
                        'method'           => $expense->method ?? 'cash',
                        'notes'            => "Expense #{$expense->id}: " . ($expense->note ?? 'N/A'),
                    ]);

                    // 2. Create DebitCredit (Debit)
                    DebitCredit::create([
                        'type'           => 'debit',
                        'amount'         => $expense->amount,
                        'description'    => "Expense: " . ($expense->category->name ?? 'Uncategorized'),
                        'date'           => $expense->date,
                        'user_id'        => $expense->created_by ?? 1,
                        'transaction_id' => $txn->id,
                    ]);
                });
                $count++;
                $this->info("Synced Expense #{$expense->id}");
            }
        }

        $this->info("Sync Complete. Synced {$count} new records.");
    }
}
