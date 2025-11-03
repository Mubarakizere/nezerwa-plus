<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Models\Sale;

Route::post('/sync/offline-sales', function (Request $request) {
    $sales = $request->input('sales', []);
    $synced = [];

    foreach ($sales as $saleData) {
        $sale = Sale::create([
            'customer_id' => $saleData['customer_id'] ?? null,
            'user_id'     => $saleData['user_id'] ?? 1,
            'total_amount'=> $saleData['total_amount'] ?? 0,
            'created_at'  => $saleData['created_at'] ?? now(),
        ]);
        $synced[] = $sale->id;
    }

    return response()->json(['synced' => $synced]);
});
