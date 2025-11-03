@php
    $supplier = $purchase->supplier ?? null;
    $date = $purchase->date ?? $purchase->purchased_at ?? $purchase->created_at;
    $computedSubtotal = $purchase->subtotal ?? $purchase->items->sum(fn($i) => (float)$i->quantity * (float)$i->unit_cost);
    $tax      = $purchase->tax ?? $purchase->tax_amount ?? 0;
    $discount = $purchase->discount ?? $purchase->discount_amount ?? 0;
    $total    = $purchase->total ?? $purchase->total_amount ?? ($computedSubtotal + $tax - $discount);
    $paid     = $purchase->paid ?? $purchase->amount_paid ?? 0;
    $balance  = max(0, $total - $paid);
    $fmt = fn($n) => number_format((float)$n, 2);
@endphp
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Purchase Invoice #{{ $purchase->id }}</title>
    <style>
        @page { margin: 28mm 18mm; }
        body { font-family: DejaVu Sans, sans-serif; color: #111827; font-size: 12px; }
        .header { display: flex; justify-content: space-between; align-items: center; border-bottom: 2px solid #e5e7eb; padding-bottom: 8px; margin-bottom: 18px; }
        .h1 { font-size: 22px; font-weight: 700; margin: 0; }
        .muted { color: #6b7280; }
        .block { margin-bottom: 14px; }
        .chip { display:inline-block; padding:3px 8px; border:1px solid #e5e7eb; border-radius: 6px; font-size: 11px; color:#374151; }
        table { width: 100%; border-collapse: collapse; }
        th, td { padding: 10px 8px; }
        thead th { font-size: 11px; text-transform: uppercase; letter-spacing: .04em; color:#6b7280; border-bottom:1px solid #e5e7eb; text-align:left; }
        tbody td { border-bottom:1px solid #f3f4f6; }
        tfoot td { padding: 6px 8px; }
        .right { text-align: right; }
        .totals { width: 320px; margin-left: auto; }
        .brand { font-weight: 700; letter-spacing: .01em; }
        .small { font-size: 11px; }
    </style>
</head>
<body>

    <div class="header">
        <div>
            <div class="brand">{{ config('app.name', 'Stock Manager') }}</div>
            <div class="muted small">Purchase Invoice</div>
        </div>
        <div class="right">
            <div class="h1">#{{ $purchase->id }}</div>
            <div class="muted small">{{ \Carbon\Carbon::parse($date)->format('M j, Y g:i A') }}</div>
            <div class="chip" style="margin-top:6px;">Method: {{ strtoupper($purchase->method ?? 'cash') }}</div>
        </div>
    </div>

    <div class="block" style="display:flex; gap:24px;">
        <div style="flex:1;">
            <div class="muted small" style="margin-bottom:4px;">Supplier</div>
            <div><strong>{{ $supplier->name ?? '—' }}</strong></div>
            @if(!empty($supplier?->email))<div class="small muted">{{ $supplier->email }}</div>@endif
            @if(!empty($supplier?->phone))<div class="small muted">{{ $supplier->phone }}</div>@endif
            @if(!empty($supplier?->address))<div class="small muted">{{ $supplier->address }}</div>@endif
        </div>
        <div style="flex:1;">
            <div class="muted small" style="margin-bottom:4px;">Purchase</div>
            <div><strong>Status:</strong> {{ ucfirst($purchase->status ?? 'completed') }}</div>
            <div><strong>Reference:</strong> #{{ $purchase->id }}</div>
        </div>
    </div>

    <table style="margin-top:10px;">
        <thead>
            <tr>
                <th>Product</th>
                <th class="right">Qty</th>
                <th class="right">Unit Cost</th>
                <th class="right">Line Total</th>
            </tr>
        </thead>
        <tbody>
            @foreach($purchase->items as $item)
                @php
                    $qty = (float)$item->quantity;
                    $uc  = (float)$item->unit_cost;
                    $lt  = $item->total_cost ?? $qty * $uc;
                @endphp
                <tr>
                    <td>{{ $item->product->name ?? ('#'.$item->product_id) }}</td>
                    <td class="right">{{ number_format($qty, 2) }}</td>
                    <td class="right">RWF {{ $fmt($uc) }}</td>
                    <td class="right">RWF {{ $fmt($lt) }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <table class="totals" style="margin-top:14px;">
        <tr>
            <td class="right muted">Subtotal</td>
            <td class="right"><strong>RWF {{ $fmt($computedSubtotal) }}</strong></td>
        </tr>
        <tr>
            <td class="right muted">Tax</td>
            <td class="right">+ RWF {{ $fmt($tax) }}</td>
        </tr>
        <tr>
            <td class="right muted">Discount</td>
            <td class="right">– RWF {{ $fmt($discount) }}</td>
        </tr>
        <tr>
            <td class="right">Total</td>
            <td class="right"><strong>RWF {{ $fmt($total) }}</strong></td>
        </tr>
        <tr>
            <td class="right muted">Paid</td>
            <td class="right">RWF {{ $fmt($paid) }}</td>
        </tr>
        <tr>
            <td class="right">Balance</td>
            <td class="right"><strong>RWF {{ $fmt($balance) }}</strong></td>
        </tr>
    </table>

    <div style="margin-top:32px; border-top:1px solid #e5e7eb; padding-top:8px;" class="small muted">
        Generated by {{ config('app.name', 'Stock Manager') }} – {{ now()->format('M j, Y g:i A') }}
    </div>

</body>
</html>
