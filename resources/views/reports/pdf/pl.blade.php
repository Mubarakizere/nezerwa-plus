<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Profit &amp; Loss Statement</title>
    <style>
        @page { margin: 24px 26px 34px 26px; }
        body { font-family: DejaVu Sans, sans-serif; color:#111827; font-size:11px; }
        h1 { font-size:18px; margin:0 0 4px; letter-spacing:.3px; text-transform:uppercase; }
        .sub { color:#6b7280; font-size:10px; margin-bottom:14px; }
        .bar { height:2px; background:#4f46e5; margin:8px 0 14px; }

        table { width:100%; border-collapse:collapse; margin-top:8px; }
        th, td { border:1px solid #e5e7eb; padding:7px 8px; text-align:left; vertical-align:top; }
        th { background:#f9fafb; font-size:10px; text-transform:uppercase; color:#374151; }
        .right { text-align:right; }
        .strong { font-weight:700; }
        .muted { color:#6b7280; }
        .green { color:#16a34a; } .red { color:#dc2626; } .amber { color:#d97706; }

        .note { margin-top:10px; font-size:10px; color:#6b7280; }
        .footer { margin-top:12px; padding-top:6px; border-top:1px solid #e5e7eb; color:#6b7280; font-size:9.5px; }
        .pill { border:1px solid #e5e7eb; border-radius:999px; padding:1px 6px; font-size:10px; }
    </style>
</head>
<body>
@php
    $fmt = function($n){ return number_format((float)$n, 2); };
    $revenue        = (float)($plRevenue ?? 0);
    $cogs           = (float)($plCogs ?? 0);
    $gross          = (float)($plGrossProfit ?? ($revenue - $cogs));
    $otherExpenses  = (float)($plOtherExpenses ?? 0);
    $net            = (float)($plNetProfit ?? ($gross - $otherExpenses));
@endphp

<h1>Profit &amp; Loss Statement</h1>
<div class="sub">
    Period: <strong>{{ $start->toDateString() }}</strong> &rarr; <strong>{{ $end->toDateString() }}</strong>
    <span class="pill" style="margin-left:6px;">RWF</span>
</div>
<div class="bar"></div>

<table>
    <tbody>
        <tr>
            <td class="strong">Revenue</td>
            <td class="right strong">RWF {{ $fmt($revenue) }}</td>
        </tr>
        <tr>
            <td>Cost of Goods Sold (COGS)</td>
            <td class="right">RWF {{ $fmt($cogs) }}</td>
        </tr>
        <tr>
            <td class="strong">Gross Profit</td>
            <td class="right strong">RWF {{ $fmt($gross) }}</td>
        </tr>
        <tr>
            <td>Operating Expenses (Other Debits w/o transaction)</td>
            <td class="right">RWF {{ $fmt($otherExpenses) }}</td>
        </tr>
        <tr>
            <td class="strong">Net Profit</td>
            <td class="right strong {{ $net>=0 ? 'green' : 'red' }}">RWF {{ $fmt($net) }}</td>
        </tr>
    </tbody>
</table>

<div class="note">
    <div>• <strong>Revenue</strong> = Sum of sale items (subtotal) in period.</div>
    <div>• <strong>COGS</strong> = Revenue − Profit (from sale items).</div>
    <div>• <strong>Operating Expenses</strong> = DebitCredits (type=debit) with <em>transaction_id = NULL</em> in period.</div>
</div>

<div class="footer">
    Generated on {{ now()->format('d M Y, H:i') }} — Period: {{ $start->toDateString() }} to {{ $end->toDateString() }}
</div>
</body>
</html>
