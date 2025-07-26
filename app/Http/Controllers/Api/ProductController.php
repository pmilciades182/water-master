<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;

class ProductController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $companyId = $request->user()->company_id;
        
        $query = Product::with(['category', 'productAttributes'])
            ->forCompany($companyId);

        if ($request->has('category_id')) {
            $query->where('category_id', $request->category_id);
        }

        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('name', 'ILIKE', "%{$search}%")
                  ->orWhere('sku', 'ILIKE', "%{$search}%")
                  ->orWhere('description', 'ILIKE', "%{$search}%");
            });
        }

        if ($request->has('low_stock') && $request->low_stock) {
            $query->lowStock();
        }

        $products = $query->paginate($request->get('per_page', 15));

        return response()->json($products);
    }

    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'category_id' => 'required|exists:product_categories,id',
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'sku' => 'nullable|string|max:255',
            'price' => 'required|numeric|min:0',
            'stock' => 'required|integer|min:0',
            'min_stock' => 'required|integer|min:0',
            'attributes' => 'nullable|array',
            'is_active' => 'boolean'
        ]);

        $companyId = $request->user()->company_id;

        $product = Product::create([
            'company_id' => $companyId,
            'category_id' => $request->category_id,
            'name' => $request->name,
            'description' => $request->description,
            'sku' => $request->sku,
            'price' => $request->price,
            'stock' => $request->stock,
            'min_stock' => $request->min_stock,
            'attributes' => $request->attributes,
            'is_active' => $request->get('is_active', true)
        ]);

        $product->load(['category', 'productAttributes']);

        return response()->json($product, 201);
    }

    public function show(string $id): JsonResponse
    {
        $companyId = request()->user()->company_id;
        
        $product = Product::with(['category', 'productAttributes'])
            ->forCompany($companyId)
            ->findOrFail($id);

        return response()->json($product);
    }

    public function update(Request $request, string $id): JsonResponse
    {
        $request->validate([
            'category_id' => 'sometimes|exists:product_categories,id',
            'name' => 'sometimes|string|max:255',
            'description' => 'nullable|string',
            'sku' => 'nullable|string|max:255',
            'price' => 'sometimes|numeric|min:0',
            'stock' => 'sometimes|integer|min:0',
            'min_stock' => 'sometimes|integer|min:0',
            'attributes' => 'nullable|array',
            'is_active' => 'boolean'
        ]);

        $companyId = $request->user()->company_id;
        
        $product = Product::forCompany($companyId)->findOrFail($id);
        $product->update($request->only([
            'category_id', 'name', 'description', 'sku', 'price', 
            'stock', 'min_stock', 'attributes', 'is_active'
        ]));

        $product->load(['category', 'productAttributes']);

        return response()->json($product);
    }

    public function destroy(string $id): JsonResponse
    {
        $companyId = request()->user()->company_id;
        
        $product = Product::forCompany($companyId)->findOrFail($id);
        $product->delete();

        return response()->json(['message' => 'Product deleted successfully']);
    }
}
