<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Support\CloudinaryService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ProductController extends Controller
{
    public function index()
    {
        $user = request()->user();
        $products = Product::with(['category:id,name', 'supplier:id,name', 'taxSetting:id,name,rate'])
            ->withSum(['openBottles as open_bottles_remaining_ml' => fn($q) => $q
                ->where('status', 'open')
                ->when(!$user->isAdmin(), fn($q2) => $q2->where('branch_id', $user->branch_id))
            ], 'remaining_volume_ml')
            ->when(request('search'), fn($q, $s) => $q->where(function ($inner) use ($s) {
                $inner->where('name', 'like', "%$s%")
                    ->orWhere('sku', 'like', "%$s%")
                    ->orWhere('barcode', 'like', "%$s%")
                    ->orWhere('brand', 'like', "%$s%");
            }))
            ->when(request('category_id'), fn($q, $c) => $q->where('category_id', $c))
            ->when(request('product_type'), fn($q, $type) => $q->where('product_type', $type))
            ->when(request('low_stock'), fn($q) => $q->whereColumn('stock_quantity', '<=', 'min_stock_level'))
            ->latest()
            ->paginate(request('per_page', 20));
        return response()->json($products);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name'                    => 'required|string|max:200',
            'description'             => 'nullable|string',
            'category_id'             => 'required|exists:categories,id',
            'product_type'            => 'required|string|max:50',
            'brand'                   => 'nullable|string|max:100',
            'unit_type'               => 'nullable|string|max:50',
            'base_unit'               => 'nullable|string|max:50',
            'selling_variants'        => 'nullable|string',
            'shot_variants'           => 'nullable|string',
            'purchase_price'          => 'required|numeric|min:0',
            'selling_price'           => 'required|numeric|min:0',
            'stock_quantity'          => 'required|integer|min:0',
            'min_stock_level'         => 'required|integer|min:0',
            'is_active'               => 'boolean',
            'bottle_deposit_required' => 'boolean',
            'bottle_deposit_amount'   => 'nullable|numeric|min:0',
            'supplier_id'             => 'nullable|exists:suppliers,id',
            'tax_setting_id'          => 'nullable|exists:tax_settings,id',
            'barcode'                 => 'nullable|string|max:100|unique:products,barcode',
            'image'                   => 'nullable|image|max:2048',
        ]);

        $data['sku'] = 'NEW-' . uniqid('', true); // temp unique; replaced below with numeric ID
        $data['branch_id'] = $request->user()->branch_id;
        $data['selling_variants'] = isset($data['selling_variants']) ? trim($data['selling_variants']) : null;
        $data['shot_variants'] = json_decode($request->input('shot_variants', '[]'), true) ?? [];
        $data['bottle_deposit_required'] = $request->boolean('bottle_deposit_required');
        $data['is_active'] = $request->boolean('is_active', true);

        if ($request->hasFile('image')) {
            $uploaded = CloudinaryService::uploadProductImage($request->file('image'));
            $data['image'] = $uploaded['url'];
            $data['image_public_id'] = $uploaded['public_id'];
        }

        $product = Product::create($data);
        $product->update(['sku' => str_pad($product->id, 6, '0', STR_PAD_LEFT)]);
        return response()->json($product->fresh(), 201);
    }

    public function show(Product $product)
    {
        return response()->json($product->load(['category', 'supplier']));
    }

    public function update(Request $request, Product $product)
    {
        $data = $request->validate([
            'name'                    => 'required|string|max:200',
            'description'             => 'nullable|string',
            'category_id'             => 'required|exists:categories,id',
            'product_type'            => 'required|string|max:50',
            'brand'                   => 'nullable|string|max:100',
            'unit_type'               => 'nullable|string|max:50',
            'base_unit'               => 'nullable|string|max:50',
            'selling_variants'        => 'nullable|string',
            'shot_variants'           => 'nullable|string',
            'purchase_price'          => 'required|numeric|min:0',
            'selling_price'           => 'required|numeric|min:0',
            'stock_quantity'          => 'required|integer|min:0',
            'min_stock_level'         => 'required|integer|min:0',
            'is_active'               => 'boolean',
            'bottle_deposit_required' => 'boolean',
            'bottle_deposit_amount'   => 'nullable|numeric|min:0',
            'supplier_id'             => 'nullable|exists:suppliers,id',
            'tax_setting_id'          => 'nullable|exists:tax_settings,id',
            'barcode'                 => 'nullable|string|max:100|unique:products,barcode,' . $product->id,
            'image'                   => 'nullable|image|max:2048',
        ]);

        $data['selling_variants'] = isset($data['selling_variants']) ? trim($data['selling_variants']) : null;
        $data['shot_variants'] = json_decode($request->input('shot_variants', '[]'), true) ?? [];
        $data['bottle_deposit_required'] = $request->boolean('bottle_deposit_required');
        $data['is_active'] = $request->boolean('is_active', true);

        if ($request->hasFile('image')) {
            $currentImage = (string) $product->getRawOriginal('image');
            CloudinaryService::destroyImage($product->image_public_id);
            if ($currentImage && !str_starts_with($currentImage, 'http://') && !str_starts_with($currentImage, 'https://')) {
                Storage::disk('public')->delete($currentImage);
            }
            $uploaded = CloudinaryService::uploadProductImage($request->file('image'));
            $data['image'] = $uploaded['url'];
            $data['image_public_id'] = $uploaded['public_id'];
        }

        $product->update($data);
        return response()->json($product->fresh(['category', 'supplier']));
    }

    public function destroy(Product $product)
    {
        $currentImage = (string) $product->getRawOriginal('image');
        CloudinaryService::destroyImage($product->image_public_id);
        if ($currentImage && !str_starts_with($currentImage, 'http://') && !str_starts_with($currentImage, 'https://')) {
            Storage::disk('public')->delete($currentImage);
        }
        $product->delete();
        return response()->json(['message' => 'Product deleted']);
    }

    private function authorizeBranch(?int $branchId): void
    {
        $user = request()->user();
        if (!$user->isAdmin() && (int) $user->branch_id !== (int) $branchId) {
            abort(403, 'Forbidden for this branch.');
        }
    }

}
