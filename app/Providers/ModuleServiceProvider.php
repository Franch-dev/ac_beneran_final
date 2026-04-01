<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;

class ModuleServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $modulePaths = glob(base_path('Modules/*'), GLOB_ONLYDIR) ?: [];
        sort($modulePaths);

        foreach ($modulePaths as $modulePath) {
            $namespace = Str::of(basename($modulePath))->snake('-')->toString();

            $viewsPath = $modulePath.'/resources/views';
            if (is_dir($viewsPath)) {
                $this->loadViewsFrom($viewsPath, $namespace);
            }

            $migrationsPath = $modulePath.'/database/migrations';
            if (is_dir($migrationsPath)) {
                $this->loadMigrationsFrom($migrationsPath);
            }
        }
    }
}
