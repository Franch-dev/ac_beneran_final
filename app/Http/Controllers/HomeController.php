<?php

namespace App\Http\Controllers;

use App\Models\Masjid;
use App\Models\AcUnit;
use App\Models\ServiceOrder;

class HomeController extends Controller
{
    public function index()
    {
        $totalMasjid = Masjid::count();
        $totalUnit = AcUnit::sum('quantity');
        $totalServis = ServiceOrder::count();
        $manualRating = '4.7';

        return view('home', compact('totalMasjid', 'totalUnit', 'totalServis', 'manualRating'));
    }
}
