<?php

namespace Modules\FutureModule\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Support\PlatformNavigation;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Route;

class FutureModuleHomeController extends Controller
{
    public function __invoke(): View
    {
        $tracks = collect(config('future_module.tracks', []));
        $dashboardRoute = request()->routeIs('modules.future-module.subdomain.*') && Route::has('modules.future-module.subdomain.dashboard')
            ? 'modules.future-module.subdomain.dashboard'
            : 'modules.future-module.dashboard';
        $dashboardPath = route($dashboardRoute, [], false);

        return view('future-module::index', [
            'summary' => [
                'totalTracks' => $tracks->count(),
                'readyTracks' => $tracks->where('status', 'ready')->count(),
                'queuedTracks' => $tracks->where('status', 'queued')->count(),
            ],
            'dashboardUrl' => auth()->check() ? route($dashboardRoute) : PlatformNavigation::loginUrl($dashboardPath),
        ]);
    }
}
