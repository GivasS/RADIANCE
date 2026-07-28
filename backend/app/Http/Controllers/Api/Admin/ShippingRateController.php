<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ShippingRateRequest;
use App\Models\AdminLog;
use App\Models\ShippingRate;
use Illuminate\Http\JsonResponse;

class ShippingRateController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json(['rates' => ShippingRate::orderBy('position')->get()]);
    }

    public function store(ShippingRateRequest $request): JsonResponse
    {
        $rate = ShippingRate::create($request->validated());
        AdminLog::record('create', 'shipping_rate', $rate->id, null, $rate->toArray());

        return response()->json(['rate' => $rate], 201);
    }

    public function update(ShippingRateRequest $request, ShippingRate $rate): JsonResponse
    {
        $old = $rate->toArray();
        $rate->update($request->validated());
        AdminLog::record('update', 'shipping_rate', $rate->id, $old, $rate->toArray());

        return response()->json(['rate' => $rate]);
    }

    public function destroy(ShippingRate $rate): JsonResponse
    {
        $rate->delete();
        AdminLog::record('delete', 'shipping_rate', $rate->id);

        return response()->json(['message' => 'Faixa de frete removida.']);
    }
}
