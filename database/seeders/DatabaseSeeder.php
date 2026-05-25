<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        if (app()->environment('production')) {
            throw new \RuntimeException('DatabaseSeeder creates demo users and must not run in production.');
        }

        $this->truncateAcServiceTables();

        User::updateOrCreate(['email' => 'frontdesk@example.com'], [
            'name' => 'Frontdesk Operator',
            'password' => Hash::make('password'),
            'role' => 'frontdesk',
        ]);

        User::updateOrCreate(['email' => 'manager@example.com'], [
            'name' => 'Manager Utama',
            'password' => Hash::make('password'),
            'role' => 'manager',
        ]);

        User::updateOrCreate(['email' => 'admin@example.com'], [
            'name' => 'Administrator',
            'password' => Hash::make('password'),
            'role' => 'admin',
        ]);

        User::updateOrCreate(['email' => 'teknisi@example.com'], [
            'name' => 'Teknisi Lapangan',
            'password' => Hash::make('password'),
            'role' => 'technician',
        ]);

        User::updateOrCreate(['email' => 'teknisi2@example.com'], [
            'name' => 'Teknisi Cadangan',
            'password' => Hash::make('password'),
            'role' => 'technician',
        ]);

        User::updateOrCreate(['email' => 'viewer@example.com'], [
            'name' => 'Auditor Viewer',
            'password' => Hash::make('password'),
            'role' => 'viewer',
        ]);

        $this->call([
            MasjidSeeder::class,
            AnggotaSeeder::class,
            AcUnitSeeder::class,
            ServiceOrderSeeder::class,
        ]);
    }

    private function truncateAcServiceTables(): void
    {
        $connection = DB::connection('ac_service');
        $schema = $connection->getSchemaBuilder();
        $schema->disableForeignKeyConstraints();

        foreach ([
            'photo_proofs',
            'receipts',
            'invoice_edits',
            'workflow_steps',
            'masjid_change_requests',
            'guest_orders',
            'technician_assignments',
            'invoices',
            'service_details',
            'service_order_histories',
            'sync_events',
            'service_orders',
            'anggota_service_orders',
            'anggota_ac_units',
            'ac_units',
            'masjids',
            'anggotas',
        ] as $table) {
            if ($schema->hasTable($table)) {
                $connection->table($table)->truncate();
            }
        }

        $schema->enableForeignKeyConstraints();
    }
}
