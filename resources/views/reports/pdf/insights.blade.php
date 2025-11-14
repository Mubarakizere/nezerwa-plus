<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Business Insights Report</title>
    <style>
        @page { margin: 24px 26px 34px 26px; }
        body { font-family: DejaVu Sans, sans-serif; color:#111827; font-size:11px; }
        h1 { font-size:18px; margin:0 0 4px; letter-spacing:.3px; text-transform:uppercase; }
        .sub { color:#6b7280; font-size:10px; margin-bottom:14px; }
        .bar { height:2px; background:#4f46e5; margin:8px 0 14px; }

        .grid { display:flex; flex-wrap:wrap; margin:-6px; }
        .col { padding:6px; }
        .col-4 { width:25%; } .col-3 { width:33.3333%; } .col-6 { width:50%; }
        .card { border:1px solid #e5e7eb; border-radius:8px; padding:10px; background:#fff; }
        .card h3 { margin:0 0 6px; font-size:10px; color:#6b7280; font-weight:600; text-transform:uppercase; }
        .card .v { font-size:14px; font-weight:700; }
        .green { color:#16a34a; } .red { color:#dc2626; } .indigo { color:#4f46e5; } .amber { color:#d97706; }

        table { width:100%; border-collapse:collapse; margin-top:10px; }
        th, td { border:1px solid #e5e7eb; padding:6px 7px; text-align:left; vertical-align:top; }
        th { background:#f9fafb; font-size:10px; text-transform:uppercase; color:#374151; }
        tr:nth-child(even) td { background:#fcfcfd; }

        .section { margin-top:16px; page-break-inside: avoid; }
        .footer { margin-top:12px; padding-top:6px; border-top:1px solid #e5e7eb; color:#6b7280; font-size:9.5px; }

        .muted { color:#6b7280; }
        .right { text-align:right; }
    </style>
</head>
<body>
@php
    $fmt = function($n){ return number_format((float)$n, 2); };
    $p   = fn($n)=> number_format((float)$n,1).'%';
@endphp

<h1>Business Insights</h1>
<div class="sub">
    Period: <strong>{{ $start->toDateString() }}</strong> &rarr; <strong>{{ $end->toDateString() }}</strong>
    <span style="margin-left:6px; border:1px solid #e5e7eb; border-radius:999px; padding:1px 6px; font-size:10px;">RWF</span>
</div>
<div class="bar"></div>

{{-- Summary --}}
<div class="grid">
    <div class="col col-4"><div class="card"><h3>Total Sales</h3><div class="v indigo">RWF {{ $fmt($totalSales ?? 0) }}</div></div></div>
    <div class="col col-4"><div class="card"><h3>Total Profit</h3><div class="v green">RWF {{ $fmt($totalProfit ?? 0) }}</div></div></div>
    <div class="col col-4"><div class="card"><h3>Total Purchases</h3><div class="v amber">RWF {{ $fmt($totalPurchases ?? 0) }}</div></div></div>
</div>
<div class="grid">
    <div class="col col-4"><div class="card"><h3>Credits (In)</h3><div class="v green">RWF {{ $fmt($credits ?? 0) }}</div></div></div>
    <div class="col col-4"><div class="card"><h3>Debits (Out)</h3><div class="v red">RWF {{ $fmt($debits ?? 0) }}</div></div></div>
    @php $nb = (float)($netBalance ?? 0); @endphp
    <div class="col col-4"><div class="card"><h3>Net Balance</h3><div class="v {{ $nb>=0 ? 'green' : 'red' }}">RWF {{ $fmt($nb) }}</div></div></div>
</div>
<div class="grid">
    <div class="col col-3"><div class="card"><h3>Profit Margin</h3><div class="v">{{ $p($profitMargin ?? 0) }}</div></div></div>
    <div class="col col-3"><div class="card"><h3>Expense Ratio</h3><div class="v">{{ $p($expenseRatio ?? 0) }}</div></div></div>
    <div class="col col-6">
        <div class="card">
            <h3>Notes</h3>
            <div class="muted">Margin = Profit / Sales. Expense Ratio ≈ (Purchases + Debits) / Sales.</div>
        </div>
    </div>
</div>

{{-- Top 5 Products --}}
<div class="section">
    <h3 style="margin:0 0 6px; font-size:12px;">Top 5 Products</h3>
    <table>
        <thead><tr><th>#</th><th>Product</th><th class="right">Total Sales (RWF)</th></tr></thead>
        <tbody>
        @php $i=1; @endphp
        @forelse(($topProducts ?? []) as $row)
            <tr>
                <td style="width:28px;">{{ $i++ }}</td>
                <td>{{ $row->product->name ?? '—' }}</td>
                <td class="right">{{ $fmt($row->total_sales ?? 0) }}</td>
            </tr>
        @empty
            <tr><td colspan="3" class="muted">No product data in this period.</td></tr>
        @endforelse
        </tbody>
    </table>
</div>

{{-- Top 5 Customers --}}
<div class="section">
    <h3 style="margin:0 0 6px; font-size:12px;">Top 5 Customers</h3>
    <table>
        <thead><tr><th>#</th><th>Customer</th><th class="right">Total Spent (RWF)</th></tr></thead>
        <tbody>
        @php $i=1; @endphp
        @forelse(($topCustomers ?? []) as $row)
            <tr>
                <td style="width:28px;">{{ $i++ }}</td>
                <td>{{ $row->customer->name ?? '—' }}</td>
                <td class="right">{{ $fmt($row->total_spent ?? 0) }}</td>
            </tr>
        @empty
            <tr><td colspan="3" class="muted">No customer data in this period.</td></tr>
        @endforelse
        </tbody>
    </table>
</div>

<div class="footer">
    Generated on {{ now()->format('d M Y, H:i') }} — Period: {{ $start->toDateString() }} to {{ $end->toDateString() }}
</div>
</body>
</html>
