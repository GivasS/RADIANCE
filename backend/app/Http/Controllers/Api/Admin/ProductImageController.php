<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ProductImageRequest;
use App\Models\AdminLog;
use App\Models\Product;
use App\Models\ProductImage;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ProductImageController extends Controller
{
    /**
     * Upload de 1..N imagens de uma vez (drag-and-drop multiplo do admin).
     */
    public function store(ProductImageRequest $request, Product $product): JsonResponse
    {
        $hasMain = $product->images()->where('is_main', true)->exists();
        $created = [];

        foreach ($request->file('images') as $index => $file) {
            // SECURITY: nunca preserva o nome original do arquivo (especificacoes.txt 8.1.12).
            $filename = Str::random(40).'.'.$file->getClientOriginalExtension();
            $path = $file->storeAs("products/{$product->id}", $filename, 'public');

            $created[] = $product->images()->create([
                'path' => $path,
                'alt_text' => $request->input('alt_text'),
                'position' => $product->images()->max('position') + 1,
                'is_main' => ! $hasMain && $index === 0,
            ]);
        }

        AdminLog::record('upload_images', 'product', $product->id, null, ['count' => count($created)]);

        return response()->json(['images' => $created], 201);
    }

    /**
     * Atualiza legenda ou marca como imagem principal.
     */
    public function update(Request $request, Product $product, ProductImage $image): JsonResponse
    {
        abort_if($image->product_id !== $product->id, 404);

        $data = $request->validate([
            'alt_text' => ['nullable', 'string', 'max:255'],
            'is_main' => ['nullable', 'boolean'],
        ]);

        DB::transaction(function () use ($product, $image, $data) {
            if (! empty($data['is_main'])) {
                $product->images()->where('id', '!=', $image->id)->update(['is_main' => false]);
                $data['is_main'] = true;
            }

            $image->update($data);
        });

        return response()->json(['image' => $image->fresh()]);
    }

    /**
     * Reordena as imagens do produto (drag-and-drop de posicao).
     */
    public function reorder(Request $request, Product $product): JsonResponse
    {
        $data = $request->validate([
            'image_ids' => ['required', 'array', 'min:1'],
            'image_ids.*' => ['integer'],
        ]);

        foreach ($data['image_ids'] as $position => $imageId) {
            $product->images()->where('id', $imageId)->update(['position' => $position]);
        }

        return response()->json(['images' => $product->images()->get()]);
    }

    public function destroy(Product $product, ProductImage $image): JsonResponse
    {
        abort_if($image->product_id !== $product->id, 404);

        $wasMain = $image->is_main;
        $image->delete();

        // Se apagou a principal, promove a proxima disponivel.
        if ($wasMain) {
            $next = $product->images()->orderBy('position')->first();
            $next?->update(['is_main' => true]);
        }

        AdminLog::record('delete_image', 'product', $product->id, null, ['image_id' => $image->id]);

        return response()->json(['message' => 'Imagem removida.']);
    }
}
