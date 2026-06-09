<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class PressSettingController extends Controller
{
    private const CACHE_KEY = 'press_settings';

    public function show()
    {
        $settings = Cache::get(self::CACHE_KEY, []);
        return response()->json($settings ?: (object)[]);
    }

    public function store(Request $request)
    {
        $this->requireAdmin();

        $data = $request->validate([
            'company_name'           => 'sometimes|nullable|string|max:200',
            'registration_no'        => 'sometimes|nullable|string|max:100',
            'phone'                  => 'sometimes|nullable|string|max:50',
            'email'                  => 'sometimes|nullable|email|max:200',
            'address'                => 'sometimes|nullable|string|max:500',
            'city'                   => 'sometimes|nullable|string|max:100',
            'postal_code'            => 'sometimes|nullable|string|max:20',
            'default_wastage_percent'=> 'sometimes|numeric|min:0|max:50',
            'default_profit_margin'  => 'sometimes|numeric|min:0|max:100',
            'default_tax_percent'    => 'sometimes|numeric|min:0|max:30',
            'quotation_validity_days'=> 'sometimes|integer|min:1',
            'currency'               => 'sometimes|in:LKR,USD,EUR',
            'quotation_footer'       => 'sometimes|nullable|string|max:2000',
            'default_lead_time_days' => 'sometimes|integer|min:1',
            'low_stock_threshold'    => 'sometimes|integer|min:0',
            'require_proof_approval' => 'sometimes|boolean',
            'auto_create_prepress'   => 'sometimes|boolean',
            'default_delivery_method'=> 'sometimes|in:own_vehicle,courier,customer_pickup,third_party',
            'delivery_footer'        => 'sometimes|nullable|string|max:1000',
        ]);

        Cache::forever(self::CACHE_KEY, $data);

        return response()->json(['message' => 'Settings saved successfully', 'data' => $data]);
    }

    private function requireAdmin(): void
    {
        if (!request()->user()?->isAdmin()) {
            abort(403, 'Admin access required');
        }
    }
}
