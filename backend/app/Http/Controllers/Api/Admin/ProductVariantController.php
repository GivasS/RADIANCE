<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ProductVariantRequest;
use App\Http\Requests\Admin\StockAdjustmentRequest;
use App\Models\AdminLog;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\StockMovement;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ProductVariantController extends Controller
{
    public function store(ProductVariantRequest $request, Product $product): JsonResponse
    {
        $variant = $product->variants()->create($request->validated());

        AdminLog::record('create', 'product_variant', $variant->id, null, $variant->toArray());

        return response()->json(['variant' => $variant], 201);
    }

    public function update(ProductVariantRequest $request, Product $product, ProductVariant $variant): JsonResponse
    {
        abort_if($variant->product_id !== $product->id, 404);

        $old = $variant->toArray();
        $variant->update($request->validated());

        AdminLog::record('update', 'product_variant', $variant->id, $old, $variant->toArray());

        return response()->json(['variant' => $variant]);
    }

    public function destroy(Product $product, ProductVariant $variant): JsonResponse
    {
        abort_if($variant->product_id !== $product->id, 404);

        if ($product->variants()->count() <= 1) {
            throw ValidationException::withMessages([
                'variant' => 'O produto precisa ter pelo menos uma variação. Para remover tudo, apague o produto.',
            ]);
        }

        $variant->delete();

        AdminLog::record('delete', 'product_variant', $variant->id);

        return response()->json(['message' => 'Variação removida.']);
    }

    /**
     * Ajuste manual de estoque com justificativa obrigatoria (especificacoes.txt 5.3.16).
     */
    public function adjustStock(StockAdjustmentRequest $request, Product $product, ProductVariant $variant): JsonResponse
    {
        abort_if($variant->product_id !== $product->id, 404);

        $movement = DB::transaction(function () use ($request, $variant) {
            /** @var ProductVariant $locked */
            $locked = ProductVariant::whereKey($variant->id)->lockForUpdate()->firstOrFail();

            $newQuantity = $locked->stock_quantity + $request->integer('delta');

            if ($newQuantity < 0) {
                throw ValidationException::withMessages([
                    'delta' => 'O ajuste deixaria o estoque negativo.',
                ]);
            }

            $locked->update(['stock_quantity' => $newQuantity]);

            return StockMovement::create([
                'variant_id' => $locked->id,
                'delta' => $request->integer('delta'),
                'balance_after' => $newQuantity,
                'reason' => 'ajuste_admin',
                'user_id' => auth()->id(),
                'notes' => $request->string('notes'),
            ]);
        });

        return response()->json([
            'variant' => $variant->fresh(),
            'movement' => $movement,
        ]);
    }
}
