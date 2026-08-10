<?php

namespace App\Providers;

use App\Services\Payments\CardGatewayInterface;
use App\Services\Payments\EfiCardGateway;
use App\Services\Payments\EfiPixGateway;
use App\Services\Payments\FakeCardGateway;
use App\Services\Payments\FakePixGateway;
use App\Services\Payments\PaymentGatewayInterface;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Enquanto EFI_CLIENT_ID nao estiver configurado, usa os gateways
        // falsos pra dar pra testar o checkout de ponta a ponta sem chaves.
        $this->app->bind(PaymentGatewayInterface::class, function () {
            return config('services.efi.client_id')
                ? $this->app->make(EfiPixGateway::class)
                : $this->app->make(FakePixGateway::class);
        });

        $this->app->bind(CardGatewayInterface::class, function () {
            return config('services.efi.client_id')
                ? $this->app->make(EfiCardGateway::class)
                : $this->app->make(FakeCardGateway::class);
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
