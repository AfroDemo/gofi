<?php

namespace App\Providers;

use App\Services\Payment\Contracts\HotspotPaymentGateway;
use App\Services\Payment\PaymentGatewayManager;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(PaymentGatewayManager::class, function ($app) {
            return new PaymentGatewayManager(
                $app->make(\App\Services\Payment\PalmpesaGateway::class),
                $app->make(\App\Services\Payment\SnippeGateway::class),
            );
        });

        $this->app->bind(HotspotPaymentGateway::class, function ($app) {
            return $app->make(PaymentGatewayManager::class)->active();
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
