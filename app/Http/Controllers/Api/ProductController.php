<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\Stock;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class ProductController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $products = Product::all();
        return response()->json([
            'success' => true,
            'data' => $products
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'barcode' => 'required|string|unique:products,barcode',
        ]);

        $baseSlug = Str::slug($request->name);
        $slug = $baseSlug;
        $counter = 1;

        // Ensure slug is unique
        while (Product::where('slug', $slug)->exists()) {
            $slug = $baseSlug . '-' . $counter;
            $counter++;
        }

        $product = Product::create([
            'name' => $request->name,
            'barcode' => $request->barcode,
            'slug' => $slug,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Product created successfully',
            'data' => $product
        ], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $product = Product::find($id);

        if (!$product) {
            return response()->json([
                'success' => false,
                'message' => 'Product not found'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $product
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $product = Product::find($id);

        if (!$product) {
            return response()->json([
                'success' => false,
                'message' => 'Product not found'
            ], 404);
        }

        $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'barcode' => 'sometimes|required|string|unique:products,barcode,' . $id,
        ]);

        if ($request->has('name')) {
            $product->name = $request->name;

            $baseSlug = Str::slug($request->name);
            $slug = $baseSlug;
            $counter = 1;

            // Ensure slug is unique (excluding current product)
            while (Product::where('slug', $slug)->where('id', '!=', $id)->exists()) {
                $slug = $baseSlug . '-' . $counter;
                $counter++;
            }

            $product->slug = $slug;
        }

        if ($request->has('barcode')) {
            $product->barcode = $request->barcode;
        }

        $product->save();

        return response()->json([
            'success' => true,
            'message' => 'Product updated successfully',
            'data' => $product
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $product = Product::find($id);

        if (!$product) {
            return response()->json([
                'success' => false,
                'message' => 'Product not found'
            ], 404);
        }

        $product->delete();

        return response()->json([
            'success' => true,
            'message' => 'Product deleted successfully'
        ]);
    }

    /**
     * Search products for sale by name or barcode with FIFO method
     * Only returns products with stock quantity > 0
     */
    public function search(Request $request)
    {
        $request->validate([
            'query' => 'required|string|min:1',
        ]);

        $query = $request->input('query');

        // Search products by name or barcode
        $products = Product::where(function ($q) use ($query) {
            $q->where('name', 'like', '%' . $query . '%')
              ->orWhere('barcode', 'like', '%' . $query . '%');
        })
        ->whereHas('stocks', function ($q) {
            $q->where('quantity', '>', 0);
        })
        ->with(['stocks' => function ($q) {
            // FIFO: Order by oldest stock first (last_update_at or created_at)
            $q->where('quantity', '>', 0)
              ->orderByRaw('COALESCE(last_update_at, created_at) ASC');
        }])
        ->get();

        // Format response with stock information
        $formattedProducts = $products->map(function ($product) {
            $availableStock = $product->stocks->first(); // Get first stock (FIFO)
            
            return [
                'id' => $product->id,
                'name' => $product->name,
                'barcode' => $product->barcode,
                'slug' => $product->slug,
                'stock' => $availableStock ? [
                    'id' => $availableStock->id,
                    'sku' => $availableStock->sku,
                    'sale_price' => $availableStock->sale_price,
                    'purchase_price' => $availableStock->purchase_price,
                    'quantity' => $availableStock->quantity,
                    'last_update_at' => $availableStock->last_update_at,
                ] : null,
                'total_available_quantity' => $product->stocks->sum('quantity'),
            ];
        });

        return response()->json([
            'success' => true,
            'data' => $formattedProducts,
            'count' => $formattedProducts->count()
        ]);
    }
}
