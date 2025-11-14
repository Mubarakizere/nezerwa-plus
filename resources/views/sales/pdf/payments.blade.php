{{-- resources/views/sales/pdf/payments.blade.php --}}
@php
    /** @var \Illuminate\Support\Carbon $start */
    /** @var \Illuminate\Support\Carbon $end */
    /** @var \Illuminate\Support\Collection|\Illuminate\Support\Enumerable $saleLines */
    /** @var \Illuminate\Support\Collection|\Illuminate\Support\Enumerable $totalsByMethod */

    $fmt = fn($n) => number_format((float)$n, 2);

    // Ensure we have numeric totals for each method
    $methods = ['cash','bank','momo','mobile'];
    $methodTotals = [];
    foreach ($methods as $m) {
        $methodTotals[$m] = (float) ($totalsByMethod[$m] ?? 0);
    }

    $grandTotal   = (float) ($saleLines->sum('total_amount') ?? 0);
    $grandPaid    = (float) ($saleLines->sum('paid') ?? 0);
    $grandBalance = (float) ($saleLines->sum('balance') ?? 0);

    $rangeText = $start->toDateString() === $end->toDateString()
        ? $start->toDateString()
        : ($start->toDateString() . ' — ' . $end->toDateString());
@endphp
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Sales Payments Report ({{ $rangeText }})</title>
    <style>
        /* DomPDF-friendly CSS (keep it simple) */
        @page { margin: 22mm 16mm; }
        body { font-family: DejaVu Sans, Arial, Helvetica, sans-serif; font-size: 12px; color: #111; }
        h1, h2, h3 { margin: 0 0 6px; }
        .muted { color: #666; }
        .mb-2 { margin-bottom: 8px; }
        .mb-3 { margin-bottom: 12px; }
        .mb-4 { margin-bottom: 16px; }
        .mt-2 { margin-top: 8px; }
        .right { text-align: right; }
        .center { text-align: center; }
        .small { font-size: 11px; }
        .tiny { font-size: 10px; }

        .summary {
            width: 100%;
            border: 1px solid #ddd;
            border-radius: 6px;
            padding: 8px 10px;
        }

        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #ddd; padding: 6px 8px; }
        th { background: #f3f3f3; font-weight: 600; }
        tfoot td { font-weight: 700; background: #fafafa; }

        .chip {
            display: inline-block; padding: 2px 6px; border-radius: 12px;
            background: #eef2ff; color: #1f2a44; font-size: 10px;
        }
        .table-clean th, .table-clean td { border: none; padding: 2px 0; }
        .divider { height: 1px; background: #ddd; margin: 8px 0; }
    </style>
</head>
<body>

    {{-- Header --}}
    <table class="table-clean">
        <tr>
            <td>
                <h1>Sales Payments Report</h1>
                <div class="muted">Period: <strong>{{ $rangeText }}</strong></div>
            </td>
            <td class="right small muted">
                Generated: {{ now()->format('Y-m-d H:i') }}
            </td>
        </tr>
    </table>

    <div class="divider"></div>

    {{-- Totals Summary --}}
    <table class="summary mb-4" cellspacing="0" cellpadding="0">
        <tr>
            <td>
                <strong>Total Sales (Gross):</strong>
            </td>
            <td class="right">
                {{ $fmt($grandTotal) }}
            </td>
        </tr>
        <tr>
            <td>
                <strong>Total Paid (All Methods):</strong>
            </td>
            <td class="right">
                {{ $fmt($grandPaid) }}
            </td>
        </tr>
        <tr>
            <td>
                <strong>Outstanding Balance:</strong>
            </td>
            <td class="right">
                {{ $fmt($grandBalance) }}
            </td>
        </tr>
    </table>

    {{-- Method Breakdown --}}
    <h3 class="mb-2">Breakdown by Payment Method</h3>
    <table class="mb-4">
        <thead>
            <tr>
                <th class="left">Method</th>
                <th class="right">Amount</th>
                <th class="right">Share</th>
            </tr>
        </thead>
        <tbody>
            @php $paidTotal = max(0.0, $methodTotals['cash'] + $methodTotals['bank'] + $methodTotals['momo'] + $methodTotals['mobile']); @endphp
            @foreach ($methods as $m)
                @php
                    $amt = $methodTotals[$m];
                    $pct = $paidTotal > 0 ? round(($amt / $paidTotal) * 100, 1) : 0;
                    $label = strtoupper($m);
                @endphp
                <tr>
                    <td>{{ $label }}</td>
                    <td class="right">{{ $fmt($amt) }}</td>
                    <td class="right">{{ $pct }}%</td>
                </tr>
            @endforeach
        </tbody>
        <tfoot>
            <tr>
                <td>Total Paid</td>
                <td class="right">{{ $fmt($paidTotal) }}</td>
                <td class="right">100%</td>
            </tr>
        </tfoot>
    </table>

    {{-- Details --}}
    <h3 class="mb-2">Per-Sale Details</h3>
    @if($saleLines->isEmpty())
        <p class="muted">No payments in this period.</p>
    @else
        <table>
            <thead>
                <tr>
                    <th>Sale #</th>
                    <th>Date</th>
                    <th>Customer</th>
                    <th class="right">Total</th>
                    <th class="right">Cash</th>
                    <th class="right">Bank</th>
                    <th class="right">MoMo</th>
                    <th class="right">Mobile</th>
                    <th class="right">Paid</th>
                    <th class="right">Balance</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($saleLines as $r)
                    <tr>
                        <td>#{{ $r['sale_id'] }}</td>
                        <td>{{ \Illuminate\Support\Carbon::parse($r['sale_date'])->toDateString() }}</td>
                        <td>
                            {{ $r['customer_name'] ?? 'Walk-in' }}
                            @if(($r['paid'] ?? 0) && ($r['paid'] ?? 0) < ($r['total_amount'] ?? 0))
                                <span class="chip">Partially Paid</span>
                            @endif
                        </td>
                        <td class="right">{{ $fmt($r['total_amount'] ?? 0) }}</td>
                        <td class="right">{{ $fmt($r['cash']   ?? 0) }}</td>
                        <td class="right">{{ $fmt($r['bank']   ?? 0) }}</td>
                        <td class="right">{{ $fmt($r['momo']   ?? 0) }}</td>
                        <td class="right">{{ $fmt($r['mobile'] ?? 0) }}</td>
                        <td class="right">{{ $fmt($r['paid']   ?? 0) }}</td>
                        <td class="right">{{ $fmt($r['balance']?? 0) }}</td>
                    </tr>
                @endforeach
            </tbody>
            <tfoot>
                <tr>
                    <td colspan="3">Totals</td>
                    <td class="right">{{ $fmt($grandTotal) }}</td>
                    <td class="right">{{ $fmt($methodTotals['cash']) }}</td>
                    <td class="right">{{ $fmt($methodTotals['bank']) }}</td>
                    <td class="right">{{ $fmt($methodTotals['momo']) }}</td>
                    <td class="right">{{ $fmt($methodTotals['mobile']) }}</td>
                    <td class="right">{{ $fmt($grandPaid) }}</td>
                    <td class="right">{{ $fmt($grandBalance) }}</td>
                </tr>
            </tfoot>
        </table>
    @endif

    <p class="tiny muted mt-2">
        * Paid columns reflect split payments recorded per sale within the selected period (based on <em>sale date</em>).
    </p>
</body>
</html>
