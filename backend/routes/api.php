<?php

use App\Http\Controllers\Api\Admin\CategoryController as AdminCategoryController;
use App\Http\Controllers\Api\Admin\CouponController as AdminCouponController;
use App\Http\Controllers\Api\Admin\CustomerController;
use App\Http\Controllers\Api\Admin\DashboardController;
use App\Http\Controllers\Api\Admin\OrderController as AdminOrderController;
use App\Http\Controllers\Api\Admin\ProductController as AdminProductController;
use App\Http\Controllers\Api\Admin\ProductImageController;
use App\Http\Controllers\Api\Admin\ProductVariantController;
use App\Http\Controllers\Api\Admin\SettingController;
use App\Http\Controllers\Api\Admin\ShippingRateController;
use App\Http\Controllers\Api\AddressController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CartController;
use App\Http\Controllers\Api\CategoryController;
use App\Http\Controllers\Api\CheckoutController;
use App\Http\Controllers\Api\CouponController;
use App\Http\Controllers\Api\OrderController;
use App\Http\Controllers\Api\ProductController;
use App\Http\Controllers\Api\SettingController as PublicSettingController;
use App\Http\Controllers\Api\ShippingController;
use App\Http\Controllers\Api\WebhookController;
use Illuminate\Support\Facades\Route;

Route::prefix('auth')->group(function () {
    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/login', [AuthController::class, 'login']);
    Route::post('/forgot-password', [AuthController::class, 'forgotPassword']);
    Route::post('/reset-password', [AuthController::class, 'resetPassword']);

    Route::middleware('auth:sanctum')->group(function () {
        Route::post('/logout', [AuthController::class, 'logout']);
        Route::get('/me', [AuthController::class, 'me']);
    });
});

// Loja - leitura publica
Route::get('/categories', [CategoryController::class, 'index']);
Route::get('/categories/{slug}', [CategoryController::class, 'show']);

Route::get('/products', [ProductController::class, 'index']);
Route::get('/products/search', [ProductController::class, 'search']);
Route::get('/products/featured', [ProductController::class, 'featured']);
Route::get('/products/{slug}', [ProductController::class, 'show']);

Route::get('/settings/public', [PublicSettingController::class, 'public']);

// Carrinho - funciona logado ou anonimo (ResolveCart resolve qual carrinho usar)
Route::middleware('cart')->prefix('cart')->group(function () {
    Route::get('/', [CartController::class, 'index']);
    Route::post('/items', [CartController::class, 'storeItem']);
    Route::put('/items/{item}', [CartController::class, 'updateItem']);
    Route::delete('/items/unavailable', [CartController::class, 'destroyUnavailable']);
    Route::delete('/items/{item}', [CartController::class, 'destroyItem']);

    Route::post('/shipping/quote', [ShippingController::class, 'quote']);
    Route::post('/coupon/validate', [CouponController::class, 'validateCode']);
});

// Endereços e checkout - exigem login (especificacoes.txt 1.1.3: login obrigatorio so no checkout)
Route::middleware(['auth:sanctum', 'cart'])->group(function () {
    Route::apiResource('addresses', AddressController::class)->except(['show']);
    Route::post('/checkout', [CheckoutController::class, 'store']);
});

// "Meus Pedidos" - so os do proprio usuario
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/orders', [OrderController::class, 'index']);
    Route::get('/orders/{orderNumber}', [OrderController::class, 'show']);
});

// Webhook EFI - publico (autenticado por mTLS no Nginx, nao por sessao)
Route::post('/webhooks/efi/pix', [WebhookController::class, 'efiPix']);

// Painel administrativo
Route::middleware(['auth:sanctum', 'admin'])->prefix('admin')->group(function () {
    Route::get('/categories/trashed', [AdminCategoryController::class, 'trashed']);
    Route::post('/categories/{id}/restore', [AdminCategoryController::class, 'restore']);
    Route::apiResource('categories', AdminCategoryController::class)->except(['show'])
        ->parameters(['categories' => 'category']);
    Route::get('/categories/{category}', [AdminCategoryController::class, 'show']);

    Route::get('/products/trashed', [AdminProductController::class, 'trashed']);
    Route::post('/products/{id}/restore', [AdminProductController::class, 'restore']);
    Route::apiResource('products', AdminProductController::class)->except(['show'])
        ->parameters(['products' => 'product']);
    Route::get('/products/{product}', [AdminProductController::class, 'show']);

    Route::post('/products/{product}/images', [ProductImageController::class, 'store']);
    Route::post('/products/{product}/images/reorder', [ProductImageController::class, 'reorder']);
    Route::put('/products/{product}/images/{image}', [ProductImageController::class, 'update']);
    Route::delete('/products/{product}/images/{image}', [ProductImageController::class, 'destroy']);

    Route::post('/products/{product}/variants', [ProductVariantController::class, 'store']);
    Route::put('/products/{product}/variants/{variant}', [ProductVariantController::class, 'update']);
    Route::delete('/products/{product}/variants/{variant}', [ProductVariantController::class, 'destroy']);
    Route::post('/products/{product}/variants/{variant}/adjust-stock', [ProductVariantController::class, 'adjustStock']);

    Route::get('/dashboard', [DashboardController::class, 'index']);

    Route::get('/orders/export', [AdminOrderController::class, 'exportCsv']);
    Route::get('/orders', [AdminOrderController::class, 'index']);
    Route::get('/orders/{order}', [AdminOrderController::class, 'show']);
    Route::put('/orders/{order}/status', [AdminOrderController::class, 'updateStatus']);
    Route::put('/orders/{order}/tracking', [AdminOrderController::class, 'setTracking']);
    Route::post('/orders/{order}/cancel', [AdminOrderController::class, 'cancel']);

    Route::apiResource('coupons', AdminCouponController::class);
    Route::apiResource('shipping-rates', ShippingRateController::class)->except(['show'])
        ->parameters(['shipping-rates' => 'rate']);

    Route::get('/customers', [CustomerController::class, 'index']);
    Route::get('/customers/{customer}', [CustomerController::class, 'show']);

    Route::get('/settings', [SettingController::class, 'index']);
    Route::put('/settings', [SettingController::class, 'update']);
});
