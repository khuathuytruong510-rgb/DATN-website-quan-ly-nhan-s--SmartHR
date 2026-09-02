<?php

namespace App\Providers;

use App\Contracts\DigitalSignatureProvider;
use App\Services\ESign\MockDigitalSignatureProvider;
use App\Support\NavBadgeCounts;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(DigitalSignatureProvider::class, function () {
            return match (config('esign.provider')) {
                default => new MockDigitalSignatureProvider(),
            };
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        View::composer('layouts.app', function ($view) {
            $user = Auth::user();
            $view->with('navBadges', $user ? app(NavBadgeCounts::class)->for($user) : []);
        });
    }
}
