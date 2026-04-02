<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Console\Events\CommandStarting;
use Illuminate\Support\Facades\Event;
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
                    $host = env('DB_HOST', '127.0.0.1');
                    $port = env('DB_PORT', '3306');
                    $username = env('DB_USERNAME', 'root');
                    $password = env('DB_PASSWORD', '');

                    $databases = array_filter([
                        env('DB_DATABASE', 'main_platform'),
                        env('MAIN_DB_DATABASE', 'main_platform'),
                        env('AC_SERVICE_DB_DATABASE', 'ac_masjid_db'),
                        env('INVENTORY_DB_DATABASE', 'inventory_db')
                    ]);

                    $pdo = new PDO("mysql:host=$host;port=$port", $username, $password);
                    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

                    foreach (array_unique($databases) as $database) {
                        $pdo->exec("CREATE DATABASE IF NOT EXISTS `$database` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;");
                    }
                } catch (\Exception $e) {
                    // Let Laravel handle normal errors if this fails
                }
            }
        });
    }
}
