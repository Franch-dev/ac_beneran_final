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
            AcUnitSeeder::class,
            ServiceOrderSeeder::class,
        ]);
    }

    private function truncateAcServiceTables(): void
    {
        $connection = DB::connection('ac_service');

        $connection->statement('SET FOREIGN_KEY_CHECKS=0');

        foreach ([
            'workflow_steps',
            'technician_assignments',
            'invoices',
            'service_details',
            'service_orders',
            'ac_units',
            'masjids',
        ] as $table) {
            $connection->table($table)->truncate();
        }

        $connection->statement('SET FOREIGN_KEY_CHECKS=1');
    }
}
