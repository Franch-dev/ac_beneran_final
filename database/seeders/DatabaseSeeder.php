<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Masjid;
use App\Models\AcUnit;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // Bersihkan data dummy AC & Masjid jika ada
        AcUnit::query()->delete();
        \Illuminate\Support\Facades\DB::table('service_details')->delete();
        \Illuminate\Support\Facades\DB::table('service_orders')->delete();
        \Illuminate\Support\Facades\DB::table('invoices')->delete();
        Masjid::withTrashed()->forceDelete();

        // Buat user (gunakan firstOrCreate agar tidak duplikat)
        User::firstOrCreate(['email' => 'frontdesk@example.com'], [
            'name' => 'Frontdesk Operator',
            'password' => Hash::make('password'),
            'role' => 'frontdesk',
        ]);

        User::firstOrCreate(['email' => 'manager@example.com'], [
            'name' => 'Manager Utama',
            'password' => Hash::make('password'),
            'role' => 'manager',
        ]);

        User::firstOrCreate(['email' => 'admin@example.com'], [
            'name' => 'Administrator',
            'password' => Hash::make('password'),
            'role' => 'admin',
        ]);
        // Tidak ada dummy masjid / AC — data real diisi melalui aplikasi
        $this->call([
        MasjidSeeder::class,
        AcUnitSeeder::class,
        ]);
    }
}
