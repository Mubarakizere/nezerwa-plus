{{-- resources/views/purchases/invoice.blade.php --}}
@php
    use Carbon\Carbon;

    $supplier = $purchase->supplier ?? null;
    $date     = $purchase->date ?? $purchase->purchased_at ?? $purchase->created_at;

    $computedSubtotal = $purchase->subtotal
        ?? optional($purchase->items)->sum(fn($i) => (float)$i->quantity * (float)$i->unit_cost);

    $tax      = $purchase->tax ?? $purchase->tax_amount ?? 0;
    $discount = $purchase->discount ?? $purchase->discount_amount ?? 0;
    $total    = $purchase->total ?? $purchase->total_amount ?? ($computedSubtotal + $tax - $discount);
    $paid     = $purchase->paid ?? $purchase->amount_paid ?? 0;
    $balance  = max(0, (float)$total - (float)$paid);

    $fmt = fn($n) => number_format((float)$n, 2);
@endphp
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Purchase Invoice #{{ $purchase->id }}</title>
    <style>
        /* Page */
        @page { margin: 26mm 16mm; }
        * { box-sizing: border-box; }
        body { font-family: DejaVu Sans, sans-serif; color:#111827; font-size:12px; line-height:1.45; }

        /* Utilities */
        .muted { color:#6b7280; }
        .small { font-size:11px; }
        .right { text-align:right; }
        .center { text-align:center; }
        .mt-6 { margin-top:24px; }
        .mb-2 { margin-bottom:8px; }
        .mb-3 { margin-bottom:12px; }
        .mb-4 { margin-bottom:16px; }
        .brand { font-weight:700; letter-spacing:.01em; font-size:18px; }

        /* Header */
        .header-table { width:100%; border-collapse:collapse; margin-bottom:16px; }
        .header-table td { vertical-align:top; }
        .badge { display:inline-block; padding:3px 8px; border:1px solid #e5e7eb; border-radius:6px; font-size:11px; color:#374151; }

        /* Blocks */
        .meta-table { width:100%; border-collapse:collapse; }
        .meta-table td { width:50%; vertical-align:top; padding:0 8px 0 0; }
        .meta-box { border:1px solid #e5e7eb; border-radius:8px; padding:10px; }
        .meta-title { font-weight:600; margin-bottom:6px; }

        /* Items table */
        table.items { width:100%; border-collapse:collapse; margin-top:12px; }
        .items thead th {
            font-size:11px; text-transform:uppercase; letter-spacing:.04em;
            color:#6b7280; border-bottom:1px solid #e5e7eb; padding:8px; text-align:left;
        }
        .items tbody td { padding:8px; border-bottom:1px solid #f3f4f6; }
        .items tfoot td { padding:6px 8px; }

        /* Totals block (separate table to avoid border conflicts) */
        .totals { width:320px; margin-left:auto; border-collapse:collapse; margin-top:12px; }
        .totals td { padding:6px 8px; }
        .totals .muted { color:#6b7280; }
        .totals .line { border-top:1px solid #e5e7eb; height:1px; }

        /* Footer */
        .footer { margin-top:28px; border-top:1px solid #e5e7eb; padding-top:8px; color:#6b7280; font-size:11px; }
    </style>
</head>
<body>

    {{-- HEADER --}}
    <table class="header-table">
        <tr>
            <td>
                <div class="brand">{{ config('app.name', 'Stock Manager') }}</div>
                <div class="small muted">Purchase Invoice</div>
            </td>
            <td class="right">
                <div style="font-size:22px; font-weight:700; margin:0;">#{{ $purchase->id }}</div>
                <div class="small muted">{{ $date ? Carbon::parse($date)->format('M j, Y g:i A') : '—' }}</div>
                <div style="margin-top:6px;">
                    <span class="badge">Method: {{ strtoupper($purchase->method ?? 'cash') }}</span>
                </div>
            </td>
        </tr>
    </table>

    {{-- SUPPLIER / PURCHASE META --}}
    <table class="meta-table">
        <tr>
            <td>
                <div class="meta-box">
                    <div class="meta-title muted small">Supplier</div>
                    <div><strong>{{ $supplier->name ?? '—' }}</strong></div>
                    @if(!empty($supplier?->email))<div class="small muted">{{ $supplier->email }}</div>@endif
                    @if(!empty($supplier?->phone))<div class="small muted">{{ $supplier->phone }}</div>@endif
                    @if(!empty($supplier?->address))<div class="small muted">{{ $supplier->address }}</div>@endif
                </div>
            </td>
            <td>
                <div class="meta-box">
                    <div class="meta-title muted small">Purchase</div>
                    <div class="mb-2"><strong>Status:</strong> {{ ucfirst($purchase->status ?? 'completed') }}</div>
                    <div class="mb-2"><strong>Reference:</strong> #{{ $purchase->id }}</div>
                    @if(!empty($purchase->reference))
                        <div class="mb-2"><strong>External Ref:</strong> {{ $purchase->reference }}</div>
                    @endif
                </div>
            </td>
        </tr>
    </table>

    {{-- ITEMS --}}
    <table class="items">
        <thead>
            <tr>
                <th>Product</th>
                <th class="right">Qty</th>
                <th class="right">Unit Cost</th>
                <th class="right">Line Total</th>
            </tr>
        </thead>
        <tbody>
            @forelse($purchase->items as $item)
                @php
                    $qty = (float)$item->quantity;
                    $uc  = (float)$item->unit_cost;
                    $lt  = $item->total_cost ?? ($qty * $uc);
                @endphp
                <tr>
                    <td>{{ optional($item->product)->name ?? ('#'.$item->product_id) }}</td>
                    <td class="right">{{ number_format($qty, 2) }}</td>
                    <td class="right">RWF {{ $fmt($uc) }}</td>
                    <td class="right">RWF {{ $fmt($lt) }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="4" class="center muted">No items.</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    {{-- TOTALS --}}
    <table class="totals">
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
            <td><div class="line"></div></td>
            <td></td>
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

    {{-- FOOTER --}}
    <div class="footer small">
        Generated by {{ config('app.name', 'Stock Manager') }} – {{ now()->format('M j, Y g:i A') }}
    </div>

</body>
</html>
