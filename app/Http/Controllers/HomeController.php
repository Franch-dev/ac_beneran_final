<?php

namespace App\Http\Controllers;

use App\Models\Masjid;
use App\Models\AcUnit;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;
use Throwable;

class HomeController extends Controller
{
    public function index()
    {
        [$totalMasjid, $totalUnit] = $this->resolveAcServiceMetrics();
        $manualRating = '4.7';

        $catalogModules = collect(config('modules.catalog', []))->map(function (array $module) {
            $routeName = $module['route'] ?? null;
            $subdomainRouteName = $routeName
                ? Str::replaceLast('.index', '.subdomain.index', $routeName)
                : null;

            if (! empty($module['subdomain']) && $subdomainRouteName && Route::has($subdomainRouteName)) {
                $module['url'] = route($subdomainRouteName);
            } elseif ($routeName && Route::has($routeName)) {
                $module['url'] = route($routeName);
            } else {
                $module['url'] = $module['path'] ?? '#';
            }

            $module['domain_label'] = $module['subdomain']
                ?: ltrim((string) ($module['path'] ?? ''), '/');

            return $module;
        });

        $catalogCount = $catalogModules->count();

        return view('home', compact('totalMasjid', 'totalUnit', 'manualRating', 'catalogModules', 'catalogCount'));
    }

    protected function resolveAcServiceMetrics(): array
    {
        try {
            return [
                Masjid::count(),
                (int) AcUnit::sum('quantity'),
            ];
        } catch (Throwable $exception) {
            report($exception);

            return [0, 0];
        }
    }
}
