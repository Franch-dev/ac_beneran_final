<?php

namespace Modules\AcService\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Masjid;

class AcServiceHomeController extends Controller
{

    public function __invoke()
    {
        if (auth()->check()) {
            session(['show_role_buttons' => true]);
        }

        $masjids = Masjid::query()
            ->orderBy('name')
            ->get(['id', 'name', 'custom_id']);

        return view('ac-service::index', compact('masjids'));
    }

}
