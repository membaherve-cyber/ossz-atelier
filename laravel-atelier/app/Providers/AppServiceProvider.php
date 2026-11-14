<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;
use App\Models\Notification;

/**
 * OSSZ ATELIER application service provider.
 *
 * The application intentionally registers no additional bindings: all domain
 * services are small static/final classes under App\Services and App\Support.
 * Boot-time registrations: login rate limiter (spec §5) and the global view
 * composer sharing the signed-in user + unread notification count with every
 * view (Blade renders the view body before the layout, so defining $me only
 * in the layout is not enough — spec §8).
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
        $this->shareSessionUser();
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

    /**
     * Share $me (signed-in user or null) and $unread (notification badge
     * count) with every view, so screens can use them directly.
     */
    protected function shareSessionUser(): void
    {
        View::composer('*', function ($view) {
            $user = auth()->user();
            $view->with('me', $user);
            $view->with(
                'unread',
                $user === null
                    ? 0
                    : Notification::where('user_id', $user->id)->where('is_read', false)->count()
            );
        });
    }
}
