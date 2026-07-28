<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use Illuminate\Http\JsonResponse;

class SettingController extends Controller
{
    /**
     * Subconjunto de configuracoes seguro pra expor na loja (preco, frete,
     * parcelamento, contato). Nada operacional/sensivel aqui.
     */
    public function public(): JsonResponse
    {
        return response()->json([
            'store_name' => Setting::get('store_name'),
            'store_email' => Setting::get('store_email'),
            'store_whatsapp' => Setting::get('store_whatsapp'),
            'free_shipping_threshold' => (float) Setting::get('free_shipping_threshold', 219),
            'max_installments' => (int) Setting::get('max_installments', 3),
            'min_installment_value' => (float) Setting::get('min_installment_value', 20),
            'pix_discount_percent' => (int) Setting::get('pix_discount_percent', 5),
            'low_stock_alert' => (int) Setting::get('low_stock_alert', 5),
        ]);
    }
}
