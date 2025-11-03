<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Finance Report ({{ $start }} → {{ $end }})</title>
    <style>
        /* ===== Base ===== */
        @page { margin: 30px 35px 40px 35px; }

        body {
            font-family: DejaVu Sans, sans-serif;
            color: #111827;
            font-size: 12px;
            line-height: 1.6;
        }

        /* ===== Typography ===== */
        h1 {
            text-align: center;
            font-size: 20px;
            font-weight: 700;
            color: #111827;
            margin: 0 0 4px 0;
        }
        p {
            text-align: center;
            font-size: 11px;
            color: #6B7280;
            margin: 0 0 20px 0;
        }

        h2 {
            font-size: 14px;
            color: #374151;
            border-bottom: 1px solid #E5E7EB;
            padding-bottom: 4px;
            margin-top: 28px;
            margin-bottom: 10px;
        }

        /* ===== Tables ===== */
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
            margin-bottom: 25px;
        }

        th, td {
            border: 1px solid #E5E7EB;
            padding: 8px 10px;
        }

        th {
            background-color: #F9FAFB;
            font-weight: 600;
            color: #374151;
            font-size: 11px;
            text-transform: uppercase;
        }

        td {
            font-size: 11.5px;
        }

        td.label {
            font-weight: 600;
            color: #374151;
            text-align: left;
        }

        tr:nth-child(even) td {
            background-color: #FAFAFA;
        }

        /* ===== Totals & Colors ===== */
        .text-green { color: #059669; font-weight: 600; }
        .text-red { color: #DC2626; font-weight: 600; }
        .text-indigo { color: #4F46E5; font-weight: 600; }

        /* ===== Section Summary Boxes ===== */
        .summary-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
            gap: 10px;
            margin-top: 12px;
        }

        .summary-card {
            border: 1px solid #E5E7EB;
            border-radius: 6px;
            padding: 8px 12px;
            background-color: #F9FAFB;
        }

        .summary-card strong {
            display: block;
            font-size: 12px;
            color: #374151;
            margin-bottom: 3px;
        }

        /* ===== Footer ===== */
        footer {
            position: fixed;
            bottom: 20px;
            left: 0;
            right: 0;
            text-align: center;
            font-size: 10px;
            color: #6B7280;
            border-top: 1px solid #E5E7EB;
            padding-top: 6px;
        }

        /* ===== Header Logo / Branding ===== */
        .header-bar {
            text-align: center;
            margin-bottom: 10px;
        }
        .logo {
            height: 45px;
            margin-bottom: 6px;
        }
    </style>
</head>
<body>

    {{-- 🔹 Header --}}
    <div class="header-bar">
        {{-- Uncomment if you add logo --}}
        {{-- <img src="{{ public_path('images/logo.png') }}" alt="Logo" class="logo"> --}}
        <h1>Finance Report</h1>
        <p>Period: {{ $start }} → {{ $end }}</p>
    </div>

    {{-- 🔸 Financial Summary --}}
    <h2>Financial Overview</h2>

    <div class="summary-grid">
        <div class="summary-card">
            <strong>Total Credits (In)</strong>
            <span class="text-green">{{ number_format($credits, 2) }}</span>
        </div>

        <div class="summary-card">
            <strong>Total Debits (Out)</strong>
            <span class="text-red">{{ number_format($debits, 2) }}</span>
        </div>

        <div class="summary-card">
            <strong>Net Balance</strong>
            <span class="{{ $netBalance >= 0 ? 'text-green' : 'text-red' }}">
                {{ number_format($netBalance, 2) }}
            </span>
        </div>

        <div class="summary-card">
            <strong>Total Profit</strong>
            <span class="text-indigo">{{ number_format($totalProfit, 2) }}</span>
        </div>
    </div>

    {{-- 🔸 Transactions Summary Table --}}
    <h2>Transaction Breakdown</h2>
    <table>
        <thead>
            <tr>
                <th style="width: 25%;">Category</th>
                <th style="width: 25%;">Credits (In)</th>
                <th style="width: 25%;">Debits (Out)</th>
                <th style="width: 25%;">Net</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td class="label">Sales</td>
                <td>{{ number_format($salesCredits, 2) }}</td>
                <td>{{ number_format($salesDebits, 2) }}</td>
                <td class="{{ ($salesCredits - $salesDebits) >= 0 ? 'text-green' : 'text-red' }}">
                    {{ number_format($salesCredits - $salesDebits, 2) }}
                </td>
            </tr>
            <tr>
                <td class="label">Purchases</td>
                <td>{{ number_format($purchaseCredits, 2) }}</td>
                <td>{{ number_format($purchaseDebits, 2) }}</td>
                <td class="{{ ($purchaseCredits - $purchaseDebits) >= 0 ? 'text-green' : 'text-red' }}">
                    {{ number_format($purchaseCredits - $purchaseDebits, 2) }}
                </td>
            </tr>
            <tr>
                <td class="label">Loans</td>
                <td>{{ number_format($loanCredits, 2) }}</td>
                <td>{{ number_format($loanDebits, 2) }}</td>
                <td class="{{ ($loanCredits - $loanDebits) >= 0 ? 'text-green' : 'text-red' }}">
                    {{ number_format($loanCredits - $loanDebits, 2) }}
                </td>
            </tr>
            <tr>
                <td class="label">Other Transactions</td>
                <td>{{ number_format($otherCredits, 2) }}</td>
                <td>{{ number_format($otherDebits, 2) }}</td>
                <td class="{{ ($otherCredits - $otherDebits) >= 0 ? 'text-green' : 'text-red' }}">
                    {{ number_format($otherCredits - $otherDebits, 2) }}
                </td>
            </tr>
        </tbody>
    </table>

    {{-- 🔸 Profitability --}}
    <h2>Profitability Metrics</h2>
    <table>
        <tbody>
            <tr>
                <td class="label">Profit Margin (%)</td>
                <td class="text-green">{{ number_format($profitMargin, 1) }}%</td>
            </tr>
            <tr>
                <td class="label">Expense Ratio (%)</td>
                <td class="text-indigo">{{ number_format($expenseRatio, 1) }}%</td>
            </tr>
            <tr>
                <td class="label">Revenue Growth (vs Previous Period)</td>
                <td class="{{ $revenueGrowth >= 0 ? 'text-green' : 'text-red' }}">
                    {{ number_format($revenueGrowth, 1) }}%
                </td>
            </tr>
        </tbody>
    </table>

    {{-- 🔸 Footer --}}
    <footer>
       \ Stock Management System — Finance Report |
        Generated on {{ now()->format('d M Y, H:i') }} |
        Confidential Internal Use Only
    </footer>

</body>
</html>
