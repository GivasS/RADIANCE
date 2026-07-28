<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Category;
use Illuminate\Http\JsonResponse;

class CategoryController extends Controller
{
    /**
     * Lista as categorias principais ativas, com as subcategorias ativas aninhadas.
     */
    public function index(): JsonResponse
    {
        $categories = Category::query()
            ->whereNull('parent_id')
            ->where('active', true)
            ->with(['children' => fn ($q) => $q->where('active', true)->orderBy('position')])
            ->orderBy('position')
            ->get();

        return response()->json(['categories' => $categories]);
    }

    /**
     * Mostra uma categoria ativa pelo slug, com as subcategorias ativas.
     */
    public function show(string $slug): JsonResponse
    {
        $category = Category::query()
            ->where('slug', $slug)
            ->where('active', true)
            ->with(['children' => fn ($q) => $q->where('active', true)->orderBy('position')])
            ->firstOrFail();

        return response()->json(['category' => $category]);
    }
}
