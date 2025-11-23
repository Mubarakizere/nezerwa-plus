@extends('layouts.pdf')

@section('title', 'Business Insights')

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
        <h3>Performance Summary</h3>
        <div style="display: flex; gap: 10px; margin-bottom: 20px;">
            <div style="flex: 1; border: 1px solid #e5e7eb; padding: 10px; border-radius: 4px;">
                <div class="text-xs text-gray-500">Total Sales</div>
                <div class="font-bold">{{ number_format($totalSales, 2) }}</div>
            </div>
            <div style="flex: 1; border: 1px solid #e5e7eb; padding: 10px; border-radius: 4px;">
                <div class="text-xs text-gray-500">Total Profit</div>
                <div class="font-bold text-success">{{ number_format($totalProfit, 2) }}</div>
            </div>
            <div style="flex: 1; border: 1px solid #e5e7eb; padding: 10px; border-radius: 4px;">
                <div class="text-xs text-gray-500">Net Balance</div>
                <div class="font-bold {{ $netBalance >= 0 ? 'text-success' : 'text-danger' }}">{{ number_format($netBalance, 2) }}</div>
            </div>
        </div>
    </div>

    <div class="section grid-2">
        <div class="col">
            <h3>Top Products</h3>
            <table class="table table-striped">
                <thead>
                    <tr>
                        <th>Product</th>
                        <th class="num">Sales</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($topProducts as $p)
                        <tr>
                            <td>{{ $p->product->name ?? 'Unknown' }}</td>
                            <td class="num">{{ number_format($p->total_sales, 2) }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <div class="col">
            <h3>Top Customers</h3>
            <table class="table table-striped">
                <thead>
                    <tr>
                        <th>Customer</th>
                        <th class="num">Spent</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($topCustomers as $c)
                        <tr>
                            <td>{{ $c->customer->name ?? 'Unknown' }}</td>
                            <td class="num">{{ number_format($c->total_spent, 2) }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
@endsection
