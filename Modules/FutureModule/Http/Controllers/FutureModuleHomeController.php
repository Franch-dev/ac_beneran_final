<?php

namespace Modules\FutureModule\Http\Controllers;

use App\Http\Controllers\Controller;

class FutureModuleHomeController extends Controller
{
    public function __invoke()
    {
        return view('future-module::index');
    }
}
