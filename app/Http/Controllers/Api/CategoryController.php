<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class CategoryController extends Controller
{
    public function index()
    {
        $categories = Category::withCount('products')
            ->latest()
            ->paginate(request('per_page', 20));
        return response()->json($categories);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name'            => 'required|string|max:100|unique:categories',
            'description'     => 'nullable|string',
            'is_active'       => 'boolean',
            'enable_variants' => 'boolean',
        ]);
        $data['slug'] = Str::slug($data['name']);
        $data['branch_id'] = $request->user()->branch_id;
        $category = Category::create($data);
        return response()->json($category, 201);
    }

    public function show(Category $category)
    {
        return response()->json($category->loadCount('products'));
    }

    public function update(Request $request, Category $category)
    {
        $data = $request->validate([
            'name'            => 'required|string|max:100|unique:categories,name,' . $category->id,
            'description'     => 'nullable|string',
            'is_active'       => 'boolean',
            'enable_variants' => 'boolean',
        ]);
        $data['slug'] = Str::slug($data['name']);
        $category->update($data);
        return response()->json($category);
    }

    public function destroy(Category $category)
    {
        $category->delete();
        return response()->json(['message' => 'Category deleted']);
    }

    public function all()
    {
        return response()->json(Category::where('is_active', true)->get(['id', 'name', 'enable_variants']));
    }
}
