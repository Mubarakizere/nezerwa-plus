@extends('layouts.pdf')

@section('title', 'Finance Report')

@section('header-meta')
    <table class="meta-table">
        <tr>
            <td class="text-gray-500">Period:</td>
            <td class="font-bold">
                {{ $start->format('d M Y') }} - {{ $end->format('d M Y') }}
            </td>
        </tr>
    </table>
@endsection

@section('content')
    <div class="section">
        <h3>Financial Overview</h3>
        <table class="table">
            <thead>
                <tr>
                    <th>Category</th>
                    <th class="num">Credits (In)</th>
                    <th class="num">Debits (Out)</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>Sales & Purchases</td>
                    <td class="num text-success">{{ number_format($salesCredits, 2) }}</td>
                    <td class="num text-danger">{{ number_format($purchaseDebits, 2) }}</td>
                </tr>
                <tr>
                    <td>Loans</td>
                    <td class="num text-success">{{ number_format($loanCredits, 2) }}</td>
                    <td class="num text-danger">{{ number_format($loanDebits, 2) }}</td>
                </tr>
                <tr>
                    <td>Other Transactions</td>
                    <td class="num text-success">{{ number_format($otherCredits, 2) }}</td>
                    <td class="num text-danger">{{ number_format($otherDebits, 2) }}</td>
                </tr>
                <tr style="background-color: #f3f4f6; font-weight: bold;">
                    <td>Totals</td>
                    <td class="num text-success">{{ number_format($credits, 2) }}</td>
                    <td class="num text-danger">{{ number_format($debits, 2) }}</td>
                </tr>
            </tbody>
        </table>
    </div>

    <div class="section grid-2">
        <div class="col">
            <h3>Net Balance</h3>
            <div style="font-size: 24px; font-weight: bold; color: {{ $netBalance >= 0 ? '#059669' : '#dc2626' }};">
                {{ number_format($netBalance, 2) }}
            </div>
            <div class="text-sm text-gray-500 mt-1">Total Credits - Total Debits</div>
        </div>
        <div class="col">
            <h3>Key Metrics</h3>
            <table style="width: 100%;">
                <tr>
                    <td class="text-gray-500">Profit Margin:</td>
                    <td class="text-right font-bold">{{ number_format($profitMargin, 1) }}%</td>
                </tr>
                <tr>
                    <td class="text-gray-500">Expense Ratio:</td>
                    <td class="text-right font-bold">{{ number_format($expenseRatio, 1) }}%</td>
                </tr>
                <tr>
                    <td class="text-gray-500">Revenue Growth:</td>
                    <td class="text-right font-bold {{ $revenueGrowth >= 0 ? 'text-success' : 'text-danger' }}">
                        {{ number_format($revenueGrowth, 1) }}%
                    </td>
                </tr>
            </table>
        </div>
    </div>
@endsection
