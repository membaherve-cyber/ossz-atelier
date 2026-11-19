<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

/*
|--------------------------------------------------------------------------
| Create The Application
|--------------------------------------------------------------------------
|
| OSSZ ATELIER — standalone Laravel 11 application (see agent-ctx/laravel-spec.md).
|
*/

return Application::configure(basePath: dirname(__DIR__))

    /*
    |----------------------------------------------------------------------
    | Application Routing
    |----------------------------------------------------------------------
    |
    | All routes live in routes/web.php (Blade + forms, CSRF-protected).
    | `/up` is the framework health-check endpoint used by the host.
    |
    */

    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )

    /*
    |----------------------------------------------------------------------
    | Application Middleware
    |----------------------------------------------------------------------
    |
    | SetLocale is appended to the `web` group: every session-backed request
    | adopts the UI locale stored in the session (default: config('app.locale')).
    |
    | The `perm` alias enforces the role/permission matrix from
    | App\Services\Permissions (usage: ->middleware('perm:orders.manage')).
    |
    | The `guest` alias sends already-authenticated visitors away from the
    | login screen back to the dashboard ("/"), per spec §3.
    |
    */

    ->withMiddleware(function (Middleware $middleware) {
        $middleware->web(append: [
            \App\Http\Middleware\SetLocale::class,
            \App\Http\Middleware\SecurityHeaders::class,
        ]);

        $middleware->alias([
            'perm' => \App\Http\Middleware\EnsurePermission::class,
            'guest' => \App\Http\Middleware\RedirectIfAuthenticated::class,
        ]);
    })

    /*
    |----------------------------------------------------------------------
    | Exception Handling
    |----------------------------------------------------------------------
    |
    | Framework defaults are used (403 for permission failures raised by
    | EnsurePermission, redirect to the named `login` route for
    | unauthenticated requests, 404 for missing models, etc.).
    |
    */

    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();
