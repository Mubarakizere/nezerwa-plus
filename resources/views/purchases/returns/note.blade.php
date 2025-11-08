<!doctype html>
<html>
<head>
  <meta charset="utf-8">
  <title>Credit Note #{{ $ret->id }}</title>
  <style>
    body{font-family:ui-sans-serif,system-ui,Segoe UI,Roboto,Ubuntu,Cantarell,Noto Sans,sans-serif}
    .muted{color:#6b7280}
    table{width:100%;border-collapse:collapse;margin-top:12px}
    th,td{padding:8px;border-bottom:1px solid #e5e7eb;font-size:14px;text-align:left}
    .right{text-align:right}
  </style>
</head>
<body>
  <h2>Credit Note – Return #{{ $ret->id }}</h2>
  <p class="muted">
    Supplier: <strong>{{ $supplier->name ?? '—' }}</strong><br>
    Date: {{ optional($ret->return_date)->format('M j, Y') ?? '—' }}<br>
    Channel: {{ strtoupper($ret->payment_channel ?? '-') }} | Ref: {{ $ret->method ?: '—' }}<br>
    Processed by: {{ $ret->user->name ?? '—' }}
  </p>

  <table>
    <thead>
      <tr>
        <th>Product</th>
        <th class="right">Qty</th>
        <th class="right">Unit Cost</th>
        <th class="right">Line Total</th>
      </tr>
    </thead>
    <tbody>
      @foreach($ret->items as $i)
        <tr>
          <td>{{ $i->product->name ?? '#'.$i->product_id }}</td>
          <td class="right">{{ number_format($i->quantity,2) }}</td>
          <td class="right">RWF {{ number_format($i->unit_cost,2) }}</td>
          <td class="right">RWF {{ number_format($i->total_cost,2) }}</td>
        </tr>
      @endforeach
    </tbody>
    <tfoot>
      <tr>
        <td colspan="3" class="right"><strong>Total Return</strong></td>
        <td class="right"><strong>RWF {{ number_format($ret->total_amount,2) }}</strong></td>
      </tr>
      <tr>
        <td colspan="3" class="right">Cash Refund</td>
        <td class="right">RWF {{ number_format($ret->refund_amount,2) }}</td>
      </tr>
    </tfoot>
  </table>
</body>
</html>
