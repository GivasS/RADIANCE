<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ProductRequest;
use App\Models\AdminLog;
use App\Models\Product;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    /**
     * Lista produtos (todos os status) com busca e filtros pro painel.
     */
    public function index(Request $request): JsonResponse
    {
        $products = Product::query()
            ->with(['category', 'images' => fn ($q) => $q->where('is_main', true)])
            ->withSum('variants as stock_total', 'stock_quantity')
            ->when($request->filled('search'), function ($q) use ($request) {
                $term = $request->string('search');
                $q->where(fn ($q) => $q->where('name', 'like', "%{$term}%")->orWhere('sku', 'like', "%{$term}%"));
            })
            ->when($request->filled('category_id'), fn ($q) => $q->where('category_id', $request->integer('category_id')))
            ->when($request->has('active'), fn ($q) => $q->where('active', $request->boolean('active')))
            ->orderByDesc('created_at')
            ->paginate($request->integer('per_page', 20));

        return response()->json($products);
    }

    public function store(ProductRequest $request): JsonResponse
    {
        $product = Product::create($request->validated());

        // Todo produto recebe automaticamente a variacao padrao "UNICO" (especificacoes.txt 2.2.6).
        $product->variants()->create([
            'variant_name' => 'Padrão',
            'variant_value' => 'ÚNICO',
            'stock_quantity' => 0,
        ]);

        AdminLog::record('create', 'product', $product->id, null, $product->toArray());

        return response()->json(['product' => $product->load('variants')], 201);
    }

    public function show(Product $product): JsonResponse
    {
        $product->load(['category', 'images', 'variants']);

        return response()->json(['product' => $product]);
    }

    public function update(ProductRequest $request, Product $product): JsonResponse
    {
        $old = $product->toArray();
        $product->update($request->validated());

        AdminLog::record('update', 'product', $product->id, $old, $product->toArray());

        return response()->json(['product' => $product->fresh(['category', 'images', 'variants'])]);
    }

    public function destroy(Product $product): JsonResponse
    {
        $product->delete();

        AdminLog::record('delete', 'product', $product->id);

        return response()->json(['message' => 'Produto movido para a lixeira.']);
    }

    public function trashed(): JsonResponse
    {
        $products = Product::onlyTrashed()->with('category')->orderByDesc('deleted_at')->get();

        return response()->json(['products' => $products]);
    }

    public function restore(int $id): JsonResponse
    {
        $product = Product::onlyTrashed()->findOrFail($id);
        $product->restore();

        AdminLog::record('restore', 'product', $product->id);

        return response()->json(['product' => $product]);
    }
}
