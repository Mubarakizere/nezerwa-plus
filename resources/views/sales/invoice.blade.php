<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Invoice #{{ $sale->id }}</title>
    <style>
        /* ====== PAGE & BASE ====== */
        @page { margin: 28px 36px; }
        * { box-sizing: border-box; }
        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 12.5px;
            color: #111827;
            margin: 0;
            background: #fff;
        }
        .container { width: 100%; max-width: 900px; margin: 0 auto; }

        h1,h2,h3,h4 { margin: 0; color:#1f2937; }
        p { margin: 2px 0; }

        table { width: 100%; border-collapse: collapse; }
        th, td { padding: 8px 10px; border: 1px solid #e5e7eb; vertical-align: top; }
        th { background: #f3f4f6; color:#111827; text-align: left; font-weight: 600; }
        .text-right{ text-align:right; } .text-center{ text-align:center; } .text-left{ text-align:left; }

        /* ====== HEADER (table for dompdf safety) ====== */
        .header-wrap { margin-bottom: 18px; border-bottom: 2px solid #1e40af; padding-bottom: 10px; }
        .header-table { width:100%; border:0; border-collapse: collapse; }
        .header-table td { border:0; padding:0; vertical-align: top; }
        .title { font-size: 26px; color:#1e40af; letter-spacing:.3px; }
        .brand { text-align: right; }
        .brand h3 { font-size: 18px; }
        .muted { color:#6b7280; }

        /* ====== BADGES / CHIPS ====== */
        .chip { display:inline-block; padding:2px 8px; border-radius: 999px; font-size: 11px; font-weight: 700; border:1px solid transparent; }
        .chip-green { background:#dcfce7; color:#166534; border-color:#86efac; }
        .chip-yellow{ background:#fef9c3; color:#854d0e; border-color:#fde68a; }
        .chip-red   { background:#fee2e2; color:#991b1b; border-color:#fecaca; }
        .chip-blue  { background:#dbeafe; color:#1e40af; border-color:#bfdbfe; }
        .chip-purple{ background:#efe2ff; color:#5b21b6; border-color:#e9d5ff; }
        .chip-gray  { background:#f3f4f6; color:#374151; border-color:#e5e7eb; }

        /* ====== SECTIONS ====== */
        .section { margin-top: 16px; }
        .box { border:1px solid #e5e7eb; border-radius: 6px; padding: 10px; }
        .kv td { border:0; padding: 4px 0; }

        /* ====== ITEMS ====== */
        .items th { background: #f3f4f6; }
        .tfoot td { font-weight: 700; background:#f9fafb; }
        .ok { color:#16a34a; } .bad { color:#dc2626; }

        /* ====== FOOTER ====== */
        .footer { text-align:center; margin-top: 26px; padding-top: 10px; font-size:12px; color:#6b7280; border-top:1px solid #e5e7eb; }

        /* ====== PRINT ====== */
        @media print { body { -webkit-print-color-adjust: exact; } .no-print{ display:none; } }
    </style>
</head>
<body>
@php
    use Carbon\Carbon;
    $balance = round(($sale->total_amount ?? 0) - ($sale->amount_paid ?? 0), 2);
    $status  = $sale->status ?? 'pending';
    $channel = strtolower($sale->payment_channel ?? 'cash');
    $ref     = $sale->method ?: '-';

    $statusClass = $status === 'completed' ? 'chip-green' : ($status === 'pending' ? 'chip-yellow' : 'chip-red');
    $channelClass = $channel === 'cash' ? 'chip-green' : ($channel === 'bank' ? 'chip-blue' : ($channel === 'momo' ? 'chip-purple' : 'chip-gray'));

    $companyName = config('company.name', config('app.name', 'Stock Manager'));
    $companyPhone = config('company.phone', null);
    $companyEmail = config('company.email', null);
    $companyAddr  = config('company.address', null);
    $companyTin   = config('company.tax_id', null);

    $logo = public_path('banner.png');
    $hasLogo = @file_exists($logo);
@endphp

<div class="container">

    {{-- HEADER --}}
    <div class="header-wrap">
        <table class="header-table">
            <tr>
                <td>
                    <h1 class="title">INVOICE</h1>
                    <p><strong>Invoice #:</strong> {{ $sale->id }}</p>
                    <p><strong>Date:</strong> {{ optional($sale->sale_date)->format('Y-m-d') }}</p>
                    @if($sale->user)
                        <p><strong>Processed by:</strong> {{ $sale->user->name }}</p>
                    @endif>
                    <p>
                        <span class="chip {{ $statusClass }}">{{ strtoupper($status) }}</span>
                        <span class="chip {{ $channelClass }}">{{ strtoupper($channel) }}</span>
                    </p>
                </td>
                <td class="brand">
                    @if($hasLogo)
                        <img src="{{ $logo }}" alt="Logo" style="max-height:60px; margin-bottom:6px;">
                    @endif
                    <h3>{{ $companyName }}</h3>
                    @if($companyAddr)<div class="muted">{{ $companyAddr }}</div>@endif
                    @if($companyPhone || $companyEmail)
                        <div class="muted">
                            @if($companyPhone) {{ $companyPhone }} @endif
                            @if($companyPhone && $companyEmail) &nbsp;•&nbsp; @endif
                            @if($companyEmail) {{ $companyEmail }} @endif
                        </div>
                    @endif
                    @if($companyTin)
                        <div class="muted">TIN: {{ $companyTin }}</div>
                    @endif
                </td>
            </tr>
        </table>
    </div>

    {{-- BILL TO + PAYMENT DETAILS --}}
    <table class="section" style="border:0;">
        <tr>
            <td style="width:55%; border:0; padding-right:8px;">
                <div class="box">
                    <h4 style="margin-bottom:6px;">Bill To</h4>
                    @if($sale->customer)
                        <table class="kv">
                            <tr><td><strong>Name:</strong></td><td>{{ $sale->customer->name }}</td></tr>
                            @if($sale->customer->phone)
                                <tr><td><strong>Phone:</strong></td><td>{{ $sale->customer->phone }}</td></tr>
                            @endif
                            @if($sale->customer->email)
                                <tr><td><strong>Email:</strong></td><td>{{ $sale->customer->email }}</td></tr>
                            @endif
                        </table>
                    @else
                        <p>Walk-in Customer</p>
                    @endif
                </div>
            </td>
            <td style="width:45%; border:0; padding-left:8px;">
                <div class="box">
                    <h4 style="margin-bottom:6px;">Payment Details</h4>
                    <table class="kv">
                        <tr><td><strong>Channel:</strong></td><td><span class="chip {{ $channelClass }}">{{ strtoupper($channel) }}</span></td></tr>
                        <tr><td><strong>Reference:</strong></td><td>{{ $ref }}</td></tr>
                        <tr><td><strong>Recorded:</strong></td><td>{{ now()->format('Y-m-d H:i') }}</td></tr>
                    </table>
                </div>
            </td>
        </tr>
    </table>

    {{-- ITEMS --}}
    <div class="section">
        <table class="items">
            <thead>
                <tr>
                    <th>Product</th>
                    <th class="text-center" style="width:90px;">Qty</th>
                    <th class="text-right"  style="width:140px;">Unit Price (RWF)</th>
                    <th class="text-right"  style="width:160px;">Subtotal (RWF)</th>
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
            <tfoot class="tfoot">
                <tr>
                    <td colspan="3" class="text-right">Total:</td>
                    <td class="text-right">{{ number_format($sale->total_amount, 2) }}</td>
                </tr>
                <tr>
                    <td colspan="3" class="text-right">Paid:</td>
                    <td class="text-right ok">{{ number_format($sale->amount_paid ?? 0, 2) }}</td>
                </tr>
                <tr>
                    <td colspan="3" class="text-right">Balance:</td>
                    <td class="text-right {{ $balance > 0 ? 'bad' : 'ok' }}">{{ number_format($balance, 2) }}</td>
                </tr>
            </tfoot>
        </table>
    </div>

    {{-- LINKED LOAN (optional) --}}
    @if($sale->loan)
        <div class="section">
            <h4 style="margin:6px 0;">Linked Loan</h4>
            <table>
                <tbody>
                    <tr><th style="width:180px;">Loan Type</th><td>{{ ucfirst($sale->loan->type) }}</td></tr>
                    <tr><th>Amount</th><td>{{ number_format($sale->loan->amount, 2) }}</td></tr>
                    <tr><th>Status</th><td>{{ ucfirst($sale->loan->status) }}</td></tr>
                    <tr><th>Loan Date</th><td>{{ \Carbon\Carbon::parse($sale->loan->loan_date)->format('Y-m-d') }}</td></tr>
                    @if($sale->loan->due_date)
                        <tr><th>Due Date</th><td>{{ \Carbon\Carbon::parse($sale->loan->due_date)->format('Y-m-d') }}</td></tr>
                    @endif
                    @if($sale->loan->notes)
                        <tr><th>Notes</th><td>{{ $sale->loan->notes }}</td></tr>
                    @endif
                </tbody>
            </table>
        </div>
    @endif

    {{-- NOTES --}}
    @if($sale->notes)
        <div class="section">
            <h4 style="margin-bottom:6px;">Notes</h4>
            <div class="box" style="white-space: pre-line;">{{ $sale->notes }}</div>
        </div>
    @endif

    {{-- SIGNATURES --}}
    <div class="section">
        <table style="border:0; margin-top:10px;">
            <tr>
                <td style="border:0; width:50%; padding-right:12px;">
                    <div class="box" style="height:84px;">
                        <strong>Customer Signature</strong>
                        <div style="margin-top:40px; border-top:1px solid #e5e7eb;"></div>
                    </div>
                </td>
                <td style="border:0; width:50%; padding-left:12px;">
                    <div class="box" style="height:84px;">
                        <strong>Cashier Signature</strong>
                        <div style="margin-top:40px; border-top:1px solid #e5e7eb;"></div>
                    </div>
                </td>
            </tr>
        </table>
    </div>

    {{-- FOOTER --}}
    <div class="footer">
        <p>Thank you for your business!</p>
        <p>Generated by {{ $companyName }} — {{ now()->format('d M Y, H:i') }}</p>
    </div>
</div>
</body>
</html>
