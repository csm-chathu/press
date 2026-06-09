<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\GoldBuyback;
use App\Models\GoldRate;
use App\Models\ScrapItem;
use Illuminate\Http\Request;

class GoldBuybackController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();

        $query = GoldBuyback::with(['customer:id,name,phone,kyc_verified', 'user:id,name'])
            ->orderByDesc('created_at');

        if (!$user->isAdmin() && $user->branch_id) {
            $query->where('branch_id', $user->branch_id);
        }

        if ($s = $request->search) {
            $query->where(function ($q) use ($s) {
                $q->where('buyback_number', 'like', "%$s%")
                  ->orWhereHas('customer', fn($q) => $q->where('name', 'like', "%$s%"));
            });
        }

        if ($status = $request->status) {
            $query->where('status', $status);
        }

        return $query->paginate($request->per_page ?? 20);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'customer_id'           => 'required|exists:customers,id',
            'description'           => 'required|string|max:255',
            'item_type'             => 'required|in:jewelry,coin,bar,scrap,other',
            'gross_weight'          => 'required|numeric|min:0',
            'deduction_weight'      => 'nullable|numeric|min:0',
            'net_weight'            => 'required|numeric|min:0',
            'declared_karat'        => 'required|in:9k,14k,18k,22k,24k,unknown',
            'assay_method'          => 'nullable|in:visual,acid,xrf,fire_assay',
            'assay_karat'           => 'nullable|numeric|min:0|max:24',
            'xrf_reading'           => 'nullable|numeric|min:0|max:1',
            'melt_loss_percent'     => 'nullable|numeric|min:0|max:100',
            'assay_notes'           => 'nullable|string',
            'rate_per_gram'         => 'required|numeric|min:0',
            'buying_price_per_gram' => 'required|numeric|min:0',
            'offered_total'         => 'required|numeric|min:0',
            'final_price'           => 'required|numeric|min:0',
            'payment_method'        => 'required|in:cash,bank_transfer,cheque',
            'kyc_verified'          => 'boolean',
            'status'                => 'required|in:pending,approved,completed,rejected',
            'notes'                 => 'nullable|string',
            'create_scrap'          => 'boolean',
        ]);

        $data['user_id']   = $request->user()->id;
        $data['branch_id'] = $request->user()->branch_id;
        $data['gold_rate_id'] = GoldRate::today()?->id;

        $buyback = GoldBuyback::create($data);

        // Auto-create scrap item if status is completed and user opted in
        if (($data['status'] === 'completed' || ($data['create_scrap'] ?? false)) && $buyback->net_weight > 0) {
            ScrapItem::create([
                'description'    => $buyback->description,
                'source_type'    => 'buyback',
                'buyback_id'     => $buyback->id,
                'gold_rate_id'   => $buyback->gold_rate_id,
                'branch_id'      => $buyback->branch_id,
                'user_id'        => $buyback->user_id,
                'karat'          => $buyback->declared_karat !== 'unknown' ? $buyback->declared_karat : 'mixed',
                'weight_g'       => $buyback->net_weight,
                'estimated_value'=> $buyback->final_price,
                'status'         => 'available',
            ]);
        }

        AuditLog::record('buyback_created', "Buy-back {$buyback->buyback_number} created", $buyback, null, [
            'buyback_number' => $buyback->buyback_number,
            'customer_id'    => $buyback->customer_id,
            'final_price'    => $buyback->final_price,
        ]);

        return response()->json($buyback->load('customer:id,name'), 201);
    }

    public function update(Request $request, GoldBuyback $goldBuyback)
    {
        $data = $request->validate([
            'status'       => 'sometimes|in:pending,approved,completed,rejected',
            'assay_method' => 'nullable|in:visual,acid,xrf,fire_assay',
            'assay_karat'  => 'nullable|numeric|min:0|max:24',
            'xrf_reading'  => 'nullable|numeric|min:0|max:1',
            'melt_loss_percent' => 'nullable|numeric|min:0|max:100',
            'assay_notes'  => 'nullable|string',
            'final_price'  => 'nullable|numeric|min:0',
            'kyc_verified' => 'boolean',
            'notes'        => 'nullable|string',
        ]);

        $old = $goldBuyback->only(['status', 'final_price', 'kyc_verified']);
        $goldBuyback->update($data);

        // Create scrap if newly completed and no scrap yet
        if (isset($data['status']) && $data['status'] === 'completed' && !$goldBuyback->scrapItem) {
            ScrapItem::create([
                'description'    => $goldBuyback->description,
                'source_type'    => 'buyback',
                'buyback_id'     => $goldBuyback->id,
                'gold_rate_id'   => $goldBuyback->gold_rate_id,
                'branch_id'      => $goldBuyback->branch_id,
                'user_id'        => $request->user()->id,
                'karat'          => $goldBuyback->declared_karat !== 'unknown' ? $goldBuyback->declared_karat : 'mixed',
                'weight_g'       => $goldBuyback->net_weight,
                'estimated_value'=> $goldBuyback->final_price,
                'status'         => 'available',
            ]);
        }

        AuditLog::record('buyback_updated', "Buy-back {$goldBuyback->buyback_number} updated", $goldBuyback, $old, $data);

        return response()->json($goldBuyback->load('customer:id,name'));
    }

    public function destroy(GoldBuyback $goldBuyback)
    {
        AuditLog::record('buyback_deleted', "Buy-back {$goldBuyback->buyback_number} deleted", $goldBuyback, $goldBuyback->toArray(), null);
        $goldBuyback->delete();
        return response()->noContent();
    }
}
