<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Stock Movement Report</title>
    <style>
        /* ===== Base Page ===== */
        @page { margin: 25px 25px 35px 25px; }

        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 11px;
            color: #111;
        }

        h2 {
            text-align: center;
            font-size: 16px;
            margin-bottom: 4px;
            color: #1f2937;
        }

        p {
            text-align: center;
            color: #555;
            font-size: 10px;
            margin: 0 0 10px 0;
        }

        /* ===== Pills (breakdown) ===== */
        .wrap { display: flex; gap: 6px; flex-wrap: wrap; justify-content: center; margin: 6px 0 2px; }
        .pill { font-size: 9.5px; padding: 2px 6px; border-radius: 999px; font-weight: 600; border: 1px solid transparent; }
        .pill-rose    { background:#fee2e2; color:#991b1b; border-color:#fecaca; }   /* Out: Sales */
        .pill-amber   { background:#fef3c7; color:#92400e; border-color:#fde68a; }   /* Out: Return to Supplier */
        .pill-emerald { background:#dcfce7; color:#166534; border-color:#bbf7d0; }   /* In: Purchases */
        .pill-sky     { background:#e0f2fe; color:#075985; border-color:#bae6fd; }   /* In: Customer Return */
        .pill-indigo  { background:#e0e7ff; color:#3730a3; border-color:#c7d2fe; }   /* Out: Loans */
        .pill-purple  { background:#f3e8ff; color:#6b21a8; border-color:#e9d5ff; }   /* In: Loan Returns */

        /* ===== Table ===== */
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
            table-layout: fixed;
            word-wrap: break-word;
        }

        th, td {
            border: 1px solid #e5e7eb;
            padding: 5px 6px;
        }

        th {
            background: #f3f4f6;
            color: #111827;
            font-size: 10.5px;
            text-transform: uppercase;
            letter-spacing: 0.4px;
        }

        td { font-size: 10.5px; }
        tr:nth-child(even) { background: #fafafa; }

        .text-right { text-align: right; }
        .text-center { text-align: center; }

        /* ===== Badges (IN/OUT) ===== */
        .badge-in {
            background: #dcfce7;
            color: #166534;
            font-weight: 600;
            padding: 2px 6px;
            border-radius: 4px;
            font-size: 9.5px;
        }
        .badge-out {
            background: #fee2e2;
            color: #b91c1c;
            font-weight: 600;
            padding: 2px 6px;
            border-radius: 4px;
            font-size: 9.5px;
        }

        /* ===== Totals ===== */
        tfoot td {
            font-weight: 600;
            background: #f9fafb;
            border-top: 2px solid #d1d5db;
            font-size: 10.5px;
        }

        /* ===== Column widths (9 cols) ===== */
        th:nth-child(1) { width: 11%; }  /* Date */
        th:nth-child(2) { width: 17%; }  /* Product */
        th:nth-child(3) { width: 7%; }   /* Type */
        th:nth-child(4) { width: 7%; }   /* Qty */
        th:nth-child(5) { width: 8%; }   /* Unit */
        th:nth-child(6) { width: 8%; }   /* Total */
        th:nth-child(7) { width: 17%; }  /* Reference / Note */
        th:nth-child(8) { width: 12%; }  /* Recorded By */
        th:nth-child(9) { width: 13%; }  /* Source */

        /* ===== Footer ===== */
        .footer {
            text-align: right;
            font-size: 9.5px;
            color: #555;
            margin-top: 8px;
            border-top: 1px solid #ddd;
            padding-top: 4px;
        }
    </style>
</head>
<body>

    {{-- 🔹 Header --}}
    <h2>Stock Movement Report</h2>
    <p>Generated on {{ now()->format('d M Y, H:i') }}</p>

    {{-- 🔹 Breakdown --}}
    @php
        $fmt = fn($n) => number_format((float)($n ?? 0), 2);
        $b = $breakdown ?? [];
    @endphp
    <div class="wrap">
        <span class="pill pill-rose">Sales OUT: {{ $fmt($b['out_sales'] ?? 0) }}</span>
        <span class="pill pill-amber">Returns to Supplier: {{ $fmt($b['out_returns'] ?? 0) }}</span>
        <span class="pill pill-emerald">Purchases IN: {{ $fmt($b['in_purchases'] ?? 0) }}</span>
        <span class="pill pill-sky">Customer Return (IN): {{ $fmt($b['in_sale_returns'] ?? 0) }}</span>
        <span class="pill pill-indigo">Loans OUT: {{ $fmt($b['out_loans'] ?? 0) }}</span>
        <span class="pill pill-purple">Loan Returns (IN): {{ $fmt($b['in_loan_returns'] ?? 0) }}</span>
    </div>

    {{-- 🔸 Table --}}
    <table>
        <thead>
            <tr>
                <th>Date</th>
                <th>Product</th>
                <th>Type</th>
                <th class="text-right">Qty</th>
                <th class="text-right">Unit Cost</th>
                <th class="text-right">Total Cost</th>
                <th>Reference / Note</th>
                <th>Recorded By</th>
                <th>Source</th>
            </tr>
        </thead>

        <tbody>
            @php $totalIn = 0; $totalOut = 0; @endphp

            @foreach($movements as $m)
                @php
                    $isIn = ($m->type ?? '') === 'in';
                    if ($isIn) { $totalIn += (float)($m->quantity ?? 0); }
                    else       { $totalOut += (float)($m->quantity ?? 0); }

                    // Reference / Note: movement first, then source fallbacks
                    $reference = $m->reference
                        ?? $m->note
                        ?? optional($m->source)->reference
                        ?? optional($m->source)->note
                        ?? optional($m->source)->remarks
                        ?? optional($m->source)->return_reason
                        ?? null;

                    // Source label (4 origins)
                    $source = 'N/A';
                    if (($m->source_type ?? null) === \App\Models\Purchase::class) {
                        $source = 'Purchase #'.($m->source_id ?? '');
                    } elseif (($m->source_type ?? null) === \App\Models\Sale::class) {
                        $source = 'Sale #'.($m->source_id ?? '');
                    } elseif (($m->source_type ?? null) === \App\Models\PurchaseReturn::class) {
                        $pid = optional($m->source)->purchase_id ?? null;
                        $source = 'Return to Supplier #'.($m->source_id ?? '').($pid ? " (Purchase #$pid)" : '');
                    } elseif (($m->source_type ?? null) === \App\Models\SaleReturn::class) {
                        $sid = optional($m->source)->sale_id ?? null;
                        $source = 'Customer Return #'.($m->source_id ?? '').($sid ? " (Sale #$sid)" : '');
                    } elseif (($m->source_type ?? null) === \App\Models\ItemLoan::class) {
                        $source = 'Item Loan #'.($m->source_id ?? '');
                    } elseif (($m->source_type ?? null) === \App\Models\ItemLoanReturn::class) {
                        $lid = optional($m->source)->item_loan_id ?? null;
                        $source = 'Loan Return #'.($m->source_id ?? '').($lid ? " (Loan #$lid)" : '');
                    }
                @endphp

                <tr>
                    <td>{{ optional($m->created_at)->format('d M Y, H:i') }}</td>
                    <td>{{ optional($m->product)->name }}</td>
                    <td class="text-center">
                        @if($isIn)
                            <span class="badge-in">IN</span>
                        @else
                            <span class="badge-out">OUT</span>
                        @endif
                    </td>
                    <td class="text-right">{{ number_format((float)($m->quantity ?? 0), 2) }}</td>
                    <td class="text-right">{{ isset($m->unit_cost) ? number_format((float)$m->unit_cost, 2) : '—' }}</td>
                    <td class="text-right">{{ isset($m->total_cost) ? number_format((float)$m->total_cost, 2) : '—' }}</td>
                    <td>
                        @if($reference)
                            {{ $reference }}
                        @else
                            —
                        @endif
                    </td>
                    <td>{{ optional($m->user)->name ?? 'System' }}</td>
                    <td>{{ $source }}</td>
                </tr>
            @endforeach
        </tbody>

        {{-- 🔹 Summary --}}
        @php $net = $totalIn - $totalOut; @endphp
        <tfoot>
            <tr>
                <td colspan="3" class="text-right">Total In:</td>
                <td class="text-right">{{ number_format($totalIn, 2) }}</td>
                <td colspan="2" class="text-right">Total Out:</td>
                <td colspan="3" class="text-right">{{ number_format($totalOut, 2) }}</td>
            </tr>
            <tr>
                <td colspan="8" class="text-right">Net Movement:</td>
                <td class="text-right" style="color: {{ $net >= 0 ? '#166534' : '#b91c1c' }}">
                    {{ number_format($net, 2) }}
                </td>
            </tr>
        </tfoot>
    </table>

    {{-- 🔸 Footer --}}
    <div class="footer">
        {{ config('app.name') }}
    </div>

</body>
</html>
