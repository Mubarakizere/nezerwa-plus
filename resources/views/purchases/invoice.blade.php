<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Purchase Invoice #{{ $purchase->id }}</title>
    <style>
        /* ===============================
           PAGE & TYPOGRAPHY
        =============================== */
        @page { margin: 30px 40px; }
        body {
            font-family: 'DejaVu Sans', sans-serif;
            font-size: 13px;
            color: #111827;
            background: #fff;
            margin: 0;
        }
        h1, h2, h3, h4 { margin: 0; color: #1e293b; }
        p { margin: 2px 0; }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th, td {
            border: 1px solid #e5e7eb;
            padding: 8px 10px;
            vertical-align: top;
        }
        th {
            background: #f9fafb;
            color: #111827;
            text-align: left;
            font-weight: 600;
        }
        td { color: #374151; }
        .text-right { text-align: right; }
        .text-center { text-align: center; }

        /* ===============================
           HEADER
        =============================== */
        .header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            border-bottom: 2px solid #1e40af;
            padding-bottom: 8px;
            margin-bottom: 20px;
        }
        .company h2 {
            font-size: 22px;
            color: #1e40af;
            font-weight: 700;
        }
        .company small { color: #6b7280; }
        .invoice-meta { text-align: right; font-size: 13px; }

        /* ===============================
           BADGES
        =============================== */
        .badge {
            display: inline-block;
            padding: 2px 8px;
            border-radius: 12px;
            font-size: 11px;
            font-weight: 600;
        }
        .badge-green { background: #dcfce7; color: #166534; }
        .badge-yellow { background: #fef9c3; color: #854d0e; }
        .badge-red { background: #fee2e2; color: #991b1b; }

        /* ===============================
           TOTALS TABLE
        =============================== */
        .total-row td { font-weight: bold; background: #f9fafb; }
        .muted { color: #6b7280; }

        /* ===============================
           FOOTER
        =============================== */
        .footer {
            margin-top: 40px;
            border-top: 1px solid #e5e7eb;
            padding-top: 10px;
            text-align: center;
            font-size: 12px;
            color: #6b7280;
        }

        /* ===============================
           RESPONSIVE / PRINT
        =============================== */
        @media print {
            body { -webkit-print-color-adjust: exact; }
            .no-print { display: none; }
        }
    </style>
</head>
<body>
<div class="wrap" style="max-width:900px; margin:0 auto; padding:20px;">

    {{-- 🔹 HEADER --}}
    <div class="header">
        <div class="company">
            <h2>{{ config('company.name', config('app.name', 'Your Company')) }}</h2>
            @if(config('company.address_line1'))<div>{{ config('company.address_line1') }}</div>@endif
            @if(config('company.address_line2'))<div>{{ config('company.address_line2') }}</div>@endif
            @if(config('company.phone'))<div>Phone: {{ config('company.phone') }}</div>@endif
            @if(config('company.email'))<div>Email: {{ config('company.email') }}</div>@endif
            @if(config('company.tax_id'))<div>{{ config('company.tax_id') }}</div>@endif
        </div>

        <div class="invoice-meta">
            <h3 style="margin-bottom:4px;">Purchase Invoice</h3>
            <p><strong>Invoice #:</strong> {{ $purchase->id }}</p>
            <p><strong>Date:</strong> {{ \Carbon\Carbon::parse($purchase->purchase_date)->format('Y-m-d') }}</p>
            <p><strong>Recorded by:</strong> {{ $purchase->user->name ?? 'System' }}</p>
            @php
                $status = $purchase->status ?? 'completed';
                $badge = $status === 'completed' ? 'badge-green' : ($status === 'pending' ? 'badge-yellow' : 'badge-red');
            @endphp
            <p><strong>Status:</strong> <span class="badge {{ $badge }}">{{ ucfirst($status) }}</span></p>
        </div>
    </div>

    {{-- 🔹 SUPPLIER INFO --}}
    <div style="margin-bottom:16px; display:flex; justify-content:space-between;">
        <div>
            <strong>Supplier:</strong><br>
            {{ $purchase->supplier->name ?? 'Unknown Supplier' }}<br>
            @if($purchase->supplier->phone)<span>Phone: {{ $purchase->supplier->phone }}</span><br>@endif
            @if($purchase->supplier->email)<span>Email: {{ $purchase->supplier->email }}</span><br>@endif
        </div>
        <div style="text-align:right;">
            @if($purchase->invoice_number)
                <div>Supplier Invoice: <strong>{{ $purchase->invoice_number }}</strong></div>
            @endif
            @if($purchase->method)
                <div>Payment Method: <strong>{{ ucfirst($purchase->method) }}</strong></div>
            @endif
        </div>
    </div>

    {{-- 🔹 ITEMS TABLE --}}
    <table>
        <thead>
            <tr>
                <th>Product</th>
                <th class="text-center">Qty</th>
                <th class="text-right">Unit Cost</th>
                <th class="text-right">Subtotal</th>
            </tr>
        </thead>
        <tbody>
            @foreach($purchase->items as $item)
                <tr>
                    <td>{{ $item->product->name ?? 'N/A' }}</td>
                    <td class="text-center">{{ number_format($item->quantity, 2) }}</td>
                    <td class="text-right">{{ number_format($item->unit_cost, 2) }}</td>
                    <td class="text-right">{{ number_format($item->total_cost, 2) }}</td>
                </tr>
            @endforeach
        </tbody>

        @php
            $subtotal = $purchase->subtotal ?? $purchase->items->sum('total_cost');
            $tax = $purchase->tax ?? 0;
            $discount = $purchase->discount ?? 0;
            $total = $purchase->total_amount ?? (($subtotal + $tax) - $discount);
            $paid = $purchase->amount_paid ?? 0;
            $balance = $total - $paid;
        @endphp

        <tfoot>
            <tr>
                <td colspan="3" class="text-right muted">Subtotal</td>
                <td class="text-right">{{ number_format($subtotal, 2) }}</td>
            </tr>
            <tr>
                <td colspan="3" class="text-right muted">Tax</td>
                <td class="text-right">{{ number_format($tax, 2) }}</td>
            </tr>
            <tr>
                <td colspan="3" class="text-right muted">Discount</td>
                <td class="text-right">-{{ number_format($discount, 2) }}</td>
            </tr>
            <tr class="total-row">
                <td colspan="3" class="text-right">Total Amount</td>
                <td class="text-right">{{ number_format($total, 2) }}</td>
            </tr>
            <tr>
                <td colspan="3" class="text-right muted">Amount Paid</td>
                <td class="text-right">{{ number_format($paid, 2) }}</td>
            </tr>
            <tr>
                <td colspan="3" class="text-right muted">Balance Due</td>
                <td class="text-right" style="color: {{ $balance > 0 ? '#dc2626' : '#16a34a' }}">
                    {{ number_format($balance, 2) }}
                </td>
            </tr>
        </tfoot>
    </table>

    {{-- 🔹 NOTES --}}
    @if($purchase->notes)
        <div style="margin-top:16px;">
            <strong>Notes:</strong>
            <p class="muted" style="white-space: pre-line;">{{ $purchase->notes }}</p>
        </div>
    @endif

    {{-- 🔹 FOOTER --}}
    <div class="footer">
        <p>Thank you for your business!</p>
        <p>Generated on {{ now()->format('d M Y, H:i') }}</p>
        <p>{{ config('company.name', config('app.name')) }} • {{ config('company.email', 'info@example.com') }}</p>
    </div>
</div>
</body>
</html>
