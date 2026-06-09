<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\QuotationTemplate;
use Illuminate\Http\Request;

class QuotationTemplateController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $templates = QuotationTemplate::when(
            !$user->isAdmin(),
            fn($q) => $q->where('branch_id', $user->branch_id)
        )->orderBy('name')->get();

        return response()->json($templates);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name'                  => 'required|string|max:255',
            'product_type'          => 'nullable|string|max:100',
            'paper_type'            => 'nullable|string|max:100',
            'gsm'                   => 'nullable|integer|min:1',
            'size'                  => 'nullable|string|max:50',
            'width_mm'              => 'nullable|numeric|min:0',
            'height_mm'             => 'nullable|numeric|min:0',
            'color_count'           => 'nullable|integer|min:1',
            'printing_method'       => 'nullable|string|max:50',
            'plate_cost'            => 'nullable|numeric|min:0',
            'paper_cost'            => 'nullable|numeric|min:0',
            'ink_cost'              => 'nullable|numeric|min:0',
            'finishing_cost'        => 'nullable|numeric|min:0',
            'labour_cost'           => 'nullable|numeric|min:0',
            'wastage_percent'       => 'nullable|numeric|min:0|max:100',
            'profit_margin_percent' => 'nullable|numeric|min:0',
            'tax_rate'              => 'nullable|numeric|min:0',
            'notes'                 => 'nullable|string',
            'terms'                 => 'nullable|string',
        ]);

        $template = QuotationTemplate::create([
            ...$data,
            'branch_id'  => $request->user()->branch_id,
            'created_by' => $request->user()->id,
        ]);

        return response()->json($template, 201);
    }

    public function destroy(QuotationTemplate $quotationTemplate)
    {
        $user = request()->user();
        if (!$user->isAdmin() && (int) $user->branch_id !== (int) $quotationTemplate->branch_id) {
            abort(403);
        }
        $quotationTemplate->delete();
        return response()->json(['message' => 'Template deleted']);
    }
}
