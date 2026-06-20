<?php

use App\Http\Middleware\EnsureUserHasRole;
use App\Http\Middleware\TenantResolver;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->prepend(TenantResolver::class);
        $middleware->alias([
            'role' => EnsureUserHasRole::class,
        ]);
        // Paksa no-cache untuk /login agar CSRF token di form selalu fresh
        // setelah session expire (menghindari 419 Page Expired).
        $middleware->appendToGroup('web', \App\Http\Middleware\NoCacheForAuthenticatedRedirects::class);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*'),
        );

        // CSRF token mismatch → redirect ke /login dengan flash message.
        $exceptions->render(function (\Illuminate\Session\TokenMismatchException $e, Request $request) {
            if ($request->is('api/*') || $request->expectsJson()) {
                return response()->json(['message' => 'Sesi Anda telah berakhir. Silakan login ulang.'], 419);
            }
            return redirect()
                ->route('login')
                ->with('status', 'Sesi Anda telah berakhir. Silakan login ulang.');
        });
    })->create();
