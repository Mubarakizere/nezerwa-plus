@extends('layouts.pdf')

@section('title', 'Profit & Loss Statement')

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
        <table class="table">
            <thead>
                <tr>
                    <th>Item</th>
                    <th class="num">Amount</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td colspan="2" class="font-bold text-gray-500" style="background-color: #f9fafb;">REVENUE</td>
                </tr>
                <tr>
                    <td>Sales Revenue</td>
                    <td class="num">{{ number_format($plRevenue, 2) }}</td>
                </tr>
                <tr>
                    <td colspan="2" class="font-bold text-gray-500" style="background-color: #f9fafb;">COST OF GOODS SOLD</td>
                </tr>
                <tr>
                    <td>Cost of Goods Sold</td>
                    <td class="num">({{ number_format($plCogs, 2) }})</td>
                </tr>
                <tr style="border-top: 2px solid #e5e7eb;">
                    <td class="font-bold">GROSS PROFIT</td>
                    <td class="num font-bold">{{ number_format($plGrossProfit, 2) }}</td>
                </tr>
                <tr>
                    <td colspan="2" class="font-bold text-gray-500" style="background-color: #f9fafb;">OPERATING EXPENSES</td>
                </tr>
                <tr>
                    <td>Other Expenses</td>
                    <td class="num">({{ number_format($plOtherExpenses, 2) }})</td>
                </tr>
                <tr style="border-top: 2px solid #e5e7eb; background-color: #f3f4f6;">
                    <td class="font-bold" style="font-size: 14px;">NET PROFIT</td>
                    <td class="num font-bold" style="font-size: 14px; color: {{ $plNetProfit >= 0 ? '#059669' : '#dc2626' }};">
                        {{ number_format($plNetProfit, 2) }}
                    </td>
                </tr>
            </tbody>
        </table>
    </div>
@endsection
