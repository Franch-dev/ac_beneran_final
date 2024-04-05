<?php

namespace Modules\Inventory\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Support\PlatformNavigation;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Route;

class InventoryHomeController extends Controller
{
    public function __invoke(): View
    {
        $assets = collect(config('inventory.assets', []));
        $dashboardRoute = request()->routeIs('modules.inventory.subdomain.*') && Route::has('modules.inventory.subdomain.dashboard')
            ? 'modules.inventory.subdomain.dashboard'
            : 'modules.inventory.dashboard';
        $dashboardPath = route($dashboardRoute, [], false);

        return view('inventory::index', [
            'summary' => [
                'totalAssets' => $assets->count(),
                'totalQuantity' => (int) $assets->sum('quantity'),
                'categories' => $assets->pluck('category')->filter()->unique()->count(),
            ],
            'dashboardUrl' => auth()->check() ? route($dashboardRoute) : PlatformNavigation::loginUrl($dashboardPath),
        ]);
    }
}
