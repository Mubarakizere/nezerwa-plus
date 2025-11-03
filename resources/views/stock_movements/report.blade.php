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

        td {
            font-size: 10.5px;
        }

        tr:nth-child(even) {
            background: #fafafa;
        }

        .text-right { text-align: right; }
        .text-center { text-align: center; }

        /* ===== Badges ===== */
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

        /* ===== Column widths (prevent overflow) ===== */
        th:nth-child(1) { width: 12%; }
        th:nth-child(2) { width: 18%; }
        th:nth-child(3) { width: 8%; }
        th:nth-child(4) { width: 8%; }
        th:nth-child(5) { width: 10%; }
        th:nth-child(6) { width: 10%; }
        th:nth-child(7) { width: 14%; }
        th:nth-child(8) { width: 15%; }

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
                <th>Recorded By</th>
                <th>Source</th>
            </tr>
        </thead>

        <tbody>
            @php
                $totalIn = 0;
                $totalOut = 0;
            @endphp

            @foreach($movements as $m)
                <tr>
                    <td>{{ $m->created_at->format('d M Y, H:i') }}</td>
                    <td>{{ $m->product->name }}</td>
                    <td class="text-center">
                        @if($m->type === 'in')
                            <span class="badge-in">IN</span>
                            @php $totalIn += $m->quantity; @endphp
                        @else
                            <span class="badge-out">OUT</span>
                            @php $totalOut += $m->quantity; @endphp
                        @endif
                    </td>
                    <td class="text-right">{{ number_format($m->quantity, 2) }}</td>
                    <td class="text-right">{{ number_format($m->unit_cost ?? 0, 2) }}</td>
                    <td class="text-right">{{ number_format($m->total_cost ?? 0, 2) }}</td>
                    <td>{{ $m->user->name ?? 'System' }}</td>
                    <td>{{ class_basename($m->source_type) }} #{{ $m->source_id }}</td>
                </tr>
            @endforeach
        </tbody>

        {{-- 🔹 Summary --}}
        @php
            $net = $totalIn - $totalOut;
        @endphp
        <tfoot>
            <tr>
                <td colspan="3" class="text-right">Total In:</td>
                <td class="text-right">{{ number_format($totalIn, 2) }}</td>
                <td colspan="2" class="text-right">Total Out:</td>
                <td colspan="2" class="text-right">{{ number_format($totalOut, 2) }}</td>
            </tr>
            <tr>
                <td colspan="7" class="text-right">Net Movement:</td>
                <td class="text-right" style="color: {{ $net >= 0 ? '#166534' : '#b91c1c' }}">
                    {{ number_format($net, 2) }}
                </td>
            </tr>
        </tfoot>
    </table>

    {{-- 🔸 Footer --}}
    <div class="footer">
        Diva Stock Management System • {{ config('app.name') }}
    </div>

</body>
</html>
