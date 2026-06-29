<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {

    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        \App\Models\Product::observe(\App\Observers\ProductObserver::class);
        \App\Models\Category::observe(\App\Observers\CategoryObserver::class);
        \App\Models\Collection::observe(\App\Observers\CollectionObserver::class);

        RateLimiter::for('create-session', function ($request) {
            return Limit::perHour(10)->by($request->ip());
        });

        RateLimiter::for('refresh-session', function ($request) {
            return Limit::perHour(5)->by($request->ip());
        });
    }
}
