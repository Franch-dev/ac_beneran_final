<?php

namespace Modules\Inventory\Http\Controllers;

use App\Http\Controllers\Controller;

class InventoryHomeController extends Controller
{
    public function __invoke()
    {
        return view('inventory::index');
    }
}
