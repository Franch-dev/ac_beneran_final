<?php

namespace Modules\FutureModule\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;

class FutureModuleDashboardController extends Controller
{
    public function __invoke(): View
    {
        session(['show_role_buttons' => true]);

        $tracks = collect(config('future_module.tracks', []));
        $summary = $this->summary($tracks);

        return view('future-module::dashboard', [
            'tracks' => $tracks,
            'summary' => $summary,
        ]);
    }

    protected function summary(Collection $tracks): array
    {
        return [
            'totalTracks' => $tracks->count(),
            'readyTracks' => $tracks->where('status', 'ready')->count(),
            'queuedTracks' => $tracks->where('status', 'queued')->count(),
        ];
    }
}
