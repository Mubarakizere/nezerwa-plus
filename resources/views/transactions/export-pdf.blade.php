<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Transactions Report</title>
    <style>
        /* ===== GLOBAL PAGE STYLES ===== */
        @page { margin: 25px 25px 35px 25px; }

        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 11px;
            color: #222;
        }

        h2 {
            text-align: center;
            font-size: 16px;
            font-weight: 700;
            letter-spacing: 0.5px;
            margin-bottom: 15px;
            border-bottom: 1px solid #4f46e5;
            padding-bottom: 5px;
        }

        /* ===== SUMMARY SECTION ===== */
        .summary {
            width: 100%;
            margin-bottom: 15px;
        }
        .summary td {
            font-size: 11px;
            padding: 3px 8px;
        }
        .summary strong {
            display: inline-block;
            width: 100px;
        }

        /* ===== TABLE ===== */
        table {
            width: 100%;
            border-collapse: collapse;
            table-layout: fixed; /* 🚀 prevents overflow */
            word-wrap: break-word;
        }

        th, td {
            border: 1px solid #ccc;
            padding: 5px 6px;
            text-align: left;
        }

        th {
            background-color: #f3f4f6;
            font-weight: 600;
            font-size: 10.5px;
        }

        td {
            font-size: 10.5px;
        }

        tr:nth-child(even) { background: #fafafa; }

        /* Fit each column cleanly */
        th:nth-child(1) { width: 11%; }  /* Date */
        th:nth-child(2) { width: 8%; }   /* Type */
        th:nth-child(3) { width: 10%; }  /* Amount */
        th:nth-child(4) { width: 10%; }  /* Method */
        th:nth-child(5) { width: 13%; }  /* User */
        th:nth-child(6) { width: 13%; }  /* Customer */
        th:nth-child(7) { width: 13%; }  /* Supplier */
        th:nth-child(8) { width: 22%; }  /* Notes */

        /* ===== FOOTER ===== */
        .footer {
            margin-top: 15px;
            text-align: right;
            font-size: 9.5px;
            color: #666;
            border-top: 1px solid #e5e7eb;
            padding-top: 4px;
        }
    </style>
</head>
<body>
    {{-- 🔹 Header --}}
    <h2>TRANSACTIONS REPORT</h2>

    {{-- 🔸 Summary --}}
    @php
        $totalCredits = $transactions->where('type', 'credit')->sum('amount');
        $totalDebits = $transactions->where('type', 'debit')->sum('amount');
        $net = $totalCredits - $totalDebits;
    @endphp

    <table class="summary">
        <tr>
            <td><strong>Total Credits:</strong></td>
            <td style="color: green;">{{ number_format($totalCredits, 2) }}</td>
        </tr>
        <tr>
            <td><strong>Total Debits:</strong></td>
            <td style="color: red;">{{ number_format($totalDebits, 2) }}</td>
        </tr>
        <tr>
            <td><strong>Net Balance:</strong></td>
            <td style="color: {{ $net >= 0 ? 'green' : 'red' }};">{{ number_format($net, 2) }}</td>
        </tr>
    </table>

    {{-- 🔸 Transactions Table --}}
    <table>
        <thead>
            <tr>
                <th>Date</th>
                <th>Type</th>
                <th>Amount</th>
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
                    <td style="font-weight:bold; color:{{ $t->type == 'credit' ? 'green' : 'red' }}">
                        {{ ucfirst($t->type) }}
                    </td>
                    <td>{{ number_format($t->amount, 2) }}</td>
                    <td>{{ $t->method ?? '—' }}</td>
                    <td>{{ $t->user->name ?? '—' }}</td>
                    <td>{{ $t->customer->name ?? '—' }}</td>
                    <td>{{ $t->supplier->name ?? '—' }}</td>
                    <td>{{ $t->notes ?? '—' }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    {{-- 🔸 Footer --}}
    <div class="footer">
        Generated on {{ now()->format('d M Y, H:i') }}
    </div>
</body>
</html>
