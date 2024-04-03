<?php

namespace Modules\AcAnggota\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Anggota;
use App\Models\AnggotaAcUnit;
use App\Support\PlatformNavigation;
use Illuminate\Support\Facades\Route;
use Throwable;

class AcAnggotaHomeController extends Controller
{
    public function __invoke()
    {
        // Redirect authenticated users to cardhome with sidebar
        if (auth()->check()) {
            return redirect()->route('modules.ac-anggota.card');
        }

        // Guest: redirect to cardhome
        return redirect()->route('modules.ac-anggota.card');
    }

    public function card()
    {
        session([
            'show_role_buttons' => true,
            'current_module' => 'ac-anggota'
        ]);

        $metrics = $this->metrics();
        $metrics['masjids'] = $this->availableMasjids();
        $metrics = array_merge($metrics, $this->navigationCard());

        return view('ac-anggota::cardhome', $metrics);
    }

    protected function metrics(): array
    {
        try {
            return [
                'totalAnggota' => Anggota::count(),
                'totalUnit' => (int) AnggotaAcUnit::sum('quantity'),
            ];
        } catch (Throwable $e) {
            report($e);

            return ['totalAnggota' => 0, 'totalUnit' => 0];
        }
    }

    protected function navigation(): array
    {
        $dashboardRoute = request()->routeIs('modules.ac-anggota.subdomain.*') && Route::has('modules.ac-anggota.subdomain.dashboard')
            ? 'modules.ac-anggota.subdomain.dashboard'
            : 'modules.ac-anggota.dashboard';
        $monitoringRoute = request()->routeIs('modules.ac-anggota.subdomain.*') && Route::has('modules.ac-anggota.subdomain.monitoring')
            ? 'modules.ac-anggota.subdomain.monitoring'
            : 'modules.ac-anggota.monitoring';
        $dashboardPath = route($dashboardRoute, [], false);
        $monitoringPath = route($monitoringRoute, [], false);

        return [
            'dashboardUrl' => auth()->check() ? route($dashboardRoute) : PlatformNavigation::loginUrl($dashboardPath),
            'monitoringUrl' => auth()->check() ? route($monitoringRoute) : PlatformNavigation::loginUrl($monitoringPath),
        ];
    }

    protected function navigationCard(): array
    {
        $dashboardRoute = 'modules.ac-anggota.dashboard';
        $monitoringRoute = 'modules.ac-anggota.monitoring';
        $homeRoute = 'home';

        return [
            'dashboardUrl' => auth()->check() ? route($dashboardRoute) : PlatformNavigation::loginUrl(route($dashboardRoute, [], false)),
            'monitoringUrl' => auth()->check() ? route($monitoringRoute) : PlatformNavigation::loginUrl(route($monitoringRoute, [], false)),
            'landingpageUrl' => route($homeRoute),
        ];
    }

    protected function availableMasjids()
    {
        return \App\Models\Masjid::query()
            ->orderBy('name')
            ->get(['id', 'name', 'custom_id']);
    }
}
