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
                <span class="{{ $sale->status === 'paid' || $sale->isGift() ? 'text-success' : ($sale->status === 'pending' ? 'text-danger' : 'text-gray-500') }}">
                    {{ $sale->isGift() ? 'Boss Gift (Completed)' : ucfirst($sale->status) }}
                </span>
            </td>
        </tr>
        @if($sale->isGift())
            <tr>
                <td class="text-gray-500">Type:</td>
                <td class="font-bold" style="color: #7c3aed;">COMPLIMENTARY GIFT</td>
            </tr>
            @if($sale->gift_reason)
                <tr>
                    <td class="text-gray-500">Reason:</td>
                    <td style="color: #6b21a8; font-style: italic;">{{ $sale->gift_reason }}</td>
                </tr>
            @endif
        @endif
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
            @if($sale->isGift())
                <div class="text-sm" style="color: #7c3aed; margin-top: 5px;">
                    <strong>Notice:</strong> Authorized as a complimentary gift by boss. Zero payment required.
                </div>
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

    @if($sale->loan && $sale->loan->payments->count())
        <div style="margin-top: 15px; margin-bottom: 15px;">
            <div style="font-size: 10px; font-weight: bold; text-transform: uppercase; color: #4b5563; margin-bottom: 5px; border-bottom: 1px solid #e5e7eb; padding-bottom: 3px;">
                Credit Repayments Received
            </div>
            <table class="table" style="font-size: 10px; margin-bottom: 0;">
                <thead>
                    <tr>
                        <th style="padding: 4px 6px;">Payment Date</th>
                        <th style="padding: 4px 6px;">Channel</th>
                        <th style="padding: 4px 6px;">Cashier</th>
                        <th style="padding: 4px 6px;" class="num">Amount Paid</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($sale->loan->payments->sortBy('payment_date') as $lp)
                        <tr>
                            <td style="padding: 4px 6px;">{{ $lp->payment_date ? \Carbon\Carbon::parse($lp->payment_date)->format('Y-m-d') : '—' }}</td>
                            <td style="padding: 4px 6px; text-transform: uppercase;">{{ $lp->method }}</td>
                            <td style="padding: 4px 6px;">{{ $lp->user->name ?? 'System' }}</td>
                            <td style="padding: 4px 6px;" class="num text-success">+ {{ number_format($lp->amount, 2) }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif

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
        @if($sale->isGift())
            <div class="totals-row" style="margin-top: 10px; font-size: 11px;">
                <div class="totals-label">Gift Settled by Boss</div>
                <div class="totals-value text-success" style="color: #7c3aed; font-weight: bold;">{{ number_format($sale->total_amount, 2) }}</div>
            </div>
            <div class="totals-row" style="font-size: 11px;">
                <div class="totals-label">Balance Due</div>
                <div class="totals-value text-success">0.00</div>
            </div>
        @else
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
        @endif
    </div>

    @if($sale->isGift())
        <div style="text-align: center; margin-top: 40px;">
            <div style="display: inline-block; border: 2px solid #7c3aed; color: #7c3aed; padding: 10px 24px; font-weight: bold; font-size: 18px; transform: rotate(-8deg); opacity: 0.85; border-radius: 4px; letter-spacing: 1px;">
                COMPLIMENTARY BOSS GIFT
            </div>
        </div>
    @elseif($sale->status === 'paid' || ($sale->total_amount - $sale->amount_paid <= 0))
        <div style="text-align: center; margin-top: 50px;">
            <div style="display: inline-block; border: 2px solid #059669; color: #059669; padding: 10px 20px; font-weight: bold; font-size: 18px; transform: rotate(-10deg); opacity: 0.8;">
                PAID IN FULL
            </div>
        </div>
    @endif
@endsection
