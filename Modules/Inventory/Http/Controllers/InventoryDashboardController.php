<?php

namespace Modules\Inventory\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;

class InventoryDashboardController extends Controller
{
    public function __invoke(): View
    {
        session(['show_role_buttons' => true]);

        $assets = collect(config('inventory.assets', []));
        $summary = $this->summary($assets);

        return view('inventory::dashboard', [
            'assets' => $assets,
            'summary' => $summary,
        ]);
    }

    protected function summary(Collection $assets): array
    {
        return [
            'totalAssets' => $assets->count(),
            'totalQuantity' => (int) $assets->sum('quantity'),
            'categories' => $assets->pluck('category')->filter()->unique()->count(),
            'lowStock' => $assets->where('status', 'low')->count(),
        ];
    }
}
