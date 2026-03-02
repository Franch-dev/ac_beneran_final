<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Masjid;
use App\Models\AcUnit;
use Carbon\Carbon;

class AcUnitSeeder extends Seeder
{
    public function run(): void
    {
        $csvPath = database_path('seeders/data/PKL data AC - Sheet1.csv');
        $rows = array_map('str_getcsv', file($csvPath));
        $header = array_shift($rows);
        if (isset($header[0])) {
            $header[0] = preg_replace('/^\xEF\xBB\xBF/', '', $header[0]);
        }

        foreach ($rows as $row) {
            $d = @array_combine($header, $row);
            if (!$d || !isset($d['nama_masjid'])) {
                continue;
            }

            $masjid = Masjid::where('name', $d['nama_masjid'])->first();
            if (!$masjid) continue;

            // Map pk_type; default to 1PK when blank/unknown.
            $pkRaw = strtoupper(trim($d['pk_type'] ?? ''));
            $allowedPk = ['1PK', '2PK', '5PK'];
            $pkType = in_array($pkRaw, $allowedPk, true) ? $pkRaw : '1PK';

            $brand = $d['brand'] ?: 'UNKNOWN';

            $lastServiceDate = $this->normalizeDate($d['last_service_date'] ?? null);

            $quantity = (int) ($d['quantity'] ?? 0);
            if ($quantity <= 0) {
                $quantity = 1; // fallback to minimal unit so record persists
            }

            AcUnit::create([
                'masjid_id' => $masjid->id,
                'pk_type' => $pkType,
                'brand' => $brand,
                'quantity' => $quantity,
                'last_service_date' => $lastServiceDate,
            ]);
        }
    }

    private function normalizeDate(?string $value): ?string
    {
        if (!$value) return null;

        $value = trim($value);
        // Handle stray placeholders like "...-Des-2025"
        if (preg_match('/[^0-9A-Za-z-]/', $value)) {
            $value = preg_replace('/[^0-9A-Za-z-]/', '', $value);
        }
        if ($value === '') return null;

        $monthMap = [
            'JAN' => 'Jan',
            'FEB' => 'Feb',
            'MAR' => 'Mar',
            'APR' => 'Apr',
            'MEI' => 'May',
            'JUN' => 'Jun',
            'JUL' => 'Jul',
            'AGU' => 'Aug',
            'SEP' => 'Sep',
            'OKT' => 'Oct',
            'NOV' => 'Nov',
            'DES' => 'Dec',
        ];

        $value = preg_replace_callback('/[A-Za-z]+/', function ($matches) use ($monthMap) {
            $upper = strtoupper($matches[0]);
            return $monthMap[$upper] ?? $matches[0];
        }, $value);

        try {
            return Carbon::parse($value)->format('Y-m-d');
        } catch (\Exception $e) {
            return null;
        }
    }
}
