<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CustomerController extends Controller
{
    /**
     * Listagem, busca (especificacoes.txt 5.5.26). Nunca expoe password_hash
     * (ja escondido pelo #[Hidden] do model User).
     */
    public function index(Request $request): JsonResponse
    {
        $customers = User::query()
            ->where('role', 'customer')
            ->when($request->filled('search'), function ($q) use ($request) {
                $term = $request->string('search');
                $q->where(fn ($q2) => $q2->where('name', 'like', "%{$term}%")->orWhere('email', 'like', "%{$term}%"));
            })
            ->withCount('orders')
            ->orderByDesc('created_at')
            ->paginate($request->integer('per_page', 20));

        return response()->json($customers);
    }

    /**
     * Historico de pedidos do cliente (especificacoes.txt 5.5.26).
     */
    public function show(User $customer): JsonResponse
    {
        abort_if($customer->role !== 'customer', 404);

        $customer->load(['addresses', 'orders' => fn ($q) => $q->orderByDesc('created_at')]);

        return response()->json(['customer' => $customer]);
    }
}
