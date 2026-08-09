<?php

namespace Modules\NabdBridge\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\ProductUnitQuantity;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class NabdProductController extends Controller
{
    /**
     * GET /api/nabd/products
     *
     * Query params:
     *   - category_id (int)
     *   - search (string) — matches name or barcode
     *   - per_page (int, default 50, max 200)
     *   - page (int)
     */
    public function index(Request $request): JsonResponse
    {
        $perPage = min((int) $request->query('per_page', 50), 200);

        $query = Product::with(['unit_quantities.unit', 'category', 'tax_group'])
            ->where('type', '!=', 'grouped');

        if ($request->filled('category_id')) {
            $query->where('category_id', $request->integer('category_id'));
        }

        if ($request->filled('search')) {
            $term = $request->string('search');
            $query->where(function ($q) use ($term): void {
                $q->where('name', 'like', "%{$term}%")
                    ->orWhere('barcode', 'like', "%{$term}%")
                    ->orWhere('sku', 'like', "%{$term}%");
            });
        }

        $paginator = $query->orderBy('name')->paginate($perPage);

        return response()->json([
            'status' => 'success',
            'data' => $paginator->items(),
            'meta' => [
                'total' => $paginator->total(),
                'per_page' => $paginator->perPage(),
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
            ],
        ]);
    }

    /**
     * GET /api/nabd/products/{id}
     */
    public function show(int $id): JsonResponse
    {
        $product = Product::with([
            'unit_quantities.unit',
            'category',
            'tax_group',
            'taxes',
            'galleries',
        ])->findOrFail($id);

        return response()->json([
            'status' => 'success',
            'data' => $product,
        ]);
    }

    /**
     * GET /api/nabd/products/{id}/stock
     *
     * Returns available stock per unit for the product.
     */
    public function stock(int $id): JsonResponse
    {
        $product = Product::findOrFail($id);

        $quantities = ProductUnitQuantity::with('unit')
            ->where('product_id', $id)
            ->get()
            ->map(fn (ProductUnitQuantity $uq) => [
                'unit_id' => $uq->unit_id,
                'unit_name' => $uq->unit?->name,
                'quantity' => $uq->quantity,
                'low_quantity' => $uq->low_quantity,
                'sale_price' => $uq->sale_price,
                'sale_price_with_tax' => $uq->sale_price_with_tax,
                'purchase_price' => $uq->purchase_price,
            ]);

        return response()->json([
            'status' => 'success',
            'data' => [
                'product_id' => $product->id,
                'product_name' => $product->name,
                'units' => $quantities,
            ],
        ]);
    }

    /**
     * GET /api/nabd/categories
     *
     * Query params:
     *   - parent_id (int) — filter by parent category
     *   - per_page (int, default 100)
     */
    public function categories(Request $request): JsonResponse
    {
        $perPage = min((int) $request->query('per_page', 100), 500);

        $query = ProductCategory::query();

        if ($request->filled('parent_id')) {
            $query->where('parent_id', $request->integer('parent_id'));
        }

        $paginator = $query->orderBy('name')->paginate($perPage);

        return response()->json([
            'status' => 'success',
            'data' => $paginator->items(),
            'meta' => [
                'total' => $paginator->total(),
                'per_page' => $paginator->perPage(),
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
            ],
        ]);
    }
}
