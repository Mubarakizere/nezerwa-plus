<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Finance Report</title>
    <style>
        @page { margin: 24px 26px 34px 26px; }
        * { box-sizing: border-box; }
        body { font-family: DejaVu Sans, sans-serif; color:#1f2937; font-size:11px; }
        h1 { font-size:18px; margin:0 0 4px; letter-spacing:.3px; text-transform:uppercase; }
        .sub { color:#6b7280; font-size:10px; margin-bottom:14px; }
        .bar { height:2px; background:#4f46e5; margin:8px 0 14px; }

        .grid { display:flex; flex-wrap:wrap; margin:-6px; }
        .col { padding:6px; }
        .col-3 { width:25%; }
        .card { border:1px solid #e5e7eb; border-radius:8px; padding:10px; background:#fff; }
        .card h3 { margin:0 0 6px; font-size:10px; color:#6b7280; font-weight:600; text-transform:uppercase; }
        .card .v { font-size:14px; font-weight:700; }
        .green { color:#16a34a; } .red { color:#dc2626; } .indigo { color:#4f46e5; } .amber { color:#d97706; }

        table { width:100%; border-collapse:collapse; margin-top:10px; }
        th, td { border:1px solid #e5e7eb; padding:6px 7px; text-align:left; vertical-align:top; }
        th { background:#f9fafb; font-size:10px; text-transform:uppercase; color:#374151; }
        tr:nth-child(even) td { background:#fcfcfd; }

        .section { margin-top:16px; page-break-inside: avoid; }
        .muted { color:#6b7280; }
        .right { text-align:right; }
        .footer { margin-top:12px; padding-top:6px; border-top:1px solid #e5e7eb; color:#6b7280; font-size:9.5px; }
        .tag { display:inline-block; padding:2px 6px; font-size:10px; border-radius:999px; border:1px solid #e5e7eb; color:#374151; }
    </style>
</head>
<body>
@php
    $fmt = function($n){ return number_format((float)$n, 2); };
    $p  = fn($n) => number_format((float)$n, 1).'%';
@endphp

<h1>Finance Report</h1>
<div class="sub">
    Period: <strong>{{ $start->toDateString() }}</strong> &rarr; <strong>{{ $end->toDateString() }}</strong>
    <span class="tag" style="margin-left:6px;">RWF</span>
</div>
<div class="bar"></div>

{{-- Summary Cards --}}
<div class="grid">
    <div class="col col-3">
        <div class="card">
            <h3>Total Credits</h3>
            <div class="v green">RWF {{ $fmt($credits ?? 0) }}</div>
        </div>
    </div>
    <div class="col col-3">
        <div class="card">
            <h3>Total Debits</h3>
            <div class="v red">RWF {{ $fmt($debits ?? 0) }}</div>
        </div>
    </div>
    <div class="col col-3">
        <div class="card">
            <h3>Net Balance</h3>
            @php $nb = (float)($netBalance ?? 0); @endphp
            <div class="v {{ $nb>=0 ? 'green' : 'red' }}">RWF {{ $fmt($nb) }}</div>
        </div>
    </div>
    <div class="col col-3">
        <div class="card">
            <h3>Total Profit</h3>
            <div class="v indigo">RWF {{ $fmt($totalProfit ?? 0) }}</div>
        </div>
    </div>
</div>

<div class="grid" style="margin-top:6px;">
    <div class="col col-3">
        <div class="card">
            <h3>Profit Margin</h3>
            <div class="v">{{ $p($profitMargin ?? 0) }}</div>
        </div>
    </div>
    <div class="col col-3">
        <div class="card">
            <h3>Expense Ratio</h3>
            <div class="v">{{ $p($expenseRatio ?? 0) }}</div>
        </div>
    </div>
    <div class="col col-3">
        <div class="card">
            <h3>Revenue Growth</h3>
            @php $rg = (float)($revenueGrowth ?? 0); @endphp
            <div class="v {{ $rg>=0 ? 'green':'red' }}">{{ $p($rg) }}</div>
        </div>
    </div>
</div>

{{-- Category Breakdown --}}
<div class="section">
    <h3 style="margin:0 0 6px; font-size:12px;">Category Breakdown</h3>
    <table>
        <thead>
        <tr>
            <th>Category</th>
            <th class="right">Credits (In)</th>
            <th class="right">Debits (Out)</th>
            <th class="right">Net</th>
            <th>Notes</th>
        </tr>
        </thead>
        <tbody>
        @php
            $rows = [
                ['Sales',      (float)($salesCredits ?? 0),   (float)($salesDebits ?? 0),    'Revenue from sales'],
                ['Purchases',  (float)($purchaseCredits ?? 0),(float)($purchaseDebits ?? 0), 'Inventory/COGS purchasing'],
                ['Loans',      (float)($loanCredits ?? 0),    (float)($loanDebits ?? 0),     'Loans taken/given'],
                ['Other',      (float)($otherCredits ?? 0),   (float)($otherDebits ?? 0),    'Misc. entries'],
            ];
            $tCred = 0; $tDeb = 0;
        @endphp

        @foreach($rows as $r)
            @php [$label,$c,$d,$note] = $r; $tCred+=$c; $tDeb+=$d; $net=$c-$d; @endphp
            <tr>
                <td><strong>{{ $label }}</strong></td>
                <td class="right green">RWF {{ $fmt($c) }}</td>
                <td class="right red">RWF {{ $fmt($d) }}</td>
                <td class="right {{ $net>=0?'green':'red' }}">RWF {{ $fmt($net) }}</td>
                <td class="muted">{{ $note }}</td>
            </tr>
        @endforeach

        @php $totalNet = $tCred - $tDeb; @endphp
        <tr>
            <td style="background:#f9fafb;"><strong>Totals</strong></td>
            <td class="right" style="background:#f9fafb;"><strong>RWF {{ $fmt($tCred) }}</strong></td>
            <td class="right" style="background:#f9fafb;"><strong>RWF {{ $fmt($tDeb) }}</strong></td>
            <td class="right" style="background:#f9fafb;">
                <strong class="{{ $totalNet>=0?'green':'red' }}">RWF {{ $fmt($totalNet) }}</strong>
            </td>
            <td style="background:#f9fafb;" class="muted">Sum of categories shown above</td>
        </tr>
        </tbody>
    </table>
</div>

<div class="footer">
    Generated on {{ now()->format('d M Y, H:i') }} — Period: {{ $start->toDateString() }} to {{ $end->toDateString() }}
</div>
</body>
</html>
