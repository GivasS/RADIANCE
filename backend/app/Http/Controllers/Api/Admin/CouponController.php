<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\CouponRequest;
use App\Models\AdminLog;
use App\Models\Coupon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CouponController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $coupons = Coupon::query()
            ->when($request->filled('search'), fn ($q) => $q->where('code', 'like', '%'.$request->string('search').'%'))
            ->orderByDesc('created_at')
            ->paginate($request->integer('per_page', 20));

        return response()->json($coupons);
    }

    public function store(CouponRequest $request): JsonResponse
    {
        $data = $request->validated();
        $data['code'] = strtoupper($data['code']);

        $coupon = Coupon::create($data);
        AdminLog::record('create', 'coupon', $coupon->id, null, $coupon->toArray());

        return response()->json(['coupon' => $coupon], 201);
    }

    /**
     * Detalhe com visualizacao de usos (especificacoes.txt 5.5.24).
     */
    public function show(Coupon $coupon): JsonResponse
    {
        $coupon->load(['uses.user:id,name,email']);

        return response()->json(['coupon' => $coupon]);
    }

    public function update(CouponRequest $request, Coupon $coupon): JsonResponse
    {
        $old = $coupon->toArray();
        $data = $request->validated();
        $data['code'] = strtoupper($data['code']);

        $coupon->update($data);
        AdminLog::record('update', 'coupon', $coupon->id, $old, $coupon->toArray());

        return response()->json(['coupon' => $coupon]);
    }

    public function destroy(Coupon $coupon): JsonResponse
    {
        $coupon->delete();
        AdminLog::record('delete', 'coupon', $coupon->id);

        return response()->json(['message' => 'Cupom removido.']);
    }
}
