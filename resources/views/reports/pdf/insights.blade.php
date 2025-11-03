<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Business Insights Report ({{ $start }} → {{ $end }})</title>
    <style>
        body {
            font-family: DejaVu Sans, sans-serif;
            color: #222;
            font-size: 13px;
            line-height: 1.6;
            margin: 40px;
        }

        /* ---- Typography ---- */
        h1 {
            font-size: 20px;
            font-weight: 600;
            color: #111827;
            text-align: center;
            margin-bottom: 5px;
        }
        h2 {
            font-size: 15px;
            color: #374151;
            border-bottom: 1px solid #E5E7EB;
            padding-bottom: 4px;
            margin-top: 25px;
            margin-bottom: 10px;
        }
        h3 {
            font-size: 14px;
            color: #4F46E5;
            margin-bottom: 4px;
        }
        p {
            margin: 0 0 10px;
        }

        /* ---- Summary Cards ---- */
        .summary {
            display: flex;
            justify-content: space-between;
            gap: 10px;
            margin: 25px 0;
        }
        .card {
            flex: 1;
            border: 1px solid #E5E7EB;
            border-radius: 6px;
            padding: 12px 14px;
            text-align: center;
            background-color: #FAFAFA;
        }
        .card h3 {
            font-weight: 600;
            font-size: 13px;
            color: #6B7280;
            margin-bottom: 6px;
        }
        .card p {
            font-size: 15px;
            font-weight: 600;
            margin: 0;
        }

        /* ---- Tables ---- */
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
            margin-bottom: 20px;
        }
        th, td {
            border: 1px solid #E5E7EB;
            padding: 8px 10px;
            text-align: left;
        }
        th {
            background-color: #F9FAFB;
            font-weight: 600;
            color: #374151;
        }
        tr:nth-child(even) {
            background-color: #FAFAFA;
        }

        /* ---- Colors ---- */
        .text-green { color: #059669; }
        .text-red { color: #DC2626; }
        .text-indigo { color: #4F46E5; }

        /* ---- Footer ---- */
        footer {
            text-align: center;
            font-size: 11px;
            color: #6B7280;
            border-top: 1px solid #E5E7EB;
            padding-top: 8px;
            margin-top: 30px;
        }
    </style>
</head>
<body>

    {{-- ===== HEADER ===== --}}
    <h1>Stock Manager</h1>
    <p style="text-align:center; font-size:12px; color:#6B7280;">
        Business Insights Report<br>
        Period: {{ $start }} → {{ $end }}
    </p>

    {{-- ===== SUMMARY CARDS ===== --}}
    <div class="summary">
        <div class="card">
            <h3>Total Sales</h3>
            <p class="text-indigo">{{ number_format($totalSales, 2) }}</p>
        </div>
        <div class="card">
            <h3>Total Profit</h3>
            <p class="text-green">{{ number_format($totalProfit, 2) }}</p>
        </div>
        <div class="card">
            <h3>Net Balance</h3>
            <p class="{{ $netBalance >= 0 ? 'text-green' : 'text-red' }}">
                {{ number_format($netBalance, 2) }}
            </p>
        </div>
    </div>

    {{-- ===== RATIOS ===== --}}
    <h2>Key Financial Ratios</h2>
    <table>
        <tbody>
            <tr>
                <td>Profit Margin</td>
                <td><span class="text-green">{{ number_format($profitMargin, 1) }}%</span></td>
            </tr>
            <tr>
                <td>Expense Ratio</td>
                <td><span class="text-indigo">{{ number_format($expenseRatio, 1) }}%</span></td>
            </tr>
        </tbody>
    </table>

    {{-- ===== TOP PRODUCTS ===== --}}
    <h2>Top 5 Products</h2>
    <table>
        <thead>
            <tr>
                <th>Product</th>
                <th>Total Sales (RWF)</th>
            </tr>
        </thead>
        <tbody>
            @forelse($topProducts as $p)
                <tr>
                    <td>{{ $p->product->name ?? 'N/A' }}</td>
                    <td>{{ number_format($p->total_sales, 2) }}</td>
                </tr>
            @empty
                <tr><td colspan="2" style="text-align:center; color:#9CA3AF;">No product data</td></tr>
            @endforelse
        </tbody>
    </table>

    {{-- ===== TOP CUSTOMERS ===== --}}
    <h2>Top 5 Customers</h2>
    <table>
        <thead>
            <tr>
                <th>Customer</th>
                <th>Total Spent (RWF)</th>
            </tr>
        </thead>
        <tbody>
            @forelse($topCustomers as $c)
                <tr>
                    <td>{{ $c->customer->name ?? 'N/A' }}</td>
                    <td>{{ number_format($c->total_spent, 2) }}</td>
                </tr>
            @empty
                <tr><td colspan="2" style="text-align:center; color:#9CA3AF;">No customer data</td></tr>
            @endforelse
        </tbody>
    </table>

    {{-- ===== FOOTER ===== --}}
    <footer>
        Generated by <strong>Stock Manager</strong> |
        {{ now()->format('d M Y H:i') }} <br>
        Confidential business document — internal use only.
    </footer>

</body>
</html>
