<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class SettingController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json(['settings' => Setting::orderBy('key_name')->get()]);
    }

    /**
     * Atualizacao em lote: [{key_name, value}, ...] (especificacoes.txt 5.5.27).
     */
    public function update(Request $request): JsonResponse
    {
        $data = $request->validate([
            'settings' => ['required', 'array'],
            'settings.*.key_name' => ['required', 'string'],
            'settings.*.value' => ['required'],
        ]);

        foreach ($data['settings'] as $item) {
            Setting::where('key_name', $item['key_name'])->update(['value' => $item['value']]);
            Cache::forget("setting:{$item['key_name']}");
        }

        return response()->json(['settings' => Setting::orderBy('key_name')->get()]);
    }
}
