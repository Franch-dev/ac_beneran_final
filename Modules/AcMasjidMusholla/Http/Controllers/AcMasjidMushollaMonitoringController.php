<?php

namespace Modules\AcMasjidMusholla\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\AcUnit;
use Throwable;

class AcMasjidMushollaMonitoringController extends Controller
{
    public function __invoke()
    {
        try {
            $units = AcUnit::query()
                ->with('masjid:id,name,type,custom_id')
                ->orderByDesc('updated_at')
                ->limit(100)
                ->get();
        } catch (Throwable $e) {
            report($e);
            $units = collect();
        }

        return view('ac-masjid-musholla::monitoring', compact('units'));
    }
}
