<?php

namespace App\Http\Controllers;

use App\Models\Masjid;
use App\Models\AcUnit;
use App\Support\InternalRedirectPath;
use App\Support\PlatformNavigation;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;
use Throwable;

class HomeController extends Controller
{
    public function index()
    {
        if (auth()->check() && ! session('show_role_buttons')) {
            session(['show_role_buttons' => false]);
        }

        [$totalMasjid, $totalUnit] = $this->resolveAcServiceMetrics();
        $manualRating = '4.7';
        $masjids = $this->resolveMasjidOptions();

        $guestOrderRoutes = [
            'ac-service' => [
                'index' => 'modules.ac-service.guest-order.index',
                'store' => 'modules.ac-service.guest-order.store',
            ],
            'ac-masjid-musholla' => [
                'index' => 'modules.ac-masjid-musholla.guest-order.index',
                'store' => 'modules.ac-masjid-musholla.guest-order.store',
            ],
        ];

        $catalogModules = collect(config('modules.catalog', []))->map(function (array $module) use ($guestOrderRoutes) {
            $routeName = $module['route'] ?? null;
            $subdomainRouteName = $routeName
                ? Str::replaceLast('.index', '.subdomain.index', $routeName)
                : null;
            $dashboardRouteName = $module['dashboard_route'] ?? null;

            if (! empty($module['subdomain']) && $subdomainRouteName && Route::has($subdomainRouteName)) {
                $module['url'] = route($subdomainRouteName);
            } elseif ($routeName && Route::has($routeName)) {
                $module['url'] = route($routeName);
            } else {
                $module['url'] = $module['path'] ?? '#';
            }

            if ($dashboardRouteName && Route::has($dashboardRouteName)) {
                $module['dashboard_url'] = route($dashboardRouteName);
            } else {
                $module['dashboard_url'] = null;
            }

            $module['login_redirect'] = InternalRedirectPath::normalize($module['login_redirect'] ?? null);
            $module['login_url'] = PlatformNavigation::loginUrl($module['login_redirect']);
            $module['cta_label'] = auth()->check()
                ? ($module['auth_cta'] ?? 'Buka Hub')
                : ($module['guest_cta'] ?? 'Buka Website');

            if (! empty($module['key']) && isset($guestOrderRoutes[$module['key']])) {
                $module['guest_order_url'] = Route::has($guestOrderRoutes[$module['key']]['index'])
                    ? route($guestOrderRoutes[$module['key']]['index'])
                    : null;
                $module['guest_order_store_route_name'] = $guestOrderRoutes[$module['key']]['store'];
            }

            $module['domain_label'] = $module['subdomain']
                ?: ltrim((string) ($module['path'] ?? ''), '/');

            return $module;
        });

        $catalogCount = $catalogModules->count();

        return view('home', compact('totalMasjid', 'totalUnit', 'manualRating', 'catalogModules', 'catalogCount', 'masjids'));
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

    protected function resolveMasjidOptions()
    {
        try {
            return Masjid::query()
                ->with('acUnits')
                ->orderBy('name')
                ->get([
                    'id',
                    'name',
                    'custom_id',
                    'type',
                    'address',
                    'dkm_name',
                    'marbot_name',
                    'phone_numbers',
                ]);
        } catch (Throwable $exception) {
            report($exception);

            return collect();
        }
    }
}
