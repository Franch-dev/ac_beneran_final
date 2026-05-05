<?php

namespace Modules\AcMasjidMusholla\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\AcUnit;
use App\Models\Masjid;
use App\Support\PlatformNavigation;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;
use Throwable;

class AcMasjidMushollaHomeController extends Controller
{
    public function __invoke()
    {
        // Redirect authenticated users to cardhome with sidebar
        if (auth()->check()) {
            return redirect()->route('modules.ac-masjid-musholla.card');
        }

        $metrics = $this->metrics();
        $metrics['masjids'] = $this->availableMasjids();
        $metrics = array_merge($metrics, $this->navigation());
        $metrics = array_merge($metrics, $this->catalogModules());

        return view('ac-masjid-musholla::cardhome', $metrics);
    }

    public function card()
    {
        session([
            'show_role_buttons' => true,
            'current_module' => 'ac-masjid-musholla'
        ]);

        $metrics = $this->metrics();
        $metrics['masjids'] = $this->availableMasjids();
        $metrics = array_merge($metrics, $this->navigationCard());

        return view('ac-masjid-musholla::cardhome', $metrics);
    }

    protected function metrics(): array
    {
        try {
            return [
                'totalMasjid' => Masjid::count(),
                'totalUnit' => (int) AcUnit::sum('quantity'),
            ];
        } catch (Throwable $e) {
            report($e);

            return ['totalMasjid' => 0, 'totalUnit' => 0];
        }
    }

    protected function availableMasjids()
    {
        return Masjid::query()
            ->orderBy('name')
            ->get(['id', 'name', 'custom_id']);
    }

    protected function navigation(): array
    {
        $dashboardRoute = request()->routeIs('modules.ac-masjid-musholla.subdomain.*') && Route::has('modules.ac-masjid-musholla.subdomain.dashboard')
            ? 'modules.ac-masjid-musholla.subdomain.dashboard'
            : 'modules.ac-masjid-musholla.dashboard';
        $monitoringRoute = request()->routeIs('modules.ac-masjid-musholla.subdomain.*') && Route::has('modules.ac-masjid-musholla.subdomain.monitoring')
            ? 'modules.ac-masjid-musholla.subdomain.monitoring'
            : 'modules.ac-masjid-musholla.monitoring';
        $dashboardPath = route($dashboardRoute, [], false);
        $monitoringPath = route($monitoringRoute, [], false);
$landingRoute = 'home';

        return [
            'dashboardUrl' => auth()->check() ? route($dashboardRoute) : PlatformNavigation::loginUrl($dashboardPath),
            'monitoringUrl' => auth()->check() ? route($monitoringRoute) : PlatformNavigation::loginUrl($monitoringPath),
            'landingpageUrl' => route($landingRoute),
        ];
    }

    protected function navigationCard(): array
    {
        $dashboardRoute = 'modules.ac-masjid-musholla.dashboard';
        $monitoringRoute = 'modules.ac-masjid-musholla.monitoring';
        $homeRoute = 'home';

        return [
            'dashboardUrl' => auth()->check() ? route($dashboardRoute) : PlatformNavigation::loginUrl(route($dashboardRoute, [], false)),
            'monitoringUrl' => auth()->check() ? route($monitoringRoute) : PlatformNavigation::loginUrl(route($monitoringRoute, [], false)),
            'landingpageUrl' => route($homeRoute),
        ];
    }

    protected function catalogModules(): array
    {
        try {
            $guestOrderRoutes = [
                'ac-service' => [
                    'index' => 'modules.ac-service.guest-order.index',
                    'store' => 'modules.ac-service.guest-order.store',
                ],
                'ac-anggota' => [
                    'index' => 'modules.ac-anggota.guest-order.index',
                    'store' => 'modules.ac-anggota.guest-order.store',
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

            return [
                'catalogModules' => $catalogModules,
                'catalogCount' => $catalogModules->count(),
                'manualRating' => '4.7',
            ];
        } catch (Throwable $e) {
            report($e);
            return ['catalogModules' => collect([]), 'catalogCount' => 0, 'manualRating' => '4.7'];
        }
    }
}

