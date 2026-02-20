<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
use App\Models\ServiceOrder;

class InvoiceController extends Controller
{
    public function print(ServiceOrder $serviceOrder)
    {
        $serviceOrder->load('masjid', 'serviceDetails', 'invoice');
        if (!$serviceOrder->invoice) {
            abort(404, 'Invoice tidak ditemukan');
        }
        return view('invoice', compact('serviceOrder'));
    }

    public function spk(ServiceOrder $serviceOrder)
    {
        $serviceOrder->load('masjid', 'serviceDetails');
        return view('spk', compact('serviceOrder'));
    }
}
