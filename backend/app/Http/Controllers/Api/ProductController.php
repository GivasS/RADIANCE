<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Product;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ProductController extends Controller
{
    /**
     * PLP - listagem de produtos com filtros, ordenacao e paginacao.
     */
    public function index(Request $request): JsonResponse
    {
        $query = Product::query()
            ->publiclyVisible()
            ->with(['category', 'images' => fn ($q) => $q->where('is_main', true)])
            ->withSum('variants as stock_total', 'stock_quantity');

        if ($request->filled('category')) {
            $query->whereHas('category', fn ($q) => $q->where('slug', $request->string('category')));
        }

        if ($request->filled('min_price')) {
            $query->where(DB::raw('COALESCE(promo_price, price)'), '>=', $request->float('min_price'));
        }

        if ($request->filled('max_price')) {
            $query->where(DB::raw('COALESCE(promo_price, price)'), '<=', $request->float('max_price'));
        }

        $query = match ($request->string('sort')->toString()) {
            'menor_preco' => $query->orderByRaw('COALESCE(promo_price, price) ASC'),
            'maior_preco' => $query->orderByRaw('COALESCE(promo_price, price) DESC'),
            'lancamentos' => $query->orderByDesc('id'),
            default => $query->orderByDesc('featured')->orderByDesc('id'),
        };

        $products = $query->paginate($request->integer('per_page', 24));

        return response()->json($products);
    }

    /**
     * PDP - produto pelo slug. Mostra mesmo esgotado (a UI decide o que fazer),
     * so nao mostra se estiver inativo ou apagado.
     */
    public function show(string $slug): JsonResponse
    {
        $product = Product::query()
            ->where('slug', $slug)
            ->where('active', true)
            ->with([
                'category',
                'images',
                'variants' => fn ($q) => $q->where('active', true)->orderBy('position'),
            ])
            ->firstOrFail();

        return response()->json(['product' => $product]);
    }

    /**
     * Busca full-text (name, short_description, description).
     */
    public function search(Request $request): JsonResponse
    {
        $term = $request->string('q')->toString();

        if ($term === '') {
            return response()->json(['products' => ['data' => []]]);
        }

        $products = Product::query()
            ->publiclyVisible()
            ->whereRaw('MATCH(name, short_description, description) AGAINST(? IN NATURAL LANGUAGE MODE)', [$term])
            ->with(['category', 'images' => fn ($q) => $q->where('is_main', true)])
            ->withSum('variants as stock_total', 'stock_quantity')
            ->paginate($request->integer('per_page', 24));

        return response()->json($products);
    }

    /**
     * Produtos em destaque, pra home ("Mais Vendidos" / "Voce Pode Gostar").
     */
    public function featured(): JsonResponse
    {
        $products = Product::query()
            ->publiclyVisible()
            ->where('featured', true)
            ->with(['category', 'images' => fn ($q) => $q->where('is_main', true)])
            ->withSum('variants as stock_total', 'stock_quantity')
            ->limit(12)
            ->get();

        return response()->json(['products' => $products]);
    }
}
