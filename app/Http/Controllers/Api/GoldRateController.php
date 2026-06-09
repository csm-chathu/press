<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\GoldRate;
use Illuminate\Http\Request;

class GoldRateController extends Controller
{
    /** Return today's rate + last 30 days history */
    public function index()
    {
        $today   = GoldRate::today();
        $history = GoldRate::with('createdBy:id,name')
            ->orderByDesc('date')
            ->take(30)
            ->get();

        return response()->json([
            'today'   => $today,
            'history' => $history,
            'karats'  => GoldRate::$karatPurity,
        ]);
    }

    /** Set or update today's gold rate (admin or authorized users only) */
    public function store(Request $request)
    {
        if (!$request->user()->canOverrideGoldRate()) {
            abort(403, 'You do not have permission to set gold rates.');
        }

        $data = $request->validate([
            'rate_per_gram' => 'required|numeric|min:1',
            'date'          => 'nullable|date',
        ]);

        $date    = $data['date'] ?? today()->toDateString();
        $oldRate = GoldRate::where('date', $date)->first();

        $rate = GoldRate::updateOrCreate(
            ['date' => $date],
            [
                'rate_per_gram' => $data['rate_per_gram'],
                'created_by'    => $request->user()->id,
            ]
        );

        AuditLog::record(
            'gold_rate_updated',
            "Gold rate for {$date} set to LKR {$data['rate_per_gram']}/g by {$request->user()->name}",
            $rate,
            $oldRate ? ['rate_per_gram' => $oldRate->rate_per_gram] : [],
            ['rate_per_gram' => $data['rate_per_gram']]
        );

        return response()->json($rate, 201);
    }

    /** Return today's rate only — lightweight for ProductModal */
    public function todayRate()
    {
        return response()->json(GoldRate::today());
    }

    /** Calculate price for weight + karat using today's rate */
    public function calculate(Request $request)
    {
        $request->validate([
            'weight' => 'required|numeric|min:0',
            'karat'  => 'required|string',
        ]);

        $rate = GoldRate::today();
        if (!$rate) {
            return response()->json(['message' => 'No gold rate set for today.'], 404);
        }

        $price = $rate->calculate((float) $request->weight, $request->karat);

        return response()->json([
            'price'         => $price,
            'rate_per_gram' => $rate->rate_per_gram,
            'karat_purity'  => GoldRate::$karatPurity[strtolower($request->karat)] ?? null,
            'date'          => $rate->date,
        ]);
    }
}
