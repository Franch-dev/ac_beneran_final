<?php

namespace Modules\AcMasjidMusholla\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\AcUnit;
use App\Models\Masjid;
use Throwable;

class AcMasjidMushollaHomeController extends Controller
{
    public function __invoke()
    {
        $metrics = $this->metrics();

        return view('ac-masjid-musholla::home', $metrics);
    }

    protected function metrics(): array
    {
        try {
            return [
                'totalMasjid' => Masjid::count(),
                'totalUnit' => (int) AcUnit::sum('quantity'),
            ];
        } catch (Throwable $e) {
            report($e);

            return ['totalMasjid' => 0, 'totalUnit' => 0];
        }
    }
}
