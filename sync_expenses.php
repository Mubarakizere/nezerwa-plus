<?php

use App\Models\Expense;
use App\Models\Transaction;
use App\Models\DebitCredit;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

// Run this in Tinker or via command

echo "Starting Expense Sync...\n";

$expenses = Expense::all();
$count = 0;

foreach ($expenses as $expense) {
    // Check if txn exists
    $exists = Transaction::where('notes', 'LIKE', "Expense #{$expense->id}:%")->exists();
    
    if (!$exists) {
        DB::transaction(function () use ($expense) {
            // 1. Create Transaction (Debit)
            $txn = Transaction::create([
                'type'             => 'debit',
                'user_id'          => $expense->created_by ?? 1, // Default to admin if unknown
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
        echo "Synced Expense #{$expense->id}\n";
    }
}

echo "Sync Complete. Synced {$count} expenses.\n";
