<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\JobCard;
use App\Models\Quotation;
use App\Models\QuotationItem;
use App\Models\Sale;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class QuotationController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $quotations = Quotation::with(['customer:id,name,phone', 'createdBy:id,name'])
            ->when(!$user->isAdmin(), fn($q) => $q->where('branch_id', $user->branch_id))
            ->when($request->filled('search'), fn($q) => $q->where(function ($inner) use ($request) {
                $s = $request->input('search');
                $inner->where('quotation_number', 'like', "%$s%")
                    ->orWhere('title', 'like', "%$s%")
                    ->orWhereHas('customer', fn($c) => $c->where('name', 'like', "%$s%"));
            }))
            ->when($request->filled('status'), fn($q) => $q->where('status', $request->input('status')))
            ->when($request->filled('customer_id'), fn($q) => $q->where('customer_id', $request->input('customer_id')))
            ->latest()
            ->paginate($request->integer('per_page', 20));

        return response()->json($quotations);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'customer_id'           => 'nullable|exists:customers,id',
            'title'                 => 'required|string|max:255',
            'product_type'          => 'nullable|string|max:100',
            'paper_type'            => 'nullable|string|max:100',
            'gsm'                   => 'nullable|integer|min:1',
            'size'                  => 'nullable|string|max:50',
            'width_mm'              => 'nullable|numeric|min:0',
            'height_mm'             => 'nullable|numeric|min:0',
            'quantity'              => 'nullable|integer|min:1',
            'color_count'           => 'nullable|integer|min:1|max:8',
            'printing_method'       => 'nullable|string|max:50',
            'plate_cost'            => 'nullable|numeric|min:0',
            'paper_cost'            => 'nullable|numeric|min:0',
            'ink_cost'              => 'nullable|numeric|min:0',
            'finishing_cost'        => 'nullable|numeric|min:0',
            'labour_cost'           => 'nullable|numeric|min:0',
            'wastage_percent'       => 'nullable|numeric|min:0|max:100',
            'profit_margin_percent' => 'nullable|numeric|min:0|max:100',
            'tax_rate'              => 'nullable|numeric|min:0|max:100',
            'valid_until'           => 'nullable|date',
            'notes'                 => 'nullable|string',
            'terms'                 => 'nullable|string',
            'items'                 => 'nullable|array',
            'items.*.description'   => 'required_with:items|string',
            'items.*.quantity'      => 'required_with:items|integer|min:1',
            'items.*.unit_price'    => 'required_with:items|numeric|min:0',
        ]);

        $quotation = new Quotation($data);
        $quotation->quotation_number = Quotation::generateNumber();
        $quotation->branch_id        = $request->user()->branch_id;
        $quotation->created_by       = $request->user()->id;
        $quotation->status           = 'draft';
        $quotation->calculateTotals();
        $quotation->save();

        if (!empty($data['items'])) {
            foreach ($data['items'] as $i => $item) {
                QuotationItem::create([
                    'quotation_id' => $quotation->id,
                    'description'  => $item['description'],
                    'quantity'     => $item['quantity'],
                    'unit_price'   => $item['unit_price'],
                    'total'        => $item['quantity'] * $item['unit_price'],
                    'sort_order'   => $i,
                ]);
            }
        }

        AuditLog::record('quotation_created', "Quotation {$quotation->quotation_number} created", $quotation);

        return response()->json($quotation->load(['customer', 'items', 'createdBy:id,name']), 201);
    }

    public function show(Quotation $quotation)
    {
        $this->authorizeBranch($quotation->branch_id);
        return response()->json($quotation->load(['customer', 'items', 'createdBy:id,name', 'order']));
    }

    public function update(Request $request, Quotation $quotation)
    {
        $this->authorizeBranch($quotation->branch_id);

        if ($quotation->status === 'converted') {
            return response()->json(['message' => 'Converted quotations cannot be edited'], 422);
        }

        $data = $request->validate([
            'customer_id'           => 'nullable|exists:customers,id',
            'title'                 => 'required|string|max:255',
            'product_type'          => 'nullable|string|max:100',
            'paper_type'            => 'nullable|string|max:100',
            'gsm'                   => 'nullable|integer|min:1',
            'size'                  => 'nullable|string|max:50',
            'width_mm'              => 'nullable|numeric|min:0',
            'height_mm'             => 'nullable|numeric|min:0',
            'quantity'              => 'nullable|integer|min:1',
            'color_count'           => 'nullable|integer|min:1|max:8',
            'printing_method'       => 'nullable|string|max:50',
            'plate_cost'            => 'nullable|numeric|min:0',
            'paper_cost'            => 'nullable|numeric|min:0',
            'ink_cost'              => 'nullable|numeric|min:0',
            'finishing_cost'        => 'nullable|numeric|min:0',
            'labour_cost'           => 'nullable|numeric|min:0',
            'wastage_percent'       => 'nullable|numeric|min:0|max:100',
            'profit_margin_percent' => 'nullable|numeric|min:0|max:100',
            'tax_rate'              => 'nullable|numeric|min:0|max:100',
            'status'                => 'nullable|in:draft,sent,approved,rejected',
            'valid_until'           => 'nullable|date',
            'notes'                 => 'nullable|string',
            'terms'                 => 'nullable|string',
            'items'                 => 'nullable|array',
            'items.*.description'   => 'required_with:items|string',
            'items.*.quantity'      => 'required_with:items|integer|min:1',
            'items.*.unit_price'    => 'required_with:items|numeric|min:0',
        ]);

        $quotation->fill($data);
        $quotation->calculateTotals();
        $quotation->save();

        if (isset($data['items'])) {
            $quotation->items()->delete();
            foreach ($data['items'] as $i => $item) {
                QuotationItem::create([
                    'quotation_id' => $quotation->id,
                    'description'  => $item['description'],
                    'quantity'     => $item['quantity'],
                    'unit_price'   => $item['unit_price'],
                    'total'        => $item['quantity'] * $item['unit_price'],
                    'sort_order'   => $i,
                ]);
            }
        }

        AuditLog::record('quotation_updated', "Quotation {$quotation->quotation_number} updated", $quotation);

        return response()->json($quotation->load(['customer', 'items', 'createdBy:id,name']));
    }

    public function destroy(Quotation $quotation)
    {
        $this->authorizeBranch($quotation->branch_id);

        if ($quotation->status === 'converted') {
            return response()->json(['message' => 'Cannot delete a converted quotation'], 422);
        }

        AuditLog::record('quotation_deleted', "Quotation {$quotation->quotation_number} deleted", $quotation);
        $quotation->delete();

        return response()->json(['message' => 'Quotation deleted']);
    }

    public function downloadPdf(Quotation $quotation)
    {
        $this->authorizeBranch($quotation->branch_id);
        $quotation->load(['customer', 'items', 'createdBy:id,name']);

        $pdf = Pdf::loadView('pdf.quotation', ['quotation' => $quotation])->setPaper('A4');

        return $pdf->download("Quotation-{$quotation->quotation_number}.pdf");
    }

    public function convert(Request $request, Quotation $quotation)
    {
        $this->authorizeBranch($quotation->branch_id);

        if ($quotation->status === 'converted') {
            return response()->json(['message' => 'Already converted'], 422);
        }

        DB::beginTransaction();
        try {
            // Create a sales order from the quotation
            $invPrefix = 'INV-' . now()->format('Ymd') . '-';
            $lastInv   = Sale::withTrashed()
                ->whereDate('created_at', today())
                ->where('invoice_number', 'like', $invPrefix . '%')
                ->max('invoice_number');
            $invNext       = $lastInv ? ((int) substr($lastInv, -4)) + 1 : 1;
            $invoiceNumber = $invPrefix . str_pad($invNext, 4, '0', STR_PAD_LEFT);

            $sale = Sale::create([
                'branch_id'      => $quotation->branch_id,
                'invoice_number' => $invoiceNumber,
                'customer_id'    => $quotation->customer_id,
                'user_id'        => $request->user()->id,
                'quotation_id'   => $quotation->id,
                'order_type'     => 'from_quotation',
                'subtotal'       => $quotation->subtotal,
                'discount'       => 0,
                'tax'            => $quotation->tax,
                'tax_rate'       => $quotation->tax_rate,
                'total'          => $quotation->total,
                'payment_method' => 'cash',
                'payment_status' => 'pending',
                'amount_paid'    => 0,
                'status'         => 'draft',
                'order_status'   => 'new',
                'sold_at'        => now(),
            ]);

            $quotation->update(['status' => 'converted']);

            AuditLog::record('quotation_converted', "Quotation {$quotation->quotation_number} → Order {$sale->invoice_number}", $quotation);

            DB::commit();
            return response()->json(['order' => $sale, 'quotation' => $quotation]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => $e->getMessage()], 422);
        }
    }

    private function authorizeBranch(?int $branchId): void
    {
        $user = request()->user();
        if (!$user->isAdmin() && (int) $user->branch_id !== (int) $branchId) {
            abort(403, 'Forbidden for this branch.');
        }
    }
}
