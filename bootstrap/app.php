<?php

use App\Http\Middleware\RoleMiddleware;
use App\Http\Middleware\CspReportOnlyMiddleware;
use App\Http\Middleware\SecurityHeadersMiddleware;
use App\Support\DebugBfd979Log;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Support\Facades\Route;
use Illuminate\Session\TokenMismatchException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
        then: function (): void {
            $moduleRouteFiles = glob(base_path('Modules/*/routes/web.php')) ?: [];
            sort($moduleRouteFiles);

            foreach ($moduleRouteFiles as $routeFile) {
                if (is_file($routeFile)) {
                    Route::middleware('web')->group($routeFile);
                }
            }
        },
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->append(SecurityHeadersMiddleware::class);
        $middleware->append(CspReportOnlyMiddleware::class);

        $middleware->alias([
            'role' => RoleMiddleware::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // #region agent log
        $exceptions->renderable(function (TokenMismatchException $e, $request) {
            DebugBfd979Log::write('H419', 'token_mismatch_419', [
                'path' => $request?->path(),
                'full_url' => $request?->fullUrl(),
                'host' => $request?->getHost(),
                'session_domain_effective' => config('session.domain'),
                'session_secure' => config('session.secure'),
                'same_site' => config('session.same_site'),
                'has_cookie' => $request?->hasHeader('Cookie'),
            ]);

            return null;
        });
        // #endregion
    })->create();
