<?php

use App\Http\Middleware\RoleMiddleware;
use App\Http\Middleware\CspReportOnlyMiddleware;
use App\Http\Middleware\SecurityHeadersMiddleware;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Support\Facades\Log;
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
        $exceptions->renderable(function (TokenMismatchException $e, $request) {
            Log::warning('csrf_token_mismatch', [
                'path' => $request?->path(),
                'host' => $request?->getHost(),
                'has_cookie' => $request?->hasHeader('Cookie'),
            ]);

            return null;
        });
    })->create();
