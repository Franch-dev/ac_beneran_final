<?php

namespace App\Providers;

use App\Support\PlatformNavigation;
use Illuminate\Support\ServiceProvider;
use Illuminate\Console\Events\CommandStarting;
use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Http\Request;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\View;
use PDO;

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
        View::share('platformHomeUrl', PlatformNavigation::homeUrl());
        View::share('platformCatalogUrl', PlatformNavigation::catalogUrl());

        RateLimiter::for('login', function (Request $request) {
            $email = (string) $request->input('email', '');
            $throttleKey = strtolower($email).'|'.$request->ip();

            return [
                Limit::perMinute(5)->by($throttleKey),
            ];
        });

        RateLimiter::for('writes', function (Request $request) {
            $userKey = auth()->check() ? 'user:'.auth()->id() : 'ip:'.$request->ip();
            $routeKey = (string) ($request->route()?->getName() ?? $request->path());

            return [
                Limit::perMinute(90)->by($userKey.'|'.$routeKey),
            ];
        });

        DB::whenQueryingForLongerThan(250, function ($connection, QueryExecuted $event): void {
            logger()->warning('slow_query_detected', [
                'connection' => $connection->getName(),
                'time_ms' => $event->time,
                'sql' => $event->sql,
            ]);
        });

        // Session cookie must match the browser host and protocol or it will not be stored,
        // which yields a new session on POST → CSRF token mismatch (419 Page Expired).
        if (! $this->app->runningInConsole()) {
            $request = request();
            $sessionDomain = config('session.domain');
            if (is_string($sessionDomain) && $sessionDomain !== '') {
                $plain = ltrim($sessionDomain, '.');
                if ($plain !== '' && ! str_ends_with($request->getHost(), $plain)) {
                    config(['session.domain' => null]);
                }
            }

            if ($this->app->environment('local') && ! $request->secure()) {
                if (config('session.secure')) {
                    config(['session.secure' => false]);
                }
                if (strtolower((string) config('session.same_site')) === 'none') {
                    config(['session.same_site' => 'lax']);
                }
            }
        }

        Event::listen(CommandStarting::class, function (CommandStarting $event) {
            if (in_array($event->command, ['migrate', 'migrate:fresh', 'migrate:refresh', 'db:seed'])) {
                try {
                    $connectionNames = ['main', 'ac_service', 'inventory'];
                    $created = [];

                    foreach ($connectionNames as $name) {
                        $config = config("database.connections.{$name}");
                        if (! is_array($config) || ($config['driver'] ?? null) !== 'mysql') {
                            continue;
                        }

                        $host = (string) ($config['host'] ?? '127.0.0.1');
                        $port = (string) ($config['port'] ?? '3306');
                        $database = (string) ($config['database'] ?? '');
                        $username = (string) ($config['username'] ?? 'root');
                        $password = (string) ($config['password'] ?? '');

                        if ($database === '') {
                            continue;
                        }

                        $pdoKey = "{$host}:{$port}:{$username}";
                        if (! isset($created[$pdoKey])) {
                            $created[$pdoKey] = new PDO(
                                "mysql:host={$host};port={$port}",
                                $username,
                                $password,
                                [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
                            );
                        }

                        $quotedDb = str_replace('`', '``', $database);
                        $created[$pdoKey]->exec(
                            "CREATE DATABASE IF NOT EXISTS `{$quotedDb}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
                        );
                    }
                } catch (\Exception $e) {
                    // Let Laravel handle normal errors if this fails
                }
            }
        });
    }
}
