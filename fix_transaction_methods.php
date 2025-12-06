<?php

use App\Models\Sale;
use App\Models\Transaction;

// Iterate over all transactions linked to a sale
Transaction::whereNotNull('sale_id')->chunk(100, function ($txns) {
    foreach ($txns as $txn) {
        $sale = Sale::find($txn->sale_id);
        if (!$sale || !$sale->payment_channel) continue;

        // If the transaction method is cash but the sale channel is something else (and valid), fix it.
        // Or actually, just always sync them to be safe.
        if ($txn->method !== $sale->payment_channel) {
            echo "Fixing Txn #{$txn->id} (Sale #{$sale->id}): {$txn->method} -> {$sale->payment_channel}\n";
            $txn->update(['method' => $sale->payment_channel]);
        }
    }
});

echo "Done fixing transaction methods.\n";
