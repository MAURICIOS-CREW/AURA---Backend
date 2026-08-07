<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Contracts\LicensePlateNormalizerInterface;
use App\Services\LicensePlateNormalizer;
use App\Contracts\AccessNotifierInterface;
use App\Services\AccessNotifierService;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(LicensePlateNormalizerInterface::class, LicensePlateNormalizer::class);
        $this->app->bind(AccessNotifierInterface::class, AccessNotifierService::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        \Laravel\Sanctum\Sanctum::usePersonalAccessTokenModel(\App\Models\PersonalAccessToken::class);
    }
}
