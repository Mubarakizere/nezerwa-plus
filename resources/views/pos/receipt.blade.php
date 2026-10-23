<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Receipt #{{ $sale->id }} - {{ config('app.name', 'KingWine') }}</title>
    <style>
        /* Thermal Receipt Styling (80mm standard width) */
        @page {
            size: 80mm auto;
            margin: 0;
        }

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: 'Courier New', Courier, monospace, -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto;
            font-size: 12px;
            line-height: 1.35;
            color: #000;
            background-color: #f3f4f6;
            padding: 20px 0;
        }

        .receipt-container {
            width: 78mm;
            max-width: 100%;
            margin: 0 auto;
            padding: 12px;
            background: #fff;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.08);
            border-radius: 4px;
        }

        .text-center { text-align: center; }
        .text-right { text-align: right; }
        .text-left { text-align: left; }
        .font-bold { font-weight: bold; }
        .uppercase { text-transform: uppercase; }

        .header {
            margin-bottom: 10px;
            padding-bottom: 8px;
            border-bottom: 1px dashed #000;
        }

        .store-name {
            font-size: 16px;
            font-weight: 900;
            letter-spacing: 0.5px;
            margin-bottom: 3px;
        }

        .store-info {
            font-size: 10px;
            line-height: 1.25;
            color: #333;
        }

        .meta-table {
            width: 100%;
            font-size: 11px;
            margin: 6px 0;
            border-collapse: collapse;
        }

        .meta-table td {
            padding: 1.5px 0;
        }

        .dashed-line {
            border-top: 1px dashed #000;
            margin: 6px 0;
        }

        .double-line {
            border-top: 2px dashed #000;
            margin: 6px 0;
        }

        /* Items Table */
        .items-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 11px;
            margin: 6px 0;
        }

        .items-table th {
            text-align: left;
            padding-bottom: 4px;
            border-bottom: 1px solid #000;
            font-size: 10px;
            text-transform: uppercase;
        }

        .items-table td {
            padding: 3px 0;
            vertical-align: top;
        }

        .item-name {
            font-weight: bold;
            font-size: 11px;
        }

        .item-calc {
            font-size: 10px;
            color: #444;
        }

        /* Totals */
        .totals-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 11px;
            margin: 6px 0;
        }

        .totals-table td {
            padding: 2px 0;
        }

        .grand-total {
            font-size: 14px;
            font-weight: 900;
        }

        .gift-badge {
            display: inline-block;
            background: #000;
            color: #fff;
            padding: 2px 6px;
            font-size: 10px;
            font-weight: bold;
            border-radius: 2px;
            margin: 4px 0;
        }

        .footer {
            margin-top: 10px;
            padding-top: 8px;
            border-top: 1px dashed #000;
            font-size: 10px;
            line-height: 1.3;
        }

        .barcode {
            margin: 8px auto 4px auto;
            font-family: 'Libre Barcode 39', monospace, sans-serif;
            font-size: 26px;
            letter-spacing: 2px;
        }

        /* Screen Action Bar */
        .screen-actions {
            max-width: 78mm;
            margin: 12px auto 0 auto;
            display: flex;
            gap: 8px;
        }

        .btn {
            flex: 1;
            padding: 8px 12px;
            border-radius: 6px;
            font-size: 12px;
            font-weight: bold;
            cursor: pointer;
            border: none;
            text-align: center;
            text-decoration: none;
        }

        .btn-print {
            background: #4f46e5;
            color: #fff;
        }

        .btn-close {
            background: #e5e7eb;
            color: #374151;
        }

        /* Iframe preview adaptation */
        body.in-iframe {
            background-color: #fff !important;
            padding: 0 !important;
        }

        body.in-iframe .receipt-container {
            box-shadow: none !important;
            padding: 8px !important;
            width: 100% !important;
        }

        body.in-iframe .screen-actions {
            display: none !important;
        }

        /* Print Override */
        @media print {
            body {
                background: #fff;
                padding: 0;
            }
            .receipt-container {
                box-shadow: none;
                width: 100%;
                padding: 4px;
            }
            .screen-actions {
                display: none !important;
            }
        }
    </style>
