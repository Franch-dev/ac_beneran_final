<?php

namespace Modules\AcAnggota\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\AnggotaAcUnit;
use Throwable;

class AcAnggotaMonitoringController extends Controller
{
    public function __invoke()
    {
        session(['show_role_buttons' => true, 'current_module' => 'ac-anggota']);

        try {
            $units = AnggotaAcUnit::query()
                ->with('anggota:id,name,custom_id,address')
                ->orderByDesc('updated_at')
                ->limit(100)
                ->get();
        } catch (Throwable $e) {
            report($e);
            $units = collect();
        }

        return view('ac-anggota::monitoring', compact('units'));
    }
}
