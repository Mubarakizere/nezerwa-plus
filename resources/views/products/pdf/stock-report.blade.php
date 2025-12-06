<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Stock Report</title>
    <style>
        body { font-family: sans-serif; font-size: 12px; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background-color: #f2f2f2; }
        .text-right { text-align: right; }
        .text-center { text-align: center; }
        .badge { padding: 2px 6px; border-radius: 4px; font-size: 10px; color: white; }
        .bg-red { background-color: #ef4444; }
        .bg-amber { background-color: #f59e0b; }
        .bg-green { background-color: #10b981; }
        h1 { font-size: 18px; margin-bottom: 5px; }
        .meta { font-size: 10px; color: #666; margin-bottom: 20px; }
    </style>
</head>
<body>
    <h1>Product Stock Report</h1>
    <div class="meta">
        Generated on: {{ now()->format('Y-m-d H:i') }} <br>
        Filter: {{ ucfirst($filter) }} Products 
        @if($filter === 'low') (<= {{ $threshold }}) @endif
    </div>

    <table>
        <thead>
            <tr>
                <th>Product Name</th>
                <th>Category</th>
                <th class="text-right">Cost Price</th>
                <th class="text-right">Selling Price</th>
                <th class="text-right">Stock</th>
                <th class="text-right">Total Value (Cost)</th>
            </tr>
        </thead>
        <tbody>
            @php $totalValue = 0; @endphp
            @foreach($products as $product)
                @php
                    $stock = (float) $product->computed_stock;
                    $cost  = (float) ($product->cost_price ?? 0);
                    $val   = $stock * $cost;
                    if ($stock > 0) $totalValue += $val;
                @endphp
                <tr>
                    <td>{{ $product->name }}</td>
                    <td>{{ $product->category->name ?? '-' }}</td>
                    <td class="text-right">{{ number_format($cost, 2) }}</td>
                    <td class="text-right">{{ number_format((float)$product->price, 2) }}</td>
                    <td class="text-right">
                        @if($stock <= 0)
                            <span class="badge bg-red">OUT ({{ $stock }})</span>
                        @elseif($stock <= $threshold)
                            <span class="badge bg-amber">{{ $stock }}</span>
                        @else
                            <span class="badge bg-green">{{ $stock }}</span>
                        @endif
                    </td>
                    <td class="text-right">{{ number_format($val, 2) }}</td>
                </tr>
            @endforeach
        </tbody>
        <tfoot>
            <tr>
                <th colspan="5" class="text-right">Total Inventory Value:</th>
                <th class="text-right">{{ number_format($totalValue, 2) }}</th>
            </tr>
        </tfoot>
    </table>
</body>
</html>