</head>
<body>

    <div class="receipt-container">
        {{-- Header / Logo --}}
        <div class="header text-center">
            <div class="store-name">KING WINE</div>
            <div class="store-info">
                Retail & Stock Management<br>
                Kigali, Rwanda
            </div>
        </div>

        {{-- Sale Metadata --}}
        <table class="meta-table">
            <tr>
                <td><strong>Receipt #:</strong></td>
                <td class="text-right">#{{ $sale->id }}</td>
            </tr>
            <tr>
                <td><strong>Date:</strong></td>
                <td class="text-right">{{ $sale->created_at ? $sale->created_at->format('d/m/Y H:i') : now()->format('d/m/Y H:i') }}</td>
            </tr>
            <tr>
                <td><strong>Cashier:</strong></td>
                <td class="text-right">{{ $sale->user->name ?? 'Cashier' }}</td>
            </tr>
            <tr>
                <td><strong>{{ $sale->supplier_id ? 'Supplier:' : 'Customer:' }}</strong></td>
                <td class="text-right">
                    @if($sale->supplier)
                        {{ $sale->supplier->name }}
                    @else
                        {{ $sale->customer->name ?? 'Walk-in Customer' }}
                    @endif
                </td>
            </tr>

        </table>

        <div class="dashed-line"></div>

        {{-- Items List --}}
        <table class="items-table">
            <thead>
                <tr>
                    <th style="width: 55%;">Item</th>
                    <th class="text-center" style="width: 15%;">Qty</th>
                    <th class="text-right" style="width: 30%;">Total</th>
                </tr>
            </thead>
            <tbody>
                @foreach($sale->items as $item)
                    <tr>
                        <td colspan="3" style="padding-top: 4px;">
                            <div class="item-name">{{ $item->product->name ?? 'Unknown Item' }}</div>
                        </td>
                    </tr>
                    <tr>
                        <td class="item-calc" style="padding-bottom: 4px;">
                            @ {{ number_format($item->unit_price, 0) }} RWF
                        </td>
                        <td class="text-center" style="padding-bottom: 4px;">
                            {{ number_format($item->quantity, 0) }}
                        </td>
                        <td class="text-right font-bold" style="padding-bottom: 4px;">
                            {{ number_format($item->subtotal, 0) }}
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        <div class="dashed-line"></div>

        {{-- Totals Breakdown --}}
        <table class="totals-table">
            <tr>
                <td>Total Items:</td>
                <td class="text-right font-bold">{{ number_format($sale->items->sum('quantity'), 0) }}</td>
            </tr>
            <tr>
                <td>Subtotal:</td>
                <td class="text-right font-bold">{{ number_format($sale->total_amount, 0) }} RWF</td>
            </tr>

            @if($sale->isGift())
                <tr>
                    <td colspan="2" class="text-center">
                        <div class="gift-badge">COMPLIMENTARY BOSS GIFT</div>
                        @if($sale->gift_reason)
                            <div style="font-size: 10px; font-style: italic;">Reason: {{ $sale->gift_reason }}</div>
                        @endif
                    </td>
                </tr>
            @endif

            @if($sale->isSupplierOffset())
                <tr>
                    <td colspan="2" class="text-center">
                        <div class="gift-badge" style="background: #b45309;">SUPPLIER DEBT OFFSET</div>
                        <div style="font-size: 9px; margin-top: 2px;">Goods exchanged against Store Taken Loans (FIFO)</div>
                    </td>
                </tr>
            @endif

            <tr class="grand-total">

                <td style="padding-top: 4px;">TOTAL DUE:</td>
                <td class="text-right" style="padding-top: 4px;">
                    {{ $sale->isGift() ? '0' : number_format($sale->total_amount, 0) }} RWF
                </td>
            </tr>
        </table>

        <div class="dashed-line"></div>

        {{-- Payment Details --}}
        <table class="totals-table" style="font-size: 10px;">
            @if($sale->isGift())
                <tr>
                    <td>Paid via:</td>
                    <td class="text-right font-bold">Boss Gift (Zero Due)</td>
                </tr>
            @elseif($sale->payments->isNotEmpty())
                @foreach($sale->payments as $payment)
                    <tr>
                        <td class="uppercase">{{ $payment->method }}:</td>
                        <td class="text-right font-bold">{{ number_format($payment->amount, 0) }} RWF</td>
                    </tr>
                    @if($payment->reference)
                        <tr>
                            <td colspan="2" style="font-size: 9px; color: #555; padding-left: 5px;">
                                Ref: {{ $payment->reference }}
                            </td>
                        </tr>
                    @endif
                @endforeach
                <tr>
                    <td style="padding-top: 3px;">Total Paid:</td>
                    <td class="text-right font-bold" style="padding-top: 3px;">
                        {{ number_format($sale->amount_paid, 0) }} RWF
                    </td>
                </tr>
            @else
                <tr>
                    <td class="uppercase">{{ $sale->payment_channel ?? 'Cash' }}:</td>
                    <td class="text-right font-bold">{{ number_format($sale->amount_paid, 0) }} RWF</td>
                </tr>
            @endif

            @if(!$sale->isGift() && ($sale->total_amount - $sale->amount_paid) > 0)
                <tr style="color: #991b1b; font-weight: bold;">
                    <td>Balance Outstanding:</td>
                    <td class="text-right">{{ number_format($sale->total_amount - $sale->amount_paid, 0) }} RWF</td>
                </tr>
            @endif
        </table>

        {{-- Footer --}}
        <div class="footer text-center">
            <div class="barcode">*REC-{{ str_pad($sale->id, 6, '0', STR_PAD_LEFT) }}*</div>
            <p style="margin-top: 4px;">Thank you for your business!</p>
            <p>Goods once sold are not returnable without this valid receipt within 48h.</p>
            <p style="margin-top: 4px; font-size: 8px; color: #666;">Powered by King Wine POS</p>
        </div>
    </div>

    {{-- Screen Actions Bar (hidden when printing) --}}
    <div class="screen-actions">
        <button type="button" onclick="window.print()" class="btn btn-print">
            Print Thermal Receipt
        </button>
        <button type="button" onclick="window.close()" class="btn btn-close">
            Close
        </button>
    </div>

    {{-- Auto Print & Iframe detection script --}}
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            // Detect if displayed inside preview iframe
            if (window.self !== window.top) {
                document.body.classList.add('in-iframe');
            }

            const params = new URLSearchParams(window.location.search);
            // Only auto-print if explicitly requested and NOT embedded inside an inline preview iframe
            if (params.get('autoprint') === '1' && window.self === window.top) {
                setTimeout(() => {
                    window.print();
                }, 250);
            }
        });
    </script>
</body>
</html>
