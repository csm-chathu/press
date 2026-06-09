<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8" />
<style>
  * { box-sizing: border-box; margin: 0; padding: 0; }
  body { font-family: DejaVu Sans, sans-serif; font-size: 11px; color: #1f2937; background: #fff; }
  .page { padding: 30px 40px 60px; }

  .header { display: table; width: 100%; margin-bottom: 20px; }
  .hd-l { display: table-cell; vertical-align: top; width: 60%; }
  .hd-r { display: table-cell; vertical-align: top; text-align: right; }
  .company { font-size: 22px; font-weight: bold; color: #92400e; letter-spacing: 1px; text-transform: uppercase; }
  .company-sub { color: #78716c; font-size: 9px; margin-top: 2px; letter-spacing: 0.5px; }
  .doc-title { font-size: 18px; font-weight: bold; color: #1f2937; text-transform: uppercase; letter-spacing: 1px; }
  .doc-num { font-size: 13px; font-weight: bold; color: #d97706; margin-top: 4px; }
  .badge { display: inline-block; padding: 3px 10px; border-radius: 12px; font-size: 9px; font-weight: bold; text-transform: uppercase; margin-top: 6px; }
  .badge-paid    { background: #d1fae5; color: #065f46; }
  .badge-pending { background: #fef3c7; color: #92400e; }
  .badge-partial { background: #dbeafe; color: #1e40af; }

  .divider       { border: none; border-top: 1px solid #e5e7eb; margin: 14px 0; }
  .divider-amber { border: none; border-top: 2px solid #d97706; margin: 14px 0; }

  .info-table { display: table; width: 100%; margin-bottom: 18px; }
  .info-l { display: table-cell; width: 50%; vertical-align: top; }
  .info-r { display: table-cell; width: 50%; vertical-align: top; }
  .info-block { margin-bottom: 12px; }
  .info-label { font-size: 8px; text-transform: uppercase; letter-spacing: 0.5px; color: #9ca3af; margin-bottom: 3px; }
  .info-val { font-size: 11px; font-weight: bold; color: #111827; }
  .info-sub { font-size: 10px; color: #6b7280; margin-top: 1px; }

  table.items { width: 100%; border-collapse: collapse; margin-bottom: 14px; }
  table.items thead tr { background: #92400e; }
  table.items thead th { color: #fff; font-size: 9px; font-weight: bold; padding: 7px 9px; text-align: left; text-transform: uppercase; letter-spacing: 0.5px; }
  table.items thead th.r { text-align: right; }
  table.items tbody tr:nth-child(even) { background: #fefce8; }
  table.items tbody td { padding: 8px 9px; font-size: 11px; border-bottom: 1px solid #f3f4f6; vertical-align: top; }
  table.items tbody td.r { text-align: right; }
  table.items tfoot td { padding: 6px 9px; font-size: 11px; }
  table.items tfoot td.r { text-align: right; }
  .item-sku { font-size: 9px; color: #9ca3af; margin-top: 1px; }

  .totals-box { float: right; width: 260px; margin-bottom: 16px; }
  .tot-row { display: table; width: 100%; margin-bottom: 4px; font-size: 11px; }
  .tot-l { display: table-cell; color: #6b7280; }
  .tot-r { display: table-cell; text-align: right; }
  .tot-grand { border-top: 2px solid #d97706; padding-top: 7px; margin-top: 5px; }
  .tot-grand .tot-l { font-weight: bold; color: #111827; font-size: 13px; }
  .tot-grand .tot-r { font-weight: bold; color: #d97706; font-size: 13px; }

  .payment-box { clear: both; background: #fffbeb; border: 1px solid #fde68a; border-radius: 6px; padding: 11px 14px; margin-bottom: 16px; }
  .pay-title { font-size: 9px; text-transform: uppercase; letter-spacing: 0.5px; font-weight: bold; color: #92400e; margin-bottom: 7px; }
  .pay-row { display: table; width: 100%; margin-bottom: 3px; font-size: 10px; }
  .pay-l { display: table-cell; color: #6b7280; }
  .pay-r { display: table-cell; text-align: right; font-weight: bold; }
  .pay-balance { color: #dc2626; }

  .notes-box { background: #f9fafb; border: 1px solid #e5e7eb; border-radius: 5px; padding: 9px 12px; margin-bottom: 16px; }
  .notes-label { font-size: 8px; text-transform: uppercase; letter-spacing: 0.5px; color: #9ca3af; margin-bottom: 4px; }
  .notes-val { font-size: 10px; color: #374151; }

  .footer { position: fixed; bottom: 0; left: 0; right: 0; background: #fffbeb; border-top: 2px solid #fde68a; padding: 8px 40px; display: table; width: 100%; }
  .footer-l { display: table-cell; font-size: 9px; color: #78716c; vertical-align: middle; }
  .footer-r { display: table-cell; text-align: right; font-size: 9px; color: #78716c; vertical-align: middle; }
</style>
</head>
<body>
<div class="page">

  {{-- Header --}}
  <div class="header">
    <div class="hd-l">
      <div class="company">LMUC Press</div>
      <div class="company-sub">Professional Printing Services</div>
    </div>
    <div class="hd-r">
      <div class="doc-title">Tax Invoice</div>
      <div class="doc-num">{{ $sale->invoice_number }}</div>
      @php
        $statusClass = match($sale->payment_status) {
          'paid'    => 'badge-paid',
          'pending' => 'badge-pending',
          default   => 'badge-partial',
        };
      @endphp
      <span class="badge {{ $statusClass }}">{{ strtoupper($sale->payment_status ?? 'pending') }}</span>
    </div>
  </div>

  <hr class="divider-amber" />

  {{-- Customer + Invoice Meta --}}
  <div class="info-table">
    <div class="info-l">
      <div class="info-block">
        <div class="info-label">Bill To</div>
        @if ($sale->customer)
          <div class="info-val">{{ $sale->customer->name }}</div>
          @if($sale->customer->phone)  <div class="info-sub">{{ $sale->customer->phone }}</div>@endif
          @if($sale->customer->email)  <div class="info-sub">{{ $sale->customer->email }}</div>@endif
          @if($sale->customer->address)<div class="info-sub">{{ $sale->customer->address }}</div>@endif
        @else
          <div class="info-val">Walk-in Customer</div>
        @endif
      </div>
    </div>
    <div class="info-r" style="text-align:right;">
      <div class="info-block">
        <div class="info-label">Invoice Date</div>
        <div class="info-val">{{ \Carbon\Carbon::parse($sale->sold_at)->format('d M Y') }}</div>
      </div>
      <div class="info-block">
        <div class="info-label">Invoice Time</div>
        <div class="info-val">{{ \Carbon\Carbon::parse($sale->sold_at)->format('h:i A') }}</div>
      </div>
      <div class="info-block">
        <div class="info-label">Served by</div>
        <div class="info-val">{{ $sale->user?->name ?? '—' }}</div>
      </div>
    </div>
  </div>

  <hr class="divider" />

  {{-- Items Table --}}
  <table class="items">
    <thead>
      <tr>
        <th style="width:42%;">Item</th>
        <th class="r" style="width:10%;">Qty</th>
        <th class="r" style="width:18%;">Unit Price</th>
        <th class="r" style="width:14%;">Discount</th>
        <th class="r" style="width:16%;">Total</th>
      </tr>
    </thead>
    <tbody>
      @foreach($sale->items as $item)
      <tr>
        <td>
          <div style="font-weight:bold;">{{ $item->product?->name ?? 'Unknown Item' }}</div>
          @if($item->product?->sku)<div class="item-sku">SKU: {{ $item->product->sku }}</div>@endif
        </td>
        <td class="r">{{ $item->quantity }}</td>
        <td class="r">LKR {{ number_format($item->unit_price, 2) }}</td>
        <td class="r">@if($item->discount > 0)LKR {{ number_format($item->discount, 2) }}@else—@endif</td>
        <td class="r" style="font-weight:bold;">LKR {{ number_format($item->total, 2) }}</td>
      </tr>
      @endforeach
    </tbody>
  </table>

  {{-- Totals --}}
  <div class="totals-box">
    @if(round($sale->subtotal, 2) != round($sale->total, 2))
    <div class="tot-row">
      <span class="tot-l">Subtotal</span>
      <span class="tot-r">LKR {{ number_format($sale->subtotal, 2) }}</span>
    </div>
    @endif
    @if($sale->discount > 0)
    <div class="tot-row">
      <span class="tot-l">Discount</span>
      <span class="tot-r">- LKR {{ number_format($sale->discount, 2) }}</span>
    </div>
    @endif
    @if($sale->tax > 0)
    <div class="tot-row">
      <span class="tot-l">Tax ({{ $sale->tax_rate }}%)</span>
      <span class="tot-r">+ LKR {{ number_format($sale->tax, 2) }}</span>
    </div>
    @endif
    <div class="tot-row tot-grand">
      <span class="tot-l">TOTAL</span>
      <span class="tot-r">LKR {{ number_format($sale->total, 2) }}</span>
    </div>
  </div>

  {{-- Payment Details --}}
  <div class="payment-box">
    <div class="pay-title">Payment Details</div>
    @if($sale->payments && $sale->payments->count() > 1)
      @foreach($sale->payments as $payment)
      <div class="pay-row">
        <span class="pay-l">{{ ucwords(str_replace('_', ' ', $payment->payment_method)) }}</span>
        <span class="pay-r">LKR {{ number_format($payment->amount, 2) }}</span>
      </div>
      @endforeach
      <div class="pay-row" style="border-top:1px solid #fde68a; padding-top:4px; margin-top:4px;">
        <span class="pay-l">Total Paid</span>
        <span class="pay-r">LKR {{ number_format($sale->amount_paid, 2) }}</span>
      </div>
    @else
    <div class="pay-row">
      <span class="pay-l">Payment Method</span>
      <span class="pay-r">{{ ucwords(str_replace('_', ' ', $sale->payment_method ?? 'N/A')) }}</span>
    </div>
    <div class="pay-row">
      <span class="pay-l">Amount Paid</span>
      <span class="pay-r">LKR {{ number_format($sale->amount_paid, 2) }}</span>
    </div>
    @endif
    @php $balance = round($sale->total - $sale->amount_paid, 2); @endphp
    @if($balance > 0.01)
    <div class="pay-row" style="border-top:1px solid #fde68a; padding-top:4px; margin-top:4px;">
      <span class="pay-l pay-balance" style="font-weight:bold;">Balance Due</span>
      <span class="pay-r pay-balance">LKR {{ number_format($balance, 2) }}</span>
    </div>
    @endif
  </div>

  @if($sale->notes)
  <div class="notes-box">
    <div class="notes-label">Notes</div>
    <div class="notes-val">{{ $sale->notes }}</div>
  </div>
  @endif

  <div style="text-align:center; font-size:10px; color:#6b7280; padding-top:8px;">
    Thank you for choosing LMUC Press!
  </div>
</div>

<div class="footer">
  <span class="footer-l">LMUC Press · Professional Printing Services</span>
  <span class="footer-r">Generated {{ now()->format('d M Y, h:i A') }}</span>
</div>
</body>
</html>
