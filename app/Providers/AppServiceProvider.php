<?php

namespace App\Providers;

use Illuminate\Auth\Middleware\Authenticate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Plain (non-Filament) routes behind `auth` middleware — e.g. /subscribe —
        // have no bare `login` named route to fall back on; both panels define
        // their own (filament.admin.auth.login / filament.user.auth.login).
        Authenticate::redirectUsing(fn () => '/user/login');
    }
}
