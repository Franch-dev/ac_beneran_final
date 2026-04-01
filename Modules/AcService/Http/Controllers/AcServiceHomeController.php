<?php

namespace Modules\AcService\Http\Controllers;

use App\Http\Controllers\Controller;

class AcServiceHomeController extends Controller
{
    public function __invoke()
    {
        return view('ac-service::index');
    }
}
