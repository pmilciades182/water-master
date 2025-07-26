<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ProductCategory;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class ProductCategoryController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $companyId = $request->user()->company_id;
        
        $categories = ProductCategory::with(['products'])
            ->forCompany($companyId)
            ->active()
            ->get();

        return response()->json($categories);
    }

    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'config' => 'nullable|array',
            'is_active' => 'boolean'
        ]);

        $companyId = $request->user()->company_id;

        $category = ProductCategory::create([
            'company_id' => $companyId,
            'name' => $request->name,
            'description' => $request->description,
            'config' => $request->config,
            'is_active' => $request->get('is_active', true)
        ]);

        return response()->json($category, 201);
    }

    public function show(string $id): JsonResponse
    {
        $companyId = request()->user()->company_id;
        
        $category = ProductCategory::with(['products'])
            ->forCompany($companyId)
            ->findOrFail($id);

        return response()->json($category);
    }

    public function update(Request $request, string $id): JsonResponse
    {
        $request->validate([
            'name' => 'sometimes|string|max:255',
            'description' => 'nullable|string',
            'config' => 'nullable|array',
            'is_active' => 'boolean'
        ]);

        $companyId = $request->user()->company_id;
        
        $category = ProductCategory::forCompany($companyId)->findOrFail($id);
        $category->update($request->only(['name', 'description', 'config', 'is_active']));

        return response()->json($category);
    }

    public function destroy(string $id): JsonResponse
    {
        $companyId = request()->user()->company_id;
        
        $category = ProductCategory::forCompany($companyId)->findOrFail($id);
        
        if ($category->products()->count() > 0) {
            return response()->json([
                'message' => 'Cannot delete category with associated products'
            ], 422);
        }

        $category->delete();

        return response()->json(['message' => 'Category deleted successfully']);
    }
}
