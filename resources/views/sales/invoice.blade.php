@extends('layouts.pdf')

@section('title', "Invoice #{$sale->id}")

@section('header-meta')
    <table class="meta-table">
        <tr>
            <td class="text-gray-500">Invoice #:</td>
            <td class="font-bold">{{ $sale->id }}</td>
        </tr>
        <tr>
            <td class="text-gray-500">Date:</td>
            <td>{{ \Carbon\Carbon::parse($sale->sale_date)->format('d M Y') }}</td>
        </tr>
        <tr>
            <td class="text-gray-500">Status:</td>
            <td>
                <span class="{{ $sale->status === 'paid' ? 'text-success' : ($sale->status === 'pending' ? 'text-danger' : 'text-gray-500') }}">
                    {{ ucfirst($sale->status) }}
                </span>
            </td>
        </tr>
    </table>
@endsection

@section('content')
    <div class="section grid-2">
        <div class="col">
            <h3>Bill To</h3>
            @if($sale->customer)
                <div class="font-bold">{{ $sale->customer->name }}</div>
                <div class="text-sm text-gray-500">
                    {{ $sale->customer->phone ?? 'No Phone' }}<br>
                    {{ $sale->customer->email ?? '' }}
                </div>
            @else
                <div class="font-bold">Walk-in Customer</div>
            @endif
        </div>
        <div class="col">
            @if($sale->notes)
                <h3>Notes</h3>
                <div class="text-sm text-gray-500">{{ $sale->notes }}</div>
            @endif
        </div>
    </div>

    <div class="section">
        <table class="table table-striped">
            <thead>
                <tr>
                    <th>Item</th>
                    <th class="num">Qty</th>
                    <th class="num">Price</th>
                    <th class="num">Total</th>
                </tr>
            </thead>
            <tbody>
                @foreach($sale->items as $item)
                    <tr>
                        <td>
                            <div class="font-bold">{{ $item->product->name ?? 'Unknown Item' }}</div>
                        </td>
                        <td class="num">{{ number_format($item->quantity) }}</td>
                        <td class="num">{{ number_format($item->unit_price, 2) }}</td>
                        <td class="num">{{ number_format($item->subtotal, 2) }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <div class="totals">
        <div class="totals-row">
            <div class="totals-label">Subtotal</div>
            <div class="totals-value">{{ number_format($sale->total_amount, 2) }}</div>
        </div>
        {{-- Add Tax/Discount rows here if available in model --}}
        <div class="totals-row grand-total">
            <div class="totals-label">Total</div>
            <div class="totals-value">{{ number_format($sale->total_amount, 2) }}</div>
        </div>
        <div class="totals-row" style="margin-top: 10px; font-size: 11px;">
            <div class="totals-label">Amount Paid</div>
            <div class="totals-value text-success">{{ number_format($sale->amount_paid, 2) }}</div>
        </div>
        @php $due = $sale->total_amount - $sale->amount_paid; @endphp
        @if($due > 0)
            <div class="totals-row" style="font-size: 11px;">
                <div class="totals-label">Balance Due</div>
                <div class="totals-value text-danger">{{ number_format($due, 2) }}</div>
            </div>
        @endif
    </div>

    @if($sale->status === 'paid')
        <div style="text-align: center; margin-top: 50px;">
            <div style="display: inline-block; border: 2px solid #059669; color: #059669; padding: 10px 20px; font-weight: bold; font-size: 18px; transform: rotate(-10deg); opacity: 0.8;">
                PAID IN FULL
            </div>
        </div>
    @endif
@endsection
