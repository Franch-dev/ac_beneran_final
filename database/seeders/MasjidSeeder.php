<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Masjid;

class MasjidSeeder extends Seeder
{
    public function run(): void
    {
        $csvPath = database_path('seeders/data/PKL data AC - Sheet1.csv');
        if (!is_file($csvPath)) {
            throw new \RuntimeException("Seeder source not found: {$csvPath}");
        }

        $rows = array_map('str_getcsv', file($csvPath));
        $header = array_shift($rows);
        // Remove BOM if present on first header
        if (isset($header[0])) {
            $header[0] = preg_replace('/^\xEF\xBB\xBF/', '', $header[0]);
        }

        foreach ($rows as $row) {
            $d = @array_combine($header, $row);
            if (!$d || !isset($d['nama_masjid'])) {
                continue;
            }

            // Infer type from name prefix (no type column in the AC-centric CSV)
            $type = str_starts_with(strtoupper($d['nama_masjid']), 'MUSHOLLA') ? 'musholla' : 'masjid';

            $phone = trim((string) ($d['no_tlpn'] ?? ''));

            Masjid::updateOrCreate(
                ['name' => $d['nama_masjid']],
                [
                    'custom_id' => Masjid::generateCustomId($type),
                    'type' => $type,
                    'address' => trim((string) ($d['alamat'] ?? '-')),
                    'dkm_name' => trim((string) ($d['nama_dkm_marbot'] ?? '-')) ?: '-',
                    'marbot_name' => trim((string) ($d['nama_dkm_marbot'] ?? '-')) ?: '-',
                    'phone_numbers' => $phone !== '' ? [$phone] : ['-'],
                ]
            );
        }
    }
}
