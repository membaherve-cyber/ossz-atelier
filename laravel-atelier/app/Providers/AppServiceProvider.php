<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

/**
 * OSSZ ATELIER application service provider.
 *
 * The application intentionally registers no additional bindings: all domain
 * services are small static/final classes under App\Services and App\Support.
 * The only boot-time registration is the login rate limiter required by the
 * architecture spec §5 ("5 attempts/min per email + IP", consumed by the
 * `throttle:login` middleware attached to POST /login in routes/web.php).
 */
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
        $this->configureRateLimiting();
    }

    /**
     * Login throttle: 5 attempts per minute keyed by email + IP (spec §5).
     */
    protected function configureRateLimiting(): void
    {
        RateLimiter::for('login', function (Request $request) {
            $email = strtolower(trim((string) $request->input('email', '')));

            return Limit::perMinute(5)->by($email.'|'.$request->ip());
        });
    }
}
