<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8" />
<title>Quotation {{ $quotation->quotation_number }}</title>
<style>
  * { margin: 0; padding: 0; box-sizing: border-box; }
  body { font-family: Arial, Helvetica, sans-serif; font-size: 11px; color: #333; background: #fff; }
  .page { padding: 30px 36px; }

  /* Header */
  .header { display: flex; justify-content: space-between; align-items: flex-start; border-bottom: 3px solid #d97706; padding-bottom: 16px; margin-bottom: 20px; }
  .company-name { font-size: 22px; font-weight: 700; color: #92400e; letter-spacing: 0.5px; }
  .company-sub  { font-size: 10px; color: #6b7280; margin-top: 2px; }
  .qt-block { text-align: right; }
  .qt-number { font-size: 16px; font-weight: 700; color: #1f2937; }
  .qt-label  { font-size: 9px; text-transform: uppercase; letter-spacing: 1px; color: #9ca3af; }
  .qt-meta   { font-size: 10px; color: #6b7280; margin-top: 4px; }

  /* Status badge */
  .badge { display: inline-block; padding: 2px 10px; border-radius: 20px; font-size: 9px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.8px; }
  .badge-draft    { background: #f3f4f6; color: #4b5563; }
  .badge-sent     { background: #dbeafe; color: #1d4ed8; }
  .badge-approved { background: #d1fae5; color: #065f46; }
  .badge-rejected { background: #fee2e2; color: #991b1b; }
  .badge-converted{ background: #fef3c7; color: #92400e; }

  /* Sections */
  .section { margin-bottom: 18px; }
  .section-title { font-size: 9px; text-transform: uppercase; letter-spacing: 1px; color: #9ca3af; font-weight: 700; margin-bottom: 8px; border-bottom: 1px solid #f3f4f6; padding-bottom: 4px; }

  /* Two-column info grid */
  .info-grid { display: flex; gap: 24px; }
  .info-col  { flex: 1; }
  .info-row  { margin-bottom: 6px; }
  .info-label{ font-size: 9px; text-transform: uppercase; letter-spacing: 0.5px; color: #9ca3af; }
  .info-value{ font-size: 11px; font-weight: 600; color: #111; }

  /* Specs table */
  .specs-table { width: 100%; border-collapse: collapse; }
  .specs-table td { padding: 5px 8px; font-size: 10px; border-bottom: 1px solid #f3f4f6; }
  .specs-table td:first-child { color: #6b7280; width: 35%; }
  .specs-table td:last-child { font-weight: 600; color: #111; }

  /* Cost table */
  .cost-table { width: 100%; border-collapse: collapse; }
  .cost-table tr td { padding: 5px 8px; font-size: 11px; }
  .cost-table tr td:last-child { text-align: right; }
  .cost-table .subtotal-row td { border-top: 1px solid #e5e7eb; font-weight: 600; }
  .cost-table .total-row   td { border-top: 2px solid #d97706; font-size: 14px; font-weight: 700; color: #92400e; padding-top: 8px; }
  .cost-table .muted td { color: #9ca3af; font-size: 9px; }

  /* Items table */
  .items-table { width: 100%; border-collapse: collapse; }
  .items-table th { background: #f9fafb; font-size: 9px; text-transform: uppercase; letter-spacing: 0.5px; color: #6b7280; padding: 6px 8px; text-align: left; border-bottom: 1px solid #e5e7eb; }
  .items-table td { padding: 6px 8px; font-size: 11px; border-bottom: 1px solid #f3f4f6; }
  .items-table td.num { text-align: right; }

  /* Notes */
  .notes-box { background: #f9fafb; border-radius: 6px; padding: 10px 12px; font-size: 10px; color: #4b5563; line-height: 1.5; white-space: pre-line; }

  /* Footer */
  .footer { margin-top: 30px; border-top: 1px solid #e5e7eb; padding-top: 12px; display: flex; justify-content: space-between; font-size: 9px; color: #9ca3af; }
</style>
</head>
<body>
<div class="page">

  <!-- Header -->
  <div class="header">
    <div>
      <div class="company-name">LMUC Press</div>
      <div class="company-sub">Printing Press &amp; Publishing Solutions</div>
    </div>
    <div class="qt-block">
      <div class="qt-label">Quotation</div>
      <div class="qt-number">{{ $quotation->quotation_number }}</div>
      <div class="qt-meta">
        Date: {{ $quotation->created_at->format('d M Y') }}<br>
        @if($quotation->valid_until)
        Valid until: {{ $quotation->valid_until->format('d M Y') }}
        @endif
      </div>
      <div style="margin-top:6px;">
        <span class="badge badge-{{ $quotation->status }}">{{ $quotation->status }}</span>
      </div>
    </div>
  </div>

  <!-- Customer & Meta -->
  <div class="section">
    <div class="section-title">Quotation For</div>
    <div class="info-grid">
      <div class="info-col">
        <div class="info-row">
          <div class="info-label">Customer</div>
          <div class="info-value">{{ $quotation->customer?->name ?? 'Walk-in Customer' }}</div>
        </div>
        @if($quotation->customer?->phone)
        <div class="info-row">
          <div class="info-label">Phone</div>
          <div class="info-value">{{ $quotation->customer->phone }}</div>
        </div>
        @endif
      </div>
      <div class="info-col">
        <div class="info-row">
          <div class="info-label">Job Title</div>
          <div class="info-value">{{ $quotation->title }}</div>
        </div>
        <div class="info-row">
          <div class="info-label">Prepared by</div>
          <div class="info-value">{{ $quotation->createdBy?->name ?? '—' }}</div>
        </div>
      </div>
    </div>
  </div>

  <!-- Print Specifications -->
  <div class="section">
    <div class="section-title">Print Specifications</div>
    <table class="specs-table">
      @if($quotation->product_type)
      <tr><td>Product Type</td><td>{{ ucfirst(str_replace('_',' ',$quotation->product_type)) }}</td></tr>
      @endif
      @if($quotation->paper_type)
      <tr><td>Paper Type</td><td>{{ $quotation->paper_type }}{{ $quotation->gsm ? ' '.$quotation->gsm.'gsm' : '' }}</td></tr>
      @endif
      @if($quotation->size)
      <tr><td>Size</td><td>{{ $quotation->size }}{{ ($quotation->width_mm && $quotation->height_mm) ? ' ('.$quotation->width_mm.' × '.$quotation->height_mm.' mm)' : '' }}</td></tr>
      @endif
      @if($quotation->quantity)
      <tr><td>Quantity</td><td>{{ number_format($quotation->quantity) }} pcs</td></tr>
      @endif
      @if($quotation->color_count)
      <tr><td>Colors</td><td>{{ $quotation->color_count }} color(s)</td></tr>
      @endif
      @if($quotation->printing_method)
      <tr><td>Printing Method</td><td>{{ ucfirst($quotation->printing_method) }}</td></tr>
      @endif
    </table>
  </div>

  <!-- Line Items (if any) -->
  @if($quotation->items->count())
  <div class="section">
    <div class="section-title">Line Items</div>
    <table class="items-table">
      <thead>
        <tr>
          <th>Description</th>
          <th style="text-align:right">Qty</th>
          <th style="text-align:right">Unit Price (Rs.)</th>
          <th style="text-align:right">Total (Rs.)</th>
        </tr>
      </thead>
      <tbody>
        @foreach($quotation->items as $item)
        <tr>
          <td>{{ $item->description }}</td>
          <td class="num">{{ number_format($item->quantity) }}</td>
          <td class="num">{{ number_format($item->unit_price, 2) }}</td>
          <td class="num">{{ number_format($item->total, 2) }}</td>
        </tr>
        @endforeach
      </tbody>
    </table>
  </div>
  @endif

  <!-- Cost Summary -->
  <div class="section">
    <div class="section-title">Cost Summary</div>
    <table class="cost-table" style="max-width:340px; margin-left:auto;">
      @if($quotation->plate_cost) <tr><td>Plate Cost</td><td>Rs. {{ number_format($quotation->plate_cost, 2) }}</td></tr> @endif
      @if($quotation->paper_cost) <tr><td>Paper Cost</td><td>Rs. {{ number_format($quotation->paper_cost, 2) }}</td></tr> @endif
      @if($quotation->ink_cost)   <tr><td>Ink Cost</td><td>Rs. {{ number_format($quotation->ink_cost, 2) }}</td></tr> @endif
      @if($quotation->finishing_cost) <tr><td>Finishing Cost</td><td>Rs. {{ number_format($quotation->finishing_cost, 2) }}</td></tr> @endif
      @if($quotation->labour_cost) <tr><td>Labour Cost</td><td>Rs. {{ number_format($quotation->labour_cost, 2) }}</td></tr> @endif
      @if($quotation->wastage_percent)
      <tr class="muted"><td>Wastage ({{ $quotation->wastage_percent }}%)</td><td>included</td></tr>
      @endif
      @if($quotation->profit_margin_percent)
      <tr class="muted"><td>Profit Margin ({{ $quotation->profit_margin_percent }}%)</td><td>included</td></tr>
      @endif
      <tr class="subtotal-row"><td>Subtotal</td><td>Rs. {{ number_format($quotation->subtotal, 2) }}</td></tr>
      @if($quotation->tax_rate)
      <tr><td>Tax ({{ $quotation->tax_rate }}%)</td><td>Rs. {{ number_format($quotation->tax, 2) }}</td></tr>
      @endif
      <tr class="total-row"><td>TOTAL</td><td>Rs. {{ number_format($quotation->total, 2) }}</td></tr>
      @if($quotation->quantity && $quotation->total)
      <tr class="muted"><td>Unit Price</td><td>Rs. {{ number_format($quotation->total / $quotation->quantity, 2) }}/pc</td></tr>
      @endif
    </table>
  </div>

  <!-- Notes & Terms -->
  @if($quotation->notes || $quotation->terms)
  <div class="section">
    @if($quotation->notes)
    <div style="margin-bottom:12px;">
      <div class="section-title">Notes</div>
      <div class="notes-box">{{ $quotation->notes }}</div>
    </div>
    @endif
    @if($quotation->terms)
    <div>
      <div class="section-title">Terms &amp; Conditions</div>
      <div class="notes-box">{{ $quotation->terms }}</div>
    </div>
    @endif
  </div>
  @endif

  <!-- Footer -->
  <div class="footer">
    <span>LMUC Press — Printing Press Management System</span>
    <span>Generated {{ now()->format('d M Y, H:i') }} · {{ $quotation->quotation_number }}</span>
  </div>

</div>
</body>
</html>
