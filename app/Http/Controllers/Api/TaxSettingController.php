<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\TaxSetting;
use Illuminate\Http\Request;

class TaxSettingController extends Controller
{
    public function index()
    {
        return response()->json(TaxSetting::all());
    }

    public function store(Request $request)
    {
        $this->requireAdmin();
        $data = $request->validate([
            'name'        => 'required|string|max:100',
            'rate'        => 'required|numeric|min:0|max:100',
            'applies_to'  => 'required|in:all,gold_only,gemstone_only,making_charges',
            'is_active'   => 'boolean',
            'description' => 'nullable|string',
        ]);
        return response()->json(TaxSetting::create($data), 201);
    }

    public function update(Request $request, TaxSetting $taxSetting)
    {
        $this->requireAdmin();
        $data = $request->validate([
            'name'        => 'sometimes|string|max:100',
            'rate'        => 'sometimes|numeric|min:0|max:100',
            'applies_to'  => 'sometimes|in:all,gold_only,gemstone_only,making_charges',
            'is_active'   => 'boolean',
            'description' => 'nullable|string',
        ]);
        $taxSetting->update($data);
        return response()->json($taxSetting);
    }

    public function destroy(TaxSetting $taxSetting)
    {
        $this->requireAdmin();
        $taxSetting->delete();
        return response()->json(['message' => 'Tax setting deleted']);
    }

    private function requireAdmin()
    {
        if (!request()->user()->isAdmin()) {
            abort(403, 'Admin access required');
        }
    }
}
