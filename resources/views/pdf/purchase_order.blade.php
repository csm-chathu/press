<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8" />
<style>
  * { box-sizing: border-box; margin: 0; padding: 0; }
  body { font-family: DejaVu Sans, sans-serif; font-size: 11px; color: #1f2937; background: #fff; }
  .page { padding: 30px 40px 60px; }

  .header { display: table; width: 100%; margin-bottom: 20px; }
  .hd-l { display: table-cell; vertical-align: top; width: 55%; }
  .hd-r { display: table-cell; vertical-align: top; text-align: right; }
  .company { font-size: 22px; font-weight: bold; color: #92400e; letter-spacing: 1px; text-transform: uppercase; }
  .company-sub { color: #78716c; font-size: 9px; margin-top: 2px; letter-spacing: 0.5px; }
  .doc-title { font-size: 18px; font-weight: bold; color: #1f2937; text-transform: uppercase; letter-spacing: 2px; }
  .doc-num { font-size: 13px; font-weight: bold; color: #d97706; margin-top: 4px; }
  .badge { display: inline-block; padding: 3px 10px; border-radius: 12px; font-size: 9px; font-weight: bold; text-transform: uppercase; margin-top: 6px; }
  .badge-draft    { background: #f3f4f6; color: #6b7280; }
  .badge-sent     { background: #dbeafe; color: #1e40af; }
  .badge-approved { background: #d1fae5; color: #065f46; }
  .badge-received { background: #fce7f3; color: #9d174d; }

  .divider       { border: none; border-top: 1px solid #e5e7eb; margin: 14px 0; }
  .divider-amber { border: none; border-top: 2px solid #d97706; margin: 14px 0; }

  .info-table { display: table; width: 100%; margin-bottom: 18px; }
  .info-l { display: table-cell; width: 50%; vertical-align: top; }
  .info-r { display: table-cell; width: 50%; vertical-align: top; text-align: right; }
  .info-label { font-size: 8px; text-transform: uppercase; letter-spacing: 0.5px; color: #9ca3af; margin-bottom: 3px; }
  .info-val { font-size: 11px; font-weight: bold; color: #111827; }
  .info-sub { font-size: 10px; color: #6b7280; margin-top: 1px; }
  .info-block { margin-bottom: 10px; }

  table.items { width: 100%; border-collapse: collapse; margin-bottom: 14px; }
  table.items thead tr { background: #1f2937; }
  table.items thead th { color: #fff; font-size: 9px; font-weight: bold; padding: 8px 10px; text-align: left; text-transform: uppercase; letter-spacing: 0.5px; }
  table.items thead th.r { text-align: right; }
  table.items tbody tr:nth-child(even) { background: #f9fafb; }
  table.items tbody td { padding: 9px 10px; font-size: 11px; border-bottom: 1px solid #f3f4f6; vertical-align: top; }
  table.items tbody td.r { text-align: right; }
  .item-sku { font-size: 9px; color: #9ca3af; margin-top: 1px; }

  .totals-box { float: right; width: 260px; margin-bottom: 20px; }
  .tot-row { display: table; width: 100%; margin-bottom: 4px; font-size: 11px; }
  .tot-l { display: table-cell; color: #6b7280; }
  .tot-r { display: table-cell; text-align: right; }
  .tot-grand { border-top: 2px solid #d97706; padding-top: 7px; margin-top: 5px; }
  .tot-grand .tot-l { font-weight: bold; color: #111827; font-size: 13px; }
  .tot-grand .tot-r { font-weight: bold; color: #d97706; font-size: 13px; }

  .terms-box { clear: both; background: #f9fafb; border: 1px solid #e5e7eb; border-radius: 6px; padding: 11px 14px; margin-bottom: 14px; }
  .terms-title { font-size: 9px; text-transform: uppercase; letter-spacing: 0.5px; color: #6b7280; font-weight: bold; margin-bottom: 6px; }
  .terms-item { font-size: 10px; color: #4b5563; margin-bottom: 3px; }

  .notes-box { background: #fffbeb; border: 1px solid #fde68a; border-radius: 5px; padding: 9px 12px; margin-bottom: 14px; }
  .notes-label { font-size: 8px; text-transform: uppercase; letter-spacing: 0.5px; color: #9ca3af; margin-bottom: 4px; }
  .notes-val { font-size: 10px; color: #374151; }

  .sig-table { display: table; width: 100%; margin-top: 30px; }
  .sig-cell { display: table-cell; width: 45%; }
  .sig-line { border-top: 1px solid #6b7280; padding-top: 5px; margin-top: 40px; font-size: 9px; color: #6b7280; }

  .footer { position: fixed; bottom: 0; left: 0; right: 0; background: #1f2937; padding: 8px 40px; display: table; width: 100%; }
  .footer-l { display: table-cell; font-size: 9px; color: #9ca3af; vertical-align: middle; }
  .footer-r { display: table-cell; text-align: right; font-size: 9px; color: #9ca3af; vertical-align: middle; }
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
      <div class="doc-title">Purchase Order</div>
      <div class="doc-num">{{ $purchase->purchase_number }}</div>
      @php
        $statusClass = match($purchase->status) {
          'draft'            => 'badge-draft',
          'sent', 'approved' => 'badge-sent',
          'completed', 'received', 'partial_received' => 'badge-received',
          default            => 'badge-draft',
        };
      @endphp
      <span class="badge {{ $statusClass }}">{{ strtoupper($purchase->status) }}</span>
    </div>
  </div>

  <hr class="divider-amber" />

  {{-- Supplier + PO Meta --}}
  <div class="info-table">
    <div class="info-l">
      <div class="info-block">
        <div class="info-label">Supplier / Vendor</div>
        <div class="info-val">{{ $purchase->supplier?->name ?? '—' }}</div>
        @if($purchase->supplier?->phone)  <div class="info-sub">{{ $purchase->supplier->phone }}</div>@endif
        @if($purchase->supplier?->email)  <div class="info-sub">{{ $purchase->supplier->email }}</div>@endif
        @if($purchase->supplier?->address)<div class="info-sub">{{ $purchase->supplier->address }}</div>@endif
      </div>
    </div>
    <div class="info-r">
      <div class="info-block">
        <div class="info-label">PO Date</div>
        <div class="info-val">{{ \Carbon\Carbon::parse($purchase->purchased_at)->format('d M Y') }}</div>
      </div>
      <div class="info-block">
        <div class="info-label">PO Number</div>
        <div class="info-val">{{ $purchase->purchase_number }}</div>
      </div>
      <div class="info-block">
        <div class="info-label">Prepared by</div>
        <div class="info-val">{{ $purchase->user?->name ?? '—' }}</div>
      </div>
    </div>
  </div>

  <hr class="divider" />

  {{-- Items Table --}}
  <table class="items">
    <thead>
      <tr>
        <th style="width:8%;">#</th>
        <th style="width:46%;">Product / Description</th>
        <th class="r" style="width:14%;">Qty</th>
        <th class="r" style="width:16%;">Unit Cost</th>
        <th class="r" style="width:16%;">Line Total</th>
      </tr>
    </thead>
    <tbody>
      @foreach($purchase->items as $i => $item)
      <tr>
        <td>{{ $i + 1 }}</td>
        <td>
          <div style="font-weight:bold;">{{ $item->product?->name ?? 'Unknown Product' }}</div>
          @if($item->product?->sku)<div class="item-sku">SKU: {{ $item->product->sku }}</div>@endif
        </td>
        <td class="r">{{ $item->quantity }}</td>
        <td class="r">LKR {{ number_format($item->unit_cost, 2) }}</td>
        <td class="r" style="font-weight:bold;">LKR {{ number_format($item->total, 2) }}</td>
      </tr>
      @endforeach
    </tbody>
  </table>

  {{-- Totals --}}
  <div class="totals-box">
    <div class="tot-row">
      <span class="tot-l">Subtotal</span>
      <span class="tot-r">LKR {{ number_format($purchase->subtotal, 2) }}</span>
    </div>
    @if($purchase->tax > 0)
    <div class="tot-row">
      <span class="tot-l">Tax</span>
      <span class="tot-r">+ LKR {{ number_format($purchase->tax, 2) }}</span>
    </div>
    @endif
    <div class="tot-row tot-grand">
      <span class="tot-l">ORDER TOTAL</span>
      <span class="tot-r">LKR {{ number_format($purchase->total, 2) }}</span>
    </div>
  </div>

  @if($purchase->notes)
  <div class="notes-box">
    <div class="notes-label">Notes / Instructions</div>
    <div class="notes-val">{{ $purchase->notes }}</div>
  </div>
  @endif

  {{-- Terms --}}
  <div class="terms-box">
    <div class="terms-title">Terms &amp; Conditions</div>
    <div class="terms-item">1. Please confirm receipt of this Purchase Order within 2 business days.</div>
    <div class="terms-item">2. All goods must be delivered as per specified quantities and specifications.</div>
    <div class="terms-item">3. Invoice must reference PO number: <strong>{{ $purchase->purchase_number }}</strong></div>
    <div class="terms-item">4. LMUC Press reserves the right to reject goods not meeting quality standards.</div>
  </div>

  {{-- Signatures --}}
  <div class="sig-table">
    <div class="sig-cell">
      <div class="sig-line">Authorised by — LMUC Press</div>
    </div>
    <div class="sig-cell" style="width:10%;"></div>
    <div class="sig-cell">
      <div class="sig-line">Acknowledged by — Supplier</div>
    </div>
  </div>
</div>

<div class="footer">
  <span class="footer-l">LMUC Press · This is a computer-generated Purchase Order</span>
  <span class="footer-r">Generated {{ now()->format('d M Y, h:i A') }}</span>
</div>
</body>
</html>
