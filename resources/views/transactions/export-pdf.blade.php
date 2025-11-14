<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Transactions Report</title>
    <style>
        /* ===== PAGE ===== */
        @page { margin: 25px 25px 38px 25px; } /* extra bottom for page number */

        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 11px;
            color: #222;
        }

        /* ===== HEADER ===== */
        h2 {
            text-align: center;
            font-size: 16px;
            font-weight: 700;
            letter-spacing: .3px;
            margin: 0 0 12px 0;
            padding-bottom: 6px;
            border-bottom: 1px solid #4f46e5;
        }

        /* Applied filters block */
        .filters {
            margin: 0 0 10px 0;
            padding: 6px 8px;
            border: 1px solid #e5e7eb;
            background: #fafafa;
            font-size: 10.5px;
        }
        .filters strong { color:#374151; }

        /* ===== SUMMARY ===== */
        .summary {
            width: 100%;
            margin: 8px 0 14px 0;
        }
        .summary td {
            font-size: 11px;
            padding: 3px 8px;
            vertical-align: top;
        }
        .summary strong {
            display: inline-block;
            min-width: 110px;
        }

        /* ===== TABLE ===== */
        table {
            width: 100%;
            border-collapse: collapse;
            table-layout: fixed;       /* prevents overflow */
            word-wrap: break-word;
        }
        thead { display: table-header-group; } /* repeat header on each page */
        tfoot { display: table-footer-group; }

        th, td {
            border: 1px solid #d1d5db;
            padding: 5px 6px;
            text-align: left;
        }
        th {
            background: #f3f4f6;
            font-weight: 600;
            font-size: 10.5px;
            color: #374151;
        }
        td { font-size: 10.5px; }
        tr:nth-child(even) td { background: #fbfbfb; }

        /* Column widths */
        th:nth-child(1) { width: 12%; } /* Date */
        th:nth-child(2) { width: 9%;  } /* Type */
        th:nth-child(3) { width: 12%; } /* Amount */
        th:nth-child(4) { width: 10%; } /* Method */
        th:nth-child(5) { width: 12%; } /* User */
        th:nth-child(6) { width: 15%; } /* Customer */
        th:nth-child(7) { width: 15%; } /* Supplier */
        th:nth-child(8) { width: 15%; } /* Notes */

        /* Numeric alignment */
        .ta-right { text-align: right; white-space: nowrap; }

        /* Type chip */
        .chip {
            display: inline-block;
            padding: 1px 6px;
            border-radius: 10px;
            font-size: 10px;
            font-weight: 600;
        }
        .chip.credit { color:#065f46; background:#d1fae5; }
        .chip.debit  { color:#991b1b; background:#fee2e2; }

        /* ===== FOOTER ===== */
        .footer {
            margin-top: 10px;
            text-align: right;
            font-size: 9.5px;
            color: #666;
            border-top: 1px solid #e5e7eb;
            padding-top: 4px;
        }
    </style>
</head>
<body>
    <h2>TRANSACTIONS REPORT</h2>

    {{-- Filters applied (if any) --}}
    @php
        $hasFilters = request('type') || request('method') || request('date_from') || request('date_to')
            || request('customer_id') || request('supplier_id') || request('q');
    @endphp
    @if($hasFilters)
        <div class="filters">
            <strong>Filters:</strong>
            @if(request('type')) Type=<em>{{ ucfirst(request('type')) }}</em>; @endif
            @if(request('method')) Method=<em>{{ request('method') }}</em>; @endif
            @if(request('date_from')) From=<em>{{ request('date_from') }}</em>; @endif
            @if(request('date_to')) To=<em>{{ request('date_to') }}</em>; @endif
            @if(request('customer_id')) Customer ID=<em>{{ request('customer_id') }}</em>; @endif
            @if(request('supplier_id')) Supplier ID=<em>{{ request('supplier_id') }}</em>; @endif
            @if(request('q')) Search="<em>{{ request('q') }}</em>"; @endif
        </div>
    @endif

    {{-- Summary --}}
    @php
        $totalCredits = $transactions->where('type', 'credit')->sum('amount');
        $totalDebits  = $transactions->where('type', 'debit')->sum('amount');
        $net          = $totalCredits - $totalDebits;
        $fmt = fn($n) => number_format((float)$n, 2);
    @endphp

    <table class="summary">
        <tr>
            <td><strong>Total Credits:</strong></td>
            <td style="color:#065f46;">{{ $fmt($totalCredits) }} RWF</td>
            <td><strong>Total Debits:</strong></td>
            <td style="color:#991b1b;">{{ $fmt($totalDebits) }} RWF</td>
            <td><strong>Net Balance:</strong></td>
            <td style="color: {{ $net >= 0 ? '#065f46' : '#991b1b' }};">{{ $fmt($net) }} RWF</td>
        </tr>
    </table>

    {{-- Transactions --}}
    <table>
        <thead>
            <tr>
                <th>Date</th>
                <th>Type</th>
                <th class="ta-right">Amount</th>
                <th>Method</th>
                <th>User</th>
                <th>Customer</th>
                <th>Supplier</th>
                <th>Notes</th>
            </tr>
        </thead>
        <tbody>
            @foreach($transactions as $t)
                <tr>
                    <td>{{ \Carbon\Carbon::parse($t->transaction_date)->format('d M Y') }}</td>
                    <td>
                        <span class="chip {{ $t->type === 'credit' ? 'credit' : 'debit' }}">
                            {{ ucfirst($t->type) }}
                        </span>
                    </td>
                    <td class="ta-right">{{ $fmt($t->amount) }} RWF</td>
                    <td>{{ $t->method ?? '—' }}</td>
                    <td>{{ optional($t->user)->name ?? '—' }}</td>
                    <td>{{ optional($t->customer)->name ?? '—' }}</td>
                    <td>{{ optional($t->supplier)->name ?? '—' }}</td>
                    <td>{{ $t->notes ?? '—' }}</td>
                </tr>
            @endforeach
        </tbody>
        <tfoot>
            <tr>
                <th colspan="2" class="ta-right">Totals:</th>
                <th class="ta-right">{{ $fmt($transactions->sum('amount')) }} RWF</th>
                <th colspan="5"></th>
            </tr>
        </tfoot>
    </table>

    <div class="footer">
        Generated on {{ now()->format('d M Y, H:i') }}
    </div>

    {{-- Dompdf page numbers --}}
    <script type="text/php">
        if (isset($pdf)) {
            $pdf->page_text(520, 810, "Page {PAGE_NUM}/{PAGE_COUNT}", "DejaVu Sans", 9, [0.4,0.4,0.4]);
        }
    </script>
</body>
</html>
