<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Support\PaperYieldService;
use Illuminate\Http\Request;

class PaperYieldController extends Controller
{
    /**
     * Calculate pieces-per-sheet and sheets needed.
     *
     * POST /api/tools/paper-yield
     * {
     *   // Paper source — either a product_id OR explicit mm dimensions OR a named size
     *   "paper_product_id": 1,          // optional: lookup width/height from product
     *   "paper_size": "A3",             // optional: named size (A3, SRA3, etc.)
     *   "paper_w_mm": 420,              // optional: explicit width
     *   "paper_h_mm": 297,              // optional: explicit height
     *
     *   // Job (finished piece) dimensions — required
     *   "job_w_mm": 85,
     *   "job_h_mm": 55,
     *   // OR use a named size:
     *   "job_size": "A5",
     *
     *   // Optional
     *   "quantity": 1000,               // copies needed
     *   "wastage_percent": 5,           // %
     *   "bleed_mm": 3,                  // bleed per edge (default 3)
     *   "gutter_mm": 0                  // gap between pieces (default 0)
     * }
     */
    public function calculate(Request $request)
    {
        $data = $request->validate([
            'paper_product_id' => 'nullable|exists:products,id',
            'paper_size'       => 'nullable|string|max:20',
            'paper_w_mm'       => 'nullable|numeric|min:1',
            'paper_h_mm'       => 'nullable|numeric|min:1',
            'job_size'         => 'nullable|string|max:20',
            'job_w_mm'         => 'nullable|numeric|min:1',
            'job_h_mm'         => 'nullable|numeric|min:1',
            'quantity'         => 'nullable|integer|min:1',
            'wastage_percent'  => 'nullable|numeric|min:0|max:100',
            'bleed_mm'         => 'nullable|numeric|min:0',
            'gutter_mm'        => 'nullable|numeric|min:0',
        ]);

        // ── Resolve paper dimensions ──────────────────────────────────────
        [$paperW, $paperH] = $this->resolvePaperDimensions($data);
        if (!$paperW || !$paperH) {
            return response()->json([
                'message' => 'Provide paper_product_id, paper_size, or paper_w_mm + paper_h_mm.',
            ], 422);
        }

        // ── Resolve job dimensions ────────────────────────────────────────
        [$jobW, $jobH] = $this->resolveJobDimensions($data);
        if (!$jobW || !$jobH) {
            return response()->json([
                'message' => 'Provide job_size or job_w_mm + job_h_mm.',
            ], 422);
        }

        $bleed    = (float) ($data['bleed_mm']  ?? 3);
        $gutter   = (float) ($data['gutter_mm'] ?? 0);
        $quantity = (int)   ($data['quantity']   ?? 0);
        $wastage  = (float) ($data['wastage_percent'] ?? 0);

        // ── Calculate ─────────────────────────────────────────────────────
        if ($quantity > 0) {
            $result = PaperYieldService::sheetsNeeded($paperW, $paperH, $jobW, $jobH, $quantity, $wastage, $bleed, $gutter);
        } else {
            $result = PaperYieldService::piecesPerSheet($paperW, $paperH, $jobW, $jobH, $bleed, $gutter);
            $result['quantity']            = null;
            $result['wastage_percent']     = $wastage;
            $result['sheets_net']          = null;
            $result['wastage_sheets']      = null;
            $result['sheets_with_wastage'] = null;
        }

        // Attach paper product info if resolved from product
        if (!empty($data['paper_product_id'])) {
            $product = Product::select('id', 'name', 'sku', 'base_unit', 'bundle_size', 'stock_quantity', 'purchase_price')
                ->find($data['paper_product_id']);
            $result['paper_product'] = $product;

            if ($product && $product->bundle_size > 0 && !is_null($result['sheets_with_wastage'])) {
                $bundlesNeeded = (int) ceil($result['sheets_with_wastage'] / $product->bundle_size);
                $result['bundles_needed']     = $bundlesNeeded;
                $result['bundles_in_stock']   = (int) floor($product->stock_quantity / $product->bundle_size);
                $result['sheets_in_stock']    = (int) $product->stock_quantity;
                $result['stock_sufficient']   = $product->stock_quantity >= $result['sheets_with_wastage'];
            }
        }

        return response()->json($result);
    }

    /** Return all named paper sizes */
    public function sizes()
    {
        $sizes = [];
        foreach (PaperYieldService::SIZES as $name => [$w, $h]) {
            $sizes[] = ['name' => $name, 'width_mm' => $w, 'height_mm' => $h];
        }
        return response()->json($sizes);
    }

    // ── helpers ───────────────────────────────────────────────────────────

    private function resolvePaperDimensions(array $data): array
    {
        if (!empty($data['paper_product_id'])) {
            $product = Product::find($data['paper_product_id']);
            if ($product && $product->paper_size) {
                $mm = PaperYieldService::sizeToMm($product->paper_size);
                if ($mm) return $mm;
            }
        }

        if (!empty($data['paper_size'])) {
            $mm = PaperYieldService::sizeToMm($data['paper_size']);
            if ($mm) return $mm;
        }

        if (!empty($data['paper_w_mm']) && !empty($data['paper_h_mm'])) {
            return [(float) $data['paper_w_mm'], (float) $data['paper_h_mm']];
        }

        return [null, null];
    }

    private function resolveJobDimensions(array $data): array
    {
        if (!empty($data['job_size'])) {
            $mm = PaperYieldService::sizeToMm($data['job_size']);
            if ($mm) return $mm;
        }

        if (!empty($data['job_w_mm']) && !empty($data['job_h_mm'])) {
            return [(float) $data['job_w_mm'], (float) $data['job_h_mm']];
        }

        return [null, null];
    }
}
