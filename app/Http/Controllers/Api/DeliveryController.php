<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\DeliveryItem;
use App\Models\DeliveryNote;
use App\Models\JobCard;
use Illuminate\Http\Request;

class DeliveryController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $deliveries = DeliveryNote::with([
            'customer:id,name,phone',
            'dispatchedBy:id,name',
            'items',
        ])
            ->when(!$user->isAdmin(), fn($q) => $q->where('branch_id', $user->branch_id))
            ->when($request->filled('search'), fn($q) => $q->where(function ($inner) use ($request) {
                $s = $request->input('search');
                $inner->where('delivery_number', 'like', "%$s%")
                    ->orWhereHas('customer', fn($c) => $c->where('name', 'like', "%$s%"));
            }))
            ->when($request->filled('status'), fn($q) => $q->where('status', $request->input('status')))
            ->when($request->filled('date_from'), fn($q) => $q->where('delivery_date', '>=', $request->input('date_from')))
            ->when($request->filled('date_to'), fn($q) => $q->where('delivery_date', '<=', $request->input('date_to')))
            ->latest()
            ->paginate($request->integer('per_page', 20));

        return response()->json($deliveries);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'customer_id'            => 'nullable|exists:customers,id',
            'order_id'               => 'nullable|exists:sales,id',
            'delivery_date'          => 'required|date',
            'delivery_method'        => 'nullable|in:own_vehicle,courier,customer_pickup',
            'vehicle_details'        => 'nullable|string|max:255',
            'tracking_number'        => 'nullable|string|max:100',
            'delivery_address'       => 'nullable|string',
            'notes'                  => 'nullable|string',
            'items'                  => 'required|array|min:1',
            'items.*.job_card_id'    => 'nullable|exists:job_cards,id',
            'items.*.description'    => 'required|string',
            'items.*.quantity_ordered'   => 'required|integer|min:1',
            'items.*.quantity_delivered' => 'required|integer|min:0',
            'items.*.notes'          => 'nullable|string',
        ]);

        $totalQty     = collect($data['items'])->sum('quantity_ordered');
        $deliveredQty = collect($data['items'])->sum('quantity_delivered');

        $status = 'pending';
        if ($deliveredQty >= $totalQty) {
            $status = 'delivered';
        } elseif ($deliveredQty > 0) {
            $status = 'partial';
        }

        $delivery = DeliveryNote::create([
            ...$data,
            'branch_id'          => $request->user()->branch_id,
            'delivery_number'    => DeliveryNote::generateNumber(),
            'total_quantity'     => $totalQty,
            'delivered_quantity' => $deliveredQty,
            'status'             => $status,
            'dispatched_by'      => $request->user()->id,
            'dispatched_at'      => now(),
            'delivered_at'       => $status === 'delivered' ? now() : null,
        ]);

        foreach ($data['items'] as $item) {
            DeliveryItem::create([
                'delivery_note_id'   => $delivery->id,
                'job_card_id'        => $item['job_card_id'] ?? null,
                'description'        => $item['description'],
                'quantity_ordered'   => $item['quantity_ordered'],
                'quantity_delivered' => $item['quantity_delivered'],
                'notes'              => $item['notes'] ?? null,
            ]);

            // Update job card status if fully delivered
            if (!empty($item['job_card_id']) && $item['quantity_delivered'] >= $item['quantity_ordered']) {
                $jobCard = JobCard::find($item['job_card_id']);
                if ($jobCard && $jobCard->status !== 'delivered') {
                    $jobCard->update(['status' => 'delivered', 'completed_at' => now()]);
                }
            }
        }

        AuditLog::record('delivery_created', "Delivery {$delivery->delivery_number} created", $delivery);

        return response()->json($delivery->load(['customer', 'items.jobCard', 'dispatchedBy']), 201);
    }

    public function show(DeliveryNote $deliveryNote)
    {
        $this->authorizeBranch($deliveryNote->branch_id);
        return response()->json($deliveryNote->load(['customer', 'order', 'items.jobCard', 'dispatchedBy']));
    }

    public function update(Request $request, DeliveryNote $deliveryNote)
    {
        $this->authorizeBranch($deliveryNote->branch_id);

        $data = $request->validate([
            'status'             => 'nullable|in:pending,dispatched,delivered,partial,returned',
            'tracking_number'    => 'nullable|string|max:100',
            'vehicle_details'    => 'nullable|string|max:255',
            'delivery_address'   => 'nullable|string',
            'notes'              => 'nullable|string',
            'delivered_quantity' => 'nullable|integer|min:0',
        ]);

        if (($data['status'] ?? null) === 'delivered' && !$deliveryNote->delivered_at) {
            $data['delivered_at'] = now();
        }

        $deliveryNote->update($data);
        AuditLog::record('delivery_updated', "Delivery {$deliveryNote->delivery_number} → {$deliveryNote->status}", $deliveryNote);

        return response()->json($deliveryNote->load(['customer', 'items']));
    }

    private function authorizeBranch(?int $branchId): void
    {
        $user = request()->user();
        if (!$user->isAdmin() && (int) $user->branch_id !== (int) $branchId) {
            abort(403, 'Forbidden for this branch.');
        }
    }
}
