<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Invoice #{{ $sale->id }}</title>
    <style>
        /* ===============================
           GLOBAL LAYOUT & TYPOGRAPHY
        =============================== */
        @page { margin: 30px 40px; }
        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 13px;
            color: #111827;
            background: #fff;
            margin: 0;
        }
        .container { width: 100%; max-width: 900px; margin: 0 auto; }

        h1, h2, h3, h4, h5, h6 {
            margin: 0;
            color: #1e293b;
        }

        p { margin: 2px 0; }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th, td {
            padding: 8px 10px;
            border: 1px solid #e5e7eb;
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
        .text-left { text-align: left; }

        /* ===============================
           HEADER SECTION
        =============================== */
        .header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 25px;
            border-bottom: 2px solid #1e40af;
            padding-bottom: 10px;
        }
        .header h1 {
            font-size: 26px;
            color: #1e40af;
            letter-spacing: 0.5px;
        }
        .brand {
            text-align: right;
        }
        .brand h3 {
            font-size: 18px;
            color: #111827;
        }
        .brand small {
            color: #6b7280;
        }

        /* ===============================
           TABLE & TOTALS
        =============================== */
        .items th { background: #f3f4f6; }
        .total-row td {
            font-weight: bold;
            background: #f9fafb;
        }
        .highlight {
            color: #16a34a;
        }
        .danger {
            color: #dc2626;
        }

        /* ===============================
           FOOTER
        =============================== */
        .footer {
            text-align: center;
            margin-top: 40px;
            padding-top: 12px;
            font-size: 12px;
            color: #6b7280;
            border-top: 1px solid #e5e7eb;
        }

        /* ===============================
           RESPONSIVE / PRINT STYLES
        =============================== */
        @media print {
            body { -webkit-print-color-adjust: exact; }
            .no-print { display: none; }
            .container { width: 100%; }
        }
    </style>
</head>
<body>
<div class="container">

    {{-- 🔹 Header --}}
    <div class="header">
        <div>
            <h1>INVOICE</h1>
            <p><strong>Invoice #:</strong> {{ $sale->id }}</p>
            <p><strong>Date:</strong> {{ optional($sale->sale_date)->format('Y-m-d') }}</p>
            @if($sale->user)
                <p><strong>Processed by:</strong> {{ $sale->user->name }}</p>
            @endif
        </div>
        <div class="brand">
            <h3>{{ config('app.name', 'Stock Manager') }}</h3>
            <small>Professional Stock Management System</small>
        </div>
    </div>

    {{-- 🔹 Customer Info --}}
    <div style="margin-bottom: 20px;">
        <h4 style="margin-bottom: 6px; color:#1f2937;">Bill To:</h4>
        @if($sale->customer)
            <p><strong>Name:</strong> {{ $sale->customer->name }}</p>
            @if($sale->customer->phone)
                <p><strong>Phone:</strong> {{ $sale->customer->phone }}</p>
            @endif
            @if($sale->customer->email)
                <p><strong>Email:</strong> {{ $sale->customer->email }}</p>
            @endif
        @else
            <p>Walk-in Customer</p>
        @endif
    </div>

    {{-- 🔹 Items --}}
    <table class="items">
        <thead>
            <tr>
                <th>Product</th>
                <th class="text-center">Qty</th>
                <th class="text-right">Unit Price (RWF)</th>
                <th class="text-right">Subtotal (RWF)</th>
            </tr>
        </thead>
        <tbody>
            @foreach($sale->items as $item)
                <tr>
                    <td>{{ $item->product->name ?? 'N/A' }}</td>
                    <td class="text-center">{{ $item->quantity }}</td>
                    <td class="text-right">{{ number_format($item->unit_price, 2) }}</td>
                    <td class="text-right">{{ number_format($item->subtotal, 2) }}</td>
                </tr>
            @endforeach
        </tbody>
        @php
            $balance = round(($sale->total_amount ?? 0) - ($sale->amount_paid ?? 0), 2);
        @endphp
        <tfoot>
            <tr class="total-row">
                <td colspan="3" class="text-right">Total:</td>
                <td class="text-right">{{ number_format($sale->total_amount, 2) }}</td>
            </tr>
            <tr class="total-row">
                <td colspan="3" class="text-right">Paid:</td>
                <td class="text-right highlight">{{ number_format($sale->amount_paid ?? 0, 2) }}</td>
            </tr>
            <tr class="total-row">
                <td colspan="3" class="text-right">Balance:</td>
                <td class="text-right {{ $balance > 0 ? 'danger' : 'highlight' }}">
                    {{ number_format($balance, 2) }}
                </td>
            </tr>
        </tfoot>
    </table>

    {{-- 🔹 Linked Loan --}}
    @if($sale->loan)
        <div style="margin-top: 30px;">
            <h4 style="margin-bottom: 6px; color:#1f2937;">Linked Loan</h4>
            <table>
                <tbody>
                    <tr>
                        <th>Loan Type</th>
                        <td>{{ ucfirst($sale->loan->type) }}</td>
                    </tr>
                    <tr>
                        <th>Amount</th>
                        <td>{{ number_format($sale->loan->amount, 2) }}</td>
                    </tr>
                    <tr>
                        <th>Status</th>
                        <td>{{ ucfirst($sale->loan->status) }}</td>
                    </tr>
                    <tr>
                        <th>Loan Date</th>
                        <td>{{ \Carbon\Carbon::parse($sale->loan->loan_date)->format('Y-m-d') }}</td>
                    </tr>
                    @if($sale->loan->due_date)
                        <tr>
                            <th>Due Date</th>
                            <td>{{ \Carbon\Carbon::parse($sale->loan->due_date)->format('Y-m-d') }}</td>
                        </tr>
                    @endif
                    @if($sale->loan->notes)
                        <tr>
                            <th>Notes</th>
                            <td>{{ $sale->loan->notes }}</td>
                        </tr>
                    @endif
                </tbody>
            </table>
        </div>
    @endif

    {{-- 🔹 Notes --}}
    @if($sale->notes)
        <div style="margin-top: 25px;">
            <h4 style="margin-bottom: 6px; color:#1f2937;">Notes:</h4>
            <p style="white-space: pre-line;">{{ $sale->notes }}</p>
        </div>
    @endif

    {{-- 🔹 Footer --}}
    <div class="footer">
        <p>Thank you for your business!</p>
        <p>Generated by {{ config('app.name') }} — {{ now()->format('d M Y, H:i') }}</p>
    </div>
</div>
</body>
</html>
