<?php

namespace Database\Seeders;


use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class AnggotaSeeder extends Seeder
{
    public function run(): void
    {
        $csvPath = database_path('seeders/data/anggota.csv');

        if (! file_exists($csvPath)) {
            $this->command->warn('Anggota CSV not found at ' . $csvPath);
            return;
        }

        $connection = 'ac_service';

        // Safe truncate with FK disabled
        $schema = DB::connection($connection)->getSchemaBuilder();
        $schema->disableForeignKeyConstraints();
        DB::connection($connection)->table('anggotas')->truncate();
        $schema->enableForeignKeyConstraints();

        $file = fopen($csvPath, 'r');
        fgetcsv($file); // Skip header

        $count = 0;
        while (($row = fgetcsv($file, 1000, ',')) !== false) {
            if (count($row) < 21) {
                continue;
            }

            $phoneNumber = trim((string) ($row[9] ?? ''));
            $whatsAppNumber = trim((string) ($row[10] ?? ''));
            $address = collect([
                trim((string) ($row[12] ?? '')),
                trim((string) ($row[13] ?? '')),
                trim((string) ($row[14] ?? '')),
                trim((string) ($row[17] ?? '')),
                trim((string) ($row[18] ?? '')),
                trim((string) ($row[19] ?? '')),
                trim((string) ($row[20] ?? '')),
            ])->filter()->implode(', ');

            DB::connection($connection)->table('anggotas')->updateOrInsert(
                ['member_code' => trim((string) $row[0])],
                [
                    'custom_id' => sprintf('003-%04d', $count + 1),
                    'type' => 'anggota',
                    'registered_at' => ! empty($row[1]) ? date('Y-m-d', strtotime(str_replace('/', '-', $row[1]))) : null,
                    'name' => trim((string) $row[2]),
                    'gender' => trim((string) ($row[3] ?? '')),
                    'family_card_number' => trim((string) ($row[4] ?? '')),
                    'national_id_number' => trim((string) ($row[5] ?? '')),
                    'birth_date' => ! empty($row[6]) ? date('Y-m-d', strtotime(str_replace('/', '-', $row[6]))) : null,
                    'family_role' => trim((string) ($row[7] ?? '')),
                    'membership_status' => trim((string) ($row[8] ?? '')),
                    'phone_number' => $phoneNumber !== '' ? $phoneNumber : null,
                    'whatsapp_number' => $whatsAppNumber !== '' ? ltrim($whatsAppNumber, '62') : null,
                    'email' => trim((string) ($row[11] ?? '')) ?: null,
                    'location' => trim((string) ($row[12] ?? '')),
                    'street' => trim((string) ($row[13] ?? '')),
                    'house_number' => trim((string) ($row[14] ?? '')),
                    'rt' => trim((string) ($row[15] ?? '')),
                    'rw' => trim((string) ($row[16] ?? '')),
                    'subdistrict' => trim((string) ($row[17] ?? '')),
                    'district' => trim((string) ($row[18] ?? '')),
                    'city' => trim((string) ($row[19] ?? '')),
                    'province' => trim((string) ($row[20] ?? '')),
                    'address' => $address !== '' ? $address : null,
                    'contact_name' => trim((string) $row[2]),
                    'phone_numbers' => json_encode(array_values(array_filter([$phoneNumber, $whatsAppNumber]))),
                    'setup_status' => 'completed',
                ]
            );
            $count++;
        }

        fclose($file);

        $total = DB::connection($connection)->table('anggotas')->count();
        $this->command->info('Anggota seeded successfully (' . $count . ' records from CSV). Total: ' . $total);
    }
}
