<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Address\AddressRequest;
use App\Models\Address;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AddressController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $addresses = $request->user()->addresses()->orderByDesc('is_default')->get();

        return response()->json(['addresses' => $addresses]);
    }

    public function store(AddressRequest $request): JsonResponse
    {
        $address = DB::transaction(function () use ($request) {
            if ($request->boolean('is_default')) {
                $request->user()->addresses()->update(['is_default' => false]);
            }

            return $request->user()->addresses()->create($request->validated());
        });

        return response()->json(['address' => $address], 201);
    }

    public function update(AddressRequest $request, Address $address): JsonResponse
    {
        abort_if($address->user_id !== $request->user()->id, 404);

        DB::transaction(function () use ($request, $address) {
            if ($request->boolean('is_default')) {
                $request->user()->addresses()->where('id', '!=', $address->id)->update(['is_default' => false]);
            }

            $address->update($request->validated());
        });

        return response()->json(['address' => $address->fresh()]);
    }

    public function destroy(Request $request, Address $address): JsonResponse
    {
        abort_if($address->user_id !== $request->user()->id, 404);

        $address->delete();

        return response()->json(['message' => 'Endereço removido.']);
    }
}
