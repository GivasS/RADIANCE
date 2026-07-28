<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\CategoryRequest;
use App\Models\AdminLog;
use App\Models\Category;
use Illuminate\Http\JsonResponse;

class CategoryController extends Controller
{
    /**
     * Lista todas as categorias (ativas e inativas), com hierarquia.
     */
    public function index(): JsonResponse
    {
        $categories = Category::query()
            ->whereNull('parent_id')
            ->with(['children' => fn ($q) => $q->orderBy('position')])
            ->orderBy('position')
            ->get();

        return response()->json(['categories' => $categories]);
    }

    public function store(CategoryRequest $request): JsonResponse
    {
        $category = Category::create($request->validated());

        AdminLog::record('create', 'category', $category->id, null, $category->toArray());

        return response()->json(['category' => $category], 201);
    }

    public function show(Category $category): JsonResponse
    {
        $category->load(['children', 'parent']);

        return response()->json(['category' => $category]);
    }

    public function update(CategoryRequest $request, Category $category): JsonResponse
    {
        $old = $category->toArray();
        $category->update($request->validated());

        AdminLog::record('update', 'category', $category->id, $old, $category->toArray());

        return response()->json(['category' => $category]);
    }

    /**
     * Soft delete - a categoria vai para a Lixeira.
     */
    public function destroy(Category $category): JsonResponse
    {
        $category->delete();

        AdminLog::record('delete', 'category', $category->id);

        return response()->json(['message' => 'Categoria movida para a lixeira.']);
    }

    /**
     * Lixeira: lista categorias com soft delete.
     */
    public function trashed(): JsonResponse
    {
        $categories = Category::onlyTrashed()->orderByDesc('deleted_at')->get();

        return response()->json(['categories' => $categories]);
    }

    public function restore(int $id): JsonResponse
    {
        $category = Category::onlyTrashed()->findOrFail($id);
        $category->restore();

        AdminLog::record('restore', 'category', $category->id);

        return response()->json(['category' => $category]);
    }
}
